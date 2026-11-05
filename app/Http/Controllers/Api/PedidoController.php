<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetallePedido;
use App\Models\MovimientoStock;
use App\Models\Pedido;
use App\Models\Producto;
use App\Support\Imagen;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{

    public function index(Request $request)
    {
        $pedidos = Pedido::with('detalles.producto')
            ->where('id_cliente', $request->user()->id)
            ->where('situacion', '!=', 'eliminado')
            ->orderByDesc('fecha')
            ->get();

        return response()->json($pedidos->map(fn (Pedido $p) => $this->formatPedido($p)));
    }

    public function indexTodos()
    {
        $pedidos = Pedido::with(['detalles.producto', 'cliente'])
            ->where('situacion', '!=', 'eliminado')
            ->orderByDesc('fecha')
            ->get();

        return response()->json($pedidos->map(function (Pedido $p) {
            $data = $this->formatPedido($p);
            $data['cliente'] = $p->cliente->name ?? '—';

            return $data;
        }));
    }

    public function show(Request $request, Pedido $pedido)
    {
        if ($pedido->id_cliente !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $pedido->load('detalles.producto');

        return response()->json($this->formatPedido($pedido));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id_producto' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
            'direccion_entrega' => ['nullable', 'string', 'max:255'],
            'nota' => ['nullable', 'string', 'max:500'],
        ]);

        $items = collect($data['items'])
            ->groupBy('id_producto')
            ->map(fn ($grupo, $id) => [
                'id_producto' => (int) $id,
                'cantidad' => (int) $grupo->sum('cantidad'),
            ])
            ->sortBy('id_producto')
            ->values()
            ->all();

        try {
            $pedido = DB::transaction(function () use ($items, $data, $request) {
                $productos = Producto::whereIn('id', array_column($items, 'id_producto'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $producto = $productos->get($item['id_producto']);

                    if (! $producto || ! $producto->estado) {
                        throw new DomainException('Uno de los productos ya no está disponible.');
                    }

                    if ($item['cantidad'] > $producto->stock) {
                        throw new DomainException(sprintf(
                            '"%s" no tiene stock suficiente (disponible: %d, pedido: %d).',
                            $producto->nombre_producto,
                            $producto->stock,
                            $item['cantidad']
                        ));
                    }
                }

                $pedido = Pedido::create([
                    'id_cliente' => $request->user()->id,
                    'fecha' => now(),
                    'total' => 0,
                    'situacion' => 'pendiente',
                    'direccion_entrega' => $data['direccion_entrega'] ?? null,
                    'nota' => $data['nota'] ?? null,
                    'estado' => true,
                ]);

                $total = 0;

                foreach ($items as $item) {
                    $producto = $productos->get($item['id_producto']);
                    $subtotal = round($producto->precio * $item['cantidad'], 2);
                    $total += $subtotal;

                    DetallePedido::create([
                        'id_pedido' => $pedido->id,
                        'id_producto' => $producto->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $producto->precio,
                        'subtotal' => $subtotal,
                    ]);

                    $producto->decrement('stock', $item['cantidad']);

                    MovimientoStock::create([
                        'id_producto' => $producto->id,
                        'tipo' => 'salida',
                        'cantidad' => $item['cantidad'],
                        'id_usuario' => $request->user()->id,
                        'fecha' => now(),
                        'estado' => true,
                    ]);
                }

                $pedido->update(['total' => $total]);

                return $pedido;
            }, 3);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pedido->load('detalles.producto');

        return response()->json($this->formatPedido($pedido), 201);
    }

    private function formatPedido(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'fecha' => $pedido->fecha->format('Y-m-d H:i:s'),
            'situacion' => $pedido->situacion,
            'total' => (float) $pedido->total,
            'items' => $pedido->detalles->map(fn ($d) => [
                'producto' => $d->producto->nombre_producto ?? 'Producto eliminado',
                'imagen' => Imagen::url($d->producto?->imagen_producto),
                'cantidad' => $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ]),
        ];
    }
}
