<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function index(){
        return view('Login.login');
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required'
        ], [
            'username.required' => 'Username harus diisi.',
            'password.required' => 'Password harus diisi.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $username = $request->input('username');
        $password = $request->input('password');
        
        $user = null;
        // Cek format input sekali dan simpan hasilnya
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);

        // 2. Cari Pengguna Berdasarkan Format Input
        if ($isEmail) {
            // Jika formatnya email, cari user admin/owner
            $user = User::where('email', $username)
                        ->whereIn('role', ['admin', 'owner'])
                        ->first();
        } else {
            // Jika bukan email, cari user employee berdasarkan NIK
            $user = User::where('nik', $username)
                        ->where('role', 'employee')
                        ->first();
        }

        // 3. Pengecekan Eksistensi Pengguna (Dengan Pesan Terpisah)
        if (!$user) {
            // Tentukan pesan error berdasarkan format input yang dicoba
            $errorMessage = $isEmail
                ? 'Email yang anda masukkan kurang tepat'
                : 'NIK yang anda masukkan kurang tepat';

            return back()
                ->withErrors(['username' => $errorMessage])
                ->withInput($request->except('password'));
        }

        // 4. Pengecekan Password (Jika pengguna ditemukan)
        if (!Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['password' => 'Password yang anda masukkan kurang tepat'])
                ->withInput($request->except('password'));
        }

        // 5. Jika Semua Berhasil: Login dan Redirect
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        return $this->redirectAfterLogin($user->role);
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    private function redirectAfterLogin(string $role): \Illuminate\Http\RedirectResponse
    {
         switch ($role) {
            case 'admin':
                return redirect()->route('admin.index');
            case 'employee':
                return redirect()->route('employee.index');
            default:
                return redirect('/');
        }
    }

    
}
