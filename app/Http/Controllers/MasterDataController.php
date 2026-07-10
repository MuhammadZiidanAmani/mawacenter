<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportStudentsRequest;
use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillManualPayment;
use App\Models\BillPaymentAllocation;
use App\Models\EducationUnit;
use App\Models\FeeDiscount;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\SppPaymentCorrection;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Models\User;
use App\Services\ChargeCalculator;
use App\Services\StudentImportService;
use App\Support\ClassLevel;
use App\Support\StudentXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->value() ?: 'academic-years';
        if ($tab === 'spp-settings') {
            $tab = 'fee-types';
        }
        if ($tab === 'students' && $request->routeIs('master.index')) {
            return redirect()->route('student-management.students.index', $request->except('tab'));
        }
        if ($tab === 'students' && $request->routeIs('master.create')) {
            return redirect()->route('student-management.students.create');
        }
        $search = $request->string('search')->value();
        $perPage = $this->perPage($request);
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $studentYearId = $request->integer('year_id') ?: $activeAcademicYear?->id;
        $studentStatus = 'active';
        $classYearId = $tab === 'classes' ? ($request->integer('year_id') ?: $activeAcademicYear?->id) : null;
        $classStatus = $tab === 'classes' ? $request->query('status', 'active') : null;
        $feeTypeClassId = $tab === 'fee-types' ? ($request->integer('class_id') ?: null) : null;
        $feeTypeClass = $feeTypeClassId ? SchoolClass::find($feeTypeClassId) : null;
        $feeTypeClassUnitId = $feeTypeClass?->education_unit_id;
        $feeTypeClassLevel = $feeTypeClass ? ClassLevel::key($feeTypeClass->level ?: $feeTypeClass->name) : null;
        $feeTypeYearId = $tab === 'fee-types' ? ($request->integer('year_id') ?: $activeAcademicYear?->id) : null;
        $feeTypeStatus = $tab === 'fee-types' ? $request->query('status', 'active') : null;
        $feeDiscountYearId = $tab === 'fee-discounts' ? ($request->integer('year_id') ?: $activeAcademicYear?->id) : null;
        $feeDiscountStatus = $tab === 'fee-discounts' ? $request->query('status', 'active') : null;
        $studentSort = in_array($request->string('sort')->value(), ['nis', 'name', 'gender', 'unit', 'class'], true)
            ? $request->string('sort')->value()
            : 'name';
        $studentSortDirection = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';
        $listSort = $request->string('sort')->value();
        $listDirection = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';

        $data = match ($tab) {
            'academic-years' => AcademicYear::withCount('students')
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderByDesc('name')
                ->paginate($perPage)->withQueryString(),
            'education-units' => EducationUnit::withCount('schoolClasses')
                ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
                ->when(
                    in_array($listSort, ['code', 'name', 'school_classes_count', 'is_active'], true),
                    fn ($query) => $query->orderBy($listSort, $listDirection),
                    fn ($query) => $query
                        ->orderByRaw($this->educationUnitOrderExpression())
                        ->orderBy('name')
                )->paginate($perPage)->withQueryString(),
            'classes' => (function () use ($request, $search, $classYearId, $classStatus, $listSort, $listDirection, $perPage) {
                $query = SchoolClass::select('school_classes.*')->with(['educationUnit'])->withCount([
                    'students' => fn ($query) => $query
                        ->when($classYearId, fn ($studentQuery, $yearId) => $studentQuery->where('academic_year_id', $yearId)),
                ])
                    ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('school_classes.name', 'like', "%{$search}%")->orWhere('school_classes.level', 'like', "%{$search}%")))
                    ->when($request->integer('unit_id'), fn ($query, $unitId) => $query->where('school_classes.education_unit_id', $unitId))
                    ->when($classStatus !== null && $classStatus !== '', fn ($query) => $query->where('school_classes.is_active', $classStatus === 'active'))
                    ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                    ->when(
                        in_array($listSort, ['name', 'unit', 'students_count', 'is_active'], true),
                        fn ($query) => $query->orderBy(match ($listSort) {
                            'unit' => 'education_units.name',
                            'students_count' => 'students_count',
                            'is_active' => 'school_classes.is_active',
                            default => 'school_classes.name',
                        }, $listDirection),
                        fn ($query) => $query
                            ->orderByRaw(str_replace('code', 'education_units.code', $this->educationUnitOrderExpression()))
                            ->orderBy('education_units.name')->orderBy('school_classes.name')
                    );

                $classPerPage = $request->query('per_page') === 'all'
                    ? max(1, (clone $query)->count())
                    : ($request->has('per_page') ? $perPage : 25);

                return $query->paginate($classPerPage)->withQueryString();
            })(),
            'fee-types' => (function () use ($request, $search, $feeTypeClassId, $feeTypeClassUnitId, $feeTypeClassLevel, $feeTypeYearId, $feeTypeStatus, $listSort, $listDirection, $perPage) {
                $query = FeeType::with(['educationUnit', 'schoolClass', 'academicYear'])
                    ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
                    ->when($request->integer('unit_id'), fn ($query, $unitId) => $query->where('education_unit_id', $unitId))
                    ->when($feeTypeClassId && $feeTypeClassUnitId, fn ($query) => $query
                        ->where('education_unit_id', $feeTypeClassUnitId)
                        ->where(fn ($feeType) => $feeType
                            ->where('school_class_id', $feeTypeClassId)
                            ->orWhere('class_level', $feeTypeClassLevel)
                            ->orWhere(fn ($scope) => $scope->whereNull('school_class_id')->whereNull('class_level'))))
                    ->when($feeTypeYearId, fn ($query, $yearId) => $query
                        ->where(fn ($feeType) => $feeType
                            ->where('academic_year_id', $yearId)
                            ->orWhereNull('academic_year_id')))
                    ->when($feeTypeStatus !== null && $feeTypeStatus !== '', fn ($query) => $query->where('is_active', $feeTypeStatus === 'active'))
                    ->orderBy(match ($listSort) {
                        'name' => 'name', 'unit' => 'education_unit_id', 'class' => 'class_level',
                        'year' => 'academic_year_id', 'amount' => 'amount', 'is_active' => 'is_active',
                        default => 'education_unit_id',
                    }, $listSort ? $listDirection : 'asc')
                    ->orderBy('name');

                $feeTypePerPage = $request->query('per_page') === 'all'
                    ? max(1, (clone $query)->count())
                    : ($request->has('per_page') ? $perPage : 25);

                return $query->paginate($feeTypePerPage)->withQueryString();
            })(),
            'fee-discounts' => (function () use ($request, $search, $feeDiscountYearId, $feeDiscountStatus, $listSort, $listDirection, $perPage) {
                $query = FeeDiscount::with(['student.schoolClass.educationUnit', 'feeType'])
                    ->when($search, fn ($query) => $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")))
                    ->when($request->integer('unit_id'), fn ($query, $unitId) => $query->whereHas('student.schoolClass', fn ($class) => $class->where('education_unit_id', $unitId)))
                    ->when($request->integer('class_id'), fn ($query, $classId) => $query->whereHas('student', fn ($student) => $student->where('school_class_id', $classId)))
                    ->when($feeDiscountYearId, fn ($query, $yearId) => $query->whereHas('student', fn ($student) => $student->where('academic_year_id', $yearId)))
                    ->when($feeDiscountStatus !== null && $feeDiscountStatus !== '', fn ($query) => $query->where('is_active', $feeDiscountStatus === 'active'))
                    ->orderBy(match ($listSort) {
                        'student' => 'student_id', 'payment' => 'source_type',
                        'discount' => 'discount_value', 'is_active' => 'is_active',
                        default => 'created_at',
                    }, $listSort ? $listDirection : 'desc');

                $feeDiscountPerPage = $request->query('per_page') === 'all'
                    ? max(1, (clone $query)->count())
                    : ($request->has('per_page') ? $perPage : 25);

                return $query->paginate($feeDiscountPerPage)->withQueryString();
            })(),
            'data-roles' => (function () use ($request, $search, $listSort, $listDirection, $perPage) {
                $query = Role::withCount('users')
                    ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('key', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")))
                    ->orderBy(match ($listSort) {
                        'name' => 'name',
                        'key' => 'key',
                        'users_count' => 'users_count',
                        'is_active' => 'is_active',
                        default => 'name',
                    }, $listSort ? $listDirection : 'asc');

                $rolePerPage = $request->query('per_page') === 'all'
                    ? max(1, (clone $query)->count())
                    : ($request->has('per_page') ? $perPage : 25);

                return $query->paginate($rolePerPage)->withQueryString();
            })(),
            'data-users' => (function () use ($request, $search, $listSort, $listDirection, $perPage) {
                $query = User::query()
                    ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('role', 'like', "%{$search}%")))
                    ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
                    ->orderBy(match ($listSort) {
                        'name' => 'name',
                        'username' => 'username',
                        'email' => 'email',
                        'role' => 'role',
                        default => 'name',
                    }, $listSort ? $listDirection : 'asc');

                $userPerPage = $request->query('per_page') === 'all'
                    ? max(1, (clone $query)->count())
                    : ($request->has('per_page') ? $perPage : 25);

                return $query->paginate($userPerPage)->withQueryString();
            })(),
            default => Student::select('students.*')->with(['schoolClass.educationUnit'])
                ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('students.name', 'like', "%{$search}%")->orWhere('students.nis', 'like', "%{$search}%")->orWhere('students.nisn', 'like', "%{$search}%")))
                ->when($request->integer('class_id'), fn ($query, $classId) => $query->where('students.school_class_id', $classId))
                ->when($request->integer('year_id'), fn ($query, $yearId) => $query->where('students.academic_year_id', $yearId))
                ->where('students.is_active', true)
                ->when($request->integer('unit_id'), fn ($query, $unitId) => $query->whereHas('schoolClass', fn ($q) => $q->where('education_unit_id', $unitId)))
                ->when(in_array($studentSort, ['unit', 'class'], true), fn ($query) => $query
                    ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id'))
                ->when($studentSort === 'unit', fn ($query) => $query
                    ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
                    ->orderByRaw(str_replace('code', 'education_units.code', $this->educationUnitOrderExpression())." {$studentSortDirection}")
                    ->orderBy('education_units.name', $studentSortDirection)
                    ->orderBy('students.name'))
                ->when($studentSort === 'class', fn ($query) => $query
                    ->orderBy('school_classes.name', $studentSortDirection)
                    ->orderBy('students.name'))
                ->when(in_array($studentSort, ['nis', 'name', 'gender'], true), fn ($query) => $query
                    ->orderBy('students.'.$studentSort, $studentSortDirection))
                ->paginate($perPage)->withQueryString(),
        };

        if ($tab === 'fee-discounts') {
            $calculator = app(ChargeCalculator::class);
            $data->getCollection()->each(function (FeeDiscount $discount) use ($calculator) {
                $original = $calculator->baseAmount($discount->student, $discount->source_type, $discount->feeType);
                $discount->setAttribute('original_amount', $original);
                $discount->setAttribute('discount_amount', $discount->discountAmount($original));
                $discount->setAttribute('final_amount', max(0, $original - $discount->discountAmount($original)));
            });
        }

        $studentClassAlumniCount = 0;
        $studentClassAlumniClass = null;
        $studentClassAlumniYear = null;
        if ($tab === 'students' && $studentStatus === 'active' && $request->integer('unit_id') && $request->integer('class_id') && $studentYearId) {
            $studentClassAlumniClass = SchoolClass::with('educationUnit')
                ->whereKey($request->integer('class_id'))
                ->where('education_unit_id', $request->integer('unit_id'))
                ->first();
            $studentClassAlumniYear = AcademicYear::find($studentYearId);

            if ($studentClassAlumniClass && $studentClassAlumniYear) {
                $studentClassAlumniCount = Student::where('school_class_id', $studentClassAlumniClass->id)
                    ->where('academic_year_id', $studentYearId)
                    ->where('is_active', true)
                    ->count();
            }
        }

        $showCreate = $request->routeIs('master.create') || $request->routeIs('student-management.students.create');
        $showStudentImport = $request->routeIs('student-management.students.import');
        $studentImportPreview = $this->studentImportPreview($request);

        return view('master.index', [
            'tab' => $tab,
            'data' => $data,
            'classes' => $this->classOptions(),
            'classLevels' => $this->classLevelOptions(),
            'educationUnits' => $this->educationUnitOptions(),
            'academicYears' => $this->academicYearOptions(),
            'activeAcademicYear' => $activeAcademicYear,
            'studentYearId' => $studentYearId,
            'studentStatus' => $studentStatus,
            'classYearId' => $classYearId,
            'classStatus' => $classStatus,
            'feeTypeYearId' => $feeTypeYearId,
            'feeTypeStatus' => $feeTypeStatus,
            'feeDiscountYearId' => $feeDiscountYearId,
            'feeDiscountStatus' => $feeDiscountStatus,
            'studentOptions' => $this->studentOptions($tab, $showCreate),
            'existingStudentOptions' => $this->existingStudentOptions($tab, $showCreate),
            'feeTypeOptions' => $this->feeTypeOptions($tab, $showCreate),
            'roleOptions' => Role::options(),
            'permissionOptions' => Role::PERMISSIONS,
            'stats' => $this->masterStats(),
            'showCreate' => $showCreate,
            'showStudentImport' => $showStudentImport,
            'studentImportPreview' => $studentImportPreview,
            'studentImportToken' => $request->string('import_token')->value() ?: null,
            'studentClassAlumniCount' => $studentClassAlumniCount,
            'studentClassAlumniClass' => $studentClassAlumniClass,
            'studentClassAlumniYear' => $studentClassAlumniYear,
        ]);
    }

    public function create(Request $request)
    {
        return $this->index($request);
    }

    public function studentIndex(Request $request)
    {
        $request->merge(['tab' => 'students']);

        return $this->index($request);
    }

    public function studentCreate(Request $request)
    {
        $request->merge(['tab' => 'students']);

        return $this->index($request);
    }

    public function studentImport(Request $request)
    {
        $request->merge(['tab' => 'students']);

        return $this->index($request);
    }

    public function studentTransfer(Request $request)
    {
        return $this->studentClassMovementPage($request, 'transfer');
    }

    public function studentPromotion(Request $request)
    {
        return $this->studentClassMovementPage($request, 'promotion');
    }

    public function storeStudentTransfer(Request $request): RedirectResponse
    {
        return $this->storeStudentClassMovement($request, 'transfer');
    }

    public function storeStudentPromotion(Request $request): RedirectResponse
    {
        return $this->storeStudentClassMovement($request, 'promotion');
    }

    public function classAlumniCreate(Request $request)
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $yearId = $request->integer('year_id') ?: $activeAcademicYear?->id;
        $unitId = $request->integer('unit_id');
        $classId = $request->integer('class_id');

        $class = $unitId && $classId
            ? SchoolClass::with('educationUnit')
                ->whereKey($classId)
                ->where('education_unit_id', $unitId)
                ->first()
            : null;
        $year = $yearId ? AcademicYear::find($yearId) : null;

        if (! $class || ! $year) {
            return redirect()->route('student-management.students.index', array_filter([
                'unit_id' => $unitId,
                'class_id' => $classId,
                'year_id' => $yearId,
                'status' => 'active',
            ], fn ($value) => filled($value)))->with('error', 'Pilih unit pendidikan, kelas, dan tahun pelajaran aktif terlebih dahulu.');
        }

        $studentCount = Student::where('school_class_id', $class->id)
            ->where('academic_year_id', $year->id)
            ->where('is_active', true)
            ->count();

        if ($studentCount < 1) {
            return redirect()->route('student-management.students.index', [
                'unit_id' => $unitId,
                'class_id' => $classId,
                'year_id' => $yearId,
                'status' => 'active',
            ])->with('error', 'Tidak ada siswa aktif pada kelas dan tahun pelajaran yang dipilih.');
        }

        return view('student-management.class-alumni', [
            'activeAcademicYear' => $activeAcademicYear,
            'class' => $class,
            'year' => $year,
            'studentCount' => $studentCount,
        ]);
    }

    public function storeClassAlumni(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'exists:education_units,id'],
            'class_id' => ['required', 'exists:school_classes,id'],
            'year_id' => ['required', 'exists:academic_years,id'],
            'exit_date' => ['required', 'date'],
            'inactive_reason' => ['required', 'string', 'max:120'],
        ]);

        $class = SchoolClass::whereKey($validated['class_id'])
            ->where('education_unit_id', $validated['unit_id'])
            ->first();

        if (! $class) {
            throw ValidationException::withMessages([
                'class_id' => 'Kelas tidak sesuai dengan unit pendidikan yang dipilih.',
            ]);
        }

        $students = Student::where('school_class_id', $validated['class_id'])
            ->where('academic_year_id', $validated['year_id'])
            ->where('is_active', true);

        $count = (clone $students)->count();
        if ($count < 1) {
            throw ValidationException::withMessages([
                'class_id' => 'Tidak ada siswa aktif pada kelas dan tahun pelajaran yang dipilih.',
            ]);
        }

        $students->update([
            'is_active' => false,
            'exit_date' => $validated['exit_date'],
            'inactive_reason' => $validated['inactive_reason'],
        ]);

        return redirect()->route('student-management.students.index', [
            'unit_id' => $validated['unit_id'],
            'class_id' => $validated['class_id'],
            'year_id' => $validated['year_id'],
            'status' => 'inactive',
        ])->with('success', number_format($count, 0, ',', '.').' siswa berhasil dijadikan alumni.');
    }

    public function studentAlumni(Request $request)
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $selectedUnitId = $request->integer('unit_id') ?: null;
        $selectedYearId = $request->integer('year_id') ?: null;
        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->string('sort')->value(), ['nis', 'name', 'gender', 'unit', 'year', 'exit_date'], true)
            ? $request->string('sort')->value()
            : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPageInput = $request->query('per_page', 10);
        $perPage = in_array((string) $perPageInput, ['10', '25', '50', '100', '500', 'all'], true) ? (string) $perPageInput : '10';

        $alumniQuery = Student::select('students.*')
            ->with(['schoolClass.educationUnit', 'academicYear'])
            ->where('students.is_active', false)
            ->when($selectedUnitId, fn ($query, $unitId) => $query->whereHas('schoolClass', fn ($class) => $class->where('education_unit_id', $unitId)))
            ->when($selectedYearId, fn ($query, $yearId) => $query->where('students.academic_year_id', $yearId))
            ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'students.academic_year_id')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('students.nis', 'like', "%{$search}%")
                ->orWhere('students.nisn', 'like', "%{$search}%")
                ->orWhere('students.name', 'like', "%{$search}%")
                ->orWhere('students.inactive_reason', 'like', "%{$search}%")
                ->orWhere('education_units.code', 'like', "%{$search}%")
                ->orWhere('academic_years.name', 'like', "%{$search}%")));

        match ($sort) {
            'nis' => $alumniQuery->orderBy('students.nis', $direction),
            'name' => $alumniQuery->orderBy('students.name', $direction),
            'gender' => $alumniQuery->orderBy('students.gender', $direction),
            'unit' => $alumniQuery
                ->orderByRaw(str_replace('code', 'education_units.code', $this->educationUnitOrderExpression())." {$direction}")
                ->orderBy('education_units.name', $direction),
            'year' => $alumniQuery->orderBy('academic_years.name', $direction),
            'exit_date' => $alumniQuery->orderBy('students.exit_date', $direction),
            default => $alumniQuery->orderBy('students.name'),
        };

        $alumni = $alumniQuery
            ->paginate($perPage === 'all' ? PHP_INT_MAX : (int) $perPage)
            ->appends($request->except(['page', 'class_id', 'reason', 'sort', 'direction']));

        return view('student-management.alumni', [
            'activeAcademicYear' => $activeAcademicYear,
            'academicYears' => $this->academicYearOptions(),
            'educationUnits' => $this->educationUnitOptions(),
            'alumni' => $alumni,
            'filters' => [
                'unit_id' => $selectedUnitId,
                'year_id' => $selectedYearId,
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_years,name'],
            'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        [$start, $end] = explode('/', $validated['name']);
        $validated['start_date'] ??= $start.'-07-01';
        $validated['end_date'] ??= $end.'-06-30';
        $validated['is_active'] = $request->boolean('is_active');
        if ($validated['is_active']) {
            AcademicYear::query()->update(['is_active' => false]);
        }
        AcademicYear::create($validated);

        return $this->done('academic-years', 'Tahun pelajaran berhasil ditambahkan.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years', 'name')->ignore($academicYear)],
            'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        [$start, $end] = explode('/', $validated['name']);
        $validated['start_date'] ??= $start.'-07-01';
        $validated['end_date'] ??= $end.'-06-30';
        $validated['is_active'] = $request->boolean('is_active');
        if ($validated['is_active']) {
            AcademicYear::whereKeyNot($academicYear->id)->update(['is_active' => false]);
        }
        $academicYear->update($validated);

        return $this->done('academic-years', 'Tahun pelajaran berhasil diperbarui.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        try {
            SchoolClass::create($this->validateClass($request));
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw ValidationException::withMessages([
                    'name' => 'Nama kelas tersebut sudah digunakan pada unit pendidikan yang dipilih.',
                ]);
            }

            throw $exception;
        }

        return $this->done('classes', 'Kelas berhasil ditambahkan.');
    }

    public function updateClass(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        try {
            $schoolClass->update($this->validateClass($request, $schoolClass));
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw ValidationException::withMessages([
                    'name' => 'Nama kelas tersebut sudah digunakan pada unit pendidikan yang dipilih.',
                ]);
            }

            throw $exception;
        }

        return $this->done('classes', 'Kelas berhasil diperbarui.');
    }

    public function storeEducationUnit(Request $request): RedirectResponse
    {
        EducationUnit::create($this->validateEducationUnit($request));

        return $this->done('education-units', 'Unit pendidikan berhasil ditambahkan.');
    }

    public function updateEducationUnit(Request $request, EducationUnit $educationUnit): RedirectResponse
    {
        $educationUnit->update($this->validateEducationUnit($request, $educationUnit));

        return $this->done('education-units', 'Unit pendidikan berhasil diperbarui.');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $identityStudent = $request->filled('existing_student_id')
            ? Student::findOrFail($request->integer('existing_student_id'))
            : null;
        $data = $this->validateStudent($request, null, $identityStudent);

        if ($identityStudent) {
            $identityStudent = $identityStudent->identityStudent ?: $identityStudent;
            $data = array_merge($data, $identityStudent->only($this->studentPersonalFields()), [
                'identity_student_id' => $identityStudent->id,
            ]);
        }

        Student::create($data);

        return $this->done('students', 'Data siswa berhasil ditambahkan.');
    }

    public function updateStudent(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validateStudent($request, $student);
        $rootIdentityId = $student->identity_student_id ?: $student->id;

        DB::transaction(function () use ($student, $data, $rootIdentityId) {
            $student->update($data);

            Student::where(fn ($query) => $query
                ->whereKey($rootIdentityId)
                ->orWhere('identity_student_id', $rootIdentityId))
                ->update(collect($data)->only($this->studentPersonalFields())->all());
        });

        return $this->done('students', 'Data siswa berhasil diperbarui.');
    }

    public function exportStudents(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'unit_id' => ['nullable', 'exists:education_units,id'],
            'class_id' => [
                'nullable',
                $request->integer('unit_id')
                    ? Rule::exists('school_classes', 'id')->where('education_unit_id', $request->integer('unit_id'))
                    : Rule::exists('school_classes', 'id'),
            ],
            'year_id' => ['nullable', 'exists:academic_years,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $validated['status'] ??= 'active';
        $yearId = $validated['year_id'] ?? AcademicYear::where('is_active', true)->value('id');
        $unit = isset($validated['unit_id']) ? EducationUnit::findOrFail($validated['unit_id']) : null;
        $students = Student::with('schoolClass.educationUnit')
            ->when($unit, fn ($query) => $query->whereHas('schoolClass', fn ($q) => $q->where('education_unit_id', $unit->id)))
            ->when($validated['class_id'] ?? null, fn ($query, $classId) => $query->where('school_class_id', $classId))
            ->when($yearId, fn ($query, $id) => $query->where('academic_year_id', $id))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")->orWhere('nisn', 'like', "%{$search}%")))
            ->orderBy('name')->get();

        $headers = $this->studentImportHeaders();
        $rows = [$headers];
        foreach ($students as $index => $student) {
            $rows[] = [
                $index + 1,
                $student->nis,
                $student->nisn,
                $student->name,
                $student->birth_place,
                $student->birth_date?->format('Y-m-d'),
                $student->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                $student->father_name,
                $student->mother_name,
                $student->father_whatsapp,
                $student->mother_whatsapp,
                $student->province,
                $student->city,
                $student->district,
                $student->village,
                $student->address,
                $student->schoolClass?->educationUnit?->code,
                $student->schoolClass?->name,
                $student->entry_date?->format('Y-m-d'),
                $student->billing_start_date?->format('Y-m-d'),
                $student->is_active ? 'Aktif' : 'Nonaktif',
                $student->exit_date?->format('Y-m-d'),
                $student->inactive_reason,
            ];
        }

        $filenameScope = $unit ? Str::slug($unit->code) : 'semua';

        return response()->streamDownload(function () use ($rows) {
            $path = tempnam(sys_get_temp_dir(), 'students-xlsx-');
            StudentXlsx::write($path, $rows);
            readfile($path);
            unlink($path);
        }, 'data-siswa-'.$filenameScope.'-'.now()->format('Y-m-d').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function studentTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $path = tempnam(sys_get_temp_dir(), 'student-template-xlsx-');
            StudentXlsx::write($path, [$this->studentImportHeaders()]);
            readfile($path);
            unlink($path);
        }, 'template-import-data-siswa.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function previewStudentImport(ImportStudentsRequest $request, StudentImportService $importer): RedirectResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return redirect()->route('student-management.students.import')
                ->withErrors(['file' => 'Atur tahun pelajaran aktif terlebih dahulu.']);
        }

        $file = $request->file('file');
        $token = (string) Str::uuid();
        $path = $file->storeAs('student-imports', $token.'.xlsx');

        try {
            $preview = $importer->preview(Storage::path($path), $activeYear);
            $request->session()->put("student_imports.{$token}", [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'academic_year_id' => $activeYear->id,
                'preview' => $preview,
            ]);
        } catch (\Throwable $exception) {
            Storage::delete($path);
            throw $exception;
        }

        return redirect()->route('student-management.students.import', [
            'import_token' => $token,
        ]);
    }

    public function importStudents(Request $request, StudentImportService $importer): RedirectResponse
    {
        $validated = $request->validate(['token' => ['required', 'uuid']]);
        $stored = $request->session()->pull("student_imports.{$validated['token']}");
        if (! $stored || ! Storage::exists($stored['path'])) {
            return redirect()->route('student-management.students.import')
                ->withErrors(['file' => 'File preview sudah tidak tersedia. Silakan unggah ulang.']);
        }

        $activeYear = AcademicYear::find($stored['academic_year_id']);
        if (! $activeYear) {
            Storage::delete($stored['path']);

            return redirect()->route('student-management.students.import')
                ->withErrors(['file' => 'Tahun pelajaran untuk file ini sudah tidak tersedia.']);
        }

        try {
            $result = $importer->import(Storage::path($stored['path']), $activeYear);
        } finally {
            Storage::delete($stored['path']);
        }

        $imported = $result['imported'];
        $createdClasses = $result['created_classes'];
        $failures = $result['failures'];

        if ($imported === 0) {
            return redirect()->route('student-management.students.index')
                ->with('error', 'Tidak ada data yang berhasil diimpor. '.collect($failures)->pluck('message')->take(5)->implode(' '));
        }

        $message = "{$imported} data siswa berhasil diimpor.";
        if ($createdClasses > 0) {
            $message .= " {$createdClasses} kelas baru dibuat otomatis.";
        }
        if ($failures) {
            $message .= ' '.count($failures).' baris dilewati: '.collect($failures)->pluck('message')->take(3)->implode(' ');
        }

        return $this->done('students', $message);
    }

    public function storeFeeType(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($this->validateFeeType($request) as $data) {
                FeeType::create($data);
            }
        });

        return $this->done('fee-types', 'Kategori pembayaran berhasil ditambahkan.');
    }

    public function updateFeeType(Request $request, FeeType $feeType): RedirectResponse
    {
        DB::transaction(function () use ($request, $feeType) {
            $payloads = $this->validateFeeType($request, $feeType);
            $feeType->update(array_shift($payloads));
            foreach ($payloads as $data) {
                FeeType::create($data);
            }
        });

        return $this->done('fee-types', 'Kategori pembayaran berhasil diperbarui.');
    }

    public function storeFeeDiscount(Request $request): RedirectResponse
    {
        FeeDiscount::create($this->validateFeeDiscount($request));

        return $this->done('fee-discounts', 'Keringanan biaya berhasil ditambahkan.');
    }

    public function updateFeeDiscount(Request $request, FeeDiscount $feeDiscount): RedirectResponse
    {
        $feeDiscount->update($this->validateFeeDiscount($request, $feeDiscount));

        return $this->done('fee-discounts', 'Keringanan biaya berhasil diperbarui.');
    }

    public function storeRole(Request $request): RedirectResponse
    {
        Role::create($this->validateRole($request));

        return $this->done('data-roles', 'Role berhasil ditambahkan.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $role->update($this->validateRole($request, $role));

        return $this->done('data-roles', 'Role berhasil diperbarui.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->normalizeUserRequest($request);

        User::create($this->validateUser($request));

        return $this->done('data-users', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->normalizeUserRequest($request);
        $validated = $this->validateUser($request, $user);

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return $this->done('data-users', 'User berhasil diperbarui.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $model = match ($type) {
            'academic-years' => AcademicYear::findOrFail($id),
            'education-units' => EducationUnit::findOrFail($id),
            'classes' => SchoolClass::findOrFail($id),
            'fee-types' => FeeType::findOrFail($id),
            'fee-discounts' => FeeDiscount::findOrFail($id),
            'data-roles' => Role::findOrFail($id),
            'data-users' => User::findOrFail($id),
            default => Student::findOrFail($id),
        };

        if ($model instanceof Student) {
            DB::transaction(fn () => $this->deleteStudentWithRelatedData($model));

            return $this->done($type, 'Data siswa beserta seluruh data contoh terkait berhasil dihapus.');
        }

        if ($model instanceof User && auth()->id() === $model->id) {
            return redirect()->route('master.index', ['tab' => $type])
                ->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if ($model instanceof Role && User::where('role', $model->key)->exists()) {
            return redirect()->route('master.index', ['tab' => $type])
                ->with('error', 'Role masih digunakan oleh user dan tidak dapat dihapus.');
        }

        try {
            $model->delete();
        } catch (QueryException) {
            return redirect()->route('master.index', ['tab' => $type])
                ->with('error', 'Data masih digunakan dan tidak dapat dihapus.');
        }

        return $this->done($type, 'Data berhasil dihapus.');
    }

    private function deleteStudentWithRelatedData(Student $student): void
    {
        $billIds = Bill::where('student_id', $student->id)->pluck('id');
        $sppPaymentIds = SppPayment::where('student_id', $student->id)->pluck('id');
        $otherPaymentIds = OtherPayment::where('student_id', $student->id)->pluck('id');

        BillManualPayment::whereIn('bill_id', $billIds)->delete();
        BillPaymentAllocation::whereIn('bill_id', $billIds)
            ->orWhere(fn ($query) => $query
                ->where('payment_type', 'spp')
                ->whereIn('payment_id', $sppPaymentIds))
            ->orWhere(fn ($query) => $query
                ->where('payment_type', 'other')
                ->whereIn('payment_id', $otherPaymentIds))
            ->delete();
        Bill::whereIn('id', $billIds)->delete();

        SppPaymentCorrection::whereIn('spp_payment_id', $sppPaymentIds)->delete();
        SppPaymentItem::where('student_id', $student->id)->delete();
        SppPayment::whereIn('id', $sppPaymentIds)->delete();
        OtherPayment::whereIn('id', $otherPaymentIds)->delete();
        FeeDiscount::where('student_id', $student->id)->delete();
        $student->delete();
    }

    private function validateClass(Request $request, ?SchoolClass $schoolClass = null): array
    {
        $validated = $request->validate([
            'education_unit_id' => ['required', 'exists:education_units,id'],
            'name' => ['required', 'max:50', Rule::unique('school_classes')->where('education_unit_id', $request->education_unit_id)->ignore($schoolClass)],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['level'] = 'Kelas '.$validated['name'];
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            || in_array((int) ($exception->errorInfo[1] ?? 0), [1062, 1555, 2067], true);
    }

    private function validateEducationUnit(Request $request, ?EducationUnit $educationUnit = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'max:20', Rule::unique('education_units')->ignore($educationUnit)],
            'name' => ['required', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $request->merge([
            'key' => str($request->input('key', ''))
                ->lower()
                ->replaceMatches('/[^a-z0-9_]+/', '_')
                ->trim('_')
                ->value(),
        ]);

        $validated = $request->validate([
            'key' => ['required', 'max:60', Rule::unique('roles', 'key')->ignore($role)],
            'name' => ['required', 'max:120'],
            'description' => ['nullable', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(Role::PERMISSIONS))],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['permissions'] = array_values(array_unique($validated['permissions'] ?? []));
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function normalizeUserRequest(Request $request): void
    {
        $request->merge([
            'username' => str($request->input('username', ''))
                ->lower()
                ->replaceMatches('/\s+/', '')
                ->value(),
        ]);
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'max:150'],
            'username' => ['required', 'max:100', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::exists('roles', 'key')->where('is_active', true)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
        ]);
    }

    private function validateStudent(Request $request, ?Student $student = null, ?Student $identityStudent = null): array
    {
        $newIdentity = ! $student && ! $identityStudent;
        $rules = [
            'nis' => ['required', 'max:30'],
            'nisn' => ['nullable', 'max:30'],
            'name' => [$newIdentity || $student ? 'required' : 'nullable', 'max:120'],
            'birth_place' => ['nullable', 'max:120'],
            'birth_date' => ['nullable', 'date'],
            'gender' => [$newIdentity || $student ? 'required' : 'nullable', Rule::in(['L', 'P'])],
            'father_name' => ['nullable', 'max:120'],
            'mother_name' => ['nullable', 'max:120'],
            'father_whatsapp' => ['nullable', 'max:25'],
            'mother_whatsapp' => ['nullable', 'max:25'],
            'province' => ['nullable', 'max:120'],
            'city' => ['nullable', 'max:120'],
            'district' => ['nullable', 'max:120'],
            'village' => ['nullable', 'max:120'],
            'address' => ['nullable', 'max:1000'],
            'education_unit_id' => ['required', 'exists:education_units,id'],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('education_unit_id', $request->education_unit_id)],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'entry_date' => ['required', 'date'],
            'billing_start_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', Rule::requiredIf(fn () => ! $request->boolean('is_active')), 'date', 'after_or_equal:entry_date'],
            'inactive_reason' => ['nullable', Rule::requiredIf(fn () => ! $request->boolean('is_active')), 'max:255'],
            'guardian_name' => ['nullable', 'max:120'],
            'whatsapp' => ['nullable', 'max:25'],
            'is_active' => ['nullable', 'boolean'],
        ];
        if (! $student) {
            $rules['existing_student_id'] = ['nullable', 'exists:students,id'];
        }
        $validated = $request->validate($rules);
        if (! empty($validated['nisn'])) {
            $nisnQuery = Student::where('nisn', $validated['nisn']);
            if ($student) {
                $rootIdentityId = $student->identity_student_id ?: $student->id;
                $nisnQuery->where(fn ($query) => $query
                    ->whereKeyNot($rootIdentityId)
                    ->where(fn ($group) => $group
                        ->whereNull('identity_student_id')
                        ->orWhere('identity_student_id', '!=', $rootIdentityId)));
            }
            if ($nisnQuery->exists()) {
                throw ValidationException::withMessages(['nisn' => 'NISN sudah digunakan siswa lain.']);
            }
        }
        $nisIsUsed = Student::where('nis', $validated['nis'])
            ->when($student, fn ($query) => $query->whereKeyNot($student->id))
            ->whereHas('schoolClass', fn ($query) => $query->where('education_unit_id', $validated['education_unit_id']))
            ->exists();
        if ($nisIsUsed) {
            throw ValidationException::withMessages([
                'nis' => 'NIS sudah digunakan pada unit pendidikan yang dipilih.',
            ]);
        }
        if ($identityStudent) {
            $rootIdentityId = $identityStudent->identity_student_id ?: $identityStudent->id;
            $alreadyInUnit = Student::where(fn ($query) => $query
                ->whereKey($rootIdentityId)
                ->orWhere('identity_student_id', $rootIdentityId))
                ->whereHas('schoolClass', fn ($query) => $query->where('education_unit_id', $validated['education_unit_id']))
                ->exists();
            if ($alreadyInUnit) {
                throw ValidationException::withMessages([
                    'existing_student_id' => 'Siswa ini sudah terdaftar pada unit pendidikan yang dipilih.',
                ]);
            }
        }
        unset($validated['education_unit_id'], $validated['existing_student_id']);
        $validated['is_active'] = $request->boolean('is_active');
        if ($validated['is_active']) {
            $validated['exit_date'] = null;
            $validated['inactive_reason'] = null;
        }

        return $validated;
    }

    private function studentImportHeaders(): array
    {
        return ['No', 'NIS', 'NISN', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'Nama Ayah', 'Nama Ibu', 'No. WA Ayah', 'No. WA Ibu', 'Provinsi', 'Kabupaten/Kota', 'Kecamatan', 'Desa', 'Alamat', 'Unit Pendidikan', 'Kelas', 'Tanggal Masuk', 'Mulai Tagihan Khusus', 'Status', 'Tanggal Keluar', 'Alasan Nonaktif'];
    }

    private function studentImportPreview(Request $request): ?array
    {
        $token = $request->string('import_token')->value();
        if ($token === '') {
            return null;
        }

        return $request->session()->get("student_imports.{$token}.preview");
    }

    private function educationUnitOptions()
    {
        return EducationUnit::select(['id', 'code', 'name'])
            ->orderByRaw($this->educationUnitOrderExpression())
            ->orderBy('name')
            ->get();
    }

    private function classOptions()
    {
        return SchoolClass::select(['id', 'education_unit_id', 'name', 'level'])
            ->with('educationUnit:id,code')
            ->orderBy('education_unit_id')
            ->orderBy('name')
            ->get();
    }

    private function classLevelOptions()
    {
        return SchoolClass::select(['education_unit_id', 'name', 'level'])
            ->orderBy('education_unit_id')
            ->orderBy('name')
            ->get()
            ->map(fn (SchoolClass $class) => [
                'education_unit_id' => $class->education_unit_id,
                'key' => ClassLevel::key($class->level ?: $class->name),
            ])
            ->filter(fn (array $level) => $level['key'] !== null)
            ->unique(fn (array $level) => $level['education_unit_id'].'|'.$level['key'])
            ->map(fn (array $level) => $level + ['label' => ClassLevel::label($level['key'])])
            ->values();
    }

    private function academicYearOptions()
    {
        return AcademicYear::select(['id', 'name', 'start_date', 'end_date', 'is_active', 'created_at'])
            ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('start_date')
            ->orderByDesc('name')
            ->orderByDesc('id')
            ->get();
    }

    private function studentOptions(string $tab, bool $showCreate)
    {
        if (! $showCreate || $tab !== 'fee-discounts') {
            return collect();
        }

        return Student::select(['id', 'nis', 'name', 'school_class_id'])
            ->with('schoolClass.educationUnit:id,code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function existingStudentOptions(string $tab, bool $showCreate)
    {
        if (! $showCreate || $tab !== 'students') {
            return collect();
        }

        return Student::select(['id', 'identity_student_id', 'nis', 'name', 'school_class_id'])
            ->with('schoolClass.educationUnit:id,code')
            ->whereNull('identity_student_id')
            ->orderBy('name')
            ->get();
    }

    private function studentPersonalFields(): array
    {
        return [
            'nisn', 'name', 'birth_place', 'birth_date', 'gender', 'father_name', 'mother_name',
            'father_whatsapp', 'mother_whatsapp', 'province', 'city', 'district', 'village',
            'address', 'guardian_name', 'whatsapp',
        ];
    }

    private function feeTypeOptions(string $tab, bool $showCreate)
    {
        if (! $showCreate || $tab !== 'fee-discounts') {
            return collect();
        }

        return FeeType::select(['id', 'education_unit_id', 'school_class_id', 'name'])
            ->with(['educationUnit:id,code', 'schoolClass:id,name'])
            ->where('is_active', true)
            ->where('payment_group', '!=', 'spp')
            ->orderBy('name')
            ->get();
    }

    private function masterStats(): array
    {
        return [
            'students' => Student::count(),
            'active_students' => Student::where('is_active', true)->count(),
            'classes' => SchoolClass::count(),
            'education_units' => EducationUnit::where('is_active', true)->count(),
            'fee_types' => FeeType::where('is_active', true)->count(),
            'roles' => Role::where('is_active', true)->count(),
            'users' => User::count(),
        ];
    }

    private function educationUnitOrderExpression(): string
    {
        return "CASE code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END";
    }

    private function validateFeeType(Request $request, ?FeeType $feeType = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'max:120'],
            'payment_group' => ['nullable', Rule::in(['spp', 'daftar-ulang', 'laundry', 'lain-lain'])],
            'education_unit_id' => ['required', 'exists:education_units,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'school_class_id' => ['nullable'],
            'school_class_ids' => ['nullable', 'array'],
            'school_class_ids.*' => ['integer', Rule::exists('school_classes', 'id')->where('education_unit_id', $request->education_unit_id)],
            'class_scope' => ['nullable', Rule::in(['all', 'level', 'selected'])],
            'class_level' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'integer', 'min:0'],
            'period' => ['nullable', Rule::in(['Bulanan', 'Tahunan', 'Sekali Bayar'])],
            'creates_bill' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['payment_group'] ??= $feeType?->payment_group ?? 'lain-lain';

        $fixedBehavior = [
            'spp' => ['period' => 'Bulanan', 'creates_bill' => true],
            'daftar-ulang' => ['period' => 'Sekali Bayar', 'creates_bill' => true],
            'laundry' => ['period' => 'Bulanan', 'creates_bill' => false],
        ][$validated['payment_group']] ?? null;
        $validated['period'] = $fixedBehavior['period'] ?? ($validated['period'] ?? $feeType?->period ?? 'Sekali Bayar');
        $validated['creates_bill'] = $fixedBehavior['creates_bill']
            ?? ($request->has('creates_bill') ? $request->boolean('creates_bill') : ($feeType?->creates_bill ?? true));

        $legacyClassIds = collect($request->input('school_class_ids', []))->filter()->map(fn ($id) => (int) $id);
        if ($legacyClassIds->isEmpty() && is_numeric($request->input('school_class_id'))) {
            $legacyClassIds = collect([(int) $request->input('school_class_id')]);
        }
        $legacyLevels = SchoolClass::where('education_unit_id', $validated['education_unit_id'])
            ->whereIn('id', $legacyClassIds)
            ->get(['name', 'level'])
            ->map(fn (SchoolClass $class) => ClassLevel::key($class->level ?: $class->name))
            ->filter()
            ->unique()
            ->values();

        $scope = $validated['class_scope'] ?? null;
        $requestedLevel = ClassLevel::key($validated['class_level'] ?? null);
        $levels = match (true) {
            $scope === 'all' || $request->input('school_class_id') === 'all' => collect([null]),
            $requestedLevel !== null => collect([$requestedLevel]),
            $legacyLevels->isNotEmpty() => $legacyLevels,
            default => collect([null]),
        };

        $availableLevels = SchoolClass::where('education_unit_id', $validated['education_unit_id'])
            ->get(['name', 'level'])
            ->map(fn (SchoolClass $class) => ClassLevel::key($class->level ?: $class->name))
            ->filter()
            ->unique();
        foreach ($levels->filter() as $level) {
            if (! $availableLevels->contains($level)) {
                throw ValidationException::withMessages(['class_level' => 'Tingkat tidak sesuai dengan unit pendidikan.']);
            }
        }

        if ($validated['payment_group'] === 'spp') {
            foreach ($levels as $level) {
                $duplicate = FeeType::query()
                    ->paymentGroup('spp')
                    ->where('education_unit_id', $validated['education_unit_id'])
                    ->whereNull('school_class_id')
                    ->when($level, fn ($query, $value) => $query->where('class_level', $value), fn ($query) => $query->whereNull('class_level'))
                    ->when(
                        $validated['academic_year_id'] ?? null,
                        fn ($query, $yearId) => $query->where('academic_year_id', $yearId),
                        fn ($query) => $query->whereNull('academic_year_id'),
                    )
                    ->when($feeType, fn ($query) => $query->whereKeyNot($feeType->id))
                    ->exists();
                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'payment_group' => 'Kategori SPP untuk unit, tingkat, dan tahun pelajaran tersebut sudah tersedia.',
                    ]);
                }
            }
        }
        $baseCode = Str::upper(Str::slug($validated['name'], '-'));
        $baseCode = substr($baseCode ?: 'PEMBAYARAN', 0, 20);

        $reservedCodes = [];

        return $levels->map(function ($level, int $index) use ($baseCode, $feeType, $request, $validated, &$reservedCodes) {
            $code = $baseCode;
            $suffix = 2;
            while (
                in_array($code, $reservedCodes, true)
                || FeeType::where('code', $code)
                    ->when($feeType && $index === 0, fn ($query) => $query->whereKeyNot($feeType->id))
                    ->exists()
            ) {
                $number = '-'.$suffix++;
                $code = substr($baseCode, 0, 20 - strlen($number)).$number;
            }
            $reservedCodes[] = $code;

            return [
                'name' => $validated['name'],
                'payment_group' => $validated['payment_group'],
                'education_unit_id' => $validated['education_unit_id'],
                'school_class_id' => null,
                'class_level' => $level,
                'academic_year_id' => $validated['academic_year_id'] ?? $feeType?->academic_year_id,
                'amount' => $validated['amount'],
                'period' => $validated['period'],
                'creates_bill' => $validated['creates_bill'],
                'code' => $code,
                'is_active' => $request->boolean('is_active'),
            ];
        })->all();
    }

    private function validateFeeDiscount(Request $request, ?FeeDiscount $feeDiscount = null): array
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'source_type' => ['required', Rule::in(['spp', 'fee_type'])],
            'fee_type_id' => ['nullable', Rule::requiredIf($request->input('source_type') === 'fee_type'), 'exists:fee_types,id'],
            'discount_type' => ['required', Rule::in(['amount', 'percentage'])],
            'discount_value' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['fee_type_id'] = $validated['source_type'] === 'fee_type' ? $validated['fee_type_id'] : null;
        $validated['is_active'] = $request->boolean('is_active');

        $student = Student::with('schoolClass.educationUnit')->findOrFail($validated['student_id']);
        $feeType = $validated['fee_type_id'] ? FeeType::find($validated['fee_type_id']) : null;
        $originalAmount = app(ChargeCalculator::class)->ensureBaseAmountExists($student, $validated['source_type'], $feeType);

        if ($validated['discount_type'] === 'percentage' && $validated['discount_value'] > 100) {
            throw ValidationException::withMessages(['discount_value' => 'Persentase keringanan maksimal 100%.']);
        }
        if ($validated['discount_type'] === 'amount' && $validated['discount_value'] > $originalAmount) {
            throw ValidationException::withMessages(['discount_value' => 'Nominal keringanan tidak boleh melebihi biaya asli.']);
        }

        if ($validated['is_active']) {
            $startDate = CarbonImmutable::parse($validated['start_date']);
            $endDate = isset($validated['end_date']) ? CarbonImmutable::parse($validated['end_date']) : null;
            $overlap = FeeDiscount::query()
                ->where('student_id', $validated['student_id'])
                ->where('source_type', $validated['source_type'])
                ->where('fee_type_id', $validated['fee_type_id'])
                ->where('is_active', true)
                ->when($feeDiscount, fn ($query) => $query->whereKeyNot($feeDiscount->id))
                ->whereDate('start_date', '<=', $endDate ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate))
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages(['start_date' => 'Siswa sudah memiliki keringanan aktif untuk pembayaran dan periode tersebut.']);
            }
        }

        return $validated;
    }

    private function done(string $tab, string $message): RedirectResponse
    {
        $parameters = ['tab' => $tab];

        if ($tab === 'students') {
            $parameters = request()->only([
                'unit_id',
                'class_id',
                'year_id',
                'search',
                'per_page',
                'sort',
                'direction',
                'page',
            ]);

            return redirect()->route('student-management.students.index', $parameters)->with('success', $message);
        }

        return redirect()->route('master.index', $parameters)->with('success', $message);
    }

    private function studentManagementPlaceholder(string $title, string $description, string $section)
    {
        return view('student-management.placeholder', [
            'title' => $title,
            'description' => $description,
            'section' => $section,
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
            'stats' => [
                'students' => Student::where('is_active', true)->count(),
                'classes' => SchoolClass::count(),
                'alumni' => Student::where('is_active', false)->count(),
            ],
        ]);
    }

    private function studentClassMovementPage(Request $request, string $mode)
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = $request->integer('year_id') ?: $activeAcademicYear?->id;
        $selectedUnitId = $request->integer('unit_id') ?: null;
        $selectedClassId = $request->integer('class_id') ?: null;
        $search = trim((string) $request->input('search', ''));
        $isPromotion = $mode === 'promotion';
        $movementSort = in_array($request->string('sort')->value(), ['nis', 'name', 'unit', 'class', 'year'], true)
            ? $request->string('sort')->value()
            : null;
        $movementDirection = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $perPageInput = $request->query('per_page', 10);
        $perPage = in_array((string) $perPageInput, ['10', '25', '50', '100', '500', 'all'], true) ? (string) $perPageInput : '10';
        $classOptions = $this->classOptions();
        $targetUnitId = $selectedUnitId ?: ($selectedClassId ? SchoolClass::whereKey($selectedClassId)->value('education_unit_id') : null);
        $targetClasses = $targetUnitId
            ? $classOptions->where('education_unit_id', (int) $targetUnitId)->values()
            : $classOptions;

        $studentsQuery = Student::select('students.*')
            ->with(['schoolClass.educationUnit', 'academicYear'])
            ->where('students.is_active', true)
            ->when($selectedYearId, fn ($query) => $query->where('students.academic_year_id', $selectedYearId))
            ->when($selectedClassId, fn ($query) => $query->where('students.school_class_id', $selectedClassId))
            ->when($selectedUnitId, fn ($query) => $query->whereHas('schoolClass', fn ($class) => $class->where('education_unit_id', $selectedUnitId)))
            ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->join('education_units', 'education_units.id', '=', 'school_classes.education_unit_id')
            ->leftJoin('academic_years', 'academic_years.id', '=', 'students.academic_year_id')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('students.name', 'like', "%{$search}%")
                ->orWhere('students.nis', 'like', "%{$search}%")
                ->orWhere('education_units.code', 'like', "%{$search}%")
                ->orWhere('education_units.name', 'like', "%{$search}%")
                ->orWhere('school_classes.name', 'like', "%{$search}%")
                ->orWhere('academic_years.name', 'like', "%{$search}%")));

        match ($movementSort) {
            'nis' => $studentsQuery->orderBy('students.nis', $movementDirection),
            'name' => $studentsQuery->orderBy('students.name', $movementDirection),
            'unit' => $studentsQuery->orderByRaw(str_replace('code', 'education_units.code', $this->educationUnitOrderExpression())." {$movementDirection}"),
            'class' => $studentsQuery->orderBy('school_classes.name', $movementDirection),
            'year' => $studentsQuery->orderBy('academic_years.name', $movementDirection),
            default => $studentsQuery
                ->orderByRaw(str_replace('code', 'education_units.code', $this->educationUnitOrderExpression()))
                ->orderBy('school_classes.name')
                ->orderBy('students.name'),
        };

        $students = $perPage === 'all'
            ? $studentsQuery->get()
            : $studentsQuery->paginate((int) $perPage)->withQueryString();

        return view('student-management.class-movement', [
            'title' => $isPromotion ? 'Naik Kelas' : 'Pindah Kelas',
            'description' => $isPromotion
                ? 'Pilih tahun pelajaran dan kelas tujuan, centang siswa yang akan diproses, lalu konfirmasi kenaikan kelas.'
                : 'Pilih kelas tujuan, centang siswa yang akan diproses, lalu konfirmasi pemindahan kelas.',
            'section' => $isPromotion ? 'naik-kelas' : 'pindah-kelas',
            'mode' => $mode,
            'activeAcademicYear' => $activeAcademicYear,
            'academicYears' => $this->academicYearOptions(),
            'educationUnits' => $this->educationUnitOptions(),
            'classes' => $classOptions,
            'targetClasses' => $targetClasses,
            'students' => $students,
            'filters' => [
                'year_id' => $selectedYearId,
                'unit_id' => $selectedUnitId,
                'class_id' => $selectedClassId,
                'search' => $search,
                'per_page' => $perPage,
            ],
            'targetYearId' => $isPromotion
                ? (AcademicYear::where('id', '!=', $selectedYearId)->orderByDesc('name')->value('id') ?: $selectedYearId)
                : $selectedYearId,
        ]);
    }

    private function storeStudentClassMovement(Request $request, string $mode): RedirectResponse
    {
        $isPromotion = $mode === 'promotion';
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'source_year_id' => ['required', 'exists:academic_years,id'],
            'target_year_id' => [$isPromotion ? 'required' : 'nullable', 'exists:academic_years,id'],
            'target_class_id' => ['required', 'exists:school_classes,id'],
            'unit_id' => ['nullable', 'exists:education_units,id'],
            'class_id' => ['nullable', 'exists:school_classes,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive,all'],
        ]);

        $targetYearId = $isPromotion ? (int) $validated['target_year_id'] : (int) $validated['source_year_id'];
        $targetClassId = (int) $validated['target_class_id'];
        $targetClass = SchoolClass::findOrFail($targetClassId);
        if (filled($validated['unit_id'] ?? null) && (int) $targetClass->education_unit_id !== (int) $validated['unit_id']) {
            throw ValidationException::withMessages([
                'target_class_id' => 'Kelas tujuan harus berada pada unit pendidikan yang sedang difilter.',
            ]);
        }
        $studentIds = collect($validated['student_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $students = Student::whereIn('id', $studentIds)
            ->where('is_active', true)
            ->where('academic_year_id', $validated['source_year_id'])
            ->get();

        if ($students->count() !== $studentIds->count()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Ada siswa yang tidak aktif, tidak ditemukan, atau tidak sesuai tahun pelajaran sumber.',
            ]);
        }

        $unchanged = $students->every(fn (Student $student) => (int) $student->school_class_id === $targetClassId
            && (int) $student->academic_year_id === $targetYearId);
        if ($unchanged) {
            throw ValidationException::withMessages([
                'target_class_id' => $isPromotion
                    ? 'Kelas dan tahun pelajaran tujuan masih sama dengan data siswa terpilih.'
                    : 'Kelas tujuan masih sama dengan kelas siswa terpilih.',
            ]);
        }

        $normalizeStudentName = fn (string $name): string => Str::of($name)->squish()->lower()->value();
        $selectedDuplicateNames = $students
            ->groupBy(fn (Student $student) => $normalizeStudentName($student->name))
            ->filter(fn ($group, string $name) => $name !== '' && $group->count() > 1)
            ->map(fn ($group) => $group->first()->name)
            ->values();
        $targetStudents = Student::select(['id', 'name'])
            ->where('academic_year_id', $targetYearId)
            ->where('school_class_id', $targetClassId)
            ->whereNotIn('id', $studentIds)
            ->get();
        $targetDuplicateNames = $students
            ->filter(fn (Student $student) => $targetStudents->contains(
                fn (Student $targetStudent) => $normalizeStudentName($targetStudent->name) === $normalizeStudentName($student->name)
            ))
            ->pluck('name')
            ->unique()
            ->values();
        $duplicateNames = collect($selectedDuplicateNames->all())
            ->merge($targetDuplicateNames->all())
            ->unique()
            ->values();

        if ($duplicateNames->isNotEmpty()) {
            throw ValidationException::withMessages([
                'target_class_id' => 'Kelas tujuan sudah memiliki siswa dengan nama yang sama: '.$duplicateNames->join(', ').'.',
            ]);
        }

        DB::transaction(function () use ($students, $targetClassId, $targetYearId) {
            Student::whereIn('id', $students->pluck('id'))->update([
                'school_class_id' => $targetClassId,
                'academic_year_id' => $targetYearId,
            ]);
        });

        $route = $isPromotion ? 'student-management.class-promotion.index' : 'student-management.class-transfer.index';
        $message = $isPromotion
            ? number_format($students->count(), 0, ',', '.').' siswa berhasil dinaikkan kelas.'
            : number_format($students->count(), 0, ',', '.').' siswa berhasil dipindahkan kelas.';

        return redirect()->route($route, array_filter([
            'year_id' => $targetYearId,
            'unit_id' => $targetClass->education_unit_id,
            'class_id' => $targetClassId,
            'search' => $validated['search'] ?? null,
        ], fn ($value) => filled($value)))->with('success', $message);
    }
}
