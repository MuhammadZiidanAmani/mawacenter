<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPaymentAllocation extends Model
{
    protected $fillable = ['bill_id', 'payment_type', 'payment_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
