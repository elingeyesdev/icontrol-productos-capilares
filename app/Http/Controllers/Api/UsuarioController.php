<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{

    public function index(Request $request)
    {
        return $this->listar($request, $request->get('rol'));
    }

    public function clientes(Request $request)
    {
        return $this->listar($request, 'cliente');
    }

    public function vendedores(Request $request)
    {
        return $this->listar($request, 'vendedor');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'estado' => ['nullable', 'boolean'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        $activo = $request->has('estado') ? $request->boolean('estado') : $user->estado;
        $propio = $user->id === $request->user()->id;

        if ($propio && $user->hasRole('administrador') && $data['role'] !== 'administrador') {
            return response()->json(['message' => 'No puedes quitarte tu propio rol de administrador.'], 422);
        }

        if ($propio && ! $activo) {
            return response()->json(['message' => 'No puedes desactivar tu propia cuenta.'], 422);
        }

        $user->name = $data['name'];
        $user->telefono = $data['telefono'] ?? null;
        $user->email = $data['email'];
        $user->estado = $activo;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('foto')) {
            $user->imagen_usuario = $request->file('foto')->store('usuarios', 'images');
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return response()->json($this->formato($user->load('roles')));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta desde aquí.'], 422);
        }

        $user->estado = false;
        $user->save();
        $user->tokens()->delete();

        return response()->json(['message' => "Usuario \"{$user->name}\" desactivado correctamente."]);
    }

    public function rolIndex(Request $request)
    {
        return $this->listar($request, null);
    }

    public function rolStore(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($data['user_id']);

        if ($user->id === $request->user()->id && $user->hasRole('administrador') && $data['role'] !== 'administrador') {
            return response()->json(['message' => 'No puedes quitarte tu propio rol de administrador.'], 422);
        }

        $user->syncRoles([$data['role']]);

        return response()->json($this->formato($user->load('roles')));
    }

    public function rolDestroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id && $user->hasRole('administrador')) {
            return response()->json(['message' => 'No puedes quitarte tu propio rol de administrador.'], 422);
        }

        $user->syncRoles([]);

        return response()->json(['message' => "Rol de \"{$user->name}\" eliminado correctamente."]);
    }

    private function listar(Request $request, ?string $rol)
    {
        $query = User::with('roles');

        if ($rol) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $rol));
        }

        if ($buscar = $request->get('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        return response()->json($query->orderByDesc('id')->get()->map(fn (User $u) => $this->formato($u)));
    }

    private function formato(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'telefono' => $u->telefono,
            'estado' => (bool) $u->estado,
            'rol' => $u->roles->first()?->name,
            'foto' => Imagen::url($u->imagen_usuario),
        ];
    }
}
