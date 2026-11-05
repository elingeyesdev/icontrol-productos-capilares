<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $conSituacion = ['ventas', 'compras', 'pedidos'];

    private array $soloRenombrar = ['users', 'categorias', 'productos', 'proveedores', 'movimientos_stock'];

    private array $nuevas = ['detalle_ventas', 'detalle_compras', 'detalle_pedidos', 'historial_precios_productos'];

    public function up(): void
    {
        foreach ($this->conSituacion as $tabla) {
            if (Schema::hasColumn($tabla, 'estado') && ! Schema::hasColumn($tabla, 'situacion')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->renameColumn('estado', 'situacion');
                });
            }

            if (Schema::hasColumn($tabla, 'activo') && ! Schema::hasColumn($tabla, 'estado')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->renameColumn('activo', 'estado');
                });
            }
        }

        foreach ($this->soloRenombrar as $tabla) {
            if (Schema::hasColumn($tabla, 'activo') && ! Schema::hasColumn($tabla, 'estado')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->renameColumn('activo', 'estado');
                });
            }
        }

        foreach ($this->nuevas as $tabla) {
            if (! Schema::hasColumn($tabla, 'estado')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->boolean('estado')->default(true);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->nuevas as $tabla) {
            if (Schema::hasColumn($tabla, 'estado')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropColumn('estado');
                });
            }
        }

        foreach ($this->soloRenombrar as $tabla) {
            if (Schema::hasColumn($tabla, 'estado') && ! Schema::hasColumn($tabla, 'activo')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->renameColumn('estado', 'activo');
                });
            }
        }

        foreach ($this->conSituacion as $tabla) {
            if (Schema::hasColumn($tabla, 'estado') && ! Schema::hasColumn($tabla, 'activo')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->renameColumn('estado', 'activo');
                });
            }

            if (Schema::hasColumn($tabla, 'situacion') && ! Schema::hasColumn($tabla, 'estado')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->renameColumn('situacion', 'estado');
                });
            }
        }
    }
};
