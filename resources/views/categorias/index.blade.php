<x-app-layout>
    <div x-data="{ showCreate: false, editCat: null, deleteCat: null }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Administrar Categorías</h1>
                <p>{{ $categorias->count() }} categoría{{ $categorias->count() !== 1 ? 's' : '' }} registrada{{ $categorias->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-4">
                <i class="bi bi-check-circle-fill"></i> {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            </div>
        @endif

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div class="nav-search" style="position:relative; max-width:340px;">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar categoría…">
                    </div>
                    <button type="submit" class="btn-add-user"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <x-sort-select :sort="$sort" :dir="$dir" :options="[
                    'id|desc' => 'Más recientes primero',
                    'id|asc' => 'Más antiguas primero',
                    'nombre|asc' => 'Nombre (A-Z)',
                    'nombre|desc' => 'Nombre (Z-A)',
                    'productos|desc' => 'Más productos primero',
                    'estado|desc' => 'Estado',
                ]" />
            </form>
            <button type="button" class="btn-add-user" @click="showCreate = true">
                <i class="bi bi-tag-fill"></i> Nueva Categoría
            </button>
        </div>

        <div class="usr-table-card">
            @if($categorias->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-tags"></i> No hay categorías registradas.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th style="text-align:center;">Productos</th>
                            <th style="text-align:center;">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorias as $cat)
                            <tr>
                                <td><strong>#{{ $cat->id }}</strong></td>
                                <td style="font-weight:700;">{{ $cat->nombre_categoria }}</td>
                                <td>{{ $cat->descripcion ?: '—' }}</td>
                                <td>
                                    <span class="rol-badge" style="background:var(--purple-bg);color:#5a30a0;">
                                        {{ $cat->productos_count }}
                                    </span>
                                </td>
                                <td>
                                    @if($cat->estado)
                                        <span class="estado-activo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Activo</span>
                                    @else
                                        <span class="estado-inactivo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar"
                                                @click="editCat = { id: {{ $cat->id }}, nombre: '{{ addslashes($cat->nombre_categoria) }}', descripcion: '{{ addslashes($cat->descripcion ?? '') }}', estado: {{ $cat->estado ? 'true' : 'false' }} }">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button type="button" class="btn-eliminar"
                                                @click="deleteCat = { id: {{ $cat->id }}, nombre: '{{ addslashes($cat->nombre_categoria) }}' }">
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
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="showCreate = false" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="showCreate = false"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:var(--mint-bg); color:#2a7a5a;"><i class="bi bi-tag-fill"></i></span>
                    <h2 class="usr-modal-title">Nueva <span>categoría</span></h2>
                </div>
                <div class="usr-modal-body">
                    <form method="POST" action="{{ route('categorias.store') }}">
                        @csrf
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Nombre</label>
                            <input type="text" name="nombre_categoria" required maxlength="100"
                                   style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                        </div>
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Descripción</label>
                            <textarea name="descripcion" rows="3"
                                      style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);"></textarea>
                        </div>
                        <div class="usr-modal-footer">
                            <button type="button" class="usr-btn-cancel" @click="showCreate = false">Cancelar</button>
                            <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Crear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="editCat" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="editCat = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="editCat = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="editCat">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);"><i class="bi bi-pencil-square"></i></span>
                            <h2 class="usr-modal-title">Editar <span>categoría</span></h2>
                        </div>
                        <div class="usr-modal-body">
                            <form method="POST" :action="'/categorias/' + editCat.id">
                                @csrf @method('PUT')
                                <div class="form-group" style="margin-bottom:14px;">
                                    <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Nombre</label>
                                    <input type="text" name="nombre_categoria" x-model="editCat.nombre" required maxlength="100"
                                           style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                                </div>
                                <div class="form-group" style="margin-bottom:14px;">
                                    <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Descripción</label>
                                    <textarea name="descripcion" x-model="editCat.descripcion" rows="3"
                                              style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);"></textarea>
                                </div>
                                <div class="usr-toggle-row">
                                    <span>Categoría activa</span>
                                    <label class="usr-switch">
                                        <input type="checkbox" name="estado" value="1" x-model="editCat.estado">
                                        <span class="usr-switch-slider"></span>
                                    </label>
                                </div>
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="editCat = null">Cancelar</button>
                                    <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="deleteCat" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteCat = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteCat = null"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:#fde8e8; color:#b03030;"><i class="bi bi-trash3-fill"></i></span>
                    <h2 class="usr-modal-title" style="color:#b03030;">Eliminar <span style="color:#b03030;">Categoría</span></h2>
                </div>
                <template x-if="deleteCat">
                    <div class="usr-modal-body">
                        <p class="usr-delete-msg">¿Seguro que deseas eliminar <strong x-text="deleteCat.nombre"></strong>? Esta acción no se puede deshacer.</p>
                        <form method="POST" :action="'/categorias/' + deleteCat.id">
                            @csrf @method('DELETE')
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="deleteCat = null">Cancelar</button>
                                <button type="submit" class="usr-btn-delete"><i class="bi bi-trash3"></i> Eliminar</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>