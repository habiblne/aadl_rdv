<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilaya extends Model
{
    protected $fillable = [
        'code',
        'nom',
        'dr_id',
    ];

    public function souscripteurs()
    {
        return $this->hasMany(Souscripteur::class, 'wil', 'code');
    }

    public function dr()
    {
        return $this->belongsTo(Dr::class);
    }
}
