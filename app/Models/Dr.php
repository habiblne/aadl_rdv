<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Dr extends Model
{
    protected $fillable = [
        'nom',
    ];

    public function rdvs(): HasMany
    {
        return $this->hasMany(Rdv::class);
    }

    public function responsables(): HasMany
    {
        return $this->hasMany(Responsable::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    public function wilayas(): HasMany
    {
        return $this->hasMany(Wilaya::class);
    }
}
