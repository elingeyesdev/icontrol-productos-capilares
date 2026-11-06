<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Sortable;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetallePedido;
use App\Models\DetalleVenta;
use App\Models\HistorialPrecioProducto;
use App\Models\MovimientoStock;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MainController extends Controller
{
    use Sortable;

    public function carritoStore(Request $request)
    {
        $data = $request->validate([
            'id_producto' => ['required', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $producto = Producto::findOrFail($data['id_producto']);

        if (!$producto->estado || $producto->stock < 1) {
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
            'stock' => $producto->stock,
            'cantidad' => min($producto->stock, $cantidadActual + $data['cantidad']),
        ];

        session(['carrito' => $carrito]);

        if ($request->wantsJson()) {
            return $this->carritoJsonRespuesta($carrito, "\"{$producto->nombre_producto}\" agregado al carrito.");
        }

        return back()->with('status', "\"{$producto->nombre_producto}\" agregado al carrito.");
    }

    public function carritoUpdate(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $carrito = session('carrito', []);
        $clave = (string) $producto->id;

        if (isset($carrito[$clave])) {
            $carrito[$clave]['cantidad'] = min($producto->stock, $data['cantidad']);
            $carrito[$clave]['stock'] = $producto->stock;
            session(['carrito' => $carrito]);
        }

        if ($request->wantsJson()) {
            return $this->carritoJsonRespuesta($carrito, 'Carrito actualizado.');
        }

        return back()->with('status', 'Carrito actualizado.');
    }

    public function carritoDestroy(Request $request, Producto $producto)
    {
        $carrito = session('carrito', []);
        unset($carrito[(string) $producto->id]);
        session(['carrito' => $carrito]);

        if ($request->wantsJson()) {
            return $this->carritoJsonRespuesta($carrito, 'Producto quitado del carrito.');
        }

        return back()->with('status', 'Producto quitado del carrito.');
    }

    private function carritoJsonRespuesta(array $carrito, string $mensaje)
    {
        return response()->json([
            'message' => $mensaje,
            'carrito' => array_values($carrito),
        ]);
    }

    public function categoriaIndex(Request $request)
    {
        $query = Categoria::withCount('productos');

        if ($buscar = $request->get('buscar')) {
            $query->where('nombre_categoria', 'like', "%{$buscar}%");
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'id',
            'nombre' => 'nombre_categoria',
            'productos' => 'productos_count',
            'estado' => 'estado',
        ], 'id', 'desc');

        $categorias = $query->get();

        return view('categorias.index', compact('categorias', 'sort', 'dir'));
    }

    public function categoriaStore(Request $request)
    {
        $data = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:100', 'unique:categorias,nombre_categoria'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', 'boolean'],
        ]);

        Categoria::create([
            'nombre_categoria' => $data['nombre_categoria'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $request->boolean('estado', true),
        ]);

        return back()->with('status', 'Categoría creada correctamente.');
    }

    public function categoriaUpdate(Request $request, Categoria $categoria)
    {
        $data = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:100', 'unique:categorias,nombre_categoria,' . $categoria->id],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', 'boolean'],
        ]);

        $categoria->update([
            'nombre_categoria' => $data['nombre_categoria'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => $request->boolean('estado'),
        ]);

        return back()->with('status', 'Categoría actualizada correctamente.');
    }

    public function categoriaDestroy(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) {
            return back()->with('error', "No puedes eliminar \"{$categoria->nombre_categoria}\" porque tiene productos asociados. Reasigna o elimina esos productos primero.");
        }

        $categoria->delete();

        return back()->with('status', 'Categoría eliminada correctamente.');
    }

    public function compraIndex(Request $request)
    {
        $query = Compra::with(['proveedor', 'usuario', 'detalles.producto'])
            ->where('compras.situacion', '!=', 'eliminada');

        if ($buscar = $request->get('buscar')) {
            $query->whereHas('proveedor', fn ($q) => $q->where('nombre_empresa', 'like', "%{$buscar}%"));
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'compras.id',
            'proveedor' => function ($q, $dir) {
                $q->join('proveedores as prov_sort', 'compras.id_proveedor', '=', 'prov_sort.id')
                  ->orderBy('prov_sort.nombre_empresa', $dir)
                  ->select('compras.*');
            },
            'usuario' => function ($q, $dir) {
                $q->join('users as reg_sort', 'compras.id_usuario', '=', 'reg_sort.id')
                  ->orderBy('reg_sort.name', $dir)
                  ->select('compras.*');
            },
            'total' => 'compras.total',
            'fecha' => 'compras.fecha',
            'situacion' => 'compras.situacion',
        ], 'fecha', 'desc');

        $compras = $query->get();
        $proveedores = Proveedor::where('estado', true)->orderBy('nombre_empresa')->get();
        $productos = Producto::where('estado', true)->orderBy('nombre_producto')->get();

        return view('compras.index', compact('compras', 'proveedores', 'productos', 'sort', 'dir'));
    }

    public function compraStore(Request $request)
    {
        $data = $request->validate([
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0.01'],
        ]);

        $compra = DB::transaction(function () use ($data) {
            $compra = Compra::create([
                'id_proveedor' => $data['id_proveedor'],
                'id_usuario' => auth()->id(),
                'fecha' => now(),
                'total' => 0,
                'situacion' => 'completada',
                'estado' => true,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);
                $subtotal = $item['precio_unitario'] * $item['cantidad'];
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
                    'id_usuario' => auth()->id(),
                    'fecha' => now(),
                    'estado' => true,
                ]);

                $producto->stock += $item['cantidad'];
                $producto->save();
            }

            $compra->update(['total' => $total]);

            return $compra;
        });

        return back()->with('status', "Compra #{$compra->id} registrada correctamente.");
    }

    public function compraUpdate(Request $request, Compra $compra)
    {
        if ($compra->situacion !== 'completada') {
            return back()->with('error', 'Solo se pueden modificar compras completadas.');
        }

        $data = $request->validate([
            'id_proveedor' => ['required', 'exists:proveedores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            DB::transaction(function () use ($data, $compra) {
                $this->revertirStockCompra($compra);
                $compra->detalles()->delete();

                $total = 0;

                foreach ($data['items'] as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);
                    $subtotal = $item['precio_unitario'] * $item['cantidad'];
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
                        'id_usuario' => auth()->id(),
                        'fecha' => now(),
                        'estado' => true,
                    ]);

                    $producto->stock += $item['cantidad'];
                    $producto->save();
                }

                $compra->update([
                    'id_proveedor' => $data['id_proveedor'],
                    'total' => $total,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Compra #{$compra->id} actualizada correctamente.");
    }

    public function compraDestroy(Compra $compra)
    {
        if ($compra->situacion === 'cancelada') {
            return back()->with('error', 'Esta compra ya está cancelada.');
        }

        try {
            DB::transaction(function () use ($compra) {
                $this->revertirStockCompra($compra);
                $compra->update(['situacion' => 'cancelada', 'estado' => false]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Compra #{$compra->id} cancelada y stock revertido correctamente.");
    }

    /**
     * Revierte el stock que aportó una compra. Si parte de ese stock ya salió
     * (vendido, etc.) lanza una excepción en lugar de dejar el stock inconsistente.
     */
    private function revertirStockCompra(Compra $compra): void
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
                throw new \RuntimeException(
                    "No se puede revertir la compra #{$compra->id}: \"{$fila['producto']->nombre_producto}\" tiene {$fila['producto']->stock} en stock y la compra aportó {$fila['cantidad']} (parte ya salió del inventario)."
                );
            }
        }

        foreach ($porProducto as $fila) {
            $producto = $fila['producto'];
            $producto->stock -= $fila['cantidad'];
            $producto->save();

            MovimientoStock::create([
                'id_producto' => $producto->id,
                'tipo' => 'salida',
                'cantidad' => $fila['cantidad'],
                'id_usuario' => auth()->id(),
                'fecha' => now(),
                'estado' => true,
            ]);
        }
    }

    public function compraEliminar(Compra $compra)
    {
        if ($compra->situacion === 'eliminada') {
            return back()->with('error', 'Esta compra ya fue eliminada.');
        }

        try {
            DB::transaction(function () use ($compra) {
                if ($compra->situacion === 'completada') {
                    $this->revertirStockCompra($compra);
                }

                $compra->update(['situacion' => 'eliminada', 'estado' => false]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Compra #{$compra->id} eliminada correctamente.");
    }

    public function dashboardIndex()
    {
        $ventasMes = Venta::where('situacion', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);

        $comprasMes = Compra::where('situacion', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);

        $stats = [
            'productos_activos' => Producto::where('estado', true)->count(),
            'usuarios_activos' => User::where('estado', true)->count(),
            'ventas_mes_total' => (clone $ventasMes)->sum('total'),
            'ventas_mes_count' => (clone $ventasMes)->count(),
            'compras_mes_total' => (clone $comprasMes)->sum('total'),
            'compras_mes_count' => (clone $comprasMes)->count(),
            'pedidos_pendientes' => Pedido::where('situacion', 'pendiente')->count(),
        ];

        $stockProductos = Producto::where('estado', true)
            ->orderBy('stock')
            ->take(6)
            ->get();

        $ultimosPedidos = Pedido::with('cliente')
            ->orderByDesc('fecha')
            ->take(6)
            ->get();

        $ultimasVentas = Venta::with('cliente')
            ->where('situacion', 'completada')
            ->orderByDesc('fecha')
            ->take(6)
            ->get();

        $ventasUltimosMeses = collect(range(5, 0))->map(function ($i) {
            $fecha = now()->copy()->subMonths($i);

            return [
                'mes' => ucfirst($fecha->translatedFormat('M')),
                'total' => (float) Venta::where('situacion', 'completada')
                    ->whereMonth('fecha', $fecha->month)
                    ->whereYear('fecha', $fecha->year)
                    ->sum('total'),
            ];
        });

        $pedidosPorEstado = Pedido::where('situacion', '!=', 'eliminado')
            ->selectRaw('situacion, COUNT(*) as total')
            ->groupBy('situacion')
            ->pluck('total', 'situacion');

        $ventasPorEstado = Venta::where('situacion', '!=', 'eliminada')
            ->selectRaw('situacion, COUNT(*) as total')
            ->groupBy('situacion')
            ->pluck('total', 'situacion');

        return view('dashboard', compact(
            'stats', 'stockProductos', 'ultimosPedidos', 'ultimasVentas',
            'ventasUltimosMeses', 'pedidosPorEstado', 'ventasPorEstado'
        ));
    }

    public function pedidoCheckout(Request $request)
    {
        $carrito = session('carrito', []);

        if (empty($carrito)) {
            return response()->json(['message' => 'Tu carrito está vacío.'], 422);
        }

        $datos = $request->validate([
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
            $pedido = DB::transaction(function () use ($carrito, $datos) {
                auth()->user()->update(['telefono' => $datos['telefono']]);

                $pedido = Pedido::create([
                    'id_cliente' => auth()->id(),
                    'fecha' => now(),
                    'total' => 0,
                    'situacion' => 'pendiente',
                    'direccion_entrega' => $datos['direccion_entrega'],
                    'nota' => $datos['nota'] ?? null,
                    'estado' => true,
                ]);

                $total = 0;

                foreach ($carrito as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['id']);

                    if (!$producto->estado || $item['cantidad'] > $producto->stock) {
                        throw new \RuntimeException("\"{$producto->nombre_producto}\" ya no tiene suficiente stock disponible.");
                    }

                    $subtotal = $producto->precio * $item['cantidad'];
                    $total += $subtotal;

                    DetallePedido::create([
                        'id_pedido' => $pedido->id,
                        'id_producto' => $producto->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $producto->precio,
                        'subtotal' => $subtotal,
                    ]);

                    $producto->stock -= $item['cantidad'];
                    $producto->save();

                    MovimientoStock::create([
                        'id_producto' => $producto->id,
                        'tipo' => 'salida',
                        'cantidad' => $item['cantidad'],
                        'id_usuario' => auth()->id(),
                        'fecha' => now(),
                        'estado' => true,
                    ]);
                }

                $pedido->update(['total' => $total]);

                return $pedido;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        session()->forget('carrito');

        return response()->json([
            'message' => "¡Pedido #{$pedido->id} realizado con éxito!",
            'pedido_id' => $pedido->id,
        ]);
    }

    public function pedidoMisPedidos()
    {
        $pedidos = Pedido::with('detalles.producto')
            ->where('id_cliente', auth()->id())
            ->orderByDesc('fecha')
            ->get();

        return view('pedidos.mis', compact('pedidos'));
    }

    public function pedidoIndex(Request $request)
    {
        $query = Pedido::with(['cliente', 'detalles.producto'])
            ->where('pedidos.situacion', '!=', 'eliminado');

        if ($buscar = $request->get('buscar')) {
            $query->whereHas('cliente', fn ($q) => $q->where('name', 'like', "%{$buscar}%"));
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'pedidos.id',
            'cliente' => function ($q, $dir) {
                $q->join('users as cli_sort', 'pedidos.id_cliente', '=', 'cli_sort.id')
                  ->orderBy('cli_sort.name', $dir)
                  ->select('pedidos.*');
            },
            'total' => 'pedidos.total',
            'fecha' => 'pedidos.fecha',
            'situacion' => 'pedidos.situacion',
        ], 'fecha', 'desc');

        $pedidos = $query->get();
        $clientes = User::whereHas('roles', fn ($q) => $q->where('name', 'cliente'))->orderBy('name')->get();
        $productos = Producto::where('estado', true)->where('stock', '>', 0)->orderBy('nombre_producto')->get();

        $pedidoAbrir = null;
        if ($verId = $request->integer('ver')) {
            $pedidoAbrir = Pedido::with(['cliente', 'detalles.producto'])
                ->where('situacion', '!=', 'eliminado')
                ->find($verId);
        }

        return view('pedidos.index', compact('pedidos', 'clientes', 'productos', 'sort', 'dir', 'pedidoAbrir'));
    }

    public function notificacionesVistas(Request $request)
    {
        $data = $request->validate([
            'hasta' => ['nullable', 'date'],
        ]);

        $hasta = isset($data['hasta']) ? \Illuminate\Support\Carbon::parse($data['hasta']) : now();

        if ($hasta->isFuture()) {
            $hasta = now();
        }

        User::whereKey($request->user()->id)->update(['notificaciones_vistas_at' => $hasta]);

        return response()->json(['ok' => true]);
    }

    public function pedidoStore(Request $request)
    {
        $data = $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $pedido = DB::transaction(function () use ($data) {
                $pedido = Pedido::create([
                    'id_cliente' => $data['id_cliente'],
                    'fecha' => now(),
                    'total' => 0,
                    'situacion' => 'pendiente',
                    'estado' => true,
                ]);

                $total = 0;

                foreach ($data['items'] as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);

                    if ($item['cantidad'] > $producto->stock) {
                        throw new \RuntimeException("No hay suficiente stock de \"{$producto->nombre_producto}\": disponible {$producto->stock}, solicitado {$item['cantidad']}.");
                    }

                    $subtotal = $producto->precio * $item['cantidad'];
                    $total += $subtotal;

                    DetallePedido::create([
                        'id_pedido' => $pedido->id,
                        'id_producto' => $producto->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $producto->precio,
                        'subtotal' => $subtotal,
                    ]);

                    $producto->stock -= $item['cantidad'];
                    $producto->save();

                    MovimientoStock::create([
                        'id_producto' => $producto->id,
                        'tipo' => 'salida',
                        'cantidad' => $item['cantidad'],
                        'id_usuario' => auth()->id(),
                        'fecha' => now(),
                        'estado' => true,
                    ]);
                }

                $pedido->update(['total' => $total]);

                return $pedido;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Pedido #{$pedido->id} registrado correctamente.");
    }

    public function pedidoDestroy(Pedido $pedido)
    {
        if ($pedido->situacion === 'eliminado') {
            return back()->with('error', 'Este pedido ya fue eliminado.');
        }

        DB::transaction(function () use ($pedido) {

            if (in_array($pedido->situacion, ['pendiente', 'confirmado'])) {
                foreach ($pedido->detalles as $detalle) {
                    $producto = $detalle->producto;

                    if ($producto) {
                        $producto->stock += $detalle->cantidad;
                        $producto->save();

                        MovimientoStock::create([
                            'id_producto' => $producto->id,
                            'tipo' => 'entrada',
                            'cantidad' => $detalle->cantidad,
                            'id_usuario' => auth()->id(),
                            'fecha' => now(),
                            'estado' => true,
                        ]);
                    }
                }
            }

            $pedido->update(['situacion' => 'eliminado', 'estado' => false]);
        });

        return back()->with('status', "Pedido #{$pedido->id} eliminado correctamente.");
    }

    public function pedidoUpdate(Request $request, Pedido $pedido)
    {
        if (! in_array($pedido->situacion, ['pendiente', 'confirmado'])) {
            return back()->with('error', 'Solo se pueden modificar pedidos pendientes o confirmados.');
        }

        $data = $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($data, $pedido) {

                foreach ($pedido->detalles as $detalle) {
                    if ($detalle->producto) {
                        $detalle->producto->stock += $detalle->cantidad;
                        $detalle->producto->save();

                        MovimientoStock::create([
                            'id_producto' => $detalle->producto->id,
                            'tipo' => 'entrada',
                            'cantidad' => $detalle->cantidad,
                            'id_usuario' => auth()->id(),
                            'fecha' => now(),
                            'estado' => true,
                        ]);
                    }
                }
                $pedido->detalles()->delete();

                $total = 0;

                foreach ($data['items'] as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);

                    if ($item['cantidad'] > $producto->stock) {
                        throw new \RuntimeException("No hay suficiente stock de \"{$producto->nombre_producto}\": disponible {$producto->stock}, solicitado {$item['cantidad']}.");
                    }

                    $subtotal = $producto->precio * $item['cantidad'];
                    $total += $subtotal;

                    DetallePedido::create([
                        'id_pedido' => $pedido->id,
                        'id_producto' => $producto->id,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $producto->precio,
                        'subtotal' => $subtotal,
                    ]);

                    MovimientoStock::create([
                        'id_producto' => $producto->id,
                        'tipo' => 'salida',
                        'cantidad' => $item['cantidad'],
                        'id_usuario' => auth()->id(),
                        'fecha' => now(),
                        'estado' => true,
                    ]);

                    $producto->stock -= $item['cantidad'];
                    $producto->save();
                }

                $pedido->update([
                    'id_cliente' => $data['id_cliente'],
                    'total' => $total,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Pedido #{$pedido->id} actualizado correctamente.");
    }

    public function pedidoUpdateEstado(Request $request, Pedido $pedido)
    {
        $data = $request->validate([
            'situacion' => ['required', 'in:confirmado,entregado,cancelado'],
        ]);

        if (in_array($pedido->situacion, ['entregado', 'cancelado', 'eliminado'])) {
            return back()->with('error', 'Este pedido ya no se puede modificar.');
        }

        if ($data['situacion'] === 'entregado') {
            $tipoPago = $request->input('tipo_pago');

            if (! array_key_exists($tipoPago, Venta::TIPOS_PAGO)) {
                return back()->with('error', 'Selecciona el tipo de pago para confirmar el pedido.');
            }

            try {
                DB::transaction(function () use ($pedido, $tipoPago) {
                    $venta = Venta::create([
                        'id_cliente' => $pedido->id_cliente,
                        'id_vendedor' => auth()->id(),
                        'fecha' => now(),
                        'total' => 0,
                        'tipo_pago' => $tipoPago,
                        'situacion' => 'completada',
                        'estado' => true,
                    ]);

                    $total = 0;

                    foreach ($pedido->detalles as $detalle) {
                        $producto = $detalle->producto;

                        if (!$producto) {
                            throw new \RuntimeException('Uno de los productos del pedido ya no existe.');
                        }

                        $subtotal = $detalle->precio_unitario * $detalle->cantidad;
                        $total += $subtotal;

                        DetalleVenta::create([
                            'id_venta' => $venta->id,
                            'id_producto' => $producto->id,
                            'cantidad' => $detalle->cantidad,
                            'precio_unitario' => $detalle->precio_unitario,
                            'subtotal' => $subtotal,
                        ]);
                    }

                    $venta->update(['total' => $total]);

                    $pedido->update(['situacion' => 'entregado', 'id_venta' => $venta->id]);
                });
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }

            return back()->with('status', "Pedido #{$pedido->id} marcado como entregado y registrado en Ventas.");
        }

        if ($data['situacion'] === 'cancelado') {
            DB::transaction(function () use ($pedido) {
                foreach ($pedido->detalles as $detalle) {
                    $producto = $detalle->producto;

                    if ($producto) {
                        $producto->stock += $detalle->cantidad;
                        $producto->save();

                        MovimientoStock::create([
                            'id_producto' => $producto->id,
                            'tipo' => 'entrada',
                            'cantidad' => $detalle->cantidad,
                            'id_usuario' => auth()->id(),
                            'fecha' => now(),
                            'estado' => true,
                        ]);
                    }
                }

                $pedido->update(['situacion' => 'cancelado']);
            });

            return back()->with('status', "Pedido #{$pedido->id} cancelado y stock restaurado correctamente.");
        }

        $pedido->update(['situacion' => $data['situacion']]);

        return back()->with('status', "Pedido #{$pedido->id} marcado como \"{$data['situacion']}\".");
    }

    public function permisoIndex(Request $request)
    {
        $query = Permission::withCount('roles');

        if ($buscar = $request->get('buscar')) {
            $query->where('name', 'like', "%{$buscar}%");
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'id',
            'name' => 'name',
            'roles' => 'roles_count',
        ], 'id', 'desc');

        $permisos = $query->get();

        return view('permisos.index', compact('permisos', 'sort', 'dir'));
    }

    public function permisoStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:permissions,name', 'regex:/^[a-z0-9]+(-[a-z0-9]+)+$/'],
        ], [
            'name.regex' => 'El nombre debe seguir el formato "accion-modulo" (ej: ver-reportes), solo minúsculas y guiones.',
        ]);

        Permission::create(['name' => $data['name'], 'guard_name' => 'web']);

        return back()->with('status', "Permiso \"{$data['name']}\" creado correctamente.");
    }

    public function permisoUpdate(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('permissions', 'name')->ignore($permission->id),
                'regex:/^[a-z0-9]+(-[a-z0-9]+)+$/',
            ],
        ], [
            'name.regex' => 'El nombre debe seguir el formato "accion-modulo" (ej: ver-reportes), solo minúsculas y guiones.',
        ]);

        $permission->update(['name' => $data['name']]);

        return back()->with('status', "Permiso actualizado a \"{$data['name']}\" correctamente.");
    }

    public function permisoDestroy(Permission $permission)
    {
        if ($permission->roles()->exists()) {
            return back()->with('error', "No puedes eliminar \"{$permission->name}\" porque está asignado a uno o más roles. Quítaselo primero desde Administrar Rol.");
        }

        $permission->delete();

        return back()->with('status', "Permiso \"{$permission->name}\" eliminado correctamente.");
    }

    public function productoIndex(Request $request)
    {
        $query = Producto::with('categoria');

        if ($buscar = $request->get('buscar')) {
            $query->where('nombre_producto', 'like', "%{$buscar}%");
        }

        if ($cat = $request->get('categoria')) {
            $query->where('id_categoria', $cat);
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'productos.id',
            'nombre' => 'productos.nombre_producto',
            'categoria' => function ($q, $dir) {
                $q->join('categorias', 'productos.id_categoria', '=', 'categorias.id')
                  ->orderBy('categorias.nombre_categoria', $dir)
                  ->select('productos.*');
            },
            'precio' => 'productos.precio',
            'stock' => 'productos.stock',
            'estado' => 'productos.estado',
        ], 'id', 'desc');

        $productos = $query->get();
        $categorias = Categoria::where('estado', true)->orderBy('nombre_categoria')->get();

        return view('productos.index', compact('productos', 'categorias', 'sort', 'dir'));
    }

    public function productoStore(Request $request)
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

        DB::transaction(function () use ($data) {
            $producto = Producto::create($data);

            if ($producto->stock > 0) {
                MovimientoStock::create([
                    'id_producto' => $producto->id,
                    'tipo' => 'entrada',
                    'cantidad' => $producto->stock,
                    'id_usuario' => auth()->id(),
                    'fecha' => now(),
                    'estado' => true,
                ]);
            }
        });

        return back()->with('status', 'Producto agregado correctamente.');
    }

    public function productoUpdate(Request $request, Producto $producto)
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

        $data['estado'] = $request->boolean('estado');

        $precioAnterior = (float) $producto->precio;
        $precioNuevo = (float) $data['precio'];

        DB::transaction(function () use ($producto, $data, $precioAnterior, $precioNuevo) {
            $producto->update($data);

            if ($precioNuevo !== $precioAnterior) {
                HistorialPrecioProducto::create([
                    'id_producto' => $producto->id,
                    'precio_anterior' => $precioAnterior,
                    'precio_nuevo' => $precioNuevo,
                    'id_usuario' => auth()->id(),
                    'fecha' => now(),
                ]);
            }
        });

        return back()->with('status', 'Producto actualizado correctamente.');
    }

    public function productoDestroy(Producto $producto)
    {
        $producto->update(['estado' => false]);

        return back()->with('status', 'Producto desactivado correctamente.');
    }

    public function profileEdit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function profileUpdate(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->safe()->except('foto'));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        if ($request->hasFile('foto')) {
            $request->user()->imagen_usuario = $request->file('foto')->store('usuarios', 'images');
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function profileDestroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // Tiene pedidos/ventas/compras asociadas: se desactiva en lugar de borrar.
            $user->estado = false;
            $user->save();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function proveedorIndex(Request $request)
    {
        $query = Proveedor::withCount('compras');

        if ($buscar = $request->get('buscar')) {
            $query->where('nombre_empresa', 'like', "%{$buscar}%");
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'id',
            'nombre' => 'nombre_empresa',
            'compras' => 'compras_count',
            'estado' => 'estado',
        ], 'id', 'desc');

        $proveedores = $query->get();

        return view('proveedores.index', compact('proveedores', 'sort', 'dir'));
    }

    public function proveedorStore(Request $request)
    {
        $data = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'boolean'],
        ]);

        Proveedor::create([
            'nombre_empresa' => $data['nombre_empresa'],
            'contacto' => $data['contacto'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'estado' => $request->boolean('estado', true),
        ]);

        return back()->with('status', 'Proveedor creado correctamente.');
    }

    public function proveedorUpdate(Request $request, Proveedor $proveedor)
    {
        $data = $request->validate([
            'nombre_empresa' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'boolean'],
        ]);

        $proveedor->update([
            'nombre_empresa' => $data['nombre_empresa'],
            'contacto' => $data['contacto'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'estado' => $request->boolean('estado'),
        ]);

        return back()->with('status', 'Proveedor actualizado correctamente.');
    }

    public function proveedorDestroy(Proveedor $proveedor)
    {
        if ($proveedor->compras()->exists()) {
            return back()->with('error', "No puedes eliminar \"{$proveedor->nombre_empresa}\" porque tiene compras asociadas. Desactívalo en su lugar.");
        }

        $proveedor->delete();

        return back()->with('status', 'Proveedor eliminado correctamente.');
    }

    public function reporteIndex()
    {
        $desde = now()->startOfMonth();
        $hasta = now()->endOfMonth();

        $totalVentas = Venta::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta])->sum('total');
        $totalCompras = Compra::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta])->sum('total');
        $productosStockBajo = Producto::where('estado', true)->where('stock', '<=', 10)->count();
        $productoTop = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.id_venta')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.id_producto')
            ->where('ventas.situacion', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('productos.nombre_producto, SUM(detalle_ventas.cantidad) as total_cantidad')
            ->groupBy('productos.id', 'productos.nombre_producto')
            ->orderByDesc('total_cantidad')
            ->first();

        return view('reportes.index', compact('totalVentas', 'totalCompras', 'productosStockBajo', 'productoTop'));
    }

    public function reporteVentas(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $query = Venta::with(['cliente', 'vendedor'])
            ->where('situacion', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idCliente = $request->get('id_cliente')) {
            $query->where('id_cliente', $idCliente);
        }

        $ventas = $query->orderByDesc('fecha')->get();
        $totalVentas = $ventas->sum('total');
        $clientes = User::whereHas('roles', fn ($q) => $q->where('name', 'cliente'))->orderBy('name')->get();

        return view('reportes.ventas', compact('ventas', 'totalVentas', 'clientes', 'desde', 'hasta'));
    }

    public function reporteVentasPdf(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $query = Venta::with(['cliente', 'vendedor'])
            ->where('situacion', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idCliente = $request->get('id_cliente')) {
            $query->where('id_cliente', $idCliente);
        }

        $ventas = $query->orderByDesc('fecha')->get();
        $totalVentas = $ventas->sum('total');

        $pdf = Pdf::loadView('reportes.pdf.ventas', compact('ventas', 'totalVentas', 'desde', 'hasta'));

        return $pdf->stream('reporte-ventas-'.now()->format('Y-m-d').'.pdf');
    }

    public function reporteCompras(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $query = Compra::with(['proveedor', 'usuario'])
            ->where('situacion', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idProveedor = $request->get('id_proveedor')) {
            $query->where('id_proveedor', $idProveedor);
        }

        $compras = $query->orderByDesc('fecha')->get();
        $totalCompras = $compras->sum('total');
        $proveedores = Proveedor::where('estado', true)->orderBy('nombre_empresa')->get();

        return view('reportes.compras', compact('compras', 'totalCompras', 'proveedores', 'desde', 'hasta'));
    }

    public function reporteComprasPdf(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $query = Compra::with(['proveedor', 'usuario'])
            ->where('situacion', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idProveedor = $request->get('id_proveedor')) {
            $query->where('id_proveedor', $idProveedor);
        }

        $compras = $query->orderByDesc('fecha')->get();
        $totalCompras = $compras->sum('total');

        $pdf = Pdf::loadView('reportes.pdf.compras', compact('compras', 'totalCompras', 'desde', 'hasta'));

        return $pdf->stream('reporte-compras-'.now()->format('Y-m-d').'.pdf');
    }

    public function reporteStockBajo(Request $request)
    {
        [$tipo, $orden] = $this->reporteFiltrosMovimientoStock($request);

        $movimientos = $this->reporteConsultaMovimientoStock($tipo, $orden)->get();

        return view('reportes.stock', compact('movimientos', 'tipo', 'orden'));
    }

    public function reporteStockBajoPdf(Request $request)
    {
        [$tipo, $orden] = $this->reporteFiltrosMovimientoStock($request);

        $movimientos = $this->reporteConsultaMovimientoStock($tipo, $orden)->get();

        $pdf = Pdf::loadView('reportes.pdf.stock', compact('movimientos', 'tipo', 'orden'));

        return $pdf->stream('reporte-movimiento-stock-'.now()->format('Y-m-d').'.pdf');
    }

    public function reporteMasVendidos(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);
        $limite = max(1, min(100, (int) $request->get('top', 15)));

        $productos = $this->reporteConsultaMasVendidos($desde, $hasta, $limite);

        return view('reportes.mas-vendidos', compact('productos', 'desde', 'hasta', 'limite'));
    }

    public function reporteMasVendidosPdf(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);
        $limite = max(1, min(100, (int) $request->get('top', 15)));

        $productos = $this->reporteConsultaMasVendidos($desde, $hasta, $limite);

        $pdf = Pdf::loadView('reportes.pdf.mas-vendidos', compact('productos', 'desde', 'hasta', 'limite'));

        return $pdf->stream('reporte-mas-vendidos-'.now()->format('Y-m-d').'.pdf');
    }

    public function reportePreciosProveedor(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $detalles = $this->reporteConsultaPreciosProveedor($request, $desde, $hasta);
        $productos = Producto::orderBy('nombre_producto')->get();
        $proveedores = Proveedor::where('estado', true)->orderBy('nombre_empresa')->get();

        return view('reportes.precios-proveedor', compact('detalles', 'productos', 'proveedores', 'desde', 'hasta'));
    }

    public function reportePreciosProveedorPdf(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $detalles = $this->reporteConsultaPreciosProveedor($request, $desde, $hasta);

        $pdf = Pdf::loadView('reportes.pdf.precios-proveedor', compact('detalles', 'desde', 'hasta'));

        return $pdf->stream('reporte-precios-proveedor-'.now()->format('Y-m-d').'.pdf');
    }

    private function reporteConsultaPreciosProveedor(Request $request, Carbon $desde, Carbon $hasta)
    {
        $query = DetalleCompra::with(['producto', 'compra.proveedor'])
            ->whereHas('compra', function ($q) use ($desde, $hasta) {
                $q->where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta]);
            });

        if ($idProducto = $request->get('id_producto')) {
            $query->where('id_producto', $idProducto);
        }

        if ($idProveedor = $request->get('id_proveedor')) {
            $query->whereHas('compra', fn ($q) => $q->where('id_proveedor', $idProveedor));
        }

        return $query->join('compras', 'compras.id', '=', 'detalle_compras.id_compra')
            ->orderByDesc('compras.fecha')
            ->select('detalle_compras.*')
            ->get();
    }

    public function reporteHistorialPrecios(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $historial = $this->reporteConsultaHistorialPrecios($request, $desde, $hasta);
        $productos = Producto::orderBy('nombre_producto')->get();

        return view('reportes.historial-precios', compact('historial', 'productos', 'desde', 'hasta'));
    }

    public function reporteHistorialPreciosPdf(Request $request)
    {
        [$desde, $hasta] = $this->reporteRangoFechas($request);

        $historial = $this->reporteConsultaHistorialPrecios($request, $desde, $hasta);

        $pdf = Pdf::loadView('reportes.pdf.historial-precios', compact('historial', 'desde', 'hasta'));

        return $pdf->stream('reporte-historial-precios-'.now()->format('Y-m-d').'.pdf');
    }

    private function reporteConsultaHistorialPrecios(Request $request, Carbon $desde, Carbon $hasta)
    {
        $query = HistorialPrecioProducto::with(['producto', 'usuario'])
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idProducto = $request->get('id_producto')) {
            $query->where('id_producto', $idProducto);
        }

        return $query->orderByDesc('fecha')->get();
    }

    private function reporteFiltrosMovimientoStock(Request $request): array
    {
        $tipo = $request->get('tipo');
        $tipo = in_array($tipo, ['entrada', 'salida']) ? $tipo : null;

        $orden = $request->get('orden') === 'za' ? 'za' : 'az';

        return [$tipo, $orden];
    }

    private function reporteConsultaMovimientoStock(?string $tipo, string $orden)
    {
        $query = MovimientoStock::with(['producto', 'usuario'])
            ->join('productos', 'productos.id', '=', 'movimientos_stock.id_producto')
            ->where('movimientos_stock.estado', true)
            ->select('movimientos_stock.*');

        if ($tipo) {
            $query->where('movimientos_stock.tipo', $tipo);
        }

        return $query->orderBy('productos.nombre_producto', $orden === 'za' ? 'desc' : 'asc');
    }

    private function reporteRangoFechas(Request $request): array
    {
        $parsear = function (?string $valor, bool $fin) {
            if (! $valor) {
                return null;
            }

            try {
                $fecha = Carbon::parse($valor);
            } catch (\Throwable $e) {
                return null;
            }

            return $fin ? $fecha->endOfDay() : $fecha->startOfDay();
        };

        $desde = $parsear($request->get('desde'), false) ?? now()->startOfMonth();
        $hasta = $parsear($request->get('hasta'), true) ?? now()->endOfDay();

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        return [$desde, $hasta];
    }

    private function reporteConsultaMasVendidos(Carbon $desde, Carbon $hasta, int $limite)
    {
        return DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.id_venta')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.id_producto')
            ->where('ventas.situacion', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('productos.id, productos.nombre_producto, productos.imagen_producto, SUM(detalle_ventas.cantidad) as total_cantidad, SUM(detalle_ventas.subtotal) as total_ingresos')
            ->groupBy('productos.id', 'productos.nombre_producto', 'productos.imagen_producto')
            ->orderByDesc('total_cantidad')
            ->limit($limite)
            ->get();
    }

    public function rolIndex()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        $permisosPorModulo = Permission::orderBy('name')->get()
            ->groupBy(function ($permiso) {
                $partes = explode('-', $permiso->name, 2);
                return $partes[1] ?? 'general';
            });

        return view('roles.index', compact('roles', 'permisosPorModulo'));
    }

    public function rolStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        ]);

        Role::create(['name' => $data['name']]);

        return back()->with('status', 'Rol creado correctamente.');
    }

    public function rolUpdate(Request $request, Role $role)
    {
        if (in_array($role->name, ['administrador', 'vendedor', 'cliente'])) {
            return back()->with('error', 'No puedes renombrar un rol base del sistema.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,' . $role->id],
        ]);

        $role->update(['name' => $data['name']]);

        return back()->with('status', 'Rol actualizado correctamente.');
    }

    public function rolDestroy(Role $role)
    {

        if (in_array($role->name, ['administrador', 'vendedor', 'cliente'])) {
            return back()->with('error', 'No puedes eliminar un rol base del sistema.');
        }

        $role->delete();

        return back()->with('status', 'Rol eliminado correctamente.');
    }

    public function rolSyncPermissions(Request $request, Role $role)
    {
        $data = $request->validate([
            'permisos' => ['array'],
            'permisos.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->syncPermissions($data['permisos'] ?? []);

        return back()->with('status', "Permisos de \"{$role->name}\" actualizados.");
    }

    public function stockIndex(Request $request)
    {
        $query = MovimientoStock::with(['producto', 'usuario']);

        if ($buscar = $request->get('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('movimientos_stock.tipo', 'like', "%{$buscar}%")
                  ->orWhereHas('producto', fn ($p) => $p->where('nombre_producto', 'like', "%{$buscar}%"));
            });
        }

        if ($tipo = $request->get('tipo')) {
            $query->where('movimientos_stock.tipo', $tipo);
        }

        if ($idProducto = $request->get('id_producto')) {
            $query->where('movimientos_stock.id_producto', $idProducto);
        }

        if ($desde = $request->get('desde')) {
            $query->whereDate('movimientos_stock.fecha', '>=', $desde);
        }

        if ($hasta = $request->get('hasta')) {
            $query->whereDate('movimientos_stock.fecha', '<=', $hasta);
        }

        $totalEntradas = (clone $query)->where('movimientos_stock.tipo', 'entrada')->sum('movimientos_stock.cantidad');
        $totalSalidas = (clone $query)->where('movimientos_stock.tipo', 'salida')->sum('movimientos_stock.cantidad');
        $totalMovimientos = (clone $query)->count();

        [$sort, $dir] = $this->applySort($query, $request, [
            'producto' => function ($q, $dir) {
                $q->join('productos as prod_sort', 'movimientos_stock.id_producto', '=', 'prod_sort.id')
                  ->orderBy('prod_sort.nombre_producto', $dir)
                  ->select('movimientos_stock.*');
            },
            'tipo' => 'movimientos_stock.tipo',
            'cantidad' => 'movimientos_stock.cantidad',
            'fecha' => 'movimientos_stock.fecha',
            'usuario' => function ($q, $dir) {
                $q->join('users as reg_sort', 'movimientos_stock.id_usuario', '=', 'reg_sort.id')
                  ->orderBy('reg_sort.name', $dir)
                  ->select('movimientos_stock.*');
            },
        ], 'fecha', 'desc');

        $movimientos = $query->get();
        $productos = Producto::where('estado', true)->orderBy('nombre_producto')->get();

        return view('stock.index', compact(
            'movimientos', 'productos', 'sort', 'dir',
            'totalEntradas', 'totalSalidas', 'totalMovimientos'
        ));
    }

    public function stockStore(Request $request)
    {
        $data = $request->validate([
            'id_producto' => ['required', 'exists:productos,id'],
            'tipo' => ['required', 'in:entrada,salida'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($data) {
                $producto = Producto::lockForUpdate()->findOrFail($data['id_producto']);

                if ($data['tipo'] === 'salida' && $data['cantidad'] > $producto->stock) {
                    throw new \RuntimeException("No puedes registrar una salida de {$data['cantidad']} unidades: \"{$producto->nombre_producto}\" solo tiene {$producto->stock} en stock.");
                }

                MovimientoStock::create([
                    'id_producto' => $producto->id,
                    'tipo' => $data['tipo'],
                    'cantidad' => $data['cantidad'],
                    'id_usuario' => auth()->id(),
                    'fecha' => now(),
                    'estado' => true,
                ]);

                $producto->stock = $data['tipo'] === 'entrada'
                    ? $producto->stock + $data['cantidad']
                    : $producto->stock - $data['cantidad'];

                $producto->save();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Movimiento de stock registrado correctamente.');
    }

    public function stockDestroy(MovimientoStock $movimiento)
    {
        try {
            DB::transaction(function () use ($movimiento) {
                $producto = Producto::lockForUpdate()->find($movimiento->id_producto);

                if ($producto) {
                    $nuevoStock = $movimiento->tipo === 'entrada'
                        ? $producto->stock - $movimiento->cantidad
                        : $producto->stock + $movimiento->cantidad;

                    if ($nuevoStock < 0) {
                        throw new \RuntimeException("No se puede eliminar el movimiento: \"{$producto->nombre_producto}\" quedaría con stock negativo ({$nuevoStock}). Parte de esa entrada ya salió del inventario.");
                    }

                    $producto->stock = $nuevoStock;
                    $producto->save();
                }

                $movimiento->delete();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Movimiento eliminado y stock revertido correctamente.');
    }

    public function usuarioIndex(Request $request, ?string $rolFiltro = null)
    {
        $query = User::with('roles');

        if ($rolFiltro) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $rolFiltro));
        }

        if ($buscar = $request->get('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'telefono' => 'telefono',
            'estado' => 'estado',
        ], 'id', 'desc');

        $usuarios = $query->get();
        $roles = Role::orderBy('name')->get();

        $titulo = match ($rolFiltro) {
            'cliente' => 'Administrar Clientes',
            'vendedor' => 'Administrar Vendedores',
            default => 'Administrar Usuarios',
        };

        return view('usuarios.index', compact('usuarios', 'roles', 'titulo', 'rolFiltro', 'sort', 'dir'));
    }

    public function usuarioUpdate(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'estado' => ['nullable', 'boolean'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($user->id === auth()->id() && $user->hasRole('administrador') && $data['role'] !== 'administrador') {
            return back()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        if ($user->id === auth()->id() && empty($data['estado'])) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->name = $data['name'];
        $user->telefono = $data['telefono'] ?? null;
        $user->email = $data['email'];
        $user->estado = $request->boolean('estado');

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('foto')) {
            $user->imagen_usuario = $request->file('foto')->store('usuarios', 'images');
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return back()->with('status', "Usuario \"{$user->name}\" actualizado correctamente.");
    }

    public function usuarioDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta desde aquí.');
        }

        $user->estado = false;
        $user->save();

        return back()->with('status', "Usuario \"{$user->name}\" desactivado correctamente.");
    }

    public function usuarioRolIndex(Request $request)
    {
        $query = User::with('roles');

        if ($buscar = $request->get('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
        ], 'id', 'desc');

        $usuarios = $query->get();
        $roles = Role::orderBy('name')->get();

        return view('usuario-roles.index', compact('usuarios', 'roles', 'sort', 'dir'));
    }

    public function usuarioRolStore(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($data['user_id']);

        if ($user->id === auth()->id() && $user->hasRole('administrador') && $data['role'] !== 'administrador') {
            return back()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        $user->syncRoles([$data['role']]);

        return back()->with('status', "Rol de \"{$user->name}\" asignado correctamente.");
    }

    public function usuarioRolDestroy(User $user)
    {
        if ($user->id === auth()->id() && $user->hasRole('administrador')) {
            return back()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        $user->syncRoles([]);

        return back()->with('status', "Rol de \"{$user->name}\" eliminado correctamente.");
    }

    public function ventaIndex(Request $request)
    {
        $query = Venta::with(['cliente', 'vendedor', 'detalles.producto', 'pedido'])
            ->where('ventas.situacion', '!=', 'eliminada');

        if ($buscar = $request->get('buscar')) {
            $query->whereHas('cliente', fn ($q) => $q->where('name', 'like', "%{$buscar}%"));
        }

        [$sort, $dir] = $this->applySort($query, $request, [
            'id' => 'ventas.id',
            'cliente' => function ($q, $dir) {
                $q->join('users as cli_sort', 'ventas.id_cliente', '=', 'cli_sort.id')
                  ->orderBy('cli_sort.name', $dir)
                  ->select('ventas.*');
            },
            'vendedor' => function ($q, $dir) {
                $q->join('users as ven_sort', 'ventas.id_vendedor', '=', 'ven_sort.id')
                  ->orderBy('ven_sort.name', $dir)
                  ->select('ventas.*');
            },
            'total' => 'ventas.total',
            'fecha' => 'ventas.fecha',
            'situacion' => 'ventas.situacion',
        ], 'fecha', 'desc');

        $ventas = $query->get();
        $clientes = User::whereHas('roles', fn ($q) => $q->where('name', 'cliente'))->orderBy('name')->get();
        $productos = Producto::where('estado', true)->where('stock', '>', 0)->orderBy('nombre_producto')->get();

        return view('ventas.index', compact('ventas', 'clientes', 'productos', 'sort', 'dir'));
    }

    public function ventaStore(Request $request)
    {
        $data = $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'tipo_pago' => ['required', 'in:'.implode(',', array_keys(Venta::TIPOS_PAGO))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $venta = DB::transaction(function () use ($data) {
                $venta = Venta::create([
                    'id_cliente' => $data['id_cliente'],
                    'id_vendedor' => auth()->id(),
                    'fecha' => now(),
                    'total' => 0,
                    'tipo_pago' => $data['tipo_pago'],
                    'situacion' => 'completada',
                    'estado' => true,
                ]);

                $total = 0;

                foreach ($data['items'] as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);

                    if ($item['cantidad'] > $producto->stock) {
                        throw new \RuntimeException("No hay suficiente stock de \"{$producto->nombre_producto}\": disponible {$producto->stock}, solicitado {$item['cantidad']}.");
                    }

                    $subtotal = $producto->precio * $item['cantidad'];
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
                        'id_usuario' => auth()->id(),
                        'fecha' => now(),
                        'estado' => true,
                    ]);

                    $producto->stock -= $item['cantidad'];
                    $producto->save();
                }

                $venta->update(['total' => $total]);

                return $venta;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Venta #{$venta->id} registrada correctamente.");
    }

    public function ventaUpdate(Request $request, Venta $venta)
    {
        if ($venta->situacion !== 'completada') {
            return back()->with('error', 'Solo se pueden modificar ventas completadas.');
        }

        if (! $venta->esManual()) {
            return back()->with('error', 'Esta venta proviene de un pedido entregado y no se puede modificar manualmente.');
        }

        $data = $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'tipo_pago' => ['required', 'in:'.implode(',', array_keys(Venta::TIPOS_PAGO))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_producto' => ['required', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($data, $venta) {

                foreach ($venta->detalles as $detalle) {
                    if ($detalle->producto) {
                        $detalle->producto->stock += $detalle->cantidad;
                        $detalle->producto->save();

                        MovimientoStock::create([
                            'id_producto' => $detalle->producto->id,
                            'tipo' => 'entrada',
                            'cantidad' => $detalle->cantidad,
                            'id_usuario' => auth()->id(),
                            'fecha' => now(),
                            'estado' => true,
                        ]);
                    }
                }
                $venta->detalles()->delete();

                $total = 0;

                foreach ($data['items'] as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['id_producto']);

                    if ($item['cantidad'] > $producto->stock) {
                        throw new \RuntimeException("No hay suficiente stock de \"{$producto->nombre_producto}\": disponible {$producto->stock}, solicitado {$item['cantidad']}.");
                    }

                    $subtotal = $producto->precio * $item['cantidad'];
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
                        'id_usuario' => auth()->id(),
                        'fecha' => now(),
                        'estado' => true,
                    ]);

                    $producto->stock -= $item['cantidad'];
                    $producto->save();
                }

                $venta->update([
                    'id_cliente' => $data['id_cliente'],
                    'tipo_pago' => $data['tipo_pago'],
                    'total' => $total,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Venta #{$venta->id} actualizada correctamente.");
    }

    public function ventaDestroy(Venta $venta)
    {
        if ($venta->situacion === 'cancelada') {
            return back()->with('error', 'Esta venta ya está cancelada.');
        }

        if (! $venta->esManual()) {
            return back()->with('error', 'Esta venta proviene de un pedido entregado; gestiona el pedido en lugar de la venta.');
        }

        DB::transaction(function () use ($venta) {
            foreach ($venta->detalles as $detalle) {
                $producto = $detalle->producto;

                if ($producto) {
                    $producto->stock += $detalle->cantidad;
                    $producto->save();

                    MovimientoStock::create([
                        'id_producto' => $producto->id,
                        'tipo' => 'entrada',
                        'cantidad' => $detalle->cantidad,
                        'id_usuario' => auth()->id(),
                        'fecha' => now(),
                        'estado' => true,
                    ]);
                }
            }

            $venta->update(['situacion' => 'cancelada', 'estado' => false]);
        });

        return back()->with('status', "Venta #{$venta->id} cancelada y stock revertido correctamente.");
    }

    public function ventaEliminar(Venta $venta)
    {
        if ($venta->situacion === 'eliminada') {
            return back()->with('error', 'Esta venta ya fue eliminada.');
        }

        if (! $venta->esManual()) {
            return back()->with('error', 'Esta venta proviene de un pedido entregado; gestiona el pedido en lugar de la venta.');
        }

        DB::transaction(function () use ($venta) {
            if ($venta->situacion === 'completada') {
                foreach ($venta->detalles as $detalle) {
                    $producto = $detalle->producto;

                    if ($producto) {
                        $producto->stock += $detalle->cantidad;
                        $producto->save();

                        MovimientoStock::create([
                            'id_producto' => $producto->id,
                            'tipo' => 'entrada',
                            'cantidad' => $detalle->cantidad,
                            'id_usuario' => auth()->id(),
                            'fecha' => now(),
                            'estado' => true,
                        ]);
                    }
                }
            }

            $venta->update(['situacion' => 'eliminada', 'estado' => false]);
        });

        return back()->with('status', "Venta #{$venta->id} eliminada correctamente.");
    }
}
