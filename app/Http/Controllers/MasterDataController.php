<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportStudentsRequest;
use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\FeeDiscount;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\SppSetting;
use App\Models\Student;
use App\Services\StudentImportService;
use App\Services\ChargeCalculator;
use App\Support\StudentXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->value() ?: 'students';
        $search = $request->string('search')->value();

        $data = match ($tab) {
            'academic-years' => AcademicYear::withCount('students')
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->latest()->paginate(10)->withQueryString(),
            'education-units' => EducationUnit::withCount('schoolClasses')
                ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
                ->orderByRaw("CASE code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'ULYA' THEN 6 WHEN 'PONPES' THEN 7 WHEN 'STIT' THEN 8 ELSE 9 END")
                ->orderBy('name')->paginate(10)->withQueryString(),
            'classes' => SchoolClass::with(['educationUnit'])->withCount('students')
                ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('level', 'like', "%{$search}%")))
                ->when($request->integer('unit_id'), fn ($query, $unitId) => $query->where('education_unit_id', $unitId))
                ->orderBy('education_unit_id')->orderBy('name')->paginate(10)->withQueryString(),
            'fee-types' => FeeType::with(['educationUnit', 'schoolClass'])
                ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")))
                ->orderBy('education_unit_id')->orderBy('school_class_id')->orderBy('name')->paginate(10)->withQueryString(),
            'spp-settings' => SppSetting::with('educationUnit')
                ->orderBy('education_unit_id')->paginate(10)->withQueryString(),
            'fee-discounts' => FeeDiscount::with(['student.schoolClass.educationUnit', 'feeType'])
                ->when($search, fn ($query) => $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")))
                ->latest()->paginate(10)->withQueryString(),
            default => Student::with(['schoolClass.educationUnit', 'academicYear'])
                ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%")->orWhere('nisn', 'like', "%{$search}%")))
                ->when($request->integer('class_id'), fn ($query, $classId) => $query->where('school_class_id', $classId))
                ->when($request->integer('year_id'), fn ($query, $yearId) => $query->where('academic_year_id', $yearId))
                ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status')->value() === 'active'))
                ->when($request->integer('unit_id'), fn ($query, $unitId) => $query->whereHas('schoolClass', fn ($q) => $q->where('education_unit_id', $unitId)))
                ->orderBy('name')->paginate(in_array($request->integer('per_page'), [10, 25, 50, 100]) ? $request->integer('per_page') : 10)->withQueryString(),
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

        return view('master.index', [
            'tab' => $tab,
            'data' => $data,
            'classes' => SchoolClass::with('educationUnit')->orderBy('education_unit_id')->orderBy('name')->get(),
            'educationUnits' => EducationUnit::orderBy('name')->get(),
            'academicYears' => AcademicYear::orderByDesc('is_active')->latest()->get(),
            'studentOptions' => Student::with('schoolClass.educationUnit')->where('is_active', true)->orderBy('name')->get(),
            'feeTypeOptions' => FeeType::with(['educationUnit', 'schoolClass'])->where('is_active', true)->orderBy('name')->get(),
            'stats' => [
                'students' => Student::count(),
                'active_students' => Student::where('is_active', true)->count(),
                'classes' => SchoolClass::count(),
                'education_units' => EducationUnit::where('is_active', true)->count(),
                'fee_types' => FeeType::where('is_active', true)->count(),
            ],
            'showCreate' => $request->routeIs('master.create'),
        ]);
    }

    public function create(Request $request)
    {
        return $this->index($request);
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
        SchoolClass::create($this->validateClass($request));
        return $this->done('classes', 'Kelas berhasil ditambahkan.');
    }

    public function updateClass(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->update($this->validateClass($request, $schoolClass));
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
        Student::create($this->validateStudent($request));
        return $this->done('students', 'Data siswa berhasil ditambahkan.');
    }

    public function updateStudent(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validateStudent($request, $student));
        return $this->done('students', 'Data siswa berhasil diperbarui.');
    }

    public function exportStudents(): StreamedResponse
    {
        $headers = $this->studentImportHeaders();
        $rows = [$headers];
        foreach (Student::with('schoolClass.educationUnit')->orderBy('name')->get() as $index => $student) {
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
                $student->is_active ? 'Aktif' : 'Nonaktif',
                $student->exit_date?->format('Y-m-d'),
                $student->inactive_reason,
            ];
        }

        return response()->streamDownload(function () use ($rows) {
            $path = tempnam(sys_get_temp_dir(), 'students-xlsx-');
            StudentXlsx::write($path, $rows);
            readfile($path);
            unlink($path);
        }, 'data-siswa-'.now()->format('Y-m-d').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
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

    public function importStudents(ImportStudentsRequest $request, StudentImportService $importer): RedirectResponse
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return $this->done('students', 'Import gagal. Atur tahun pelajaran aktif terlebih dahulu.');
        }

        $result = $importer->import($request->file('file'), $activeYear);
        $imported = $result['imported'];
        $createdClasses = $result['created_classes'];
        $failures = $result['failures'];

        if ($imported === 0) {
            return redirect()->route('master.index', ['tab' => 'students'])
                ->with('error', 'Tidak ada data yang berhasil diimpor. '.implode(' ', array_slice($failures, 0, 5)));
        }

        $message = "{$imported} data siswa berhasil diimpor.";
        if ($createdClasses > 0) {
            $message .= " {$createdClasses} kelas baru dibuat otomatis.";
        }
        if ($failures) {
            $message .= ' '.count($failures).' baris dilewati: '.implode(' ', array_slice($failures, 0, 3));
        }

        return $this->done('students', $message);
    }

    public function storeFeeType(Request $request): RedirectResponse
    {
        FeeType::create($this->validateFeeType($request));
        return $this->done('fee-types', 'Jenis pembayaran berhasil ditambahkan.');
    }

    public function updateFeeType(Request $request, FeeType $feeType): RedirectResponse
    {
        $feeType->update($this->validateFeeType($request, $feeType));
        return $this->done('fee-types', 'Jenis pembayaran berhasil diperbarui.');
    }

    public function storeSppSetting(Request $request): RedirectResponse
    {
        SppSetting::create($this->validateSppSetting($request));
        return $this->done('spp-settings', 'Set SPP berhasil ditambahkan.');
    }

    public function updateSppSetting(Request $request, SppSetting $sppSetting): RedirectResponse
    {
        $sppSetting->update($this->validateSppSetting($request, $sppSetting));
        return $this->done('spp-settings', 'Set SPP berhasil diperbarui.');
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

    public function destroy(string $type, int $id): RedirectResponse
    {
        $model = match ($type) {
            'academic-years' => AcademicYear::findOrFail($id),
            'education-units' => EducationUnit::findOrFail($id),
            'classes' => SchoolClass::findOrFail($id),
            'fee-types' => FeeType::findOrFail($id),
            'spp-settings' => SppSetting::findOrFail($id),
            'fee-discounts' => FeeDiscount::findOrFail($id),
            default => Student::findOrFail($id),
        };
        try {
            $model->delete();
        } catch (QueryException) {
            return redirect()->route('master.index', ['tab' => $type])
                ->with('error', 'Data masih digunakan dan tidak dapat dihapus.');
        }

        return $this->done($type, 'Data berhasil dihapus.');
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

    private function validateStudent(Request $request, ?Student $student = null): array
    {
        $validated = $request->validate([
            'nis' => ['required', 'max:30', Rule::unique('students')->ignore($student)],
            'nisn' => ['nullable', 'max:30', Rule::unique('students')->ignore($student)],
            'name' => ['required', 'max:120'],
            'birth_place' => ['nullable', 'max:120'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['required', Rule::in(['L', 'P'])],
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
            'exit_date' => ['nullable', Rule::requiredIf(fn () => ! $request->boolean('is_active')), 'date', 'after_or_equal:entry_date'],
            'inactive_reason' => ['nullable', Rule::requiredIf(fn () => ! $request->boolean('is_active')), 'max:255'],
            'guardian_name' => ['nullable', 'max:120'],
            'whatsapp' => ['nullable', 'max:25'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        unset($validated['education_unit_id']);
        $validated['is_active'] = $request->boolean('is_active');
        if ($validated['is_active']) {
            $validated['exit_date'] = null;
            $validated['inactive_reason'] = null;
        }
        return $validated;
    }

    private function studentImportHeaders(): array
    {
        return ['No', 'NIS', 'NISN', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'Nama Ayah', 'Nama Ibu', 'No. WA Ayah', 'No. WA Ibu', 'Provinsi', 'Kabupaten/Kota', 'Kecamatan', 'Desa', 'Alamat', 'Unit Pendidikan', 'Kelas', 'Tanggal Masuk', 'Status', 'Tanggal Keluar', 'Alasan Nonaktif'];
    }

    private function validateFeeType(Request $request, ?FeeType $feeType = null): array
    {
        $allClasses = $request->input('school_class_id') === 'all';
        if ($allClasses) {
            $request->merge(['school_class_id' => null]);
        }

        $validated = $request->validate([
            'name' => ['required', 'max:120'],
            'education_unit_id' => ['required', 'exists:education_units,id'],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('education_unit_id', $request->education_unit_id)],
            'amount' => ['required', 'integer', 'min:0'],
            'period' => ['nullable', Rule::in(['Bulanan', 'Tahunan', 'Sekali Bayar'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if (! $allClasses && ! $validated['school_class_id']) {
            throw ValidationException::withMessages(['school_class_id' => 'Pilih kelas atau Semua Kelas.']);
        }
        $baseCode = Str::upper(Str::slug($validated['name'], '-'));
        $baseCode = substr($baseCode ?: 'PEMBAYARAN', 0, 20);
        $code = $baseCode;
        $suffix = 2;
        while (FeeType::where('code', $code)->when($feeType, fn ($query) => $query->whereKeyNot($feeType->id))->exists()) {
            $number = '-'.$suffix++;
            $code = substr($baseCode, 0, 20 - strlen($number)).$number;
        }
        $validated['code'] = $code;
        $validated['period'] = $validated['period'] ?? $feeType?->period ?? 'Bulanan';
        $validated['is_active'] = $request->boolean('is_active');
        return $validated;
    }

    private function validateSppSetting(Request $request, ?SppSetting $sppSetting = null): array
    {
        $validated = $request->validate([
            'education_unit_id' => ['required', 'exists:education_units,id', Rule::unique('spp_settings')->ignore($sppSetting)],
            'amount' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        return $validated;
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
        return redirect()->route('master.index', ['tab' => $tab])->with('success', $message);
    }
}
