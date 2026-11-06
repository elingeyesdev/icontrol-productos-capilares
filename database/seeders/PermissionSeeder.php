<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia la caché de Spatie antes de empezar
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Mismos 24 permisos que tiene tu base (respaldo luciane.sql)
        $permisos = [
            'ver-productos', 'crear-productos', 'editar-productos', 'eliminar-productos',
            'ver-categorias', 'crear-categorias', 'editar-categorias', 'eliminar-categorias',
            'ver-stock',
            'ver-usuarios', 'crear-usuarios', 'editar-usuarios', 'eliminar-usuarios',
            'ver-usuario-roles',
            'ver-roles', 'crear-roles', 'editar-roles',
            'ver-permisos',
            'ver-pedidos', 'editar-pedidos',
            'ver-ventas', 'crear-ventas',
            'ver-compras', 'crear-compras',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Administrador: todos los permisos
        $admin = Role::findByName('administrador', 'web');
        $admin->syncPermissions($permisos);

        // Vendedor
        $vendedor = Role::findByName('vendedor', 'web');
        $vendedor->syncPermissions([
            'ver-productos', 'ver-categorias', 'ver-stock',
            'ver-ventas', 'crear-ventas',
            'ver-pedidos', 'editar-pedidos',
        ]);

        // Proveedor
        $proveedor = Role::findByName('proveedor', 'web');
        $proveedor->syncPermissions([
            'ver-productos', 'ver-categorias', 'ver-stock',
            'ver-compras', 'crear-compras',
        ]);

        // Cliente: sin permisos de panel

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}