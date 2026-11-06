<?php

use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/carrito', [MainController::class, 'carritoStore'])->name('carrito.store');
Route::put('/carrito/{producto}', [MainController::class, 'carritoUpdate'])->name('carrito.update');
Route::delete('/carrito/{producto}', [MainController::class, 'carritoDestroy'])->name('carrito.destroy');

Route::get('/dashboard', [MainController::class, 'dashboardIndex'])
    ->middleware(['auth', 'activo', 'verified', 'role:administrador'])
    ->name('dashboard');

Route::middleware(['auth', 'activo'])->group(function () {
    Route::get('/profile', [MainController::class, 'profileEdit'])->name('profile.edit');
    Route::patch('/profile', [MainController::class, 'profileUpdate'])->name('profile.update');
    Route::delete('/profile', [MainController::class, 'profileDestroy'])->name('profile.destroy');

    Route::post('/pedidos/checkout', [MainController::class, 'pedidoCheckout'])->name('pedidos.checkout');
    Route::get('/mis-pedidos', [MainController::class, 'pedidoMisPedidos'])->name('pedidos.mis');
    Route::post('/notificaciones/vistas', [MainController::class, 'notificacionesVistas'])
        ->middleware('role:administrador|vendedor')
        ->name('notificaciones.vistas');
});

