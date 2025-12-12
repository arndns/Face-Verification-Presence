<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        $today = date('Y-m-d');
        
        $totalEmployees = \App\Models\Employee::count();
        
        // Presence records store the clock-in time in `waktu_masuk`, so filter by that date
        $presenceToday = \App\Models\Presence::whereDate('waktu_masuk', $today)->count();
        
        $permitsToday = \App\Models\Permit::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        return view('Admin.index', compact('totalEmployees', 'presenceToday', 'permitsToday'));
    }

    public function viewdata(Request $request)
    {
        $search = $request->input('search');

        $employee = Employee::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(5)
            ->withQueryString();

        return view('Admin.pegawai.data', compact('employee', 'search'));
    }

    


    public function create()
    {
        return view('Admin.pegawai.CRUD.create');
    }
    public function store(Request $request)
    {
        $validator = $request->validate([
            'nik' => 'required|string|max:20|unique:employees,nik|unique:users,username',
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'no_hp' => 'nullable|string|max:15',
            'jabatan' => 'required|string|max:100',
            'foto' => 'nullable|image|mimes:jpeg,jpg|max:2048',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $realnamefile = $file->getClientOriginalName();
            $path = $file->storeAs('upload/pegawai', $realnamefile, 'public');

            $validator['foto'] = $path;
        }
        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $validator['nik'],
                'role' => 'employee', // Set role default
                'password' => Hash::make($validator['password']),
            ]);
            Employee::create([
                'user_id' => $user->id,
                'nik' => $validator['nik'],
                'nama' => $validator['nama'],
                'email' => $validator['email'],
                'no_hp' => $validator['no_hp'],
                'jabatan' => $validator['jabatan'],
                'foto' => $validator['foto'] ?? null,
            ]);
            DB::commit();
            return redirect()->route('admin.data')
                ->with('success', 'Data Pengguna Berhasil Disimpan!');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Gagal membuat data pegawai: ' . $e->getMessage());
            if (\Illuminate\Support\Str::contains($e->getMessage(), 'Duplicate entry')) {
                return redirect()->back()->with('error', 'NIK sudah terdaftar sebagai username.')->withInput();
            }

            return redirect()->back()->with('error', 'Terjadi kesalahan. Data gagal disimpan.')->withInput();
        }
    }

    public function editdata(Employee $employee)
    {

        return view('Admin.pegawai.CRUD.update', [
            'employee' => $employee
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validator = $request->validate([
            'nik' => 'required|string|max:20|unique:employees,nik,' . $employee->id . '|unique:users,username,' . $employee->user_id,
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'no_hp' => 'nullable|string|max:15',
            'jabatan' => 'required|string|max:100',
            'foto' => 'nullable|image|mimes:jpeg,jpg|max:2048',
            'password' => ['nullable', 'confirmed', Password::min(8)], 
        ]);

        $oldFoto = $employee->foto;
        $newFotoPath = null;

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $realnamefile = $file->getClientOriginalName();
            $newFotoPath = $file->storeAs('upload/pegawai', $realnamefile, 'public');
            $validator['foto'] = $newFotoPath;
        }
        DB::beginTransaction();
        try {
            $user = $employee->user;
            $userData = [
                'username' => $validator['nik'],
            ];
            if (!empty($validator['password'])) {
                $userData['password'] = Hash::make($validator['password']);
            }
            $user->update($userData);
            $employeeData = [
                'nik' => $validator['nik'],
                'nama' => $validator['nama'],
                'email' => $validator['email'],
                'no_hp' => $validator['no_hp'],
                'jabatan' => $validator['jabatan'],
            ];
            if ($newFotoPath) {
                $employeeData['foto'] = $newFotoPath;
            }
            $employee->update($employeeData);
            DB::commit();
            if ($newFotoPath && $oldFoto) {
                if (Storage::disk('public')->exists($oldFoto)) {
                    Storage::disk('public')->delete($oldFoto);
                }
            }
            return redirect()->route('admin.data')
                ->with('success', 'Data Pegawai Berhasil Diperbarui!');
        } catch (\Exception $e) {
            DB::rollback();
            if ($newFotoPath) {
                if (Storage::disk('public')->exists($newFotoPath)) {
                    Storage::disk('public')->delete($newFotoPath);
                }
            }
            Log::error('Gagal mengupdate data pegawai: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan. Data gagal diperbarui.')->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        $user = $employee->user;
        $fotoPath = $employee->foto;

        DB::beginTransaction();
        try {
            $employee->delete();
            if ($user) {
                $user->delete();
            }
            DB::commit();
            if ($fotoPath) {
                if (Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }
            }
            return redirect()->route('admin.data')
                ->with('success', 'Data Pegawai Berhasil Dihapus!');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Gagal menghapus data pegawai: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan. Data gagal dihapus.');
        }
    }

    public function presenceHistory(Request $request)
    {
        // 1. Ambil Data Presensi (Hadir)
        $presenceQuery = DB::table('presences')
            ->join('employees', 'presences.employee_id', '=', 'employees.id')
            ->select(
                'presences.id',
                'presences.employee_id',
                'presences.waktu_masuk',
                'presences.waktu_pulang',
                'presences.status',
                'employees.nik',
                'employees.nama',
                'employees.jabatan',
                DB::raw("'presence' as type"),
                DB::raw("NULL as leave_type")
            );

        // 2. Ambil Data Izin (Approved)
        $permitQuery = \App\Models\Permit::with('employee')
            ->where('status', 'approved');

        // --- Filter Search (Nama/NIK) ---
        if ($request->filled('search')) {
            $search = $request->search;
            $presenceQuery->where(function ($q) use ($search) {
                $q->where('employees.nama', 'LIKE', "%{$search}%")
                  ->orWhere('employees.nik', 'LIKE', "%{$search}%");
            });

            $permitQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('nik', 'LIKE', "%{$search}%");
            });
        }

        // --- Filter Date Range ---
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        if ($dateFrom) {
            $presenceQuery->whereDate('presences.waktu_masuk', '>=', $dateFrom);
            $permitQuery->whereDate('end_date', '>=', $dateFrom); // Ambil permit yang berakhir setelah/pada dateFrom
        }
        if ($dateTo) {
            $presenceQuery->whereDate('presences.waktu_masuk', '<=', $dateTo);
            $permitQuery->whereDate('start_date', '<=', $dateTo); // Ambil permit yang mulai sebelum/pada dateTo
        }

        // Ambil data raw
        $rawPresences = $presenceQuery->orderBy('presences.waktu_masuk', 'desc')->get();
        $rawPermits = $permitQuery->orderBy('start_date', 'desc')->get();

        // 3. Gabungkan & Expand Permit menjadi per-hari
        $mergedData = collect();

        // Masukkan data presensi
        foreach ($rawPresences as $p) {
            $mergedData->push((object)[
                'date' => Carbon::parse($p->waktu_masuk)->format('Y-m-d'),
                'datetime' => Carbon::parse($p->waktu_masuk), // untuk sorting
                'nik' => $p->nik,
                'nama' => $p->nama,
                'jabatan' => $p->jabatan,
                'waktu_masuk' => $p->waktu_masuk,
                'waktu_pulang' => $p->waktu_pulang,
                'status' => $p->status,
                'type' => 'presence',
                'leave_type' => null
            ]);
        }

        // Masukkan data permit (expand date range)
        foreach ($rawPermits as $permit) {
            if (!$permit->employee) continue;

            $start = Carbon::parse($permit->start_date);
            $end = Carbon::parse($permit->end_date);
            
            // Filter period sesuai request date range
            if ($dateFrom && $end->lt(Carbon::parse($dateFrom))) continue;
            if ($dateTo && $start->gt(Carbon::parse($dateTo))) continue;

            // Adjust start/end loop agar tidak keluar dari filter
            $loopStart = ($dateFrom && $start->lt(Carbon::parse($dateFrom))) ? Carbon::parse($dateFrom) : $start;
            $loopEnd = ($dateTo && $end->gt(Carbon::parse($dateTo))) ? Carbon::parse($dateTo) : $end;

            $period = CarbonPeriod::create($loopStart, $loopEnd);

            foreach ($period as $date) {
                // Cek apakah di tanggal ini user sudah ada presensi? (Opsional: prioritize presence over permit display, or show both)
                // Disini kita tampilkan saja sebagai baris terpisah atau bisa di-deduplicate jika mau.
                // Untuk simpelnya, kita masukkan saja, nanti user lihat ada double (izin & masuk) jika kejadian.
                
                $mergedData->push((object)[
                    'date' => $date->format('Y-m-d'),
                    'datetime' => $date->setTime(0,0,0), // set time 00:00
                    'nik' => $permit->employee->nik,
                    'nama' => $permit->employee->nama,
                    'jabatan' => $permit->employee->jabatan,
                    'waktu_masuk' => null,
                    'waktu_pulang' => null,
                    'status' => 'Izin (' . $this->formatLeaveTypeLabel($permit->leave_type) . ')',
                    'type' => 'permit',
                    'leave_type' => $permit->leave_type
                ]);
            }
        }

        // 4. Sorting Descending by Date
        $sortedData = $mergedData->sortByDesc('datetime')->values();

        // 5. Manual Pagination
        $perPage = 15;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $currentItems = $sortedData->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        $presences = new LengthAwarePaginator(
            $currentItems, 
            $sortedData->count(), 
            $perPage, 
            $currentPage, 
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('Admin.presence.history', compact('presences'));
    }

    protected function formatLeaveTypeLabel($type) {
        $labels = [
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'cuti_tahunan' => 'Cuti Tahunan'
        ];
        return $labels[$type] ?? ucfirst($type);
    }

    public function permitIndex(Request $request)
    {
        $query = \App\Models\Permit::with('employee');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by employee name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                  ->orWhere('nik', 'LIKE', "%{$search}%");
            });
        }

        $permits = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('Admin.permit.index', compact('permits'));
    }

    public function approvePermit(Request $request, \App\Models\Permit $permit)
    {
        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:500'
        ]);

        $permit->update([
            'status' => 'approved',
            'admin_note' => $validated['admin_note'] ?? null
        ]);

        return redirect()->route('admin.permit.index')->with('success', 'Pengajuan cuti berhasil disetujui!');
    }

    public function rejectPermit(Request $request, \App\Models\Permit $permit)
    {
        $validated = $request->validate([
            'admin_note' => 'required|string|max:500'
        ]);

        $permit->update([
            'status' => 'rejected',
            'admin_note' => $validated['admin_note']
        ]);

        return redirect()->route('admin.permit.index')->with('success', 'Pengajuan cuti telah ditolak.');
    }

    public function updatePermit(Request $request, \App\Models\Permit $permit)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'admin_note' => 'nullable|string|max:500'
        ]);

        $permit->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note']
        ]);

        return redirect()->route('admin.permit.index')->with('success', 'Status pengajuan cuti berhasil diperbarui.');
    }
}
