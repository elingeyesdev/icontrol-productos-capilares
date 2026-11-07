<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleVenta;
use App\Models\DetallePedido;
use App\Models\MovimientoStock;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Notifications\PedidoAceptado;
use App\Support\Imagen;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /** Etiquetas iguales a las que muestra la web (Pedido::detalleVista). */
    private const SITUACIONES = [
        'pendiente' => 'En espera',
        'confirmado' => 'Confirmado',
        'entregado' => 'Aceptado',
        'cancelado' => 'Cancelado',
    ];

    // ─────────────────────────────────────────────────────────────────────
    //  CLIENTE (sus propios pedidos)
    // ─────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $pedidos = Pedido::with('detalles.producto')
            ->where('id_cliente', $request->user()->id)
            ->where('situacion', '!=', 'eliminado')
            ->orderByDesc('fecha')
            ->get();

        return response()->json($pedidos->map(fn (Pedido $p) => $this->formatPedido($p)));
    }

    public function show(Request $request, Pedido $pedido)
    {
        if ($pedido->id_cliente !== $request->user()->id || $pedido->situacion === 'eliminado') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $pedido->load('detalles.producto');

        return response()->json($this->formatPedido($pedido));
    }

    /** Última dirección y teléfono usados, para rellenar el checkout (como el carrito web). */
    public function ultimaDireccion(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'direccion_entrega' => $user->ultimaDireccionEntrega() ?: null,
            'telefono' => $user->telefono,
        ]);
    }

    /** Checkout: mismas reglas que MainController::pedidoCheckout. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id_producto' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'direccion_entrega' => ['required', 'string', 'max:255'],
            'nota' => ['nullable', 'string', 'max:500'],
        ], [
            'telefono.required' => 'Ingresa un teléfono de contacto.',
            'telefono.regex' => 'Ingresa un teléfono válido (solo números, +, espacios o guiones).',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
            'direccion_entrega.required' => 'Ingresa la dirección de entrega.',
            'direccion_entrega.max' => 'La dirección no puede superar los 255 caracteres.',
            'nota.max' => 'La nota no puede superar los 500 caracteres.',
        ]);

        try {
            $pedido = DB::transaction(function () use ($data, $request) {
                $request->user()->update(['telefono' => $data['telefono']]);

                $pedido = Pedido::create([
                    'id_cliente' => $request->user()->id,
                    'fecha' => now(),
                    'total' => 0,
                    'situacion' => 'pendiente',
                    'direccion_entrega' => $data['direccion_entrega'],
                    'nota' => $data['nota'] ?? null,
                    'estado' => true,
                ]);

                $pedido->update(['total' => $this->reservarItems($pedido, $data['items'], $request->user()->id)]);

                return $pedido;
            }, 3);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pedido->load('detalles.producto');

        return response()->json($this->formatPedido($pedido), 201);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PANEL (administrador / vendedor)
    // ─────────────────────────────────────────────────────────────────────

    public function indexTodos(Request $request)
    {
        $query = Pedido::with(['detalles.producto', 'cliente'])
            ->where('situacion', '!=', 'eliminado');

        if ($buscar = $request->get('buscar')) {
            $query->whereHas('cliente', fn ($q) => $q->where('name', 'like', "%{$buscar}%"));
        }

        if ($situacion = $request->get('situacion')) {
            $query->where('situacion', $situacion);
        }

        $pedidos = $query->orderByDesc('fecha')->orderByDesc('id')->get();

        return response()->json($pedidos->map(fn (Pedido $p) => $this->formatPedido($p, true)));
    }

    public function showTodos(Pedido $pedido)
    {
        if ($pedido->situacion === 'eliminado') {
            return response()->json(['message' => 'Este pedido fue eliminado.'], 404);
        }

        $pedido->load(['detalles.producto', 'cliente']);

        return response()->json($this->formatPedido($pedido, true));
    }

    /** Pedido creado por el personal para un cliente (MainController::pedidoStore). */
    public function storeTodos(Request $request)
    {
        $data = $this->validarPedidoPanel($request);

        try {
            $pedido = DB::transaction(function () use ($data, $request) {
                $pedido = Pedido::create([
                    'id_cliente' => $data['id_cliente'],
                    'fecha' => now(),
                    'total' => 0,
                    'situacion' => 'pendiente',
                    'estado' => true,
                ]);

                $pedido->update(['total' => $this->reservarItems($pedido, $data['items'], $request->user()->id)]);

                return $pedido;
            }, 3);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatPedido($pedido->load(['detalles.producto', 'cliente']), true), 201);
    }

    /** Solo pedidos pendientes o confirmados (MainController::pedidoUpdate). */
    public function updateTodos(Request $request, Pedido $pedido)
    {
        if (! in_array($pedido->situacion, ['pendiente', 'confirmado'])) {
            return response()->json(['message' => 'Solo se pueden modificar pedidos pendientes o confirmados.'], 422);
        }

        $data = $this->validarPedidoPanel($request);

        try {
            DB::transaction(function () use ($data, $pedido, $request) {
                $this->devolverStock($pedido, $request->user()->id);
                $pedido->detalles()->delete();

                $total = $this->reservarItems($pedido, $data['items'], $request->user()->id);

                $pedido->update(['id_cliente' => $data['id_cliente'], 'total' => $total]);
            }, 3);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatPedido($pedido->fresh(['detalles.producto', 'cliente']), true));
    }

    /**
     * Cambia la situación: confirmado | entregado | cancelado.
     * "entregado" exige tipo_pago, genera la Venta y avisa al cliente.
     */
    public function updateEstado(Request $request, Pedido $pedido)
    {
        $data = $request->validate([
            'situacion' => ['required', 'in:confirmado,entregado,cancelado'],
            'tipo_pago' => ['nullable', 'string'],
        ]);

        if (in_array($pedido->situacion, ['entregado', 'cancelado', 'eliminado'])) {
            return response()->json(['message' => 'Este pedido ya no se puede modificar.'], 422);
        }

        if ($data['situacion'] === 'entregado') {
            $tipoPago = $data['tipo_pago'] ?? null;

            if (! array_key_exists((string) $tipoPago, Venta::TIPOS_PAGO)) {
                return response()->json([
                    'message' => 'Selecciona el tipo de pago para confirmar el pedido.',
                    'tipos_pago' => Venta::TIPOS_PAGO,
                ], 422);
            }

            try {
                $venta = DB::transaction(fn () => $this->convertirEnVenta($pedido, $tipoPago, $request->user()->id));
            } catch (DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            $this->notificarAceptado($pedido, $venta);

            $pedido->refresh()->load(['detalles.producto', 'cliente']);

            return response()->json($this->formatPedido($pedido, true) + [
                'message' => "Pedido #{$pedido->id} marcado como entregado y registrado en Ventas.",
            ]);
        }

        if ($data['situacion'] === 'cancelado') {
            DB::transaction(function () use ($pedido, $request) {
                $this->devolverStock($pedido, $request->user()->id);
                $pedido->update(['situacion' => 'cancelado']);
            });

            $pedido->refresh()->load(['detalles.producto', 'cliente']);

            return response()->json($this->formatPedido($pedido, true) + [
                'message' => "Pedido #{$pedido->id} cancelado y stock restaurado correctamente.",
            ]);
        }

        $pedido->update(['situacion' => 'confirmado']);

        $pedido->load(['detalles.producto', 'cliente']);

        return response()->json($this->formatPedido($pedido, true) + [
            'message' => "Pedido #{$pedido->id} marcado como \"confirmado\".",
        ]);
    }

    /** Eliminación lógica; si seguía reservando stock, lo devuelve. */
    public function destroyTodos(Request $request, Pedido $pedido)
    {
        if ($pedido->situacion === 'eliminado') {
            return response()->json(['message' => 'Este pedido ya fue eliminado.'], 422);
        }

        DB::transaction(function () use ($pedido, $request) {
            if (in_array($pedido->situacion, ['pendiente', 'confirmado'])) {
                $this->devolverStock($pedido, $request->user()->id);
            }

            $pedido->update(['situacion' => 'eliminado', 'estado' => false]);
        });

        return response()->json(['message' => "Pedido #{$pedido->id} eliminado correctamente."]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function validarPedidoPanel(Request $request): array
    {
        return $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id_producto' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);
    }

    /**
     * Crea los detalles, descuenta stock y registra las salidas.
     * Agrupa productos repetidos y bloquea filas en orden de id (evita deadlocks).
     * Devuelve el total del pedido.
     */
    private function reservarItems(Pedido $pedido, array $items, int $idUsuario): float
    {
        $items = collect($items)
            ->groupBy('id_producto')
            ->map(fn ($grupo, $id) => [
                'id_producto' => (int) $id,
                'cantidad' => (int) $grupo->sum('cantidad'),
            ])
            ->sortBy('id_producto')
            ->values();

        $productos = Producto::whereIn('id', $items->pluck('id_producto'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $total = 0;

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
                'id_usuario' => $idUsuario,
                'fecha' => now(),
                'estado' => true,
            ]);
        }

        return $total;
    }

    /** Devuelve al inventario lo que el pedido tenía reservado. */
    private function devolverStock(Pedido $pedido, int $idUsuario): void
    {
        foreach ($pedido->detalles()->with('producto')->get() as $detalle) {
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
                'estado' => true,
            ]);
        }
    }

    /** El stock ya se descontó al crear el pedido, así que la venta no lo toca de nuevo. */
    private function convertirEnVenta(Pedido $pedido, string $tipoPago, int $idVendedor): Venta
    {
        $venta = Venta::create([
            'id_cliente' => $pedido->id_cliente,
            'id_vendedor' => $idVendedor,
            'fecha' => now(),
            'total' => 0,
            'tipo_pago' => $tipoPago,
            'situacion' => 'completada',
            'estado' => true,
        ]);

        $total = 0;

        foreach ($pedido->detalles()->with('producto')->get() as $detalle) {
            if (! $detalle->producto) {
                throw new DomainException('Uno de los productos del pedido ya no existe.');
            }

            $subtotal = round($detalle->precio_unitario * $detalle->cantidad, 2);
            $total += $subtotal;

            DetalleVenta::create([
                'id_venta' => $venta->id,
                'id_producto' => $detalle->producto->id,
                'cantidad' => $detalle->cantidad,
                'precio_unitario' => $detalle->precio_unitario,
                'subtotal' => $subtotal,
            ]);
        }

        $venta->update(['total' => $total]);
        $pedido->update(['situacion' => 'entregado', 'id_venta' => $venta->id]);

        return $venta;
    }

    private function notificarAceptado(Pedido $pedido, Venta $venta): void
    {
        try {
            $pedido->cliente?->notify(new PedidoAceptado($pedido, $venta));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function formatPedido(Pedido $pedido, bool $conCliente = false): array
    {
        $data = [
            'id' => $pedido->id,
            'fecha' => $pedido->fecha->format('Y-m-d H:i:s'),
            'situacion' => $pedido->situacion,
            'situacion_texto' => self::SITUACIONES[$pedido->situacion] ?? ucfirst((string) $pedido->situacion),
            'total' => (float) $pedido->total,
            'direccion_entrega' => $pedido->direccion_entrega,
            'nota' => $pedido->nota,
            'venta_id' => $pedido->id_venta,
            'items' => $pedido->detalles->map(fn ($d) => [
                'id_producto' => $d->id_producto,
                'producto' => $d->producto->nombre_producto ?? 'Producto eliminado',
                'imagen' => Imagen::url($d->producto?->imagen_producto),
                'cantidad' => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ])->values(),
        ];

        if ($conCliente) {
            $data['id_cliente'] = $pedido->id_cliente;
            $data['cliente'] = $pedido->cliente->name ?? '—';
            $data['telefono'] = $pedido->cliente->telefono ?? null;
        }

        return $data;
    }
}
