<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\HistorialPrecioProducto;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Venta;
use App\Support\Imagen;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Reportes del panel web en JSON (y su PDF, con las mismas vistas reportes.pdf.*).
 * Filtros: ?desde=YYYY-MM-DD&hasta=YYYY-MM-DD (por defecto, el mes en curso).
 */
class ReporteController extends Controller
{
    public function index()
    {
        $desde = now()->startOfMonth();
        $hasta = now()->endOfMonth();

        $top = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.id_venta')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.id_producto')
            ->where('ventas.situacion', 'completada')
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('productos.nombre_producto, SUM(detalle_ventas.cantidad) as total_cantidad')
            ->groupBy('productos.id', 'productos.nombre_producto')
            ->orderByDesc('total_cantidad')
            ->first();

        return response()->json([
            'total_ventas' => (float) Venta::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta])->sum('total'),
            'total_compras' => (float) Compra::where('situacion', 'completada')->whereBetween('fecha', [$desde, $hasta])->sum('total'),
            'productos_stock_bajo' => Producto::where('estado', true)->where('stock', '<=', 10)->count(),
            'producto_top' => $top ? [
                'nombre' => $top->nombre_producto,
                'unidades' => (int) $top->total_cantidad,
            ] : null,
        ]);
    }

    // ── Ventas ──────────────────────────────────────────────────────────

    public function ventas(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $ventas = $this->consultaVentas($request, $desde, $hasta);

        return response()->json([
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'total' => (float) $ventas->sum('total'),
            'cantidad' => $ventas->count(),
            'ventas' => $ventas->map(fn (Venta $v) => [
                'id' => $v->id,
                'fecha' => $v->fecha->format('Y-m-d H:i:s'),
                'cliente' => $v->cliente->name ?? '—',
                'vendedor' => $v->vendedor->name ?? '—',
                'tipo_pago' => $v->tipo_pago,
                'tipo_pago_texto' => $v->tipoPagoEtiqueta(),
                'total' => (float) $v->total,
            ])->values(),
        ]);
    }

    public function ventasPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $ventas = $this->consultaVentas($request, $desde, $hasta);
        $totalVentas = $ventas->sum('total');

        return Pdf::loadView('reportes.pdf.ventas', compact('ventas', 'totalVentas', 'desde', 'hasta'))
            ->stream('reporte-ventas-'.now()->format('Y-m-d').'.pdf');
    }

    private function consultaVentas(Request $request, Carbon $desde, Carbon $hasta)
    {
        $query = Venta::with(['cliente', 'vendedor'])
            ->where('situacion', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idCliente = $request->get('id_cliente')) {
            $query->where('id_cliente', $idCliente);
        }

        return $query->orderByDesc('fecha')->get();
    }

    // ── Compras ─────────────────────────────────────────────────────────

    public function compras(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $compras = $this->consultaCompras($request, $desde, $hasta);

        return response()->json([
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'total' => (float) $compras->sum('total'),
            'cantidad' => $compras->count(),
            'compras' => $compras->map(fn (Compra $c) => [
                'id' => $c->id,
                'fecha' => $c->fecha->format('Y-m-d H:i:s'),
                'proveedor' => $c->proveedor->nombre_empresa ?? '—',
                'usuario' => $c->usuario->name ?? '—',
                'total' => (float) $c->total,
            ])->values(),
        ]);
    }

    public function comprasPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $compras = $this->consultaCompras($request, $desde, $hasta);
        $totalCompras = $compras->sum('total');

        return Pdf::loadView('reportes.pdf.compras', compact('compras', 'totalCompras', 'desde', 'hasta'))
            ->stream('reporte-compras-'.now()->format('Y-m-d').'.pdf');
    }

    private function consultaCompras(Request $request, Carbon $desde, Carbon $hasta)
    {
        $query = Compra::with(['proveedor', 'usuario'])
            ->where('situacion', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idProveedor = $request->get('id_proveedor')) {
            $query->where('id_proveedor', $idProveedor);
        }

        return $query->orderByDesc('fecha')->get();
    }

    // ── Movimientos de stock (en la web se llama "stock-bajo") ──────────

    public function stock(Request $request)
    {
        [$tipo, $orden] = $this->filtrosMovimientoStock($request);
        $movimientos = $this->consultaMovimientoStock($tipo, $orden)->get();

        return response()->json([
            'tipo' => $tipo,
            'orden' => $orden,
            'cantidad' => $movimientos->count(),
            'movimientos' => $movimientos->map(fn (MovimientoStock $m) => [
                'id' => $m->id,
                'fecha' => $m->fecha->format('Y-m-d H:i:s'),
                'producto' => $m->producto->nombre_producto ?? 'Producto eliminado',
                'tipo' => $m->tipo,
                'cantidad' => (int) $m->cantidad,
                'usuario' => $m->usuario->name ?? '—',
            ])->values(),
        ]);
    }

    public function stockPdf(Request $request)
    {
        [$tipo, $orden] = $this->filtrosMovimientoStock($request);
        $movimientos = $this->consultaMovimientoStock($tipo, $orden)->get();

        return Pdf::loadView('reportes.pdf.stock', compact('movimientos', 'tipo', 'orden'))
            ->stream('reporte-movimiento-stock-'.now()->format('Y-m-d').'.pdf');
    }

    private function filtrosMovimientoStock(Request $request): array
    {
        $tipo = $request->get('tipo');
        $tipo = in_array($tipo, ['entrada', 'salida']) ? $tipo : null;

        return [$tipo, $request->get('orden') === 'za' ? 'za' : 'az'];
    }

    private function consultaMovimientoStock(?string $tipo, string $orden)
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

    // ── Más vendidos (?top=15, máx. 100) ────────────────────────────────

    public function masVendidos(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $limite = max(1, min(100, (int) $request->get('top', 15)));
        $productos = $this->consultaMasVendidos($desde, $hasta, $limite);

        return response()->json([
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'top' => $limite,
            'productos' => $productos->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre_producto,
                'imagen' => Imagen::url($p->imagen_producto),
                'unidades' => (int) $p->total_cantidad,
                'ingresos' => (float) $p->total_ingresos,
            ])->values(),
        ]);
    }

    public function masVendidosPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $limite = max(1, min(100, (int) $request->get('top', 15)));
        $productos = $this->consultaMasVendidos($desde, $hasta, $limite);

        return Pdf::loadView('reportes.pdf.mas-vendidos', compact('productos', 'desde', 'hasta', 'limite'))
            ->stream('reporte-mas-vendidos-'.now()->format('Y-m-d').'.pdf');
    }

    private function consultaMasVendidos(Carbon $desde, Carbon $hasta, int $limite)
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

    // ── Precios por proveedor (?id_producto=&id_proveedor=) ─────────────

    public function preciosProveedor(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $detalles = $this->consultaPreciosProveedor($request, $desde, $hasta);

        return response()->json([
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'cantidad' => $detalles->count(),
            'detalles' => $detalles->map(fn (DetalleCompra $d) => [
                'id_compra' => $d->id_compra,
                'fecha' => $d->compra?->fecha?->format('Y-m-d H:i:s'),
                'proveedor' => $d->compra?->proveedor?->nombre_empresa ?? '—',
                'producto' => $d->producto->nombre_producto ?? 'Producto eliminado',
                'cantidad' => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'subtotal' => (float) $d->subtotal,
            ])->values(),
        ]);
    }

    public function preciosProveedorPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $detalles = $this->consultaPreciosProveedor($request, $desde, $hasta);

        return Pdf::loadView('reportes.pdf.precios-proveedor', compact('detalles', 'desde', 'hasta'))
            ->stream('reporte-precios-proveedor-'.now()->format('Y-m-d').'.pdf');
    }

    private function consultaPreciosProveedor(Request $request, Carbon $desde, Carbon $hasta)
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

    // ── Historial de precios de productos (?id_producto=) ───────────────

    public function historialPrecios(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $historial = $this->consultaHistorialPrecios($request, $desde, $hasta);

        return response()->json([
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'cantidad' => $historial->count(),
            'historial' => $historial->map(fn (HistorialPrecioProducto $h) => [
                'id' => $h->id,
                'fecha' => $h->fecha->format('Y-m-d H:i:s'),
                'id_producto' => $h->id_producto,
                'producto' => $h->producto->nombre_producto ?? 'Producto eliminado',
                'precio_anterior' => (float) $h->precio_anterior,
                'precio_nuevo' => (float) $h->precio_nuevo,
                'usuario' => $h->usuario->name ?? '—',
            ])->values(),
        ]);
    }

    public function historialPreciosPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $historial = $this->consultaHistorialPrecios($request, $desde, $hasta);

        return Pdf::loadView('reportes.pdf.historial-precios', compact('historial', 'desde', 'hasta'))
            ->stream('reporte-historial-precios-'.now()->format('Y-m-d').'.pdf');
    }

    private function consultaHistorialPrecios(Request $request, Carbon $desde, Carbon $hasta)
    {
        $query = HistorialPrecioProducto::with(['producto', 'usuario'])
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idProducto = $request->get('id_producto')) {
            $query->where('id_producto', $idProducto);
        }

        return $query->orderByDesc('fecha')->get();
    }

    // ── Rango de fechas (idéntico al de la web) ─────────────────────────

    private function rangoFechas(Request $request): array
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
}
