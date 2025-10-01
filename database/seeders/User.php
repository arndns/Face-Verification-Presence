<?php

namespace Database\Seeders;

use App\Models\User as ModelsUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class User extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ModelsUser::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nama' => 'Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        ModelsUser::firstOrCreate(
            ['email' => 'paijo@example.com'],
            [
                'nama' => 'Paijo',
                'nik' => '09122131241',
                'no_tilpun' => '085765432293',
                'jabatan' => 'Direktur',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'email_verified_at' => now(),
            ]
        );

        ModelsUser::firstOrCreate(
            ['email' => 'sumanto@example.com'],
            [
                'nama' => 'Sumanto',
                'nik' => '092356788945',
                'no_tilpun' => '08576234789023',
                'jabatan' => 'Koor Humas',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'email_verified_at' => now(),
            ]
        );
        
    }
}
