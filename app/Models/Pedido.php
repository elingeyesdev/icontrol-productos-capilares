<?php

namespace App\Models;

use App\Models\Concerns\TieneDetalleVista;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory, TieneDetalleVista;

    protected $table = 'pedidos';

    protected $fillable = [
        'id_cliente',
        'id_venta',
        'fecha',
        'total',
        'situacion',
        'direccion_entrega',
        'nota',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
        'estado' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_cliente');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'id_pedido');
    }

    /**
     * Datos listos para <x-detalle-panel>.
     * $conCliente = false cuando el propio cliente ve su pedido.
     */
    public function detalleVista(bool $conCliente = true): array
    {
        $this->loadMissing(['cliente', 'detalles.producto', 'venta']);

        [$estado, $tono] = match ($this->situacion) {
            'pendiente' => ['En espera', 'warn'],
            'confirmado' => ['Confirmado', 'info'],
            'entregado' => ['Aceptado', 'ok'],
            'cancelado' => ['Cancelado', 'bad'],
            default => [ucfirst((string) $this->situacion), 'info'],
        };

        // Solo los estados por los que realmente pasó el pedido.
        $pasos = [['texto' => 'Pedido realizado', 'fecha' => $this->fechaVista($this->fecha), 'tono' => 'ok']];

        if ($this->situacion === 'confirmado') {
            $pasos[] = ['texto' => 'Confirmado', 'fecha' => null, 'tono' => 'info'];
        } elseif ($this->situacion === 'entregado') {
            $pasos[] = [
                'texto' => 'Aceptado',
                'fecha' => $this->fechaVista($this->venta?->fecha ?? $this->updated_at),
                'tono' => 'ok',
            ];
        } elseif ($this->situacion === 'cancelado') {
            $pasos[] = ['texto' => 'Cancelado', 'fecha' => $this->fechaVista($this->updated_at), 'tono' => 'bad'];
        }

        $datos = [];
        if ($conCliente) {
            $datos[] = ['icono' => 'bi-person-fill', 'etiqueta' => 'Cliente', 'valor' => $this->cliente->name ?? '—'];
            if ($tel = $this->cliente->telefono ?? null) {
                $datos[] = ['icono' => 'bi-telephone-fill', 'etiqueta' => 'Teléfono', 'valor' => $tel];
            }
        }
        if ($this->direccion_entrega) {
            $datos[] = ['icono' => 'bi-geo-alt-fill', 'etiqueta' => 'Dirección de entrega', 'valor' => $this->direccion_entrega];
        }
        if ($this->nota) {
            $datos[] = ['icono' => 'bi-chat-left-text-fill', 'etiqueta' => 'Nota del cliente', 'valor' => $this->nota];
        }

        return [
            'tipo' => 'Pedido',
            'id' => $this->id,
            'estado' => ['texto' => $estado, 'tono' => $tono],
            'pasos' => $pasos,
            'datos' => $datos,
            'items' => $this->itemsVista($this->detalles),
            'total' => (float) $this->total,
        ];
    }
}
