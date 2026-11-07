<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mismos cálculos que App\Http\Controllers\KpiController (web) y MainController::kpisIndex,
 * pero devolviendo JSON. Filtro de periodo: ?periodo=mes|trimestre|anio
 */
class KpiController extends Controller
{
    private const PERIODOS = [
        'mes' => 'Este mes',
        'trimestre' => 'Últimos 3 meses',
        'anio' => 'Últimos 12 meses',
    ];

    private const STOCK_BAJO = 5;

    /** Resumen general (equivale a /kpis de la web). */
    public function resumen()
    {
        $ventasMes = Venta::where('situacion', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);

        $comprasMes = Compra::where('situacion', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);

        [$meses, $totales] = $this->serieMensual(Venta::class, 6);

        return response()->json([
            'stats' => [
                'productos_activos' => Producto::where('estado', true)->count(),
                'usuarios_activos' => User::where('estado', true)->count(),
                'ventas_mes_total' => (float) (clone $ventasMes)->sum('total'),
                'ventas_mes_count' => (clone $ventasMes)->count(),
                'compras_mes_total' => (float) (clone $comprasMes)->sum('total'),
                'compras_mes_count' => (clone $comprasMes)->count(),
                'pedidos_pendientes' => Pedido::where('situacion', 'pendiente')->count(),
            ],
            'stock_bajo' => Producto::with('categoria')
                ->where('estado', true)
                ->orderBy('stock')
                ->take(6)
                ->get()
                ->map(fn (Producto $p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre_producto,
                    'categoria' => $p->categoria?->nombre_categoria,
                    'stock' => (int) $p->stock,
                ])->values(),
            'ultimos_pedidos' => Pedido::with('cliente')
                ->orderByDesc('fecha')
                ->take(6)
                ->get()
                ->map(fn (Pedido $p) => [
                    'id' => $p->id,
                    'cliente' => $p->cliente->name ?? '—',
                    'total' => (float) $p->total,
                    'situacion' => $p->situacion,
                    'fecha' => $p->fecha->format('Y-m-d H:i:s'),
                ])->values(),
            'ultimas_ventas' => Venta::with('cliente')
                ->where('situacion', 'completada')
                ->orderByDesc('fecha')
                ->take(6)
                ->get()
                ->map(fn (Venta $v) => [
                    'id' => $v->id,
                    'cliente' => $v->cliente->name ?? '—',
                    'total' => (float) $v->total,
                    'tipo_pago' => $v->tipo_pago,
                    'fecha' => $v->fecha->format('Y-m-d H:i:s'),
                ])->values(),
            'ventas_ultimos_meses' => ['etiquetas' => $meses, 'valores' => $totales],
            'pedidos_por_estado' => Pedido::where('situacion', '!=', 'eliminado')
                ->selectRaw('situacion, COUNT(*) as total')
                ->groupBy('situacion')
                ->pluck('total', 'situacion'),
            'ventas_por_estado' => Venta::where('situacion', '!=', 'eliminada')
                ->selectRaw('situacion, COUNT(*) as total')
                ->groupBy('situacion')
                ->pluck('total', 'situacion'),
        ]);
    }

