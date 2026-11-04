<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_producto' => ['required', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $producto = Producto::findOrFail($data['id_producto']);

        if (!$producto->activo || $producto->stock < 1) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Este producto no está disponible.'], 422);
            }

            return back()->with('error', 'Este producto no está disponible.');
        }

        $carrito = session('carrito', []);
        $clave = (string) $producto->id;
        $cantidadActual = $carrito[$clave]['cantidad'] ?? 0;

        $carrito[$clave] = [
            'id' => $producto->id,
            'nombre' => $producto->nombre_producto,
            'precio' => (float) $producto->precio,
            'imagen' => $producto->imagen_producto,
            'cantidad' => min($producto->stock, $cantidadActual + $data['cantidad']),
        ];

        session(['carrito' => $carrito]);

        if ($request->wantsJson()) {
            return $this->jsonCarrito($carrito, "\"{$producto->nombre_producto}\" agregado al carrito.");
        }

        return back()->with('status', "\"{$producto->nombre_producto}\" agregado al carrito.");
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $carrito = session('carrito', []);
        $clave = (string) $producto->id;

        if (isset($carrito[$clave])) {
            $carrito[$clave]['cantidad'] = min($producto->stock, $data['cantidad']);
            session(['carrito' => $carrito]);
        }

        if ($request->wantsJson()) {
            return $this->jsonCarrito($carrito, 'Carrito actualizado.');
        }

        return back()->with('status', 'Carrito actualizado.');
    }

    public function destroy(Request $request, Producto $producto)
    {
        $carrito = session('carrito', []);
        unset($carrito[(string) $producto->id]);
        session(['carrito' => $carrito]);

        if ($request->wantsJson()) {
            return $this->jsonCarrito($carrito, 'Producto quitado del carrito.');
        }

        return back()->with('status', 'Producto quitado del carrito.');
    }

    private function jsonCarrito(array $carrito, string $mensaje)
    {
        return response()->json([
            'message' => $mensaje,
            'carrito' => array_values($carrito),
        ]);
    }
}
