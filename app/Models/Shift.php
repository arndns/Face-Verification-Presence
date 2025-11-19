<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Throwable;

class Shift extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function employees(): HasMany{
        return $this->hasMany(Employee::class);
    }

    public function presences(): HasMany{
        return $this->hasMany(Presence::class);
    }

    protected function jamMasuk(): Attribute
    {
        return $this->timeAttribute();
    }

    protected function jamPulang(): Attribute
    {
        return $this->timeAttribute();
    }

    protected function timeAttribute(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->formatTime($value, ['H:i:s', 'H:i'], 'H:i'),
            set: fn($value) => $this->formatTime($value, ['H:i', 'H:i:s'], 'H:i:s'),
        );
    }

    protected function formatTime($value, array $inputFormats, string $outputFormat)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace('.', ':', $value);
        }

        foreach ($inputFormats as $format) {
            try {
                return Carbon::createFromFormat($format, $value, config('app.timezone'))
                    ->setTimezone(config('app.timezone'))
                    ->format($outputFormat);
            } catch (Throwable $th) {
                continue;
            }
        }

        return $value;
    }
}
