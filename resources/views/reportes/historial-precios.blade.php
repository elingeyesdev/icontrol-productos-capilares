<x-app-layout>
    <div>
        <x-reportes.banner titulo="Historial de cambios de precio" subtitulo="{{ $historial->count() }} cambio{{ $historial->count() !== 1 ? 's' : '' }} de precio en el rango seleccionado" />

        <a href="{{ route('reportes.index') }}" style="display:inline-flex; align-items:center; gap:6px; margin-bottom:14px; font-size:13px; font-weight:700; color:var(--clr-accent); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Volver a reportes
        </a>

        <p style="font-size:12.5px; color:var(--clr-sub); margin:-6px 0 14px;">
            Cada vez que se edita el precio de venta de un producto desde su vista, queda registrado aquí quién lo cambió, cuándo, y de qué valor a qué valor.
        </p>

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" class="filter-input" style="max-width:160px;">
                <span style="font-size:12.5px; color:var(--clr-sub);">hasta</span>
                <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" class="filter-input" style="max-width:160px;">
                <select name="id_producto" class="filter-select" style="max-width:220px;">
                    <option value="">Todos los productos</option>
                    @foreach($productos as $p)
                        <option value="{{ $p->id }}" {{ request('id_producto') == $p->id ? 'selected' : '' }}>{{ $p->nombre_producto }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-add-user"><i class="bi bi-funnel-fill"></i> Filtrar</button>
            </form>
            <a href="{{ route('reportes.historial-precios.pdf', request()->query()) }}" target="_blank" rel="noopener" class="btn-add-user">
                <i class="bi bi-file-earmark-pdf-fill"></i> Vista previa PDF
            </a>
        </div>

        <div class="usr-table-card">
            @if($historial->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-inbox"></i> No hay cambios de precio registrados en este rango.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio anterior</th>
                            <th>Precio nuevo</th>
                            <th>Variación</th>
                            <th>Modificado por</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial as $registro)
                            @php $sube = $registro->precio_nuevo >= $registro->precio_anterior; @endphp
                            <tr>
                                <td style="font-weight:700;">{{ $registro->producto->nombre_producto ?? '—' }}</td>
                                <td>Bs. {{ number_format($registro->precio_anterior, 2) }}</td>
                                <td>Bs. {{ number_format($registro->precio_nuevo, 2) }}</td>
                                <td style="color: {{ $sube ? '#2e8b57' : '#b03030' }}; font-weight:700;">
                                    <i class="bi bi-arrow-{{ $sube ? 'up' : 'down' }}-short"></i>
                                    Bs. {{ number_format(abs($registro->precio_nuevo - $registro->precio_anterior), 2) }}
                                </td>
                                <td>{{ $registro->usuario->name ?? '—' }}</td>
                                <td>{{ $registro->fecha->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
