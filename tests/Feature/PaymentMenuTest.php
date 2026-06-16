<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\FeeType;
use App\Models\OtherPayment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_menu_uses_requested_order(): void
    {
        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Daftar Ulang', 'SPP', 'Laundry', 'Lain-lain']);
    }

    public function test_registration_and_laundry_payment_sections_are_accessible(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang')
            ->assertOk()
            ->assertSee('Daftar Pembayaran Daftar Ulang');

        $this->actingAs($user)->get('/keuangan/pembayaran/lain-lain?category=laundry')
            ->assertOk()
            ->assertSee('Daftar Pembayaran Laundry');
    }

    public function test_payment_section_rejects_fee_type_from_another_group(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => '1001',
            'name' => 'Siswa',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $laundry = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-7A',
            'name' => 'Laundry 7A',
            'amount' => 150000,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$laundry->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fee_type_id');
    }

    public function test_laundry_payment_uses_monthly_flow_like_spp(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => '2001',
            'name' => 'Siswa Laundry',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $laundry = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'laundry',
            'code' => 'LAUNDRY-7A',
            'name' => 'Laundry 7A',
            'amount' => 150000,
            'period' => 'Bulanan',
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create());

        $this->get('/keuangan/pembayaran/lain-lain/create?category=laundry')
            ->assertOk()
            ->assertSee('data-laundry-form', false)
            ->assertSee('name="months[]"', false)
            ->assertSee('Biaya Laundry / Bulan');

        $this->getJson('/keuangan/pembayaran/lain-lain/months?category=laundry&student_id='.$student->id.'&fee_type_id='.$laundry->id.'&year=2026')
            ->assertOk()
            ->assertJsonPath('first_payable_month', 1)
            ->assertJsonPath('months.0.payment_status', 'Belum Dibayar');

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=laundry&student_id='.$student->id.'&fee_type_id='.$laundry->id.'&year=2026&months[]=1&months[]=2')
            ->assertOk()
            ->assertJson([
                'original_amount' => 300000,
                'remaining_amount' => 300000,
            ]);

        $this->post('/keuangan/pembayaran/lain-lain?category=laundry', [
            'transaction_date' => '16/06/2026',
            'transaction_time' => '10.00',
            'student_id' => $student->id,
            'fee_type_id' => $laundry->id,
            'year' => 2026,
            'months' => [1, 2],
            'payment_method' => 'Cash',
            'status' => 'Diterima',
            'paid_amount' => 200000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=laundry');

        $payment = OtherPayment::firstOrFail();
        $this->assertDatabaseHas('other_payment_items', [
            'other_payment_id' => $payment->id,
            'month' => 1,
            'paid_amount' => 150000,
            'payment_status' => 'Lunas',
        ]);
        $this->assertDatabaseHas('other_payment_items', [
            'other_payment_id' => $payment->id,
            'month' => 2,
            'paid_amount' => 50000,
            'remaining_amount' => 100000,
        ]);

        $this->get('/keuangan/pembayaran/lain-lain?category=laundry')
            ->assertOk()
            ->assertSee('Januari 2026, Februari 2026')
            ->assertSee('data-other-edit-url="'.route('finance.other.show', $payment).'"', false)
            ->assertSee('data-other-delete-url="'.route('finance.other.destroy', $payment).'"', false);
    }

    public function test_registration_payment_pending_does_not_reduce_the_remaining_charge(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PONPES', 'name' => 'Pondok Pesantren', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 'Kelas 7']);
        $student = Student::create([
            'nis' => '1002',
            'name' => 'Siswa Daftar Ulang',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DAFTAR-ULANG-7A',
            'name' => 'Daftar Ulang 7A',
            'amount' => 1000000,
            'period' => 'Sekali Bayar',
            'is_active' => true,
        ]);
        $this->actingAs(User::factory()->create());

        $this->get('/keuangan/pembayaran/lain-lain/create?category=daftar-ulang')
            ->assertOk()
            ->assertSee('data-payment-category="daftar-ulang"', false)
            ->assertSee('>Daftar Ulang 7A</option>', false)
            ->assertDontSee('Daftar Ulang 7A · PONPES', false);

        $studentSearch = 'PONPES - 1002 - Siswa Daftar Ulang';
        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_search='.urlencode($studentSearch).'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'original_amount' => 1000000,
                'remaining_amount' => 1000000,
            ]);

        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-06-14',
            'transaction_time' => '09:00:00',
            'student_search' => $studentSearch,
            'fee_type_id' => $feeType->id,
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'paid_amount' => 400000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');

        $pending = OtherPayment::firstOrFail();
        $this->assertSame('Pending', $pending->payment_status);
        $this->assertSame(1000000, $pending->remaining_amount);

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'paid_amount' => 0,
                'remaining_amount' => 1000000,
            ]);
        $this->get('/laporan?start_date=2026-06-14&end_date=2026-06-14&type=other')
            ->assertOk()
            ->assertDontSee('Siswa Daftar Ulang');

        $this->post('/keuangan/pembayaran/lain-lain?category=daftar-ulang', [
            'transaction_date' => '2026-06-14',
            'transaction_time' => '10:00:00',
            'student_id' => $student->id,
            'fee_type_id' => $feeType->id,
            'payment_method' => 'Transfer',
            'status' => 'Diterima',
            'paid_amount' => 600000,
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');

        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'paid_amount' => 600000,
                'remaining_amount' => 400000,
            ]);

        $payment = OtherPayment::latest('id')->firstOrFail();
        $this->get('/keuangan/pembayaran/lain-lain?category=daftar-ulang')
            ->assertOk()
            ->assertSee(route('finance.other.receipt', $payment), false)
            ->assertSee('title="Cetak Struk"', false)
            ->assertSee('<span class="education-code">PONPES</span>', false)
            ->assertSee('registration-payment-table', false)
            ->assertSee('Unit Pendidikan')
            ->assertSee('class="registration-payment-detail"', false)
            ->assertSee('Kategori Pembayaran')
            ->assertDontSee('<span>Kategori</span>', false)
            ->assertSee('Cara Bayar')
            ->assertSee('Petugas')
            ->assertSee('data-other-edit-url="'.route('finance.other.show', $payment).'"', false)
            ->assertSee('data-other-delete-url="'.route('finance.other.destroy', $payment).'"', false);

        $this->get(route('finance.other.show', $payment))
            ->assertOk()
            ->assertJsonPath('student_name', 'Siswa Daftar Ulang')
            ->assertJsonPath('payment_method', 'Transfer');

        $this->put(route('finance.other.update', $payment), [
            'transaction_date' => '15/06/2026',
            'transaction_time' => '11.30',
            'payment_method' => 'Cash',
            'status' => 'Pending',
        ])->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');
        $this->assertDatabaseHas('other_payments', [
            'id' => $payment->id,
            'transaction_at' => '2026-06-15 11:30:00',
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'payment_status' => 'Pending',
        ]);
        $this->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertOk()
            ->assertJson([
                'paid_amount' => 0,
                'remaining_amount' => 1000000,
            ]);

        $this->get(route('finance.other.receipt', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('Kwitansi Pembayaran')
            ->assertSee('DU-20260615-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT))
            ->assertSee('@page { size: A4 portrait; margin: 0; }', false)
            ->assertSee('Tahun Pelajaran')
            ->assertSee('Potongan (Rp)')
            ->assertSee("window.addEventListener('load', () => window.print())", false);

        $this->delete(route('finance.other.destroy', $payment))
            ->assertRedirect('/keuangan/pembayaran/lain-lain?category=daftar-ulang');
        $this->assertDatabaseMissing('other_payments', ['id' => $payment->id]);
    }

    public function test_registration_payment_rejects_category_for_another_class(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'Madrasah Tsanawiyah', 'is_active' => true]);
        $studentClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII A', 'level' => 'Kelas VII']);
        $otherClass = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'VII B', 'level' => 'Kelas VII']);
        $student = Student::create([
            'nis' => '1003',
            'name' => 'Siswa VII A',
            'gender' => 'P',
            'school_class_id' => $studentClass->id,
            'academic_year_id' => $year->id,
            'is_active' => true,
        ]);
        $feeType = FeeType::create([
            'education_unit_id' => $unit->id,
            'school_class_id' => $otherClass->id,
            'academic_year_id' => $year->id,
            'payment_group' => 'daftar-ulang',
            'code' => 'DAFTAR-ULANG-VII-B',
            'name' => 'Daftar Ulang VII B',
            'amount' => 900000,
            'period' => 'Sekali Bayar',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/keuangan/pembayaran/lain-lain/quote?category=daftar-ulang&student_id='.$student->id.'&fee_type_id='.$feeType->id)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fee_type_id');
    }
}
