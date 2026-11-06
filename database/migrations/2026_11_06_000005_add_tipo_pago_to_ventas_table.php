<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ventas', 'tipo_pago')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->string('tipo_pago', 20)->nullable()->after('total');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'tipo_pago')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('tipo_pago');
            });
        }
    }
};
