<?php

namespace App\Auth;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) || 
            (isset($credentials['password']) && count($credentials) === 1)) {
            return null;
        }

        $username = $credentials['username'] ?? null;

        if (!$username) {
            return null;
        }

        // Cek apakah username adalah NIK (cek di tabel employees)
        $employee = Employee::where('nik', $username)->first();
        
        if ($employee) {
            // Jika ditemukan di tabel employees, ambil user dengan role employee
            $user = User::where('id', $employee->user_id)
                ->where('role', 'employee')
                ->first();
            
            return $user;
        }

        // Jika tidak ditemukan di employees, cek sebagai admin
        return User::where('username', $username)
            ->where('role', 'admin')
            ->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'];
        
        // Validasi password menggunakan Hash
        return $this->hasher->check($plain, $user->getAuthPassword());
    }
}