

<script>
    (function () {
        try {
            const t = localStorage.getItem('luciane_tema');
            if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        } catch (e) {}
    })();
</script>

<div x-data="{ sidebarOpen: false, moduloSeguridad: {{ request()->is('administrador/usuarios*') || request()->is('administrador/roles*') ? 'true' : 'false' }}, moduloParametrizacion: true, moduloTransaccional: true, moduloReportes: {{ request()->is('reportes*') ? 'true' : 'false' }} }">
    
    <nav class="fixed top-0 left-0 right-0 h-[62px] bg-brand-navbar shadow-navbar flex items-center px-5 z-50">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 no-underline">
            <div class="w-12 h-12 flex items-center justify-center shrink-0 transition-transform hover:rotate-6 hover:scale-105">
                <img src="{{ asset('images/branding/logo.png') }}" alt="Luciane" class="w-full h-full object-contain" />
            </div>
            <span class="logo-name text-2xl italic font-semibold tracking-wide">Luciane</span>
        </a>

        <div class="ml-auto flex items-center gap-1.5">
            <form method="GET" action="{{ url('/') }}" class="relative">
                <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[#7a5a8a] text-sm pointer-events-none"></i>
                <input type="text" name="q" placeholder="Buscar producto…"
                       value="{{ request('q') }}"
                       class="bg-white/40 focus:bg-white/60 border-none rounded-pill py-1.5 pl-8 pr-3.5 text-sm text-brand-text w-[185px] focus:w-[220px] outline-none transition-all placeholder:text-[#7a5a8a]" />
            </form>

            <button type="button" id="btnTema"
                    class="w-[38px] h-[38px] rounded-[10px] bg-white/25 hover:bg-white/50 flex items-center justify-center text-lg text-[#3a2450] transition-colors"
                    aria-label="Cambiar tema" title="Cambiar tema">
                <i class="bi bi-moon-stars-fill"></i>
            </button>

            @hasanyrole('administrador|vendedor')
            @php $pedidosPendientes = \App\Models\Pedido::with('cliente')->where('estado', 'pendiente')->latest('fecha')->take(5)->get(); @endphp
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                        class="w-[38px] h-[38px] rounded-[10px] bg-white/25 hover:bg-white/50 flex items-center justify-center text-lg text-[#3a2450] relative transition-colors"
                        aria-label="Pedidos pendientes">
                    <i class="bi bi-bell-fill"></i>
                    @if($pedidosPendientes->count() > 0)
                        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#b03030] text-white text-[10px] font-bold flex items-center justify-center">
                            {{ $pedidosPendientes->count() }}
                        </span>
                    @endif
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="absolute top-[calc(100%+12px)] right-0 w-80 max-w-[90vw] bg-brand-white rounded-2xl shadow-card overflow-hidden z-[1200]">
                    <div class="px-4 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                        <i class="bi bi-bell-fill"></i> Pedidos pendientes
                    </div>
                    @if($pedidosPendientes->isEmpty())
                        <div class="py-5 px-4 text-center text-sm text-brand-sub font-semibold">
                            Sin pedidos pendientes 🎉
                        </div>
                    @else
                        <div style="display:flex; flex-direction:column; gap:2px; padding:6px;">
                            @foreach($pedidosPendientes as $p)
                                <a href="{{ route('pedidos.index') }}" class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl hover:bg-brand-sidebar no-underline">
                                    <span class="text-[12.5px] font-bold text-brand-text">#{{ $p->id }} — {{ $p->cliente->name ?? '—' }}</span>
                                    <span class="text-[12px] font-bold text-brand-accent">Bs. {{ number_format($p->total, 2) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ route('pedidos.index') }}"
                       class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active">
                        Ver todos los pedidos <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            @endhasanyrole

            <button @click="sidebarOpen = !sidebarOpen" type="button"
                    class="lg:hidden w-[38px] h-[38px] rounded-[10px] bg-white/25 hover:bg-white/50 flex items-center justify-center text-lg text-[#3a2450]"
                    aria-label="Menú">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </nav>

    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
         class="lg:hidden fixed top-[62px] left-0 right-0 bottom-0 bg-black/40 z-[1030]"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed top-[62px] left-0 bottom-0 w-[230px] lg:w-[220px] bg-brand-sidebar overflow-y-auto overflow-x-hidden z-[1040] pt-[18px] pb-6 transition-transform">

        <div class="flex items-center gap-2.5 px-4 pt-3.5 pb-3">
            <div class="w-[45px] h-[45px] rounded-xl overflow-hidden shrink-0 flex items-center justify-center bg-brand-hover/40">
                @if(auth()->user()?->imagen_usuario ?? false)
                    <img src="{{ asset('images/' . auth()->user()->imagen_usuario) }}"
                        alt="{{ auth()->user()->name }}" class="w-full h-full object-cover" />
                @else
                    <i class="bi bi-person-fill text-xl text-brand-sub"></i>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[16.5px] font-extrabold text-brand-accent truncate">
                    {{ auth()->user()->name ?? 'Invitado' }}
                </div>
                @auth
                    <div class="text-[11px] font-semibold text-brand-sub mt-0.5">
                        {{ auth()->user()->getRoleNames()->first() ?? '' }}
                    </div>
                @endauth
            </div>
        </div>

        @unless(auth()->check() && auth()->user()->hasAnyRole(['administrador', 'vendedor']))
            <div class="h-px bg-brand-sub/20 mx-3.5 my-1.5"></div>
            <div class="text-[9.5px] font-extrabold tracking-widest uppercase text-brand-sub px-4 pt-3.5 pb-1">Catálogo</div>
            <a href="{{ url('/') }}"
               class="sidebar-link {{ request()->is('/') ? 'sidebar-link-active' : '' }}">
                <i class="bi bi-grid-heart"></i> Todos los productos
            </a>
        @endunless

        @hasanyrole('administrador|vendedor')
            <div class="h-px bg-brand-sub/20 mx-3.5 my-1.5"></div>
            <div class="flex items-center gap-1.5 px-4 pt-3.5 pb-1 text-[9.5px] font-extrabold tracking-widest uppercase text-[#8a50c0]">
                <i class="bi bi-shield-fill"></i> Administración
            </div>

            @role('administrador')
                <a href="{{ route('dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                    <i class="bi bi-bar-chart-fill"></i> Inicio
                </a>
            @endrole

            @role('administrador')
                <button @click="moduloSeguridad = !moduloSeguridad" type="button" class="sidebar-module">
                    <i class="bi bi-lock-fill"></i>
                    <span>Módulo de Seguridad</span>
                    <i class="bi bi-chevron-down transition-transform" :class="!moduloSeguridad && '-rotate-90'"></i>
                </button>
                <div x-show="moduloSeguridad" x-collapse>
                    <a href="{{ route('usuarios.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-person-gear"></i> Administrar Usuarios
                    </a>
                    <a href="{{ route('roles.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-person-badge"></i> Administrar Rol
                    </a>
                    <a href="{{ route('usuario-roles.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-person-vcard"></i> Administrar Rol Usuario
                    </a>
                    <a href="{{ route('permisos.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-shield-lock"></i> Administrar Permisos
                    </a>
                </div>
            @endrole

            <button @click="moduloParametrizacion = !moduloParametrizacion" type="button" class="sidebar-module">
                <i class="bi bi-sliders"></i>
                <span>Módulo de Parametrización</span>
                <i class="bi bi-chevron-down transition-transform" :class="!moduloParametrizacion && '-rotate-90'"></i>
            </button>
            <div x-show="moduloParametrizacion" x-collapse>
                <a href="{{ route('productos.index') }}" class="sidebar-link sidebar-sublink">
                    <i class="bi bi-box-seam"></i> Administrar Productos
                </a>
                <a href="{{ route('categorias.index') }}" class="sidebar-link sidebar-sublink">
                    <i class="bi bi-tags"></i> Administrar Categorías
                </a>
                @role('administrador')
                    <a href="{{ route('usuarios.clientes') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-person-hearts"></i> Administrar Clientes
                    </a>
                    <a href="{{ route('usuarios.vendedores') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-person-badge"></i> Administrar Vendedores
                    </a>
                    <a href="{{ route('proveedores.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-truck"></i> Administrar Proveedores
                    </a>
                @endrole
            </div>

            <button @click="moduloTransaccional = !moduloTransaccional" type="button" class="sidebar-module">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Módulo Transaccional</span>
                <i class="bi bi-chevron-down transition-transform" :class="!moduloTransaccional && '-rotate-90'"></i>
            </button>
            <div x-show="moduloTransaccional" x-collapse>
                @role('administrador')
                    <a href="{{ route('pedidos.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-bag-check"></i> Administrar Pedidos
                    </a>
                @endrole
                @hasanyrole('administrador|vendedor')
                    <a href="{{ route('ventas.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-cash-coin"></i> Administrar Ventas
                    </a>
                @endhasanyrole
                @role('administrador')
                    <a href="{{ route('compras.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-cart-check"></i> Administrar Compras
                    </a>
                @endrole
            </div>

            @role('administrador')
                <button @click="moduloReportes = !moduloReportes" type="button" class="sidebar-module">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>Módulo de Reportes</span>
                    <i class="bi bi-chevron-down transition-transform" :class="!moduloReportes && '-rotate-90'"></i>
                </button>
                <div x-show="moduloReportes" x-collapse>
                    <a href="{{ route('reportes.ventas') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-cash-coin"></i> Reporte de Ventas
                    </a>
                    <a href="{{ route('reportes.compras') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-cart-check"></i> Reporte de Compras
                    </a>
                    <a href="{{ route('reportes.stock') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-box-seam"></i> Stock / Inventario Bajo
                    </a>
                    <a href="{{ route('reportes.mas-vendidos') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-trophy-fill"></i> Productos Más Vendidos
                    </a>
                </div>
            @endrole
        @endhasanyrole

        <div class="h-px bg-brand-sub/20 mx-3.5 my-1.5"></div>
        @auth
            <div class="text-[9.5px] font-extrabold tracking-widest uppercase text-brand-sub px-4 pt-3.5 pb-1">Mi cuenta</div>
            @if(!auth()->user()->hasAnyRole(['administrador', 'vendedor']))
                <a href="{{ route('pedidos.mis') }}" class="sidebar-link">
                    <i class="bi bi-bag-check"></i> Mis Pedidos
                </a>
            @endif
            <a href="{{ route('profile.edit') }}" class="sidebar-link">
                <i class="bi bi-person-gear"></i> Ajustes de cuenta
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); this.closest('form').submit();"
                   class="sidebar-link !text-[#b03030] hover:!bg-[#f5c0c0] hover:!text-[#7a1010]">
                    <i class="bi bi-box-arrow-right !text-[#b03030]"></i> Cerrar sesión
                </a>
            </form>
        @else
            <a href="{{ route('login') }}" class="sidebar-link">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
            </a>
        @endauth
    </aside>

    @if(!auth()->check() || !auth()->user()->hasAnyRole(['administrador', 'vendedor', 'proveedor']))
        <div x-data="{ open: false }" class="fixed bottom-6 right-6 z-[1250]">
            <div x-show="open" @click.outside="open = false" x-cloak x-transition
                 class="absolute bottom-[calc(100%+14px)] right-0 w-80 max-w-[85vw] bg-brand-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-4 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                    <i class="bi bi-cart3"></i> Mi carrito
                </div>
                <template x-if="$store.cart.items.length === 0">
                    <div class="py-5 px-4 text-center text-sm text-brand-sub font-semibold">
                        Tu carrito está vacío
                    </div>
                </template>
                <template x-if="$store.cart.items.length > 0">
                    <div>
                        <div style="max-height:280px; overflow-y:auto; display:flex; flex-direction:column; gap:8px; padding:12px;">
                            <template x-for="item in $store.cart.items" :key="item.id">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-weight:700; font-size:12.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" x-text="item.nombre"></div>
                                        <div style="font-size:11px; color:var(--clr-sub);" x-text="item.cantidad + ' x Bs. ' + item.precio.toFixed(2)"></div>
                                    </div>
                                    <button type="button" class="btn-eliminar" style="width:26px; height:26px;" title="Quitar" @click="$store.cart.remove(item.id)">
                                        <i class="bi bi-x-lg" style="font-size:10px;"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div class="px-4 py-2.5 flex items-center justify-between text-[12.5px] font-bold border-t border-brand-sidebar">
                            <span>Total</span>
                            <span x-text="'Bs. ' + $store.cart.total.toFixed(2)"></span>
                        </div>
                    </div>
                </template>
                @auth
                    <button type="button" @click="$store.cart.checkout()"
                            :disabled="$store.cart.items.length === 0"
                            class="w-full flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-white bg-brand-accent hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <i class="bi bi-bag-check-fill"></i> Finalizar pedido
                    </button>
                @else
                    <a href="{{ route('login') }}"
                       class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active no-underline">
                        <i class="bi bi-box-arrow-in-right"></i> Inicia sesión para pedir
                    </a>
                @endauth
            </div>

            <button @click="open = !open" type="button"
                    class="w-14 h-14 rounded-full bg-brand-accent hover:brightness-110 shadow-card flex items-center justify-center text-white text-xl relative transition-all"
                    aria-label="Carrito">
                <i class="bi bi-cart3"></i>
                <span x-show="$store.cart.count > 0" x-cloak
                      class="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 rounded-full bg-[#b03030] text-white text-[11px] font-bold flex items-center justify-center"
                      x-text="$store.cart.count"></span>
            </button>
        </div>

        <div x-show="$store.cart.toast" x-cloak x-transition
             class="fixed bottom-24 right-6 z-[1400] bg-brand-text text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-card max-w-[85vw]"
             x-text="$store.cart.toast"></div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('cart', {
                    items: @json(array_values(session('carrito', []))),
                    toast: '',

                    get count() {
                        return this.items.reduce((sum, i) => sum + i.cantidad, 0);
                    },
                    get total() {
                        return this.items.reduce((sum, i) => sum + (i.precio * i.cantidad), 0);
                    },
                    showToast(mensaje) {
                        this.toast = mensaje;
                        setTimeout(() => { if (this.toast === mensaje) this.toast = ''; }, 2500);
                    },
                    async add(idProducto, cantidad) {
                        try {
                            const res = await fetch('/carrito', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ id_producto: idProducto, cantidad: cantidad }),
                            });
                            const data = await res.json();
                            if (!res.ok) { this.showToast(data.message || 'No se pudo agregar al carrito.'); return false; }
                            this.items = data.carrito;
                            this.showToast(data.message);
                            return true;
                        } catch (e) {
                            this.showToast('Error de conexión. Intenta de nuevo.');
                            return false;
                        }
                    },
                    async remove(idProducto) {
                        try {
                            const res = await fetch('/carrito/' + idProducto, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                            });
                            const data = await res.json();
                            this.items = data.carrito;
                        } catch (e) {
                            this.showToast('Error de conexión. Intenta de nuevo.');
                        }
                    },
                    async checkout() {
                        if (this.items.length === 0) return;

                        try {
                            const res = await fetch('/pedidos/checkout', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                            });
                            const data = await res.json();
                            if (!res.ok) { this.showToast(data.message || 'No se pudo completar el pedido.'); return; }
                            this.items = [];
                            this.showToast(data.message);
                        } catch (e) {
                            this.showToast('Error de conexión. Intenta de nuevo.');
                        }
                    },
                });
            });
        </script>
    @endif
</div>
