<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationUnit;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Petugas')
            ->assertSee('Bendahara')
            ->assertSee('Wali Santri');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'rahasia123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/logout')->assertRedirect('/login');
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

    public function test_guardian_can_login_with_unit_and_nis_without_password(): void
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
        $guardian = User::factory()->create(['role' => 'orang_tua']);
        $guardian->guardianStudents()->attach($student->id);

        $this->post('/login', [
            'login_type' => 'wali',
            'guardian_unit_id' => $unit->id,
            'username' => '260001',
        ])->assertRedirect('/keuangan/tagihan');

        $this->assertAuthenticatedAs($guardian);
    }

    public function test_guardian_login_auto_creates_linked_account_from_valid_unit_and_nis(): void
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
        ])->assertRedirect('/keuangan/tagihan');

        $this->assertAuthenticated();
        $this->assertAuthenticatedAs(User::where('username', 'wali-paud-240289')->first());
    }
}
