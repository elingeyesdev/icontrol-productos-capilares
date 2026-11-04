<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleVenta;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'vendedor', 'detalles.producto', 'pedido'])
            ->where('estado', '!=', 'eliminada');

        if ($buscar = $request->get('buscar')) {
            $query->whereHas('cliente', fn ($q) => $q->where('name', 'like', "%{$buscar}%"));
        }

        return response()->json($query->orderByDesc('fecha')->orderByDesc('id')->get()->map(fn (Venta $v) => $this->formato($v)));
    }

    public function clientes()
    {
        $clientes = User::whereHas('roles', fn ($q) => $q->where('name', 'cliente'))
            ->where('activo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($clientes);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        try {
            $venta = DB::transaction(function () use ($data, $request) {
                $venta = Venta::create([
                    'id_cliente' => $data['id_cliente'],
                    'id_vendedor' => $request->user()->id,
                    'fecha' => now(),
                    'total' => 0,
                    'estado' => 'completada',
                    'activo' => true,
                ]);

                $venta->update(['total' => $this->registrarItems($venta, $data['items'], $request->user()->id)]);

                return $venta;
            });
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formato($venta->load(['cliente', 'vendedor', 'detalles.producto', 'pedido'])), 201);
    }

    public function update(Request $request, Venta $venta)
    {
        if ($venta->estado !== 'completada') {
            return response()->json(['message' => 'Solo se pueden modificar ventas completadas.'], 422);
        }

        if (! $venta->esManual()) {
            return response()->json(['message' => 'Esta venta proviene de un pedido entregado y no se puede modificar manualmente.'], 422);
        }

        $data = $this->validar($request);

        try {
            DB::transaction(function () use ($data, $venta, $request) {

                $this->devolverStock($venta, $request->user()->id);
                $venta->detalles()->delete();

                $total = $this->registrarItems($venta, $data['items'], $request->user()->id);

                $venta->update(['id_cliente' => $data['id_cliente'], 'total' => $total]);
            });
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formato($venta->fresh(['cliente', 'vendedor', 'detalles.producto', 'pedido'])));
    }

    public function destroy(Request $request, Venta $venta)
    {
        if ($venta->estado === 'cancelada') {
            return response()->json(['message' => 'Esta venta ya está cancelada.'], 422);
        }

        DB::transaction(function () use ($venta, $request) {
            $this->devolverStock($venta, $request->user()->id);
            $venta->update(['estado' => 'cancelada', 'activo' => false]);
        });

        return response()->json(['message' => "Venta #{$venta->id} cancelada y stock revertido correctamente."]);
    }

    public function eliminar(Request $request, Venta $venta)
    {
        if ($venta->estado === 'eliminada') {
            return response()->json(['message' => 'Esta venta ya fue eliminada.'], 422);
        }

        DB::transaction(function () use ($venta, $request) {
            if ($venta->estado === 'completada') {
                $this->devolverStock($venta, $request->user()->id);
            }

            $venta->update(['estado' => 'eliminada', 'activo' => false]);
        });

        return response()->json(['message' => "Venta #{$venta->id} eliminada correctamente."]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);
    }

    private function registrarItems(Venta $venta, array $items, int $idUsuario): float
    {
        $total = 0;

        foreach ($items as $item) {
            $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);

            if ($item['cantidad'] > $producto->stock) {
                throw new DomainException("No hay suficiente stock de \"{$producto->nombre_producto}\": disponible {$producto->stock}, solicitado {$item['cantidad']}.");
            }

            $subtotal = round($producto->precio * $item['cantidad'], 2);
            $total += $subtotal;

            DetalleVenta::create([
                'id_venta' => $venta->id,
                'id_producto' => $producto->id,
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $producto->precio,
                'subtotal' => $subtotal,
            ]);

            MovimientoStock::create([
                'id_producto' => $producto->id,
                'tipo' => 'salida',
                'cantidad' => $item['cantidad'],
                'id_usuario' => $idUsuario,
                'fecha' => now(),
                'activo' => true,
            ]);

            $producto->decrement('stock', $item['cantidad']);
        }

        return $total;
    }

    private function devolverStock(Venta $venta, int $idUsuario): void
    {
        foreach ($venta->detalles as $detalle) {
            $producto = $detalle->producto;

            if (! $producto) {
                continue;
            }

            $producto->increment('stock', $detalle->cantidad);

            MovimientoStock::create([
                'id_producto' => $producto->id,
                'tipo' => 'entrada',
                'cantidad' => $detalle->cantidad,
                'id_usuario' => $idUsuario,
                'fecha' => now(),
                'activo' => true,
            ]);
        }
    }

    private function formato(Venta $v): array
    {
        return [
            'id' => $v->id,
            'fecha' => $v->fecha->format('Y-m-d H:i:s'),
            'estado' => $v->estado,
            'total' => (float) $v->total,
            'id_cliente' => $v->id_cliente,
            'cliente' => $v->cliente->name ?? '—',
            'vendedor' => $v->vendedor->name ?? '—',
            'manual' => $v->pedido === null,
            'items' => $v->detalles->map(fn ($d) => [
                'id_producto' => $d->id_producto,
                'producto' => $d->producto->nombre_producto ?? 'Producto eliminado',
                'cantidad' => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ])->values(),
        ];
    }
}
