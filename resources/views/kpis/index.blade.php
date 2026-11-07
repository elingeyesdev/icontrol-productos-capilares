<x-app-layout>
    <div>
        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Estadísticas</h1>
                <p>Resumen general de {{ now()->translatedFormat('F Y') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-mint flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">Bs. {{ number_format($stats['ventas_mes_total'], 2) }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Ventas del mes ({{ $stats['ventas_mes_count'] }})</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-peach flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">Bs. {{ number_format($stats['compras_mes_total'], 2) }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Compras del mes ({{ $stats['compras_mes_count'] }})</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">{{ $stats['pedidos_pendientes'] }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Pedidos pendientes</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-yellow flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">{{ $stats['productos_activos'] }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Productos activos</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-graph-up" style="color:var(--clr-accent);"></i> Ventas de los últimos 6 meses
                </div>
                <div class="p-5">
                    <canvas id="chartVentasMeses" height="90"></canvas>
                </div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-bag-check" style="color:var(--clr-accent);"></i> Pedidos por situación
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
                                <span class="font-extrabold text-brand-sub">{{ $pedidosPorEstado[$clave] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-receipt" style="color:var(--clr-accent);"></i> Ventas por situación
                </div>
                <div class="p-5 flex items-center gap-5">
                    <div style="width:150px; height:150px; flex-shrink:0;">
                        <canvas id="chartVentasEstado"></canvas>
                    </div>
                    <div class="flex-1" style="display:flex; flex-direction:column; gap:8px;">
                        @foreach(['completada' => ['Completada', '#63c69b'], 'cancelada' => ['Cancelada', '#e56b8f']] as $clave => [$etiqueta, $color])
                            <div class="flex items-center justify-between gap-2 text-[12.5px]">
                                <span class="flex items-center gap-2 font-bold text-brand-text">
                                    <span style="width:9px; height:9px; border-radius:50%; background:{{ $color }}; display:inline-block;"></span>
                                    {{ $etiqueta }}
                                </span>
                                <span class="font-extrabold text-brand-sub">{{ $ventasPorEstado[$clave] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-box-seam" style="color:var(--clr-accent);"></i> Stock de productos
                </div>
                @if($stockProductos->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> No hay productos registrados.</p>
                @else
                    <div style="display:flex; flex-direction:column;">
                        @foreach($stockProductos as $p)
                            <div class="flex items-center justify-between gap-2 px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                                <span class="text-[13px] font-bold text-brand-text">{{ $p->nombre_producto }}</span>
                                <span class="rol-badge {{ $p->stock == 0 ? 'rol-default' : ($p->stock <= 5 ? 'rol-cliente' : 'estado-activo') }}">{{ $p->stock }} un.</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <a href="{{ route('productos.index') }}"
                   class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active no-underline">
                    Ver todos los productos <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-bag-check"></i> Últimos pedidos
                </div>
                @if($ultimosPedidos->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> Todavía no hay pedidos.</p>
                @else
                    <div style="display:flex; flex-direction:column;">
                        @foreach($ultimosPedidos as $pedido)
                            <div class="flex items-center justify-between gap-2 px-5 py-3 border-b border-brand-sidebar last:border-b-0">
                                <div>
                                    <div class="text-[13px] font-bold text-brand-text">#{{ $pedido->id }} — {{ $pedido->cliente->name ?? '—' }}</div>
                                    <div class="text-[11.5px] text-brand-sub">{{ $pedido->fecha->format('d/m/Y H:i') }}</div>
                                </div>
                                <span class="{{ match($pedido->situacion) {
                                    'completado' => 'estado-activo',
                                    'cancelado' => 'estado-inactivo',
                                    default => 'rol-badge rol-default',
                                } }}">{{ ucfirst($pedido->situacion) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <a href="{{ route('pedidos.index') }}"
                   class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active no-underline">
                    Ver todos los pedidos <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card overflow-hidden lg:col-span-2">
                <div class="px-5 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-receipt"></i> Últimas ventas
                </div>
                @if($ultimasVentas->isEmpty())
                    <p class="mensaje-vacio" style="padding:24px;"><i class="bi bi-inbox"></i> Todavía no hay ventas registradas.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ultimasVentas as $venta)
                                <tr>
                                    <td>#{{ $venta->id }}</td>
                                    <td style="font-weight:700;">{{ $venta->cliente->name ?? '—' }}</td>
                                    <td>Bs. {{ number_format($venta->total, 2) }}</td>
                                    <td>{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                <a href="{{ route('ventas.index') }}"
                   class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active no-underline">
                    Ver todas las ventas <i class="bi bi-arrow-right"></i>
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Chart(document.getElementById('chartVentasMeses'), {
                type: 'bar',
                data: {
                    labels: @json($ventasUltimosMeses->pluck('mes')),
                    datasets: [{
                        label: 'Ventas (Bs)',
                        data: @json($ventasUltimosMeses->pluck('total')),
                        backgroundColor: '#c9a7e8',
                        borderRadius: 6,
                        maxBarThickness: 46,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (v) => 'Bs ' + v },
                        },
                    },
                },
            });

            new Chart(document.getElementById('chartPedidosEstado'), {
                type: 'doughnut',
                data: {
                    labels: ['Pendiente', 'Confirmado', 'Entregado', 'Cancelado'],
                    datasets: [{
                        data: [
                            {{ $pedidosPorEstado['pendiente'] ?? 0 }},
                            {{ $pedidosPorEstado['confirmado'] ?? 0 }},
                            {{ $pedidosPorEstado['entregado'] ?? 0 }},
                            {{ $pedidosPorEstado['cancelado'] ?? 0 }},
                        ],
                        backgroundColor: ['#f6b93b', '#4a90d9', '#63c69b', '#e56b8f'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    cutout: '65%',
                    plugins: { legend: { display: false } },
                },
            });

            new Chart(document.getElementById('chartVentasEstado'), {
                type: 'doughnut',
                data: {
                    labels: ['Completada', 'Cancelada'],
                    datasets: [{
                        data: [
                            {{ $ventasPorEstado['completada'] ?? 0 }},
                            {{ $ventasPorEstado['cancelada'] ?? 0 }},
                        ],
                        backgroundColor: ['#63c69b', '#e56b8f'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    cutout: '65%',
                    plugins: { legend: { display: false } },
                },
            });
        });
    </script>
</x-app-layout>
