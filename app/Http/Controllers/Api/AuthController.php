<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEGUNDOS = 60;

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'password' => Hash::make($data['password']),
                'telefono' => $data['telefono'] ?? null,
                'estado' => true,
            ]);

            $rol = Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);
            $user->assignRole($rol);

            return $user;
        });

        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user->load('roles')),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower($data['email']);
        $clave = $email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($clave);

            throw ValidationException::withMessages([
                'email' => ["Demasiados intentos. Intenta de nuevo en {$segundos} segundos."],
            ])->status(429);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);

            throw ValidationException::withMessages([
                'email' => ['Las credenciales ingresadas no son correctas.'],
            ]);
        }

        if (! $user->estado) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está desactivada. Contacta al administrador.'],
            ])->status(403);
        }

        RateLimiter::clear($clave);

        $user->tokens()->where('name', 'flutter-app')->delete();

        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request)
    {
        return response()->json($this->formatUser($request->user()));
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'rol' => $user->roles->first()?->name,
            'foto' => Imagen::url($user->imagen_usuario),
            // Para que la app muestre solo los módulos que el usuario puede usar.
            'permisos' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
