<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPaymentAllocation extends Model
{
    public const TYPE_SPP = 'spp';

    public const TYPE_OTHER = 'other';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_GUARDIAN_TRANSFER = 'guardian_transfer';

    public const TYPE_ONLINE_PAYMENT = 'online_payment';

    public const TYPES = [
        self::TYPE_SPP,
        self::TYPE_OTHER,
        self::TYPE_MANUAL,
        self::TYPE_GUARDIAN_TRANSFER,
        self::TYPE_ONLINE_PAYMENT,
    ];

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
