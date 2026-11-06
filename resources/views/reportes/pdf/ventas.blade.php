<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ventas</title>
    @include('reportes.pdf._styles')
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-header-cell pdf-header-logo">
            <div class="pdf-header-logo-badge">{{ substr(config('app.name'), 0, 1) }}</div>
        </div>
        <div class="pdf-header-cell">
            <h1>{{ config('app.name') }} — Reporte de ventas</h1>
            <p>Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} — generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Pago</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $venta)
                <tr>
                    <td>#{{ $venta->id }}</td>
                    <td>{{ $venta->cliente->name ?? '—' }}</td>
                    <td>{{ $venta->vendedor->name ?? '—' }}</td>
                    <td>{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                    <td>Bs. {{ number_format($venta->total, 2) }}</td>
                    <td>{{ $venta->tipoPagoEtiqueta() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No hay ventas registradas en este rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pdf-total-box">
        Total del periodo: Bs. {{ number_format($totalVentas, 2) }} &nbsp;|&nbsp; {{ $ventas->count() }} venta(s)
    </div>

    <div class="pdf-footer">
        control-productos-capilares — Reporte generado automáticamente
    </div>
</body>
</html>
