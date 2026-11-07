<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class PerfilController extends Controller
{
    /** Equivale a MainController::profileUpdate (+ teléfono, que la web guarda al hacer checkout). */
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($request->user()->id),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? $user->telefono,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('foto')) {
            $user->imagen_usuario = $request->file('foto')->store('usuarios', 'images');
        }

        $user->save();
        $user->load('roles');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'rol' => $user->roles->first()?->name,
            'foto' => Imagen::url($user->imagen_usuario),
            'permisos' => $user->getAllPermissions()->pluck('name')->values(),
        ]);
    }

    /**
     * Cambio de contraseña. Se compara con Hash::check porque la regla "current_password"
     * usa el guard por defecto (sesión web) y con token Sanctum siempre fallaría.
     */
    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    /** Como la web: intenta borrar la cuenta y, si tiene pedidos/ventas/compras, solo la desactiva. */
    public function destroy(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['La contraseña no es correcta.'],
            ]);
        }

        $user->tokens()->delete();

        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            $user->estado = false;
            $user->save();
        }

        return response()->json(['message' => 'Tu cuenta fue eliminada.']);
    }
}
