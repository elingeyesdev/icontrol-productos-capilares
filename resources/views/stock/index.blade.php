<x-app-layout>
    <div x-data="{
            showCreate: false,
            deleteMov: null,
            productos: {{ $productos->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre_producto,
                'categoria' => $p->categoria->nombre_categoria ?? '—',
                'imagen' => $p->imagen_producto ? asset('images/'.$p->imagen_producto) : null,
                'stock' => $p->stock,
            ])->toJson() }},
            selProd: null,
            tipo: 'entrada',
            cantidad: 1
        }"
        x-init="$watch('showCreate', v => { if (v) { selProd = productos.length ? productos[0] : null; tipo = 'entrada'; cantidad = 1; } })">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Movimientos de Stock</h1>
                <p>{{ $totalMovimientos }} movimiento{{ $totalMovimientos !== 1 ? 's' : '' }} en este filtro</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-mint flex items-center justify-center text-lg mb-3" style="color:#2a7a5a;">
                    <i class="bi bi-arrow-down-circle-fill"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">+{{ number_format($totalEntradas) }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Unidades de entrada</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                <div class="w-10 h-10 rounded-xl bg-pastel-pink flex items-center justify-center text-lg mb-3" style="color:#a03060;">
                    <i class="bi bi-arrow-up-circle-fill"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">−{{ number_format($totalSalidas) }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Unidades de salida</div>
            </div>

            <div class="bg-brand-white rounded-2xl shadow-card p-5">
                @php $neto = $totalEntradas - $totalSalidas; @endphp
                <div class="w-10 h-10 rounded-xl bg-pastel-purple flex items-center justify-center text-lg mb-3" style="color:var(--clr-accent);">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div class="text-2xl font-extrabold text-brand-text">{{ $neto >= 0 ? '+' : '' }}{{ number_format($neto) }}</div>
                <div class="text-[12.5px] font-bold text-brand-sub">Movimiento neto</div>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif

        <div class="usr-toolbar mb-3">
            <div style="display:flex; gap:6px;">
                <a href="{{ request()->fullUrlWithQuery(['tipo' => null]) }}"
                   class="filter-pill {{ !request('tipo') ? 'filter-pill-active' : '' }}">
                    <i class="bi bi-list-ul"></i> Todos
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tipo' => 'entrada']) }}"
                   class="filter-pill {{ request('tipo') === 'entrada' ? 'filter-pill-active' : '' }}">
                    <i class="bi bi-arrow-down-circle-fill"></i> Entradas
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tipo' => 'salida']) }}"
                   class="filter-pill {{ request('tipo') === 'salida' ? 'filter-pill-active' : '' }}">
                    <i class="bi bi-arrow-up-circle-fill"></i> Salidas
                </a>
            </div>
            <button type="button" class="btn-add-user" @click="showCreate = true">
                <i class="bi bi-arrow-left-right"></i> Registrar Stock
            </button>
        </div>

        <form method="GET" class="mb-4" style="display:flex; flex-direction:column; gap:8px;">
            @if(request('tipo'))
                <input type="hidden" name="tipo" value="{{ request('tipo') }}">
            @endif
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <div class="nav-search" style="position:relative; max-width:260px;">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por producto…">
                </div>
                <select name="id_producto" class="filter-select" style="max-width:220px;">
                    <option value="">Todos los productos</option>
                    @foreach($productos as $p)
                        <option value="{{ $p->id }}" {{ request('id_producto') == $p->id ? 'selected' : '' }}>{{ $p->nombre_producto }}</option>
                    @endforeach
                </select>
                <input type="date" name="desde" value="{{ request('desde') }}" class="filter-input" style="max-width:150px;" title="Desde">
                <span style="font-size:12.5px; color:var(--clr-sub);">hasta</span>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="filter-input" style="max-width:150px;" title="Hasta">
                <button type="submit" class="btn-add-user"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                @if(request()->anyFilled(['buscar', 'id_producto', 'desde', 'hasta', 'tipo']))
                    <a href="{{ url()->current() }}" class="usr-btn-cancel" style="text-decoration:none; display:inline-flex; align-items:center;">Limpiar</a>
                @endif
            </div>
            <x-sort-select :sort="$sort" :dir="$dir" :options="[
                'fecha|desc' => 'Más recientes primero',
                'fecha|asc' => 'Más antiguos primero',
                'producto|asc' => 'Producto (A-Z)',
                'producto|desc' => 'Producto (Z-A)',
                'cantidad|desc' => 'Cantidad: mayor a menor',
                'cantidad|asc' => 'Cantidad: menor a mayor',
                'tipo|asc' => 'Tipo',
                'usuario|asc' => 'Registrado por',
            ]" />
        </form>

        <div class="usr-table-card">
            @if($movimientos->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-arrow-left-right"></i> No hay movimientos registrados.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align:center;">Tipo</th>
                            <th style="text-align:center;">Cantidad</th>
                            <th>Fecha</th>
                            <th>Registrado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimientos as $m)
                            @php $esEntrada = $m->tipo === 'entrada'; @endphp
                            <tr>
                                <td style="font-weight:700;">{{ $m->producto->nombre_producto ?? '—' }}</td>
                                <td>
                                    <span class="rol-badge" style="background:{{ $esEntrada ? '#e4f4ee' : '#fde9f0' }}; color:{{ $esEntrada ? '#2a7a5a' : '#a03060' }};">
                                        <i class="bi bi-arrow-{{ $esEntrada ? 'down' : 'up' }}-circle-fill"></i> {{ ucfirst($m->tipo) }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight:800; font-size:14px; color:{{ $esEntrada ? '#3a8a3a' : '#b03030' }};">
                                        {{ $esEntrada ? '+' : '−' }}{{ $m->cantidad }}
                                    </span>
                                </td>
                                <td style="color:var(--clr-sub); font-size:12.5px; white-space:nowrap;">
                                    {{ $m->fecha->format('d/m/Y H:i') }}
                                </td>
                                <td style="color:var(--clr-sub); font-size:12.5px;">
                                    {{ $m->usuario->name ?? '—' }}
                                </td>
                                <td>
                                    <button type="button" class="btn-eliminar"
                                            @click="deleteMov = { id: {{ $m->id }}, producto: '{{ addslashes($m->producto->nombre_producto ?? '') }}', tipo: '{{ $m->tipo }}', cantidad: {{ $m->cantidad }} }">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div x-show="showCreate" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="showCreate = false" class="usr-modal-box usr-modal-box--card">
                <button type="button" class="usr-modal-close" @click="showCreate = false"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header usr-modal-header--card">
                    <span class="usr-modal-icon" style="background:var(--mint-bg); color:#2a7a5a;"><i class="bi bi-arrow-left-right"></i></span>
                    <div>
                        <h2 class="usr-modal-title">Registrar <span>stock</span></h2>
                        <p class="usr-header-sub">Nuevo movimiento</p>
                    </div>
                </div>
                <div class="usr-modal-body usr-modal-body--flat">
                    <form method="POST" action="{{ route('stock.store') }}">
                        @csrf
                        <div class="usr-card-grid">

                            <div class="usr-card-aside">
                                <div class="usr-avatar-circle is-square">
                                    <div class="usr-avatar-photo" style="border-radius:18px;">
                                        <template x-if="selProd && selProd.imagen">
                                            <img :src="selProd.imagen" alt="Producto" style="border-radius:18px;">
                                        </template>
                                        <template x-if="!selProd || !selProd.imagen">
                                            <i class="bi bi-box-seam usr-avatar-placeholder"></i>
                                        </template>
                                    </div>
                                </div>
                                <div class="usr-card-name" x-text="selProd ? selProd.nombre : '—'"></div>
                                <div class="usr-card-badges">
                                    <span class="rol-badge" style="background:var(--purple-bg); color:#5a30a0;" x-text="selProd ? selProd.categoria : '—'"></span>
                                </div>
                                <div class="usr-stock-current">
                                    <span>Stock actual</span>
                                    <strong x-text="selProd ? selProd.stock : '—'"></strong>
                                </div>
                            </div>

                            <div class="usr-card-main">
                                <p class="usr-section-label">Producto</p>
                                <select @change="selProd = JSON.parse($event.target.value)" required>
                                    <template x-for="p in productos" :key="p.id">
                                        <option :value="JSON.stringify(p)" x-text="p.nombre"></option>
                                    </template>
                                </select>
                                <input type="hidden" name="id_producto" :value="selProd ? selProd.id : ''">

                                <p class="usr-section-label">Tipo de movimiento</p>
                                <div class="usr-segmented">
                                    <button type="button" class="usr-segmented-btn" :class="tipo === 'entrada' ? 'active' : ''"
                                            data-tipo="entrada" @click="tipo = 'entrada'">
                                        <i class="bi bi-arrow-down-circle-fill"></i> Entrada
                                    </button>
                                    <button type="button" class="usr-segmented-btn" :class="tipo === 'salida' ? 'active' : ''"
                                            data-tipo="salida" @click="tipo = 'salida'">
                                        <i class="bi bi-arrow-up-circle-fill"></i> Salida
                                    </button>
                                </div>
                                <input type="hidden" name="tipo" :value="tipo">

                                <p class="usr-section-label">Cantidad</p>
                                <div class="usr-stepper">
                                    <button type="button" @click="cantidad = Math.max(1, cantidad - 1)">−</button>
                                    <input type="number" name="cantidad" x-model.number="cantidad" min="1" required>
                                    <button type="button" @click="cantidad = cantidad + 1">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="usr-modal-footer">
                            <button type="button" class="usr-btn-cancel" @click="showCreate = false">Cancelar</button>
                            <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Registrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="deleteMov" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteMov = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteMov = null"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:#fde8e8; color:#b03030;"><i class="bi bi-trash3-fill"></i></span>
                    <h2 class="usr-modal-title" style="color:#b03030;">Eliminar <span style="color:#b03030;">Movimiento</span></h2>
                </div>
                <template x-if="deleteMov">
                    <div class="usr-modal-body">
                        <p class="usr-delete-msg">
                            ¿Seguro que deseas eliminar este movimiento de
                            <strong x-text="deleteMov.tipo"></strong> de
                            <strong x-text="deleteMov.cantidad"></strong> unidades de
                            <strong x-text="deleteMov.producto"></strong>?
                            El stock del producto se ajustará de vuelta automáticamente.
                        </p>
                        <form method="POST" :action="'/stock/' + deleteMov.id">
                            @csrf @method('DELETE')
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="deleteMov = null">Cancelar</button>
                                <button type="submit" class="usr-btn-delete"><i class="bi bi-trash3"></i> Eliminar</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
