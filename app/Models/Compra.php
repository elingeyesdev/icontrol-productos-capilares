<?php

namespace App\Models;

use App\Models\Concerns\TieneDetalleVista;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    use HasFactory, TieneDetalleVista;

    protected $table = 'compras';

    protected $fillable = [
        'id_proveedor',
        'id_usuario',
        'fecha',
        'total',
        'situacion',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
        'estado' => 'boolean',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra');
    }

    /** Datos listos para <x-detalle-panel>. */
    public function detalleVista(): array
    {
        $this->loadMissing(['proveedor', 'usuario', 'detalles.producto']);

        [$estado, $tono] = match ($this->situacion) {
            'completada' => ['Completada', 'ok'],
            'cancelada' => ['Cancelada', 'bad'],
            default => [ucfirst((string) $this->situacion), 'info'],
        };

        $pasos = [['texto' => 'Compra registrada', 'fecha' => $this->fechaVista($this->fecha), 'tono' => 'ok']];
        if ($this->situacion === 'cancelada') {
            $pasos[] = ['texto' => 'Cancelada', 'fecha' => $this->fechaVista($this->updated_at), 'tono' => 'bad'];
        }

        return [
            'tipo' => 'Compra',
            'id' => $this->id,
            'estado' => ['texto' => $estado, 'tono' => $tono],
            'pasos' => $pasos,
            'datos' => [
                ['icono' => 'bi-truck', 'etiqueta' => 'Proveedor', 'valor' => $this->proveedor->nombre_empresa ?? '—'],
                ['icono' => 'bi-person-badge-fill', 'etiqueta' => 'Registrado por', 'valor' => $this->usuario->name ?? '—'],
            ],
            'items' => $this->itemsVista($this->detalles),
            'total' => (float) $this->total,
        ];
    }
}
