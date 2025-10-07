<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'kota',
        'alamat',
        'latitude',
        'longitude',
        'radius',
    ];


    public function presences(): HasMany
    {
        return $this->hasMany(presence::class);
    }
}
