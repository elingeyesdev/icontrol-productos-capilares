<x-app-layout>
    <div>
        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Pedidos</h1>
                <p>Qué tan bien se atiende lo que piden los clientes</p>
            </div>
        </div>

        <x-kpi-periodo :periodos="$periodos" :periodo="$periodo" />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-kpi-card icono="bi-bag-check" tono="purple" :valor="$stats['total']" etiqueta="Pedidos recibidos" />
            <x-kpi-card icono="bi-check2-circle" tono="mint" :valor="number_format($stats['tasa_entrega'], 1) . '%'" etiqueta="Entregados" />
            <x-kpi-card icono="bi-x-circle" tono="peach" :valor="number_format($stats['tasa_cancelacion'], 1) . '%'" etiqueta="Cancelados" />
            <x-kpi-card icono="bi-hourglass-split" tono="yellow" :valor="'Bs. ' . number_format($stats['monto_en_curso'], 2)" etiqueta="Monto en curso" nota="Pendientes y confirmados" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-pie-chart" style="color:var(--clr-accent);"></i> Pedidos por situación
                </div>
                <div class="p-5 flex items-center gap-5">
                    <div style="width:150px; height:150px; flex-shrink:0;">
                        <canvas id="chartPedidosEstado"></canvas>
                    </div>
                    <div class="flex-1" style="display:flex; flex-direction:column; gap:8px;">
                        @foreach(['pendiente' => ['Pendiente', '#f6b93b'], 'confirmado' => ['Confirmado', '#4a90d9'], 'entregado' => ['Entregado', '#63c69b'], 'cancelado' => ['Cancelado', '#e56b8f']] as $clave => [$etiqueta, $color])
                            <div class="flex items-center justify-between gap-2 text-[12.5px]">
                                <span class="flex items-center gap-2 font-bold text-brand-text">
                                    <span style="width:9px; height:9px; border-radius:50%; background:{{ $color }}; display:inline-block;"></span>
                                    {{ $etiqueta }}
                                </span>
                                <span class="font-extrabold text-brand-sub">{{ $porEstado[$clave] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-graph-up" style="color:var(--clr-accent);"></i> Pedidos por mes
                </div>
                <div class="p-5"><canvas id="chartPedidosMes" height="150"></canvas></div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-alarm" style="color:var(--clr-accent);"></i> Pendientes más antiguos ({{ $pendientesTotal }} en total)
                </div>
                @if($pendientes->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-check-circle"></i> No hay pedidos pendientes.</p>
                @else
                    @foreach($pendientes as $p)
                        <div class="flex items-center justify-between gap-2 px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                            <div>
                                <div class="text-[13px] font-bold text-brand-text">#{{ $p->id }} — {{ $p->cliente->name ?? '—' }}</div>
                                <div class="text-[11.5px] text-brand-sub">{{ $p->fecha->format('d/m/Y H:i') }} · {{ $p->fecha->diffForHumans() }}</div>
                            </div>
                            <span class="text-[12.5px] font-extrabold text-brand-sub">Bs. {{ number_format($p->total, 2) }}</span>
                        </div>
                    @endforeach
                @endif
                <a href="{{ route('pedidos.index') }}"
                   class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active no-underline">
                    Ver todos los pedidos <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Chart(document.getElementById('chartPedidosEstado'), {
                type: 'doughnut',
                data: {
                    labels: ['Pendiente', 'Confirmado', 'Entregado', 'Cancelado'],
                    datasets: [{
                        data: [{{ $porEstado['pendiente'] ?? 0 }}, {{ $porEstado['confirmado'] ?? 0 }}, {{ $porEstado['entregado'] ?? 0 }}, {{ $porEstado['cancelado'] ?? 0 }}],
                        backgroundColor: ['#f6b93b', '#4a90d9', '#63c69b', '#e56b8f'],
                        borderWidth: 0,
                    }],
                },
                options: { cutout: '65%', plugins: { legend: { display: false } } },
            });

            new Chart(document.getElementById('chartPedidosMes'), {
                type: 'bar',
                data: {
                    labels: @json($etiquetas),
                    datasets: [{ label: 'Pedidos', data: @json($valores), backgroundColor: '#c9a7e8', borderRadius: 6, maxBarThickness: 46 }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        });
    </script>
</x-app-layout>
