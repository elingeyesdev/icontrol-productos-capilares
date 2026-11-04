<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos más vendidos</title>
    @include('reportes.pdf._styles')
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-header-cell pdf-header-logo">
            <div class="pdf-header-logo-badge">{{ substr(config('app.name'), 0, 1) }}</div>
        </div>
        <div class="pdf-header-cell">
            <h1>{{ config('app.name') }} — Productos más vendidos (Top {{ $limite }})</h1>
            <p>Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} — generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Unidades vendidas</th>
                <th>Ingresos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->nombre_producto }}</td>
                    <td>{{ (int) $p->total_cantidad }}</td>
                    <td>Bs. {{ number_format($p->total_ingresos, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No hay ventas en este rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pdf-footer">
        control-productos-capilares — Reporte generado automáticamente
    </div>
</body>
</html>
