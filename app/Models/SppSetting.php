<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SppSetting extends Model
{
    protected $fillable = ['education_unit_id', 'amount', 'is_active'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'is_active' => 'boolean'];
    }

    public function educationUnit(): BelongsTo
    {
        return $this->belongsTo(EducationUnit::class);
    }
}
