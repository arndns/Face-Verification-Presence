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
    public function index(){
        
        return view('admin.index');
    }

    public function getdata(){
        $users = User::where('role', 'employee')->latest()->paginate(5);
        return view('admin.pegawai.data', compact('users'));
    }

    public function create(){
        return view('admin.pegawai.CRUD.create');
    }

    public function store(Request $request ){
       // 1. Validasi data yang masuk dari form
        $validator = Validator::make($request->all(), [
            'nik'       => 'required|unique:users,nik',
            'nama'      => 'required|string|max:255',
            'jabatan'   => 'required|string|max:255',
            'email'     => 'required|string|max:255|email|unique:users,email',
            'no_tilpun' => 'required|string|max:15',
            'password'  => 'required|string|min:8',
        ]);

        // 2. Jika validasi gagal
        if ($validator->fails()) {
            // Arahkan kembali ke halaman sebelumnya (form) dengan membawa:
            // - Pesan error dari validator ($validator)
            // - Input yang sudah diisi oleh pengguna (withInput)
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
        }

        // 3. Jika validasi berhasil, buat pengguna baru
        // Menggunakan $validator->validated() lebih aman karena hanya mengambil data yang sudah divalidasi.
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

        // 4. Arahkan ke halaman daftar pengguna dengan pesan sukses
        // Ganti 'users.index' dengan nama route yang sesuai untuk menampilkan daftar pengguna.
        return redirect()->route('admin.data')
                         ->with('success', 'Data Pengguna Berhasil Disimpan!');
    }

    public function editdata($id){
         $user = User::findOrFail($id);
         return view('admin.pegawai.CRUD.update', compact('user')); 
    }   

     public function update(Request $request, User $user)
    {
        // 1. Validasi HANYA untuk data yang dikirimkan.
        // Aturan 'sometimes' berarti: "jika field ini ada di request, maka terapkan aturan berikutnya".
        // Ini memungkinkan kita mengirim hanya beberapa field untuk diupdate.
        $validatedData = $request->validate([
            // 'sometimes' | 'required' berarti jika NIK dikirim, maka tidak boleh kosong.
            'nik'       => 'string|max:255', 
            'nama'      => 'string|max:255',
            'email'     => 'email|max:255', 
            'jabatan'   => 'string|max:255',
            'no_tilpun' => 'string|max:15',
            // 'nullable' berarti password boleh kosong atau tidak dikirim sama sekali.
            'password'  => 'nullable|string|min:8|confirmed', // 'confirmed' adalah praktik terbaik
        ]);

        // 2. Cek jika password baru diisi, lalu hash dan tambahkan ke data.
        // Gunakan $request->filled() karena lebih aman untuk mendeteksi input yang benar-benar diisi.
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            // Jika password tidak diisi, hapus dari array agar tidak menimpa password lama dengan nilai null.
            unset($validatedData['password']);
        }

        // 3. Update data user di database.
        // Laravel akan secara cerdas hanya mengupdate kolom yang ada di dalam array $validatedData.
        $user->update($validatedData);

        // 4. Redirect ke halaman daftar user dengan pesan sukses.
        return redirect()->route('admin.data')
                         ->with('success', 'Data Pegawai Berhasil Diperbarui!');
    }

     public function destroy(User $user)
    {
        // Jalankan perintah untuk menghapus record user dari database
        $user->delete();

        // Arahkan kembali (redirect) ke halaman daftar user
        // dengan membawa pesan sukses menggunakan session flash.
        return redirect()->route('admin.data')
                         ->with('success', 'Data Pegawai Berhasil Dihapus!');
    }

    
}
