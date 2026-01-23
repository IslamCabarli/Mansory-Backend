<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    // GET /api/brands - Bütün markaları gətir
    /**
     * @OA\Get(
     *     path="/api/brands",
     *     summary="Bütün markaları gətir",
     *     tags={"Brands"},
     *     @OA\Response(response=200, description="Uğurlu")
     * )
     */

    public function index()
    {
        $brands = Brand::withCount('cars')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brands
        ]);
    }

    // GET /api/brands/{id} - Bir markanı gətir
     /**
     * @OA\Get(
     *     path="/api/brands/{id}",
     *     summary="Bir markanı gətir",
     *     tags={"Brands"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Uğurlu"),
     *     @OA\Response(response=404, description="Tapılmadı")
     * )
     */
    public function show($id)
    {
        $brand = Brand::with(['cars' => function($query) {
            $query->available()->with('images');
        }])->find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand tapılmadı'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $brand
        ]);
    }

    // POST /api/brands - Yeni marka yarat
    /**
     * @OA\Post(
     *     path="/api/brands",
     *     summary="Yeni marka yarat",
     *     tags={"Brands"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Yaradıldı"),
     *     @OA\Response(response=422, description="Validation xətası")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:brands',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Logo yüklə
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Marka uğurla yaradıldı',
            'data' => $brand
        ], 201);
    }

    // PUT/PATCH /api/brands/{id} - Markanı yenilə

    /**
     * @OA\Put(
     *     path="/api/brands/{id}",
     *     summary="Markanı yenilə",
     *     tags={"Brands"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Yeniləndi"),
     *     @OA\Response(response=404, description="Tapılmadı"),
     *     @OA\Response(response=422, description="Validation xətası")
     * )
     */
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand tapılmadı'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:brands,name,' . $id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Yeni logo yüklə
        if ($request->hasFile('logo')) {
            // Köhnə logonu sil
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Marka uğurla yeniləndi',
            'data' => $brand
        ]);
    }

    // DELETE /api/brands/{id} - Markanı sil
    /**
     * @OA\Delete(
     *     path="/api/brands/{id}",
     *     summary="Markanı sil",
     *     tags={"Brands"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Silindi"),
     *     @OA\Response(response=404, description="Tapılmadı")
     * )
     */
    public function destroy($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Brand tapılmadı'
            ], 404);
        }

        // Logo sil
        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Marka uğurla silindi'
        ]);
    }
}