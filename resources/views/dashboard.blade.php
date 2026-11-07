<x-app-layout>
    <div>
        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Bienvenido, {{ auth()->user()->name }}</h1>
                <p>Panel de administración · {{ now()->translatedFormat('l d \d\e F \d\e Y') }}</p>
            </div>
        </div>

        @if($pedidosPendientes > 0)
            <a href="{{ route('pedidos.index') }}"
               class="flex items-center gap-3 bg-brand-white rounded-2xl shadow-card p-4 mb-6 no-underline">
                <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center text-lg">
                    <i class="bi bi-bell"></i>
                </div>
                <div class="flex-1">
                    <div class="text-[13.5px] font-extrabold text-brand-text">
                        Tienes {{ $pedidosPendientes }} {{ $pedidosPendientes === 1 ? 'pedido pendiente' : 'pedidos pendientes' }}
                    </div>
                    <div class="text-[12px] text-brand-sub">Revísalos para confirmarlos o entregarlos.</div>
                </div>
                <i class="bi bi-arrow-right text-brand-accent"></i>
            </a>
        @endif

        <div class="text-[11px] font-extrabold tracking-widest uppercase text-brand-sub mb-3">Accesos rápidos</div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @can('ver-productos')
                <a href="{{ route('productos.index') }}" class="bg-brand-white rounded-2xl shadow-card p-5 no-underline">
                    <div class="w-10 h-10 rounded-xl bg-pastel-yellow flex items-center justify-center text-lg mb-3"><i class="bi bi-box-seam"></i></div>
                    <div class="text-[14px] font-extrabold text-brand-text">Productos</div>
                    <div class="text-[12px] text-brand-sub">Catálogo y stock</div>
                </a>
            @endcan

            @can('ver-pedidos')
                <a href="{{ route('pedidos.index') }}" class="bg-brand-white rounded-2xl shadow-card p-5 no-underline">
                    <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center text-lg mb-3"><i class="bi bi-bag-check"></i></div>
                    <div class="text-[14px] font-extrabold text-brand-text">Pedidos</div>
                    <div class="text-[12px] text-brand-sub">Gestionar pedidos</div>
                </a>
            @endcan

            @can('ver-ventas')
                <a href="{{ route('ventas.index') }}" class="bg-brand-white rounded-2xl shadow-card p-5 no-underline">
                    <div class="w-10 h-10 rounded-xl bg-pastel-mint flex items-center justify-center text-lg mb-3"><i class="bi bi-cash-coin"></i></div>
                    <div class="text-[14px] font-extrabold text-brand-text">Ventas</div>
                    <div class="text-[12px] text-brand-sub">Registrar y consultar</div>
                </a>
            @endcan

            @can('ver-compras')
                <a href="{{ route('compras.index') }}" class="bg-brand-white rounded-2xl shadow-card p-5 no-underline">
                    <div class="w-10 h-10 rounded-xl bg-pastel-peach flex items-center justify-center text-lg mb-3"><i class="bi bi-cart-check"></i></div>
                    <div class="text-[14px] font-extrabold text-brand-text">Compras</div>
                    <div class="text-[12px] text-brand-sub">Compras a proveedores</div>
                </a>
            @endcan

            @can('ver-reportes')
                <a href="{{ route('reportes.ventas') }}" class="bg-brand-white rounded-2xl shadow-card p-5 no-underline">
                    <div class="w-10 h-10 rounded-xl bg-pastel-mint flex items-center justify-center text-lg mb-3"><i class="bi bi-file-earmark-bar-graph"></i></div>
                    <div class="text-[14px] font-extrabold text-brand-text">Reportes</div>
                    <div class="text-[12px] text-brand-sub">Ventas, compras y stock</div>
                </a>
            @endcan

            @can('ver-kpis')
                <a href="{{ route('kpis.index') }}" class="bg-brand-white rounded-2xl shadow-card p-5 no-underline">
                    <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center text-lg mb-3"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="text-[14px] font-extrabold text-brand-text">KPIs y Estadísticas</div>
                    <div class="text-[12px] text-brand-sub">Indicadores del negocio</div>
                </a>
            @endcan
        </div>
    </div>
</x-app-layout>
