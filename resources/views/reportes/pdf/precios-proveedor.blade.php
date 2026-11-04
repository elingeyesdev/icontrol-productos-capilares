<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Precios por proveedor</title>
    @include('reportes.pdf._styles')
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-header-cell pdf-header-logo">
            <div class="pdf-header-logo-badge">{{ substr(config('app.name'), 0, 1) }}</div>
        </div>
        <div class="pdf-header-cell">
            <h1>{{ config('app.name') }} — Precios por proveedor</h1>
            <p>Del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} — generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detalles as $d)
                <tr>
                    <td>{{ $d->compra->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $d->compra->proveedor->nombre_empresa ?? '—' }}</td>
                    <td>{{ $d->producto->nombre_producto ?? '—' }}</td>
                    <td>{{ $d->cantidad }}</td>
                    <td>Bs. {{ number_format($d->precio_unitario, 2) }}</td>
                    <td>Bs. {{ number_format($d->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No hay compras en este rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pdf-footer">
        control-productos-capilares — Reporte generado automáticamente
    </div>
</body>
</html>
