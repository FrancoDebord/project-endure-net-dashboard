<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AeSelectedHousehold extends Model
{
    protected $fillable = [
        'run_id',
        'household_id',
        'arm',
        'village',
        'cluster_id',
        'dag',
        'study_cohort',
        'numero_menage',
        'head_name',
        'head_phone',
        'contact_name',
        'contact_phone',
        'moment_visite',
        'adresse',
        'nb_moustiquaires_etude',
        'net_codes',
        'form_completed',
        'form_completed_at',
        'form_completed_by',
    ];

    protected $casts = [
        'net_codes'          => 'array',
        'form_completed'     => 'boolean',
        'form_completed_at'  => 'datetime',
        'nb_moustiquaires_etude' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AeSelectionRun::class, 'run_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'form_completed_by');
    }

    public function armLabel(): string
    {
        return match ($this->arm) {
            'PD' => 'PERMANET DUAL',
            'G2' => 'INTERCEPTOR G2',
            default => $this->arm ?? '—',
        };
    }

    public function netCodesFormatted(): string
    {
        return $this->net_codes ? implode(', ', $this->net_codes) : '—';
    }
}
