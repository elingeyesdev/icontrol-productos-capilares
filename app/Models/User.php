<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'telefono', 'imagen_usuario', 'estado'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{

    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'estado' => 'boolean',
            'notificaciones_vistas_at' => 'datetime',
        ];
    }

    /**
     * Dirección de entrega del último pedido del usuario (para reponerla
     * en el carrito y que no tenga que escribirla de nuevo).
     */
    public function ultimaDireccionEntrega(): string
    {
        return (string) Pedido::where('id_cliente', $this->id)
            ->whereNotNull('direccion_entrega')
            ->where('direccion_entrega', '!=', '')
            ->orderByDesc('id')
            ->value('direccion_entrega');
    }
}