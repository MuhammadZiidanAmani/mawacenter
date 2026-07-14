<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianTransferRequest extends Model
{
    protected $fillable = [
        'user_id', 'student_id', 'bill_ids', 'amount', 'proof_path', 'status',
        'verified_by', 'verified_at', 'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'bill_ids' => 'array',
            'amount' => 'integer',
            'verified_at' => 'datetime',
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
