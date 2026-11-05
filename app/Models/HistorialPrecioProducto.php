<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPrecioProducto extends Model
{
    protected $table = 'historial_precios_productos';

    protected $fillable = [
        'id_producto',
        'precio_anterior',
        'precio_nuevo',
        'id_usuario',
        'fecha',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'precio_anterior' => 'decimal:2',
        'precio_nuevo' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
