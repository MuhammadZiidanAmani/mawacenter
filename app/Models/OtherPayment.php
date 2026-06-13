<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherPayment extends Model
{
    protected $fillable = [
        'student_id', 'fee_type_id', 'transaction_at', 'payment_method', 'status',
        'original_amount', 'discount_amount', 'total_amount', 'paid_amount',
        'remaining_amount', 'payment_status', 'operator_name', 'import_source', 'import_key',
    ];

    protected function casts(): array
    {
        return [
            'transaction_at' => 'datetime',
            'original_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'paid_amount' => 'integer',
            'remaining_amount' => 'integer',
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
}
