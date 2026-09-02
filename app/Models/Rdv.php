<?php

namespace App\Models;

use App\Support\RdvHashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rdv extends Model
{
    public const STATUT_RDV_PRIS = 0;
    public const STATUT_RDV_ACCEPTE = 1;
    public const STATUT_RDV_VALIDE = 2;
    public const STATUT_RDV_COMPLETE = 3;

    protected $fillable = [
        'souscripteur_id',
        'dr_id',
        'date',
        'motif',
        'statut',
        'accepted_by_responsable_id',
        'accepted_at',
        'validated_by_agent_id',
        'validated_at',
        'completed_by_responsable_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'statut' => 'integer',
            'accepted_at' => 'datetime',
            'validated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function souscripteur(): BelongsTo
    {
        return $this->belongsTo(Souscripteur::class);
    }

    public function dr(): BelongsTo
    {
        return $this->belongsTo(Dr::class);
    }

    public function acceptedByResponsable(): BelongsTo
    {
        return $this->belongsTo(Responsable::class, 'accepted_by_responsable_id');
    }

    public function validatedByAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'validated_by_agent_id');
    }

    public function completedByResponsable(): BelongsTo
    {
        return $this->belongsTo(Responsable::class, 'completed_by_responsable_id');
    }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_RDV_PRIS => 'RDV pris',
            self::STATUT_RDV_ACCEPTE => 'RDV accepté',
            self::STATUT_RDV_VALIDE => 'RDV validé',
            self::STATUT_RDV_COMPLETE => 'RDV complété',
            default => '',
        };
    }

    public function getHashidAttribute(): string
    {
        return app(RdvHashids::class)->encode($this);
    }
}
