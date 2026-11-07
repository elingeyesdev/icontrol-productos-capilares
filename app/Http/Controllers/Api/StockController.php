<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoStock;
use App\Models\Producto;
use DomainException;
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

        try {
            $movimiento = DB::transaction(function () use ($data, $request) {
                $producto = Producto::lockForUpdate()->findOrFail($data['id_producto']);

                if ($data['tipo'] === 'salida' && $data['cantidad'] > $producto->stock) {
                    throw new DomainException("No puedes registrar una salida de {$data['cantidad']} unidades: \"{$producto->nombre_producto}\" solo tiene {$producto->stock} en stock.");
                }

                $movimiento = MovimientoStock::create([
                    'id_producto' => $producto->id,
                    'tipo' => $data['tipo'],
                    'cantidad' => $data['cantidad'],
                    'id_usuario' => $request->user()->id,
                    'fecha' => now(),
                    'estado' => true,
                ]);

                $producto->stock = $data['tipo'] === 'entrada'
                    ? $producto->stock + $data['cantidad']
                    : $producto->stock - $data['cantidad'];
                $producto->save();

                return $movimiento;
            });
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formato($movimiento->load(['producto', 'usuario'])), 201);
    }

    /** Igual que la web: si el stock quedaría negativo, no se elimina el movimiento. */
    public function destroy(MovimientoStock $movimiento)
    {
        try {
            DB::transaction(function () use ($movimiento) {
                $producto = Producto::lockForUpdate()->find($movimiento->id_producto);

                if ($producto) {
                    $nuevoStock = $movimiento->tipo === 'entrada'
                        ? $producto->stock - $movimiento->cantidad
                        : $producto->stock + $movimiento->cantidad;

                    if ($nuevoStock < 0) {
                        throw new DomainException("No se puede eliminar el movimiento: \"{$producto->nombre_producto}\" quedaría con stock negativo ({$nuevoStock}). Parte de esa entrada ya salió del inventario.");
                    }

                    $producto->stock = $nuevoStock;
                    $producto->save();
                }

                $movimiento->delete();
            });
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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
