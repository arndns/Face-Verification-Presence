<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Throwable;

class Presence extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'shift_id',
        'location_id',
        'waktu_masuk',
        'waktu_pulang',
        'foto_masuk',
        'foto_pulang',
        'status',
    ];
    protected $casts = [
        'waktu_masuk' => 'datetime',
        'waktu_pulang' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Presence $presence) {
            $presence->applyShiftStatus();
        });
    }

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

    protected function applyShiftStatus(): void
    {
        $employee = $this->resolveEmployeeContext();
        $shift = $this->shift ?? $employee?->shift;

        if (!$this->shift_id && $shift) {
            $this->shift_id = $shift->id;
        }

        if (!$this->location_id && $employee?->location_id) {
            $this->location_id = $employee->location_id;
        }

        if (!$shift || !$shift->jam_masuk || !$this->waktu_masuk) {
            return;
        }

        $timezone = optional($employee?->location)->timezone ?? config('app.timezone');
        $clockIn = $this->waktu_masuk instanceof Carbon
            ? $this->waktu_masuk->copy()->timezone($timezone)
            : Carbon::parse($this->waktu_masuk, $timezone);

        $shiftStart = $this->buildShiftDateTime($shift->jam_masuk, $clockIn, $timezone);

        if (!$shiftStart) {
            return;
        }

        $this->status = $clockIn->greaterThan($shiftStart) ? 'Terlambat' : 'Tepat Waktu';
    }

    protected function resolveEmployeeContext(): ?Employee
    {
        if ($this->relationLoaded('employee')) {
            return $this->employee;
        }

        if (!$this->employee_id) {
            return null;
        }

        return $this->employee()->with(['shift', 'location'])->first();
    }

    protected function buildShiftDateTime(?string $time, Carbon $reference, string $timezone): ?Carbon
    {
        if (!$time) {
            return null;
        }

        $normalized = str_replace('.', ':', $time);
        $dateString = $reference->toDateString();

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat(
                    'Y-m-d ' . $format,
                    sprintf('%s %s', $dateString, $normalized),
                    $timezone
                );
            } catch (Throwable $e) {
                continue;
            }
        }

        return null;
    }
}
