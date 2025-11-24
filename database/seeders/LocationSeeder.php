<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default location
        $location = Location::create([
            'kota' => 'Jakarta',
            'alamat' => 'Kantor Pusat - Jl. Sudirman No. 1',
            'latitude' => -6.208763,  // Contoh koordinat Jakarta
            'longitude' => 106.845599,
            'radius' => 100, // 100 meter radius
        ]);

        // Assign location to all employees that don't have one
        Employee::whereNull('location_id')->update([
            'location_id' => $location->id
        ]);
    }
}
