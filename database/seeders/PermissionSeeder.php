<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'ver-productos', 'crear-productos', 'editar-productos', 'eliminar-productos',
            'ver-categorias', 'crear-categorias', 'editar-categorias', 'eliminar-categorias',
            'ver-proveedores', 'crear-proveedores', 'editar-proveedores', 'eliminar-proveedores',
            'ver-stock',
            'ver-usuarios', 'crear-usuarios', 'editar-usuarios', 'eliminar-usuarios',
            'ver-usuario-roles',
            'ver-roles', 'crear-roles', 'editar-roles',
            'ver-permisos',
            'ver-pedidos', 'editar-pedidos',
            'ver-ventas', 'crear-ventas',
            'ver-compras', 'crear-compras',
            'ver-reportes',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::findByName('administrador');
        $admin->syncPermissions($permisos);

        $vendedor = Role::findByName('vendedor');
        $vendedor->syncPermissions([
            'ver-productos', 'ver-categorias', 'ver-stock',
            'ver-ventas', 'crear-ventas',
        ]);

    }
}