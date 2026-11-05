<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;

class DashboardController extends Controller
{
    public function index()
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

        return view('dashboard', compact('stats', 'stockProductos', 'ultimosPedidos', 'ultimasVentas'));
    }
}
