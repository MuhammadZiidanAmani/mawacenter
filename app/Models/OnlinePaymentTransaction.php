<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePaymentTransaction extends Model
{
    public const STATUS_PROCESSING = 'Memproses';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_FAILED = 'Gagal';

    public const STATUS_ACCEPTED = 'Diterima';

    protected $fillable = [
        'user_id', 'student_id', 'merchant_order_id', 'bill_ids', 'payment_method',
        'payment_method_name', 'amount', 'service_fee', 'total_amount', 'allocated_amount',
        'status', 'result_code', 'reconciliation_status',
        'provider_reference', 'payment_url', 'va_number', 'qr_string', 'app_url',
        'expires_at', 'provider_response', 'callback_payload', 'failure_reason',
        'callback_received_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'bill_ids' => 'array',
            'amount' => 'integer',
            'service_fee' => 'integer',
            'total_amount' => 'integer',
            'allocated_amount' => 'integer',
            'expires_at' => 'datetime',
            'provider_response' => 'array',
            'callback_payload' => 'array',
            'callback_received_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
