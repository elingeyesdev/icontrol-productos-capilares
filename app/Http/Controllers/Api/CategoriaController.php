<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{

    public function index()
    {
        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre_categoria')
            ->get(['id', 'nombre_categoria', 'descripcion']);

        return response()->json($categorias);
    }

    public function indexTodos(Request $request)
    {
        $query = Categoria::withCount('productos');

        if ($buscar = $request->get('buscar')) {
            $query->where('nombre_categoria', 'like', "%{$buscar}%");
        }

        return response()->json($query->orderByDesc('id')->get()->map(fn (Categoria $c) => $this->formato($c)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:100', 'unique:categorias,nombre_categoria'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', 'boolean'],
        ]);

        $categoria = Categoria::create([
            'nombre_categoria' => $data['nombre_categoria'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $request->boolean('estado', true),
        ]);

        return response()->json($this->formato($categoria->loadCount('productos')), 201);
    }

    public function update(Request $request, Categoria $categoria)
    {
        $data = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:100', 'unique:categorias,nombre_categoria,'.$categoria->id],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', 'boolean'],
        ]);

        $categoria->update([
            'nombre_categoria' => $data['nombre_categoria'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $request->has('estado') ? $request->boolean('estado') : $categoria->estado,
        ]);

        return response()->json($this->formato($categoria->loadCount('productos')));
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) {
            return response()->json([
                'message' => "No puedes eliminar \"{$categoria->nombre_categoria}\" porque tiene productos asociados. Reasigna o elimina esos productos primero.",
            ], 422);
        }

        $categoria->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente.']);
    }

    private function formato(Categoria $c): array
    {
        return [
            'id' => $c->id,
            'nombre_categoria' => $c->nombre_categoria,
            'descripcion' => $c->descripcion,
            'estado' => (bool) $c->estado,
            'productos_count' => (int) ($c->productos_count ?? 0),
        ];
    }
}
