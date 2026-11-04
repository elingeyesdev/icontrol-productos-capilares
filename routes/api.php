<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ImagenController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\PermisoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/{producto}', [ProductoController::class, 'show']);

Route::get('/imagenes/{path}', [ImagenController::class, 'show'])
    ->where('path', '.*')
    ->name('api.imagen');

Route::middleware(['auth:sanctum', 'activo'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show']);

    Route::get('/productos-todos', [ProductoController::class, 'indexTodos'])->middleware('permission:ver-productos');
    Route::post('/productos', [ProductoController::class, 'store'])->middleware('permission:crear-productos');
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])->middleware('permission:editar-productos');
    Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])->middleware('permission:eliminar-productos');

    Route::middleware('role:administrador|vendedor')->group(function () {
        Route::get('/pedidos-todos', [PedidoController::class, 'indexTodos']);
    });

    Route::middleware('permission:ver-categorias')->group(function () {
        Route::get('/categorias-todos', [CategoriaController::class, 'indexTodos']);
        Route::post('/categorias', [CategoriaController::class, 'store']);
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update']);
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy']);
    });

    Route::middleware('permission:ver-proveedores')->group(function () {
        Route::get('/proveedores', [ProveedorController::class, 'index']);
        Route::post('/proveedores', [ProveedorController::class, 'store']);
        Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update']);
        Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy']);
    });

    Route::middleware('permission:ver-stock')->group(function () {
        Route::get('/stock', [StockController::class, 'index']);
        Route::post('/stock', [StockController::class, 'store']);
        Route::delete('/stock/{movimiento}', [StockController::class, 'destroy']);
    });

    Route::middleware('permission:ver-ventas')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index']);
        Route::get('/ventas/clientes', [VentaController::class, 'clientes']);
        Route::post('/ventas', [VentaController::class, 'store']);
        Route::put('/ventas/{venta}', [VentaController::class, 'update']);
        Route::delete('/ventas/{venta}', [VentaController::class, 'destroy']);
        Route::delete('/ventas/{venta}/eliminar', [VentaController::class, 'eliminar']);
    });

    Route::middleware('permission:ver-compras')->group(function () {
        Route::get('/compras', [CompraController::class, 'index']);
        Route::post('/compras', [CompraController::class, 'store']);
        Route::put('/compras/{compra}', [CompraController::class, 'update']);
        Route::delete('/compras/{compra}', [CompraController::class, 'destroy']);
        Route::delete('/compras/{compra}/eliminar', [CompraController::class, 'eliminar']);
    });

    Route::middleware('permission:ver-usuarios')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::get('/usuarios/clientes', [UsuarioController::class, 'clientes']);
        Route::get('/usuarios/vendedores', [UsuarioController::class, 'vendedores']);
        Route::put('/usuarios/{user}', [UsuarioController::class, 'update']);
        Route::delete('/usuarios/{user}', [UsuarioController::class, 'destroy']);
    });

    Route::middleware('permission:ver-roles')->group(function () {
        Route::get('/roles', [RolController::class, 'index']);
        Route::post('/roles', [RolController::class, 'store']);
        Route::put('/roles/{role}', [RolController::class, 'update']);
        Route::delete('/roles/{role}', [RolController::class, 'destroy']);
        Route::post('/roles/{role}/permisos', [RolController::class, 'syncPermisos']);
    });

    Route::middleware('permission:ver-permisos')->group(function () {
        Route::get('/permisos', [PermisoController::class, 'index']);
        Route::post('/permisos', [PermisoController::class, 'store']);
        Route::put('/permisos/{permission}', [PermisoController::class, 'update']);
        Route::delete('/permisos/{permission}', [PermisoController::class, 'destroy']);
    });

    Route::middleware('permission:ver-usuario-roles')->group(function () {
        Route::get('/rol-usuario', [UsuarioController::class, 'rolIndex']);
        Route::post('/rol-usuario', [UsuarioController::class, 'rolStore']);
        Route::delete('/rol-usuario/{user}', [UsuarioController::class, 'rolDestroy']);
    });
});
