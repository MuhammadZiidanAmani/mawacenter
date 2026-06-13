<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeDiscount extends Model
{
    protected $fillable = [
        'student_id', 'source_type', 'fee_type_id', 'discount_type', 'discount_value',
        'start_date', 'end_date', 'reason', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    public function discountAmount(int $originalAmount): int
    {
        $discount = $this->discount_type === 'percentage'
            ? (int) round($originalAmount * $this->discount_value / 100)
            : $this->discount_value;

        return min($originalAmount, $discount);
    }
}
