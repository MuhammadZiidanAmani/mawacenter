<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeType extends Model
{
    protected $fillable = ['education_unit_id', 'school_class_id', 'academic_year_id', 'payment_group', 'code', 'name', 'amount', 'period', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'is_active' => 'boolean'];
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

    public function scopePaymentGroup(Builder $query, string $group): Builder
    {
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
