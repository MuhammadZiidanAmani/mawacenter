<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillManualPayment extends Model
{
    protected $fillable = ['bill_id', 'transaction_at', 'payment_method', 'paid_amount'];

    protected function casts(): array
    {
        return ['transaction_at' => 'datetime', 'paid_amount' => 'integer'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BillPaymentAllocation::class, 'payment_id')
            ->where('payment_type', BillPaymentAllocation::TYPE_MANUAL);
    }
}
