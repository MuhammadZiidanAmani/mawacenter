<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentEnrollment extends Model
{
    public const STATUS_PLANNED = 'planned';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_PLANNED => 'Direncanakan',
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'student_id',
        'education_unit_id',
        'academic_year_id',
        'school_class_id',
        'source_enrollment_id',
        'intake_status',
        'status',
        'entry_date',
        'exit_date',
        'activated_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'exit_date' => 'date',
            'activated_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function educationUnit(): BelongsTo
    {
        return $this->belongsTo(EducationUnit::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function sourceEnrollment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_enrollment_id');
    }

    public function nextEnrollments(): HasMany
    {
        return $this->hasMany(self::class, 'source_enrollment_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
