<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermisoController extends Controller
{
    private const MENSAJES = [
        'name.regex' => 'El nombre debe seguir el formato "accion-modulo" (ej: ver-reportes), solo minúsculas y guiones.',
    ];

    public function index(Request $request)
    {
        $query = Permission::withCount('roles');

        if ($buscar = $request->get('buscar')) {
            $query->where('name', 'like', "%{$buscar}%");
        }

        return response()->json($query->orderBy('name')->get()->map(fn (Permission $p) => $this->formato($p)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:permissions,name', 'regex:/^[a-z0-9]+(-[a-z0-9]+)+$/'],
        ], self::MENSAJES);

        $permiso = Permission::create(['name' => $data['name'], 'guard_name' => 'web']);

        return response()->json($this->formato($permiso->loadCount('roles')), 201);
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('permissions', 'name')->ignore($permission->id),
                'regex:/^[a-z0-9]+(-[a-z0-9]+)+$/',
            ],
        ], self::MENSAJES);

        $permission->update(['name' => $data['name']]);

        return response()->json($this->formato($permission->loadCount('roles')));
    }

    public function destroy(Permission $permission)
    {
        if ($permission->roles()->exists()) {
            return response()->json([
                'message' => "No puedes eliminar \"{$permission->name}\" porque está asignado a uno o más roles. Quítaselo primero desde Roles.",
            ], 422);
        }

        $permission->delete();

        return response()->json(['message' => "Permiso \"{$permission->name}\" eliminado correctamente."]);
    }

    private function formato(Permission $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'roles_count' => (int) ($p->roles_count ?? 0),
        ];
    }
}
