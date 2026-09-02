<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Agent extends Authenticatable
{
    protected $fillable = [
        'email',
        'password',
        'dr_id',
    ];

    protected $hidden = [
        'password',
    ];

    public function dr(): BelongsTo
    {
        return $this->belongsTo(Dr::class);
    }
}
