<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Sortable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use Sortable;

    public function index(Request $request, ?string $rolFiltro = null)
    {
        $query = User::with('roles');

        if ($rolFiltro) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $rolFiltro));
        }

        if ($buscar = $request->get('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'telefono' => 'telefono',
            'estado' => 'estado',
        ], 'id', 'desc');

        $usuarios = $query->get();
        $roles = Role::orderBy('name')->get();

        $titulo = match ($rolFiltro) {
            'cliente' => 'Administrar Clientes',
            'vendedor' => 'Administrar Vendedores',
            default => 'Administrar Usuarios',
        };

        return view('usuarios.index', compact('usuarios', 'roles', 'titulo', 'rolFiltro', 'sort', 'dir'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'estado' => ['nullable', 'boolean'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($user->id === auth()->id() && $user->hasRole('administrador') && $data['role'] !== 'administrador') {
            return back()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        if ($user->id === auth()->id() && empty($data['estado'])) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->name = $data['name'];
        $user->telefono = $data['telefono'] ?? null;
        $user->email = $data['email'];
        $user->estado = $request->boolean('estado');

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('foto')) {
            $user->imagen_usuario = $request->file('foto')->store('usuarios', 'images');
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return back()->with('status', "Usuario \"{$user->name}\" actualizado correctamente.");
    }

    
    public function removeRole(User $user)
    {
        if ($user->id === auth()->id() && $user->hasRole('administrador')) {
            return back()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        if (!$user->roles()->exists()) {
            return back()->with('error', "\"{$user->name}\" ya no tiene ningún rol asignado.");
        }

        $user->syncRoles([]);

        return back()->with('status', "Se eliminó la asignación de rol de \"{$user->name}\".");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta desde aquí.');
        }

        $user->estado = false;
        $user->save();

        return back()->with('status', "Usuario \"{$user->name}\" desactivado correctamente.");
    }
}