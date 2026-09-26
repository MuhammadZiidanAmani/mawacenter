<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')
            ->assertOk()
            ->assertSee("MA'WA", false)
            ->assertSee('Username')
            ->assertSee('Kata Sandi')
            ->assertSee('class="login-guardian-field"', false)
            ->assertSee('name="guardian_unit_id" data-guardian-unit disabled', false)
            ->assertDontSee('class="login-form wali-mode"', false)
            ->assertSee('Saya Wali Santri? Masuk di sini');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'rahasia123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/')
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false);

        $this->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post('/login', [
            'username' => 'petugas',
            'password' => 'keliru',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_email_cannot_be_used_to_login(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', [
            'username' => $user->email,
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_guardian_can_login_with_unit_nis_and_password(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MTs', 'name' => 'MTs Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '7A', 'level' => 7, 'is_active' => true]);
        $student = Student::create([
            'nis' => '260001',
            'name' => 'Siswa Wali',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'entry_date' => '2025-07-01',
            'is_active' => true,
        ]);
        $guardian = User::factory()->create(['role' => 'orang_tua', 'password' => 'rahasia-wali']);
        $guardian->guardianStudents()->attach($student->id);

        $this->post('/login', [
            'login_type' => 'wali',
            'guardian_unit_id' => $unit->id,
            'username' => '260001',
            'password' => 'rahasia-wali',
        ])->assertRedirect('/keuangan/tagihan');

        $this->assertAuthenticatedAs($guardian);
    }

    public function test_guardian_login_does_not_auto_create_an_account(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'PAUD', 'name' => 'PAUD Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => 'A1', 'level' => 1, 'is_active' => true]);
        Student::create([
            'nis' => '240289',
            'name' => 'Siswa PAUD',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'entry_date' => '2025-07-01',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'login_type' => 'wali',
            'guardian_unit_id' => $unit->id,
            'username' => '240289',
            'password' => 'rahasia-wali',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['username' => 'wali-paud-240289']);
    }

    public function test_legacy_guardian_account_must_be_reset_by_an_administrator(): void
    {
        $year = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $unit = EducationUnit::create(['code' => 'MI', 'name' => 'MI Mambaul Hikmah', 'is_active' => true]);
        $class = SchoolClass::create(['education_unit_id' => $unit->id, 'name' => '1A', 'level' => 1, 'is_active' => true]);
        $student = Student::create([
            'nis' => '240290',
            'name' => 'Siswa Akun Lama',
            'gender' => 'L',
            'school_class_id' => $class->id,
            'academic_year_id' => $year->id,
            'entry_date' => '2025-07-01',
            'is_active' => true,
        ]);
        $guardian = User::factory()->create([
            'role' => 'orang_tua',
            'password' => 'password-tidak-diketahui',
            'must_reset_password' => true,
        ]);
        $guardian->guardianStudents()->attach($student->id);

        $this->post('/login', [
            'login_type' => 'wali',
            'guardian_unit_id' => $unit->id,
            'username' => $student->nis,
            'password' => 'password-tidak-diketahui',
        ])->assertSessionHasErrors([
            'username' => 'Password akun wali perlu diatur ulang oleh administrator.',
        ]);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts_per_minute(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'username' => 'pengguna-rate-limit',
                'password' => 'salah',
            ])->assertSessionHasErrors('username');
        }

        $this->post('/login', [
            'username' => 'pengguna-rate-limit',
            'password' => 'salah',
        ])->assertTooManyRequests();
    }

    public function test_unmapped_authenticated_route_is_denied_by_default(): void
    {
        Route::middleware(['web', 'auth', 'role.access'])
            ->get('/_test/unmapped-permission', fn () => response('ok'))
            ->name('test.unmapped-permission');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/_test/unmapped-permission')
            ->assertForbidden();
    }
}
