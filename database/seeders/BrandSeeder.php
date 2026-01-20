<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Mansory',
                'slug' => 'mansory',
                'description' => 'Luxury car modification and customization specialist',
                'is_active' => true,
            ],
            [
                'name' => 'Brabus',
                'slug' => 'brabus',
                'description' => 'High-performance automotive aftermarket tuning company',
                'is_active' => true,
            ],
            [
                'name' => 'Novitec',
                'slug' => 'novitec',
                'description' => 'Italian tuning company specializing in Ferrari and Lamborghini',
                'is_active' => true,
            ],
            [
                'name' => 'Startech',
                'slug' => 'startech',
                'description' => 'Refinement program for luxury SUVs and sports cars',
                'is_active' => true,
            ],
            [
                'name' => 'Smart',
                'slug' => 'smart',
                'description' => 'Compact city cars with unique design',
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}