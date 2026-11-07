<x-app-layout>

    @php
        $filtro = request('cat', 'Todos');
        $busqueda = request('q');
        $categorias = \App\Models\Categoria::where('estado', true)->orderBy('nombre_categoria')->get();

        $query = \App\Models\Producto::with('categoria')->where('estado', true);
        if ($filtro !== 'Todos') {
            $query->whereHas('categoria', fn($q) => $q->where('nombre_categoria', $filtro));
        }
        if ($busqueda) {
            $query->where('nombre_producto', 'like', "%{$busqueda}%");
        }
        $productosFiltrados = $query->orderBy('nombre_producto')->get();
    @endphp

    <div x-data="{
            verProd: null,
            cantidad: 1
         }"
         x-init="$watch('verProd', v => { cantidad = 1; })">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">
                    {{ $busqueda ? 'Resultados para "' . $busqueda . '"' : ($filtro === 'Todos' ? 'Todo el catálogo' : $filtro) }}
                </h1>
                <p>{{ $productosFiltrados->count() }} producto{{ $productosFiltrados->count() !== 1 ? 's' : '' }} encontrado{{ $productosFiltrados->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif

        <div class="filter-pills-row flex flex-wrap gap-2 mb-6"
             x-data
             x-init="$nextTick(() => {
                 const a = $el.querySelector('.filter-pill-active');
                 if (a && $el.scrollWidth > $el.clientWidth) {
                     $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2;
                 }
             })">
            <a href="{{ url('/catalogo') }}" class="filter-pill {{ $filtro === 'Todos' && !$busqueda ? 'filter-pill-active' : '' }}">
                <i class="bi bi-grid-heart"></i> Todos
            </a>
            @foreach($categorias as $cat)
                <a href="{{ url('/catalogo?cat=' . urlencode($cat->nombre_categoria)) }}"
                   class="filter-pill {{ $filtro === $cat->nombre_categoria ? 'filter-pill-active' : '' }}">
                    {{ $cat->nombre_categoria }}
                </a>
            @endforeach
        </div>

        @if($productosFiltrados->count() > 0)
            <div class="products-grid">
                @foreach($productosFiltrados as $p)
                    <div class="product-card" style="cursor:pointer;"
                         @click="verProd = {
                            id: {{ $p->id }},
                            nombre: {{ Js::from($p->nombre_producto) }},
                            descripcion: {{ Js::from($p->descripcion) }},
                            precio: {{ $p->precio }},
                            stock: {{ $p->stock }},
                            categoria: {{ Js::from($p->categoria->nombre_categoria ?? '') }},
                            imagen: {{ $p->imagen_producto ? Js::from(asset('images/'.$p->imagen_producto)) : 'null' }}
                         }">
                        <div class="card-img-wrap">
                            @if($p->imagen_producto)
                                <img src="{{ asset('images/' . $p->imagen_producto) }}" alt="{{ $p->nombre_producto }}">
                            @else
                                <div class="card-img-placeholder"><i class="bi bi-image"></i></div>
                            @endif

                            @if($p->stock > 0)
                                <span class="card-badge-stock-qty"><i class="bi bi-check-circle-fill"></i> En stock</span>
                            @else
                                <span class="card-badge-stock-qty out"><i class="bi bi-x-circle-fill"></i> Agotado</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="cat-tag">{{ $p->categoria->nombre_categoria ?? '' }}</div>
                            <div class="product-name">{{ $p->nombre_producto }}</div>
                            <p class="product-desc-preview">{{ $p->descripcion }}</p>
                            <div class="card-footer-row">
                                <span class="product-price">Bs {{ number_format($p->precio, 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-search-heart"></i>
                <p>No se encontraron productos.</p>
                <a href="{{ url('/catalogo') }}">Ver todos los productos →</a>
            </div>
        @endif

        <div x-show="verProd" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="verProd = null" class="usr-modal-box usr-modal-box--card product-view-modal">
                <button type="button" class="usr-modal-close" @click="verProd = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="verProd">
                    <div class="usr-modal-body usr-modal-body--flat">
                        <div class="usr-card-grid">
                            <div class="usr-card-aside">
                                <div class="usr-avatar-circle" style="border-radius:16px;">
                                    <div class="usr-avatar-photo" style="border-radius:16px;">
                                        <template x-if="verProd.imagen"><img :src="verProd.imagen" alt="Producto" style="border-radius:16px;"></template>
                                        <template x-if="!verProd.imagen"><i class="bi bi-image" style="font-size:26px; color:var(--clr-sub);"></i></template>
                                    </div>
                                </div>
                                <div class="usr-card-name" x-text="verProd.nombre"></div>
                                <div class="usr-card-badges">
                                    <span class="rol-badge rol-default" x-text="verProd.categoria"></span>
                                    <span class="rol-badge" style="background:var(--purple-bg); color:#5a30a0;" x-text="'Bs. ' + parseFloat(verProd.precio).toFixed(2)"></span>
                                </div>
                            </div>

                            <div class="usr-card-main">
                                <p class="usr-section-label">Descripción</p>
                                <p style="font-size:13.5px; color:var(--clr-text); line-height:1.5;" x-text="verProd.descripcion"></p>

                                <div class="usr-stock-current" style="margin-top:10px;">
                                    <span>Disponible</span>
                                    <strong x-text="verProd.stock + ' un.'"></strong>
                                </div>

                                <template x-if="verProd.stock > 0">
                                    <div style="margin-top:16px;">
                                        <p class="usr-section-label" style="margin-bottom:8px;">Cantidad</p>
                                        <div style="display:flex; align-items:center; justify-content:center; gap:16px; flex-wrap:wrap;">
                                            <div class="usr-stepper">
                                                <button type="button" @click="cantidad = Math.max(1, cantidad - 1)">−</button>
                                                <input type="number" x-model.number="cantidad" min="1" :max="verProd.stock">
                                                <button type="button" @click="cantidad = Math.min(verProd.stock, cantidad + 1)">+</button>
                                            </div>
                                            <div class="usr-total-inline">
                                                <span>Total</span>
                                                <strong x-text="'Bs. ' + (verProd.precio * cantidad).toFixed(2)"></strong>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-add-user" style="width:100%; justify-content:center; margin-top:14px;"
                                                @click="$store.cart.add(verProd.id, cantidad).then(ok => { if (ok) verProd = null; })">
                                            <i class="bi bi-cart-plus-fill"></i> Agregar al carrito
                                        </button>
                                    </div>
                                </template>
                                <template x-if="verProd.stock <= 0">
                                    <p class="usr-delete-msg" style="margin-top:16px;">Este producto está agotado por el momento.</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
