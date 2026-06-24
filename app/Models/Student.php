<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'identity_student_id', 'nis', 'nisn', 'name', 'birth_place', 'birth_date', 'gender',
        'father_name', 'mother_name', 'father_whatsapp', 'mother_whatsapp',
        'province', 'city', 'district', 'village', 'address',
        'school_class_id', 'academic_year_id', 'entry_date', 'billing_start_date', 'exit_date', 'inactive_reason',
        'guardian_name', 'whatsapp', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'entry_date' => 'date',
            'billing_start_date' => 'date',
            'exit_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function identityStudent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'identity_student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function feeDiscounts(): HasMany
    {
        return $this->hasMany(FeeDiscount::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
