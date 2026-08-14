<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuditLogService
{
    public function recordOperation(
        string $action,
        array $metadata = [],
        array $beforeValues = [],
        array $afterValues = [],
        ?Request $request = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        iterable $studentIds = [],
    ): AuditLog {
        return $this->recordStudentOperation(
            $action,
            $studentIds,
            $metadata,
            $beforeValues,
            $afterValues,
            $request,
            $subjectType,
            $subjectId,
        );
    }

    public function recordStudentOperation(
        string $action,
        iterable $studentIds = [],
        array $metadata = [],
        array $beforeValues = [],
        array $afterValues = [],
        ?Request $request = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
    ): AuditLog {
        $request ??= request();
        $ids = $this->normalizeIds($studentIds);

        return AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'student_count' => $ids->count(),
            'student_ids' => $ids->values()->all(),
            'before_values' => $beforeValues ?: null,
            'after_values' => $afterValues ?: null,
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function normalizeIds(iterable $studentIds): Collection
    {
        return collect($studentIds)
            ->flatten()
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
