<x-app-layout>
    <div>
        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Inventario</h1>
                <p>Qué hay que reponer y qué no se está moviendo</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-kpi-card icono="bi-currency-exchange" tono="mint" :valor="'Bs. ' . number_format($stats['valor'], 2)" etiqueta="Valor del inventario" nota="A precio de venta" />
            <x-kpi-card icono="bi-box-seam" tono="purple" :valor="number_format($stats['unidades'])" etiqueta="Unidades en stock" />
            <x-kpi-card icono="bi-exclamation-triangle" tono="peach" :valor="$stats['bajos']" etiqueta="Con stock bajo" :nota="'1 a ' . $umbral . ' unidades'" />
            <x-kpi-card icono="bi-x-octagon" tono="yellow" :valor="$stats['agotados']" etiqueta="Agotados" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-arrow-repeat" style="color:var(--clr-accent);"></i> Para reponer
                </div>
                @if($reponer->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-check-circle"></i> Ningún producto con stock bajo.</p>
                @else
                    @foreach($reponer as $p)
                        <div class="flex items-center justify-between gap-2 px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <div>
                                <div class="text-[13px] font-bold text-brand-text">{{ $p->nombre_producto }}</div>
                                <div class="text-[11.5px] text-brand-sub">{{ $p->categoria->nombre_categoria ?? '—' }}</div>
                            </div>
                            <span class="rol-badge {{ $p->stock == 0 ? 'rol-default' : 'rol-cliente' }}">{{ $p->stock == 0 ? 'Agotado' : $p->stock . ' un.' }}</span>
                        </div>
                    @endforeach
                @endif
                @can('ver-compras')
                    <a href="{{ route('compras.index') }}"
                       class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active no-underline">
                        Ir a compras <i class="bi bi-arrow-right"></i>
                    </a>
                @endcan
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-hourglass-split" style="color:var(--clr-accent);"></i> Sin ventas en 60 días ({{ $sinRotacionTotal }})
                </div>
                @if($sinRotacion->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-check-circle"></i> Todo el stock tuvo movimiento.</p>
                @else
                    @foreach($sinRotacion as $p)
                        <div class="flex items-center justify-between gap-2 px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <div>
                                <div class="text-[13px] font-bold text-brand-text">{{ $p->nombre_producto }}</div>
                                <div class="text-[11.5px] text-brand-sub">{{ $p->categoria->nombre_categoria ?? '—' }}</div>
                            </div>
                            <span class="rol-badge estado-activo">{{ $p->stock }} un.</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-tags" style="color:var(--clr-accent);"></i> Valor de stock por categoría
                </div>
                @if($porCategoria->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> No hay productos activos.</p>
                @else
                    @php $maximo = max((float) $porCategoria->max('valor'), 0.01); @endphp
                    @foreach($porCategoria as $c)
                        <div class="px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <div class="flex items-center justify-between gap-2 text-[13px] mb-1.5">
                                <span class="font-bold text-brand-text">{{ $c->nombre }}</span>
                                <span class="font-extrabold text-brand-sub">{{ (int) $c->unidades }} un. · Bs. {{ number_format($c->valor, 2) }}</span>
                            </div>
                            <div style="height:8px; border-radius:6px; background:var(--clr-card);">
                                <div style="height:8px; border-radius:6px; background:#c9a7e8; width:{{ round($c->valor / $maximo * 100) }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
