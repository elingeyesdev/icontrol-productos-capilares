<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ImagenController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\PermisoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST (Sanctum) — espejo de lo que hace la web
|--------------------------------------------------------------------------
| Los permisos de cada ruta son los mismos que usa routes/web.php, así que
| un usuario puede hacer en la app exactamente lo mismo que en el panel.
*/

// ── Público ─────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/{producto}', [ProductoController::class, 'show']);

Route::get('/imagenes/{path}', [ImagenController::class, 'show'])
    ->where('path', '.*')
    ->name('api.imagen');

// ── Autenticado ─────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'activo'])->group(function () {

    // Sesión y perfil
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // match put|post: con foto (multipart) Flutter debe usar POST, PHP no lee multipart en PUT.
    Route::match(['put', 'post'], '/perfil', [PerfilController::class, 'update']);
    Route::put('/perfil/password', [PerfilController::class, 'password']);
    Route::delete('/perfil', [PerfilController::class, 'destroy']);

    // Notificaciones (cliente: pedidos aceptados · admin/vendedor: pedidos pendientes)
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::post('/notificaciones/leidas', [NotificacionController::class, 'leidas']);
    Route::post('/notificaciones/{id}/leida', [NotificacionController::class, 'leida']);
    Route::post('/notificaciones/vistas', [NotificacionController::class, 'vistas'])
        ->middleware('role:administrador|vendedor');

    // Pedidos del propio cliente
    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/pedidos/ultima-direccion', [PedidoController::class, 'ultimaDireccion']);
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show']);

    // Pedidos (panel admin/vendedor)
    Route::middleware('permission:ver-pedidos')->group(function () {
        Route::get('/pedidos-todos', [PedidoController::class, 'indexTodos']);
        Route::get('/pedidos-todos/{pedido}', [PedidoController::class, 'showTodos']);

        Route::middleware('permission:editar-pedidos')->group(function () {
            Route::post('/pedidos-todos', [PedidoController::class, 'storeTodos']);
            Route::put('/pedidos-todos/{pedido}', [PedidoController::class, 'updateTodos']);
            Route::put('/pedidos-todos/{pedido}/estado', [PedidoController::class, 'updateEstado']);
            Route::delete('/pedidos-todos/{pedido}', [PedidoController::class, 'destroyTodos']);
        });
    });

    // Productos
    Route::get('/productos-todos', [ProductoController::class, 'indexTodos'])->middleware('permission:ver-productos');
    Route::post('/productos', [ProductoController::class, 'store'])->middleware('permission:crear-productos');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])->middleware('permission:editar-productos');
    Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])->middleware('permission:eliminar-productos');

    // Categorías
    Route::middleware('permission:ver-categorias')->group(function () {
        Route::get('/categorias-todos', [CategoriaController::class, 'indexTodos']);
        Route::post('/categorias', [CategoriaController::class, 'store'])->middleware('permission:crear-categorias');
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->middleware('permission:editar-categorias');
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->middleware('permission:eliminar-categorias');
    });

    // Proveedores
    Route::middleware('permission:ver-proveedores')->group(function () {
        Route::get('/proveedores', [ProveedorController::class, 'index']);
        Route::post('/proveedores', [ProveedorController::class, 'store'])->middleware('permission:crear-proveedores');
        Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])->middleware('permission:editar-proveedores');
        Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->middleware('permission:eliminar-proveedores');
    });

    // Stock (eliminar movimiento: solo administrador, igual que la web)
    Route::middleware('permission:ver-stock')->group(function () {
        Route::get('/stock', [StockController::class, 'index']);
        Route::post('/stock', [StockController::class, 'store']);
        Route::delete('/stock/{movimiento}', [StockController::class, 'destroy'])->middleware('role:administrador');
    });

    // Ventas (cancelar/eliminar: solo administrador y solo ventas manuales)
    Route::middleware('permission:ver-ventas')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index']);
        Route::get('/ventas/clientes', [VentaController::class, 'clientes']);
        Route::get('/ventas/tipos-pago', [VentaController::class, 'tiposPago']);
        Route::get('/ventas/{venta}', [VentaController::class, 'show']);
        Route::post('/ventas', [VentaController::class, 'store'])->middleware('permission:crear-ventas');
        Route::put('/ventas/{venta}', [VentaController::class, 'update'])->middleware('permission:crear-ventas');
        Route::delete('/ventas/{venta}', [VentaController::class, 'destroy'])->middleware('role:administrador');
        Route::delete('/ventas/{venta}/eliminar', [VentaController::class, 'eliminar'])->middleware('role:administrador');
    });

    // Compras
    Route::middleware('permission:ver-compras')->group(function () {
        Route::get('/compras', [CompraController::class, 'index']);
        Route::get('/compras/proveedores', [CompraController::class, 'proveedores']);
        Route::get('/compras/{compra}', [CompraController::class, 'show']);
        Route::middleware('permission:crear-compras')->group(function () {
            Route::post('/compras', [CompraController::class, 'store']);
            Route::put('/compras/{compra}', [CompraController::class, 'update']);
            Route::delete('/compras/{compra}', [CompraController::class, 'destroy']);
            Route::delete('/compras/{compra}/eliminar', [CompraController::class, 'eliminar']);
        });
    });

    // KPIs (?periodo=mes|trimestre|anio)
    Route::middleware('permission:ver-kpis')->prefix('kpis')->group(function () {
        Route::get('/', [KpiController::class, 'resumen']);
        Route::get('/ventas', [KpiController::class, 'ventas']);
        Route::get('/inventario', [KpiController::class, 'inventario']);
        Route::get('/pedidos', [KpiController::class, 'pedidos']);
        Route::get('/compras', [KpiController::class, 'compras']);
    });

    // Reportes: JSON y su PDF (?desde=YYYY-MM-DD&hasta=YYYY-MM-DD)
    Route::middleware('permission:ver-reportes')->prefix('reportes')->group(function () {
        Route::get('/', [ReporteController::class, 'index']);

        Route::get('/ventas', [ReporteController::class, 'ventas']);
        Route::get('/ventas/pdf', [ReporteController::class, 'ventasPdf']);

        Route::get('/compras', [ReporteController::class, 'compras']);
        Route::get('/compras/pdf', [ReporteController::class, 'comprasPdf']);

        Route::get('/stock-bajo', [ReporteController::class, 'stock']);
        Route::get('/stock-bajo/pdf', [ReporteController::class, 'stockPdf']);

        Route::get('/mas-vendidos', [ReporteController::class, 'masVendidos']);
        Route::get('/mas-vendidos/pdf', [ReporteController::class, 'masVendidosPdf']);

        Route::get('/precios-proveedor', [ReporteController::class, 'preciosProveedor']);
        Route::get('/precios-proveedor/pdf', [ReporteController::class, 'preciosProveedorPdf']);

        Route::get('/historial-precios', [ReporteController::class, 'historialPrecios']);
        Route::get('/historial-precios/pdf', [ReporteController::class, 'historialPreciosPdf']);
    });

    // Usuarios
    Route::middleware('permission:ver-usuarios')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::get('/usuarios/clientes', [UsuarioController::class, 'clientes']);
        Route::get('/usuarios/vendedores', [UsuarioController::class, 'vendedores']);
        Route::put('/usuarios/{user}', [UsuarioController::class, 'update'])->middleware('permission:editar-usuarios');
        Route::delete('/usuarios/{user}', [UsuarioController::class, 'destroy'])->middleware('permission:eliminar-usuarios');
    });

    // Roles
    Route::middleware('permission:ver-roles')->group(function () {
        Route::get('/roles', [RolController::class, 'index']);
        Route::post('/roles', [RolController::class, 'store'])->middleware('permission:crear-roles');
        Route::put('/roles/{role}', [RolController::class, 'update'])->middleware('permission:editar-roles');
        Route::delete('/roles/{role}', [RolController::class, 'destroy'])->middleware('permission:editar-roles');
        Route::post('/roles/{role}/permisos', [RolController::class, 'syncPermisos'])->middleware('permission:editar-roles');
    });

    // Permisos
    Route::middleware('permission:ver-permisos')->group(function () {
        Route::get('/permisos', [PermisoController::class, 'index']);
        Route::post('/permisos', [PermisoController::class, 'store']);
        Route::put('/permisos/{permission}', [PermisoController::class, 'update']);
        Route::delete('/permisos/{permission}', [PermisoController::class, 'destroy']);
    });

    // Asignación de roles a usuarios
    Route::middleware('permission:ver-usuario-roles')->group(function () {
        Route::get('/rol-usuario', [UsuarioController::class, 'rolIndex']);
        Route::post('/rol-usuario', [UsuarioController::class, 'rolStore']);
        Route::delete('/rol-usuario/{user}', [UsuarioController::class, 'rolDestroy']);
    });
});