    public function ventas(Request $request)
    {
        [$periodo, $desde, $hasta, $desdeAnt, $hastaAnt] = $this->rango($request);

        $base = fn ($d, $h) => Venta::where('situacion', 'completada')->whereBetween('fecha', [$d, $h]);

        $total = (float) $base($desde, $hasta)->sum('total');
        $cantidad = $base($desde, $hasta)->count();

        $porPago = $base($desde, $hasta)
            ->selectRaw('tipo_pago, COUNT(*) as cantidad, SUM(total) as total')
            ->groupBy('tipo_pago')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'tipo_pago' => $r->tipo_pago,
                'tipo_pago_texto' => Venta::TIPOS_PAGO[$r->tipo_pago] ?? 'Sin definir',
                'cantidad' => (int) $r->cantidad,
                'total' => (float) $r->total,
            ])->values();

        $topProductos = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.id_venta')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.id_producto')
            ->where('ventas.situacion', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('productos.nombre_producto as nombre, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as ingresos')
            ->groupBy('productos.id', 'productos.nombre_producto')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['nombre' => $r->nombre, 'unidades' => (int) $r->unidades, 'ingresos' => (float) $r->ingresos])
            ->values();

        $topClientes = DB::table('ventas')
            ->join('users', 'users.id', '=', 'ventas.id_cliente')
            ->where('ventas.situacion', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('users.name as nombre, COUNT(ventas.id) as compras, SUM(ventas.total) as total')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['nombre' => $r->nombre, 'compras' => (int) $r->compras, 'total' => (float) $r->total])
            ->values();

        [$etiquetas, $valores] = $this->serieMensual(Venta::class, 12);

        return response()->json([
            'periodo' => $periodo,
            'periodos' => self::PERIODOS,
            'stats' => [
                'total' => $total,
                'cantidad' => $cantidad,
                'ticket' => $cantidad > 0 ? $total / $cantidad : 0,
                'variacion' => $this->variacion($total, (float) $base($desdeAnt, $hastaAnt)->sum('total')),
                'clientes' => $base($desde, $hasta)->distinct()->count('id_cliente'),
            ],
            'por_tipo_pago' => $porPago,
            'top_productos' => $topProductos,
            'top_clientes' => $topClientes,
            'serie_mensual' => ['etiquetas' => $etiquetas, 'valores' => $valores],
        ]);
    }

    public function inventario()
    {
        $activos = Producto::where('estado', true);

        $reponer = Producto::with('categoria')
            ->where('estado', true)
            ->where('stock', '<=', self::STOCK_BAJO)
            ->orderBy('stock')
            ->orderBy('nombre_producto')
            ->limit(10)
            ->get();

        $vendidosRecientes = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.id_venta')
            ->where('ventas.situacion', 'completada')
            ->where('ventas.fecha', '>=', now()->subDays(60))
            ->select('detalle_ventas.id_producto');

        $sinRotacion = Producto::with('categoria')
            ->where('estado', true)
            ->where('stock', '>', 0)
            ->whereNotIn('id', $vendidosRecientes);

        $sinRotacionTotal = (clone $sinRotacion)->count();
        $sinRotacion = $sinRotacion->orderByDesc('stock')->limit(8)->get();

        $porCategoria = DB::table('productos')
            ->join('categorias', 'categorias.id', '=', 'productos.id_categoria')
            ->where('productos.estado', true)
            ->selectRaw('categorias.nombre_categoria as nombre, SUM(productos.stock) as unidades, SUM(productos.stock * productos.precio) as valor')
            ->groupBy('categorias.id', 'categorias.nombre_categoria')
            ->orderByDesc('valor')
            ->get()
            ->map(fn ($r) => ['nombre' => $r->nombre, 'unidades' => (int) $r->unidades, 'valor' => (float) $r->valor])
            ->values();

        $fila = fn (Producto $p) => [
            'id' => $p->id,
            'nombre' => $p->nombre_producto,
            'categoria' => $p->categoria?->nombre_categoria,
            'stock' => (int) $p->stock,
        ];

        return response()->json([
            'umbral' => self::STOCK_BAJO,
            'stats' => [
                'valor' => (float) (clone $activos)->sum(DB::raw('stock * precio')),
                'unidades' => (int) (clone $activos)->sum('stock'),
                'agotados' => (clone $activos)->where('stock', 0)->count(),
                'bajos' => (clone $activos)->whereBetween('stock', [1, self::STOCK_BAJO])->count(),
            ],
            'reponer' => $reponer->map($fila)->values(),
            'sin_rotacion_total' => $sinRotacionTotal,
            'sin_rotacion' => $sinRotacion->map($fila)->values(),
            'por_categoria' => $porCategoria,
        ]);
    }

    public function pedidos(Request $request)
    {
        [$periodo, $desde, $hasta] = $this->rango($request);

        $porEstado = Pedido::where('situacion', '!=', 'eliminado')
            ->whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('situacion, COUNT(*) as total')
            ->groupBy('situacion')
            ->pluck('total', 'situacion');

        $total = (int) $porEstado->sum();
        $entregados = (int) ($porEstado['entregado'] ?? 0);
        $cancelados = (int) ($porEstado['cancelado'] ?? 0);

        $inicio = now()->subMonthsNoOverflow(5)->startOfMonth();
        $porMes = Pedido::where('situacion', '!=', 'eliminado')
            ->where('fecha', '>=', $inicio)
            ->get(['fecha'])
            ->groupBy(fn ($p) => $p->fecha->format('Y-m'))
            ->map->count();

        $etiquetas = [];
        $valores = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonthsNoOverflow($i);
            $etiquetas[] = ucfirst($mes->translatedFormat('M'));
            $valores[] = $porMes[$mes->format('Y-m')] ?? 0;
        }

        return response()->json([
            'periodo' => $periodo,
            'periodos' => self::PERIODOS,
            'stats' => [
                'total' => $total,
                'tasa_entrega' => $total > 0 ? $entregados / $total * 100 : 0,
                'tasa_cancelacion' => $total > 0 ? $cancelados / $total * 100 : 0,
                'monto_en_curso' => (float) Pedido::whereIn('situacion', ['pendiente', 'confirmado'])->sum('total'),
            ],
            'por_estado' => $porEstado,
            'pendientes_total' => Pedido::where('situacion', 'pendiente')->count(),
            'pendientes' => Pedido::with('cliente')
                ->where('situacion', 'pendiente')
                ->orderBy('fecha')
                ->limit(5)
                ->get()
                ->map(fn (Pedido $p) => [
                    'id' => $p->id,
                    'cliente' => $p->cliente->name ?? '—',
                    'total' => (float) $p->total,
                    'fecha' => $p->fecha->format('Y-m-d H:i:s'),
                ])->values(),
            'serie_mensual' => ['etiquetas' => $etiquetas, 'valores' => $valores],
        ]);
    }

    public function compras(Request $request)
    {
        [$periodo, $desde, $hasta] = $this->rango($request);

        $ventas = (float) Venta::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta])->sum('total');
        $comprasQ = Compra::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta]);
        $compras = (float) (clone $comprasQ)->sum('total');

        $porProveedor = DB::table('compras')
            ->join('proveedores', 'proveedores.id', '=', 'compras.id_proveedor')
            ->where('compras.situacion', 'completada')
            ->whereBetween('compras.fecha', [$desde, $hasta])
            ->selectRaw('proveedores.nombre_empresa as nombre, COUNT(compras.id) as compras, SUM(compras.total) as total')
            ->groupBy('proveedores.id', 'proveedores.nombre_empresa')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['nombre' => $r->nombre, 'compras' => (int) $r->compras, 'total' => (float) $r->total])
            ->values();

        [$etiquetas, $serieVentas] = $this->serieMensual(Venta::class, 6);
        [, $serieCompras] = $this->serieMensual(Compra::class, 6);

        return response()->json([
            'periodo' => $periodo,
            'periodos' => self::PERIODOS,
            'stats' => [
                'ventas' => $ventas,
                'compras' => $compras,
                'diferencia' => $ventas - $compras,
                'cantidad' => (clone $comprasQ)->count(),
            ],
            'por_proveedor' => $porProveedor,
            'serie_mensual' => [
                'etiquetas' => $etiquetas,
                'ventas' => $serieVentas,
                'compras' => $serieCompras,
            ],
        ]);
    }

    /** [clave, desde, hasta, desdeAnterior, hastaAnterior]; el periodo anterior dura lo mismo. */
    private function rango(Request $request): array
    {
        $periodo = $request->query('periodo', 'mes');
        if (! array_key_exists($periodo, self::PERIODOS)) {
            $periodo = 'mes';
        }

        $hasta = now();
        $desde = match ($periodo) {
            'trimestre' => now()->subMonthsNoOverflow(2)->startOfMonth(),
            'anio' => now()->subMonthsNoOverflow(11)->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $duracion = (int) $desde->diffInSeconds($hasta);

        return [$periodo, $desde, $hasta, $desde->copy()->subSeconds($duracion), $desde->copy()];
    }

    private function variacion(float $actual, float $anterior): ?float
    {
        return $anterior > 0 ? (($actual - $anterior) / $anterior) * 100 : null;
    }

    /** Totales mensuales de los últimos $meses, listos para un gráfico. */
    private function serieMensual($modelo, int $meses): array
    {
        $inicio = now()->subMonthsNoOverflow($meses - 1)->startOfMonth();

        $porMes = $modelo::where('situacion', 'completada')
            ->where('fecha', '>=', $inicio)
            ->get(['fecha', 'total'])
            ->groupBy(fn ($f) => $f->fecha->format('Y-m'))
            ->map(fn ($grupo) => (float) $grupo->sum('total'));

        $etiquetas = [];
        $valores = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $mes = now()->subMonthsNoOverflow($i);
            $etiquetas[] = ucfirst($mes->translatedFormat('M'));
            $valores[] = $porMes[$mes->format('Y-m')] ?? 0;
        }

        return [$etiquetas, $valores];
    }
}
