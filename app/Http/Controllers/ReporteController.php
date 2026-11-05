<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteController extends Controller
{

    public function index()
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

    public function ventas(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

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

    public function ventasPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

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

    public function compras(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

        $query = Compra::with(['proveedor', 'usuario'])
            ->where('situacion', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idProveedor = $request->get('id_proveedor')) {
            $query->where('id_proveedor', $idProveedor);
        }

        $compras = $query->orderByDesc('fecha')->get();
        $totalCompras = $compras->sum('total');
        $proveedores = \App\Models\Proveedor::where('estado', true)->orderBy('nombre_empresa')->get();

        return view('reportes.compras', compact('compras', 'totalCompras', 'proveedores', 'desde', 'hasta'));
    }

    public function comprasPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

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

    public function stockBajo(Request $request)
    {
        [$tipo, $orden] = $this->filtrosMovimientoStock($request);

        $movimientos = $this->consultaMovimientoStock($tipo, $orden)->get();

        return view('reportes.stock', compact('movimientos', 'tipo', 'orden'));
    }

    public function stockBajoPdf(Request $request)
    {
        [$tipo, $orden] = $this->filtrosMovimientoStock($request);

        $movimientos = $this->consultaMovimientoStock($tipo, $orden)->get();

        $pdf = Pdf::loadView('reportes.pdf.stock', compact('movimientos', 'tipo', 'orden'));

        return $pdf->stream('reporte-movimiento-stock-'.now()->format('Y-m-d').'.pdf');
    }

    public function masVendidos(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $limite = (int) $request->get('top', 15);

        $productos = $this->consultaMasVendidos($desde, $hasta, $limite);

        return view('reportes.mas-vendidos', compact('productos', 'desde', 'hasta', 'limite'));
    }

    public function masVendidosPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $limite = (int) $request->get('top', 15);

        $productos = $this->consultaMasVendidos($desde, $hasta, $limite);

        $pdf = Pdf::loadView('reportes.pdf.mas-vendidos', compact('productos', 'desde', 'hasta', 'limite'));

        return $pdf->stream('reporte-mas-vendidos-'.now()->format('Y-m-d').'.pdf');
    }

    public function preciosProveedor(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

        $detalles = $this->consultaPreciosProveedor($request, $desde, $hasta);
        $productos = Producto::orderBy('nombre_producto')->get();
        $proveedores = \App\Models\Proveedor::where('estado', true)->orderBy('nombre_empresa')->get();

        return view('reportes.precios-proveedor', compact('detalles', 'productos', 'proveedores', 'desde', 'hasta'));
    }

    public function preciosProveedorPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

        $detalles = $this->consultaPreciosProveedor($request, $desde, $hasta);

        $pdf = Pdf::loadView('reportes.pdf.precios-proveedor', compact('detalles', 'desde', 'hasta'));

        return $pdf->stream('reporte-precios-proveedor-'.now()->format('Y-m-d').'.pdf');
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

    public function historialPrecios(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

        $historial = $this->consultaHistorialPrecios($request, $desde, $hasta);
        $productos = Producto::orderBy('nombre_producto')->get();

        return view('reportes.historial-precios', compact('historial', 'productos', 'desde', 'hasta'));
    }

    public function historialPreciosPdf(Request $request)
    {
        [$desde, $hasta] = $this->rangoFechas($request);

        $historial = $this->consultaHistorialPrecios($request, $desde, $hasta);

        $pdf = Pdf::loadView('reportes.pdf.historial-precios', compact('historial', 'desde', 'hasta'));

        return $pdf->stream('reporte-historial-precios-'.now()->format('Y-m-d').'.pdf');
    }

    private function consultaHistorialPrecios(Request $request, Carbon $desde, Carbon $hasta)
    {
        $query = \App\Models\HistorialPrecioProducto::with(['producto', 'usuario'])
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($idProducto = $request->get('id_producto')) {
            $query->where('id_producto', $idProducto);
        }

        return $query->orderByDesc('fecha')->get();
    }

    private function filtrosMovimientoStock(Request $request): array
    {
        $tipo = $request->get('tipo');
        $tipo = in_array($tipo, ['entrada', 'salida']) ? $tipo : null;

        $orden = $request->get('orden') === 'za' ? 'za' : 'az';

        return [$tipo, $orden];
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

    private function rangoFechas(Request $request): array
    {
        $desde = $request->get('desde')
            ? Carbon::parse($request->get('desde'))->startOfDay()
            : now()->startOfMonth();

        $hasta = $request->get('hasta')
            ? Carbon::parse($request->get('hasta'))->endOfDay()
            : now()->endOfDay();

        return [$desde, $hasta];
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
}
