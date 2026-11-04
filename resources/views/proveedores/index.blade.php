<x-app-layout>
    <div x-data="{ showCreate: false, editProv: null, deleteProv: null }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Administrar Proveedores</h1>
                <p>{{ $proveedores->count() }} proveedor{{ $proveedores->count() !== 1 ? 'es' : '' }} registrado{{ $proveedores->count() !== 1 ? 's' : '' }}</p>
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
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar proveedor…">
                    </div>
                    <button type="submit" class="btn-add-user"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <x-sort-select :sort="$sort" :dir="$dir" :options="[
                    'id|desc' => 'Más recientes primero',
                    'id|asc' => 'Más antiguos primero',
                    'nombre|asc' => 'Nombre (A-Z)',
                    'nombre|desc' => 'Nombre (Z-A)',
                    'compras|desc' => 'Más compras primero',
                    'activo|desc' => 'Estado',
                ]" />
            </form>
            <button type="button" class="btn-add-user" @click="showCreate = true">
                <i class="bi bi-truck"></i> Nuevo Proveedor
            </button>
        </div>

        <div class="usr-table-card">
            @if($proveedores->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-truck"></i> No hay proveedores registrados.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">ID</th>
                            <th>Empresa</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th style="text-align:center;">Compras</th>
                            <th style="text-align:center;">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proveedores as $prov)
                            <tr>
                                <td><strong>#{{ $prov->id }}</strong></td>
                                <td style="font-weight:700;">{{ $prov->nombre_empresa }}</td>
                                <td>{{ $prov->contacto ?: '—' }}</td>
                                <td>{{ $prov->telefono ?: '—' }}</td>
                                <td>{{ $prov->email ?: '—' }}</td>
                                <td>
                                    <span class="rol-badge" style="background:var(--purple-bg);color:#5a30a0;">
                                        {{ $prov->compras_count }}
                                    </span>
                                </td>
                                <td>
                                    @if($prov->activo)
                                        <span class="estado-activo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Activo</span>
                                    @else
                                        <span class="estado-inactivo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar"
                                                @click="editProv = {
                                                    id: {{ $prov->id }},
                                                    nombre_empresa: '{{ addslashes($prov->nombre_empresa) }}',
                                                    contacto: '{{ addslashes($prov->contacto ?? '') }}',
                                                    telefono: '{{ addslashes($prov->telefono ?? '') }}',
                                                    email: '{{ addslashes($prov->email ?? '') }}',
                                                    direccion: '{{ addslashes($prov->direccion ?? '') }}',
                                                    activo: {{ $prov->activo ? 'true' : 'false' }}
                                                }">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button type="button" class="btn-eliminar"
                                                @click="deleteProv = { id: {{ $prov->id }}, nombre: '{{ addslashes($prov->nombre_empresa) }}' }">
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
                    <span class="usr-modal-icon" style="background:var(--mint-bg); color:#2a7a5a;"><i class="bi bi-truck"></i></span>
                    <h2 class="usr-modal-title">Nuevo <span>proveedor</span></h2>
                </div>
                <div class="usr-modal-body">
                    <form method="POST" action="{{ route('proveedores.store') }}">
                        @csrf
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Nombre de la empresa</label>
                            <input type="text" name="nombre_empresa" required maxlength="150"
                                   style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                        </div>
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Persona de contacto</label>
                            <input type="text" name="contacto" maxlength="150"
                                   style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                        </div>
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Teléfono</label>
                            <input type="text" name="telefono" maxlength="20"
                                   style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                        </div>
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Email</label>
                            <input type="email" name="email" maxlength="255"
                                   style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                        </div>
                        <div class="form-group" style="margin-bottom:14px;">
                            <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Dirección</label>
                            <input type="text" name="direccion" maxlength="255"
                                   style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                        </div>
                        <div class="usr-modal-footer">
                            <button type="button" class="usr-btn-cancel" @click="showCreate = false">Cancelar</button>
                            <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Crear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="editProv" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="editProv = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="editProv = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="editProv">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);"><i class="bi bi-pencil-square"></i></span>
                            <h2 class="usr-modal-title">Editar <span>proveedor</span></h2>
                        </div>
                        <div class="usr-modal-body">
                            <form method="POST" :action="'/proveedores/' + editProv.id">
                                @csrf @method('PUT')
                                <div class="form-group" style="margin-bottom:14px;">
                                    <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Nombre de la empresa</label>
                                    <input type="text" name="nombre_empresa" x-model="editProv.nombre_empresa" required maxlength="150"
                                           style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                                </div>
                                <div class="form-group" style="margin-bottom:14px;">
                                    <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Persona de contacto</label>
                                    <input type="text" name="contacto" x-model="editProv.contacto" maxlength="150"
                                           style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                                </div>
                                <div class="form-group" style="margin-bottom:14px;">
                                    <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Teléfono</label>
                                    <input type="text" name="telefono" x-model="editProv.telefono" maxlength="20"
                                           style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                                </div>
                                <div class="form-group" style="margin-bottom:14px;">
                                    <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Email</label>
                                    <input type="email" name="email" x-model="editProv.email" maxlength="255"
                                           style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                                </div>
                                <div class="form-group" style="margin-bottom:14px;">
                                    <label style="color:var(--clr-text); font-size:12px; font-weight:700; display:block; margin-bottom:5px;">Dirección</label>
                                    <input type="text" name="direccion" x-model="editProv.direccion" maxlength="255"
                                           style="width:100%; padding:9px 13px; border:1.5px solid rgba(180,138,212,.4); border-radius:10px; font-size:13px; background:#fff; color:var(--clr-text);">
                                </div>
                                <div class="usr-toggle-row">
                                    <span>Proveedor activo</span>
                                    <label class="usr-switch">
                                        <input type="checkbox" name="activo" value="1" x-model="editProv.activo">
                                        <span class="usr-switch-slider"></span>
                                    </label>
                                </div>
                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="editProv = null">Cancelar</button>
                                    <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="deleteProv" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteProv = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteProv = null"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:#fde8e8; color:#b03030;"><i class="bi bi-trash3-fill"></i></span>
                    <h2 class="usr-modal-title" style="color:#b03030;">Eliminar <span style="color:#b03030;">Proveedor</span></h2>
                </div>
                <template x-if="deleteProv">
                    <div class="usr-modal-body">
                        <p class="usr-delete-msg">¿Seguro que deseas eliminar <strong x-text="deleteProv.nombre"></strong>? Esta acción no se puede deshacer.</p>
                        <form method="POST" :action="'/proveedores/' + deleteProv.id">
                            @csrf @method('DELETE')
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="deleteProv = null">Cancelar</button>
                                <button type="submit" class="usr-btn-delete"><i class="bi bi-trash3"></i> Eliminar</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
