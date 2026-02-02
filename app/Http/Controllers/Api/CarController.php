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
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['brand_id', 'name', 'status', 'price'],
            properties: [
                new OA\Property(property: 'brand_id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Mercedes S-Class'),
                new OA\Property(property: 'status', type: 'string', enum: ['available', 'sold', 'reserved']),
                new OA\Property(property: 'price', type: 'number', example: 150000),
                new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                new OA\Property(property: 'is_featured', type: 'boolean', example: false)
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Yaradıldı')]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:255',
            'status' => 'required|in:available,sold,reserved',
            'registration_year' => 'nullable|string|max:10',
            'price' => 'nullable|numeric|min:0',
            'vin' => 'nullable|string|unique:cars',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $car = Car::create($validator->validated());
            DB::commit();
            return response()->json(['success' => true, 'data' => $car], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[OA\Put(path: '/api/cars/{id}', summary: 'Maşını yenilə', tags: ['Cars'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(property: 'name', type: 'string')]))]
    #[OA\Response(response: 200, description: 'Yeniləndi')]
    public function update(Request $request, $id)
    {
        $car = Car::find($id);
        if (!$car) return response()->json(['success' => false, 'message' => 'Tapılmadı'], 404);

        $car->update($request->all());
        return response()->json(['success' => true, 'data' => $car->load('brand')]);
    }

    #[OA\Delete(path: '/api/cars/{id}', summary: 'Maşını sil', tags: ['Cars'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Silindi')]

    // MAsin sil
    public function destroy($id)
    {
        $car = Car::find($id);
        if (!$car) return response()->json(['success' => false, 'message' => 'Tapılmadı'], 404);

        $car->delete();
        return response()->json(['success' => true, 'message' => 'Silindi']);
    }



    #[OA\Post(path: '/api/cars/{id}/images', summary: 'Şəkil əlavə et', tags: ['Car Images'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: 'images[]', type: 'array', items: new OA\Items(type: 'string', format: 'binary')),
                    new OA\Property(property: 'image_type', type: 'string', default: 'gallery')
                ]
            )
        )
    )]
    #[OA\Response(response: 201, description: 'Əlavə edildi')]

    //   Sekil elave et
    public function addImages(Request $request, $id)
    {
        $car = Car::find($id);
        if (!$car) return response()->json(['success' => false, 'message' => 'Maşın yoxdur'], 404);

        $uploadedImages = [];
        foreach ($request->file('images', []) as $index => $image) {
            $path = $image->store('cars/' . $car->id, 'public');
            $uploadedImages[] = CarImage::create([
                'car_id' => $car->id,
                'image_path' => $path,
                'image_type' => $request->get('image_type', 'gallery'),
                'is_primary' => false
            ]);
        }
        return response()->json(['success' => true, 'data' => $uploadedImages], 201);
    }

    #[OA\Delete(path: '/api/cars/{carId}/images/{imageId}', summary: 'Şəkil sil', tags: ['Car Images'])]
    #[OA\Parameter(name: 'carId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'imageId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Silindi')]

    //      Delete car image
    public function deleteImage($carId, $imageId)
    {
        $image = CarImage::where('car_id', $carId)->find($imageId);
        if (!$image) return response()->json(['success' => false, 'message' => 'Şəkil yoxdur'], 404);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();
        return response()->json(['success' => true, 'message' => 'Silindi']);
    }

    #[OA\Put(path: '/api/cars/{carId}/images/{imageId}/primary', summary: 'Əsas şəkil et', tags: ['Car Images'])]
    #[OA\Parameter(name: 'carId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'imageId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Uğurlu')]

    //    Sekil esas et
    public function setPrimaryImage($carId, $imageId)
    {
        $car = Car::find($carId);
        if (!$car) return response()->json(['success' => false, 'message' => 'Maşın yoxdur'], 404);

        $car->images()->update(['is_primary' => false]);
        $image = $car->images()->find($imageId);
        if ($image) {
            $image->update(['is_primary' => true]);
            return response()->json(['success' => true, 'data' => $image]);
        }
        return response()->json(['success' => false, 'message' => 'Şəkil yoxdur'], 404);
    }
}