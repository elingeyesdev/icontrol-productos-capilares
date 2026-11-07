@php
    $pedidos = $pedidos->loadMissing('venta')->where('situacion', '!=', 'eliminado')->values();
    $grupoDe = fn ($p) => match ($p->situacion) {
        'entregado' => 'aceptado',
        'cancelado' => 'cancelado',
        default => 'espera',
    };
    $total = $pedidos->count();
    $nEspera = $pedidos->filter(fn ($p) => $grupoDe($p) === 'espera')->count();
    $nAceptados = $pedidos->filter(fn ($p) => $grupoDe($p) === 'aceptado')->count();
    $nCancelados = $pedidos->filter(fn ($p) => $grupoDe($p) === 'cancelado')->count();
@endphp
<x-app-layout>
    <style>
        :root { --mp-strong: #7b4fa0; }
        [data-theme="dark"] { --mp-strong: #c4a3e6; }
        .mp-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
        .mp-stat { background: var(--clr-card); border-radius: 16px; padding: 16px 20px; box-shadow: 0 2px 14px rgba(90, 50, 120, .08); }
        .mp-stat b { display: block; font-size: 28px; line-height: 1.15; color: var(--mp-strong); }
        .mp-stat span { font-size: 13px; font-weight: 600; color: var(--clr-sub); }
        .mp-tabs { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .mp-tab { min-height: 44px; padding: 0 18px; border: 0; border-radius: 999px; background: var(--purple-bg); color: var(--clr-text); font-size: 13.5px; font-weight: 700; cursor: pointer; }
        .mp-tab-on { background: #7b4fa0; color: #fff; }
        .mp-grid { display: grid; grid-template-columns: 70px 110px 160px 130px 1fr 44px; align-items: center; gap: 12px; }
        .mp-head { padding: 0 24px 8px; font-size: 11.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--clr-sub); }
        .mp-list { display: flex; flex-direction: column; gap: 10px; }
        .mp-row { background: var(--clr-card); border-radius: 16px; padding: 14px 24px; box-shadow: 0 2px 14px rgba(90, 50, 120, .08); font-size: 14px; }
        .mp-id { font-weight: 700; color: var(--mp-strong); }
        .mp-total { font-weight: 700; }
        .mp-sit { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; }
        .mp-sub { font-size: 12px; font-weight: 600; color: var(--clr-sub); }
        .mp-chip { font-size: 12.5px; padding: 5px 12px; }
        .mp-eye { width: 44px; height: 44px; border: 0; border-radius: 12px; background: var(--purple-bg); color: var(--mp-strong); display: flex; align-items: center; justify-content: center; font-size: 17px; cursor: pointer; }
        .mp-eye:hover { filter: brightness(.96); }
        @media (max-width: 820px) {
            .mp-stats { grid-template-columns: 1fr; }
            .mp-head { display: none; }
            .mp-grid { grid-template-columns: 1fr 44px; gap: 6px 12px; }
            .mp-row .mp-eye { grid-column: 2; grid-row: 1 / span 5; align-self: center; }
        }
    </style>

    <div x-data="{ verPedido: null, filtro: 'todos' }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Mis Pedidos</h1>
                <p>{{ $total }} pedido{{ $total !== 1 ? 's' : '' }} realizado{{ $total !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        @if($pedidos->isEmpty())
            <div class="usr-table-card">
                <p class="mensaje-vacio"><i class="bi bi-bag"></i> Todavía no hiciste ningún pedido. <a href="{{ url('/catalogo') }}">Explorá el catálogo →</a></p>
            </div>
        @else
            <div class="mp-stats">
                <div class="mp-stat"><b>{{ $total }}</b><span>Pedidos</span></div>
                <div class="mp-stat"><b>{{ $nEspera }}</b><span>En espera</span></div>
                <div class="mp-stat"><b>{{ $nAceptados }}</b><span>Aceptados</span></div>
            </div>

            <div class="mp-tabs" role="tablist">
                <button type="button" class="mp-tab" :class="filtro === 'todos' && 'mp-tab-on'" @click="filtro = 'todos'">Todos</button>
                <button type="button" class="mp-tab" :class="filtro === 'espera' && 'mp-tab-on'" @click="filtro = 'espera'">En espera</button>
                <button type="button" class="mp-tab" :class="filtro === 'aceptado' && 'mp-tab-on'" @click="filtro = 'aceptado'">Aceptados</button>
                <button type="button" class="mp-tab" :class="filtro === 'cancelado' && 'mp-tab-on'" @click="filtro = 'cancelado'">Cancelados</button>
            </div>

            <div class="mp-grid mp-head">
                <div>N°</div>
                <div>Productos</div>
                <div>Fecha</div>
                <div>Total</div>
                <div>Situación</div>
                <div></div>
            </div>

            <div class="mp-list">
                @foreach($pedidos as $pedido)
                    @php
                        $grupo = $grupoDe($pedido);
                        $aceptadoEn = $pedido->situacion === 'entregado' ? $pedido->venta?->fecha?->format('d/m/Y H:i') : null;
                        $etiqueta = match ($pedido->situacion) {
                            'pendiente' => 'En espera',
                            'confirmado' => 'Confirmado',
                            'entregado' => 'Aceptado',
                            'cancelado' => 'Cancelado',
                            default => ucfirst($pedido->situacion),
                        };
                        $subtexto = match ($pedido->situacion) {
                            'pendiente' => 'Esperando aceptación',
                            'confirmado' => 'Confirmado, pendiente de entrega',
                            'entregado' => $aceptadoEn ? 'Aceptado ' . $aceptadoEn : 'Pedido aceptado',
                            'cancelado' => 'Pedido cancelado',
                            default => '',
                        };
                        $claseChip = match ($grupo) {
                            'aceptado' => 'estado-activo',
                            'cancelado' => 'estado-inactivo',
                            default => 'rol-default',
                        };
                        $cantidad = $pedido->detalles->count();
                    @endphp
                    <div class="mp-grid mp-row" x-show="filtro === 'todos' || filtro === '{{ $grupo }}'">
                        <div class="mp-id">#{{ $pedido->id }}</div>
                        <div>{{ $cantidad }} producto{{ $cantidad !== 1 ? 's' : '' }}</div>
                        <div>{{ $pedido->fecha->format('d/m/Y H:i') }}</div>
                        <div class="mp-total">Bs. {{ number_format($pedido->total, 2) }}</div>
                        <div class="mp-sit">
                            <span class="{{ $claseChip }} mp-chip">{{ $etiqueta }}</span>
                            <span class="mp-sub">{{ $subtexto }}</span>
                        </div>
                        <button type="button" class="mp-eye" title="Ver detalle" aria-label="Ver detalle del pedido #{{ $pedido->id }}"
                                @click="verPedido = @js($pedido->detalleVista(false))">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                @endforeach
            </div>

            @if($nEspera === 0)
                <p class="mensaje-vacio" x-show="filtro === 'espera'" x-cloak>No tienes pedidos en espera.</p>
            @endif
            @if($nAceptados === 0)
                <p class="mensaje-vacio" x-show="filtro === 'aceptado'" x-cloak>Todavía no tienes pedidos aceptados.</p>
            @endif
            @if($nCancelados === 0)
                <p class="mensaje-vacio" x-show="filtro === 'cancelado'" x-cloak>No tienes pedidos cancelados.</p>
            @endif
        @endif

        <x-detalle-panel var="verPedido" />

    </div>
</x-app-layout>
