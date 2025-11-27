<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Face_Embedding extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id', 'descriptor', 'orientation'];
    protected $casts = [
        'descriptor' => 'array',
    ];

    protected $attributes = [
        'orientation' => 'front',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
