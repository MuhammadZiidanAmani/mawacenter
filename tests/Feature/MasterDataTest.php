<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Bill;
use App\Models\BillManualPayment;
use App\Models\EducationUnit;
use App\Models\FeeDiscount;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\SppPayment;
use App\Models\SppPaymentItem;
use App\Models\Student;
use App\Models\User;
use App\Services\BillService;
use App\Services\ChargeCalculator;
use App\Support\StudentXlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_master_data_page_can_be_opened(): void
    {
        $this->get('/manajemen-siswa/data-siswa?per_page=25')
            ->assertOk()
            ->assertSee('Data Siswa')
            ->assertDontSee('* Wajib diisi')
            ->assertSee('Show')
            ->assertSee('Import')
            ->assertSee('Export')
            ->assertSee('Download Template')
            ->assertSee('student-flat-header', false)
            ->assertSee('student-flat-table', false)
            ->assertSee('Search:')
            ->assertSee('data-student-filter-unit', false)
            ->assertSee('data-student-filter-class', false)
            ->assertSee('<option value="">semua</option>', false);
    }

    public function test_list_toolbar_supports_500_and_all_entries(): void
    {
        foreach (range(1, 11) as $number) {
            EducationUnit::create([
                'code' => 'U'.$number,
                'name' => 'Unit '.$number,
                'is_active' => true,
            ]);
        }

        $this->get('/master-data?tab=education-units&per_page=all')
            ->assertOk()
            ->assertSee('value="500"', false)
            ->assertSee('<option value="all" selected>All</option>', false)
            ->assertSee('Unit 1')
            ->assertSee('Unit 11');
    }

    public function test_student_filter_unit_shows_codes_and_class_filter_depends_on_selected_unit(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mi = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $miClass = SchoolClass::create(['education_unit_id' => $mi->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        Student::create(['nis' => '1001', 'name' => 'Siswa MI', 'gender' => 'L', 'school_class_id' => $miClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '1002', 'name' => 'Siswa MTs', 'gender' => 'L', 'school_class_id' => $mtsClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);

        $this->get('/manajemen-siswa/data-siswa?unit_id='.$mi->id)
            ->assertOk()
            ->assertSee('<option value="'.$mi->id.'" selected>MI</option>', false)
            ->assertDontSee('<option value="'.$mi->id.'" selected>MI - Madrasah Ibtidaiyah</option>', false)
            ->assertSee('data-student-filter-class', false)
            ->assertSee('data-unit-id="'.$mi->id.'"', false)
            ->assertSee('data-unit-id="'.$mts->id.'"', false)
            ->assertSee('Siswa MI')
            ->assertDontSee('Siswa MTs');
    }

    public function test_students_can_be_transferred_between_classes(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $sourceClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $targetClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII B', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '2001', 'name' => 'Siswa Pindah', 'gender' => 'L', 'school_class_id' => $sourceClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);

        $this->get('/manajemen-siswa/pindah-kelas?unit_id='.$unit->id.'&class_id='.$sourceClass->id.'&year_id='.$year->id)
            ->assertOk()
            ->assertSee('Pindah Kelas')
            ->assertSee('Siswa Pindah')
            ->assertSee('Pindahkan Kelas');

        $this->post('/manajemen-siswa/pindah-kelas', [
            'student_ids' => [$student->id],
            'source_year_id' => $year->id,
            'target_year_id' => $year->id,
            'target_class_id' => $targetClass->id,
            'unit_id' => $unit->id,
            'class_id' => $sourceClass->id,
        ])->assertRedirect('/manajemen-siswa/pindah-kelas?year_id='.$year->id.'&unit_id='.$unit->id.'&class_id='.$targetClass->id);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_class_id' => $targetClass->id,
            'academic_year_id' => $year->id,
        ]);
    }

    public function test_students_cannot_be_transferred_to_class_with_duplicate_name(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $ponpes = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $sourceClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $targetClass = SchoolClass::create(['education_unit_id' => $ponpes->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create(['nis' => '250010', 'name' => 'KENZEI IBRA RAMBU RABBANI', 'gender' => 'L', 'school_class_id' => $sourceClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '250011', 'name' => 'KENZEI IBRA RAMBU RABBANI', 'gender' => 'L', 'school_class_id' => $targetClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);

        $this->from('/manajemen-siswa/pindah-kelas')->followingRedirects()->post('/manajemen-siswa/pindah-kelas', [
            'student_ids' => [$student->id],
            'source_year_id' => $year->id,
            'target_year_id' => $year->id,
            'target_class_id' => $targetClass->id,
            'unit_id' => $mts->id,
            'class_id' => $sourceClass->id,
        ])->assertOk()
            ->assertSee('Data tidak dapat dipindahkan')
            ->assertSee('Kelas tujuan sudah memiliki siswa dengan nama yang sama: KENZEI IBRA RAMBU RABBANI.');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_class_id' => $sourceClass->id,
            'academic_year_id' => $year->id,
        ]);
    }

    public function test_students_can_be_promoted_to_next_year_and_class(): void
    {
        $currentYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $nextYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => false]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $sourceClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $targetClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'II A', 'level' => 'Kelas II']);
        $student = Student::create(['nis' => '3001', 'name' => 'Siswa Naik', 'gender' => 'P', 'school_class_id' => $sourceClass->id, 'academic_year_id' => $currentYear->id, 'is_active' => true]);

        $this->get('/manajemen-siswa/naik-kelas?unit_id='.$unit->id.'&class_id='.$sourceClass->id.'&year_id='.$currentYear->id)
            ->assertOk()
            ->assertSee('Naik Kelas')
            ->assertSee('Siswa Naik')
            ->assertSee('Naikkan Kelas');

        $this->post('/manajemen-siswa/naik-kelas', [
            'student_ids' => [$student->id],
            'source_year_id' => $currentYear->id,
            'target_year_id' => $nextYear->id,
            'target_class_id' => $targetClass->id,
            'unit_id' => $unit->id,
            'class_id' => $sourceClass->id,
        ])->assertRedirect('/manajemen-siswa/naik-kelas?year_id='.$nextYear->id.'&unit_id='.$unit->id.'&class_id='.$targetClass->id);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'school_class_id' => $targetClass->id,
            'academic_year_id' => $nextYear->id,
        ]);
    }

    public function test_student_table_hides_status_and_can_be_sorted_from_column_headings(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mi = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $miClass = SchoolClass::create(['education_unit_id' => $mi->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        Student::create(['nis' => '1001', 'name' => 'Zahra MI', 'gender' => 'P', 'school_class_id' => $miClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '1002', 'name' => 'Alya MTs', 'gender' => 'P', 'school_class_id' => $mtsClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);

        $this->get('/manajemen-siswa/data-siswa')
            ->assertOk()
            ->assertSee('Import')
            ->assertSee('Export')
            ->assertSee('student-action-column', false)
            ->assertDontSee('<th>Status</th>', false)
            ->assertDontSee('<td>2025/2026</td>', false)
            ->assertDontSee('sort=year', false)
            ->assertSee('sort=nis&amp;direction=asc', false)
            ->assertSee('sort=unit&amp;direction=desc', false)
            ->assertViewHas('data', fn ($data) => $data->getCollection()->pluck('name')->all() === ['Alya MTs', 'Zahra MI']);

        $this->get('/manajemen-siswa/data-siswa?sort=unit&direction=asc')
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->getCollection()->pluck('name')->all() === ['Zahra MI', 'Alya MTs']);

        $this->get('/manajemen-siswa/data-siswa?sort=name&direction=desc')
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->getCollection()->pluck('name')->all() === ['Zahra MI', 'Alya MTs']);
    }

    public function test_all_master_create_forms_use_dedicated_pages(): void
    {
        foreach ([
            'academic-years' => 'Tambah Tahun Pelajaran',
            'education-units' => 'Tambah Unit Pendidikan',
            'classes' => 'Tambah Kelas',
            'fee-types' => 'Tambah Kategori Pembayaran',
            'fee-discounts' => 'Tambah Keringanan',
        ] as $tab => $heading) {
            $listResponse = $this->get('/master-data?tab='.$tab)
                ->assertOk()
                ->assertSee('Show')
                ->assertSee('/master-data/create?tab='.$tab, false);
            $listResponse->assertSee('Search:');

            $this->get('/master-data/create?tab='.$tab)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Kembali ke Daftar')
                ->assertDontSee('data-modal-open', false);
        }

        $this->get('/manajemen-siswa/data-siswa/create')
            ->assertOk()
            ->assertSee('data-copy-father-whatsapp', false)
            ->assertSee('data-student-region="province"', false)
            ->assertSee('data-student-region="city"', false)
            ->assertSee('data-student-region="district"', false)
            ->assertSee('data-student-region="village"', false);

        $this->get('/master-data/create?tab=fee-types')
            ->assertOk()
            ->assertSee('Kategori Pembayaran')
            ->assertSee('SPP')
            ->assertSee('Tahun Pelajaran')
            ->assertSee('Kelas Tertentu')
            ->assertSee('data-registration-class-list', false)
            ->assertSee('data-registration-all-classes', false)
            ->assertSee('Kategori akan berlaku untuk seluruh kelas pada unit yang dipilih.')
            ->assertSee('data-currency-input', false);

        EducationUnit::create(['code' => 'PAUD', 'name' => 'Pendidikan Anak Usia Dini', 'is_active' => true]);
        $this->get('/master-data/create?tab=classes')
            ->assertOk()
            ->assertSee('<select name="education_unit_id" required><option value="">Pilih Unit Pendidikan</option>', false)
            ->assertDontSee('<select name="education_unit_id" required><option value="1">', false);
    }

    public function test_all_master_data_can_be_created(): void
    {
        $this->post('/master-data/academic-years', ['name' => '2025/2026', 'is_active' => 1])->assertRedirect();
        $this->post('/master-data/education-units', ['code' => 'SMK', 'name' => 'Sekolah Menengah Kejuruan', 'is_active' => 1])->assertRedirect();
        $this->post('/master-data/classes', ['education_unit_id' => EducationUnit::where('code', 'SMK')->first()->id, 'name' => 'VII A', 'is_active' => 1])->assertRedirect();

        $year = AcademicYear::first();
        $class = SchoolClass::first();
        $this->post('/master-data/students', [
            'nis' => '1001', 'nisn' => '2001', 'name' => 'Alya Maharani', 'gender' => 'P',
            'education_unit_id' => $class->education_unit_id, 'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertRedirect();
        $this->post('/master-data/fee-types', [
            'name' => 'SPP Bulanan', 'education_unit_id' => $class->education_unit_id,
            'school_class_id' => $class->id, 'amount' => 350000, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseCount('students', 1);
        $this->assertTrue(Student::first()->is_active);
        $this->assertTrue(FeeType::first()->is_active);
    }

    public function test_class_list_can_be_filtered_by_education_unit(): void
    {
        $firstUnit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $secondUnit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $paudUnit = EducationUnit::create(['code' => 'PAUD', 'name' => 'Pendidikan Anak Usia Dini', 'is_active' => true]);
        $raUnit = EducationUnit::create(['code' => 'RA', 'name' => 'Raudlatul Athfal', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'BAKULAN', 'name' => 'Bakulan', 'is_active' => true]);
        $firstClass = SchoolClass::create(['education_unit_id' => $firstUnit->id, 'name' => 'Kelas MI', 'level' => 'Kelas MI']);
        $secondClass = SchoolClass::create(['education_unit_id' => $secondUnit->id, 'name' => 'Kelas MTs', 'level' => 'Kelas MTs']);
        SchoolClass::create(['education_unit_id' => $raUnit->id, 'name' => 'A2', 'level' => 'Kelas A2']);
        SchoolClass::create(['education_unit_id' => $raUnit->id, 'name' => 'A1', 'level' => 'Kelas A1']);
        SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'Kelas Bakulan', 'level' => 'Kelas Bakulan']);
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        Student::create(['nis' => '1001', 'name' => 'Siswa MI', 'gender' => 'L', 'school_class_id' => $firstClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '2001', 'name' => 'Siswa Pertama', 'gender' => 'L', 'school_class_id' => $secondClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '2002', 'name' => 'Siswa Kedua', 'gender' => 'P', 'school_class_id' => $secondClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);

        $this->get('/master-data?tab=classes&unit_id='.$firstUnit->id)
            ->assertOk()
            ->assertSee('master-class-filter-panel', false)
            ->assertSee('Filter unit pendidikan', false)
            ->assertSee('Filter tahun pelajaran', false)
            ->assertSee('Filter status data', false)
            ->assertSee('<option value="'.$firstUnit->id.'" selected>MI</option>', false)
            ->assertSee('<option value="'.$year->id.'" selected>'.$year->name.'</option>', false)
            ->assertDontSee('<option value="'.$firstUnit->id.'" selected>MI - Madrasah Ibtidaiyah</option>', false)
            ->assertSeeInOrder([
                '<option value="'.$paudUnit->id.'" >PAUD</option>',
                '<option value="'.$raUnit->id.'" >RA</option>',
                '<option value="'.$firstUnit->id.'" selected>MI</option>',
                '<option value="'.$secondUnit->id.'" >MTs</option>',
                '<option value="'.$otherUnit->id.'" >BAKULAN</option>',
            ], false)
            ->assertViewHas('data', fn ($data) => $data->total() === 1 && $data->first()->education_unit_id === $firstUnit->id);

        $this->get('/master-data?tab=classes')
            ->assertOk()
            ->assertSee('<td><strong>2</strong></td>', false)
            ->assertSee('<option value="'.$year->id.'" selected>'.$year->name.'</option>', false)
            ->assertViewHas('data', fn ($data) => $data->getCollection()->pluck('name')->all() === [
                'Kelas MI', 'Kelas MTs',
            ] && $data->getCollection()->firstWhere('name', 'Kelas MTs')->students_count === 2);
    }

    public function test_class_list_can_be_filtered_by_year_and_status(): void
    {
        $currentYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $previousYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => false]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $currentClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII', 'is_active' => true]);
        $previousClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VIII A', 'level' => 'Kelas VIII', 'is_active' => true]);
        $inactiveClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'IX A', 'level' => 'Kelas IX', 'is_active' => false]);

        Student::create(['nis' => '3001', 'name' => 'Siswa Aktif Tahun Ini', 'gender' => 'L', 'school_class_id' => $currentClass->id, 'academic_year_id' => $currentYear->id, 'is_active' => true]);
        Student::create(['nis' => '3002', 'name' => 'Siswa Tahun Lalu', 'gender' => 'P', 'school_class_id' => $previousClass->id, 'academic_year_id' => $previousYear->id, 'is_active' => true]);
        Student::create(['nis' => '3003', 'name' => 'Siswa Kelas Nonaktif', 'gender' => 'L', 'school_class_id' => $inactiveClass->id, 'academic_year_id' => $currentYear->id, 'is_active' => true]);

        $this->get('/master-data?tab=classes&year_id='.$currentYear->id.'&status=active')
            ->assertOk()
            ->assertSee('<option value="'.$currentYear->id.'" selected>'.$currentYear->name.'</option>', false)
            ->assertSee('<option value="active" selected>Aktif</option>', false)
            ->assertViewHas('data', fn ($data) => $data->total() === 1
                && $data->first()->is($currentClass)
                && $data->first()->students_count === 1);

        $this->get('/master-data?tab=classes&status=inactive')
            ->assertOk()
            ->assertSee('<option value="inactive" selected>Nonaktif</option>', false)
            ->assertViewHas('data', fn ($data) => $data->total() === 1 && $data->first()->is($inactiveClass));
    }

    public function test_same_class_name_can_be_used_by_different_education_units(): void
    {
        $firstUnit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $secondUnit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);

        $this->post('/master-data/classes', [
            'education_unit_id' => $firstUnit->id,
            'name' => '7A',
            'is_active' => 1,
        ])->assertRedirect('/master-data?tab=classes');

        $this->post('/master-data/classes', [
            'education_unit_id' => $secondUnit->id,
            'name' => '7A',
            'is_active' => 1,
        ])->assertRedirect('/master-data?tab=classes');

        $this->assertDatabaseHas('school_classes', ['education_unit_id' => $firstUnit->id, 'name' => '7A']);
        $this->assertDatabaseHas('school_classes', ['education_unit_id' => $secondUnit->id, 'name' => '7A']);
        $this->assertDatabaseCount('school_classes', 2);
    }

    public function test_same_class_name_cannot_be_repeated_in_the_same_education_unit(): void
    {
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7A', 'is_active' => true]);

        $this->post('/master-data/classes', [
            'education_unit_id' => $unit->id,
            'name' => '7A',
            'is_active' => 1,
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('school_classes', 1);
    }

    public function test_academic_year_list_only_shows_requested_columns(): void
    {
        AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);

        $this->get('/master-data?tab=academic-years')
            ->assertOk()
            ->assertSee('sort=name&amp;direction=asc', false)
            ->assertSee('sort=is_active&amp;direction=desc', false)
            ->assertDontSee('<th>Jumlah Siswa</th>', false)
            ->assertDontSee('<th>Dibuat</th>', false);
    }

    public function test_student_with_financial_history_can_be_deleted_with_related_sample_data(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $student = Student::create(['nis' => '1001', 'name' => 'Siswa Bertagihan', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Bill::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id, 'source_type' => 'manual',
            'generation_key' => 'test-bill-'.$student->id, 'title' => 'Tagihan Uji', 'issue_date' => '2026-06-13',
            'original_amount' => 100000, 'discount_amount' => 0, 'total_amount' => 100000,
            'paid_amount' => 0, 'remaining_amount' => 100000, 'status' => 'Belum Dibayar',
        ]);

        $bill = Bill::create([
            'student_id' => $student->id, 'academic_year_id' => $year->id, 'source_type' => 'manual',
            'generation_key' => 'test-bill-second-'.$student->id, 'title' => 'Tagihan Uji Kedua', 'issue_date' => '2026-06-13',
            'original_amount' => 50000, 'discount_amount' => 0, 'total_amount' => 50000,
            'paid_amount' => 0, 'remaining_amount' => 50000, 'status' => 'Belum Dibayar',
        ]);
        BillManualPayment::create([
            'bill_id' => $bill->id, 'transaction_at' => '2026-06-13 10:00:00', 'payment_method' => 'Cash', 'paid_amount' => 10000,
        ]);

        $this->delete('/master-data/students/'.$student->id)
            ->assertRedirect('/manajemen-siswa/data-siswa')
            ->assertSessionHas('success', 'Data siswa beserta seluruh data contoh terkait berhasil dihapus.');

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('bills', ['student_id' => $student->id]);
        $this->assertDatabaseMissing('bill_manual_payments', ['bill_id' => $bill->id]);
    }

    public function test_fee_type_uses_matching_education_unit_and_class(): void
    {
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);

        $this->post('/master-data/fee-types', [
            'name' => 'SPP Bulanan', 'education_unit_id' => $otherUnit->id,
            'school_class_id' => $class->id, 'amount' => 350000, 'is_active' => 1,
        ])->assertSessionHasErrors('school_class_id');

        $this->post('/master-data/fee-types', [
            'name' => 'SPP Bulanan', 'education_unit_id' => $unit->id,
            'school_class_id' => $class->id, 'amount' => 350000, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('fee_types', [
            'name' => 'SPP Bulanan',
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'amount' => 350000,
        ]);
    }

    public function test_fee_type_can_apply_to_all_classes_in_education_unit(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PP', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Salaf', 'level' => 'Kelas Salaf']);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'X A', 'level' => 'Kelas X A']);
        $student = Student::create(['nis' => '9001', 'name' => 'Santri', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        $otherStudent = Student::create(['nis' => '9002', 'name' => 'Siswa MA', 'gender' => 'P', 'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id]);

        $this->post('/master-data/fee-types', [
            'name' => 'Pendaftaran Pondok', 'education_unit_id' => $unit->id,
            'school_class_id' => 'all', 'amount' => 2000000, 'is_active' => 1,
        ])->assertRedirect();

        $feeType = FeeType::first();
        $this->assertNull($feeType->school_class_id);
        $this->assertSame(2000000, app(ChargeCalculator::class)->baseAmount($student, 'fee_type', $feeType));
        $this->assertSame(0, app(ChargeCalculator::class)->baseAmount($otherStudent, 'fee_type', $feeType));
        $this->get('/master-data?tab=fee-types')
            ->assertOk()
            ->assertSee('<option value="'.$year->id.'" selected>'.$year->name.'</option>', false)
            ->assertSee('Semua Kelas');
    }

    public function test_fee_type_list_can_be_filtered_by_unit_class_year_and_status(): void
    {
        $currentYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $previousYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => false]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $otherClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VIII A', 'level' => 'Kelas VIII']);
        $outsideClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'X A', 'level' => 'Kelas X']);

        $specificFee = FeeType::create(['code' => 'SPP-VII-A', 'name' => 'SPP VII A', 'payment_group' => 'spp', 'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'academic_year_id' => $currentYear->id, 'amount' => 350000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true]);
        $allClassFee = FeeType::create(['code' => 'KEGIATAN-MTS', 'name' => 'Kegiatan MTs', 'payment_group' => 'lain-lain', 'education_unit_id' => $unit->id, 'school_class_id' => null, 'academic_year_id' => $currentYear->id, 'amount' => 100000, 'period' => 'Tahunan', 'creates_bill' => false, 'is_active' => true]);
        FeeType::create(['code' => 'SPP-VIII-A', 'name' => 'SPP VIII A', 'payment_group' => 'spp', 'education_unit_id' => $unit->id, 'school_class_id' => $otherClass->id, 'academic_year_id' => $currentYear->id, 'amount' => 360000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true]);
        FeeType::create(['code' => 'SPP-LAMA', 'name' => 'SPP Tahun Lalu', 'payment_group' => 'spp', 'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'academic_year_id' => $previousYear->id, 'amount' => 300000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true]);
        $inactiveFee = FeeType::create(['code' => 'NONAKTIF', 'name' => 'Kategori Nonaktif', 'payment_group' => 'lain-lain', 'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'academic_year_id' => $currentYear->id, 'amount' => 50000, 'period' => 'Tahunan', 'creates_bill' => false, 'is_active' => false]);
        FeeType::create(['code' => 'SPP-MA', 'name' => 'SPP MA', 'payment_group' => 'spp', 'education_unit_id' => $otherUnit->id, 'school_class_id' => $outsideClass->id, 'academic_year_id' => $currentYear->id, 'amount' => 400000, 'period' => 'Bulanan', 'creates_bill' => true, 'is_active' => true]);

        $this->get('/master-data?tab=fee-types&unit_id='.$unit->id.'&class_id='.$class->id.'&year_id='.$currentYear->id.'&status=active')
            ->assertOk()
            ->assertSee('master-fee-filter-panel', false)
            ->assertSee('data-student-filter-class', false)
            ->assertSee('<option value="'.$unit->id.'" selected>MTs</option>', false)
            ->assertSee('<option value="'.$class->id.'" data-unit-id="'.$unit->id.'" selected>VII A</option>', false)
            ->assertSee('<option value="'.$currentYear->id.'" selected>'.$currentYear->name.'</option>', false)
            ->assertViewHas('data', fn ($data) => $data->total() === 2
                && $data->getCollection()->pluck('id')->contains($specificFee->id)
                && $data->getCollection()->pluck('id')->contains($allClassFee->id));

        $this->get('/master-data?tab=fee-types&status=inactive')
            ->assertOk()
            ->assertSee('<option value="inactive" selected>Nonaktif</option>', false)
            ->assertViewHas('data', fn ($data) => $data->total() === 1 && $data->first()->is($inactiveFee));
    }

    public function test_spp_category_is_unique_per_scope(): void
    {
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);

        $this->post('/master-data/fee-types', [
            'name' => 'SPP', 'payment_group' => 'spp',
            'education_unit_id' => $unit->id, 'amount' => 250000, 'period' => 'Bulanan', 'is_active' => 1,
        ])->assertRedirect();

        $this->post('/master-data/fee-types', [
            'name' => 'SPP', 'payment_group' => 'spp',
            'education_unit_id' => $unit->id, 'amount' => 300000, 'period' => 'Bulanan', 'is_active' => 1,
        ])->assertSessionHasErrors('payment_group');

        $this->assertDatabaseCount('fee_types', 1);
        $this->assertTrue(FeeType::first()->is_active);
        $this->assertSame('spp', FeeType::first()->payment_group);
    }

    public function test_payment_category_form_applies_simple_automatic_billing_rules(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);

        $this->get('/master-data?tab=fee-types')
            ->assertOk()
            ->assertSee('Tagihan rutin setiap bulan')
            ->assertSee('Dicatat sesuai bulan yang diikuti')
            ->assertSee('Transaksi Langsung')
            ->assertSee('data-fee-category', false);

        foreach ([
            ['name' => 'SPP', 'payment_group' => 'spp', 'period' => 'Tahunan', 'creates_bill' => 0, 'expected_period' => 'Bulanan', 'expected_bill' => true],
            ['name' => 'Daftar Ulang', 'payment_group' => 'daftar-ulang', 'period' => 'Bulanan', 'creates_bill' => 0, 'expected_period' => 'Sekali Bayar', 'expected_bill' => true],
            ['name' => 'Laundry', 'payment_group' => 'laundry', 'period' => 'Sekali Bayar', 'creates_bill' => 1, 'expected_period' => 'Bulanan', 'expected_bill' => false],
            ['name' => 'Uang Kegiatan', 'payment_group' => 'lain-lain', 'period' => 'Tahunan', 'creates_bill' => 0, 'expected_period' => 'Tahunan', 'expected_bill' => false],
        ] as $category) {
            $this->post('/master-data/fee-types', [
                'name' => $category['name'],
                'payment_group' => $category['payment_group'],
                'education_unit_id' => $unit->id,
                'academic_year_id' => $year->id,
                'class_scope' => 'all',
                'school_class_id' => 'all',
                'amount' => 100000,
                'period' => $category['period'],
                'creates_bill' => $category['creates_bill'],
                'is_active' => 1,
            ])->assertRedirect('/master-data?tab=fee-types');

            $saved = FeeType::where('name', $category['name'])->firstOrFail();
            $this->assertSame($category['expected_period'], $saved->period);
            $this->assertSame($category['expected_bill'], $saved->creates_bill);
        }
    }

    public function test_registration_category_is_managed_by_fee_types_and_available_for_payment(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $firstClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $secondClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII B', 'level' => 'Kelas VII']);

        $this->post('/master-data/fee-types', [
            'name' => 'Daftar Ulang',
            'payment_group' => 'daftar-ulang',
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'school_class_ids' => [$firstClass->id, $secondClass->id],
            'amount' => 1250000,
            'period' => 'Sekali Bayar',
            'is_active' => 1,
        ])->assertRedirect('/master-data?tab=fee-types');

        $this->assertDatabaseCount('fee_types', 2);
        $setting = FeeType::where('name', 'Daftar Ulang')->where('school_class_id', $firstClass->id)->firstOrFail();
        $this->assertSame('daftar-ulang', $setting->payment_group);
        $this->assertSame('Sekali Bayar', $setting->period);
        $this->assertSame($year->id, $setting->academic_year_id);
        $this->assertSame($firstClass->id, $setting->school_class_id);
        $this->assertSame(1250000, $setting->amount);
        $this->assertSame(2, FeeType::where('name', 'Daftar Ulang')->pluck('code')->unique()->count());

        $this->get('/master-data?tab=fee-types')
            ->assertOk()
            ->assertSee('Kategori Pembayaran')
            ->assertSee('Daftar Ulang')
            ->assertSee('MTs')
            ->assertSee('VII A')
            ->assertSee('2025/2026')
            ->assertSee('Rp 1.250.000')
            ->assertSee('sort=amount&amp;direction=asc', false)
            ->assertDontSee('<th>Tahun Pelajaran</th>', false)
            ->assertDontSee('<th>Status</th>', false)
            ->assertDontSee('<th>Kelompok</th>', false)
            ->assertDontSee('<th>Periode</th>', false)
            ->assertDontSee('Set Daftar Ulang');

        $this->put('/master-data/fee-types/'.$setting->id, [
            'name' => 'Daftar Ulang Baru',
            'payment_group' => 'daftar-ulang',
            'education_unit_id' => $unit->id,
            'academic_year_id' => $year->id,
            'school_class_ids' => [$firstClass->id],
            'amount' => 1300000,
            'period' => 'Sekali Bayar',
            'is_active' => 1,
        ])->assertRedirect('/master-data?tab=fee-types');
        $this->assertDatabaseHas('fee_types', [
            'id' => $setting->id,
            'name' => 'Daftar Ulang Baru',
            'payment_group' => 'daftar-ulang',
            'amount' => 1300000,
        ]);

        Student::create(['nis' => '1001', 'name' => 'Siswa Daftar Ulang', 'gender' => 'L', 'school_class_id' => $firstClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $this->get('/keuangan/pembayaran/lain-lain/create?category=daftar-ulang')
            ->assertOk()
            ->assertSee('Daftar Ulang Baru')
            ->assertSee('VII A');
    }

    public function test_spp_discount_reduces_student_charge(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '1001', 'name' => 'Ahmad', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'code' => 'LAUNDRY', 'name' => 'Laundry Bulanan', 'payment_group' => 'laundry',
            'amount' => 100000, 'period' => 'Bulanan', 'is_active' => true,
        ]);
        $this->createSppCategory($unit, 600000);

        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 300000, 'start_date' => now()->subDay()->toDateString(), 'is_active' => 1,
        ])->assertRedirect();

        $charge = app(ChargeCalculator::class)->calculate($student, 'spp');
        $this->assertSame(600000, $charge['original_amount']);
        $this->assertSame(300000, $charge['discount_amount']);
        $this->assertSame(300000, $charge['final_amount']);
        $this->get('/master-data/create?tab=fee-discounts')
            ->assertOk()
            ->assertSee('MTs - 1001 - Ahmad')
            ->assertSee('>Laundry Bulanan</option>', false)
            ->assertDontSee('Laundry Bulanan · MTs · VII A');
        $this->get('/master-data?tab=fee-discounts')
            ->assertOk()
            ->assertSee('Keringanan Biaya')
            ->assertDontSee('<th>Keringanan</th>', false)
            ->assertDontSee('Set Biaya')
            ->assertDontSee('Yang Dibayarkan')
            ->assertDontSee('data-spp-row-toggle="fee-discount-', false)
            ->assertSee('<th>Unit Pendidikan</th>', false)
            ->assertSee('<th>Kelas</th>', false)
            ->assertDontSee('<th>Status</th>', false)
            ->assertDontSee('Periode')
            ->assertDontSee('Rp 300.000');
    }

    public function test_fee_discount_list_can_be_filtered_by_unit_class_year_and_status(): void
    {
        $currentYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $previousYear = AcademicYear::create(['name' => '2024/2025', 'is_active' => false]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $otherClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VIII A', 'level' => 'Kelas VIII']);
        $outsideClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'X A', 'level' => 'Kelas X']);
        $this->createSppCategory($unit, 600000);
        $this->createSppCategory($otherUnit, 700000);

        $student = Student::create(['nis' => '4001', 'name' => 'Siswa Keringanan Aktif', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $currentYear->id, 'is_active' => true]);
        $otherClassStudent = Student::create(['nis' => '4002', 'name' => 'Siswa Kelas Lain', 'gender' => 'P', 'school_class_id' => $otherClass->id, 'academic_year_id' => $currentYear->id, 'is_active' => true]);
        $previousYearStudent = Student::create(['nis' => '4003', 'name' => 'Siswa Tahun Lalu', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $previousYear->id, 'is_active' => true]);
        $outsideStudent = Student::create(['nis' => '4004', 'name' => 'Siswa MA', 'gender' => 'P', 'school_class_id' => $outsideClass->id, 'academic_year_id' => $currentYear->id, 'is_active' => true]);

        $activeDiscount = FeeDiscount::create(['student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount', 'discount_value' => 150000, 'start_date' => '2026-01-01', 'is_active' => true]);
        FeeDiscount::create(['student_id' => $otherClassStudent->id, 'source_type' => 'spp', 'discount_type' => 'amount', 'discount_value' => 50000, 'start_date' => '2026-01-01', 'is_active' => true]);
        FeeDiscount::create(['student_id' => $previousYearStudent->id, 'source_type' => 'spp', 'discount_type' => 'amount', 'discount_value' => 75000, 'start_date' => '2026-01-01', 'is_active' => true]);
        FeeDiscount::create(['student_id' => $outsideStudent->id, 'source_type' => 'spp', 'discount_type' => 'amount', 'discount_value' => 90000, 'start_date' => '2026-01-01', 'is_active' => true]);
        $inactiveDiscount = FeeDiscount::create(['student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount', 'discount_value' => 25000, 'start_date' => '2025-01-01', 'is_active' => false]);

        $this->get('/master-data?tab=fee-discounts&unit_id='.$unit->id.'&class_id='.$class->id.'&year_id='.$currentYear->id.'&status=active')
            ->assertOk()
            ->assertSee('master-discount-filter-panel', false)
            ->assertSee('<option value="'.$unit->id.'" selected>MTs</option>', false)
            ->assertSee('<option value="'.$class->id.'" data-unit-id="'.$unit->id.'" selected>VII A</option>', false)
            ->assertSee('<option value="'.$currentYear->id.'" selected>'.$currentYear->name.'</option>', false)
            ->assertDontSee('data-spp-row-toggle="fee-discount-', false)
            ->assertViewHas('data', fn ($data) => $data->total() === 1 && $data->first()->is($activeDiscount));

        $this->get('/master-data?tab=fee-discounts&status=inactive')
            ->assertOk()
            ->assertSee('<option value="inactive" selected>Nonaktif</option>', false)
            ->assertViewHas('data', fn ($data) => $data->total() === 1 && $data->first()->is($inactiveDiscount));
    }

    public function test_spp_discount_applies_when_period_starts_mid_month(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PP', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '9A', 'level' => 'Kelas 9']);
        $student = Student::create(['nis' => '1002', 'name' => 'Zidan', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $this->createSppCategory($unit, 600000);
        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 350000, 'start_date' => '2026-06-12', 'is_active' => true,
        ]);

        $charge = app(ChargeCalculator::class)->calculateSppMonth($student, 2026, 6);

        $this->assertSame(600000, $charge['original_amount']);
        $this->assertSame(350000, $charge['discount_amount']);
        $this->assertSame(250000, $charge['final_amount']);
    }

    public function test_other_payment_discount_reduces_only_selected_fee_type(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MA', 'name' => 'Madrasah Aliyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'X A', 'level' => 'Kelas X']);
        $student = Student::create(['nis' => '2001', 'name' => 'Bella', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'code' => 'GEDUNG',
            'name' => 'Uang Gedung', 'amount' => 2000000, 'period' => 'Sekali Bayar', 'is_active' => true,
        ]);

        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'fee_type', 'fee_type_id' => $feeType->id,
            'discount_type' => 'amount', 'discount_value' => 500000,
            'start_date' => now()->subDay()->toDateString(), 'is_active' => 1,
        ])->assertRedirect();

        $charge = app(ChargeCalculator::class)->calculate($student, 'fee_type', $feeType);
        $this->assertSame(2000000, $charge['original_amount']);
        $this->assertSame(500000, $charge['discount_amount']);
        $this->assertSame(1500000, $charge['final_amount']);
    }

    public function test_discount_cannot_exceed_original_amount_or_overlap(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1 A', 'level' => 'Kelas 1']);
        $student = Student::create(['nis' => '3001', 'name' => 'Candra', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        $this->createSppCategory($unit, 400000);

        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 500000, 'start_date' => '2026-01-01', 'is_active' => 1,
        ])->assertSessionHasErrors('discount_value');

        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 100000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $this->post('/master-data/fee-discounts', [
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'percentage',
            'discount_value' => 50, 'start_date' => '2026-06-01', 'is_active' => 1,
        ])->assertSessionHasErrors('start_date');
    }

    public function test_payment_student_options_show_unit_code_nis_and_name_in_unit_order(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $paud = EducationUnit::create(['code' => 'PAUD', 'name' => 'Pendidikan Anak Usia Dini', 'is_active' => true]);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $paudClass = SchoolClass::create(['education_unit_id' => $paud->id, 'name' => 'Kelompok Bermain', 'level' => 'Kelompok Bermain']);
        Student::create(['nis' => '1001', 'name' => 'Alya MTs', 'gender' => 'P', 'school_class_id' => $mtsClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '2002', 'name' => 'Zara PAUD', 'gender' => 'P', 'school_class_id' => $paudClass->id, 'academic_year_id' => $year->id, 'is_active' => true]);

        foreach (['/keuangan/pembayaran/spp/create', '/keuangan/pembayaran/lain-lain/create'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSeeInOrder(['PAUD - 2002 - Zara PAUD', 'MTs - 1001 - Alya MTs'])
                ->assertSee('Waktu Transaksi')
                ->assertSee('type="date" name="transaction_date"', false)
                ->assertDontSee('placeholder="DD/MM/YYYY"', false)
                ->assertDontSee('data-date-picker-button', false)
                ->assertSee('Ketik NIS atau nama siswa...')
                ->assertSee('data-student-picker', false)
                ->assertDontSee('* Wajib diisi')
                ->assertDontSee('2002 - Zara PAUD · PAUD');
        }

        $this->get('/keuangan/pembayaran/spp/create')
            ->assertDontSee('required readonly data-spp-date', false)
            ->assertSee('type="date" name="transaction_date"', false)
            ->assertSee('type="text" value="', false)
            ->assertSee('readonly data-wib-clock', false)
            ->assertSee('type="hidden" name="transaction_time"', false)
            ->assertDontSee('type="time" name="transaction_time"', false);

        $this->get('/keuangan/pembayaran/lain-lain/create')
            ->assertDontSee('required readonly data-other-date', false)
            ->assertSee('type="date" name="transaction_date"', false)
            ->assertSee('readonly data-wib-clock', false)
            ->assertSee('type="hidden" name="transaction_time"', false)
            ->assertDontSee('type="time" name="transaction_time"', false);

        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_spp_payment_supports_installment_and_later_settlement(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '4001', 'name' => 'Dina', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        Student::create(['nis' => '4002', 'name' => 'Siswa Nonaktif', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => false]);
        $this->createSppCategory($unit, 600000);
        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'spp', 'discount_type' => 'amount',
            'discount_value' => 300000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $this->get('/keuangan/pembayaran/spp')
            ->assertOk()
            ->assertSee('Pembayaran SPP')
            ->assertSee('payment-single-canvas', false)
            ->assertSee('/keuangan/pembayaran/spp/create');
        $this->get('/keuangan/pembayaran/spp?search=Dina&per_page=25')
            ->assertOk()->assertSee('Search:')->assertSee('sort=name&amp;direction=asc', false)->assertDontSee('Data Pembayaran SPP');
        $this->get('/keuangan/pembayaran/spp?sort=unit&direction=desc')->assertOk();
        $this->get('/keuangan/pembayaran/spp/create')->assertOk()->assertSee('Informasi Transaksi')->assertSee('Dina')->assertDontSee('Siswa Nonaktif');
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()->assertJson(['first_payable_month' => 1]);
        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$student->id.'&year=2026&months[]=6')
            ->assertUnprocessable();
        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$student->id.'&year=2026&months[]=1&months[]=2')
            ->assertOk()->assertJson(['original_amount' => 1200000, 'discount_amount' => 600000, 'total_amount' => 600000]);

        $payload = [
            'transaction_date' => '2026-06-12', 'transaction_time' => '08:30', 'student_id' => $student->id,
            'months' => [1, 2], 'year' => 2026, 'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 200000,
        ];
        $this->post('/keuangan/pembayaran/spp', $payload)
            ->assertRedirect()
            ->assertSessionHas('payment_action');
        $this->get('/keuangan/pembayaran/spp?search=Dina&per_page=25')
            ->assertOk()
            ->assertSee('Dina')
            ->assertSee('Unit Pendidikan: MTs')
            ->assertSee('class="registration-payment-detail spp-payment-detail"', false)
            ->assertSee('title="Edit Transaksi"', false)
            ->assertDontSee('data-spp-correction-url', false);
        $this->get('/keuangan/pembayaran/spp?search=Tidak-Ada')
            ->assertOk()->assertDontSee('Dina');

        $this->assertDatabaseHas('spp_payments', [
            'student_id' => $student->id, 'total_amount' => 600000, 'paid_amount' => 200000,
            'remaining_amount' => 400000, 'payment_status' => 'Belum Lunas',
        ]);
        $this->assertDatabaseHas('spp_payment_items', ['student_id' => $student->id, 'month' => 1, 'paid_amount' => 200000, 'remaining_amount' => 100000]);
        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$student->id.'&year=2026&months[]=1&months[]=2')
            ->assertOk()->assertJson(['paid_amount' => 200000, 'remaining_amount' => 400000, 'payment_status' => 'Belum Lunas']);

        $this->post('/keuangan/pembayaran/spp', array_merge($payload, ['paid_amount' => 400000]))
            ->assertRedirect()
            ->assertSessionHas('payment_action');
        $this->assertDatabaseHas('spp_payments', ['student_id' => $student->id, 'paid_amount' => 400000, 'remaining_amount' => 0, 'payment_status' => 'Lunas']);
        $this->assertDatabaseCount('spp_payments', 2);
        $this->assertDatabaseCount('spp_payment_items', 3);

        $this->post('/keuangan/pembayaran/spp', array_merge($payload, ['paid_amount' => 1]))->assertSessionHasErrors('months');
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()->assertJson(['first_payable_month' => 3]);
        $this->assertDatabaseCount('spp_payments', 2);
        $this->assertSame(600000, SppPayment::sum('paid_amount'));
        $this->assertSame(600000, SppPaymentItem::sum('paid_amount'));

        $payment = SppPayment::latest('id')->firstOrFail();
        $this->getJson('/keuangan/pembayaran/spp/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('student.name', 'Dina')
            ->assertJsonPath('paid_amount', 400000);
        $receipt = $this->get('/keuangan/pembayaran/spp/'.$payment->id.'/receipt');
        $receipt->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Kwitansi Pembayaran')
            ->assertSee("window.addEventListener('load', () => window.print())", false);
        $this->get('/keuangan/pembayaran/spp/'.$payment->id.'/receipt/download')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->put('/keuangan/pembayaran/spp/'.$payment->id, [
            'transaction_date' => '13/06/2026',
            'transaction_time' => '18.00',
            'payment_method' => 'Transfer',
            'status' => 'Pending',
            'paid_amount' => 300000,
        ])->assertRedirect('/keuangan/pembayaran/spp');
        $this->assertDatabaseHas('spp_payments', [
            'id' => $payment->id,
            'transaction_at' => '2026-06-13 18:00:00',
            'payment_method' => 'Transfer',
            'status' => 'Pending',
            'paid_amount' => 300000,
            'remaining_amount' => 100000,
            'payment_status' => 'Belum Lunas',
        ]);
        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'month' => 1, 'paid_amount' => 100000, 'remaining_amount' => 0]);
        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'month' => 2, 'paid_amount' => 200000, 'remaining_amount' => 100000]);

        $this->delete('/keuangan/pembayaran/spp/'.$payment->id)->assertRedirect('/keuangan/pembayaran/spp');
        $this->assertDatabaseMissing('spp_payments', ['id' => $payment->id]);
        $this->assertDatabaseCount('spp_payments', 1);
        $this->assertDatabaseCount('spp_payment_items', 1);
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()->assertJson(['first_payable_month' => 1]);
    }

    public function test_spp_selection_reports_the_oldest_outstanding_year(): void
    {
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '4010', 'name' => 'Siswa Menunggak', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $academicYear->id,
            'entry_date' => '2025-01-01', 'is_active' => true,
        ]);
        $this->createSppCategory($unit, 100000);
        $payment = SppPayment::create([
            'student_id' => $student->id, 'transaction_at' => '2025-10-01 08:00:00',
            'payment_method' => 'Cash', 'status' => 'Diterima', 'original_amount' => 1000000,
            'discount_amount' => 0, 'total_amount' => 1000000, 'paid_amount' => 1000000,
            'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        foreach (range(1, 10) as $month) {
            $payment->items()->create([
                'student_id' => $student->id, 'year' => 2025, 'month' => $month,
                'original_amount' => 100000, 'discount_amount' => 0, 'total_amount' => 100000,
                'paid_amount' => 100000, 'remaining_amount' => 0, 'payment_status' => 'Lunas',
            ]);
        }

        $this->get('/keuangan/pembayaran/spp/create?student_id='.$student->id)
            ->assertOk()
            ->assertSee('class="spp-year-control"', false)
            ->assertSee('data-spp-arrears-notice', false);
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()
            ->assertJsonPath('oldest_outstanding.year', 2025)
            ->assertJsonPath('oldest_outstanding.month', 11)
            ->assertJsonPath('oldest_outstanding.month_name', 'November');
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2025')
            ->assertOk()
            ->assertJsonPath('first_payable_month', 11);

        $newStudent = Student::create([
            'nis' => '4011', 'name' => 'Siswa Mulai Juli', 'gender' => 'P',
            'school_class_id' => $class->id, 'academic_year_id' => $academicYear->id,
            'entry_date' => '2025-07-10', 'is_active' => true,
        ]);
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$newStudent->id.'&year=2025')
            ->assertOk()
            ->assertJsonPath('first_payable_month', 8)
            ->assertJsonPath('months.0.applicable', false)
            ->assertJsonPath('months.6.applicable', false)
            ->assertJsonPath('months.6.included_in_registration', true)
            ->assertJsonPath('months.7.applicable', true);
    }

    public function test_mts_and_ma_july_spp_is_included_in_registration_payment_only(): void
    {
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $students = [];

        foreach ([
            ['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'class' => 'VII A'],
            ['code' => 'MA', 'name' => 'Madrasah Aliyah', 'class' => 'X A'],
            ['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'class' => 'Asrama A'],
        ] as $index => $unitData) {
            $unit = EducationUnit::create(['code' => $unitData['code'], 'name' => $unitData['name'], 'is_active' => true]);
            $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => $unitData['class'], 'level' => $unitData['class']]);
            $students[$unitData['code']] = Student::create([
                'nis' => 'JULI-'.($index + 1), 'name' => 'Siswa '.$unitData['code'], 'gender' => 'L',
                'school_class_id' => $class->id, 'academic_year_id' => $academicYear->id,
                'entry_date' => '2025-07-01', 'is_active' => true,
            ]);
            $this->createSppCategory($unit, 100000);
        }

        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$students['MTs']->id.'&year=2025')
            ->assertOk()
            ->assertJsonPath('first_payable_month', 8)
            ->assertJsonPath('oldest_outstanding.month', 8)
            ->assertJsonPath('months.6.applicable', false)
            ->assertJsonPath('months.6.included_in_registration', true)
            ->assertJsonPath('months.6.payment_status', 'Termasuk Daftar Ulang')
            ->assertJsonPath('months.7.applicable', true);
        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$students['MTs']->id.'&year=2026&months[]=1&months[]=2&months[]=3&months[]=4&months[]=5&months[]=6')
            ->assertStatus(422)
            ->assertJsonValidationErrors('months')
            ->assertJsonPath('errors.months.0', 'Pembayaran harus dimulai dari bulan Agustus 2025 dan dipilih secara berurutan.');
        $this->post('/keuangan/pembayaran/spp', [
            'transaction_date' => '2026-06-23', 'transaction_time' => '08:30',
            'student_id' => $students['MTs']->id, 'months' => [1, 2, 3, 4, 5, 6], 'year' => 2026,
            'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 600000,
        ])->assertSessionHasErrors('months');

        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$students['MA']->id.'&year=2025')
            ->assertOk()
            ->assertJsonPath('first_payable_month', 8)
            ->assertJsonPath('months.6.included_in_registration', true);

        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$students['PONPES']->id.'&year=2025')
            ->assertOk()
            ->assertJsonPath('first_payable_month', 7)
            ->assertJsonPath('months.6.applicable', true)
            ->assertJsonPath('months.6.included_in_registration', false);

        $this->getJson('/keuangan/pembayaran/spp/quote?student_id='.$students['MTs']->id.'&year=2025&months[]=7')
            ->assertStatus(422)
            ->assertJsonValidationErrors('months');

        $result = app(BillService::class)->generateSpp($academicYear, 2025, [7, 8]);

        $this->assertSame(['created' => 4, 'existing' => 0, 'skipped' => 2], $result);
        $this->assertDatabaseMissing('bills', ['student_id' => $students['MTs']->id, 'source_type' => 'spp', 'year' => 2025, 'month' => 7]);
        $this->assertDatabaseMissing('bills', ['student_id' => $students['MA']->id, 'source_type' => 'spp', 'year' => 2025, 'month' => 7]);
        $this->assertDatabaseHas('bills', ['student_id' => $students['PONPES']->id, 'source_type' => 'spp', 'year' => 2025, 'month' => 7]);
    }

    public function test_spp_payments_can_be_previewed_and_imported_from_monthly_report_xlsx(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Ponpes Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '10A', 'level' => 'Kelas 10']);
        $student = Student::create([
            'nis' => '220001', 'name' => 'ABDILLAH SAEFI HAMMAM', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $otherUnit = EducationUnit::create(['code' => 'PAUD', 'name' => 'PAUD Mawa', 'is_active' => true]);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'Kelompok Bermain', 'level' => 'PAUD']);
        $otherStudent = Student::create([
            'nis' => '220001', 'name' => 'SISWA NIS SAMA UNIT LAIN', 'gender' => 'L',
            'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $this->createSppCategory($unit, 600000);

        $path = tempnam(sys_get_temp_dir(), 'spp-import-test-');
        StudentXlsx::write($path, [
            ['Data Laporan SPP'],
            ['No', 'NIS', 'Nama', 'Unit Pendidikan', 'Kelas', 'Petugas', 'Cara bayar', 'Bulan', 'Tahun', 'Waktu', 'Nominal'],
            [1, '220001', 'ABDILLAH SAEFI HAMMAM', 'PONPES MAMBAUL HIKMAH', '10A', 'Ziidan Amani', 'cash', 'januari', 2026, '2026-01-06 10:08:00', 250000],
            [2, '220001', 'ABDILLAH SAEFI HAMMAM', 'PONPES MAMBAUL HIKMAH', '10A', 'Ziidan Amani', 'transfer', 'januari', 2026, '2026-01-07 10:08:00', 350000],
            [3, '999999', 'SISWA BELUM ADA', 'PONPES MAMBAUL HIKMAH', '10A', 'Ziidan Amani', 'cash', 'januari', 2026, '2026-01-08 10:08:00', 600000],
        ]);
        $workbook = file_get_contents($path);
        unlink($path);

        $preview = $this->post('/keuangan/pembayaran/spp/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('laporan-spp.xlsx', $workbook),
        ]);

        $preview->assertOk()->assertSee('Preview Import Pembayaran')->assertSee('NIS 999999 tidak ditemukan.');
        $this->assertDatabaseCount('spp_payments', 0);
        $token = $preview->viewData('importToken');

        $this->post('/keuangan/pembayaran/spp/import', ['token' => $token])
            ->assertRedirect('/keuangan/pembayaran/spp')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('spp_payments', 2);
        $this->assertDatabaseCount('spp_payment_items', 2);
        $this->assertSame(600000, SppPayment::sum('paid_amount'));
        $this->assertDatabaseHas('spp_payments', ['operator_name' => 'Ziidan Amani', 'payment_method' => 'Transfer']);
        $this->assertDatabaseHas('spp_payment_items', ['student_id' => $student->id, 'month' => 1, 'paid_amount' => 350000, 'remaining_amount' => 0]);
        $this->assertDatabaseMissing('spp_payment_items', ['student_id' => $otherStudent->id]);

        $this->get('/keuangan/pembayaran/spp?search=ABDILLAH')
            ->assertOk()
            ->assertSee('Januari 2026');
        $this->getJson('/keuangan/pembayaran/spp/months?student_id='.$student->id.'&year=2026')
            ->assertOk()
            ->assertJson([
                'first_payable_month' => 2,
                'months' => [
                    [
                        'year' => 2026,
                        'month' => 1,
                        'payment_status' => 'Lunas',
                    ],
                ],
            ]);

        $this->post('/keuangan/pembayaran/spp', [
            'transaction_date' => '2026-01-09',
            'transaction_time' => '08:30',
            'student_id' => $student->id,
            'months' => [1],
            'year' => 2026,
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'paid_amount' => 600000,
        ])->assertSessionHasErrors([
            'months' => 'SPP bulan Januari 2026 sudah lunas dan tidak dapat dibayar kembali.',
        ]);
        $this->assertDatabaseCount('spp_payments', 2);
        $this->assertDatabaseCount('spp_payment_items', 2);

        $duplicatePreview = $this->post('/keuangan/pembayaran/spp/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('laporan-spp.xlsx', $workbook),
        ]);
        $duplicatePreview->assertOk();
        $this->assertSame(2, $duplicatePreview->viewData('importPreview')['duplicates']);
        $this->assertDatabaseCount('spp_payments', 2);
    }

    public function test_spp_receipt_opens_as_direct_print_html(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PP', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '12A', 'level' => 'Kelas 12']);
        $student = Student::create([
            'nis' => '230199',
            'name' => 'Muhammad Syarif Robiansyah',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $payment = SppPayment::create([
            'student_id' => $student->id,
            'transaction_at' => '2026-06-12 15:14:00',
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'original_amount' => 600000,
            'discount_amount' => 0,
            'total_amount' => 600000,
            'paid_amount' => 600000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);
        $payment->items()->create([
            'student_id' => $student->id,
            'year' => 2026,
            'month' => 6,
            'original_amount' => 600000,
            'discount_amount' => 0,
            'total_amount' => 600000,
            'paid_amount' => 600000,
            'remaining_amount' => 0,
            'payment_status' => 'Lunas',
        ]);

        $receipt = $this->get('/keuangan/pembayaran/spp/'.$payment->id.'/receipt');

        $receipt->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('SPP-20260612-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT))
            ->assertSee('@page { size: A4 portrait; margin: 0; }', false)
            ->assertSee('Juni')
            ->assertSee('Keringanan (Rp)')
            ->assertSee("window.addEventListener('load', () => window.print())", false);
    }

    public function test_other_payment_uses_fee_type_and_automatic_discount(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '5001', 'name' => 'Rina', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $feeType = FeeType::create(['education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'code' => 'DAFTAR-ULANG', 'name' => 'Daftar Ulang', 'amount' => 1000000, 'period' => 'once', 'is_active' => true]);
        FeeDiscount::create([
            'student_id' => $student->id, 'source_type' => 'fee_type', 'fee_type_id' => $feeType->id,
            'discount_type' => 'amount', 'discount_value' => 250000, 'start_date' => '2026-06-01', 'is_active' => true,
        ]);

        $this->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang')
            ->assertOk()
            ->assertSee('Pembayaran Daftar Ulang')
            ->assertSee('sort=total&amp;direction=desc', false)
            ->assertDontSee('Data Transaksi')
            ->assertSee('/keuangan/pembayaran/lain-lain/create');
        $this->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang&sort=unit&direction=asc')->assertOk();
        $this->get('/keuangan/pembayaran/lain-lain/create?category=daftar-ulang')
            ->assertOk()
            ->assertSee('Tambah Pembayaran Daftar Ulang')
            ->assertSee('Total Bayar')
            ->assertDontSee('Nominal Dibayar Sekarang')
            ->assertSee('Rina')
            ->assertSee('Daftar Ulang');
        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()->assertJson(['original_amount' => 1000000, 'discount_amount' => 250000, 'paid_amount' => 0, 'remaining_amount' => 750000]);
        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-06-12', 'transaction_time' => '10:15',
            'student_id' => $student->id, 'fee_type_id' => $feeType->id,
            'payment_method' => 'Transfer', 'status' => 'Diterima', 'paid_amount' => 500000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang')
            ->assertSessionHas('payment_action');
        $this->assertDatabaseHas('other_payments', [
            'student_id' => $student->id, 'fee_type_id' => $feeType->id,
            'original_amount' => 1000000, 'discount_amount' => 250000, 'paid_amount' => 500000,
            'remaining_amount' => 250000, 'payment_status' => 'Belum Lunas',
        ]);
        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-06-12', 'transaction_time' => '11:15',
            'student_id' => $student->id, 'fee_type_id' => $feeType->id,
            'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 750001,
        ])->assertSessionHasErrors('paid_amount');
        $this->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang&search=Rina&per_page=25')
            ->assertOk()
            ->assertSee('Rina')
            ->assertSee('Daftar Ulang')
            ->assertSee('500.000');
        $this->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang&search=tidak-ada')
            ->assertOk()
            ->assertDontSee('Rina');
        $payment = OtherPayment::firstOrFail();
        $this->get(route('finance.other.receipt.download', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertSame(500000, OtherPayment::sum('paid_amount'));
    }

    public function test_other_payments_can_be_mapped_previewed_and_imported_from_xlsx(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'PONPES MAMBAUL HIKMAH', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7', 'level' => 'Kelas 7']);
        Student::create(['nis' => '260001', 'name' => 'ABDU ARIQIN HALIM', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $feeType = FeeType::create(['education_unit_id' => $unit->id, 'code' => 'PONDOK-1447', 'name' => 'Pendaftaran Pondok 1447/1448 H', 'amount' => 6500000, 'period' => 'Sekali Bayar', 'is_active' => true]);

        $path = tempnam(sys_get_temp_dir(), 'other-import-test-');
        StudentXlsx::write($path, [
            ['No', 'NIS', 'Nama', 'Petugas', 'Kategori Pembayaran', 'Unit Pendidikan', 'Kelas', 'Cara bayar', 'Nominal', 'Waktu'],
            [1, '260001', 'ABDU ARIQIN HALIM', 'Ziidan Amani', 'PENDAFTARAN PONDOK 1447/1448', 'PONPES MAMBAUL HIKMAH', '7', 'cash', 2000000, '2026-05-14 08:55:00'],
            [2, '260001', 'ABDU ARIQIN HALIM', 'Ziidan Amani', 'PENDAFTARAN PONDOK 1447/1448', 'PONPES MAMBAUL HIKMAH', '7', 'transfer', 4500000, '2026-05-15 08:55:00'],
            [3, '999999', 'SISWA BELUM ADA', 'Ziidan Amani', 'PENDAFTARAN PONDOK 1447/1448', 'PONPES MAMBAUL HIKMAH', '7', 'cash', 500000, '2026-05-16 08:55:00'],
        ]);
        $workbook = file_get_contents($path);
        unlink($path);

        $preview = $this->post('/keuangan/pembayaran/lain-lain/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('transaksi.xlsx', $workbook),
        ]);
        $preview->assertOk()->assertSee('Pemetaan Kategori Pembayaran')->assertSee('NIS 999999 tidak ditemukan.');
        $this->assertSame($feeType->id, (int) collect($preview->viewData('importMappings'))->first());
        $this->assertDatabaseCount('other_payments', 0);

        $this->post('/keuangan/pembayaran/lain-lain/import', ['token' => $preview->viewData('importToken')])
            ->assertRedirect('/keuangan/pembayaran/lain-lain')->assertSessionHas('success');

        $this->assertDatabaseCount('other_payments', 2);
        $this->assertSame(6500000, OtherPayment::sum('paid_amount'));
        $this->assertDatabaseHas('other_payments', ['paid_amount' => 4500000, 'remaining_amount' => 0, 'payment_status' => 'Lunas', 'operator_name' => 'Ziidan Amani']);
    }

    public function test_outstanding_bills_are_shown_from_synced_bills(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '7001', 'name' => 'Tagihan Siswa', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'entry_date' => '2025-07-01', 'is_active' => true]);
        $this->createSppCategory($unit, 600000);

        FeeType::create([
            'education_unit_id' => $unit->id, 'code' => 'DAFTAR-ULANG', 'name' => 'Daftar Ulang',
            'amount' => 500000, 'period' => 'Tahunan', 'is_active' => true,
        ]);

        $this->post('/keuangan/pembayaran/spp', [
            'transaction_date' => '2026-01-05', 'transaction_time' => '08:30', 'student_id' => $student->id,
            'months' => [1], 'year' => 2026, 'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 600000,
        ])->assertRedirect();

        $this->post('/keuangan/tagihan/sync', [
            'year' => 2026,
            'until_month' => 6,
        ])->assertRedirect('/keuangan/tagihan?year=2026&until_month=6');

        $this->get('/keuangan/tagihan?year=2026&until_month=6&per_page=25&sort=total&direction=desc')
            ->assertOk()
            ->assertSee('Tagihan Siswa')
            ->assertSee('Perbarui Tagihan')
            ->assertSee('Daftar Tagihan Siswa')
            ->assertSee('SPP')
            ->assertSee('Lain-lain')
            ->assertSee('Bayar SPP')
            ->assertSee('Bayar Lain-lain')
            ->assertSee('Januari 2026')
            ->assertSee('Februari 2026')
            ->assertSee('Juni 2026')
            ->assertSee('Daftar Ulang')
            ->assertSee('Rp 7.100.000');

        $this->assertDatabaseHas('bills', [
            'student_id' => $student->id,
            'source_type' => 'spp',
            'year' => 2026,
            'month' => 1,
            'remaining_amount' => 600000,
            'status' => 'Belum Dibayar',
        ]);
        $this->assertDatabaseHas('bills', [
            'student_id' => $student->id,
            'source_type' => 'spp',
            'year' => 2026,
            'month' => 2,
            'remaining_amount' => 600000,
        ]);
    }

    public function test_spp_generation_defaults_to_january_2025_for_old_students(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PAUD', 'name' => 'PAUD', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Kelompok Bermain', 'level' => 'Kelas KB']);
        $student = Student::create(['nis' => '7501', 'name' => 'Siswa Lama', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'entry_date' => '2023-01-10', 'is_active' => true]);
        $this->createSppCategory($unit, 100000);

        app(BillService::class)->generateSppFromEntryUntil($year, 2025, 3);

        $this->assertDatabaseMissing('bills', ['student_id' => $student->id, 'source_type' => 'spp', 'year' => 2024, 'month' => 12]);
        $this->assertDatabaseHas('bills', ['student_id' => $student->id, 'source_type' => 'spp', 'year' => 2025, 'month' => 1]);
        $this->assertDatabaseHas('bills', ['student_id' => $student->id, 'source_type' => 'spp', 'year' => 2025, 'month' => 3]);
    }

    public function test_spp_generation_can_use_student_billing_start_override_for_old_debt(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PAUD', 'name' => 'PAUD', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'Kelompok Bermain', 'level' => 'Kelas KB']);
        $student = Student::create(['nis' => '7502', 'name' => 'Siswa Tunggakan Lama', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'entry_date' => '2025-07-10', 'billing_start_date' => '2023-01-01', 'is_active' => true]);
        $this->createSppCategory($unit, 100000);

        app(BillService::class)->generateSppFromEntryUntil($year, 2023, 2);

        $this->assertDatabaseHas('bills', ['student_id' => $student->id, 'source_type' => 'spp', 'year' => 2023, 'month' => 1]);
        $this->assertDatabaseHas('bills', ['student_id' => $student->id, 'source_type' => 'spp', 'year' => 2023, 'month' => 2]);
        $this->assertDatabaseMissing('bills', ['student_id' => $student->id, 'source_type' => 'spp', 'year' => 2022, 'month' => 12]);
    }

    public function test_reports_combine_filter_and_export_payments(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '8001', 'name' => 'Siswa Laporan', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        SppPayment::create([
            'student_id' => $student->id, 'transaction_at' => '2026-06-10 08:00:00', 'payment_method' => 'Cash',
            'status' => 'Diterima', 'original_amount' => 300000, 'discount_amount' => 0, 'total_amount' => 300000,
            'paid_amount' => 300000, 'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);
        $feeType = FeeType::create(['education_unit_id' => $unit->id, 'code' => 'BUKU', 'name' => 'Buku', 'amount' => 200000, 'period' => 'Sekali Bayar', 'is_active' => true]);
        OtherPayment::create([
            'student_id' => $student->id, 'fee_type_id' => $feeType->id, 'transaction_at' => '2026-06-11 09:00:00',
            'payment_method' => 'Transfer', 'status' => 'Diterima', 'original_amount' => 200000, 'discount_amount' => 0,
            'total_amount' => 200000, 'paid_amount' => 200000, 'remaining_amount' => 0, 'payment_status' => 'Lunas',
        ]);

        $this->get('/laporan?start_date=2026-06-01&end_date=2026-06-30&per_page=25&sort=amount&direction=desc')
            ->assertOk()
            ->assertSee('report-workspace', false)
            ->assertSee('type="date" name="date_from"', false)
            ->assertSee('type="date" name="date_to"', false)
            ->assertDontSee('placeholder="DD/MM/YYYY"', false)
            ->assertDontSee('data-date-picker-button', false)
            ->assertSee('Show')
            ->assertSee('Search:')
            ->assertSee('Unit')
            ->assertSee('Cara Bayar')
            ->assertSee('Status')
            ->assertSee('Rp 500.000')
            ->assertSee('Siswa Laporan')
            ->assertSee('Buku')
            ->assertSee('Transfer')
            ->assertSee('Diterima')
            ->assertSee('sort=date&amp;direction=asc', false);
        $this->get('/laporan?start_date=2026-06-01&end_date=2026-06-30&type=spp')
            ->assertOk()->assertSee('Rp 300.000')->assertDontSee('Buku');
        $this->get('/laporan/export?start_date=2026-06-01&end_date=2026-06-30')
            ->assertOk()->assertDownload('laporan-pembayaran-20260601-20260630.csv');
    }

    public function test_application_settings_can_be_saved(): void
    {
        $this->get('/pengaturan')
            ->assertOk()
            ->assertSee('Pengaturan Aplikasi')
            ->assertSee('Role')
            ->assertSee('Admin')
            ->assertSee('Kasir')
            ->assertSee('Bendahara Perunit')
            ->assertSee('Wali Murid/Siswa');

        $this->put('/pengaturan', [
            'school_name' => 'Pondok Mambaul Hikmah',
            'school_address' => 'Jalan Pendidikan',
            'school_phone' => '08123456789',
            'school_email' => 'admin@example.com',
            'finance_officer' => 'Bendahara',
            'receipt_footer' => 'Simpan struk ini.',
            'default_payment_method' => 'Transfer',
        ])->assertRedirect('/pengaturan')->assertSessionHas('success');

        $this->assertSame('Pondok Mambaul Hikmah', AppSetting::where('key', 'school_name')->value('value'));
        $this->assertSame('Transfer', AppSetting::where('key', 'default_payment_method')->value('value'));
    }

    public function test_spp_payment_nominal_can_be_corrected_with_audit_history(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '6001', 'name' => 'Nadia', 'gender' => 'P', 'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true]);
        $this->createSppCategory($unit, 300000);

        $this->post('/keuangan/pembayaran/spp', [
            'transaction_date' => '2026-06-12', 'transaction_time' => '08:30', 'student_id' => $student->id,
            'months' => [1, 2], 'year' => 2026, 'payment_method' => 'Cash', 'status' => 'Diterima', 'paid_amount' => 400000,
        ])->assertRedirect();

        $payment = SppPayment::first();
        $this->post('/keuangan/pembayaran/spp/'.$payment->id.'/corrections', [
            'new_paid_amount' => 150000,
            'reason' => 'Salah input nominal',
        ])->assertRedirect('/keuangan/pembayaran/spp');

        $this->assertDatabaseHas('spp_payments', [
            'id' => $payment->id, 'paid_amount' => 150000, 'remaining_amount' => 450000, 'payment_status' => 'Belum Lunas',
        ]);
        $this->assertDatabaseHas('spp_payment_corrections', [
            'spp_payment_id' => $payment->id, 'old_paid_amount' => 400000, 'new_paid_amount' => 150000,
            'refund_amount' => 250000, 'reason' => 'Salah input nominal',
        ]);
        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'month' => 1, 'paid_amount' => 150000]);
        $this->assertDatabaseHas('spp_payment_items', ['spp_payment_id' => $payment->id, 'month' => 2, 'paid_amount' => 0]);
        $this->getJson('/keuangan/pembayaran/spp/'.$payment->id)
            ->assertOk()->assertJsonPath('corrections.0.refund_amount', 250000);
        $this->delete('/keuangan/pembayaran/spp/'.$payment->id)->assertSessionHasErrors('transaction');
        $this->assertDatabaseHas('spp_payments', ['id' => $payment->id]);

        $this->post('/keuangan/pembayaran/spp/'.$payment->id.'/corrections', [
            'new_paid_amount' => 200000,
            'reason' => 'Tidak boleh menambah lewat koreksi',
        ])->assertSessionHasErrors('new_paid_amount');
    }

    public function test_student_can_be_updated_and_deleted(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '1001', 'name' => 'Nama Lama', 'gender' => 'L', 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $this->put("/master-data/students/{$student->id}", [
            'nis' => '1001', 'name' => 'Nama Baru', 'gender' => 'L', 'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('students', ['name' => 'Nama Baru']);

        $this->delete("/master-data/students/{$student->id}")->assertRedirect();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_student_update_and_delete_return_to_the_active_filters(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '1001', 'name' => 'Nama Lama', 'gender' => 'L', 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $filterParameters = [
            'unit_id' => $unit->id,
            'class_id' => $class->id,
            'year_id' => $year->id,
            'status' => 'active',
            'search' => 'Nama',
            'per_page' => 25,
            'sort' => 'nis',
            'direction' => 'desc',
            'page' => 1,
        ];
        $filters = http_build_query($filterParameters);

        $this->get('/manajemen-siswa/data-siswa?'.$filters)
            ->assertOk()
            ->assertSee('/master-data/students/'.$student->id.'?'.str_replace('&', '&amp;', $filters), false)
            ->assertSee('/master-data/students/'.$student->id.'?'.str_replace('&', '&amp;', $filters), false);

        $filteredPageTwo = http_build_query(array_merge($filterParameters, ['page' => 2]));

        $this->put('/master-data/students/'.$student->id.'?'.$filteredPageTwo, [
            'nis' => '1001', 'name' => 'Nama Baru', 'gender' => 'L',
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertRedirect('/manajemen-siswa/data-siswa?'.$filteredPageTwo);

        $this->delete('/master-data/students/'.$student->id.'?'.$filteredPageTwo)
            ->assertRedirect('/manajemen-siswa/data-siswa?'.$filteredPageTwo);
    }

    public function test_education_unit_page_is_empty_without_seeded_data(): void
    {
        $this->get('/master-data?tab=education-units')
            ->assertOk()
            ->assertSee('Unit Pendidikan')
            ->assertSee('Belum ada data');
    }

    public function test_students_can_be_exported_and_imported_from_xlsx(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);

        $path = tempnam(sys_get_temp_dir(), 'student-test-');
        StudentXlsx::write($path, [
            ['No', 'NIS', 'NISN', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin', 'Nama Ayah', 'Nama Ibu', 'No. WA Ayah', 'No. WA Ibu', 'Provinsi', 'Kabupaten/Kota', 'Kecamatan', 'Desa', 'Alamat', 'Unit Pendidikan', 'Kelas'],
            [1, '1001', '2001', 'Alya Maharani', 'Jakarta', '37209', 'Perempuan', 'Budi', 'Siti', '0811', '0822', 'Jawa Barat', 'Bandung', 'Coblong', 'Dago', 'Jalan Mawar', 'PONPES', '9A'],
            [2, '1001', '2002', 'Alya MI', 'Bandung', '', 'Perempuan', '', '', '', '', '', '', '', '', '', 'MI', 'I A'],
            [3, '', '', 'Data Tidak Valid', '', '', 'Perempuan', '', '', '', '', '', '', '', '', '', 'PONPES', '9A'],
        ]);
        $workbook = file_get_contents($path);

        $previewResponse = $this->post('/master-data/students/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('siswa.xlsx', $workbook),
        ])->assertRedirect();
        unlink($path);

        $location = $previewResponse->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $preview = $this->get($location)
            ->assertOk()
            ->assertSee('Preview Import Data Siswa')
            ->assertDontSee('data-student-import-limit', false)
            ->assertSee('data-status="gagal"', false)
            ->assertDontSee('data-student-import-search', false)
            ->assertDontSee('Tampilkan Semua')
            ->assertSee('Baris perlu diperiksa')
            ->assertSee('Hanya data duplikat dan gagal yang ditampilkan.')
            ->assertSee('Keterangan')
            ->assertSee('PONPES')
            ->assertSee('9A')
            ->assertDontSee('Alya Maharani')
            ->assertDontSee('Alya MI')
            ->assertSee('Data Tidak Valid')
            ->assertSee('NIS kosong.');
        $this->assertSame(2, $preview->viewData('studentImportPreview')['valid']);
        $this->assertCount(1, $preview->viewData('studentImportPreview')['failures']);
        $this->assertDatabaseCount('students', 0);

        $this->post('/master-data/students/import', ['token' => $query['import_token']])
            ->assertRedirect('/manajemen-siswa/data-siswa')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'nis' => '1001',
            'father_name' => 'Budi',
            'mother_name' => 'Siti',
            'birth_date' => '2001-11-14 00:00:00',
        ]);
        $this->assertDatabaseHas('school_classes', ['education_unit_id' => $unit->id, 'name' => '9A']);
        $this->assertDatabaseHas('school_classes', ['education_unit_id' => $otherUnit->id, 'name' => 'I A']);
        $this->assertDatabaseCount('students', 2);

        $duplicateResponse = $this->post('/master-data/students/import/preview', [
            'file' => UploadedFile::fake()->createWithContent('siswa.xlsx', $workbook),
        ])->assertRedirect();
        $duplicateLocation = $duplicateResponse->headers->get('Location');
        $duplicatePreview = $this->get($duplicateLocation)->assertOk();
        $this->assertSame(2, $duplicatePreview->viewData('studentImportPreview')['duplicates']);
        $this->assertDatabaseCount('students', 2);

        $this->get('/master-data/students/export?unit_id='.$unit->id.'&year_id='.$year->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get('/master-data/students/template')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_student_list_and_export_use_active_year_as_default(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'I A', 'level' => 'Kelas I']);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        Student::create(['nis' => '1001', 'name' => 'Siswa MI', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);
        Student::create(['nis' => '1002', 'name' => 'Siswa MTs', 'gender' => 'L', 'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id]);

        $this->get('/manajemen-siswa/data-siswa')
            ->assertOk()
            ->assertViewHas('data', fn ($data) => $data->total() === 2)
            ->assertSee('<option value="'.$year->id.'" selected>'.$year->name.'</option>', false);

        $this->get('/master-data/students/export')
            ->assertOk()
            ->assertDownload('data-siswa-semua-'.now()->format('Y-m-d').'.xlsx');

        $this->get('/master-data/students/export?unit_id='.$unit->id)
            ->assertOk()
            ->assertDownload('data-siswa-mi-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_student_nis_must_be_unique_per_education_unit(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $otherUnit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $otherClass = SchoolClass::create(['education_unit_id' => $otherUnit->id, 'name' => 'I A', 'level' => 'Kelas I']);
        Student::create(['nis' => '1001', 'name' => 'Siswa Lama', 'gender' => 'L', 'school_class_id' => $class->id, 'academic_year_id' => $year->id]);

        $this->post('/master-data/students', [
            'nis' => '1001', 'name' => 'Siswa Baru', 'gender' => 'P',
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id, 'academic_year_id' => $year->id,
            'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertSessionHasErrors('nis');

        $this->assertDatabaseCount('students', 1);

        $this->post('/master-data/students', [
            'nis' => '1001', 'name' => 'Siswa Unit Lain', 'gender' => 'P',
            'education_unit_id' => $otherUnit->id, 'school_class_id' => $otherClass->id, 'academic_year_id' => $year->id,
            'entry_date' => '2026-06-11', 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseCount('students', 2);
    }

    public function test_existing_student_can_be_registered_in_another_unit_without_retyping_identity(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $ponpes = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $ponpesClass = SchoolClass::create(['education_unit_id' => $ponpes->id, 'name' => 'Asrama A', 'level' => 'Asrama']);
        $student = Student::create([
            'nis' => 'MTS-001', 'nisn' => '0098765432', 'name' => 'Ahmad Fauzan', 'gender' => 'L',
            'father_name' => 'Abdullah', 'address' => 'Kudus', 'school_class_id' => $mtsClass->id,
            'academic_year_id' => $year->id, 'is_active' => true,
        ]);

        $this->post('/master-data/students', [
            'existing_student_id' => $student->id, 'nis' => 'PP-099',
            'education_unit_id' => $ponpes->id, 'school_class_id' => $ponpesClass->id,
            'academic_year_id' => $year->id, 'entry_date' => '2026-06-22', 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('students', [
            'identity_student_id' => $student->id, 'nis' => 'PP-099', 'nisn' => '0098765432',
            'name' => 'Ahmad Fauzan', 'father_name' => 'Abdullah', 'address' => 'Kudus',
            'school_class_id' => $ponpesClass->id,
        ]);
    }

    public function test_existing_student_cannot_be_registered_twice_in_the_same_unit(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $firstClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $secondClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII B', 'level' => 'Kelas VII']);
        $student = Student::create(['nis' => '1001', 'name' => 'Ahmad', 'gender' => 'L', 'school_class_id' => $firstClass->id, 'academic_year_id' => $year->id]);

        $this->post('/master-data/students', [
            'existing_student_id' => $student->id, 'nis' => '1002',
            'education_unit_id' => $unit->id, 'school_class_id' => $secondClass->id,
            'academic_year_id' => $year->id, 'entry_date' => '2026-06-22', 'is_active' => 1,
        ])->assertSessionHasErrors('existing_student_id');

        $this->assertDatabaseCount('students', 1);
    }

    public function test_editing_identity_updates_both_unit_registrations(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $ponpes = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $ponpesClass = SchoolClass::create(['education_unit_id' => $ponpes->id, 'name' => 'Asrama A', 'level' => 'Asrama']);
        $identity = Student::create(['nis' => 'MTS-001', 'nisn' => '12345', 'name' => 'Nama Lama', 'gender' => 'L', 'school_class_id' => $mtsClass->id, 'academic_year_id' => $year->id]);
        $registration = Student::create(['identity_student_id' => $identity->id, 'nis' => 'PP-001', 'nisn' => '12345', 'name' => 'Nama Lama', 'gender' => 'L', 'school_class_id' => $ponpesClass->id, 'academic_year_id' => $year->id]);

        $this->put('/master-data/students/'.$registration->id, [
            'nis' => 'PP-001', 'nisn' => '12345', 'name' => 'Nama Baru', 'gender' => 'L',
            'education_unit_id' => $ponpes->id, 'school_class_id' => $ponpesClass->id,
            'academic_year_id' => $year->id, 'entry_date' => '2026-06-22', 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('students', ['id' => $identity->id, 'name' => 'Nama Baru']);
        $this->assertDatabaseHas('students', ['id' => $registration->id, 'name' => 'Nama Baru']);
    }

    public function test_billing_start_date_can_be_changed_without_validating_existing_student_picker(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $mts = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $ponpes = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $mtsClass = SchoolClass::create(['education_unit_id' => $mts->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $ponpesClass = SchoolClass::create(['education_unit_id' => $ponpes->id, 'name' => 'Asrama A', 'level' => 'Asrama']);
        $identity = Student::create([
            'nis' => 'MTS-002', 'name' => 'Ahmad', 'gender' => 'L', 'school_class_id' => $mtsClass->id,
            'academic_year_id' => $year->id, 'entry_date' => '2025-07-01', 'is_active' => true,
        ]);
        $registration = Student::create([
            'identity_student_id' => $identity->id, 'nis' => 'PP-002', 'name' => 'Ahmad', 'gender' => 'L',
            'school_class_id' => $ponpesClass->id, 'academic_year_id' => $year->id,
            'entry_date' => '2025-07-01', 'is_active' => true,
        ]);

        $this->put('/master-data/students/'.$registration->id, [
            'existing_student_id' => 999999,
            'nis' => 'PP-002', 'name' => 'Ahmad', 'gender' => 'L',
            'education_unit_id' => $ponpes->id, 'school_class_id' => $ponpesClass->id,
            'academic_year_id' => $year->id, 'entry_date' => '2025-07-01',
            'billing_start_date' => '2025-05-01', 'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('students', [
            'id' => $registration->id,
            'billing_start_date' => '2025-05-01 00:00:00',
        ]);
        $this->assertDatabaseHas('students', ['id' => $identity->id, 'billing_start_date' => null]);
    }

    public function test_inactive_student_requires_exit_date_and_reason(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);

        $payload = [
            'nis' => '1002', 'name' => 'Siswa Keluar', 'gender' => 'L',
            'education_unit_id' => $unit->id, 'school_class_id' => $class->id,
            'academic_year_id' => $year->id, 'entry_date' => '2025-07-01',
        ];

        $this->post('/master-data/students', $payload)
            ->assertSessionHasErrors(['exit_date', 'inactive_reason']);

        $this->post('/master-data/students', $payload + [
            'exit_date' => '2026-06-01',
            'inactive_reason' => 'Lulus',
        ])->assertRedirect();

        $this->assertDatabaseHas('students', [
            'nis' => '1002', 'is_active' => false, 'exit_date' => '2026-06-01 00:00:00', 'inactive_reason' => 'Lulus',
        ]);
    }

    private function createSppCategory(EducationUnit $unit, int $amount): FeeType
    {
        return FeeType::create([
            'education_unit_id' => $unit->id,
            'payment_group' => 'spp',
            'code' => 'SPP-'.$unit->id,
            'name' => 'SPP',
            'amount' => $amount,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);
    }
}
