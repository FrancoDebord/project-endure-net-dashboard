<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbSelectedHousehold extends Model
{
    protected $fillable = [
        'plan_id', 'timepoint', 'arm', 'household_id', 'rank', 'batch', 'nets_planned',
        'released_at', 'release_reason', 'released_by', 'created_by',
    ];

    protected $casts = [
        'rank'         => 'integer',
        'batch'        => 'integer',
        'nets_planned' => 'integer',
        'released_at'  => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CbSelectionPlan::class, 'plan_id');
    }
}
