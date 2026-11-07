<x-app-layout>
    <div>
        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Ventas</h1>
                <p>Cómo van las ventas y qué las mueve</p>
            </div>
        </div>

        <x-kpi-periodo :periodos="$periodos" :periodo="$periodo" />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-kpi-card icono="bi-cash-coin" tono="mint"
                :valor="'Bs. ' . number_format($stats['total'], 2)"
                etiqueta="Total vendido"
                :nota="is_null($stats['variacion']) ? null : (($stats['variacion'] >= 0 ? '▲ ' : '▼ ') . number_format(abs($stats['variacion']), 1) . '% vs periodo anterior')"
                :nota-color="($stats['variacion'] ?? 0) >= 0 ? '#2f8f5b' : '#c0394f'" />
            <x-kpi-card icono="bi-receipt" tono="purple" :valor="$stats['cantidad']" etiqueta="Ventas realizadas" />
            <x-kpi-card icono="bi-bag-heart" tono="peach" :valor="'Bs. ' . number_format($stats['ticket'], 2)" etiqueta="Ticket promedio" />
            <x-kpi-card icono="bi-people" tono="yellow" :valor="$stats['clientes']" etiqueta="Clientes distintos" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-graph-up" style="color:var(--clr-accent);"></i> Ventas por mes (últimos 12 meses)
                </div>
                <div class="p-5"><canvas id="chartVentas" height="80"></canvas></div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-trophy" style="color:var(--clr-accent);"></i> Productos más vendidos
                </div>
                @if($topProductos->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> Sin ventas en este periodo.</p>
                @else
                    @foreach($topProductos as $p)
                        <div class="flex items-center justify-between gap-2 px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <span class="text-[13px] font-bold text-brand-text">{{ $loop->iteration }}. {{ $p->nombre }}</span>
                            <span class="text-[12.5px] font-extrabold text-brand-sub">{{ (int) $p->unidades }} un. · Bs. {{ number_format($p->ingresos, 2) }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-person-heart" style="color:var(--clr-accent);"></i> Mejores clientes
                </div>
                @if($topClientes->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> Sin ventas en este periodo.</p>
                @else
                    @foreach($topClientes as $c)
                        <div class="flex items-center justify-between gap-2 px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <span class="text-[13px] font-bold text-brand-text">{{ $c->nombre }}</span>
                            <span class="text-[12.5px] font-extrabold text-brand-sub">{{ $c->compras }} compra{{ $c->compras != 1 ? 's' : '' }} · Bs. {{ number_format($c->total, 2) }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-wallet2" style="color:var(--clr-accent);"></i> Ventas por método de pago
                </div>
                @if($porPago->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> Sin ventas en este periodo.</p>
                @else
                    @php $sumaPago = max((float) $porPago->sum('total'), 0.01); @endphp
                    @foreach($porPago as $f)
                        <div class="px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <div class="flex items-center justify-between gap-2 text-[13px] mb-1.5">
                                <span class="font-bold text-brand-text">{{ \App\Models\Venta::TIPOS_PAGO[$f->tipo_pago] ?? 'Sin dato' }}</span>
                                <span class="font-extrabold text-brand-sub">{{ $f->cantidad }} ventas · Bs. {{ number_format($f->total, 2) }}</span>
                            </div>
                            <div style="height:8px; border-radius:6px; background:var(--clr-card);">
                                <div style="height:8px; border-radius:6px; background:#c9a7e8; width:{{ round($f->total / $sumaPago * 100) }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Chart(document.getElementById('chartVentas'), {
                type: 'bar',
                data: {
                    labels: @json($etiquetas),
                    datasets: [{ label: 'Ventas (Bs)', data: @json($valores), backgroundColor: '#c9a7e8', borderRadius: 6, maxBarThickness: 46 }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Bs ' + v } } },
                },
            });
        });
    </script>
</x-app-layout>
