

<script>
    (function () {
        try {
            const t = localStorage.getItem('luciane_tema');
            if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        } catch (e) {}
    })();
</script>

<div x-data="{ sidebarOpen: false, moduloSeguridad: {{ request()->is('administrador/usuarios*') || request()->is('administrador/roles*') ? 'true' : 'false' }}, moduloParametrizacion: true, moduloTransaccional: true, moduloReportes: {{ request()->is('reportes*') ? 'true' : 'false' }}, moduloKpis: {{ request()->is('kpis*') ? 'true' : 'false' }} }">
    
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

            @hasanyrole('administrador|vendedor')
            @php
                $vistasAt = auth()->user()->notificaciones_vistas_at;
                $pedidosNuevos = \App\Models\Pedido::where('situacion', 'pendiente')
                    ->when($vistasAt, fn ($q) => $q->where('fecha', '>', $vistasAt))
                    ->count();
                $pedidosPendientes = \App\Models\Pedido::with('cliente')->where('situacion', 'pendiente')->latest('fecha')->take(5)->get();
            @endphp
            <div class="relative" x-data="{
                    open: false,
                    nuevas: {{ $pedidosNuevos }},
                    cargadoAt: '{{ now()->toIso8601String() }}',
                    toggle() {
                        this.open = !this.open;
                        if (this.open && this.nuevas > 0) this.marcarVistas();
                    },
                    marcarVistas() {
                        this.nuevas = 0;
                        fetch('{{ route('notificaciones.vistas', [], false) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ hasta: this.cargadoAt }),
                        }).catch(() => {});
                    }
                 }">
                <button @click="toggle()" type="button"
                        class="w-[38px] h-[38px] rounded-[10px] bg-white/25 hover:bg-white/50 flex items-center justify-center text-lg text-[#3a2450] relative transition-colors"
                        aria-label="Pedidos pendientes">
                    <i class="bi bi-bell-fill"></i>
                    <span x-show="nuevas > 0" x-cloak x-text="nuevas > 99 ? '99+' : nuevas"
                          class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#b03030] text-white text-[10px] font-bold flex items-center justify-center"></span>
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
                                <a href="{{ route('pedidos.index', ['ver' => $p->id]) }}" class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-xl hover:bg-brand-sidebar no-underline">
                                    <span class="text-[12.5px] font-bold text-brand-text" title="{{ $p->cliente->name ?? '' }}">#{{ $p->id }} — {{ mb_substr($p->cliente->name ?? '—', 0, 10) }}</span>
                                    <span class="text-[12px] font-bold text-brand-accent whitespace-nowrap">{{ number_format($p->total, 2) }} Bs.</span>
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

            @auth
            @unlessrole('administrador|vendedor')
            @php
                $notiCliente = auth()->user()->notifications()->latest()->take(8)->get();
                $notiSinLeer = auth()->user()->unreadNotifications()->count();
            @endphp
            <div class="relative" x-data="{
                    open: false,
                    nuevas: {{ $notiSinLeer }},
                    toggle() {
                        this.open = !this.open;
                        if (this.open && this.nuevas > 0) this.marcarLeidas();
                    },
                    marcarLeidas() {
                        this.nuevas = 0;
                        fetch('{{ route('notificaciones.leidas', [], false) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                        }).catch(() => {});
                    }
                 }">
                <button @click="toggle()" type="button"
                        class="w-[38px] h-[38px] rounded-[10px] bg-white/25 hover:bg-white/50 flex items-center justify-center text-lg text-[#3a2450] relative transition-colors"
                        aria-label="Notificaciones">
                    <i class="bi bi-bell-fill"></i>
                    <span x-show="nuevas > 0" x-cloak x-text="nuevas > 99 ? '99+' : nuevas"
                          class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#b03030] text-white text-[10px] font-bold flex items-center justify-center"></span>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="absolute top-[calc(100%+12px)] right-0 w-80 max-w-[90vw] bg-brand-white rounded-2xl shadow-card overflow-hidden z-[1200]">
                    <div class="px-4 py-3.5 font-extrabold text-[13.5px] text-brand-text border-b border-brand-sidebar flex items-center gap-2">
                        <i class="bi bi-bell-fill"></i> Notificaciones
                    </div>
                    @if($notiCliente->isEmpty())
                        <div class="py-5 px-4 text-center text-sm text-brand-sub font-semibold">
                            No tienes notificaciones
                        </div>
                    @else
                        <div style="display:flex; flex-direction:column; gap:2px; padding:6px; max-height:360px; overflow-y:auto;">
                            @foreach($notiCliente as $n)
                                @php
                                    $d = $n->data;
                                    $tz = config('app.timezone');
                                    $fechaPedido = isset($d['fecha_pedido']) ? \Illuminate\Support\Carbon::parse($d['fecha_pedido'])->timezone($tz)->format('d/m/Y H:i') : null;
                                    $fechaAcept = isset($d['fecha_aceptacion']) ? \Illuminate\Support\Carbon::parse($d['fecha_aceptacion'])->timezone($tz)->format('d/m/Y H:i') : null;
                                @endphp
                                <a href="{{ route('pedidos.mis') }}"
                                   class="block px-2.5 py-2.5 rounded-xl hover:bg-brand-sidebar no-underline {{ $n->read_at ? '' : 'bg-brand-sidebar' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[12.5px] font-extrabold text-brand-text">
                                            <i class="bi bi-bag-check-fill text-brand-accent"></i> Pedido #{{ $d['pedido_id'] ?? '' }} aceptado
                                        </span>
                                        @if(isset($d['total']))
                                            <span class="text-[12px] font-bold text-brand-accent whitespace-nowrap">{{ number_format($d['total'], 2) }} Bs.</span>
                                        @endif
                                    </div>
                                    @if($fechaPedido)
                                        <div class="text-[11.5px] font-semibold text-brand-sub mt-1">Pedido realizado: {{ $fechaPedido }}</div>
                                    @endif
                                    @if($fechaAcept)
                                        <div class="text-[11.5px] font-semibold text-brand-sub">Aceptado: {{ $fechaAcept }}</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ route('pedidos.mis') }}"
                       class="flex items-center justify-center gap-1.5 py-2.5 text-[12.5px] font-bold text-brand-accent bg-brand-sidebar hover:text-brand-active">
                        Ver mis pedidos <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            @endunlessrole
            @endauth

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
                    <i class="bi bi-house-door-fill"></i> Inicio
                </a>
            @endrole

            @role('administrador')
                <button @click="moduloSeguridad = !moduloSeguridad" type="button" class="sidebar-module">
                    <i class="bi bi-lock-fill"></i>
                    <span>Seguridad</span>
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
                <span>Parametrización</span>
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
                <span>Transaccional</span>
                <i class="bi bi-chevron-down transition-transform" :class="!moduloTransaccional && '-rotate-90'"></i>
            </button>
            <div x-show="moduloTransaccional" x-collapse>
                @hasanyrole('administrador|vendedor')
                    <a href="{{ route('pedidos.index') }}" class="sidebar-link sidebar-sublink">
                        <i class="bi bi-bag-check"></i> Administrar Pedidos
                    </a>
                @endhasanyrole
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
                    <span>Reportes</span>
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

            @can('ver-kpis')
                <button @click="moduloKpis = !moduloKpis" type="button" class="sidebar-module">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>KPIs y Estadísticas</span>
                    <i class="bi bi-chevron-down transition-transform" :class="!moduloKpis && '-rotate-90'"></i>
                </button>
                <div x-show="moduloKpis" x-collapse>
                    <a href="{{ route('kpis.index') }}"
                       class="sidebar-link sidebar-sublink {{ request()->routeIs('kpis.index') ? 'sidebar-link-active' : '' }}">
                        <i class="bi bi-bar-chart-fill"></i> Resumen
                    </a>
                    <a href="{{ route('kpis.ventas') }}"
                       class="sidebar-link sidebar-sublink {{ request()->routeIs('kpis.ventas') ? 'sidebar-link-active' : '' }}">
                        <i class="bi bi-cash-coin"></i> Ventas
                    </a>
                    <a href="{{ route('kpis.inventario') }}"
                       class="sidebar-link sidebar-sublink {{ request()->routeIs('kpis.inventario') ? 'sidebar-link-active' : '' }}">
                        <i class="bi bi-box-seam"></i> Inventario
                    </a>
                    <a href="{{ route('kpis.pedidos') }}"
                       class="sidebar-link sidebar-sublink {{ request()->routeIs('kpis.pedidos') ? 'sidebar-link-active' : '' }}">
                        <i class="bi bi-bag-check"></i> Pedidos
                    </a>
                    <a href="{{ route('kpis.compras') }}"
                       class="sidebar-link sidebar-sublink {{ request()->routeIs('kpis.compras') ? 'sidebar-link-active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i> Compras y balance
                    </a>
                </div>
            @endcan
        @endhasanyrole

        <div class="h-px bg-brand-sub/20 mx-3.5 my-1.5"></div>
        <style>
            .tema-ui-claro { display: none; }
            [data-theme="dark"] .tema-ui-claro { display: inline; }
            [data-theme="dark"] .tema-ui-oscuro { display: none; }
        </style>
        <div class="text-[9.5px] font-extrabold tracking-widest uppercase text-brand-sub px-4 pt-3.5 pb-1">Apariencia</div>
        <button type="button" id="btnTema" class="sidebar-link" style="width:100%; border:0; cursor:pointer; text-align:left;"
                aria-label="Cambiar tema">
            <i class="bi bi-moon-stars-fill tema-ui-oscuro"></i><span class="tema-ui-oscuro">Modo oscuro</span>
            <i class="bi bi-sun-fill tema-ui-claro"></i><span class="tema-ui-claro">Modo claro</span>
        </button>

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
            <a href="{{ route('register') }}" class="sidebar-link">
                <i class="bi bi-person-plus"></i> Registrarse
            </a>
        @endauth
    </aside>

    @if(!auth()->check() || !auth()->user()->hasAnyRole(['administrador', 'vendedor', 'proveedor']))
        <style>
            .carrito-overlay {
                position: fixed;
                inset: 0;
                background: rgba(20, 10, 30, .6);
                backdrop-filter: blur(2px);
                opacity: 0;
                visibility: hidden;
                transition: opacity .28s ease, visibility .28s ease;
                z-index: 1450;
            }
            .carrito-overlay.abierto { opacity: 1; visibility: visible; }
            .carrito-drawer {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                width: 430px;
                max-width: 100%;
                display: flex;
                flex-direction: column;
                background: var(--clr-card);
                color: var(--clr-text);
                box-shadow: -10px 0 34px rgba(0, 0, 0, .25);
                transform: translateX(100%);
                visibility: hidden;
                transition: transform .3s ease, visibility .3s ease;
                z-index: 1460;
            }
            .carrito-drawer.abierto { transform: translateX(0); visibility: visible; }
            .carrito-head {
                position: relative;
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 16px 64px 16px 20px;
                border-bottom: 1px solid rgba(180, 138, 212, .22);
                font-size: 16px;
                font-weight: 800;
                color: var(--clr-text);
            }
            .carrito-head i { color: var(--clr-accent); }
            .carrito-head .usr-modal-close { top: 50%; transform: translateY(-50%); }
            .carrito-head .usr-modal-close:hover { transform: translateY(-50%) rotate(90deg); }
            .carrito-body { flex: 1; overflow-y: auto; padding: 18px 20px; }
            .carrito-foot {
                padding: 14px 20px 18px;
                border-top: 1px solid rgba(180, 138, 212, .22);
                background: var(--clr-card);
            }
            .carrito-vacio {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding: 48px 10px;
                text-align: center;
                font-size: 13.5px;
                font-weight: 700;
                color: var(--clr-sub);
            }
            .carrito-vacio i { font-size: 38px; color: var(--clr-accent); }
            .carrito-lista { display: flex; flex-direction: column; gap: 8px; }
            .carrito-item {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                padding: 10px;
                background: var(--clr-bg);
                border-radius: 12px;
            }
            .carrito-item .item-thumb,
            .carrito-item .item-thumb-placeholder { width: 52px; height: 52px; }
            .carrito-item-info { flex: 1; min-width: 0; }
            .carrito-item-nombre {
                font-size: 13px;
                font-weight: 800;
                color: var(--clr-text);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .carrito-item-precio { font-size: 11.5px; color: var(--clr-sub); margin: 2px 0 6px; }
            .carrito-item .usr-stepper { width: 104px; height: 28px; }
            .carrito-item .usr-stepper button { width: 28px; font-size: 15px; }
            .carrito-item .usr-stepper button:disabled { opacity: .35; cursor: not-allowed; }
            .carrito-item .usr-stepper input { width: 0; min-width: 0; font-size: 13px; padding: 0; }
            .carrito-item-der {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                justify-content: space-between;
                align-self: stretch;
                gap: 6px;
            }
            .carrito-item-subtotal { font-size: 13px; font-weight: 800; color: var(--clr-accent); white-space: nowrap; }
            .carrito-campo { margin-top: 14px; }
            .carrito-campo textarea { resize: vertical; min-height: 70px; }
            .carrito-error { margin: 5px 0 0; font-size: 11.5px; font-weight: 700; color: #d05555; }
            .carrito-alerta {
                margin-bottom: 14px;
                padding: 10px 12px;
                border-radius: 10px;
                background: rgba(220, 80, 80, .12);
                border: 1px solid rgba(208, 112, 112, .6);
                color: #c04545;
                font-size: 12.5px;
                font-weight: 700;
            }
            [data-theme="dark"] .carrito-alerta { color: #f0a0a0; }
            .carrito-foot .usr-btn-submit:disabled { opacity: .55; cursor: not-allowed; transform: none; }
            .carrito-toast {
                position: fixed;
                bottom: 24px;
                left: 50%;
                transform: translateX(-50%);
                max-width: 90vw;
                padding: 10px 16px;
                border-radius: 12px;
                background: var(--clr-text);
                color: var(--clr-bg);
                font-size: 13px;
                font-weight: 700;
                box-shadow: 0 6px 20px rgba(0, 0, 0, .25);
                z-index: 1500;
            }
        </style>

        <div class="fixed bottom-6 right-6 z-[1250]">
            <button @click="$store.cart.abrir()" type="button"
                    class="w-14 h-14 rounded-full bg-brand-accent hover:brightness-110 shadow-card flex items-center justify-center text-white text-xl relative transition-all"
                    aria-label="Carrito">
                <i class="bi bi-cart3"></i>
                <span x-show="$store.cart.count > 0" x-cloak
                      class="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 rounded-full bg-[#b03030] text-white text-[11px] font-bold flex items-center justify-center"
                      x-text="$store.cart.count"></span>
            </button>
        </div>

        <div class="carrito-overlay" :class="$store.cart.open && 'abierto'" @click="$store.cart.cerrar()"></div>

        <aside class="carrito-drawer" :class="$store.cart.open && 'abierto'" role="dialog" aria-label="Mi carrito"
               @keydown.escape.window="$store.cart.cerrar()"
               x-effect="document.body.style.overflow = $store.cart.open ? 'hidden' : ''">
            <div class="carrito-head">
                <i class="bi bi-cart3"></i> Mi carrito
                <button type="button" class="usr-modal-close" @click="$store.cart.cerrar()" aria-label="Cerrar carrito">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="carrito-body">
                <template x-if="$store.cart.items.length === 0">
                    <div class="carrito-vacio">
                        <i class="bi bi-cart-x"></i>
                        <span>Tu carrito está vacío</span>
                        <button type="button" class="usr-btn-cancel" @click="$store.cart.cerrar()">Seguir viendo productos</button>
                    </div>
                </template>

                <template x-if="$store.cart.items.length > 0">
                    <div>
                        <p class="usr-section-label" style="margin-top:0;">Productos</p>
                        <div class="carrito-lista">
                            <template x-for="item in $store.cart.items" :key="item.id">
                                <div class="carrito-item">
                                    <template x-if="item.imagen">
                                        <img :src="'{{ asset('images') }}/' + item.imagen" :alt="item.nombre" class="item-thumb">
                                    </template>
                                    <template x-if="!item.imagen">
                                        <span class="item-thumb-placeholder"><i class="bi bi-image"></i></span>
                                    </template>
                                    <div class="carrito-item-info">
                                        <div class="carrito-item-nombre" x-text="item.nombre"></div>
                                        <div class="carrito-item-precio" x-text="'Bs. ' + Number(item.precio).toFixed(2) + ' c/u'"></div>
                                        <div class="usr-stepper">
                                            <button type="button" @click="$store.cart.cambiarCantidad(item, item.cantidad - 1)" :disabled="item.cantidad <= 1">−</button>
                                            <input type="number" min="1" :value="item.cantidad"
                                                   @change="$store.cart.cambiarCantidad(item, parseInt($event.target.value))">
                                            <button type="button" @click="$store.cart.cambiarCantidad(item, item.cantidad + 1)"
                                                    :disabled="item.stock && item.cantidad >= item.stock">+</button>
                                        </div>
                                    </div>
                                    <div class="carrito-item-der">
                                        <button type="button" class="btn-eliminar" style="width:26px; height:26px;" title="Quitar"
                                                @click="$store.cart.remove(item.id)">
                                            <i class="bi bi-x-lg" style="font-size:10px;"></i>
                                        </button>
                                        <span class="carrito-item-subtotal" x-text="'Bs. ' + (item.precio * item.cantidad).toFixed(2)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        @auth
                            <form id="formCarrito" class="usr-card-main" @submit.prevent="$store.cart.checkout()" novalidate>
                                <p class="usr-section-label" style="margin-top:22px;">Datos del pedido</p>

                                <div class="carrito-alerta" x-show="$store.cart.errorGeneral" x-cloak x-text="$store.cart.errorGeneral"></div>

                                <div class="carrito-campo" style="margin-top:0;">
                                    <label for="carritoTelefono">Teléfono de contacto</label>
                                    <input id="carritoTelefono" type="tel" maxlength="20" placeholder="Ej: +591 70000000"
                                           x-model="$store.cart.form.telefono" autocomplete="tel">
                                    <p class="carrito-error" x-show="$store.cart.errors.telefono" x-cloak x-text="$store.cart.errors.telefono"></p>
                                </div>

                                <div class="carrito-campo">
                                    <label for="carritoDireccion">Dirección de entrega</label>
                                    <input id="carritoDireccion" type="text" maxlength="255" placeholder="Calle, número, zona, referencia"
                                           x-model="$store.cart.form.direccion_entrega" autocomplete="street-address">
                                    <p class="carrito-error" x-show="$store.cart.errors.direccion_entrega" x-cloak x-text="$store.cart.errors.direccion_entrega"></p>
                                </div>

                                <div class="carrito-campo">
                                    <label for="carritoNota">Nota (opcional)</label>
                                    <textarea id="carritoNota" rows="3" maxlength="500" placeholder="Indicaciones adicionales para tu pedido"
                                              x-model="$store.cart.form.nota"></textarea>
                                    <p class="carrito-error" x-show="$store.cart.errors.nota" x-cloak x-text="$store.cart.errors.nota"></p>
                                </div>
                            </form>
                        @endauth
                    </div>
                </template>
            </div>

            <div class="carrito-foot">
                <div class="usr-total-inline" style="justify-content:space-between;">
                    <span>Total</span>
                    <strong x-text="'Bs. ' + $store.cart.total.toFixed(2)"></strong>
                </div>
                @auth
                    <button type="submit" form="formCarrito" class="usr-btn-submit"
                            style="width:100%; justify-content:center; margin-top:12px;"
                            :disabled="$store.cart.items.length === 0 || $store.cart.loading">
                        <i class="bi bi-bag-check-fill"></i>
                        <span x-text="$store.cart.loading ? 'Enviando…' : 'Confirmar pedido'"></span>
                    </button>
                @else
                    <a href="{{ route('login') }}" class="usr-btn-submit"
                       style="width:100%; justify-content:center; margin-top:12px; text-decoration:none;">
                        <i class="bi bi-box-arrow-in-right"></i> Inicia sesión para pedir
                    </a>
                @endauth
            </div>
        </aside>

        <div x-show="$store.cart.toast" x-cloak x-transition class="carrito-toast" x-text="$store.cart.toast"></div>

        <script>
            document.addEventListener('alpine:init', () => {
                const cabeceras = () => ({
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                });

                Alpine.store('cart', {
                    items: @json(array_values(session('carrito', []))),
                    toast: '',
                    open: false,
                    loading: false,
                    errors: {},
                    errorGeneral: '',
                    form: {
                        telefono: @json(auth()->check() ? (auth()->user()->telefono ?? '') : ''),
                        direccion_entrega: @json(auth()->check() ? auth()->user()->ultimaDireccionEntrega() : ''),
                        nota: '',
                    },

                    get count() {
                        return this.items.reduce((sum, i) => sum + i.cantidad, 0);
                    },
                    get total() {
                        return this.items.reduce((sum, i) => sum + (i.precio * i.cantidad), 0);
                    },
                    abrir() {
                        this.errors = {};
                        this.errorGeneral = '';
                        this.open = true;
                    },
                    cerrar() {
                        this.open = false;
                    },
                    showToast(mensaje) {
                        this.toast = mensaje;
                        setTimeout(() => { if (this.toast === mensaje) this.toast = ''; }, 2500);
                    },
                    async add(idProducto, cantidad) {
                        try {
                            const res = await fetch('/carrito', {
                                method: 'POST',
                                headers: cabeceras(),
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
                    async cambiarCantidad(item, cantidad) {
                        if (!Number.isInteger(cantidad) || cantidad < 1) {
                            this.items = this.items.slice();
                            return;
                        }
                        if (item.stock && cantidad > item.stock) {
                            this.items = this.items.slice();
                            this.showToast('Solo hay ' + item.stock + ' unidades disponibles.');
                            return;
                        }
                        try {
                            const res = await fetch('/carrito/' + item.id, {
                                method: 'PUT',
                                headers: cabeceras(),
                                body: JSON.stringify({ cantidad: cantidad }),
                            });
                            const data = await res.json();
                            if (!res.ok) { this.showToast(data.message || 'No se pudo actualizar el carrito.'); return; }
                            this.items = data.carrito;
                            const actualizado = this.items.find(i => i.id === item.id);
                            if (actualizado && actualizado.cantidad < cantidad) {
                                this.showToast('Solo hay ' + actualizado.cantidad + ' unidades disponibles.');
                            }
                        } catch (e) {
                            this.showToast('Error de conexión. Intenta de nuevo.');
                        }
                    },
                    async remove(idProducto) {
                        try {
                            const res = await fetch('/carrito/' + idProducto, {
                                method: 'DELETE',
                                headers: cabeceras(),
                            });
                            const data = await res.json();
                            this.items = data.carrito;
                        } catch (e) {
                            this.showToast('Error de conexión. Intenta de nuevo.');
                        }
                    },
                    async checkout() {
                        if (this.items.length === 0 || this.loading) return;

                        this.loading = true;
                        this.errors = {};
                        this.errorGeneral = '';

                        try {
                            const res = await fetch('/pedidos/checkout', {
                                method: 'POST',
                                headers: cabeceras(),
                                body: JSON.stringify(this.form),
                            });
                            const data = await res.json();

                            if (res.status === 422 && data.errors) {
                                this.errors = Object.fromEntries(
                                    Object.entries(data.errors).map(([campo, mensajes]) => [campo, mensajes[0]])
                                );
                                return;
                            }

                            if (!res.ok) {
                                this.errorGeneral = data.message || 'No se pudo completar el pedido.';
                                return;
                            }

                            this.items = [];
                            this.form.nota = '';
                            this.open = false;
                            this.showToast(data.message);
                        } catch (e) {
                            this.errorGeneral = 'Error de conexión. Intenta de nuevo.';
                        } finally {
                            this.loading = false;
                        }
                    },
                });
            });
        </script>
    @endif
</div>
