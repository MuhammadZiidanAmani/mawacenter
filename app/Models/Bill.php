<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'source_type', 'fee_type_id', 'year', 'month',
        'generation_key', 'title', 'issue_date', 'due_date', 'original_amount', 'discount_amount',
        'total_amount', 'paid_amount', 'remaining_amount', 'status', 'unit_name', 'class_name', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date', 'due_date' => 'date', 'original_amount' => 'integer',
            'discount_amount' => 'integer', 'total_amount' => 'integer', 'paid_amount' => 'integer',
            'remaining_amount' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BillPaymentAllocation::class);
    }

    public function manualPayments(): HasMany
    {
        return $this->hasMany(BillManualPayment::class);
    }

    public function displayStatus(): string
    {
        if ($this->status === 'Dibatalkan') {
            return $this->status;
        }

        return $this->remaining_amount > 0 && $this->due_date?->isPast() ? 'Jatuh Tempo' : $this->status;
    }
}
