<x-app-layout>
    <div x-data="{
            showCreate: false,
            editingId: null,
            deleteCompra: null,
            eliminarCompra: null,
            verCompra: null,
            productos: {{ $productos->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre_producto, 'precio' => (float) $p->precio, 'imagen' => $p->imagen_producto ? asset('images/'.$p->imagen_producto) : null])->values()->toJson() }},
            proveedores: {{ $proveedores->map(fn($p) => ['id' => $p->id, 'nombre' => $p->nombre_empresa])->values()->toJson() }},
            proveedorId: '',
            selProdId: '',
            cantidad: 1,
            precioUnitario: '',
            cart: [],
            get selProd() { return this.productos.find(p => p.id == this.selProdId) || null; },
            get total() { return this.cart.reduce((sum, i) => sum + i.subtotal, 0); },
            seleccionarProducto() {
                if (this.selProd) this.precioUnitario = this.selProd.precio.toFixed(2);
            },
            agregarItem() {
                if (!this.selProd || this.cantidad < 1) return;
                const precio = parseFloat(this.precioUnitario);
                if (isNaN(precio) || precio <= 0) { alert('Ingresa un precio de compra válido.'); return; }
                const existente = this.cart.find(i => i.id_producto === this.selProd.id);
                if (existente) {
                    existente.cantidad += this.cantidad;
                    existente.subtotal = existente.cantidad * existente.precio;
                } else {
                    this.cart.push({ id_producto: this.selProd.id, nombre: this.selProd.nombre, precio: precio, cantidad: this.cantidad, subtotal: precio * this.cantidad, imagen: this.selProd.imagen });
                }
                this.cantidad = 1;
                this.selProdId = this.productos[0]?.id ?? '';
                this.seleccionarProducto();
            },
            quitarItem(id) { this.cart = this.cart.filter(i => i.id_producto !== id); },
            editarCompra(compra) {
                this.editingId = compra.id;
                this.proveedorId = compra.id_proveedor;
                this.cart = compra.items;
                this.selProdId = this.productos[0]?.id ?? '';
                this.cantidad = 1;
                this.seleccionarProducto();
                this.showCreate = true;
            }
         }"
         x-init="$watch('showCreate', v => {
            if (v) {
                if (!editingId) {
                    proveedorId = proveedores[0]?.id ?? '';
                    cart = [];
                    selProdId = productos[0]?.id ?? '';
                    cantidad = 1;
                    seleccionarProducto();
                }
            } else {
                editingId = null;
            }
         })">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Compras</h1>
                <p>{{ $compras->count() }} compra{{ $compras->count() !== 1 ? 's' : '' }} registrada{{ $compras->count() !== 1 ? 's' : '' }}</p>
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
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por proveedor…">
                    </div>
                    <button type="submit" class="btn-add-user"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <x-sort-select :sort="$sort" :dir="$dir" :options="[
                    'fecha|desc' => 'Más recientes primero',
                    'fecha|asc' => 'Más antiguas primero',
                    'total|desc' => 'Total: mayor a menor',
                    'total|asc' => 'Total: menor a mayor',
                    'proveedor|asc' => 'Proveedor (A-Z)',
                    'proveedor|desc' => 'Proveedor (Z-A)',
                    'usuario|asc' => 'Registrado por',
                    'situacion|asc' => 'Situación',
                ]" />
            </form>
            <button type="button" class="btn-add-user" @click="editingId = null; showCreate = true;">
                <i class="bi bi-plus-lg"></i> Nueva compra
            </button>
        </div>

        <div class="usr-table-card">
            @if($compras->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-bag"></i> No hay compras registradas.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th>Proveedor</th>
                            <th>Registrado por</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th style="text-align:center;">Situación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compras as $compra)
                            <tr>
                                <td>#{{ $compra->id }}</td>
                                <td style="font-weight:700;">{{ $compra->proveedor->nombre_empresa ?? '—' }}</td>
                                <td>{{ $compra->usuario->name ?? '—' }}</td>
                                <td>{{ $compra->detalles->count() }}</td>
                                <td>Bs. {{ number_format($compra->total, 2) }}</td>
                                <td>{{ $compra->fecha->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="{{ $compra->situacion === 'completada' ? 'estado-activo' : 'estado-inactivo' }}">
                                        {{ ucfirst($compra->situacion) }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar" title="Ver detalle"
                                                @click="verCompra = {
                                                    id: {{ $compra->id }},
                                                    proveedor: '{{ addslashes($compra->proveedor->nombre_empresa ?? '—') }}',
                                                    registrado_por: '{{ addslashes($compra->usuario->name ?? '—') }}',
                                                    fecha: '{{ $compra->fecha->format('d/m/Y H:i') }}',
                                                    total: {{ $compra->total }},
                                                    items: {{ $compra->detalles->map(fn($d) => ['nombre' => $d->producto->nombre_producto ?? 'Producto eliminado', 'cantidad' => $d->cantidad, 'precio' => (float) $d->precio_unitario, 'subtotal' => (float) $d->subtotal, 'imagen' => $d->producto && $d->producto->imagen_producto ? asset('images/'.$d->producto->imagen_producto) : null])->values()->toJson() }}
                                                }">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        @if($compra->situacion === 'completada')
                                            <button type="button" class="btn-editar" title="Editar compra"
                                                    @click="editarCompra({
                                                        id: {{ $compra->id }},
                                                        id_proveedor: {{ $compra->id_proveedor }},
                                                        items: {{ $compra->detalles->map(fn($d) => ['id_producto' => $d->id_producto, 'nombre' => $d->producto->nombre_producto ?? 'Producto eliminado', 'precio' => (float) $d->precio_unitario, 'cantidad' => $d->cantidad, 'subtotal' => (float) $d->subtotal, 'imagen' => $d->producto && $d->producto->imagen_producto ? asset('images/'.$d->producto->imagen_producto) : null])->values()->toJson() }}
                                                    })">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <button type="button" class="btn-eliminar" title="Cancelar compra"
                                                    @click="deleteCompra = { id: {{ $compra->id }}, proveedor: '{{ addslashes($compra->proveedor->nombre_empresa ?? '—') }}' }">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn-eliminar" title="Eliminar compra"
                                                @click="eliminarCompra = { id: {{ $compra->id }}, proveedor: '{{ addslashes($compra->proveedor->nombre_empresa ?? '—') }}' }">
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
                        <i class="bi bi-bag"></i>
                    </span>
                    <h2 class="usr-modal-title" x-text="editingId ? 'Editar compra #' + editingId : 'Nueva compra'"></h2>
                </div>
                <div class="usr-modal-body">
                    <form method="POST" :action="editingId ? '{{ url('compras') }}/' + editingId : '{{ route('compras.store') }}'">
                        @csrf
                        <input type="hidden" name="_method" :value="editingId ? 'PUT' : 'POST'">
                        <div class="usr-card-main">
                            <label>Proveedor</label>
                            <select name="id_proveedor" x-model="proveedorId" required>
                                <template x-for="p in proveedores" :key="p.id">
                                    <option :value="p.id" x-text="p.nombre"></option>
                                </template>
                            </select>

                            <p class="usr-section-label" style="margin-top:16px;">Agregar producto</p>
                            <div class="usr-field-row usr-field-row--producto">
                                <div>
                                    <select x-model="selProdId" @change="seleccionarProducto()">
                                        <template x-for="p in productos" :key="p.id">
                                            <option :value="p.id" x-text="p.nombre"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="usr-stepper">
                                    <button type="button" @click="cantidad = Math.max(1, cantidad - 1)">−</button>
                                    <input type="number" x-model.number="cantidad" min="1">
                                    <button type="button" @click="cantidad = cantidad + 1">+</button>
                                </div>
                            </div>
                            <label style="margin-top:10px;">Precio de compra (unitario)</label>
                            <input type="number" x-model="precioUnitario" step="0.01" min="0.01" placeholder="0.00">

                            <button type="button" class="btn-add-user" style="width:100%; justify-content:center; margin-top:10px;" @click="agregarItem()">
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
                                        <input type="hidden" :name="'items[' + idx + '][precio_unitario]'" :value="item.precio">
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
                                    <button type="submit" class="usr-btn-submit" :disabled="cart.length === 0 || !proveedorId">
                                        <i class="bi bi-check-lg"></i> <span x-text="editingId ? 'Guardar cambios' : 'Registrar compra'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="verCompra" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="verCompra = null" class="usr-modal-box usr-modal-box--md">
                <button type="button" class="usr-modal-close" @click="verCompra = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="verCompra">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                                <i class="bi bi-bag"></i>
                            </span>
                            <h2 class="usr-modal-title" x-text="'Compra #' + verCompra.id"></h2>
                        </div>
                        <div class="usr-modal-body">
                            <p style="font-size:13px; margin:0 0 4px;"><strong>Proveedor:</strong> <span x-text="verCompra.proveedor"></span></p>
                            <p style="font-size:13px; margin:0 0 4px;"><strong>Registrado por:</strong> <span x-text="verCompra.registrado_por"></span></p>
                            <p style="font-size:13px; margin:0 0 14px;"><strong>Fecha:</strong> <span x-text="verCompra.fecha"></span></p>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <template x-for="item in verCompra.items" :key="item.nombre + item.cantidad">
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
                                <strong x-text="'Bs. ' + verCompra.total"></strong>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="deleteCompra" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteCompra = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteCompra = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="deleteCompra">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:rgba(220,80,80,.12); color:#b03030;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </span>
                            <h2 class="usr-modal-title">Cancelar compra</h2>
                        </div>
                        <div class="usr-modal-body">
                            <p class="usr-delete-msg">¿Seguro que deseas cancelar la compra a <strong x-text="deleteCompra.proveedor"></strong>? El stock agregado por esta compra se revertirá automáticamente.</p>
                            <form :action="deleteCompra ? '{{ url('compras') }}/' + deleteCompra.id : ''" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="deleteCompra = null">Volver</button>
                                    <button type="submit" class="usr-btn-submit" style="background:#b03030;"><i class="bi bi-x-circle-fill"></i> Cancelar compra</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="eliminarCompra" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="eliminarCompra = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="eliminarCompra = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="eliminarCompra">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:rgba(220,80,80,.12); color:#b03030;">
                                <i class="bi bi-trash3-fill"></i>
                            </span>
                            <h2 class="usr-modal-title">Eliminar compra</h2>
                        </div>
                        <div class="usr-modal-body">
                            <p class="usr-delete-msg">
                                ¿Seguro que deseas eliminar la compra a <strong x-text="eliminarCompra.proveedor"></strong>?
                                Desaparecerá de este listado. Si el stock que agregó aún no fue revertido, se revertirá automáticamente.
                            </p>
                            <form :action="eliminarCompra ? '{{ url('compras') }}/' + eliminarCompra.id + '/eliminar' : ''" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="eliminarCompra = null">Volver</button>
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
