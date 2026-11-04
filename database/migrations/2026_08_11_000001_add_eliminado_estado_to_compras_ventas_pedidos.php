<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->enum('estado', ['completada', 'cancelada', 'eliminada'])->default('completada')->change();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('estado', ['completada', 'cancelada', 'eliminada'])->default('completada')->change();
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'confirmado', 'entregado', 'cancelado', 'eliminado'])->default('pendiente')->change();
        });
    }

    public function down(): void
    {
        DB::table('compras')->where('estado', 'eliminada')->update(['estado' => 'cancelada']);
        DB::table('ventas')->where('estado', 'eliminada')->update(['estado' => 'cancelada']);
        DB::table('pedidos')->where('estado', 'eliminado')->update(['estado' => 'cancelado']);

        Schema::table('compras', function (Blueprint $table) {
            $table->enum('estado', ['completada', 'cancelada'])->default('completada')->change();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('estado', ['completada', 'cancelada'])->default('completada')->change();
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'confirmado', 'entregado', 'cancelado'])->default('pendiente')->change();
        });
    }
};
