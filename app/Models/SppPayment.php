<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SppPayment extends Model
{
    protected $fillable = [
        'student_id', 'transaction_at', 'payment_method', 'transfer_proof_path', 'status', 'operator_name', 'operator_user_id',
        'import_source', 'import_key',
        'original_amount', 'discount_amount', 'total_amount', 'paid_amount',
        'remaining_amount', 'payment_status',
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

    public function operatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SppPaymentItem::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(SppPaymentCorrection::class);
    }
}
