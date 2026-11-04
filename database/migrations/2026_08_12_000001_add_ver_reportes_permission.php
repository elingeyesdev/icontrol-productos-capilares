<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permiso = Permission::firstOrCreate(['name' => 'ver-reportes']);

        $admin = Role::where('name', 'administrador')->first();
        if ($admin) {
            $admin->givePermissionTo($permiso);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'ver-reportes')->delete();
    }
};
