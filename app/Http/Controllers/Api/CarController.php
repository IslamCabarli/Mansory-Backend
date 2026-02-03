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
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class CarController extends Controller
{
    #[OA\Get(
        path: '/api/cars',
        summary: 'Bütün maşınları gətir (Filtr və Search ilə)',
        tags: ['Cars'],
        parameters: [
            new OA\Parameter(name: 'brand_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['available', 'sold', 'reserved'])),
            new OA\Parameter(name: 'is_featured', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'created_at')),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 12))
        ]
    )]
    #[OA\Response(response: 200, description: 'Uğurlu')]
    public function index(Request $request)
    {
        $query = Car::with(['brand', 'images' => function($q) {
            $q->where('is_primary', true)->orWhere('image_type', 'gallery')->orderBy('sort_order')->limit(5);
        }]);

        if ($request->has('brand_id')) { $query->where('brand_id', $request->brand_id); }
        if ($request->has('status')) { $query->where('status', $request->status); }
        if ($request->has('is_featured')) { $query->where('is_featured', $request->boolean('is_featured')); }
        if ($request->has('min_price')) { $query->where('price', '>=', $request->min_price); }
        if ($request->has('max_price')) { $query->where('price', '<=', $request->max_price); }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('body_type', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));
        $cars = $query->paginate($request->get('per_page', 12));

        return response()->json(['success' => true, 'data' => $cars]);
    }

    #[OA\Get(path: '/api/cars/{id}', summary: 'Bir maşını gətir', tags: ['Cars'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Uğurlu')]
    #[OA\Response(response: 404, description: 'Tapılmadı')]
    public function show($id)
    {
        $car = Car::with(['brand', 'images', 'specifications'])->find($id);

        if (!$car) {
            return response()->json(['success' => false, 'message' => 'Maşın tapılmadı'], 404);
        }

        $car->incrementViewCount();
        return response()->json(['success' => true, 'data' => $car]);
    }

    #[OA\Post(path: '/api/cars', summary: 'Yeni maşın yarat', tags: ['Cars'])]
    #[OA\Response(response: 201, description: 'Yaradıldı')]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Masin ucun validasiyalar
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:available,sold,reserved',
            'registration_year' => 'nullable|string|max:10',
            'mileage' => 'nullable|integer|min:0',
            'body_type' => 'nullable|string|max:100',
            'engine' => 'nullable|string|max:255',
            'fuel_type' => 'nullable|string|max:50',
            'transmission' => 'nullable|string|max:50',
            'power_hp' => 'nullable|integer|min:0',
            'power_kw' => 'nullable|integer|min:0',
            'v_max' => 'nullable|integer|min:0',
            'acceleration' => 'nullable|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'color_exterior' => 'nullable|string|max:100',
            'color_interior' => 'nullable|string|max:100',
            'doors' => 'nullable|integer|min:2|max:5',
            'seats' => 'nullable|integer|min:2|max:9',
            'vin' => 'nullable|string|unique:cars,vin',
            'is_featured' => 'boolean',
            'specifications' => 'nullable|array',
            'specifications.*.spec_label' => 'required|string|max:255',
            'specifications.*.spec_value' => 'required|string|max:255',
            'specifications.*.spec_unit' => 'nullable|string|max:50',
            'specifications.*.spec_category' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();
            
    
            $specifications = $data['specifications'] ?? [];
            unset($data['specifications']);

        
            $car = Car::create($data);

            
            if (!empty($specifications)) {
                foreach ($specifications as $index => $spec) {
                    $car->specifications()->create([
                        'spec_key' => $spec['spec_key'] ?? Str::slug($spec['spec_label'], '_'),
                        'spec_label' => $spec['spec_label'],
                        'spec_value' => $spec['spec_value'],
                        'spec_unit' => $spec['spec_unit'] ?? null,
                        'spec_category' => $spec['spec_category'] ?? 'general',
                        'sort_order' => $index
                    ]);
                }
            }

            DB::commit();

            
            $car->load(['brand', 'images', 'specifications']);

            return response()->json([
                'success' => true,
                'message' => 'Car created successfully',
                'data' => $car
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create car: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Put(path: '/api/cars/{id}', summary: 'Maşını yenilə', tags: ['Cars'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Yeniləndi')]
    public function update(Request $request, $id)
    {
        $car = Car::find($id);
        
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Car not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'brand_id' => 'sometimes|required|exists:brands,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:available,sold,reserved',
            'registration_year' => 'nullable|string|max:10',
            'mileage' => 'nullable|integer|min:0',
            'body_type' => 'nullable|string|max:100',
            'engine' => 'nullable|string|max:255',
            'fuel_type' => 'nullable|string|max:50',
            'transmission' => 'nullable|string|max:50',
            'power_hp' => 'nullable|integer|min:0',
            'power_kw' => 'nullable|integer|min:0',
            'v_max' => 'nullable|integer|min:0',
            'acceleration' => 'nullable|string|max:20',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'color_exterior' => 'nullable|string|max:100',
            'color_interior' => 'nullable|string|max:100',
            'doors' => 'nullable|integer|min:2|max:5',
            'seats' => 'nullable|integer|min:2|max:9',
            'vin' => 'nullable|string|unique:cars,vin,' . $id,
            'is_featured' => 'boolean',
            'specifications' => 'nullable|array',
            'specifications.*.spec_label' => 'required|string|max:255',
            'specifications.*.spec_value' => 'required|string|max:255',
            'specifications.*.spec_unit' => 'nullable|string|max:50',
            'specifications.*.spec_category' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();
            
            $specifications = $data['specifications'] ?? null;
            unset($data['specifications']);

            $car->update($data);

            if ($specifications !== null) {
                $car->specifications()->delete();
                
                foreach ($specifications as $index => $spec) {
                    $car->specifications()->create([
                        'spec_key' => $spec['spec_key'] ?? Str::slug($spec['spec_label'], '_'),
                        'spec_label' => $spec['spec_label'],
                        'spec_value' => $spec['spec_value'],
                        'spec_unit' => $spec['spec_unit'] ?? null,
                        'spec_category' => $spec['spec_category'] ?? 'general',
                        'sort_order' => $index
                    ]);
                }
            }

            DB::commit();

            $car->load(['brand', 'images', 'specifications']);

            return response()->json([
                'success' => true,
                'message' => 'Car updated successfully',
                'data' => $car
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update car: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(path: '/api/cars/{id}', summary: 'Maşını sil', tags: ['Cars'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Silindi')]
    public function destroy($id)
    {
        $car = Car::find($id);
        
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Car not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            
            foreach ($car->images as $image) {
                if (Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
            }
            
            
            $car->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Car deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete car: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(path: '/api/cars/{id}/images', summary: 'Şəkil əlavə et', tags: ['Car Images'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 201, description: 'Əlavə edildi')]
    public function addImages(Request $request, $id)
    {
        $car = Car::find($id);
        
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Car not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'image_type' => 'required|in:main,gallery,interior,exterior'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedImages = [];
        $sortOrder = $car->images()->count();

        foreach ($request->file('images', []) as $image) {
            $path = $image->store('cars/' . $car->id, 'public');
            
            $carImage = CarImage::create([
                'car_id' => $car->id,
                'image_path' => $path,
                'image_type' => $request->get('image_type', 'gallery'),
                'sort_order' => $sortOrder++,
                'is_primary' => false
            ]);
            
            $uploadedImages[] = $carImage;
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'data' => $uploadedImages
        ], 201);
    }

    #[OA\Delete(path: '/api/cars/{carId}/images/{imageId}', summary: 'Şəkil sil', tags: ['Car Images'])]
    #[OA\Parameter(name: 'carId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'imageId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Silindi')]
    public function deleteImage($carId, $imageId)
    {
        $image = CarImage::where('car_id', $carId)->find($imageId);
        
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        }

        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }

    #[OA\Put(path: '/api/cars/{carId}/images/{imageId}/primary', summary: 'Əsas şəkil et', tags: ['Car Images'])]
    #[OA\Parameter(name: 'carId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'imageId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Uğurlu')]
    public function setPrimaryImage($carId, $imageId)
    {
        $car = Car::find($carId);
        
        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Car not found'
            ], 404);
        }

        $image = $car->images()->find($imageId);
        
        if (!$image) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found'
            ], 404);
        }

        $car->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Primary image set successfully',
            'data' => $image
        ]);
    }
}