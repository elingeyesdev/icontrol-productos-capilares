<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'notificaciones_vistas_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('notificaciones_vistas_at')->nullable();
            });
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $vendedor = Role::where('name', 'vendedor')->first();
        if ($vendedor) {
            foreach (['ver-pedidos', 'editar-pedidos'] as $nombre) {
                $vendedor->givePermissionTo(Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']));
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'notificaciones_vistas_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('notificaciones_vistas_at');
            });
        }
    }
};
