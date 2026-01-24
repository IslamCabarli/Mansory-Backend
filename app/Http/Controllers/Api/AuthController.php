<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

use OpenApi\Attributes as OA;

#[OA\Info(title: "Mansory API", version: "1.0.0", description: "API Documentation")]
#[OA\Server(url: 'http://localhost:8000', description: "Local Server")]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class AuthController extends Controller
{
    #[OA\Post(path: '/api/auth/register', summary: 'İstifadəçi qeydiyyatı', tags: ['Authentication'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Islam'),
                new OA\Property(property: 'email', type: 'string', example: 'user@mail.com'),
                new OA\Property(property: 'password', type: 'string', example: '12345678'),
                new OA\Property(property: 'password_confirmation', type: 'string', example: '12345678')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Uğurlu qeydiyyat')]
    #[OA\Response(response: 422, description: 'Validation xətası')]
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Qeydiyyat uğurla tamamlandı',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ]
        ], 201);
    }

    #[OA\Post(path: '/api/auth/login', summary: 'İstifadəçi girişi', tags: ['Authentication'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                new OA\Property(property: 'password', type: 'string', example: '123456')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Uğurlu giriş')]
    #[OA\Response(response: 401, description: 'Səhv məlumat')]
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            return response()->json(['success' => false, 'message' => 'Email və ya şifrə yanlışdır'], 401);
        }

        return $this->respondWithToken($token);
    }

    #[OA\Get(path: '/api/auth/me', summary: 'Cari istifadəçi', security: [['bearerAuth' => []]], tags: ['Authentication'])]
    #[OA\Response(response: 200, description: 'Uğurlu')]
    public function me()
    {
        return response()->json(['success' => true, 'data' => JWTAuth::user()]);
    }

    #[OA\Post(path: '/api/auth/logout', summary: 'Çıxış', security: [['bearerAuth' => []]], tags: ['Authentication'])]
    #[OA\Response(response: 200, description: 'Uğurlu')]
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['success' => true, 'message' => 'Çıxış uğurlu']);
    }

    #[OA\Post(path: '/api/auth/refresh', summary: 'Token yenilə', security: [['bearerAuth' => []]], tags: ['Authentication'])]
    #[OA\Response(response: 200, description: 'Uğurlu')]
    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
    }

    #[OA\Put(path: '/api/profile', summary: 'Profil yenilə', security: [['bearerAuth' => []]], tags: ['Authentication'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Islam'),
                new OA\Property(property: 'email', type: 'string', example: 'user@mail.com'),
                new OA\Property(property: 'password', type: 'string', example: '12345678'),
                new OA\Property(property: 'password_confirmation', type: 'string', example: '12345678')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Yeniləndi')]
    public function updateProfile(Request $request)
    {
        $user = JWTAuth::user();
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return response()->json(['success' => true, 'message' => 'Profil yeniləndi', 'data' => $user]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success' => true,
            'message' => 'Uğurlu',
            'data' => [
                'user' => JWTAuth::user(),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ]
        ]);
    }
}