Route::middleware(['auth', 'activo'])->group(function () {

    Route::middleware('permission:ver-usuarios')->group(function () {
        Route::get('/usuarios', [MainController::class, 'usuarioIndex'])->name('usuarios.index');
        Route::get('/usuarios/clientes', fn (\Illuminate\Http\Request $r) => app(MainController::class)->usuarioIndex($r, 'cliente'))->name('usuarios.clientes');
        Route::get('/usuarios/vendedores', fn (\Illuminate\Http\Request $r) => app(MainController::class)->usuarioIndex($r, 'vendedor'))->name('usuarios.vendedores');

        Route::put('/usuarios/{user}', [MainController::class, 'usuarioUpdate'])->middleware('permission:editar-usuarios')->name('usuarios.update');
        Route::delete('/usuarios/{user}', [MainController::class, 'usuarioDestroy'])->middleware('permission:eliminar-usuarios')->name('usuarios.destroy');
    });

    Route::middleware('permission:ver-roles')->group(function () {
        Route::get('/roles', [MainController::class, 'rolIndex'])->name('roles.index');
        Route::post('/roles', [MainController::class, 'rolStore'])->middleware('permission:crear-roles')->name('roles.store');
        Route::put('/roles/{role}', [MainController::class, 'rolUpdate'])->middleware('permission:editar-roles')->name('roles.update');
        Route::delete('/roles/{role}', [MainController::class, 'rolDestroy'])->middleware('permission:editar-roles')->name('roles.destroy');
        Route::post('/roles/{role}/permisos', [MainController::class, 'rolSyncPermissions'])->middleware('permission:editar-roles')->name('roles.permisos');
    });

    Route::middleware('permission:ver-permisos')->group(function () {
        Route::get('/permisos', [MainController::class, 'permisoIndex'])->name('permisos.index');
        Route::post('/permisos', [MainController::class, 'permisoStore'])->name('permisos.store');
        Route::put('/permisos/{permission}', [MainController::class, 'permisoUpdate'])->name('permisos.update');
        Route::delete('/permisos/{permission}', [MainController::class, 'permisoDestroy'])->name('permisos.destroy');
    });

    Route::middleware('permission:ver-usuario-roles')->group(function () {
        Route::get('/rol-usuario', [MainController::class, 'usuarioRolIndex'])->name('usuario-roles.index');
        Route::post('/rol-usuario', [MainController::class, 'usuarioRolStore'])->name('usuario-roles.store');
        Route::delete('/rol-usuario/{user}', [MainController::class, 'usuarioRolDestroy'])->name('usuario-roles.destroy');
    });

    Route::middleware('permission:ver-pedidos')->group(function () {
        Route::get('/pedidos', [MainController::class, 'pedidoIndex'])->name('pedidos.index');
        Route::post('/pedidos', [MainController::class, 'pedidoStore'])->middleware('permission:editar-pedidos')->name('pedidos.store');
        Route::put('/pedidos/{pedido}', [MainController::class, 'pedidoUpdate'])->middleware('permission:editar-pedidos')->name('pedidos.update');
        Route::put('/pedidos/{pedido}/estado', [MainController::class, 'pedidoUpdateEstado'])->middleware('permission:editar-pedidos')->name('pedidos.estado');
        Route::delete('/pedidos/{pedido}', [MainController::class, 'pedidoDestroy'])->middleware('permission:editar-pedidos')->name('pedidos.destroy');
    });

    Route::middleware('permission:ver-productos')->group(function () {
        Route::get('/productos', [MainController::class, 'productoIndex'])->name('productos.index');
        Route::post('/productos', [MainController::class, 'productoStore'])->middleware('permission:crear-productos')->name('productos.store');
        Route::put('/productos/{producto}', [MainController::class, 'productoUpdate'])->middleware('permission:editar-productos')->name('productos.update');
        Route::delete('/productos/{producto}', [MainController::class, 'productoDestroy'])->middleware('permission:eliminar-productos')->name('productos.destroy');
    });

    Route::middleware('permission:ver-categorias')->group(function () {
        Route::get('/categorias', [MainController::class, 'categoriaIndex'])->name('categorias.index');
        Route::post('/categorias', [MainController::class, 'categoriaStore'])->middleware('permission:crear-categorias')->name('categorias.store');
        Route::put('/categorias/{categoria}', [MainController::class, 'categoriaUpdate'])->middleware('permission:editar-categorias')->name('categorias.update');
        Route::delete('/categorias/{categoria}', [MainController::class, 'categoriaDestroy'])->middleware('permission:eliminar-categorias')->name('categorias.destroy');
    });

    Route::middleware('permission:ver-proveedores')->group(function () {
        Route::get('/proveedores', [MainController::class, 'proveedorIndex'])->name('proveedores.index');
        Route::post('/proveedores', [MainController::class, 'proveedorStore'])->middleware('permission:crear-proveedores')->name('proveedores.store');
        Route::put('/proveedores/{proveedor}', [MainController::class, 'proveedorUpdate'])->middleware('permission:editar-proveedores')->name('proveedores.update');
        Route::delete('/proveedores/{proveedor}', [MainController::class, 'proveedorDestroy'])->middleware('permission:eliminar-proveedores')->name('proveedores.destroy');
    });

    Route::middleware('permission:ver-stock')->group(function () {
        Route::get('/stock', [MainController::class, 'stockIndex'])->name('stock.index');
        Route::post('/stock', [MainController::class, 'stockStore'])->name('stock.store');
        Route::delete('/stock/{movimiento}', [MainController::class, 'stockDestroy'])->middleware('role:administrador')->name('stock.destroy');
    });

    Route::middleware('permission:ver-ventas')->group(function () {
        Route::get('/ventas', [MainController::class, 'ventaIndex'])->name('ventas.index');
        Route::post('/ventas', [MainController::class, 'ventaStore'])->middleware('permission:crear-ventas')->name('ventas.store');
        Route::put('/ventas/{venta}', [MainController::class, 'ventaUpdate'])->middleware('permission:crear-ventas')->name('ventas.update');
        Route::delete('/ventas/{venta}', [MainController::class, 'ventaDestroy'])->middleware('role:administrador')->name('ventas.destroy');
        Route::delete('/ventas/{venta}/eliminar', [MainController::class, 'ventaEliminar'])->middleware('role:administrador')->name('ventas.eliminar');
    });

    Route::middleware('permission:ver-compras')->group(function () {
        Route::get('/compras', [MainController::class, 'compraIndex'])->name('compras.index');
        Route::post('/compras', [MainController::class, 'compraStore'])->middleware('permission:crear-compras')->name('compras.store');
        Route::put('/compras/{compra}', [MainController::class, 'compraUpdate'])->middleware('permission:crear-compras')->name('compras.update');
        Route::delete('/compras/{compra}', [MainController::class, 'compraDestroy'])->middleware('permission:crear-compras')->name('compras.destroy');
        Route::delete('/compras/{compra}/eliminar', [MainController::class, 'compraEliminar'])->middleware('permission:crear-compras')->name('compras.eliminar');
    });

    Route::middleware('permission:ver-reportes')->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [MainController::class, 'reporteIndex'])->name('index');

        Route::get('/ventas', [MainController::class, 'reporteVentas'])->name('ventas');
        Route::get('/ventas/pdf', [MainController::class, 'reporteVentasPdf'])->name('ventas.pdf');

        Route::get('/compras', [MainController::class, 'reporteCompras'])->name('compras');
        Route::get('/compras/pdf', [MainController::class, 'reporteComprasPdf'])->name('compras.pdf');

        Route::get('/stock-bajo', [MainController::class, 'reporteStockBajo'])->name('stock');
        Route::get('/stock-bajo/pdf', [MainController::class, 'reporteStockBajoPdf'])->name('stock.pdf');

        Route::get('/mas-vendidos', [MainController::class, 'reporteMasVendidos'])->name('mas-vendidos');
        Route::get('/mas-vendidos/pdf', [MainController::class, 'reporteMasVendidosPdf'])->name('mas-vendidos.pdf');

        Route::get('/precios-proveedor', [MainController::class, 'reportePreciosProveedor'])->name('precios-proveedor');
        Route::get('/precios-proveedor/pdf', [MainController::class, 'reportePreciosProveedorPdf'])->name('precios-proveedor.pdf');

        Route::get('/historial-precios', [MainController::class, 'reporteHistorialPrecios'])->name('historial-precios');
        Route::get('/historial-precios/pdf', [MainController::class, 'reporteHistorialPreciosPdf'])->name('historial-precios.pdf');
    });
});

require __DIR__.'/auth.php';
