<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherPaymentItem extends Model
{
    protected $fillable = [
        'student_id', 'fee_type_id', 'year', 'month', 'original_amount',
        'discount_amount', 'total_amount', 'paid_amount', 'remaining_amount',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'original_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'paid_amount' => 'integer',
            'remaining_amount' => 'integer',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OtherPayment::class, 'other_payment_id');
    }
}
