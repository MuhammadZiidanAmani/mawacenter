<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppPaymentItem extends Model
{
    protected $fillable = [
        'spp_payment_id', 'student_id', 'year', 'month',
        'original_amount', 'discount_amount', 'total_amount',
        'paid_amount', 'remaining_amount', 'payment_status',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SppPayment::class, 'spp_payment_id');
    }
}
