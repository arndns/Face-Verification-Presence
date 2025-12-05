<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Face_Embedding;
use App\Models\Shift;
use App\Models\Presence;
use App\Models\Location;
use App\Models\Permit;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

class EmployeeController extends Controller
{
    protected $approvedPermitToday;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            $employee = $user?->employee;
            $this->approvedPermitToday = null;

            if ($employee) {
                $timezone = optional($employee->location)->timezone ?? config('app.timezone');
                $now = now($timezone);
                $this->approvedPermitToday = $this->findApprovedPermitForDate($employee, $now);
            }

            view()->share('approvedPermitToday', $this->approvedPermitToday);

            return $next($request);
        });
    }

    public function index(): View
    {
        $user = User::with([
            'employee.location',
            'employee.faceEmbeddings',
        ])->find(Auth::id());

        $employee = $user?->employee;
        $todayPresence = null;
        $recentPresences = collect();
        $location = null;

        if ($employee) {
            $location = $this->resolveEmployeeLocation($employee);
            $todayPresence = $employee->presence()
                ->whereDate('waktu_masuk', today())
                ->latest('waktu_masuk')
                ->first();

            $recentPresences = $employee->presence()
                ->latest('waktu_masuk')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    $item->is_permit = false;
                    return $item;
                });

            $recentPermits = Permit::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('end_date', '>=', now()->subDays(60)->toDateString())
                ->get();

            $history = $recentPresences;

            foreach ($recentPermits as $permit) {
                $period = CarbonPeriod::create($permit->start_date, $permit->end_date);
                foreach ($period as $date) {
                    if ($date->gt(now())) continue;

                    // Check if this date already exists in presence to avoid duplicates if desired
                    // For now we allow both, or we could filter. 
                    // Let's check if we have a presence for this date
                    $hasPresence = $recentPresences->contains(function ($p) use ($date) {
                        return $p->waktu_masuk && $p->waktu_masuk->isSameDay($date);
                    });

                    if (!$hasPresence) {
                        $obj = new \stdClass();
                        $obj->waktu_masuk = $date->copy()->setTime(0, 0, 0);
                        $obj->waktu_pulang = null;
                        $obj->is_permit = true;
                        $obj->permit_type = $this->formatLeaveTypeLabel($permit->leave_type);
                        $history->push($obj);
                    }
                }
            }

            $recentPresences = $history->sortByDesc(function ($item) {
                return $item->waktu_masuk ?? $item->waktu_pulang;
            })->values()->take(20);
        }

        $faceRegistered = $employee ? $employee->faceEmbeddings()->exists() : false;
        $location = $employee?->location;
        $shift = null;
        $shiftStart = null;
        $shouldShowPresenceReminder = false;
        $timezone = config('app.timezone');
        $now = null;

        if ($employee) {
            $timezone = optional($employee->location)->timezone ?? config('app.timezone');
            $now = now($timezone);
            $shift = $this->resolveEmployeeShift($employee);
            $approvedPermitToday = $this->findApprovedPermitForDate($employee, $now);

            if ($shift) {
                $shiftStart = $this->resolveShiftDateTime($shift->jam_masuk, $now, $timezone);
                if (!$todayPresence && $shiftStart && !$approvedPermitToday) {
                    $shouldShowPresenceReminder = $now->greaterThanOrEqualTo($shiftStart);
                }
            }
        }

        return view('Employee.index', [
            'user' => $user,
            'employee' => $employee,
            'todayPresence' => $todayPresence,
            'recentPresences' => $recentPresences,
            'faceRegistered' => $faceRegistered,
            'locationData' => $location,
            'presenceReminder' => [
                'should_show' => $shouldShowPresenceReminder,
                'shift_name' => $shift?->nama_shift,
                'shift_start' => $shiftStart?->format('H:i'),
                'current_time' => $now?->format('H:i'),
                'timezone' => $timezone,
            ],
            'approvedPermitToday' => $approvedPermitToday,
        ]);
    }

    public function camera()
    {
        $user = User::with(['employee.location'])->find(Auth::id());

        $employeeLocation = $user && $user->employee
            ? $this->resolveEmployeeLocation($user->employee)
            : null;

        $employeeLocationPayload = $employeeLocation
            ? [
                'id' => $employeeLocation->id,
                'kota' => $employeeLocation->kota,
                'alamat' => $employeeLocation->alamat,
                'latitude' => $employeeLocation->latitude !== null ? (float) $employeeLocation->latitude : null,
                'longitude' => $employeeLocation->longitude !== null ? (float) $employeeLocation->longitude : null,
                'radius' => $employeeLocation->radius !== null ? (float) $employeeLocation->radius : null,
            ]
            : null;

        return view('Employee.camera', [
            'user' => $user,
            'employeeLocation' => $employeeLocation,
            'employeeLocationInfo' => $employeeLocation,
            'employeeLocationPayload' => $employeeLocationPayload,
        ]);
    }

    public function faceMatcher()
    {
        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $embeddings = $this->collectEmployeeEmbeddings($employee);
        if ($embeddings->isEmpty()) {
            return response()->json(['error' => 'Data wajah referensi tidak ditemukan'], 404);
        }
        return response()->json([
            'embeddings' => $embeddings,
        ]);
    }

    public function presence(Request $request)
    {
        $request->validate([
            'snapshot' => ['nullable', 'string'],
            'coordinates.latitude' => ['required', 'numeric'],
            'coordinates.longitude' => ['required', 'numeric'],
            'coordinates.accuracy' => ['nullable', 'numeric'],
        ]);

        try {
            DB::beginTransaction();
            $user = Auth::user();
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['error' => 'Data karyawan tidak ditemukan'], 404);
            }
            $employee->loadMissing('location');
            $shift = $this->resolveEmployeeShift($employee);
            if (!$shift) {
                return response()->json(['error' => 'Shift karyawan belum ditetapkan'], 422);
            }

            $employeeLocation = $this->resolveEmployeeLocation($employee);
            $coordinates = $request->input('coordinates', []);

            if (!$employeeLocation) {
                DB::rollBack();
                return response()->json(['error' => 'Lokasi presensi belum ditetapkan untuk akun Anda'], 422);
            }

            $deviceLatitude = (float) data_get($coordinates, 'latitude');
            $deviceLongitude = (float) data_get($coordinates, 'longitude');
            $allowedRadius = max((float) $employeeLocation->radius, 0);
            $distanceMeters = $this->calculateDistanceInMeters(
                $deviceLatitude,
                $deviceLongitude,
                (float) $employeeLocation->latitude,
                (float) $employeeLocation->longitude
            );

            if ($allowedRadius > 0 && $distanceMeters > $allowedRadius) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Anda berada di luar radius lokasi kantor.',
                    'distance_meters' => round($distanceMeters, 2),
                    'allowed_radius' => $allowedRadius,
                ], 422);
            }

            $timezone = optional($employee->location)->timezone ?? config('app.timezone');
            $now = now($timezone);
            $todayPresence = Presence::where('employee_id', $employee->id)
                ->whereDate('waktu_masuk', $now->toDateString())
                ->latest('waktu_masuk')
                ->first();

            $shiftStart = $this->resolveShiftDateTime($shift?->jam_masuk, $now, $timezone);
            $shiftEnd = $this->resolveShiftDateTime($shift?->jam_pulang, $now, $timezone);
            if ($shiftStart && $shiftEnd && $shiftEnd->lessThanOrEqualTo($shiftStart)) {
                $shiftEnd->addDay();
            }

            if ($todayPresence) {
                // if ($todayPresence->waktu_pulang) {
                //     return response()->json(['error' => 'Anda sudah menyelesaikan presensi hari ini'], 400);
                // }

                if ($shiftEnd && $now->lessThan($shiftEnd)) {
                    return response()->json(['error' => 'Belum waktunya melakukan presensi pulang'], 400);
                }

                $photoPath = $this->storeSnapshot($request->snapshot, $employee->id);
                $todayPresence->update([
                    'waktu_pulang' => $now,
                    'foto_pulang' => $photoPath,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'action' => 'clock_out',
                    'message' => 'Presensi pulang berhasil',
                    'timezone' => $timezone,
                    'status_kehadiran' => $todayPresence->status,
                    'recorded_at' => $this->formatTimeForResponse($todayPresence->waktu_pulang, $timezone),
                    'waktu_pulang' => $this->formatTimeForResponse($todayPresence->waktu_pulang, $timezone),
                    'shift' => [
                        'nama_shift' => $shift?->nama_shift,
                        'jam_masuk' => optional($shiftStart)->format('H:i'),
                        'jam_pulang' => optional($shiftEnd)->format('H:i'),
                    ],
                    'foto_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
                ]);
            }

            $status = $this->determinePresenceStatus($now, $shiftStart);

            $photoPath = $this->storeSnapshot($request->snapshot, $employee->id);
            $presence = Presence::create([
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'location_id' => $employee->location_id,
                'waktu_masuk' => $now,
                'foto_masuk' => $photoPath,
                'status' => $status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'action' => 'clock_in',
                'message' => 'Presensi masuk berhasil',
                'timezone' => $timezone,
                'recorded_at' => $this->formatTimeForResponse($presence->waktu_masuk, $timezone),
                'waktu_masuk' => $this->formatTimeForResponse($presence->waktu_masuk, $timezone),
                'status_kehadiran' => $status,
                'shift' => [
                    'nama_shift' => $shift?->nama_shift,
                    'jam_masuk' => optional($shiftStart)->format('H:i'),
                    'jam_pulang' => optional($shiftEnd)->format('H:i'),
                ],
                'foto_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['error' => 'Terjadi kesalahan di server. Silakan coba lagi.'], 500);
        }
    }

    public function presenceStatus(Request $request)
    {
        $user = Auth::user();
        $employee = $user?->employee;
        if (!$employee) {
            return response()->json(['error' => 'Data karyawan tidak ditemukan'], 404);
        }

        $location = $this->resolveEmployeeLocation($employee);
        $timezone = optional($location)->timezone ?? config('app.timezone');
        $now = now($timezone);

        $approvedPermitToday = $this->findApprovedPermitForDate($employee, $now);
        $shift = $this->resolveEmployeeShift($employee);

        if ($approvedPermitToday) {
            return response()->json([
                'timezone' => $timezone,
                'current_time' => $now->format('H:i:s'),
                'shift' => [
                    'nama_shift' => $shift?->nama_shift,
                    'jam_masuk' => $shift?->jam_masuk,
                    'jam_pulang' => $shift?->jam_pulang,
                ],
                'presence' => [
                    'is_on_leave' => true,
                    'leave_type' => $approvedPermitToday->leave_type,
                    'leave_start' => optional($approvedPermitToday->start_date)?->toDateString(),
                    'leave_end' => optional($approvedPermitToday->end_date)?->toDateString(),
                    'has_checked_in' => false,
                    'has_checked_out' => false,
                    'can_check_out' => false,
                    'waktu_masuk' => null,
                    'waktu_pulang' => null,
                    'status' => 'Izin',
                ],
                'reminders' => [
                    'should_check_in' => false,
                    'should_check_out' => false,
                ],
            ]);
        }

        if (!$shift) {
            return response()->json(['error' => 'Shift karyawan belum ditetapkan'], 422);
        }

        $todayPresence = $employee->presence()
            ->whereDate('waktu_masuk', $now->toDateString())
            ->latest('waktu_masuk')
            ->first();

        $shiftStart = $this->resolveShiftDateTime($shift?->jam_masuk, $now, $timezone);
        $shiftEnd = $this->resolveShiftDateTime($shift?->jam_pulang, $now, $timezone);
        if ($shiftStart && $shiftEnd && $shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        $hasCheckedIn = (bool) $todayPresence;
        $hasCheckedOut = $hasCheckedIn && (bool) $todayPresence?->waktu_pulang;
        $canCheckOut = $hasCheckedIn && $shiftEnd && $now->greaterThanOrEqualTo($shiftEnd);

        $shouldRemindCheckIn = !$hasCheckedIn && $shiftStart && $now->greaterThanOrEqualTo($shiftStart);
        $shouldRemindCheckOut = !$hasCheckedOut && $shiftEnd && $now->greaterThanOrEqualTo($shiftEnd);

        return response()->json([
            'timezone' => $timezone,
            'current_time' => $now->format('H:i:s'),
            'shift' => [
                'nama_shift' => $shift?->nama_shift,
                'jam_masuk' => $shift?->jam_masuk,
                'jam_pulang' => $shift?->jam_pulang,
            ],
            'presence' => [
                'has_checked_in' => $hasCheckedIn,
                'has_checked_out' => $hasCheckedOut,
                'can_check_out' => $canCheckOut,
                'waktu_masuk' => $this->formatTimeForResponse($todayPresence?->waktu_masuk, $timezone),
                'waktu_pulang' => $this->formatTimeForResponse($todayPresence?->waktu_pulang, $timezone),
                'status' => $todayPresence?->status,
            ],
            'reminders' => [
                'should_check_in' => $shouldRemindCheckIn,
                'should_check_out' => $shouldRemindCheckOut,
            ],
        ]);
    }

    protected function storeSnapshot(?string $base64, int $employeeId): ?string
    {
        if (empty($base64)) {
            return null;
        }

        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $data = base64_decode(substr($base64, strpos($base64, ',') + 1));

        if ($data === false) {
            return null;
        }

        $filename = sprintf('presence/%s_%s.%s', $employeeId, Str::uuid(), $extension);
        Storage::disk('public')->put($filename, $data);

        return $filename;
    }

    protected function resolveShiftDateTime(?string $time, Carbon $reference, string $timezone): ?Carbon
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
            } catch (\Throwable $th) {
                continue;
            }
        }

        return null;
    }

    protected function determinePresenceStatus(Carbon $currentTime, ?Carbon $shiftStart): string
    {
        if (!$shiftStart) {
            return 'Tepat Waktu';
        }

        return $currentTime->greaterThan($shiftStart) ? 'Terlambat' : 'Tepat Waktu';
    }

    protected function formatTimeForResponse(?Carbon $value, string $timezone): ?string
    {
        if (!$value) {
            return null;
        }

        return $value->copy()->timezone($timezone)->format('H:i:s');
    }

    protected function resolveEmployeeShift(Employee $employee): ?Shift
    {
        $employee->loadMissing('shift');

        if ($employee->shift) {
            return $employee->shift;
        }

        $defaultShift = Shift::orderBy('id')->first();
        if (!$defaultShift) {
            return null;
        }

        $employee->shift()->associate($defaultShift);
        $employee->save();
        $employee->setRelation('shift', $defaultShift);

        return $defaultShift;
    }

    protected function resolveEmployeeLocation(Employee $employee): ?Location
    {
        $employee->loadMissing('location');

        if ($employee->location) {
            return $employee->location;
        }

        $defaultLocation = Location::orderBy('id')->first();
        if (!$defaultLocation) {
            return null;
        }

        $employee->location()->associate($defaultLocation);
        $employee->save();
        $employee->setRelation('location', $defaultLocation);

        return $defaultLocation;
    }

    protected function calculateDistanceInMeters(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        $earthRadius = 6371000; // meters
        $latFrom = deg2rad($latitude1);
        $lonFrom = deg2rad($longitude1);
        $latTo = deg2rad($latitude2);
        $lonTo = deg2rad($longitude2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function collectEmployeeEmbeddings(Employee $employee)
    {
        return $employee->faceEmbeddings()
            ->get()
            ->filter(function (Face_Embedding $embedding) {
                return !empty($embedding->descriptor);
            })
            ->map(function (Face_Embedding $embedding) {
                $descriptor = $embedding->descriptor;

                if (is_string($descriptor)) {
                    $decoded = json_decode($descriptor, true);
                    $descriptor = is_array($decoded) ? $decoded : [];
                }

                if (is_array($descriptor)) {
                    $descriptor = array_map('floatval', $descriptor);
                } else {
                    $descriptor = [];
                }

                return [
                    'orientation' => $embedding->orientation ?? 'front',
                    'descriptor' => $descriptor,
                ];
            })
            ->values();
    }

    protected function findApprovedPermitForDate(Employee $employee, Carbon $date): ?Permit
    {
        return Permit::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderByDesc('start_date')
            ->first();
    }

    protected function formatLeaveTypeLabel(?string $leaveType): string
    {
        return match ($leaveType) {
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'cuti_tahunan' => 'Cuti Tahunan',
            default => ucfirst((string) $leaveType),
        };
    }

    public function history_presence(Request $request){
        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->route('employee.index')->with('error', 'Data karyawan tidak ditemukan.');
        }

        $fromDate = $request->query('from');
        $toDate = $request->query('to');

        $query = Presence::where('employee_id', $employee->id);

        if ($fromDate) {
            $query->where(function ($q) use ($fromDate) {
                $q->whereDate('waktu_masuk', '>=', $fromDate)
                    ->orWhereDate('waktu_pulang', '>=', $fromDate);
            });
        }

        if ($toDate) {
            $query->where(function ($q) use ($toDate) {
                $q->whereDate('waktu_masuk', '<=', $toDate)
                    ->orWhereDate('waktu_pulang', '<=', $toDate);
            });
        }

        $rawHistories = $query
            ->orderByDesc('waktu_masuk')
            ->orderByDesc('waktu_pulang')
            ->limit(50)
            ->get();

        $histories = $rawHistories->map(function ($presence) {
            $date = $presence->waktu_masuk ?? $presence->waktu_pulang;
            $dateIso = $date ? $date->format('Y-m-d') : null;

            return [
                'date_iso' => $dateIso,
                'formatted_date' => $date ? $date->translatedFormat('l, d M Y') : '-',
                'masuk' => $presence->waktu_masuk ? $presence->waktu_masuk->format('H:i') : '-',
                'pulang' => $presence->waktu_pulang ? $presence->waktu_pulang->format('H:i') : '-',
                'status_label' => $presence->status ?? ($presence->waktu_pulang ? 'Selesai' : 'Berjalan'),
                'status_badge' => $presence->waktu_pulang ? 'success' : 'warning',
            ];
        });

        $existingDates = $histories
            ->pluck('date_iso')
            ->filter()
            ->unique()
            ->values();

        $approvedPermits = Permit::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->when($fromDate, fn($q) => $q->whereDate('end_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('start_date', '<=', $toDate))
            ->get();

        foreach ($approvedPermits as $permit) {
            $start = $permit->start_date ? Carbon::parse($permit->start_date) : null;
            $end = $permit->end_date ? Carbon::parse($permit->end_date) : null;

            if (!$start || !$end) {
                continue;
            }

            $period = CarbonPeriod::create($start, $end);

            foreach ($period as $day) {
                $dateIso = $day->format('Y-m-d');

                if ($fromDate && $dateIso < $fromDate) {
                    continue;
                }

                if ($toDate && $dateIso > $toDate) {
                    continue;
                }

                if ($existingDates->contains($dateIso)) {
                    continue;
                }

                $histories->push([
                    'date_iso' => $dateIso,
                    'formatted_date' => $day->translatedFormat('l, d M Y'),
                    'masuk' => '-',
                    'pulang' => '-',
                    'status_label' => 'Izin (' . $this->formatLeaveTypeLabel($permit->leave_type) . ')',
                    'status_badge' => 'info',
                ]);
            }
        }

        $histories = $histories
            ->sortByDesc('date_iso')
            ->values()
            ->take(50);

        return view('Employee.history_presence', [
            'employee' => $employee,
            'histories' => $histories,
            'filters' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
        ]);
    }

    public function createPermit()
    {
        return view('Employee.permit.create');
    }

    public function storePermit(Request $request)
    {
        $validated = $request->validate([
            // Pengajuan harus dilakukan minimal H-1 sebelum tanggal mulai
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type' => 'required|in:sakit,izin,cuti_tahunan',
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        \App\Models\Permit::create([
            'employee_id' => $employee->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'leave_type' => $validated['leave_type'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('employee.permit.history')->with('success', 'Pengajuan cuti berhasil dikirim!');
    }

    public function permitHistory()
    {
        $user = Auth::user();
        $employee = $user?->employee;

        if (!$employee) {
            return redirect()->route('employee.index')->with('error', 'Data karyawan tidak ditemukan.');
        }

        $permits = \App\Models\Permit::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('Employee.permit.history', compact('permits'));
    }

    public function profile()
    {
        $user = Auth::user();
        $employee = $user?->employee;
        
        return view('Employee.profile', compact('user', 'employee'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Password saat ini salah.');
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    public function getEmbedding()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $employee = $user->employee;
        
        if (!$employee) {
            return response()->json(['error' => 'Data karyawan tidak ditemukan'], 404);
        }

        $embeddings = $this->collectEmployeeEmbeddings($employee);
        
        if ($embeddings->isEmpty()) {
            return response()->json(['error' => 'Data embedding wajah Anda belum terekam. Silakan hubungi admin.'], 404);
        }

        return response()->json([
            'embeddings' => $embeddings,
            'descriptor' => $embeddings->first()['descriptor'] ?? null,
            'orientation' => $embeddings->first()['orientation'] ?? null,
        ]);
    }

}
