<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillSyncRun extends Model
{
    protected $fillable = [
        'user_id',
        'academic_year_id',
        'year',
        'until_month',
        'status',
        'phase',
        'cursor_id',
        'filters',
        'student_ids',
        'fee_type_ids',
        'phase_data',
        'total_items',
        'processed_items',
        'created_items',
        'existing_items',
        'skipped_items',
        'refreshed_items',
        'failed_items',
        'percent',
        'message',
        'error_message',
        'started_at',
        'finished_at',
        'audited_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'student_ids' => 'array',
            'fee_type_ids' => 'array',
            'phase_data' => 'array',
            'total_items' => 'integer',
            'processed_items' => 'integer',
            'created_items' => 'integer',
            'existing_items' => 'integer',
            'skipped_items' => 'integer',
            'refreshed_items' => 'integer',
            'failed_items' => 'integer',
            'percent' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'audited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }
}
