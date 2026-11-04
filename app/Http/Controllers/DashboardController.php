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
        $ventasMes = Venta::where('estado', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);

        $comprasMes = Compra::where('estado', 'completada')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);

        $stats = [
            'productos_activos' => Producto::where('activo', true)->count(),
            'usuarios_activos' => User::where('activo', true)->count(),
            'ventas_mes_total' => (clone $ventasMes)->sum('total'),
            'ventas_mes_count' => (clone $ventasMes)->count(),
            'compras_mes_total' => (clone $comprasMes)->sum('total'),
            'compras_mes_count' => (clone $comprasMes)->count(),
            'pedidos_pendientes' => Pedido::where('estado', 'pendiente')->count(),
        ];

        $stockProductos = Producto::where('activo', true)
            ->orderBy('stock')
            ->take(6)
            ->get();

        $ultimosPedidos = Pedido::with('cliente')
            ->orderByDesc('fecha')
            ->take(6)
            ->get();

        $ultimasVentas = Venta::with('cliente')
            ->where('estado', 'completada')
            ->orderByDesc('fecha')
            ->take(6)
            ->get();

        return view('dashboard', compact('stats', 'stockProductos', 'ultimosPedidos', 'ultimasVentas'));
    }
}
