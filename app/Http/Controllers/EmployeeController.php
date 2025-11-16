<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Face_Embedding;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $user = User::find(Auth::id());
        return view('Employee.index', compact('user'));
    }

    public function webcam()
    {
        $user = User::find(Auth::id());

        return view('Employee.camera', compact('user'));
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
        try {
            DB::beginTransaction();
            $user = Auth::user();
            $employee = $user->employee;
            if (!$employee) {
                return response()->json(['error' => 'Data karyawan tidak ditemukan'], 404);
            }
            $todayPresence = Presence::where('employee_id', $employee->id)->whereDate('jam_masuk', today())->first();
            if ($todayPresence) {
                return response()->json(['error' => 'Anda sudah melakukan presensi masuk hari ini'], 400);
            }
            $presence = Presence::create([
                'employee_id' => $employee->id,
                'jam_masuk' => $request->jam_masuk,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Presensi masuk berhasil',
                'jam_masuk' => $presence->jam_masuk->format('H:i:s')
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['error' => 'Terjadi kesalahan di server. Silakan coba lagi.'], 500);
        }
    }
}
