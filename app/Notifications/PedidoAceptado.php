<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Notifications\Notification;

class PedidoAceptado extends Notification
{
    public function __construct(public Pedido $pedido, public Venta $venta)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'pedido_id' => $this->pedido->id,
            'venta_id' => $this->venta->id,
            'total' => (float) $this->venta->total,
            'fecha_pedido' => $this->pedido->fecha->toIso8601String(),
            'fecha_aceptacion' => $this->venta->fecha->toIso8601String(),
        ];
    }
}
