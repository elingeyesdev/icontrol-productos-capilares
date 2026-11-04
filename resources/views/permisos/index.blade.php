<x-app-layout>
    <div x-data="{ showCreate: false, editPermiso: null, deletePermiso: null }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Administrar Permisos</h1>
                <p>{{ $permisos->count() }} permiso{{ $permisos->count() !== 1 ? 's' : '' }} registrado{{ $permisos->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error mb-4"><i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first() }}</div>
        @endif

        <div class="usr-toolbar mb-4">
            <form method="GET" style="display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <div class="nav-search" style="position:relative; max-width:300px;">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar permiso…">
                    </div>
                    <button type="submit" class="btn-add-user"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <x-sort-select :sort="$sort" :dir="$dir" :options="[
                    'id|desc' => 'Más recientes primero',
                    'id|asc' => 'Más antiguos primero',
                    'name|asc' => 'Nombre (A-Z)',
                    'name|desc' => 'Nombre (Z-A)',
                    'roles|desc' => 'Más roles primero',
                ]" />
            </form>
            <button type="button" class="btn-add-user" @click="showCreate = true">
                <i class="bi bi-plus-lg"></i> Nuevo permiso
            </button>
        </div>

        <div class="usr-table-card">
            @if($permisos->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-shield-lock"></i> No hay permisos registrados.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th>Nombre</th>
                            <th>Módulo</th>
                            <th style="text-align:center;">Roles que lo usan</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permisos as $permiso)
                            @php
                                $partes = explode('-', $permiso->name, 2);
                                $modulo = $partes[1] ?? 'general';
                            @endphp
                            <tr>
                                <td>#{{ $permiso->id }}</td>
                                <td style="font-weight:700;">{{ $permiso->name }}</td>
                                <td><span class="rol-badge rol-default">{{ ucfirst($modulo) }}</span></td>
                                <td>{{ $permiso->roles_count }}</td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar" title="Editar"
                                                @click="editPermiso = { id: {{ $permiso->id }}, name: '{{ addslashes($permiso->name) }}' }">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button type="button" class="btn-eliminar" title="Eliminar"
                                                @click="deletePermiso = { id: {{ $permiso->id }}, name: '{{ addslashes($permiso->name) }}', roles: {{ $permiso->roles_count }} }">
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
                    <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                        <i class="bi bi-shield-lock"></i>
                    </span>
                    <h2 class="usr-modal-title">Nuevo permiso</h2>
                </div>
                <div class="usr-modal-body usr-card-main">
                    <form method="POST" action="{{ route('permisos.store') }}">
                        @csrf
                        <label>Nombre del permiso</label>
                        <input type="text" name="name" placeholder="ej: ver-reportes" required maxlength="100">
                        <p style="font-size:11.5px; color:var(--clr-sub); margin:6px 0 0;">
                            <i class="bi bi-info-circle"></i> Formato: <strong>accion-modulo</strong>, en minúsculas y con guiones (ej: ver-productos, crear-ventas).
                        </p>
                        <div class="usr-modal-footer">
                            <button type="button" class="usr-btn-cancel" @click="showCreate = false">Cancelar</button>
                            <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Crear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="editPermiso" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="editPermiso = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="editPermiso = null"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                        <i class="bi bi-pencil-fill"></i>
                    </span>
                    <h2 class="usr-modal-title">Editar permiso</h2>
                </div>
                <template x-if="editPermiso">
                    <div class="usr-modal-body usr-card-main">
                        <form method="POST" :action="editPermiso ? '{{ url('permisos') }}/' + editPermiso.id : ''">
                            @csrf
                            @method('PUT')
                            <label>Nombre del permiso</label>
                            <input type="text" name="name" :value="editPermiso.name" required maxlength="100">
                            <p style="font-size:11.5px; color:var(--clr-sub); margin:6px 0 0;">
                                <i class="bi bi-info-circle"></i> Formato: <strong>accion-modulo</strong>, en minúsculas y con guiones (ej: ver-productos, crear-ventas).
                            </p>
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="editPermiso = null">Cancelar</button>
                                <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Guardar</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="deletePermiso" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deletePermiso = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deletePermiso = null"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:#fde8e8; color:#b03030;">
                        <i class="bi bi-trash3-fill"></i>
                    </span>
                    <h2 class="usr-modal-title" style="color:#b03030;">Eliminar permiso</h2>
                </div>
                <template x-if="deletePermiso">
                    <div class="usr-modal-body">
                        <p class="usr-delete-msg">¿Seguro que deseas eliminar <strong x-text="deletePermiso.name"></strong>?</p>
                        <template x-if="deletePermiso && deletePermiso.roles > 0">
                            <p class="usr-delete-msg" style="color:#b03030;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Está asignado a <strong x-text="deletePermiso.roles"></strong> rol(es), primero quítaselo desde Administrar Rol.
                            </p>
                        </template>
                        <form :action="deletePermiso ? '{{ url('permisos') }}/' + deletePermiso.id : ''" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="deletePermiso = null">Cancelar</button>
                                <button type="submit" class="usr-btn-delete" :disabled="deletePermiso && deletePermiso.roles > 0">
                                    <i class="bi bi-trash3"></i> Eliminar
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
