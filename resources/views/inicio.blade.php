<x-app-layout>

    @php
        $categorias = \App\Models\Categoria::where('estado', true)
            ->withCount(['productos' => fn ($q) => $q->where('estado', true)])
            ->orderBy('nombre_categoria')
            ->get();

        $destacados = \App\Models\Producto::with('categoria')
            ->where('estado', true)
            ->where('stock', '>', 0)
            ->orderByDesc('id')
            ->take(3)
            ->get();

        $totalProductos = \App\Models\Producto::where('estado', true)->count();

        $iconos = [
            'acondicionador'   => 'bi-droplet-half',
            'kit-de-alisado'   => 'bi-box2-heart',
            'nutricion'        => 'bi-flower1',
            'shampoo'          => 'bi-droplet',
            'tratamiento'      => 'bi-stars',
        ];

        $esStaff = auth()->check() && auth()->user()->hasAnyRole(['administrador', 'vendedor']);
    @endphp

    <style>
        .ini-wrap { max-width: 1180px; margin: 0 auto; }

        .ini-hero { display: grid; grid-template-columns: 1.1fr 1fr; gap: 24px; align-items: center; padding: 18px 0 34px; }
        .ini-eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 20px; background: var(--clr-card); font-size: 12px; font-weight: 700; color: var(--clr-text); }
        .ini-eyebrow::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--clr-accent); }
        .ini-title { font-family: 'Playfair Display', serif; font-style: italic; font-weight: 600; font-size: clamp(34px, 5vw, 54px); line-height: 1.08; color: var(--clr-text); margin: 18px 0 14px; }
        .ini-title em { color: var(--clr-accent); }
        .ini-lead { font-size: 14.5px; line-height: 1.6; color: var(--clr-sub); max-width: 470px; margin-bottom: 22px; }
        .ini-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .ini-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 14px; font-size: 13.5px; font-weight: 700; text-decoration: none; transition: transform .25s ease, background .25s ease; }
        .ini-btn:hover { transform: translateY(-2px); }
        .ini-btn-main { background: var(--clr-active); color: #fff; box-shadow: 0 4px 14px rgba(140, 80, 180, .25); }
        .ini-btn-main:hover { background: var(--clr-accent); }
        .ini-btn-ghost { background: var(--clr-card); color: var(--clr-active); border: 1.5px solid var(--clr-hover); }
        .ini-checks { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 18px; font-size: 12px; font-weight: 600; color: var(--clr-sub); }
        .ini-checks i { color: #3a8a3a; margin-right: 4px; }

        .ini-fan { position: relative; display: flex; justify-content: center; align-items: center; min-height: 280px; }
        .ini-fan::before { content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%; background: var(--purple-bg); }
        .ini-fan-card { position: relative; width: 150px; background: var(--clr-card); border-radius: 18px; padding: 10px 10px 12px; text-align: center; text-decoration: none; box-shadow: 0 10px 28px rgba(110, 60, 150, .18); transition: transform .3s ease; }
        .ini-fan-card img { width: 100%; height: 130px; object-fit: contain; display: block; }
        .ini-fan-card .ph { height: 130px; display: flex; align-items: center; justify-content: center; font-size: 32px; color: var(--clr-sub); }
        .ini-fan-name { font-size: 12px; font-weight: 800; color: var(--clr-text); margin-top: 6px; line-height: 1.25; }
        .ini-fan-price { display: inline-block; margin-top: 5px; padding: 2px 10px; border-radius: 12px; background: var(--purple-bg); color: var(--clr-active); font-size: 11.5px; font-weight: 800; }
        .ini-fan-card:nth-child(1) { transform: rotate(-8deg) translateX(18px); }
        .ini-fan-card:nth-child(2) { transform: scale(1.12); z-index: 2; }
        .ini-fan-card:nth-child(3) { transform: rotate(8deg) translateX(-18px); }
        .ini-fan-card:hover { z-index: 3; }
        .ini-fan-card:nth-child(1):hover { transform: rotate(-8deg) translateX(18px) translateY(-6px); }
        .ini-fan-card:nth-child(2):hover { transform: scale(1.12) translateY(-6px); }
        .ini-fan-card:nth-child(3):hover { transform: rotate(8deg) translateX(-18px) translateY(-6px); }

        .ini-sec-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin: 10px 0 14px; }
        .ini-sec-title { font-family: 'Playfair Display', serif; font-style: italic; font-size: 26px; color: var(--clr-text); }
        .ini-link { font-size: 12.5px; font-weight: 700; color: var(--clr-active); text-decoration: none; }
        .ini-link:hover { color: var(--clr-accent); }

        .ini-cats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 34px; }
        .ini-cat { display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 16px; background: var(--clr-card); text-decoration: none; transition: transform .25s ease, background .25s ease; }
        .ini-cat:hover { transform: translateY(-3px); background: var(--clr-hover); }
        .ini-cat-ico { width: 42px; height: 42px; border-radius: 50%; background: var(--purple-bg); color: var(--clr-active); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .ini-cat-name { display: block; font-size: 13.5px; font-weight: 800; color: var(--clr-text); line-height: 1.2; }
        .ini-cat-sub { display: block; font-size: 11.5px; color: var(--clr-sub); }

        .ini-feat { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 26px; }
        .ini-feat a.product-card { text-decoration: none; display: block; }
        .ini-cta { border-radius: 18px; padding: 22px; background: linear-gradient(150deg, #8e5cc0, #5f3590); color: #fff; display: flex; flex-direction: column; justify-content: space-between; gap: 18px; min-height: 260px; }
        .ini-cta i.spark { font-size: 22px; }
        .ini-cta h3 { font-family: 'Playfair Display', serif; font-style: italic; font-size: 24px; line-height: 1.2; margin-top: 10px; }
        .ini-cta small { font-size: 12.5px; opacity: .85; display: block; margin-bottom: 10px; }
        .ini-cta a { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 20px; background: #fff; color: #5f3590; font-size: 13px; font-weight: 800; text-decoration: none; }

        .ini-perks { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; padding: 18px; border-radius: 20px; background: var(--purple-bg); }
        .ini-perk { display: flex; align-items: center; gap: 12px; }
        .ini-perk-ico { width: 40px; height: 40px; border-radius: 50%; background: var(--clr-card); color: var(--clr-active); display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
        .ini-perk b { display: block; font-size: 13px; color: var(--clr-text); }
        .ini-perk span { font-size: 11.5px; color: var(--clr-sub); }

        @media (max-width: 1000px) { .ini-feat { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 860px) { .ini-hero { grid-template-columns: 1fr; } .ini-fan { min-height: 250px; } }
        @media (max-width: 560px) { .ini-feat { grid-template-columns: 1fr; } .ini-fan-card { width: 112px; } .ini-fan::before { width: 230px; height: 230px; } }
    </style>

    <div class="ini-wrap">

        @if (session('status'))
            <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif

        <section class="ini-hero">
            <div>
                <span class="ini-eyebrow">Productos capilares profesionales</span>
                <h1 class="ini-title">Tu cabello merece brillar con <em>Luciane</em></h1>
                <p class="ini-lead">Descubre nuestros tratamientos, shampoos y kits de alisado. Elige tus productos y haz tu pedido en pocos pasos, con tu dirección y teléfono guardados para la próxima vez.</p>

                <div class="ini-actions">
                    <a href="{{ url('/catalogo') }}" class="ini-btn ini-btn-main">Ver catálogo <i class="bi bi-arrow-right"></i></a>
                    @guest
                        <a href="{{ route('register') }}" class="ini-btn ini-btn-ghost">Crear cuenta</a>
                    @endguest
                    @auth
                        @unless($esStaff)
                            <a href="{{ route('pedidos.mis') }}" class="ini-btn ini-btn-ghost">Mis pedidos</a>
                        @endunless
                    @endauth
                </div>

                <div class="ini-checks">
                    <span><i class="bi bi-check-lg"></i>Stock en tiempo real</span>
                    <span><i class="bi bi-check-lg"></i>Entrega a domicilio</span>
                    <span><i class="bi bi-check-lg"></i>Sigue tu pedido</span>
                </div>
            </div>

            @if($destacados->count() > 0)
                <div class="ini-fan">
                    @foreach($destacados as $p)
                        <a href="{{ url('/catalogo?q=' . urlencode($p->nombre_producto)) }}" class="ini-fan-card">
                            @if($p->imagen_producto)
                                <img src="{{ asset('images/' . $p->imagen_producto) }}" alt="{{ $p->nombre_producto }}">
                            @else
                                <div class="ph"><i class="bi bi-image"></i></div>
                            @endif
                            <div class="ini-fan-name">{{ $p->nombre_producto }}</div>
                            <span class="ini-fan-price">Bs {{ number_format($p->precio, 2) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        @if($categorias->count() > 0)
            <div class="ini-sec-head">
                <h2 class="ini-sec-title">Explora por categoría</h2>
                <a href="{{ url('/catalogo') }}" class="ini-link">Ver todo el catálogo →</a>
            </div>
            <div class="ini-cats">
                @foreach($categorias as $cat)
                    <a href="{{ url('/catalogo?cat=' . urlencode($cat->nombre_categoria)) }}" class="ini-cat">
                        <span class="ini-cat-ico"><i class="bi {{ $iconos[\Illuminate\Support\Str::slug($cat->nombre_categoria)] ?? 'bi-grid-heart' }}"></i></span>
                        <span>
                            <span class="ini-cat-name">{{ $cat->nombre_categoria }}</span>
                            <span class="ini-cat-sub">{{ $cat->productos_count }} producto{{ $cat->productos_count !== 1 ? 's' : '' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        @if($destacados->count() > 0)
            <div class="ini-sec-head">
                <h2 class="ini-sec-title">Productos destacados</h2>
            </div>
            <div class="ini-feat">
                @foreach($destacados as $p)
                    <a href="{{ url('/catalogo?q=' . urlencode($p->nombre_producto)) }}" class="product-card">
                        <div class="card-img-wrap">
                            @if($p->imagen_producto)
                                <img src="{{ asset('images/' . $p->imagen_producto) }}" alt="{{ $p->nombre_producto }}">
                            @else
                                <div class="card-img-placeholder"><i class="bi bi-image"></i></div>
                            @endif
                            <span class="card-badge-stock-qty"><i class="bi bi-check-circle-fill"></i> En stock</span>
                        </div>
                        <div class="card-body">
                            <div class="cat-tag">{{ $p->categoria->nombre_categoria ?? '' }}</div>
                            <div class="product-name">{{ $p->nombre_producto }}</div>
                            <p class="product-desc-preview">{{ $p->descripcion }}</p>
                            <div class="card-footer-row">
                                <span class="product-price">Bs {{ number_format($p->precio, 2) }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach

                <div class="ini-cta">
                    <div>
                        <i class="bi bi-stars spark"></i>
                        <h3>Descubre todo el catálogo</h3>
                    </div>
                    <div>
                        <small>{{ $totalProductos }} producto{{ $totalProductos !== 1 ? 's' : '' }} disponibles</small>
                        <a href="{{ url('/catalogo') }}">Ver catálogo <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        @endif

        <div class="ini-perks">
            <div class="ini-perk"><span class="ini-perk-ico"><i class="bi bi-bag-check"></i></span><div><b>Pedido en minutos</b><span>Carrito y confirmación simples</span></div></div>
            <div class="ini-perk"><span class="ini-perk-ico"><i class="bi bi-geo-alt"></i></span><div><b>Tus datos guardados</b><span>Dirección y teléfono listos</span></div></div>
            <div class="ini-perk"><span class="ini-perk-ico"><i class="bi bi-box-seam"></i></span><div><b>Stock en tiempo real</b><span>Sabes qué hay disponible</span></div></div>
            <div class="ini-perk"><span class="ini-perk-ico"><i class="bi bi-truck"></i></span><div><b>Sigue tu pedido</b><span>Estados al instante</span></div></div>
        </div>

    </div>
</x-app-layout>
