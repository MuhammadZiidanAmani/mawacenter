<?php

namespace App\Models;

use App\Support\ClassLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeType extends Model
{
    protected $fillable = ['education_unit_id', 'school_class_id', 'class_level', 'academic_year_id', 'payment_group', 'code', 'name', 'amount', 'period', 'creates_bill', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'creates_bill' => 'boolean', 'is_active' => 'boolean'];
    }

    public function educationUnit(): BelongsTo
    {
        return $this->belongsTo(EducationUnit::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function matchesSchoolClass(?SchoolClass $schoolClass): bool
    {
        if (! $schoolClass || $this->education_unit_id !== $schoolClass->education_unit_id) {
            return false;
        }

        if ($this->school_class_id !== null) {
            return $this->school_class_id === $schoolClass->id;
        }

        return $this->class_level === null
            || $this->class_level === ClassLevel::key($schoolClass->level ?: $schoolClass->name);
    }

    public function scopePaymentGroup(Builder $query, string $group): Builder
    {
        if ($group === 'spp') {
            return $query->where('payment_group', 'spp');
        }

        if ($group === 'daftar-ulang') {
            return $query->where(fn (Builder $feeType) => $feeType
                ->where('payment_group', 'daftar-ulang')
                ->orWhere('code', 'DAFTAR-ULANG')
                ->orWhere('code', 'like', 'DAFTAR-ULANG-%'));
        }

        if ($group === 'laundry') {
            return $query->where(fn (Builder $feeType) => $feeType
                ->where('payment_group', 'laundry')
                ->orWhere('name', 'like', '%Laundry%'));
        }

        return $query->where(fn (Builder $feeType) => $feeType
            ->where('payment_group', 'lain-lain')
            ->orWhereNull('payment_group'))
            ->where('code', '!=', 'DAFTAR-ULANG')
            ->where('code', 'not like', 'DAFTAR-ULANG-%')
            ->where('name', 'not like', '%Laundry%');
    }
}
