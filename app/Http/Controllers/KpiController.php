<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public const PERIODOS = [
        'mes' => 'Este mes',
        'trimestre' => 'Últimos 3 meses',
        'anio' => 'Últimos 12 meses',
    ];

    private const STOCK_BAJO = 5;

    /** Devuelve [clave, desde, hasta, desdeAnterior, hastaAnterior]; el periodo anterior dura lo mismo. */
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

    /** Variación porcentual contra el periodo anterior (null si no hay base). */
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

    public function ventas(Request $request)
    {
        [$periodo, $desde, $hasta, $desdeAnt, $hastaAnt] = $this->rango($request);

        $base = fn ($d, $h) => Venta::where('situacion', 'completada')->whereBetween('fecha', [$d, $h]);

        $total = (float) $base($desde, $hasta)->sum('total');
        $cantidad = $base($desde, $hasta)->count();
        $stats = [
            'total' => $total,
            'cantidad' => $cantidad,
            'ticket' => $cantidad > 0 ? $total / $cantidad : 0,
            'variacion' => $this->variacion($total, (float) $base($desdeAnt, $hastaAnt)->sum('total')),
            'clientes' => $base($desde, $hasta)->distinct()->count('id_cliente'),
        ];

        $porPago = $base($desde, $hasta)
            ->selectRaw('tipo_pago, COUNT(*) as cantidad, SUM(total) as total')
            ->groupBy('tipo_pago')
            ->orderByDesc('total')
            ->get();

        $topProductos = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.id_venta')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.id_producto')
            ->where('ventas.situacion', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('productos.nombre_producto as nombre, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as ingresos')
            ->groupBy('productos.id', 'productos.nombre_producto')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get();

        $topClientes = DB::table('ventas')
            ->join('users', 'users.id', '=', 'ventas.id_cliente')
            ->where('ventas.situacion', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('users.name as nombre, COUNT(ventas.id) as compras, SUM(ventas.total) as total')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        [$etiquetas, $valores] = $this->serieMensual(Venta::class, 12);

        return view('kpis.ventas', [
            'periodos' => self::PERIODOS,
            'periodo' => $periodo,
            'stats' => $stats,
            'porPago' => $porPago,
            'topProductos' => $topProductos,
            'topClientes' => $topClientes,
            'etiquetas' => $etiquetas,
            'valores' => $valores,
        ]);
    }

    public function inventario()
    {
        $activos = Producto::where('estado', true);

        $stats = [
            'valor' => (float) (clone $activos)->sum(DB::raw('stock * precio')),
            'unidades' => (int) (clone $activos)->sum('stock'),
            'agotados' => (clone $activos)->where('stock', 0)->count(),
            'bajos' => (clone $activos)->whereBetween('stock', [1, self::STOCK_BAJO])->count(),
        ];

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
            ->get();

        return view('kpis.inventario', [
            'stats' => $stats,
            'umbral' => self::STOCK_BAJO,
            'reponer' => $reponer,
            'sinRotacion' => $sinRotacion,
            'sinRotacionTotal' => $sinRotacionTotal,
            'porCategoria' => $porCategoria,
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

        $stats = [
            'total' => $total,
            'tasa_entrega' => $total > 0 ? $entregados / $total * 100 : 0,
            'tasa_cancelacion' => $total > 0 ? $cancelados / $total * 100 : 0,
            'monto_en_curso' => (float) Pedido::whereIn('situacion', ['pendiente', 'confirmado'])->sum('total'),
        ];

        $pendientes = Pedido::with('cliente')
            ->where('situacion', 'pendiente')
            ->orderBy('fecha')
            ->limit(5)
            ->get();

        $pendientesTotal = Pedido::where('situacion', 'pendiente')->count();

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

        return view('kpis.pedidos', [
            'periodos' => self::PERIODOS,
            'periodo' => $periodo,
            'stats' => $stats,
            'porEstado' => $porEstado,
            'pendientes' => $pendientes,
            'pendientesTotal' => $pendientesTotal,
            'etiquetas' => $etiquetas,
            'valores' => $valores,
        ]);
    }

    public function compras(Request $request)
    {
        [$periodo, $desde, $hasta] = $this->rango($request);

        $ventas = (float) Venta::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta])->sum('total');
        $comprasQ = Compra::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta]);
        $compras = (float) (clone $comprasQ)->sum('total');

        $stats = [
            'ventas' => $ventas,
            'compras' => $compras,
            'diferencia' => $ventas - $compras,
            'cantidad' => (clone $comprasQ)->count(),
        ];

        $porProveedor = DB::table('compras')
            ->join('proveedores', 'proveedores.id', '=', 'compras.id_proveedor')
            ->where('compras.situacion', 'completada')
            ->whereBetween('compras.fecha', [$desde, $hasta])
            ->selectRaw('proveedores.nombre_empresa as nombre, COUNT(compras.id) as compras, SUM(compras.total) as total')
            ->groupBy('proveedores.id', 'proveedores.nombre_empresa')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        [$etiquetas, $serieVentas] = $this->serieMensual(Venta::class, 6);
        [, $serieCompras] = $this->serieMensual(Compra::class, 6);

        return view('kpis.compras', [
            'periodos' => self::PERIODOS,
            'periodo' => $periodo,
            'stats' => $stats,
            'porProveedor' => $porProveedor,
            'etiquetas' => $etiquetas,
            'serieVentas' => $serieVentas,
            'serieCompras' => $serieCompras,
        ]);
    }
}
