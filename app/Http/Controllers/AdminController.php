<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {

        return view('admin.index');
    }

    public function getdata()
    {
        $users = User::where('role', 'employee')->latest()->paginate(5);
        return view('admin.pegawai.data', compact('users'));
    }

    public function create()
    {
        return view('admin.pegawai.CRUD.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik'       => 'required|unique:users,nik',
            'nama'      => 'required|string|max:255',
            'jabatan'   => 'required|string|max:255',
            'email'     => 'required|string|max:255|email|unique:users,email',
            'no_tilpun' => 'required|string|max:15',
            'password'  => 'required|string|min:8',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $validatedData = $validator->validated();

        $user = User::create([
            'nik'       => $validatedData['nik'],
            'nama'      => $validatedData['nama'],
            'jabatan'   => $validatedData['jabatan'],
            'email'     => $validatedData['email'],
            'no_tilpun' => $validatedData['no_tilpun'],
            'password'  => Hash::make($validatedData['password']),
            'role'      => 'employee'
        ]);
        return redirect()->route('admin.data')
            ->with('success', 'Data Pengguna Berhasil Disimpan!');
    }

    public function editdata($id)
    {
        $user = User::findOrFail($id);
        return view('admin.pegawai.CRUD.update', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'nik'       => 'string|max:255',
            'nama'      => 'string|max:255',
            'email'     => 'email|max:255',
            'jabatan'   => 'string|max:255',
            'no_tilpun' => 'string|max:15',
            'password'  => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }
        $user->update($validatedData);
        return redirect()->route('admin.data')
            ->with('success', 'Data Pegawai Berhasil Diperbarui!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.data')
            ->with('success', 'Data Pegawai Berhasil Dihapus!');
    }
}
