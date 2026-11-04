<x-app-layout>
    <div>
        <x-reportes.banner titulo="Precios por proveedor" subtitulo="{{ $detalles->count() }} registro{{ $detalles->count() !== 1 ? 's' : '' }} de compra en el rango seleccionado" />

        <a href="{{ route('reportes.index') }}" style="display:inline-flex; align-items:center; gap:6px; margin-bottom:14px; font-size:13px; font-weight:700; color:var(--clr-accent); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Volver a reportes
        </a>

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
                <select name="id_proveedor" class="filter-select" style="max-width:220px;">
                    <option value="">Todos los proveedores</option>
                    @foreach($proveedores as $pv)
                        <option value="{{ $pv->id }}" {{ request('id_proveedor') == $pv->id ? 'selected' : '' }}>{{ $pv->nombre_empresa }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-add-user"><i class="bi bi-funnel-fill"></i> Filtrar</button>
            </form>
            <a href="{{ route('reportes.precios-proveedor.pdf', request()->query()) }}" target="_blank" rel="noopener" class="btn-add-user">
                <i class="bi bi-file-earmark-pdf-fill"></i> Vista previa PDF
            </a>
        </div>

        <div class="usr-table-card">
            @if($detalles->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-inbox"></i> No hay compras en este rango.</p>
            @else
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
                        @foreach($detalles as $d)
                            <tr>
                                <td>{{ $d->compra->fecha->format('d/m/Y H:i') }}</td>
                                <td>{{ $d->compra->proveedor->nombre_empresa ?? '—' }}</td>
                                <td style="font-weight:700;">{{ $d->producto->nombre_producto ?? '—' }}</td>
                                <td>{{ $d->cantidad }}</td>
                                <td>Bs. {{ number_format($d->precio_unitario, 2) }}</td>
                                <td>Bs. {{ number_format($d->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
