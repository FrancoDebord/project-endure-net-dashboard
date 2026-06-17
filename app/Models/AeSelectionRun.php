<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AeSelectionRun extends Model
{
    protected $fillable = [
        'arm',
        'target_count',
        'selected_count',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'target_count'   => 'integer',
        'selected_count' => 'integer',
    ];

    public function households(): HasMany
    {
        return $this->hasMany(AeSelectedHousehold::class, 'run_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function armLabel(): string
    {
        return match ($this->arm) {
            'PD' => 'PERMANET DUAL',
            'G2' => 'INTERCEPTOR G2',
            default => 'Les deux bras',
        };
    }
}
