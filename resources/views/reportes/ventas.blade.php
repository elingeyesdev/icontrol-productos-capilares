<x-app-layout>
    <div>
        <x-reportes.banner titulo="Reporte de ventas" subtitulo="{{ $ventas->count() }} venta{{ $ventas->count() !== 1 ? 's' : '' }} completada{{ $ventas->count() !== 1 ? 's' : '' }} en el rango seleccionado" />

        <a href="{{ route('reportes.index') }}" style="display:inline-flex; align-items:center; gap:6px; margin-bottom:14px; font-size:13px; font-weight:700; color:var(--clr-accent); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Volver a reportes
        </a>

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" class="filter-input" style="max-width:160px;">
                <span style="font-size:12.5px; color:var(--clr-sub);">hasta</span>
                <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" class="filter-input" style="max-width:160px;">
                <select name="id_cliente" class="filter-select" style="max-width:200px;">
                    <option value="">Todos los clientes</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ request('id_cliente') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-add-user"><i class="bi bi-funnel-fill"></i> Filtrar</button>
            </form>
            <a href="{{ route('reportes.ventas.pdf', request()->query()) }}" target="_blank" rel="noopener" class="btn-add-user">
                <i class="bi bi-file-earmark-pdf-fill"></i> Vista previa PDF
            </a>
        </div>

        <div class="usr-table-card">
            @if($ventas->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-inbox"></i> No hay ventas en este rango.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Total</th>
                            <th>Pago</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ventas as $venta)
                            <tr>
                                <td>#{{ $venta->id }}</td>
                                <td style="font-weight:700;">{{ $venta->cliente->name ?? '—' }}</td>
                                <td>{{ $venta->vendedor->name ?? '—' }}</td>
                                <td>Bs. {{ number_format($venta->total, 2) }}</td>
                                <td>{{ $venta->tipoPagoEtiqueta() ?? '—' }}</td>
                                <td>{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right; font-weight:800;">Total</td>
                            <td colspan="3" style="font-weight:800; color:var(--clr-accent);">Bs. {{ number_format($totalVentas, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
