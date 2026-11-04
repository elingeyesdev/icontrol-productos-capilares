<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ImagenController extends Controller
{

    public function show(string $path)
    {
        $valido = ! str_contains($path, '..')
            && preg_match('#^(productos|usuarios|branding)/[\w\-./]+$#', $path) === 1;

        $disco = Storage::disk('public');

        if (! $valido || ! $disco->exists($path)) {
            abort(404);
        }

        return response()->file($disco->path($path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
