<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presence extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id', 'waktu_masuk', 'location_id', 'foto_masuk', 'waktu_pulang', 'shift_id', 'foto_pulang'  ];
    protected $casts = [
        'waktu_masuk' => 'datetime',
        'waktu_pulang' => 'datetime',
    ];
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
