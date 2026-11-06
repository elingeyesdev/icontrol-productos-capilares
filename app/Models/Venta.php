<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    use HasFactory;

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
}
