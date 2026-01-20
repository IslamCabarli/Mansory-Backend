<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\CarSpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CarController extends Controller
{
    // GET /api/cars - Bütün maşınları gətir
    public function index(Request $request)
    {
        $query = Car::with(['brand', 'images' => function($q) {
            $q->where('is_primary', true)->orWhere('image_type', 'gallery')->orderBy('sort_order')->limit(5);
        }]);

        // Filter: brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter: status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter: featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Filter: price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('body_type', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 12);
        $cars = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $cars
        ]);
    }

    // GET /api/cars/{id} - Bir maşını gətir
    public function show($id)
    {
        $car = Car::with(['brand', 'images', 'specifications'])
            ->find($id);

        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Maşın tapılmadı'
            ], 404);
        }

        // View count artır
        $car->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => $car
        ]);
    }

    // POST /api/cars - Yeni maşın yarat
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:available,sold,reserved',
            'registration_year' => 'nullable|string',
            'mileage' => 'nullable|integer|min:0',
            'body_type' => 'nullable|string',
            'engine' => 'nullable|string',
            'fuel_type' => 'nullable|string',
            'transmission' => 'nullable|string',
            'power_hp' => 'nullable|integer|min:0',
            'power_kw' => 'nullable|integer|min:0',
            'v_max' => 'nullable|integer|min:0',
            'acceleration' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'color_exterior' => 'nullable|string',
            'color_interior' => 'nullable|string',
            'doors' => 'nullable|integer|min:2|max:5',
            'seats' => 'nullable|integer|min:2|max:9',
            'vin' => 'nullable|string|unique:cars',
            'is_featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'specifications' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $carData = $validator->validated();
            
            // Images və specifications-ı ayır
            $images = $request->file('images', []);
            $specifications = $request->get('specifications', []);
            
            unset($carData['images'], $carData['specifications']);

            // Maşını yarat
            $car = Car::create($carData);

            // Şəkilləri yüklə
            if (!empty($images)) {
                foreach ($images as $index => $image) {
                    $path = $image->store('cars/' . $car->id, 'public');
                    
                    CarImage::create([
                        'car_id' => $car->id,
                        'image_path' => $path,
                        'image_type' => 'gallery',
                        'sort_order' => $index,
                        'is_primary' => $index === 0
                    ]);
                }
            }

            // Specifications yarat
            if (!empty($specifications)) {
                foreach ($specifications as $spec) {
                    CarSpecification::create([
                        'car_id' => $car->id,
                        'spec_key' => $spec['key'] ?? '',
                        'spec_label' => $spec['label'] ?? '',
                        'spec_value' => $spec['value'] ?? '',
                        'spec_unit' => $spec['unit'] ?? null,
                        'spec_category' => $spec['category'] ?? null,
                        'sort_order' => $spec['sort_order'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            $car->load(['brand', 'images', 'specifications']);

            return response()->json([
                'success' => true,
                'message' => 'Maşın uğurla yaradıldı',
                'data' => $car
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Xəta baş verdi: ' . $e->getMessage()
            ], 500);
        }
    }

    // PUT/PATCH /api/cars/{id} - Maşını yenilə
    public function update(Request $request, $id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Maşın tapılmadı'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'brand_id' => 'sometimes|required|exists:brands,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:available,sold,reserved',
            'registration_year' => 'nullable|string',
            'mileage' => 'nullable|integer|min:0',
            'body_type' => 'nullable|string',
            'engine' => 'nullable|string',
            'fuel_type' => 'nullable|string',
            'transmission' => 'nullable|string',
            'power_hp' => 'nullable|integer|min:0',
            'power_kw' => 'nullable|integer|min:0',
            'v_max' => 'nullable|integer|min:0',
            'acceleration' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'color_exterior' => 'nullable|string',
            'color_interior' => 'nullable|string',
            'doors' => 'nullable|integer|min:2|max:5',
            'seats' => 'nullable|integer|min:2|max:9',
            'vin' => 'nullable|string|unique:cars,vin,' . $id,
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $car->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Maşın uğurla yeniləndi',
            'data' => $car->load(['brand', 'images', 'specifications'])
        ]);
    }

    // DELETE /api/cars/{id} - Maşını sil
    public function destroy($id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Maşın tapılmadı'
            ], 404);
        }

        // Şəkilləri sil
        foreach ($car->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        // Specifications sil
        $car->specifications()->delete();

        // Maşını sil
        $car->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maşın uğurla silindi'
        ]);
    }

    // POST /api/cars/{id}/images - Şəkil əlavə et
    public function addImages(Request $request, $id)
    {
        $car = Car::find($id);

        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Maşın tapılmadı'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'image_type' => 'nullable|string|in:gallery,interior,exterior,main'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedImages = [];
        $imageType = $request->get('image_type', 'gallery');
        $currentMaxOrder = $car->images()->max('sort_order') ?? -1;

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('cars/' . $car->id, 'public');
            
            $carImage = CarImage::create([
                'car_id' => $car->id,
                'image_path' => $path,
                'image_type' => $imageType,
                'sort_order' => $currentMaxOrder + $index + 1,
                'is_primary' => false
            ]);

            $uploadedImages[] = $carImage;
        }

        return response()->json([
            'success' => true,
            'message' => 'Şəkillər uğurla əlavə olundu',
            'data' => $uploadedImages
        ], 201);
    }

    // DELETE /api/cars/{carId}/images/{imageId} - Şəkil sil
    public function deleteImage($carId, $imageId)
    {
        $image = CarImage::where('car_id', $carId)->find($imageId);

        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Şəkil tapılmadı'
            ], 404);
        }

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Şəkil uğurla silindi'
        ]);
    }

    // PUT /api/cars/{carId}/images/{imageId}/primary - Əsas şəkil et
    public function setPrimaryImage($carId, $imageId)
    {
        $car = Car::find($carId);
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Maşın tapılmadı'
            ], 404);
        }

        // Bütün şəkilləri primary-dən çıxart
        $car->images()->update(['is_primary' => false]);

        // Yeni primary şəkil təyin et
        $image = $car->images()->find($imageId);
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Şəkil tapılmadı'
            ], 404);
        }

        $image->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Əsas şəkil dəyişdirildi',
            'data' => $image
        ]);
    }
}