<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de compras</title>
    @include('reportes.pdf._styles')
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-header-cell pdf-header-logo">
            <div class="pdf-header-logo-badge">{{ substr(config('app.name'), 0, 1) }}</div>
        </div>
        <div class="pdf-header-cell">
            <h1>{{ config('app.name') }} — Reporte de compras</h1>
            <p>Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} — generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Proveedor</th>
                <th>Registrado por</th>
                <th>Fecha</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($compras as $compra)
                <tr>
                    <td>#{{ $compra->id }}</td>
                    <td>{{ $compra->proveedor->nombre_empresa ?? '—' }}</td>
                    <td>{{ $compra->usuario->name ?? '—' }}</td>
                    <td>{{ $compra->fecha->format('d/m/Y H:i') }}</td>
                    <td>Bs. {{ number_format($compra->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No hay compras registradas en este rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pdf-total-box">
        Total del periodo: Bs. {{ number_format($totalCompras, 2) }} &nbsp;|&nbsp; {{ $compras->count() }} compra(s)
    </div>

    <div class="pdf-footer">
        control-productos-capilares — Reporte generado automáticamente
    </div>
</body>
</html>
