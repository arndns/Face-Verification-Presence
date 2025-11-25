<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'zfauzanadmin123@gmail.com',
            'password' => Hash::make('mugo2sukses'),
        ]);

        $user1 = User::create([
            'username' => '1234567890', // Username = NIK
            'password' => Hash::make('password123'),
            'role' => 'employee',
        ]);

        Employee::create([
            'user_id' => $user1->id,
            'nik' => '1234567890',
            'nama' => 'John Doe',
        ]);
    }
}
