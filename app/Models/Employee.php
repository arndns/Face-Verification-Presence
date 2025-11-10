<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $fillable = ['user_id','nik', 'nama', 'email', 'foto', 'no_hp', 'divisi', 'jabatan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift(): BelongsTo{
        return $this->belongsTo(Shift::class);

    }

    public function location(): BelongsTo{
        return $this->belongsTo(Location::class);

    }
    
    public function faceEmbeddings(): HasOne
    {
        return $this->hasOne(Face_Embedding::class);
    }

    public function presence(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    public function permits (): HasMany
    {
        return $this->hasMany(Permits::class);
    }

    
    



}
