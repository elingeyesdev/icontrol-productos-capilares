<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::withCount('compras');

        if ($buscar = $request->get('buscar')) {
            $query->where('nombre_empresa', 'like', "%{$buscar}%");
        }

        return response()->json($query->orderByDesc('id')->get()->map(fn (Proveedor $p) => $this->formato($p)));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        $proveedor = Proveedor::create($data + ['activo' => $request->boolean('activo', true)]);

        return response()->json($this->formato($proveedor->loadCount('compras')), 201);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $data = $this->validar($request);

        $proveedor->update($data + [
            'activo' => $request->has('activo') ? $request->boolean('activo') : $proveedor->activo,
        ]);

        return response()->json($this->formato($proveedor->loadCount('compras')));
    }

    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->compras()->exists()) {
            return response()->json([
                'message' => "No puedes eliminar \"{$proveedor->nombre_empresa}\" porque tiene compras asociadas. Desactívalo en su lugar.",
            ], 422);
        }

        $proveedor->delete();

        return response()->json(['message' => 'Proveedor eliminado correctamente.']);
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
        ]);

        return [
            'nombre_empresa' => $data['nombre_empresa'],
            'contacto' => $data['contacto'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null,
        ];
    }

    private function formato(Proveedor $p): array
    {
        return [
            'id' => $p->id,
            'nombre_empresa' => $p->nombre_empresa,
            'contacto' => $p->contacto,
            'telefono' => $p->telefono,
            'email' => $p->email,
            'direccion' => $p->direccion,
            'activo' => (bool) $p->activo,
            'compras_count' => (int) ($p->compras_count ?? 0),
        ];
    }
}
