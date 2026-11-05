<x-app-layout>
    <div x-data="{
            showCreate: false,
            editingId: null,
            eliminarPedido: null,
            confirmarPedido: null,
            verPedido: null,
            productos: {{ $productos->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre_producto, 'precio' => (float) $p->precio, 'stock' => $p->stock, 'imagen' => $p->imagen_producto ? asset('images/'.$p->imagen_producto) : null])->values()->toJson() }},
            clientes: {{ $clientes->map(fn($c) => ['id' => $c->id, 'nombre' => $c->name])->values()->toJson() }},
            clienteId: '',
            selProdId: '',
            cantidad: 1,
            cart: [],
            stockAjuste: {},
            get selProd() { return this.productos.find(p => p.id == this.selProdId) || null; },
            get total() { return this.cart.reduce((sum, i) => sum + i.subtotal, 0); },
            stockDisponible(producto) { return producto.stock + (this.stockAjuste[producto.id] || 0); },
            agregarItem() {
                if (!this.selProd || this.cantidad < 1) return;
                const limite = this.stockDisponible(this.selProd);
                if (this.cantidad > limite) { alert('No hay suficiente stock disponible.'); return; }
                const existente = this.cart.find(i => i.id_producto === this.selProd.id);
                if (existente) {
                    if (existente.cantidad + this.cantidad > limite) { alert('No hay suficiente stock disponible.'); return; }
                    existente.cantidad += this.cantidad;
                    existente.subtotal = existente.cantidad * existente.precio;
                } else {
                    this.cart.push({ id_producto: this.selProd.id, nombre: this.selProd.nombre, precio: this.selProd.precio, cantidad: this.cantidad, subtotal: this.selProd.precio * this.cantidad, imagen: this.selProd.imagen });
                }
                this.cantidad = 1;
                this.selProdId = this.productos[0]?.id ?? '';
            },
            quitarItem(id) { this.cart = this.cart.filter(i => i.id_producto !== id); },
            editarPedido(pedido) {
                this.editingId = pedido.id;
                this.clienteId = pedido.id_cliente;
                this.cart = pedido.items;
                this.stockAjuste = {};
                pedido.items.forEach(i => { this.stockAjuste[i.id_producto] = (this.stockAjuste[i.id_producto] || 0) + i.cantidad; });
                this.selProdId = this.productos[0]?.id ?? '';
                this.cantidad = 1;
                this.showCreate = true;
            }
         }"
         x-init="$watch('showCreate', v => {
            if (v) {
                if (!editingId) {
                    clienteId = clientes[0]?.id ?? '';
                    cart = [];
                    stockAjuste = {};
                    selProdId = productos[0]?.id ?? '';
                    cantidad = 1;
                }
            } else {
                editingId = null;
            }
         })">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Administrar Pedidos</h1>
                <p>{{ $pedidos->count() }} pedido{{ $pedidos->count() !== 1 ? 's' : '' }} registrado{{ $pedidos->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div class="nav-search" style="position:relative; max-width:300px;">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por cliente…">
                    </div>
                    <button type="submit" class="btn-add-user"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <x-sort-select :sort="$sort" :dir="$dir" :options="[
                    'fecha|desc' => 'Más recientes primero',
                    'fecha|asc' => 'Más antiguos primero',
                    'total|desc' => 'Total: mayor a menor',
                    'total|asc' => 'Total: menor a mayor',
                    'cliente|asc' => 'Cliente (A-Z)',
                    'cliente|desc' => 'Cliente (Z-A)',
                    'estado|asc' => 'Estado',
                ]" />
            </form>
            <button type="button" class="btn-add-user" @click="editingId = null; showCreate = true;">
                <i class="bi bi-plus-lg"></i> Nuevo pedido
            </button>
        </div>

        <div class="usr-table-card">
            @if($pedidos->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-bag-check"></i> No hay pedidos registrados.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th>Cliente</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th style="text-align:center;">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedidos as $pedido)
                            <tr>
                                <td>#{{ $pedido->id }}</td>
                                <td style="font-weight:700;">{{ $pedido->cliente->name ?? '—' }}</td>
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
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar" title="Ver detalle"
                                                @click="verPedido = {
                                                    id: {{ $pedido->id }},
                                                    cliente: '{{ addslashes($pedido->cliente->name ?? '—') }}',
                                                    fecha: '{{ $pedido->fecha->format('d/m/Y H:i') }}',
                                                    total: {{ $pedido->total }},
                                                    items: {{ $pedido->detalles->map(fn($d) => ['nombre' => $d->producto->nombre_producto ?? 'Producto eliminado', 'cantidad' => $d->cantidad, 'precio' => (float) $d->precio_unitario, 'subtotal' => (float) $d->subtotal, 'imagen' => $d->producto && $d->producto->imagen_producto ? asset('images/'.$d->producto->imagen_producto) : null])->values()->toJson() }}
                                                }">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>

                                        @if(in_array($pedido->estado, ['pendiente', 'confirmado']))
                                            <button type="button" class="btn-editar" title="Confirmar pedido" style="background:rgba(90,180,120,.15); color:#2e7d4f;"
                                                    @click="confirmarPedido = { id: {{ $pedido->id }}, cliente: '{{ addslashes($pedido->cliente->name ?? '—') }}', total: {{ $pedido->total }} }">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        @endif

                                        @if(in_array($pedido->estado, ['pendiente', 'confirmado']))
                                            <form method="POST" action="{{ route('pedidos.estado', $pedido) }}"
                                                  onsubmit="return confirm('¿Cancelar este pedido? El stock se revertirá.');">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="estado" value="cancelado">
                                                <button type="submit" class="btn-eliminar" title="Cancelar pedido">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if(in_array($pedido->estado, ['pendiente', 'confirmado']))
                                            <button type="button" class="btn-editar" title="Editar pedido"
                                                    @click="editarPedido({
                                                        id: {{ $pedido->id }},
                                                        id_cliente: {{ $pedido->id_cliente }},
                                                        items: {{ $pedido->detalles->map(fn($d) => ['id_producto' => $d->id_producto, 'nombre' => $d->producto->nombre_producto ?? 'Producto eliminado', 'precio' => (float) $d->precio_unitario, 'cantidad' => $d->cantidad, 'subtotal' => (float) $d->subtotal, 'imagen' => $d->producto && $d->producto->imagen_producto ? asset('images/'.$d->producto->imagen_producto) : null])->values()->toJson() }}
                                                    })">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                        @endif

                                        <button type="button" class="btn-eliminar" title="Eliminar pedido"
                                                @click="eliminarPedido = { id: {{ $pedido->id }}, cliente: '{{ addslashes($pedido->cliente->name ?? '—') }}' }">
                                            <i class="bi bi-trash3-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div x-show="showCreate" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="showCreate = false" class="usr-modal-box">
                <button type="button" class="usr-modal-close" @click="showCreate = false"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                        <i class="bi bi-bag-check"></i>
                    </span>
                    <h2 class="usr-modal-title" x-text="editingId ? 'Editar pedido #' + editingId : 'Nuevo pedido'"></h2>
                </div>
                <div class="usr-modal-body">
                    <form method="POST" :action="editingId ? '{{ url('pedidos') }}/' + editingId : '{{ route('pedidos.store') }}'">
                        @csrf
                        <input type="hidden" name="_method" :value="editingId ? 'PUT' : 'POST'">
                        <div class="usr-card-main">
                            <label>Cliente</label>
                            <select name="id_cliente" x-model="clienteId" required>
                                <template x-for="c in clientes" :key="c.id">
                                    <option :value="c.id" x-text="c.nombre"></option>
                                </template>
                            </select>

                            <p class="usr-section-label" style="margin-top:16px;">Agregar producto</p>
                            <div class="usr-field-row usr-field-row--producto">
                                <div>
                                    <select x-model="selProdId">
                                        <template x-for="p in productos" :key="p.id">
                                            <option :value="p.id" x-text="p.nombre + ' — Bs. ' + p.precio.toFixed(2) + ' (stock: ' + stockDisponible(p) + ')'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="usr-stepper">
                                    <button type="button" @click="cantidad = Math.max(1, cantidad - 1)">−</button>
                                    <input type="number" x-model.number="cantidad" min="1">
                                    <button type="button" @click="cantidad = cantidad + 1">+</button>
                                </div>
                            </div>
                            <button type="button" class="btn-add-user" style="width:100%; justify-content:center; margin-top:8px;" @click="agregarItem()">
                                <i class="bi bi-plus-lg"></i> Agregar al carrito
                            </button>

                            <p class="usr-section-label" style="margin-top:16px;">Carrito</p>
                            <template x-if="cart.length === 0">
                                <p class="mensaje-vacio" style="padding:14px 0;">Aún no agregaste productos.</p>
                            </template>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <template x-for="(item, idx) in cart" :key="item.id_producto">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; background:var(--clr-bg); border-radius:10px;">
                                        <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                                            <template x-if="item.imagen">
                                                <img :src="item.imagen" :alt="item.nombre" class="item-thumb">
                                            </template>
                                            <template x-if="!item.imagen">
                                                <span class="item-thumb-placeholder"><i class="bi bi-image"></i></span>
                                            </template>
                                            <div style="min-width:0;">
                                                <div style="font-weight:700; font-size:13px;" x-text="item.nombre"></div>
                                                <div style="font-size:11.5px; color:var(--clr-sub);" x-text="item.cantidad + ' x Bs. ' + item.precio.toFixed(2) + ' = Bs. ' + item.subtotal.toFixed(2)"></div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-eliminar" style="width:28px; height:28px; flex-shrink:0;" @click="quitarItem(item.id_producto)">
                                            <i class="bi bi-x-lg" style="font-size:11px;"></i>
                                        </button>
                                        <input type="hidden" :name="'items[' + idx + '][id_producto]'" :value="item.id_producto">
                                        <input type="hidden" :name="'items[' + idx + '][cantidad]'" :value="item.cantidad">
                                    </div>
                                </template>
                            </div>

                            <div class="usr-modal-footer usr-modal-footer--stacked">
                                <div class="usr-total-inline">
                                    <span>Total</span>
                                    <strong x-text="'Bs. ' + total.toFixed(2)"></strong>
                                </div>
                                <div style="display:flex; gap:10px; justify-content:flex-end;">
                                    <button type="button" class="usr-btn-cancel" @click="showCreate = false">Cancelar</button>
                                    <button type="submit" class="usr-btn-submit" :disabled="cart.length === 0 || !clienteId">
                                        <i class="bi bi-check-lg"></i> <span x-text="editingId ? 'Guardar cambios' : 'Registrar pedido'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="verPedido" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="verPedido = null" class="usr-modal-box usr-modal-box--md">
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
                            <p style="font-size:13px; margin:0 0 4px;"><strong>Cliente:</strong> <span x-text="verPedido.cliente"></span></p>
                            <p style="font-size:13px; margin:0 0 14px;"><strong>Fecha:</strong> <span x-text="verPedido.fecha"></span></p>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <template x-for="item in verPedido.items" :key="item.nombre + item.cantidad">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; background:var(--clr-bg); border-radius:10px;">
                                        <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                                            <template x-if="item.imagen">
                                                <img :src="item.imagen" :alt="item.nombre" class="item-thumb">
                                            </template>
                                            <template x-if="!item.imagen">
                                                <span class="item-thumb-placeholder"><i class="bi bi-image"></i></span>
                                            </template>
                                            <div style="min-width:0;">
                                                <div style="font-weight:700; font-size:13px;" x-text="item.nombre"></div>
                                                <div style="font-size:11.5px; color:var(--clr-sub);" x-text="item.cantidad + ' x Bs. ' + item.precio.toFixed(2)"></div>
                                            </div>
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

        <div x-show="confirmarPedido" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="confirmarPedido = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="confirmarPedido = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="confirmarPedido">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:rgba(90,180,120,.15); color:#2e7d4f;">
                                <i class="bi bi-check-lg"></i>
                            </span>
                            <h2 class="usr-modal-title">Confirmar pedido</h2>
                        </div>
                        <div class="usr-modal-body">
                            <p class="usr-delete-msg">
                                ¿Estás seguro de confirmar el pedido #<strong x-text="confirmarPedido.id"></strong>
                                de <strong x-text="confirmarPedido.cliente"></strong>?
                                Se registrará como venta y quedará entregado. Revisa que no haya cambios de último momento,
                                porque después ya no podrá modificarse.
                            </p>
                            <form :action="confirmarPedido ? '{{ url('pedidos') }}/' + confirmarPedido.id + '/estado' : ''" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="estado" value="entregado">
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="confirmarPedido = null">Volver</button>
                                    <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Confirmar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="eliminarPedido" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="eliminarPedido = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="eliminarPedido = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="eliminarPedido">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:rgba(220,80,80,.12); color:#b03030;">
                                <i class="bi bi-trash3-fill"></i>
                            </span>
                            <h2 class="usr-modal-title">Eliminar pedido</h2>
                        </div>
                        <div class="usr-modal-body">
                            <p class="usr-delete-msg">
                                ¿Seguro que deseas eliminar el pedido de <strong x-text="eliminarPedido.cliente"></strong>?
                                Desaparecerá de este listado.
                            </p>
                            <form :action="eliminarPedido ? '{{ url('pedidos') }}/' + eliminarPedido.id : ''" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="eliminarPedido = null">Volver</button>
                                    <button type="submit" class="usr-btn-delete"><i class="bi bi-trash3-fill"></i> Eliminar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
