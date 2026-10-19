<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Denso'],
            ['name' => 'Bosch'],
            ['name' => 'NGK'],
            ['name' => 'Motul'],
            ['name' => 'Castrol'],
            ['name' => 'Mobil 1'],
            ['name' => 'Aisin'],
            ['name' => 'KYB'],
            ['name' => 'Brembo'],
            ['name' => 'Michelin'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand['name']]);
        }
    }
}