<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de movimiento de stock</title>
    @include('reportes.pdf._styles')
</head>
<body>
    <div class="pdf-header">
        <div class="pdf-header-cell pdf-header-logo">
            <div class="pdf-header-logo-badge">{{ substr(config('app.name'), 0, 1) }}</div>
        </div>
        <div class="pdf-header-cell">
            <h1>{{ config('app.name') }} — Movimiento Stock</h1>
            <p>
                @if($tipo === 'entrada')
                    Solo entradas
                @elseif($tipo === 'salida')
                    Solo salidas
                @else
                    Entradas y salidas
                @endif
                — orden {{ $orden === 'za' ? 'Z-A' : 'A-Z' }} — generado el {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="text-align:center;">Producto</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Fecha</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $movimiento)
                <tr>
                    <td style="text-align:center;">{{ $movimiento->producto->nombre_producto ?? 'Producto eliminado' }}</td>
                    <td>
                        <span class="badge {{ $movimiento->tipo === 'entrada' ? 'badge-success' : 'badge-warning' }}">
                            {{ $movimiento->tipo === 'entrada' ? 'Entrada' : 'Salida' }}
                        </span>
                    </td>
                    <td>{{ $movimiento->cantidad }} un.</td>
                    <td>{{ $movimiento->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $movimiento->usuario->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No hay movimientos de stock con este filtro.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pdf-total-box">
        {{ $movimientos->count() }} movimiento(s) en total
    </div>

    <div class="pdf-footer">
        control-productos-capilares — Reporte generado automáticamente
    </div>
</body>
</html>
