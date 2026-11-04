<x-app-layout>
    <div x-data="{ verPedido: null }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Mis Pedidos</h1>
                <p>{{ $pedidos->count() }} pedido{{ $pedidos->count() !== 1 ? 's' : '' }} realizado{{ $pedidos->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        <div class="usr-table-card">
            @if($pedidos->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-bag"></i> Todavía no hiciste ningún pedido. <a href="{{ url('/') }}">Explorá el catálogo →</a></p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedidos as $pedido)
                            <tr>
                                <td>#{{ $pedido->id }}</td>
                                <td>{{ $pedido->detalles->count() }}</td>
                                <td>Bs. {{ number_format($pedido->total, 2) }}</td>
                                <td>{{ $pedido->fecha->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="{{ match($pedido->estado) {
                                        'entregado' => 'estado-activo',
                                        'cancelado' => 'estado-inactivo',
                                        default => 'rol-badge rol-default',
                                    } }}">
                                        {{ ucfirst($pedido->estado) }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn-editar" title="Ver detalle"
                                            @click="verPedido = {
                                                id: {{ $pedido->id }},
                                                fecha: '{{ $pedido->fecha->format('d/m/Y H:i') }}',
                                                total: {{ $pedido->total }},
                                                items: {{ $pedido->detalles->map(fn($d) => ['nombre' => $d->producto->nombre_producto ?? 'Producto eliminado', 'cantidad' => $d->cantidad, 'precio' => (float) $d->precio_unitario, 'subtotal' => (float) $d->subtotal])->values()->toJson() }}
                                            }">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div x-show="verPedido" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="verPedido = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="verPedido = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="verPedido">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                                <i class="bi bi-bag-check"></i>
                            </span>
                            <h2 class="usr-modal-title" x-text="'Pedido #' + verPedido.id"></h2>
                        </div>
                        <div class="usr-modal-body">
                            <p style="font-size:13px; margin:0 0 14px;"><strong>Fecha:</strong> <span x-text="verPedido.fecha"></span></p>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <template x-for="item in verPedido.items" :key="item.nombre + item.cantidad">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; background:var(--clr-bg); border-radius:10px;">
                                        <div>
                                            <div style="font-weight:700; font-size:13px;" x-text="item.nombre"></div>
                                            <div style="font-size:11.5px; color:var(--clr-sub);" x-text="item.cantidad + ' x Bs. ' + item.precio.toFixed(2)"></div>
                                        </div>
                                        <strong style="font-size:13px;" x-text="'Bs. ' + item.subtotal.toFixed(2)"></strong>
                                    </div>
                                </template>
                            </div>
                            <div class="usr-stock-current" style="margin-top:14px;">
                                <span>Total</span>
                                <strong x-text="'Bs. ' + verPedido.total"></strong>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
