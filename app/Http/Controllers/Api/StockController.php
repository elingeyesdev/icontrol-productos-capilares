<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{

    public function index(Request $request)
    {
        $query = MovimientoStock::with(['producto', 'usuario']);

        if ($buscar = $request->get('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('tipo', 'like', "%{$buscar}%")
                  ->orWhereHas('producto', fn ($p) => $p->where('nombre_producto', 'like', "%{$buscar}%"));
            });
        }

        if ($tipo = $request->get('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($idProducto = $request->get('id_producto')) {
            $query->where('id_producto', $idProducto);
        }

        if ($desde = $request->get('desde')) {
            $query->whereDate('fecha', '>=', $desde);
        }

        if ($hasta = $request->get('hasta')) {
            $query->whereDate('fecha', '<=', $hasta);
        }

        $movimientos = $query->orderByDesc('fecha')->orderByDesc('id')->get();

        return response()->json($movimientos->map(fn (MovimientoStock $m) => $this->formato($m)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_producto' => ['required', 'exists:productos,id'],
            'tipo' => ['required', 'in:entrada,salida'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $movimiento = DB::transaction(function () use ($data, $request) {
            $producto = Producto::lockForUpdate()->findOrFail($data['id_producto']);

            if ($data['tipo'] === 'salida' && $data['cantidad'] > $producto->stock) {
                return response()->json([
                    'message' => "No puedes registrar una salida de {$data['cantidad']} unidades: \"{$producto->nombre_producto}\" solo tiene {$producto->stock} en stock.",
                ], 422);
            }

            $movimiento = MovimientoStock::create([
                'id_producto' => $producto->id,
                'tipo' => $data['tipo'],
                'cantidad' => $data['cantidad'],
                'id_usuario' => $request->user()->id,
                'fecha' => now(),
                'activo' => true,
            ]);

            $producto->stock = $data['tipo'] === 'entrada'
                ? $producto->stock + $data['cantidad']
                : $producto->stock - $data['cantidad'];
            $producto->save();

            return $movimiento;
        });

        if ($movimiento instanceof \Illuminate\Http\JsonResponse) {
            return $movimiento;
        }

        return response()->json($this->formato($movimiento->load(['producto', 'usuario'])), 201);
    }

    public function destroy(MovimientoStock $movimiento)
    {
        DB::transaction(function () use ($movimiento) {
            if ($producto = $movimiento->producto) {
                $producto->stock = max(0, $movimiento->tipo === 'entrada'
                    ? $producto->stock - $movimiento->cantidad
                    : $producto->stock + $movimiento->cantidad);
                $producto->save();
            }

            $movimiento->delete();
        });

        return response()->json(['message' => 'Movimiento eliminado y stock revertido correctamente.']);
    }

    private function formato(MovimientoStock $m): array
    {
        return [
            'id' => $m->id,
            'id_producto' => $m->id_producto,
            'producto' => $m->producto->nombre_producto ?? 'Producto eliminado',
            'tipo' => $m->tipo,
            'cantidad' => (int) $m->cantidad,
            'usuario' => $m->usuario->name ?? '—',
            'fecha' => $m->fecha->format('Y-m-d H:i:s'),
        ];
    }
}
