<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistorialPrecioProducto;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Support\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{

    public function index(Request $request)
    {
        $query = Producto::with('categoria')->where('estado', true);

        if ($idCategoria = $request->get('id_categoria')) {
            $query->where('id_categoria', $idCategoria);
        }

        if ($buscar = $request->get('buscar')) {
            $query->where('nombre_producto', 'like', "%{$buscar}%");
        }

        $productos = $query->orderBy('nombre_producto')->get();

        return response()->json($productos->map(fn (Producto $p) => $this->formatProducto($p)));
    }

    public function show(Producto $producto)
    {
        if (! $producto->estado) {
            return response()->json(['message' => 'Producto no disponible.'], 404);
        }

        $producto->load('categoria');

        return response()->json($this->formatProducto($producto));
    }

    public function indexTodos(Request $request)
    {
        $query = Producto::with('categoria');

        if ($idCategoria = $request->get('id_categoria')) {
            $query->where('id_categoria', $idCategoria);
        }

        if ($buscar = $request->get('buscar')) {
            $query->where('nombre_producto', 'like', "%{$buscar}%");
        }

        if ($request->get('estado') === 'activo') {
            $query->where('estado', true);
        } elseif ($request->get('estado') === 'inactivo') {
            $query->where('estado', false);
        }

        $productos = $query->orderBy('nombre_producto')->get();

        return response()->json($productos->map(fn (Producto $p) => $this->formatProducto($p)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_producto' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string'],
            'precio' => ['required', 'numeric', 'min:0.01'],
            'stock' => ['required', 'integer', 'min:0'],
            'id_categoria' => ['required', 'exists:categorias,id'],
            'imagen_producto' => ['nullable', 'image', 'max:2048'],
            'estado' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('imagen_producto')) {
            $data['imagen_producto'] = $request->file('imagen_producto')->store('productos', 'images');
        }

        $data['estado'] = $request->boolean('estado', true);

        $producto = DB::transaction(function () use ($data, $request) {
            $producto = Producto::create($data);

            if ($producto->stock > 0) {
                MovimientoStock::create([
                    'id_producto' => $producto->id,
                    'tipo' => 'entrada',
                    'cantidad' => $producto->stock,
                    'id_usuario' => $request->user()->id,
                    'fecha' => now(),
                    'estado' => true,
                ]);
            }

            return $producto;
        });

        return response()->json($this->formatProducto($producto->load('categoria')), 201);
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'nombre_producto' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string'],
            'precio' => ['required', 'numeric', 'min:0.01'],
            'id_categoria' => ['required', 'exists:categorias,id'],
            'imagen_producto' => ['nullable', 'image', 'max:2048'],
            'estado' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('imagen_producto')) {
            $data['imagen_producto'] = $request->file('imagen_producto')->store('productos', 'images');
        } else {
            unset($data['imagen_producto']);
        }

        if ($request->has('estado')) {
            $data['estado'] = $request->boolean('estado');
        } else {
            unset($data['estado']);
        }

        $precioAnterior = (float) $producto->precio;
        $precioNuevo = (float) $data['precio'];

        DB::transaction(function () use ($producto, $data, $precioAnterior, $precioNuevo, $request) {
            $producto->update($data);

            if ($precioNuevo !== $precioAnterior) {
                HistorialPrecioProducto::create([
                    'id_producto' => $producto->id,
                    'precio_anterior' => $precioAnterior,
                    'precio_nuevo' => $precioNuevo,
                    'id_usuario' => $request->user()->id,
                    'fecha' => now(),
                ]);
            }
        });

        return response()->json($this->formatProducto($producto->fresh('categoria')));
    }

    public function destroy(Producto $producto)
    {
        $producto->update(['estado' => false]);

        return response()->json(['message' => 'Producto desactivado correctamente.']);
    }

    private function formatProducto(Producto $p): array
    {
        return [
            'id' => $p->id,
            'nombre' => $p->nombre_producto,
            'descripcion' => $p->descripcion,
            'precio' => (float) $p->precio,
            'stock' => $p->stock,
            'categoria' => $p->categoria?->nombre_categoria,
            'id_categoria' => $p->id_categoria,
            'estado' => (bool) $p->estado,
            'imagen' => Imagen::url($p->imagen_producto),
        ];
    }
}
