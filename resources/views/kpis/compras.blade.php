<x-app-layout>
    <div>
        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Compras y balance</h1>
                <p>Lo que entra por ventas frente a lo que se gasta en reponer</p>
            </div>
        </div>

        <x-kpi-periodo :periodos="$periodos" :periodo="$periodo" />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-kpi-card icono="bi-cash-coin" tono="mint" :valor="'Bs. ' . number_format($stats['ventas'], 2)" etiqueta="Ventas" />
            <x-kpi-card icono="bi-cart-check" tono="peach" :valor="'Bs. ' . number_format($stats['compras'], 2)" etiqueta="Compras" :nota="$stats['cantidad'] . ' realizadas'" />
            <x-kpi-card icono="bi-arrow-left-right" tono="purple"
                :valor="'Bs. ' . number_format($stats['diferencia'], 2)"
                etiqueta="Ventas menos compras"
                nota="Referencial, no es utilidad"
                :nota-color="$stats['diferencia'] >= 0 ? '#2f8f5b' : '#c0394f'" />
            <x-kpi-card icono="bi-percent" tono="yellow"
                :valor="$stats['ventas'] > 0 ? number_format($stats['compras'] / $stats['ventas'] * 100, 1) . '%' : '—'"
                etiqueta="Compras sobre ventas" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-bar-chart" style="color:var(--clr-accent);"></i> Ventas y compras por mes (últimos 6 meses)
                </div>
                <div class="p-5"><canvas id="chartBalance" height="80"></canvas></div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-truck" style="color:var(--clr-accent);"></i> Compras por proveedor
                </div>
                @if($porProveedor->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> Sin compras en este periodo.</p>
                @else
                    @php $suma = max((float) $porProveedor->sum('total'), 0.01); @endphp
                    @foreach($porProveedor as $f)
                        <div class="px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <div class="flex items-center justify-between gap-2 text-[13px] mb-1.5">
                                <span class="font-bold text-brand-text">{{ $f->nombre }}</span>
                                <span class="font-extrabold text-brand-sub">{{ $f->compras }} compra{{ $f->compras != 1 ? 's' : '' }} · Bs. {{ number_format($f->total, 2) }}</span>
                            </div>
                            <div style="height:8px; border-radius:6px; background:var(--clr-card);">
                                <div style="height:8px; border-radius:6px; background:#c9a7e8; width:{{ round($f->total / $suma * 100) }}%;"></div>
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
            new Chart(document.getElementById('chartBalance'), {
                type: 'bar',
                data: {
                    labels: @json($etiquetas),
                    datasets: [
                        { label: 'Ventas', data: @json($serieVentas), backgroundColor: '#63c69b', borderRadius: 6, maxBarThickness: 36 },
                        { label: 'Compras', data: @json($serieCompras), backgroundColor: '#c9a7e8', borderRadius: 6, maxBarThickness: 36 },
                    ],
                },
                options: {
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Bs ' + v } } },
                },
            });
        });
    </script>
</x-app-layout>
