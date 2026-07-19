<?php

namespace App\Http\Controllers;

use App\Models\EducationUnit;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'educationUnits' => EducationUnit::where('is_active', true)
                ->orderByRaw("CASE code WHEN 'PAUD' THEN 1 WHEN 'RA' THEN 2 WHEN 'MI' THEN 3 WHEN 'MTs' THEN 4 WHEN 'MA' THEN 5 WHEN 'PONPES' THEN 6 ELSE 7 END")
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login_type' => ['nullable', 'string', 'in:petugas,bendahara,wali'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required_unless:login_type,wali', 'nullable', 'string'],
            'guardian_unit_id' => ['required_if:login_type,wali', 'nullable', 'integer', 'exists:education_units,id'],
        ]);
        $validated['login_type'] ??= 'petugas';

        $username = Str::lower(trim($validated['username']));

        if ($validated['login_type'] === 'wali') {
            $this->attemptGuardianLogin($request, $username, (int) $validated['guardian_unit_id']);
        } else {
            if (! Auth::attempt(['username' => $username, 'password' => $validated['password']], $request->boolean('remember'))) {
                throw ValidationException::withMessages([
                    'username' => 'Username atau kata sandi yang Anda masukkan belum tepat.',
                ]);
            }
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function attemptGuardianLogin(Request $request, string $nis, int $unitId): void
    {
        $student = Student::query()
            ->where('nis', $nis)
            ->whereHas('schoolClass', fn ($query) => $query->where('education_unit_id', $unitId))
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'username' => 'NIS atau unit pendidikan wali santri belum sesuai.',
            ]);
        }

        $identityId = $student->identity_student_id ?: $student->id;
        $studentIds = Student::query()
            ->where('id', $identityId)
            ->orWhere('identity_student_id', $identityId)
            ->pluck('id');

        $user = User::query()
            ->where('role', 'orang_tua')
            ->whereHas('guardianStudents', fn ($query) => $query->whereIn('students.id', $studentIds))
            ->first();

        $user ??= $this->createGuardianUser($student, $studentIds->map(fn ($id) => (int) $id)->all());

        Auth::login($user, $request->boolean('remember'));
    }

    /**
     * @param  array<int>  $studentIds
     */
    private function createGuardianUser(Student $student, array $studentIds): User
    {
        Role::updateOrCreate(
            ['key' => 'orang_tua'],
            [
                'name' => Role::DEFAULTS['orang_tua'],
                'description' => 'Role bawaan sistem',
                'permissions' => Role::defaultPermissionsFor('orang_tua'),
                'is_active' => true,
            ],
        );

        $unitCode = Str::lower((string) $student->schoolClass?->educationUnit?->code);
        $nis = Str::lower((string) $student->nis);
        $username = Str::slug("wali {$unitCode} {$nis}");

        $user = User::firstOrCreate(
            ['username' => $username],
            [
                'name' => 'Wali '.$student->name,
                'email' => $username.'@wali.mawacenter.local',
                'role' => 'orang_tua',
                'password' => Str::random(32),
            ],
        );

        $user->guardianStudents()->syncWithoutDetaching(
            collect($studentIds)
                ->mapWithKeys(fn (int $studentId) => [$studentId => ['relationship' => 'wali', 'is_primary' => $studentId === $student->id]])
                ->all(),
        );

        return $user;
    }

    private function redirectPath(?User $user): string
    {
        return match (true) {
            $user?->isPetugas() => route('finance.payments.index'),
            $user?->isBendaharaUnit() => route('finance.bills.index'),
            $user?->isGuardian() => route('finance.bills.index'),
            default => route('dashboard'),
        };
    }
}
