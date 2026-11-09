<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSavingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_mock_student_savings_list_with_local_search(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.index'));

        $response->assertOk()
            ->assertSee('Tabungan Santri')
            ->assertSee('Data Simulasi')
            ->assertSee('Total Saldo Titipan')
            ->assertSee('Rp 4.150.000')
            ->assertSee('Dana Titipan · Bukan Penerimaan Sekolah')
            ->assertSee('tidak dihitung sebagai pendapatan atau penerimaan unit pendidikan')
            ->assertSee('data-student-savings-classification', false)
            ->assertSee('Ahmad Fauzan')
            ->assertSee('SIM-260184')
            ->assertSee('Salwa Azzahra')
            ->assertSee('PONPES')
            ->assertSee('Cari nama, NIS, atau unit')
            ->assertSee('data-student-savings', false)
            ->assertSee('data-student-savings-search', false)
            ->assertSee('data-student-savings-empty', false)
            ->assertSee('href="'.route('finance.student-savings.show', 'SIM-260184').'"', false)
            ->assertSee('href="'.route('finance.student-savings.index').'"', false)
            ->assertSee('Setoran Gateway')
            ->assertSee('GW-20261109-0842')
            ->assertSee('BNI Virtual Account')
            ->assertSee('Berhasil')
            ->assertSee('Diproses')
            ->assertSee('Gagal')
            ->assertSee('Pembayaran melewati batas waktu')
            ->assertSee('Nomor virtual account tidak dikenali')
            ->assertSee('Saldo tidak berubah')
            ->assertSee('hanya setoran berstatus Berhasil yang menambah saldo titipan');

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(5, $xpath->query('//*[@data-student-savings-row]'));
        $this->assertCount(4, $xpath->query('//*[@data-student-savings-gateway-row]'));
        $this->assertCount(2, $xpath->query('//*[@data-student-savings-gateway-row and contains(@class, "is-failed")]'));
    }

    public function test_bendahara_without_transaction_management_cannot_view_student_savings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'bendahara']))
            ->get(route('finance.student-savings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_mock_student_savings_mutations(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.show', 'SIM-260184'));

        $response->assertOk()
            ->assertSee('Mutasi Saldo Santri')
            ->assertSee('Data Simulasi')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('NIS SIM-260184')
            ->assertSee('Saldo Saat Ini')
            ->assertSee('Rp 850.000')
            ->assertSee('Saldo Awal')
            ->assertSee('Rp 600.000')
            ->assertSee('Total Setoran')
            ->assertSee('+Rp 450.000')
            ->assertSee('Total Pemakaian')
            ->assertSee('Total Penarikan Tunai')
            ->assertSee('Pemakaian Sekolah')
            ->assertSee('Pembelian perlengkapan belajar')
            ->assertSee('Penarikan Tunai')
            ->assertSee('Diserahkan kepada wali santri')
            ->assertSee('Kasir · Nabila A.')
            ->assertSee('Kasir · Rina S.')
            ->assertSee('08/11/2026 13.40 WIB')
            ->assertSee('Setoran loket sekolah')
            ->assertSee('+Rp 300.000')
            ->assertSee('-Rp 100.000')
            ->assertSee('href="'.route('finance.student-savings.index').'"', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(4, $xpath->query('//table[contains(@class, "student-savings-mutation-table")]/tbody/tr'));
    }

    public function test_unknown_student_savings_account_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.show', 'SIM-TIDAK-ADA'))
            ->assertNotFound();
    }

    public function test_bendahara_without_transaction_management_cannot_view_student_savings_mutations(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'bendahara']))
            ->get(route('finance.student-savings.show', 'SIM-260184'))
            ->assertForbidden();
    }

    public function test_cashier_can_open_mock_deposit_form_for_assigned_unit_only(): void
    {
        Role::updateOrCreate(['key' => 'kasir'], [
            'name' => Role::DEFAULTS['kasir'],
            'permissions' => Role::defaultPermissionsFor('kasir'),
            'is_active' => true,
        ]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $cashier->educationUnits()->attach($unit->id);

        $response = $this->actingAs($cashier)
            ->get(route('finance.student-savings.create'));

        $response->assertOk()
            ->assertSee('Setor Titipan Santri')
            ->assertSee('Data Simulasi')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('SIM-260184')
            ->assertSee('Saldo Sekarang')
            ->assertSee('Rp 850.000')
            ->assertSee('Nominal Setoran')
            ->assertSee('Tanggal Transaksi')
            ->assertSee('Jam Transaksi')
            ->assertSee('Catatan Kasir')
            ->assertSee('Simpan Setoran Simulasi')
            ->assertSee('Dana Titipan · Bukan Penerimaan Sekolah')
            ->assertSee('data-student-savings-deposit-form', false)
            ->assertSee('data-student-savings-deposit-amount', false)
            ->assertDontSee('Aisyah Rahma')
            ->assertDontSee('Rafi Akbar')
            ->assertSee('href="'.route('finance.student-savings.create').'"', false);

        $this->get(route('finance.student-savings.index'))->assertForbidden();
    }

    public function test_admin_can_open_deposit_form_and_list_links_to_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('finance.student-savings.create'))
            ->assertOk()
            ->assertSee('Ahmad Fauzan')
            ->assertSee('Aisyah Rahma')
            ->assertSee('Salwa Azzahra');

        $this->get(route('finance.student-savings.index'))
            ->assertOk()
            ->assertSee('Setor Titipan')
            ->assertSee('href="'.route('finance.student-savings.create').'"', false);
    }

    public function test_cashier_can_open_mock_usage_and_withdrawal_form_for_assigned_unit_only(): void
    {
        Role::updateOrCreate(['key' => 'kasir'], [
            'name' => Role::DEFAULTS['kasir'],
            'permissions' => Role::defaultPermissionsFor('kasir'),
            'is_active' => true,
        ]);
        $unit = EducationUnit::create(['code' => 'RA', 'name' => 'Raudhatul Athfal', 'is_active' => true]);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $cashier->educationUnits()->attach($unit->id);

        $response = $this->actingAs($cashier)
            ->get(route('finance.student-savings.withdraw'));

        $response->assertOk()
            ->assertSee('Pemakaian dan Penarikan Titipan')
            ->assertSee('Pemakaian Sekolah')
            ->assertSee('Penarikan Tunai')
            ->assertSee('Aisyah Rahma')
            ->assertSee('SIM-260245')
            ->assertSee('Rp 425.000')
            ->assertSee('Nominal tidak boleh melebihi saldo titipan santri.')
            ->assertSee('Tujuan / Keterangan')
            ->assertSee('Simpan Pengeluaran Simulasi')
            ->assertSee('Dana Titipan · Bukan Penerimaan Sekolah')
            ->assertSee('data-student-savings-withdrawal-form', false)
            ->assertSee('data-student-savings-withdrawal-amount', false)
            ->assertDontSee('Ahmad Fauzan')
            ->assertDontSee('Salwa Azzahra')
            ->assertSee('href="'.route('finance.student-savings.withdraw').'"', false);
    }

    public function test_admin_list_links_to_usage_and_withdrawal_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.index'))
            ->assertOk()
            ->assertSee('Pakai / Tarik')
            ->assertSee('href="'.route('finance.student-savings.withdraw').'"', false);
    }

    public function test_guardian_sees_mock_balances_for_linked_children_only(): void
    {
        Role::updateOrCreate(['key' => 'orang_tua'], [
            'name' => Role::DEFAULTS['orang_tua'],
            'permissions' => Role::defaultPermissionsFor('orang_tua'),
            'is_active' => true,
        ]);
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VI A', 'level' => 6, 'is_active' => true]);
        $linkedStudent = Student::create([
            'nis' => 'WALI-001', 'name' => 'Anak Terhubung', 'gender' => 'L',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $outsideStudent = Student::create([
            'nis' => 'LUAR-001', 'name' => 'Anak Di Luar Akses', 'gender' => 'P',
            'school_class_id' => $class->id, 'academic_year_id' => $year->id, 'is_active' => true,
        ]);
        $guardian = User::factory()->create(['role' => 'orang_tua']);
        $guardian->guardianStudents()->attach($linkedStudent->id, ['relationship' => 'Wali', 'is_primary' => true]);

        $response = $this->actingAs($guardian)
            ->get(route('guardian.savings.index'));

        $response->assertOk()
            ->assertSee('Tabungan Anak')
            ->assertSee('Data Simulasi')
            ->assertSee('Anak Terhubung')
            ->assertSee('NIS WALI-001 · MI / VI A')
            ->assertSee('Saldo Titipan')
            ->assertSee('Rp 850.000')
            ->assertSee('Total Saldo Anak')
            ->assertSee('Dana Titipan · Bukan Penerimaan Sekolah')
            ->assertSee('1 rekening terhubung')
            ->assertSee('data-guardian-savings-account', false)
            ->assertDontSee('Anak Di Luar Akses')
            ->assertDontSee('LUAR-001')
            ->assertSee('href="'.route('guardian.savings.index').'"', false)
            ->assertSee('href="'.route('guardian.savings.show', $linkedStudent).'"', false);

        $detail = $this->get(route('guardian.savings.show', $linkedStudent));
        $detail->assertOk()
            ->assertSee('Riwayat Mutasi Anak')
            ->assertSee('Dana Titipan · Bukan Penerimaan Sekolah')
            ->assertSee('Anak Terhubung')
            ->assertSee('NIS WALI-001')
            ->assertSee('Saldo Saat Ini')
            ->assertSee('Rp 850.000')
            ->assertSee('Total Setoran')
            ->assertSee('+Rp 450.000')
            ->assertSee('Pemakaian Sekolah')
            ->assertSee('Pembelian perlengkapan belajar')
            ->assertSee('Penarikan Tunai')
            ->assertSee('Diserahkan kepada wali santri')
            ->assertSee('data-guardian-savings-mutation', false)
            ->assertSee('href="'.route('guardian.savings.index').'"', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($detail->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(4, $xpath->query('//*[@data-guardian-savings-mutation]'));

        $this->get(route('guardian.savings.show', $outsideStudent))->assertNotFound();
    }

    public function test_guardian_without_linked_children_sees_empty_savings_state(): void
    {
        Role::updateOrCreate(['key' => 'orang_tua'], [
            'name' => Role::DEFAULTS['orang_tua'],
            'permissions' => Role::defaultPermissionsFor('orang_tua'),
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'orang_tua']))
            ->get(route('guardian.savings.index'))
            ->assertOk()
            ->assertSee('Belum ada data tabungan anak')
            ->assertSee('Akun wali ini belum terhubung ke data santri aktif.')
            ->assertDontSee('data-guardian-savings-account', false);
    }

    public function test_non_guardian_cannot_open_guardian_savings_portal(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('guardian.savings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_mock_student_savings_recap_per_unit(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('finance.student-savings.recap'));

        $response->assertOk()
            ->assertSee('Rekap Titipan per Unit')
            ->assertSee('Data Simulasi')
            ->assertSee('Unit Tercakup')
            ->assertSee('5')
            ->assertSee('Rekening Santri')
            ->assertSee('Total Saldo Titipan')
            ->assertSee('Rp 4.150.000')
            ->assertSee('Dana Titipan · Bukan Penerimaan Sekolah')
            ->assertSee('Madrasah Ibtidaiyah')
            ->assertSee('Raudhatul Athfal')
            ->assertSee('Madrasah Tsanawiyah')
            ->assertSee('Madrasah Aliyah')
            ->assertSee('Pondok Pesantren')
            ->assertSee('data-student-savings-recap-row', false)
            ->assertSee('href="'.route('finance.student-savings.index').'"', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(5, $xpath->query('//*[@data-student-savings-recap-row]'));
    }

    public function test_savings_reviewer_recap_is_limited_to_assigned_units(): void
    {
        Role::create([
            'key' => 'savings-reviewer',
            'name' => 'Peninjau Tabungan',
            'permissions' => ['payments.manage'],
            'is_active' => true,
        ]);
        $unit = EducationUnit::create(['code' => 'RA', 'name' => 'Raudhatul Athfal', 'is_active' => true]);
        $reviewer = User::factory()->create(['role' => 'savings-reviewer']);
        $reviewer->educationUnits()->attach($unit->id);

        $this->actingAs($reviewer)
            ->get(route('finance.student-savings.recap'))
            ->assertOk()
            ->assertSee('Raudhatul Athfal')
            ->assertSee('Rp 425.000')
            ->assertSee('1 santri')
            ->assertDontSee('Madrasah Ibtidaiyah')
            ->assertDontSee('Pondok Pesantren');
    }
}
