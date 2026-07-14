<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function roleLabel(): string
    {
        return Role::options(false)[$this->role] ?? 'Belum Diatur';
    }

    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'key');
    }

    public function educationUnits(): BelongsToMany
    {
        return $this->belongsToMany(EducationUnit::class)->withTimestamps();
    }

    public function guardianStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student')
            ->withPivot(['relationship', 'is_primary', 'verified_at'])
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPetugas(): bool
    {
        return $this->role === 'kasir';
    }

    public function isBendaharaUnit(): bool
    {
        return $this->role === 'bendahara';
    }

    public function isGuardian(): bool
    {
        return $this->role === 'orang_tua';
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->roleRecord()->first();

        if (! $role || ! $role->is_active) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $role->permissions ?? [];

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        $legacyPermissions = [
            'dashboard.view' => 'dashboard',
            'students.view' => 'students',
            'payments.cash.create' => 'payments',
            'payments.transfer.submit_guardian' => 'payments',
            'payments.verify_transfer' => 'payments',
            'payments.view_own' => 'payments',
            'payments.view_unit' => 'payments',
            'bills.view' => 'bills',
            'bills.view_unit' => 'bills',
            'bills.view_guardian' => 'bills',
            'reports.view' => 'reports',
            'reports.view_unit' => 'reports',
            'reports.export' => 'reports',
            'master.manage' => 'master',
            'users.manage' => 'master',
            'settings.view' => 'settings',
        ];

        return isset($legacyPermissions[$permission])
            && in_array($legacyPermissions[$permission], $permissions, true);
    }

    public function accessibleUnitIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        if ($this->isBendaharaUnit() || $this->isPetugas()) {
            $ids = $this->educationUnits()->pluck('education_units.id')->map(fn ($id) => (int) $id)->all();

            return $this->isPetugas() && $ids === [] ? null : $ids;
        }

        if ($this->isGuardian()) {
            $studentIds = $this->accessibleStudentIds();
            if ($studentIds === []) {
                return [];
            }

            return Student::whereIn('students.id', $studentIds)
                ->join('school_classes', 'school_classes.id', '=', 'students.school_class_id')
                ->pluck('school_classes.education_unit_id')
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return [];
    }

    public function accessibleStudentIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        if (! $this->isGuardian()) {
            return null;
        }

        $linkedStudents = $this->guardianStudents()
            ->get(['students.id', 'students.identity_student_id']);

        if ($linkedStudents->isEmpty()) {
            return [];
        }

        $identityIds = $linkedStudents
            ->map(fn (Student $student) => $student->identity_student_id ?: $student->id)
            ->unique()
            ->values();

        return Student::query()
            ->whereIn('id', $identityIds)
            ->orWhereIn('identity_student_id', $identityIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
