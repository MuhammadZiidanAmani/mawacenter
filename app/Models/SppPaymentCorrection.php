<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppPaymentCorrection extends Model
{
    protected $fillable = [
        'spp_payment_id', 'old_paid_amount', 'new_paid_amount', 'refund_amount', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'old_paid_amount' => 'integer',
            'new_paid_amount' => 'integer',
            'refund_amount' => 'integer',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SppPayment::class, 'spp_payment_id');
    }
}
