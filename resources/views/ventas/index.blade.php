<x-app-layout>
    <div x-data="{
            showCreate: false,
            editingId: null,
            deleteVenta: null,
            eliminarVenta: null,
            verVenta: null,
            productos: {{ $productos->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre_producto, 'precio' => (float) $p->precio, 'stock' => $p->stock, 'imagen' => $p->imagen_producto ? asset('images/'.$p->imagen_producto) : null])->values()->toJson() }},
            clientes: {{ $clientes->map(fn($c) => ['id' => $c->id, 'nombre' => $c->name])->values()->toJson() }},
            clienteId: '',
            tipoPago: '',
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
            editarVenta(venta) {
                this.editingId = venta.id;
                this.clienteId = venta.id_cliente;
                this.tipoPago = venta.tipo_pago || '';
                this.cart = venta.items;
                this.stockAjuste = {};
                venta.items.forEach(i => { this.stockAjuste[i.id_producto] = (this.stockAjuste[i.id_producto] || 0) + i.cantidad; });
                this.selProdId = this.productos[0]?.id ?? '';
                this.cantidad = 1;
                this.showCreate = true;
            }
         }"
         x-init="$watch('showCreate', v => {
            if (v) {
                if (!editingId) {
                    clienteId = clientes[0]?.id ?? '';
                    tipoPago = '';
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
                <h1 class="text-3xl mb-1">Administrar Ventas</h1>
                <p>{{ $ventas->count() }} venta{{ $ventas->count() !== 1 ? 's' : '' }} registrada{{ $ventas->count() !== 1 ? 's' : '' }}</p>
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
                    'fecha|asc' => 'Más antiguas primero',
                    'total|desc' => 'Total: mayor a menor',
                    'total|asc' => 'Total: menor a mayor',
                    'cliente|asc' => 'Cliente (A-Z)',
                    'cliente|desc' => 'Cliente (Z-A)',
                    'situacion|asc' => 'Situación',
                ]" />
            </form>
            <button type="button" class="btn-add-user" @click="editingId = null; showCreate = true;">
                <i class="bi bi-plus-lg"></i> Nueva venta
            </button>
        </div>

        <div class="usr-table-card">
            @if($ventas->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-receipt"></i> No hay ventas registradas.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Pago</th>
                            <th>Fecha</th>
                            <th style="text-align:center;">Situación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ventas as $venta)
                            <tr>
                                <td>#{{ $venta->id }}</td>
                                <td style="font-weight:700;">{{ $venta->cliente->name ?? '—' }}</td>
                                <td>{{ $venta->vendedor->name ?? '—' }}</td>
                                <td>{{ $venta->detalles->count() }}</td>
                                <td>Bs. {{ number_format($venta->total, 2) }}</td>
                                <td>{{ $venta->tipoPagoEtiqueta() ?? '—' }}</td>
                                <td>{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="{{ $venta->situacion === 'completada' ? 'estado-activo' : 'estado-inactivo' }}">
                                        {{ ucfirst($venta->situacion) }}
                                    </span>
                                    <br>
                                    @if($venta->esManual())
                                        <span class="venta-origen venta-origen--manual" title="Registrada manualmente en Ventas">
                                            <i class="bi bi-pencil-square"></i> Manual
                                        </span>
                                    @else
                                        <span class="venta-origen venta-origen--pedido" title="Generada automáticamente al confirmar el pedido #{{ $venta->pedido->id }}">
                                            <i class="bi bi-bag-check-fill"></i> Pedido #{{ $venta->pedido->id }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar" title="Ver detalle"
                                                @click="verVenta = {
                                                    id: {{ $venta->id }},
                                                    cliente: '{{ addslashes($venta->cliente->name ?? '—') }}',
                                                    vendedor: '{{ addslashes($venta->vendedor->name ?? '—') }}',
                                                    fecha: '{{ $venta->fecha->format('d/m/Y H:i') }}',
                                                    total: {{ $venta->total }},
                                                    pago: {{ json_encode($venta->tipoPagoEtiqueta()) }},
                                                    items: {{ $venta->detalles->map(fn($d) => ['nombre' => $d->producto->nombre_producto ?? 'Producto eliminado', 'cantidad' => $d->cantidad, 'precio' => (float) $d->precio_unitario, 'subtotal' => (float) $d->subtotal, 'imagen' => $d->producto && $d->producto->imagen_producto ? asset('images/'.$d->producto->imagen_producto) : null])->values()->toJson() }}
                                                }">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        @if($venta->situacion === 'completada' && $venta->esManual())
                                            <button type="button" class="btn-editar" title="Editar venta"
                                                    @click="editarVenta({
                                                        id: {{ $venta->id }},
                                                        id_cliente: {{ $venta->id_cliente }},
                                                        tipo_pago: {{ json_encode($venta->tipo_pago) }},
                                                        items: {{ $venta->detalles->map(fn($d) => ['id_producto' => $d->id_producto, 'nombre' => $d->producto->nombre_producto ?? 'Producto eliminado', 'precio' => (float) $d->precio_unitario, 'cantidad' => $d->cantidad, 'subtotal' => (float) $d->subtotal, 'imagen' => $d->producto && $d->producto->imagen_producto ? asset('images/'.$d->producto->imagen_producto) : null])->values()->toJson() }}
                                                    })">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button type="button" class="btn-eliminar" title="Cancelar venta"
                                                    @click="deleteVenta = { id: {{ $venta->id }}, cliente: '{{ addslashes($venta->cliente->name ?? '—') }}' }">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        @elseif($venta->situacion === 'completada')
                                            <span class="venta-bloqueada" title="No se puede modificar: proviene de un pedido confirmado">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                        @endif
                                        <button type="button" class="btn-eliminar" title="Eliminar venta"
                                                @click="eliminarVenta = { id: {{ $venta->id }}, cliente: '{{ addslashes($venta->cliente->name ?? '—') }}' }">
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
                        <i class="bi bi-receipt"></i>
                    </span>
                    <h2 class="usr-modal-title" x-text="editingId ? 'Editar venta #' + editingId : 'Nueva venta'"></h2>
                </div>
                <div class="usr-modal-body">
                    <form method="POST" :action="editingId ? '{{ url('ventas') }}/' + editingId : '{{ route('ventas.store') }}'">
                        @csrf
                        <input type="hidden" name="_method" :value="editingId ? 'PUT' : 'POST'">
                        <div class="usr-card-main">
                            <label>Cliente</label>
                            <select name="id_cliente" x-model="clienteId" required>
                                <template x-for="c in clientes" :key="c.id">
                                    <option :value="c.id" x-text="c.nombre"></option>
                                </template>
                            </select>

                            <label style="margin-top:16px;">Tipo de pago</label>
                            <select name="tipo_pago" x-model="tipoPago" required>
                                <option value="" disabled>Selecciona…</option>
                                @foreach(\App\Models\Venta::TIPOS_PAGO as $clave => $etiqueta)
                                    <option value="{{ $clave }}">{{ $etiqueta }}</option>
                                @endforeach
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
                                    <button type="submit" class="usr-btn-submit" :disabled="cart.length === 0 || !clienteId || !tipoPago">
                                        <i class="bi bi-check-lg"></i> <span x-text="editingId ? 'Guardar cambios' : 'Registrar venta'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="verVenta" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="verVenta = null" class="usr-modal-box usr-modal-box--md">
                <button type="button" class="usr-modal-close" @click="verVenta = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="verVenta">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                                <i class="bi bi-receipt"></i>
                            </span>
                            <h2 class="usr-modal-title" x-text="'Venta #' + verVenta.id"></h2>
                        </div>
                        <div class="usr-modal-body">
                            <p style="font-size:13px; margin:0 0 4px;"><strong>Cliente:</strong> <span x-text="verVenta.cliente"></span></p>
                            <p style="font-size:13px; margin:0 0 4px;"><strong>Vendedor:</strong> <span x-text="verVenta.vendedor"></span></p>
                            <p style="font-size:13px; margin:0 0 4px;"><strong>Fecha:</strong> <span x-text="verVenta.fecha"></span></p>
                            <p style="font-size:13px; margin:0 0 14px;"><strong>Tipo de pago:</strong> <span x-text="verVenta.pago || '—'"></span></p>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <template x-for="item in verVenta.items" :key="item.nombre + item.cantidad">
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
                                <strong x-text="'Bs. ' + verVenta.total"></strong>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="deleteVenta" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteVenta = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteVenta = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="deleteVenta">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:rgba(220,80,80,.12); color:#b03030;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </span>
                            <h2 class="usr-modal-title">Cancelar venta</h2>
                        </div>
                        <div class="usr-modal-body">
                            <p class="usr-delete-msg">¿Seguro que deseas cancelar la venta de <strong x-text="deleteVenta.cliente"></strong>? El stock de los productos vendidos se repondrá automáticamente.</p>
                            <form :action="deleteVenta ? '{{ url('ventas') }}/' + deleteVenta.id : ''" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="deleteVenta = null">Volver</button>
                                    <button type="submit" class="usr-btn-submit" style="background:#b03030;"><i class="bi bi-x-circle-fill"></i> Cancelar venta</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="eliminarVenta" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="eliminarVenta = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="eliminarVenta = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="eliminarVenta">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:rgba(220,80,80,.12); color:#b03030;">
                                <i class="bi bi-trash3-fill"></i>
                            </span>
                            <h2 class="usr-modal-title">Eliminar venta</h2>
                        </div>
                        <div class="usr-modal-body">
                            <p class="usr-delete-msg">
                                ¿Seguro que deseas eliminar la venta a <strong x-text="eliminarVenta.cliente"></strong>?
                                Desaparecerá de este listado. Si el stock vendido aún no fue repuesto, se repondrá automáticamente.
                            </p>
                            <form :action="eliminarVenta ? '{{ url('ventas') }}/' + eliminarVenta.id + '/eliminar' : ''" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="eliminarVenta = null">Volver</button>
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
