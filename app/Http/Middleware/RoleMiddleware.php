<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'Anda harus login terlebih dahulu untuk mengakses halaman ini');
        }

        $user = Auth::user();
        if (!$user->hasAnyRole($roles)) {
            $rolesRoutes = [
                'admin' => 'admin.index',
                'employee' => 'employee.index',
            ];
            foreach ($rolesRoutes as $role => $route) {
                if ($user->hasRole($role)) {
                    return redirect()->route($route)
                        ->with('error', 'Role Anda tidak memiliki akses untuk mengakses halaman');
                }
            }
            return redirect()->route('login')
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
