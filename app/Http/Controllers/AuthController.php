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

    public function store(Request $request){
        
        $username = Validator::make($request->all(),[
            'username' => 'required|string',
            'password' => 'required'
        ],[

            'username.required' => 'NIK harus diisi',
            'password.required' => 'Password harus diisi',
        ]);

        
        if ($username->fails()) {
            return back()
                ->withErrors($username)
                ->withInput($request->except('password'));
        }
        $username = $request->input('username');
        $password = $request->input('password');
        
        $user = null;

        //mengecek username termasuk email atau tidak 
         if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            // Jika format email, cari berdasarkan email untuk admin/owner
            $user = User::where('email', $username)
                       ->whereIn('role', ['admin', 'owner'])
                       ->first();
        } else {
            // Jika bukan email, cari berdasarkan NIK untuk karyawan
            $user = User::where('nik', $username)
                       ->where('role', 'employee')
                       ->first();
        }

        // Verifikasi user dan password
        if (!$user || !Hash::check($password, $user->password)) {
            return back()
                ->withErrors(['username' => 'NIK atau password salah'])
                ->withInput($request->except('password'));
        }

        // Login user
        Auth::login($user, $request->boolean('remember'));

        // Regenerate session untuk keamanan
        $request->session()->regenerate();

        // Redirect berdasarkan role tanpa menampilkan role di URL
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
        return match ($role) {
            'admin' => redirect()->intended('/admin'),
            'owner' => redirect()->intended('/owner'),
            'employee' => redirect()->intended('/employee'),
            default => redirect()->intended('/'),
        };
    }

    
}
