<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Permit;
use App\Models\Presence;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            ->with(['faceEmbeddings'])
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

        if ($request->boolean('face_warning')) {
            $name = $request->input('employee_name');
            $message = 'Data wajah sudah ada.';
            if ($name) {
                $message = "Data wajah {$name} sudah ada.";
            }
            session()->flash('warning', $message);
        }

        return view('Admin.pegawai.data', compact('employee', 'search'));
    }

    


    public function create()
    {
        return view('Admin.pegawai.CRUD.create');
    }
    public function store(Request $request)
    {
        $validator = $request->validate(
            [
                'nik' => 'required|string|max:20|unique:employees,nik|unique:users,username',
                'nama' => 'required|string|max:255',
                'email' => 'required|email|unique:employees,email',
                'no_hp' => 'nullable|string|max:15',
                'jabatan' => 'required|string|max:100',
                'foto' => 'nullable|image|mimes:jpeg,jpg|max:2048',
                'password' => ['required', 'confirmed', Password::min(8)],
            ],
            [
                'foto.max' => 'Ukuran foto terlalu besar. Gunakan foto maksimal 2 MB.',
                'foto.mimes' => 'Format foto harus JPG atau JPEG.',
                'foto.image' => 'File yang diunggah harus berupa gambar.',
            ]
        );

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
        $validator = $request->validate(
            [
                'nik' => 'required|string|max:20|unique:employees,nik,' . $employee->id . '|unique:users,username,' . $employee->user_id,
                'nama' => 'required|string|max:255',
                'email' => 'required|email|unique:employees,email,' . $employee->id,
                'no_hp' => 'nullable|string|max:15',
                'jabatan' => 'required|string|max:100',
                'foto' => 'nullable|image|mimes:jpeg,jpg|max:2048',
                'password' => ['nullable', 'confirmed', Password::min(8)], 
            ],
            [
                'foto.max' => 'Ukuran foto terlalu besar. Gunakan foto maksimal 2 MB.',
                'foto.mimes' => 'Format foto harus JPG atau JPEG.',
                'foto.image' => 'File yang diunggah harus berupa gambar.',
            ]
        );

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
        $perPage = 5;
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

    public function presenceCamera()
    {
        $location = Location::orderBy('id')->first();
        $presenceLocationPayload = $this->formatLocationPayload($location);

        return view('Admin.presence.camera', [
            'presenceLocation' => $location,
            'presenceLocationPayload' => $presenceLocationPayload,
        ]);
    }

    public function presenceEmbeddings()
    {
        $employees = Employee::with(['faceEmbeddings', 'location'])
            ->whereHas('faceEmbeddings')
            ->get();

        $embeddings = [];

        foreach ($employees as $employee) {
            $locationPayload = $this->formatLocationPayload($employee->location);

            foreach ($employee->faceEmbeddings as $embedding) {
                $descriptor = $this->normalizeEmbeddingDescriptor($embedding->descriptor);
                if (count($descriptor) !== 128) {
                    continue;
                }

                $embeddings[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->nama,
                    'employee_nik' => $employee->nik,
                    'orientation' => $embedding->orientation ?? 'front',
                    'descriptor' => $descriptor,
                    'location' => $locationPayload,
                ];
            }
        }

        if (!$embeddings) {
            return response()->json([
                'error' => 'Data wajah belum tersedia. Pastikan wajah karyawan sudah direkam.',
            ], 404);
        }

        return response()->json([
            'embeddings' => $embeddings,
            'total_employees' => $employees->count(),
        ]);
    }

    public function presenceStore(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'snapshot' => ['nullable', 'string'],
            'coordinates.latitude' => ['required', 'numeric'],
            'coordinates.longitude' => ['required', 'numeric'],
            'coordinates.accuracy' => ['nullable', 'numeric'],
        ]);

        try {
            DB::beginTransaction();

            $employeeId = (int) $request->input('employee_id');
            $employee = Employee::with(['location', 'shift'])->find($employeeId);
            if (!$employee) {
                DB::rollBack();
                return response()->json(['error' => 'Data karyawan tidak ditemukan'], 404);
            }

            if (!$employee->faceEmbeddings()->exists()) {
                DB::rollBack();
                return response()->json(['error' => 'Data wajah karyawan belum terdaftar'], 422);
            }

            $shift = $this->resolveEmployeeShift($employee);
            if (!$shift) {
                DB::rollBack();
                return response()->json(['error' => 'Shift karyawan belum ditetapkan'], 422);
            }

            $employeeLocation = $this->resolveEmployeeLocation($employee);
            $coordinates = $request->input('coordinates', []);

            if (!$employeeLocation) {
                DB::rollBack();
                return response()->json(['error' => 'Lokasi presensi belum ditetapkan untuk karyawan ini'], 422);
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
                    'employee' => [
                        'id' => $employee->id,
                        'nama' => $employee->nama,
                        'nik' => $employee->nik,
                    ],
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
                'employee' => [
                    'id' => $employee->id,
                    'nama' => $employee->nama,
                    'nik' => $employee->nik,
                ],
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
        $employeeId = $request->query('employee_id');
        if (!$employeeId) {
            return response()->json(['error' => 'ID karyawan diperlukan'], 422);
        }

        $employee = Employee::with(['location', 'shift'])->find($employeeId);
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
                'employee' => [
                    'id' => $employee->id,
                    'nama' => $employee->nama,
                    'nik' => $employee->nik,
                ],
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
            'employee' => [
                'id' => $employee->id,
                'nama' => $employee->nama,
                'nik' => $employee->nik,
            ],
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

        $permits = $query->orderBy('created_at', 'desc')->paginate(5);

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

    public function destroyPermit(\App\Models\Permit $permit)
    {
        $permit->delete();
        return redirect()->route('admin.permit.index')->with('success', 'Pengajuan cuti berhasil dihapus.');
    }

    protected function formatLocationPayload(?Location $location): ?array
    {
        if (!$location) {
            return null;
        }

        return [
            'id' => $location->id,
            'kota' => $location->kota,
            'alamat' => $location->alamat,
            'latitude' => $location->latitude !== null ? (float) $location->latitude : null,
            'longitude' => $location->longitude !== null ? (float) $location->longitude : null,
            'radius' => $location->radius !== null ? (float) $location->radius : null,
        ];
    }

    protected function normalizeEmbeddingDescriptor($descriptor): array
    {
        if (is_string($descriptor)) {
            $decoded = json_decode($descriptor, true);
            $descriptor = is_array($decoded) ? $decoded : [];
        } elseif (is_object($descriptor)) {
            $descriptor = (array) $descriptor;
        }

        if (is_array($descriptor)) {
            return array_map('floatval', $descriptor);
        }

        return [];
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
        $earthRadius = 6371000;
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

    protected function findApprovedPermitForDate(Employee $employee, Carbon $date): ?Permit
    {
        return Permit::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderByDesc('start_date')
            ->first();
    }
}
