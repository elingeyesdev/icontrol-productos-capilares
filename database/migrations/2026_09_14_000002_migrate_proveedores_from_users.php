<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        $mapaIds = [];

        $usuariosProveedor = DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                     ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'proveedor')
            ->select('users.id', 'users.name', 'users.email', 'users.telefono')
            ->get();

        foreach ($usuariosProveedor as $u) {
            $mapaIds[$u->id] = DB::table('proveedores')->insertGetId([
                'nombre_empresa' => $u->name,
                'email' => $u->email,
                'telefono' => $u->telefono,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $huerfanos = DB::table('compras')
            ->whereNotIn('id_proveedor', array_keys($mapaIds) ? array_keys($mapaIds) : [0])
            ->pluck('id_proveedor')
            ->unique();

        foreach ($huerfanos as $userId) {
            if (isset($mapaIds[$userId])) {
                continue;
            }

            $usuario = DB::table('users')->where('id', $userId)->first();

            $mapaIds[$userId] = DB::table('proveedores')->insertGetId([
                'nombre_empresa' => $usuario->name ?? "Proveedor #{$userId}",
                'email' => $usuario->email ?? null,
                'telefono' => $usuario->telefono ?? null,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['id_proveedor']);
        });

        foreach ($mapaIds as $userId => $proveedorId) {
            DB::table('compras')->where('id_proveedor', $userId)->update(['id_proveedor' => $proveedorId]);
        }

        Schema::table('compras', function (Blueprint $table) {
            $table->foreign('id_proveedor')->references('id')->on('proveedores')->restrictOnDelete();
        });

        $rolProveedor = DB::table('roles')->where('name', 'proveedor')->first();

        if ($rolProveedor) {

            DB::table('roles')->where('id', $rolProveedor->id)->delete();
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['id_proveedor']);
            $table->foreign('id_proveedor')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
