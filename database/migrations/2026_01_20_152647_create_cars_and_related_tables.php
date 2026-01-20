<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'sold', 'reserved'])->default('available');
            
            // Əsas məlumatlar
            $table->string('registration_year')->nullable();
            $table->integer('mileage')->nullable(); // km
            $table->string('body_type')->nullable(); // Cabrio, Sedan və s.
            $table->string('engine')->nullable(); // 3V, V8 və s.
            $table->string('fuel_type')->nullable(); // Petrol, Diesel, Electric
            $table->string('transmission')->nullable(); // Automatic, Manual
            
            // Texniki xüsusiyyətlər
            $table->integer('power_hp')->nullable(); // At gücü
            $table->integer('power_kw')->nullable(); // Kilovat
            $table->integer('v_max')->nullable(); // Maksimal sürət km/h
            $table->string('acceleration')->nullable(); // 0-100 km/h (məsələn: "11,6")
            
            // Qiymət
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Əlavə məlumatlar
            $table->string('color_exterior')->nullable();
            $table->string('color_interior')->nullable();
            $table->integer('doors')->nullable();
            $table->integer('seats')->nullable();
            $table->string('vin')->nullable()->unique();
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->boolean('is_featured')->default(false);
            $table->integer('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('car_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('image_type')->default('gallery'); // main, gallery, interior, exterior
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('car_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->string('spec_key'); // məsələn: 'wheel_size', 'battery_capacity'
            $table->string('spec_label'); // məsələn: 'Wheel Size', 'Battery Capacity'
            $table->string('spec_value');
            $table->string('spec_unit')->nullable(); // məsələn: 'inch', 'kWh'
            $table->string('spec_category')->nullable(); // məsələn: 'performance', 'dimensions'
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_specifications');
        Schema::dropIfExists('car_images');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('brands');
    }
};