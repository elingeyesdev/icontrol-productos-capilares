<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de cambios de precio</title>
    @include('reportes.pdf._styles')
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-header-cell pdf-header-logo">
            <div class="pdf-header-logo-badge">{{ substr(config('app.name'), 0, 1) }}</div>
        </div>
        <div class="pdf-header-cell">
            <h1>{{ config('app.name') }} — Historial de cambios de precio</h1>
            <p>Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} — generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio anterior</th>
                <th>Precio nuevo</th>
                <th>Modificado por</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @forelse($historial as $registro)
                <tr>
                    <td>{{ $registro->producto->nombre_producto ?? '—' }}</td>
                    <td>Bs. {{ number_format($registro->precio_anterior, 2) }}</td>
                    <td>Bs. {{ number_format($registro->precio_nuevo, 2) }}</td>
                    <td>{{ $registro->usuario->name ?? '—' }}</td>
                    <td>{{ $registro->fecha->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No hay cambios de precio registrados en este rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pdf-footer">
        control-productos-capilares — Reporte generado automáticamente
    </div>
</body>
</html>
