<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\MovimientoStock;
use App\Models\Producto;
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

        return response()->json($query->orderByDesc('fecha')->orderByDesc('id')->get()->map(fn (Compra $c) => $this->formato($c)));
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

        DB::transaction(function () use ($data, $compra, $request) {

            $this->revertirStock($compra, $request->user()->id);
            $compra->detalles()->delete();

            $total = $this->registrarItems($compra, $data['items'], $request->user()->id);

            $compra->update(['id_proveedor' => $data['id_proveedor'], 'total' => $total]);
        });

        return response()->json($this->formato($compra->fresh(['proveedor', 'usuario', 'detalles.producto'])));
    }

    public function destroy(Request $request, Compra $compra)
    {
        if ($compra->situacion === 'cancelada') {
            return response()->json(['message' => 'Esta compra ya está cancelada.'], 422);
        }

        DB::transaction(function () use ($compra, $request) {
            $this->revertirStock($compra, $request->user()->id);
            $compra->update(['situacion' => 'cancelada', 'estado' => false]);
        });

        return response()->json(['message' => "Compra #{$compra->id} cancelada y stock revertido correctamente."]);
    }

    public function eliminar(Request $request, Compra $compra)
    {
        if ($compra->situacion === 'eliminada') {
            return response()->json(['message' => 'Esta compra ya fue eliminada.'], 422);
        }

        DB::transaction(function () use ($compra, $request) {
            if ($compra->situacion === 'completada') {
                $this->revertirStock($compra, $request->user()->id);
            }

            $compra->update(['situacion' => 'eliminada', 'estado' => false]);
        });

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
            $producto = Producto::findOrFail($item['id_producto']);
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

    private function revertirStock(Compra $compra, int $idUsuario): void
    {
        foreach ($compra->detalles as $detalle) {
            $producto = $detalle->producto;

            if (! $producto) {
                continue;
            }

            $nuevoStock = max(0, $producto->stock - $detalle->cantidad);
            $revertido = $producto->stock - $nuevoStock;
            $producto->update(['stock' => $nuevoStock]);

            if ($revertido > 0) {
                MovimientoStock::create([
                    'id_producto' => $producto->id,
                    'tipo' => 'salida',
                    'cantidad' => $revertido,
                    'id_usuario' => $idUsuario,
                    'fecha' => now(),
                    'estado' => true,
                ]);
            }
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
