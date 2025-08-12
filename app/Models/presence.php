<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class presence extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tanggal',
        'location_id',
        'masuk',
        'masuk_latitude',
        'masuk_longitude',
        'pulang',
        'pulang_latitude',
        'pulang_longitude',
        'status',
    ];

    /**
     * Mendefinisikan bahwa sebuah Presence dimiliki oleh seorang User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(location::class);
    }
}
