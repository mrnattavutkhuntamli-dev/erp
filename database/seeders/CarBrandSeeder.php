<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarBrand;

class CarBrandSeeder extends Seeder
{
    public function run(): void
    {
        $carBrands = [
            ['name' => 'Toyota'],
            ['name' => 'Honda'],
            ['name' => 'Isuzu'],
            ['name' => 'Mitsubishi'],
            ['name' => 'Nissan'],
            ['name' => 'Ford'],
            ['name' => 'Mazda'],
            ['name' => 'Yamaha'],
            ['name' => 'Hino'],
        ];

        foreach ($carBrands as $carBrand) {
            CarBrand::firstOrCreate(['name' => $carBrand['name']]);
        }
    }
}