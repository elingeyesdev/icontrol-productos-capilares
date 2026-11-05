<x-app-layout>
    <div x-data="{
            showCreate: false,
            editProd: null,
            deleteProd: null,
            categorias: {{ $categorias->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre_categoria])->toJson() }}
        }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Administrar Productos</h1>
                <p>{{ $productos->count() }} producto{{ $productos->count() !== 1 ? 's' : '' }} registrado{{ $productos->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                No se pudo guardar: {{ $errors->first() }}
            </div>
        @endif

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div class="nav-search" style="position:relative; max-width:300px;">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar producto…">
                    </div>
                    <button type="submit" class="btn-add-user"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <x-sort-select :sort="$sort" :dir="$dir" :options="[
                    'id|desc' => 'Más recientes primero',
                    'id|asc' => 'Más antiguos primero',
                    'nombre|asc' => 'Nombre (A-Z)',
                    'nombre|desc' => 'Nombre (Z-A)',
                    'categoria|asc' => 'Categoría',
                    'precio|desc' => 'Precio: mayor a menor',
                    'precio|asc' => 'Precio: menor a mayor',
                    'stock|desc' => 'Stock: mayor a menor',
                    'stock|asc' => 'Stock: menor a mayor',
                ]" />
            </form>
            <button type="button" class="btn-add-user" @click="showCreate = true">
                <i class="bi bi-box-seam-fill"></i> Nuevo Producto
            </button>
        </div>

        <div class="usr-table-card">
            @if($productos->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-box-seam"></i> No hay productos registrados.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th style="text-align:center;">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $p)
                            <tr>
                                <td>#{{ $p->id }}</td>
                                <td>
                                    @if($p->imagen_producto)
                                        <img src="{{ asset('images/' . $p->imagen_producto) }}" alt="{{ $p->nombre_producto }}" class="imagen-tabla">
                                    @else
                                        <div class="imagen-tabla" style="display:flex;align-items:center;justify-content:center;background:var(--clr-sidebar);">
                                            <i class="bi bi-image" style="color:var(--clr-sub);"></i>
                                        </div>
                                    @endif
                                </td>
                                <td style="font-weight:700; text-align:left;">{{ $p->nombre_producto }}</td>
                                <td>
                                    <span class="rol-badge" style="background:var(--purple-bg); color:#5a30a0;">
                                        {{ $p->categoria->nombre_categoria ?? '—' }}
                                    </span>
                                </td>
                                <td>Bs. {{ number_format($p->precio, 2) }}</td>
                                <td>
                                    @if($p->stock > 0)
                                        <span class="estado-activo">{{ $p->stock }} un.</span>
                                    @else
                                        <span class="estado-inactivo">Agotado</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->activo)
                                        <span class="estado-activo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Activo</span>
                                    @else
                                        <span class="estado-inactivo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar"
                                                @click="editProd = {
                                                    id: {{ $p->id }},
                                                    nombre: '{{ addslashes($p->nombre_producto) }}',
                                                    descripcion: {{ Js::from($p->descripcion) }},
                                                    precio: {{ $p->precio }},
                                                    stock: {{ $p->stock }},
                                                    id_categoria: {{ $p->id_categoria }},
                                                    activo: {{ $p->activo ? 'true' : 'false' }},
                                                    preview: {{ $p->imagen_producto ? "'".asset('images/'.$p->imagen_producto)."'" : 'null' }}
                                                }">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button type="button" class="btn-eliminar"
                                                @click="deleteProd = { id: {{ $p->id }}, nombre: '{{ addslashes($p->nombre_producto) }}' }">
                                            <i class="bi bi-trash-fill"></i>
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
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);"
             x-data="{ preview: null, catId: null, nombre: 'Nuevo producto', precio: 0 }">
            <div @click.outside="showCreate = false" class="usr-modal-box usr-modal-box--card">
                <button type="button" class="usr-modal-close" @click="showCreate = false"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header usr-modal-header--card">
                    <span class="usr-modal-icon" style="background:var(--mint-bg); color:#2a7a5a;"><i class="bi bi-box-seam-fill"></i></span>
                    <div>
                        <h2 class="usr-modal-title">Agregar <span>producto</span></h2>
                        <p class="usr-header-sub">Nuevo registro</p>
                    </div>
                </div>
                <div class="usr-modal-body usr-modal-body--flat">
                    <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="usr-card-grid">
                            <div class="usr-card-aside">
                                <div class="usr-avatar-circle" style="border-radius:16px;">
                                    <div class="usr-avatar-photo" style="border-radius:16px;">
                                        <template x-if="preview"><img :src="preview" alt="Producto" style="border-radius:16px;"></template>
                                        <template x-if="!preview"><i class="bi bi-image" style="font-size:26px; color:var(--clr-sub);"></i></template>
                                    </div>
                                    <label class="usr-avatar-edit" title="Subir imagen">
                                        <i class="bi bi-camera-fill"></i>
                                        <input type="file" name="imagen_producto" class="usr-hidden-file" accept="image/*"
                                               @change="const f=$event.target.files[0]; if(f){const r=new FileReader(); r.onload=e=>preview=e.target.result; r.readAsDataURL(f);}">
                                    </label>
                                </div>
                                <div class="usr-card-name" x-text="nombre"></div>
                                <div class="usr-card-badges">
                                    <span class="rol-badge" style="background:var(--purple-bg); color:#5a30a0;" x-text="'Bs. ' + (parseFloat(precio || 0)).toFixed(2)"></span>
                                </div>
                            </div>

                            <div class="usr-card-main">
                                <p class="usr-section-label">Información</p>
                                <div class="usr-field-row">
                                    <div>
                                        <label>Nombre</label>
                                        <input type="text" name="nombre_producto" x-model="nombre" required maxlength="150">
                                    </div>
                                    <div>
                                        <label>Precio (Bs.)</label>
                                        <input type="number" name="precio" x-model="precio" step="0.01" min="0.01" required>
                                    </div>
                                </div>

                                <div class="usr-field-row">
                                    <div>
                                        <label>Categoría</label>
                                        <select name="id_categoria" x-model="catId" required>
                                            <template x-for="c in categorias" :key="c.id">
                                                <option :value="c.id" x-text="c.nombre"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Stock inicial</label>
                                        <input type="number" name="stock" min="0" value="0" required>
                                    </div>
                                </div>

                                <p class="usr-section-label">Descripción</p>
                                <textarea name="descripcion" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="usr-modal-footer">
                            <button type="button" class="usr-btn-cancel" @click="showCreate = false">Cancelar</button>
                            <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Agregar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="editProd" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="editProd = null" class="usr-modal-box usr-modal-box--card">
                <button type="button" class="usr-modal-close" @click="editProd = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="editProd">
                    <div>
                        <div class="usr-modal-header usr-modal-header--card">
                            <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);"><i class="bi bi-pencil-square"></i></span>
                            <div>
                                <h2 class="usr-modal-title">Editar <span>producto</span></h2>
                                <p class="usr-header-sub">ID #<span x-text="editProd.id"></span></p>
                            </div>
                        </div>
                        <div class="usr-modal-body usr-modal-body--flat">
                            <form method="POST" :action="'/productos/' + editProd.id" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <div class="usr-card-grid">
                                    <div class="usr-card-aside">
                                        <div class="usr-avatar-circle" style="border-radius:16px;">
                                            <div class="usr-avatar-photo" style="border-radius:16px;">
                                                <template x-if="editProd.preview"><img :src="editProd.preview" alt="Producto" style="border-radius:16px;"></template>
                                                <template x-if="!editProd.preview"><i class="bi bi-image" style="font-size:26px; color:var(--clr-sub);"></i></template>
                                            </div>
                                            <label class="usr-avatar-edit" title="Cambiar imagen">
                                                <i class="bi bi-camera-fill"></i>
                                                <input type="file" name="imagen_producto" class="usr-hidden-file" accept="image/*"
                                                       @change="const f=$event.target.files[0]; if(f){const r=new FileReader(); r.onload=e=>editProd.preview=e.target.result; r.readAsDataURL(f);}">
                                            </label>
                                        </div>
                                        <div class="usr-card-name" x-text="editProd.nombre"></div>
                                        <div class="usr-card-badges">
                                            <span class="rol-badge" style="background:var(--purple-bg); color:#5a30a0;" x-text="'Bs. ' + (parseFloat(editProd.precio || 0)).toFixed(2)"></span>
                                        </div>
                                    </div>

                                    <div class="usr-card-main">
                                        <p class="usr-section-label">Información</p>
                                        <div class="usr-field-row">
                                            <div>
                                                <label>Nombre</label>
                                                <input type="text" name="nombre_producto" x-model="editProd.nombre" required maxlength="150">
                                            </div>
                                            <div>
                                                <label>Precio (Bs.)</label>
                                                <input type="number" name="precio" x-model="editProd.precio" step="0.01" min="0.01" required>
                                                @can('ver-reportes')
                                                    <a :href="'{{ route('reportes.historial-precios') }}?id_producto=' + editProd.id" target="_blank" rel="noopener"
                                                       style="display:inline-flex; align-items:center; gap:4px; margin-top:6px; font-size:11.5px; font-weight:700; color:var(--clr-accent); text-decoration:none;">
                                                        <i class="bi bi-clock-history"></i> Ver historial de precios
                                                    </a>
                                                @endcan
                                            </div>
                                        </div>

                                        <div class="usr-field-row">
                                            <div>
                                                <label>Categoría</label>
                                                <select name="id_categoria" x-model="editProd.id_categoria" required>
                                                    <template x-for="c in categorias" :key="c.id">
                                                        <option :value="c.id" x-text="c.nombre"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label>Stock</label>
                                                <input type="number" x-model="editProd.stock" min="0" readonly disabled title="El stock solo se modifica desde el módulo de Stock">
                                            </div>
                                        </div>

                                        <p class="usr-section-label">Descripción</p>
                                        <textarea name="descripcion" x-model="editProd.descripcion" rows="3" required></textarea>

                                        <div class="usr-toggle-row">
                                            <span>Producto activo</span>
                                            <label class="usr-switch">
                                                <input type="checkbox" name="activo" value="1" x-model="editProd.activo">
                                                <span class="usr-switch-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="editProd = null">Cancelar</button>
                                    <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="deleteProd" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteProd = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteProd = null"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:#fde8e8; color:#b03030;"><i class="bi bi-trash3-fill"></i></span>
                    <h2 class="usr-modal-title" style="color:#b03030;">Eliminar <span style="color:#b03030;">Producto</span></h2>
                </div>
                <template x-if="deleteProd">
                    <div class="usr-modal-body">
                        <p class="usr-delete-msg">¿Seguro que deseas eliminar <strong x-text="deleteProd.nombre"></strong>? Esta acción no se puede deshacer.</p>
                        <form method="POST" :action="'/productos/' + deleteProd.id">
                            @csrf @method('DELETE')
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="deleteProd = null">Cancelar</button>
                                <button type="submit" class="usr-btn-delete"><i class="bi bi-trash3"></i> Eliminar</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>