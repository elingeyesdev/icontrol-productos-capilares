<x-app-layout>
    <div>
        <x-reportes.banner titulo="Productos más vendidos" subtitulo="Top {{ $limite }} de productos por unidades vendidas en el rango seleccionado" />

        <a href="{{ route('reportes.index') }}" style="display:inline-flex; align-items:center; gap:6px; margin-bottom:14px; font-size:13px; font-weight:700; color:var(--clr-accent); text-decoration:none;">
            <i class="bi bi-arrow-left"></i> Volver a reportes
        </a>

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" class="filter-input" style="max-width:160px;">
                <span style="font-size:12.5px; color:var(--clr-sub);">hasta</span>
                <input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" class="filter-input" style="max-width:160px;">
                <select name="top" class="filter-select" style="max-width:140px;">
                    @foreach([5, 10, 15, 25, 50] as $n)
                        <option value="{{ $n }}" {{ $limite == $n ? 'selected' : '' }}>Top {{ $n }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-add-user"><i class="bi bi-funnel-fill"></i> Filtrar</button>
            </form>
            <a href="{{ route('reportes.mas-vendidos.pdf', request()->query()) }}" target="_blank" rel="noopener" class="btn-add-user">
                <i class="bi bi-file-earmark-pdf-fill"></i> Vista previa PDF
            </a>
        </div>

        <div class="usr-table-card">
            @if($productos->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-inbox"></i> No hay ventas en este rango.</p>
            @else
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
                        @foreach($productos as $i => $p)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td style="font-weight:700;">{{ $p->nombre_producto }}</td>
                                <td>{{ (int) $p->total_cantidad }}</td>
                                <td>Bs. {{ number_format($p->total_ingresos, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align:right; font-weight:800;">Total</td>
                            <td style="font-weight:800;">{{ (int) $productos->sum('total_cantidad') }}</td>
                            <td style="font-weight:800; color:var(--clr-accent);">Bs. {{ number_format($productos->sum('total_ingresos'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
