<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        return response()->json($roles->map(fn (Role $r) => $this->formato($r)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        return response()->json($this->formato($role->load('permissions')), 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,'.$role->id],
        ]);

        $role->update(['name' => $data['name']]);

        return response()->json($this->formato($role->load('permissions')));
    }

    public function destroy(Role $role)
    {

        if (in_array($role->name, ['administrador', 'vendedor', 'cliente'])) {
            return response()->json(['message' => 'No puedes eliminar un rol base del sistema.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }

    public function syncPermisos(Request $request, Role $role)
    {
        $data = $request->validate([
            'permisos' => ['array'],
            'permisos.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->syncPermissions($data['permisos'] ?? []);

        return response()->json($this->formato($role->load('permissions')));
    }

    private function formato(Role $r): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'permisos' => $r->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
        ];
    }
}
