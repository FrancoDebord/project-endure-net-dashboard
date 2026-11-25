<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbSelectionPlan extends Model
{
    protected $fillable = ['timepoint', 'arm', 'target_nets', 'spread_clusters', 'created_by', 'locked_at', 'locked_by'];

    protected $casts = [
        'target_nets'     => 'integer',
        'spread_clusters' => 'boolean',
        'locked_at'       => 'datetime',
    ];

    public function households(): HasMany
    {
        return $this->hasMany(CbSelectedHousehold::class, 'plan_id');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
