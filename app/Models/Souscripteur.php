<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Souscripteur extends Authenticatable
{
    protected $fillable = [
        'code',
        'nom',
        'prenom',
        'nin',
        'prop',
        'wil',
        'dr_id',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function rdvs(): HasMany
    {
        return $this->hasMany(Rdv::class);
    }

    public function wilaya()
    {
        return $this->belongsTo(Wilaya::class, 'wil', 'code');
    }

    public function dr(): BelongsTo
    {
        return $this->belongsTo(Dr::class);
    }
}
