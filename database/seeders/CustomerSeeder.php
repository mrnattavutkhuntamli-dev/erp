<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarModel;
use App\Models\CarBrand;

class CarModelSeeder extends Seeder
{
    public function run(): void
    {
        // หา ID ของยี่ห้อรถก่อน
        $toyota = CarBrand::where('name', 'Toyota')->first();
        $honda = CarBrand::where('name', 'Honda')->first();
        $isuzu = CarBrand::where('name', 'Isuzu')->first();
        $yamaha = CarBrand::where('name', 'Yamaha')->first();

        $models = [
            // Toyota
            ['car_brand_id' => $toyota->id, 'name' => 'Vigo', 'year_start' => 2004, 'year_end' => 2015],
            ['car_brand_id' => $toyota->id, 'name' => 'Fortuner', 'year_start' => 2005, 'year_end' => 2024],
            ['car_brand_id' => $toyota->id, 'name' => 'Hilux Revo', 'year_start' => 2015, 'year_end' => 2024],
            ['car_brand_id' => $toyota->id, 'name' => 'Yaris', 'year_start' => 2006, 'year_end' => 2024],

            // Honda
            ['car_brand_id' => $honda->id, 'name' => 'Civic', 'year_start' => 2001, 'year_end' => 2024],
            ['car_brand_id' => $honda->id, 'name' => 'City', 'year_start' => 2003, 'year_end' => 2024],
            ['car_brand_id' => $honda->id, 'name' => 'CR-V', 'year_start' => 2002, 'year_end' => 2024],

            // Isuzu
            ['car_brand_id' => $isuzu->id, 'name' => 'D-Max', 'year_start' => 2002, 'year_end' => 2024],
            ['car_brand_id' => $isuzu->id, 'name' => 'MU-X', 'year_start' => 2013, 'year_end' => 2024],

            // Yamaha
            ['car_brand_id' => $yamaha->id, 'name' => 'NMAX', 'year_start' => 2015, 'year_end' => 2024],
            ['car_brand_id' => $yamaha->id, 'name' => 'Aerox', 'year_start' => 2017, 'year_end' => 2024],
        ];

        foreach ($models as $model) {
            CarModel::create($model);
        }
    }
}
