<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Imagen
{

    public static function url(?string $path): ?string
    {
        if (! $path || ! Storage::disk('images')->exists($path)) {
            return null;
        }

        $segmentos = array_map('rawurlencode', explode('/', $path));

        return url('/api/imagenes/'.implode('/', $segmentos));
    }
}
