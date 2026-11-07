<?php

namespace App\Models;

use App\Models\Concerns\TieneDetalleVista;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    use HasFactory, TieneDetalleVista;

    protected $table = 'ventas';

    public const TIPOS_PAGO = [
        'efectivo' => 'Efectivo',
        'qr' => 'QR',
        'transferencia' => 'Transferencia',
    ];

    protected $fillable = [
        'id_cliente',
        'id_vendedor',
        'fecha',
        'total',
        'tipo_pago',
        'situacion',
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

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_vendedor');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'id_venta');
    }

    public function pedido(): HasOne
    {
        return $this->hasOne(Pedido::class, 'id_venta');
    }

    public function tipoPagoEtiqueta(): ?string
    {
        return self::TIPOS_PAGO[$this->tipo_pago] ?? null;
    }

    public function esManual(): bool
    {
        return $this->pedido === null;
    }

    /** Datos listos para <x-detalle-panel>. */
    public function detalleVista(): array
    {
        $this->loadMissing(['cliente', 'vendedor', 'detalles.producto', 'pedido']);

        [$estado, $tono] = match ($this->situacion) {
            'completada' => ['Completada', 'ok'],
            'cancelada' => ['Cancelada', 'bad'],
            default => [ucfirst((string) $this->situacion), 'info'],
        };

        $pasos = [['texto' => 'Venta realizada', 'fecha' => $this->fechaVista($this->fecha), 'tono' => 'ok']];
        if ($this->situacion === 'cancelada') {
            $pasos[] = ['texto' => 'Cancelada', 'fecha' => $this->fechaVista($this->updated_at), 'tono' => 'bad'];
        }

        $datos = [
            ['icono' => 'bi-person-fill', 'etiqueta' => 'Cliente', 'valor' => $this->cliente->name ?? '—'],
            ['icono' => 'bi-person-badge-fill', 'etiqueta' => 'Vendedor', 'valor' => $this->vendedor->name ?? '—'],
        ];
        if ($pago = $this->tipoPagoEtiqueta()) {
            $datos[] = ['icono' => 'bi-wallet2', 'etiqueta' => 'Tipo de pago', 'valor' => $pago];
        }
        $datos[] = [
            'icono' => 'bi-bag-check-fill',
            'etiqueta' => 'Origen',
            'valor' => $this->pedido ? 'Pedido #' . $this->pedido->id : 'Venta manual',
        ];

        return [
            'tipo' => 'Venta',
            'id' => $this->id,
            'estado' => ['texto' => $estado, 'tono' => $tono],
            'pasos' => $pasos,
            'datos' => $datos,
            'items' => $this->itemsVista($this->detalles),
            'total' => (float) $this->total,
        ];
    }
}
