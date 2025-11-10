<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function index()
    {
        return view('Login.login');
    }

    public function store(Request $request)
    {

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ], [
            'username.required' => 'Username atau NIK wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $username = $request->username;
        $password = $request->password;

        $employee = Employee::where('nik', $username)->first();
        if ($employee) {
            $user = User::where('id', $employee->user_id)->where('role', 'employee')->first();
            if (!$user) {
                return back()
                    ->withErrors(['username' => 'Akun tidak ditemukan atau tidak aktif.'])
                    ->onlyInput('username')
                    ->with('error', 'Login gagal. Data pegawai tidak valid.');
            }

            if (!Hash::check($password, $user->password)) {
                return back()
                    ->withErrors(['password' => 'Password yang Anda masukkan salah.'])
                    ->onlyInput('username')
                    ->with('error', 'Password tidak sesuai. Silakan coba lagi.');
            }

            Auth::login($user, $request->filled('remember'));
            return redirect()->intended('/employee/dashboard')
                ->with('success', 'Selamat datang kembali');
        }

        $user = User::where('username', $username)
            ->where('role', 'admin')
            ->first();
        if (!$user) {
            return back()
                ->withErrors(['username' => 'Username  tidak ditemukan.'])
                ->onlyInput('username')
                ->with('error', 'Username yang Anda masukkan tidak terdaftar dalam sistem.');
        }
        if (!Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['password' => 'Password yang Anda masukkan salah.'])
                ->onlyInput('username')
                ->with('error', 'Password tidak sesuai. Silakan coba lagi.');
        }
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();


        return redirect()->intended('/admin/dashboard')
            ->with('success', 'Selamat datang kembali',);
    }

    public function logout(Request $request)
    {

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->with('success', 'Anda telah berhasil logout. Sampai jumpa' );
    }
}
