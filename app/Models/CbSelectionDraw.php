<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbSelectionDraw extends Model
{
    protected $fillable = [
        'plan_id', 'timepoint', 'arm', 'batch', 'seed', 'need_nets', 'reserve',
        'spread_clusters', 'pool', 'picked', 'created_by', 'reset_at', 'reset_by',
    ];

    protected $casts = [
        'batch'           => 'integer',
        'seed'            => 'integer',
        'need_nets'       => 'integer',
        'reserve'         => 'integer',
        'spread_clusters' => 'boolean',
        'pool'            => 'array',
        'picked'          => 'array',
        'reset_at'        => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resetter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reset_by');
    }
}
