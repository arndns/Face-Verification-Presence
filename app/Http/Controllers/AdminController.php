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

class AdminController extends Controller
{
    public function index()
    {

        return view('admin.index');
    }

    public function viewdata()
    {
        $employee = Employee::orderBy('id', 'asc')->paginate(5);
        return view('admin.pegawai.data', compact('employee'));
    }

    


    public function create()
    {
        return view('admin.pegawai.CRUD.create');
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

        return view('admin.pegawai.CRUD.update', [
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
}
