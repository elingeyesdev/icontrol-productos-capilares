<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Proveedor;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'usuario', 'detalles.producto'])
            ->where('situacion', '!=', 'eliminada');

        if ($buscar = $request->get('buscar')) {
            $query->whereHas('proveedor', fn ($q) => $q->where('nombre_empresa', 'like', "%{$buscar}%"));
        }

        if ($situacion = $request->get('situacion')) {
            $query->where('situacion', $situacion);
        }

        return response()->json($query->orderByDesc('fecha')->orderByDesc('id')->get()->map(fn (Compra $c) => $this->formato($c)));
    }

    public function show(Compra $compra)
    {
        if ($compra->situacion === 'eliminada') {
            return response()->json(['message' => 'Esta compra fue eliminada.'], 404);
        }

        return response()->json($this->formato($compra->load(['proveedor', 'usuario', 'detalles.producto'])));
    }

    /**
     * Proveedores activos para el selector de la compra. Se sirve bajo ver-compras
     * porque el rol "proveedor" puede registrar compras sin tener ver-proveedores.
     */
    public function proveedores()
    {
        return response()->json(
            Proveedor::where('estado', true)->orderBy('nombre_empresa')->get(['id', 'nombre_empresa'])
        );
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        $compra = DB::transaction(function () use ($data, $request) {
            $compra = Compra::create([
                'id_proveedor' => $data['id_proveedor'],
                'id_usuario' => $request->user()->id,
                'fecha' => now(),
                'total' => 0,
                'situacion' => 'completada',
                'estado' => true,
            ]);

            $compra->update(['total' => $this->registrarItems($compra, $data['items'], $request->user()->id)]);

            return $compra;
        });

        return response()->json($this->formato($compra->load(['proveedor', 'usuario', 'detalles.producto'])), 201);
    }

    public function update(Request $request, Compra $compra)
    {
        if ($compra->situacion !== 'completada') {
            return response()->json(['message' => 'Solo se pueden modificar compras completadas.'], 422);
        }

        $data = $this->validar($request);

        try {
            DB::transaction(function () use ($data, $compra, $request) {
                $this->revertirStock($compra, $request->user()->id);
                $compra->detalles()->delete();

                $total = $this->registrarItems($compra, $data['items'], $request->user()->id);

                $compra->update(['id_proveedor' => $data['id_proveedor'], 'total' => $total]);
            });
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formato($compra->fresh(['proveedor', 'usuario', 'detalles.producto'])));
    }

    public function destroy(Request $request, Compra $compra)
    {
        if ($compra->situacion === 'cancelada') {
            return response()->json(['message' => 'Esta compra ya está cancelada.'], 422);
        }

        try {
            DB::transaction(function () use ($compra, $request) {
                $this->revertirStock($compra, $request->user()->id);
                $compra->update(['situacion' => 'cancelada', 'estado' => false]);
            });
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => "Compra #{$compra->id} cancelada y stock revertido correctamente."]);
    }

    public function eliminar(Request $request, Compra $compra)
    {
        if ($compra->situacion === 'eliminada') {
            return response()->json(['message' => 'Esta compra ya fue eliminada.'], 422);
        }

        try {
            DB::transaction(function () use ($compra, $request) {
                if ($compra->situacion === 'completada') {
                    $this->revertirStock($compra, $request->user()->id);
                }

                $compra->update(['situacion' => 'eliminada', 'estado' => false]);
            });
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => "Compra #{$compra->id} eliminada correctamente."]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0.01'],
        ]);
    }

    private function registrarItems(Compra $compra, array $items, int $idUsuario): float
    {
        $total = 0;

        foreach ($items as $item) {
            $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);
            $subtotal = round($item['precio_unitario'] * $item['cantidad'], 2);
            $total += $subtotal;

            DetalleCompra::create([
                'id_compra' => $compra->id,
                'id_producto' => $producto->id,
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'subtotal' => $subtotal,
            ]);

            MovimientoStock::create([
                'id_producto' => $producto->id,
                'tipo' => 'entrada',
                'cantidad' => $item['cantidad'],
                'id_usuario' => $idUsuario,
                'fecha' => now(),
                'estado' => true,
            ]);

            $producto->increment('stock', $item['cantidad']);
        }

        return $total;
    }

    /**
     * Revierte el stock que aportó la compra. Igual que la web: si parte de ese stock
     * ya salió (vendido, etc.) falla en lugar de dejar el inventario inconsistente.
     */
    private function revertirStock(Compra $compra, int $idUsuario): void
    {
        $porProducto = [];

        foreach ($compra->detalles()->get() as $detalle) {
            $producto = Producto::lockForUpdate()->find($detalle->id_producto);

            if (! $producto) {
                continue;
            }

            $porProducto[$producto->id] ??= ['producto' => $producto, 'cantidad' => 0];
            $porProducto[$producto->id]['cantidad'] += $detalle->cantidad;
        }

        foreach ($porProducto as $fila) {
            if ($fila['producto']->stock < $fila['cantidad']) {
                throw new DomainException(
                    "No se puede revertir la compra #{$compra->id}: \"{$fila['producto']->nombre_producto}\" tiene {$fila['producto']->stock} en stock y la compra aportó {$fila['cantidad']} (parte ya salió del inventario)."
                );
            }
        }

        foreach ($porProducto as $fila) {
            $producto = $fila['producto'];
            $producto->decrement('stock', $fila['cantidad']);

            MovimientoStock::create([
                'id_producto' => $producto->id,
                'tipo' => 'salida',
                'cantidad' => $fila['cantidad'],
                'id_usuario' => $idUsuario,
                'fecha' => now(),
                'estado' => true,
            ]);
        }
    }

    private function formato(Compra $c): array
    {
        return [
            'id' => $c->id,
            'fecha' => $c->fecha->format('Y-m-d H:i:s'),
            'situacion' => $c->situacion,
            'total' => (float) $c->total,
            'id_proveedor' => $c->id_proveedor,
            'proveedor' => $c->proveedor->nombre_empresa ?? '—',
            'usuario' => $c->usuario->name ?? '—',
            'items' => $c->detalles->map(fn ($d) => [
                'id_producto' => $d->id_producto,
                'producto' => $d->producto->nombre_producto ?? 'Producto eliminado',
                'cantidad' => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ])->values(),
        ];
    }
}
