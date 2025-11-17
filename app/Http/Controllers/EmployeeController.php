<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Face_Embedding;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $user = User::with([
            'employee.location',
            'employee.faceEmbeddings',
        ])->find(Auth::id());

        $employee = $user?->employee;
        $todayPresence = null;
        $recentPresences = collect();

        if ($employee) {
            $todayPresence = $employee->presence()
                ->whereDate('waktu_masuk', today())
                ->latest('waktu_masuk')
                ->first();

            $recentPresences = $employee->presence()
                ->latest('waktu_masuk')
                ->limit(5)
                ->get();
        }

        $faceRegistered = (bool) optional($employee?->faceEmbeddings)->id;
        $location = $employee?->location;

        return view('Employee.index', [
            'user' => $user,
            'employee' => $employee,
            'todayPresence' => $todayPresence,
            'recentPresences' => $recentPresences,
            'faceRegistered' => $faceRegistered,
            'locationData' => $location,
        ]);
    }

    public function webcam()
    {
        $user = User::with('employee.location')->find(Auth::id());
        $employee = $user?->employee;
        $locationReady = (bool) $employee?->location;

        return view('Employee.camera', [
            'user' => $user,
            'locationReady' => $locationReady,
        ]);
    }

    public function faceMatcher()
    {
        $user = Auth::user();
        $employee = $user->employee;
        if (!$employee) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $embedding = Face_Embedding::where('employee_id', $employee->id)->first();;
        if (!$embedding) {
            return response()->json(['error' => 'Data wajah referensi tidak ditemukan'], 404);
        }
        return response()->json([
            'descriptor' => $embedding->descriptor
        ]);
    }

    public function presence(Request $request)
    {
        $request->validate([
            'snapshot' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();
            $user = Auth::user();
            $employee = $user->employee;
            $employee->loadMissing('location');

            if (!$employee) {
                return response()->json(['error' => 'Data karyawan tidak ditemukan'], 404);
            }

            if (!$employee->location) {
                return response()->json(['error' => 'Lokasi kantor belum ditetapkan oleh admin. Silakan hubungi administrator.'], 422);
            }

            $todayPresence = Presence::where('employee_id', $employee->id)
                ->whereDate('waktu_masuk', today())
                ->first();

            if ($todayPresence) {
                return response()->json(['error' => 'Anda sudah melakukan presensi masuk hari ini'], 400);
            }

            $timezone = $employee->location->timezone ?? config('app.timezone');
            $now = now($timezone);

            $photoPath = $this->storeSnapshot($request->snapshot, $employee->id);
            $presence = Presence::create([
                'employee_id' => $employee->id,
                'waktu_masuk' => $now,
                'foto_masuk' => $photoPath,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Presensi masuk berhasil',
                'timezone' => $timezone,
                'waktu_masuk' => optional($presence->waktu_masuk)->format('H:i:s'),
                'foto_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['error' => 'Terjadi kesalahan di server. Silakan coba lagi.'], 500);
        }
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
}
