<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarSpecification;
use Illuminate\Database\Seeder;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        // Mansory markanı tap
        $mansory = Brand::where('slug', 'mansory')->first();
        $smart = Brand::where('slug', 'smart')->first();
        $brabus = Brand::where('slug', 'brabus')->first();

        if ($mansory) {
            // Smart by Mansory
            $car1 = Car::create([
                'brand_id' => $mansory->id,
                'name' => 'Smart by Mansory',
                'slug' => 'smart-by-mansory',
                'description' => 'Luxury customized Smart car with exclusive Mansory design and performance upgrades.',
                'status' => 'available',
                'registration_year' => '2017/12',
                'mileage' => 13000,
                'body_type' => 'Cabrio',
                'engine' => '3V',
                'fuel_type' => 'Petrol',
                'transmission' => 'Automatic',
                'power_hp' => 120,
                'power_kw' => 89,
                'v_max' => 130,
                'acceleration' => '11.6',
                'price' => 45000.00,
                'currency' => 'EUR',
                'color_exterior' => 'White/Tiffany Blue',
                'color_interior' => 'Tiffany Blue Leather',
                'doors' => 2,
                'seats' => 2,
                'is_featured' => true,
            ]);

            // Specifications əlavə et
            $specifications = [
                ['spec_key' => 'displacement', 'spec_label' => 'Displacement', 'spec_value' => '898', 'spec_unit' => 'cc', 'spec_category' => 'engine', 'sort_order' => 1],
                ['spec_key' => 'torque', 'spec_label' => 'Torque', 'spec_value' => '135', 'spec_unit' => 'Nm', 'spec_category' => 'performance', 'sort_order' => 2],
                ['spec_key' => 'fuel_consumption', 'spec_label' => 'Fuel Consumption', 'spec_value' => '4.8', 'spec_unit' => 'L/100km', 'spec_category' => 'economy', 'sort_order' => 3],
                ['spec_key' => 'co2_emissions', 'spec_label' => 'CO2 Emissions', 'spec_value' => '112', 'spec_unit' => 'g/km', 'spec_category' => 'economy', 'sort_order' => 4],
                ['spec_key' => 'wheel_size', 'spec_label' => 'Wheel Size', 'spec_value' => '18', 'spec_unit' => 'inch', 'spec_category' => 'dimensions', 'sort_order' => 5],
                ['spec_key' => 'custom_interior', 'spec_label' => 'Custom Interior', 'spec_value' => 'Tiffany Blue Leather with Diamond Stitching', 'spec_unit' => null, 'spec_category' => 'features', 'sort_order' => 6],
                ['spec_key' => 'custom_exterior', 'spec_label' => 'Custom Exterior', 'spec_value' => 'Wide Body Kit, Carbon Fiber Elements', 'spec_unit' => null, 'spec_category' => 'features', 'sort_order' => 7],
            ];

            foreach ($specifications as $spec) {
                CarSpecification::create(array_merge(['car_id' => $car1->id], $spec));
            }

            // Mansory G63
            $car2 = Car::create([
                'brand_id' => $mansory->id,
                'name' => 'Mercedes-AMG G63 by Mansory',
                'slug' => 'mercedes-g63-mansory',
                'description' => 'Ultimate luxury SUV with Mansory performance and design package.',
                'status' => 'available',
                'registration_year' => '2023',
                'mileage' => 5000,
                'body_type' => 'SUV',
                'engine' => 'V8 Biturbo',
                'fuel_type' => 'Petrol',
                'transmission' => 'Automatic',
                'power_hp' => 850,
                'power_kw' => 625,
                'v_max' => 250,
                'acceleration' => '3.5',
                'price' => 450000.00,
                'currency' => 'EUR',
                'color_exterior' => 'Matte Black',
                'color_interior' => 'Red/Black Leather',
                'doors' => 5,
                'seats' => 5,
                'is_featured' => true,
            ]);
        }

        if ($brabus) {
            // Brabus S-Class
            $car3 = Car::create([
                'brand_id' => $brabus->id,
                'name' => 'Brabus 900 based on Mercedes S-Class',
                'slug' => 'brabus-900-s-class',
                'description' => 'The pinnacle of luxury and performance. Brabus 900 rocket edition.',
                'status' => 'available',
                'registration_year' => '2024',
                'mileage' => 2000,
                'body_type' => 'Sedan',
                'engine' => 'V12 Biturbo',
                'fuel_type' => 'Petrol',
                'transmission' => 'Automatic',
                'power_hp' => 900,
                'power_kw' => 662,
                'v_max' => 350,
                'acceleration' => '3.7',
                'price' => 550000.00,
                'currency' => 'EUR',
                'color_exterior' => 'Deep Blue Metallic',
                'color_interior' => 'Cognac Leather',
                'doors' => 4,
                'seats' => 5,
                'is_featured' => true,
            ]);
        }

        if ($smart) {
            // Regular Smart
            $car4 = Car::create([
                'brand_id' => $smart->id,
                'name' => 'Smart ForTwo Passion',
                'slug' => 'smart-fortwo-passion',
                'description' => 'Compact city car perfect for urban driving.',
                'status' => 'available',
                'registration_year' => '2020',
                'mileage' => 25000,
                'body_type' => 'Coupe',
                'engine' => '3-Cylinder',
                'fuel_type' => 'Petrol',
                'transmission' => 'Automatic',
                'power_hp' => 90,
                'power_kw' => 66,
                'v_max' => 155,
                'acceleration' => '10.7',
                'price' => 18000.00,
                'currency' => 'EUR',
                'color_exterior' => 'Red',
                'color_interior' => 'Black',
                'doors' => 2,
                'seats' => 2,
                'is_featured' => false,
            ]);
        }
    }
}