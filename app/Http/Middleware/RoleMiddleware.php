<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')
                ->with('error', 'Anda harus login terlebih dahulu untuk mengakses halaman ini.');
        }
        $user = auth()->user();
        if (empty($user->password) || strlen($user->password) < 60) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Terjadi masalah dengan akun Anda. Silakan hubungi administrator.');
        }

        if (auth()->user()->role !== $role) {
            if (auth()->user()->role === 'admin' && $role === 'employee') {
                return redirect()->route('admin.index')
                    ->with('error', 'Anda tidak memiliki akses ke halaman Pegawai. Anda login sebagai Admin.');
            }

            if (auth()->user()->role === 'employee' && $role === 'admin') {
                return redirect()->route('admin.index')
                    ->with('error', 'Anda tidak memiliki akses ke halaman Admin. Anda login sebagai Pegawai.');
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Role tidak dikenali. Silakan hubungi administrator.');
        }



        return $next($request);
    }
}
