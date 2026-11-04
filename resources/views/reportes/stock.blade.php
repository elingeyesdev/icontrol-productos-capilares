<x-app-layout>
    <div>
        <x-reportes.banner titulo="Movimiento Stock" subtitulo="{{ $movimientos->count() }} movimiento{{ $movimientos->count() !== 1 ? 's' : '' }} de entrada/salida registrado{{ $movimientos->count() !== 1 ? 's' : '' }}" />

        <a href="{{ route('reportes.index') }}" style="display:inline-flex; align-items:center; gap:6px; margin-bottom:14px; font-size:13px; font-weight:700; color:var(--clr-accent); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Volver a reportes
        </a>

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <select onchange="
                    const [t, o] = this.value.split('|');
                    this.form.querySelector('[data-tipo]').value = t;
                    this.form.querySelector('[data-orden]').value = o;
                    this.form.submit();
                " class="filter-select" style="max-width:210px;">
                    <option value="|az" @selected(!$tipo && $orden === 'az')>Todos — Producto A-Z</option>
                    <option value="|za" @selected(!$tipo && $orden === 'za')>Todos — Producto Z-A</option>
                    <option value="entrada|az" @selected($tipo === 'entrada' && $orden === 'az')>Entradas — A-Z</option>
                    <option value="entrada|za" @selected($tipo === 'entrada' && $orden === 'za')>Entradas — Z-A</option>
                    <option value="salida|az" @selected($tipo === 'salida' && $orden === 'az')>Salidas — A-Z</option>
                    <option value="salida|za" @selected($tipo === 'salida' && $orden === 'za')>Salidas — Z-A</option>
                </select>
                <input type="hidden" name="tipo" value="{{ $tipo }}" data-tipo>
                <input type="hidden" name="orden" value="{{ $orden }}" data-orden>
            </form>
            <a href="{{ route('reportes.stock.pdf', request()->query()) }}" target="_blank" rel="noopener" class="btn-add-user">
                <i class="bi bi-file-earmark-pdf-fill"></i> Vista previa PDF
            </a>
        </div>

        <div class="usr-table-card">
            @if($movimientos->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-info-circle"></i> No hay movimientos de stock con este filtro.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">Producto</th>
                            <th style="text-align:center;">Tipo</th>
                            <th style="text-align:center;">Cantidad</th>
                            <th>Fecha</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimientos as $movimiento)
                            <tr>
                                <td style="font-weight:700; text-align:center;">
                                    {{ $movimiento->producto->nombre_producto ?? 'Producto eliminado' }}
                                </td>
                                <td style="text-align:center;">
                                    @if($movimiento->tipo === 'entrada')
                                        <span class="rol-badge rol-cliente"><i class="bi bi-arrow-down-circle-fill"></i> Entrada</span>
                                    @else
                                        <span class="rol-badge rol-default"><i class="bi bi-arrow-up-circle-fill"></i> Salida</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">{{ $movimiento->cantidad }} un.</td>
                                <td>{{ $movimiento->fecha->format('d/m/Y H:i') }}</td>
                                <td>{{ $movimiento->usuario->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
