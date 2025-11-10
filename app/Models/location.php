<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }
}
