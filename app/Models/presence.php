<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    protected $table = 'presence';

    protected $fillable = [
        'user_id',
        'jam_masuk',
        'jam_pulang',
        'tanggal',
        'status',
        'foto_masuk',
        'foto_pulang',
        'lokasi_masuk',
        'lokasi_keluar',
    ];

     protected $casts = [
        'tanggal' => 'date',
    ];

    public function user(){
        return $this->belongsTo(user::class);
    
    }
}
