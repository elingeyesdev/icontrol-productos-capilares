<x-app-layout>
    <div>
        <x-reportes.banner titulo="Reportes" subtitulo="Resumen de {{ now()->translatedFormat('F Y') }}" />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-mint flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">Bs. {{ number_format($totalVentas, 2) }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Ventas de este mes</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-peach flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">Bs. {{ number_format($totalCompras, 2) }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Compras de este mes</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-yellow flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">{{ $productosStockBajo }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Productos con stock bajo</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center text-lg mb-3">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="text-[15px] font-extrabold text-brand-text truncate" title="{{ $productoTop->nombre_producto ?? '—' }}">
                    {{ $productoTop->nombre_producto ?? 'Sin ventas aún' }}
                </div>
                <div class="text-[12.5px] font-bold text-brand-sub">Producto más vendido del mes</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <a href="{{ route('reportes.ventas') }}" class="bg-brand-white rounded-2xl shadow-card p-5 flex items-center gap-4 no-underline hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-pastel-mint flex items-center justify-center text-xl flex-shrink-0">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold text-brand-text">Reporte de ventas</div>
                    <div class="text-[12.5px] text-brand-sub">Por rango de fechas y cliente</div>
                </div>
                <i class="bi bi-arrow-right ml-auto text-brand-accent"></i>
            </a>

            <a href="{{ route('reportes.compras') }}" class="bg-brand-white rounded-2xl shadow-card p-5 flex items-center gap-4 no-underline hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-pastel-peach flex items-center justify-center text-xl flex-shrink-0">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold text-brand-text">Reporte de compras</div>
                    <div class="text-[12.5px] text-brand-sub">Por rango de fechas y proveedor</div>
                </div>
                <i class="bi bi-arrow-right ml-auto text-brand-accent"></i>
            </a>

            <a href="{{ route('reportes.stock') }}" class="bg-brand-white rounded-2xl shadow-card p-5 flex items-center gap-4 no-underline hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-pastel-yellow flex items-center justify-center text-xl flex-shrink-0">
                    <i class="bi bi-arrow-down-up"></i>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold text-brand-text">Movimiento Stock</div>
                    <div class="text-[12.5px] text-brand-sub">Entradas y salidas de productos</div>
                </div>
                <i class="bi bi-arrow-right ml-auto text-brand-accent"></i>
            </a>

            <a href="{{ route('reportes.precios-proveedor') }}" class="bg-brand-white rounded-2xl shadow-card p-5 flex items-center gap-4 no-underline hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-pastel-purple flex items-center justify-center text-xl flex-shrink-0">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold text-brand-text">Historial de precios de proveedor</div>
                    <div class="text-[12.5px] text-brand-sub">Auditoría de lo cobrado por cada proveedor, por compra</div>
                </div>
                <i class="bi bi-arrow-right ml-auto text-brand-accent"></i>
            </a>

            <a href="{{ route('reportes.historial-precios') }}" class="bg-brand-white rounded-2xl shadow-card p-5 flex items-center gap-4 no-underline hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 rounded-xl bg-pastel-mint flex items-center justify-center text-xl flex-shrink-0">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="text-[15px] font-extrabold text-brand-text">Historial de cambios de precio</div>
                    <div class="text-[12.5px] text-brand-sub">Auditoría del precio de venta de tus productos</div>
                </div>
                <i class="bi bi-arrow-right ml-auto text-brand-accent"></i>
            </a>
        </div>
    </div>
</x-app-layout>
