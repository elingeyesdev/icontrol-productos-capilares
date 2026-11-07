<?php

namespace App\Models\Concerns;

use Illuminate\Support\Collection;

/**
 * Helpers compartidos por Pedido, Venta y Compra para armar el payload
 * que consume el componente <x-detalle-panel>.
 */
trait TieneDetalleVista
{
    protected function itemsVista(Collection $detalles): array
    {
        return $detalles->map(fn ($d) => [
            'nombre' => $d->producto->nombre_producto ?? 'Producto eliminado',
            'imagen' => $d->producto && $d->producto->imagen_producto
                ? asset('images/' . $d->producto->imagen_producto)
                : null,
            'cantidad' => (int) $d->cantidad,
            'precio' => (float) $d->precio_unitario,
            'subtotal' => (float) $d->subtotal,
        ])->values()->all();
    }

    protected function fechaVista($fecha): ?string
    {
        return $fecha ? $fecha->format('d/m/Y H:i') : null;
    }
}
