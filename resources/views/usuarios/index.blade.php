<x-app-layout>
    <div x-data="{ editUser: null, deleteUser: null }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">{{ $titulo }}</h1>
                <p>{{ $usuarios->count() }} usuario{{ $usuarios->count() !== 1 ? 's' : '' }} encontrado{{ $usuarios->count() !== 1 ? 's' : '' }}</p>
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

        <form method="GET" class="mb-4" style="display:flex; flex-direction:column; gap:8px;">
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <div class="nav-search" style="position:relative; max-width:340px;">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Buscar por nombre o correo…">
                </div>
                <button type="submit" class="btn-add-user">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
            <x-sort-select :sort="$sort" :dir="$dir" :options="[
                'id|desc' => 'Más recientes primero',
                'id|asc' => 'Más antiguos primero',
                'name|asc' => 'Nombre (A-Z)',
                'name|desc' => 'Nombre (Z-A)',
                'email|asc' => 'Correo (A-Z)',
                'estado|desc' => 'Estado',
            ]" />
        </form>

        @if($usuarios->isEmpty())
            <p class="mensaje-vacio"><i class="bi bi-people"></i> No hay usuarios registrados.</p>
        @else
            <div class="usr-table-card">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Rol</th>
                            <th style="text-align:center;">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usuarios as $usuario)
                            @php
                                $rolPrincipal = $usuario->roles->first()?->name;
                                $rolClass = match($rolPrincipal) {
                                    'administrador' => 'rol-admin',
                                    'cliente' => 'rol-cliente',
                                    'vendedor' => 'rol-delivery',
                                    default => 'rol-default',
                                };
                                $imgSrc = $usuario->imagen_usuario
                                    ? asset('images/' . $usuario->imagen_usuario)
                                    : null;
                                $iniciales = collect(explode(' ', $usuario->name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                            @endphp
                            <tr>
                                <td>#{{ $usuario->id }}</td>
                                <td>
                                    @if($imgSrc)
                                        <img src="{{ $imgSrc }}" alt="{{ $usuario->name }}" class="imagen-tabla">
                                    @else
                                        <div class="imagen-tabla" style="display:flex;align-items:center;justify-content:center;background:var(--clr-sidebar);font-weight:800;color:var(--clr-accent);">
                                            {{ strtoupper($iniciales) }}
                                        </div>
                                    @endif
                                </td>
                                <td style="font-weight:700;">{{ $usuario->name }}</td>
                                <td>{{ $usuario->email }}</td>
                                <td>{{ $usuario->telefono ?: '—' }}</td>
                                <td>
                                    <span class="rol-badge {{ $rolClass }}">
                                        {{ $rolPrincipal ? ucfirst($rolPrincipal) : 'Sin rol' }}
                                    </span>
                                </td>
                                <td>
                                    @if($usuario->estado)
                                        <span class="estado-activo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Activo</span>
                                    @else
                                        <span class="estado-inactivo"><i class="bi bi-circle-fill" style="font-size:7px"></i> Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar" title="Editar"
                                                @click="editUser = {
                                                    id: {{ $usuario->id }},
                                                    name: '{{ $usuario->name }}',
                                                    email: '{{ $usuario->email }}',
                                                    telefono: {{ $usuario->telefono ? "'".addslashes($usuario->telefono)."'" : 'null' }},
                                                    estado: {{ $usuario->estado ? 'true' : 'false' }},
                                                    role: '{{ $rolPrincipal }}',
                                                    rolClass: '{{ $rolClass }}',
                                                    preview: {{ $imgSrc ? "'".$imgSrc."'" : 'null' }},
                                                    iniciales: '{{ strtoupper($iniciales) }}',
                                                    password: ''
                                                }">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button type="button" class="btn-eliminar" title="Eliminar"
                                                @click="deleteUser = { id: {{ $usuario->id }}, name: '{{ $usuario->name }}' }">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div x-show="editUser" x-cloak
             class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="editUser = null" class="usr-modal-box usr-modal-box--card"
                 x-data="{ showPass: false }">
                <button type="button" class="usr-modal-close" @click="editUser = null">
                    <i class="bi bi-x-lg"></i>
                </button>

                <template x-if="editUser">
                    <div class="usr-modal-header usr-modal-header--card">
                        <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                            <i class="bi bi-pencil-square"></i>
                        </span>
                        <div>
                            <h2 class="usr-modal-title">Modificar <span>usuario</span></h2>
                            <p class="usr-header-sub">ID #<span x-text="editUser.id"></span></p>
                        </div>
                    </div>
                </template>

                <div class="usr-modal-body usr-modal-body--flat">
                    <template x-if="editUser">
                        <form method="POST" :action="'/usuarios/' + editUser.id" enctype="multipart/form-data">
                            @csrf @method('PUT')

                            <div class="usr-card-grid">

                                <div class="usr-card-aside">
                                    <div class="usr-avatar-circle">
                                        <div class="usr-avatar-photo">
                                            <template x-if="editUser.preview">
                                                <img :src="editUser.preview" alt="Foto">
                                            </template>
                                            <template x-if="!editUser.preview">
                                                <span class="usr-avatar-initials" x-text="editUser.iniciales"></span>
                                            </template>
                                        </div>
                                        <label class="usr-avatar-edit" title="Cambiar foto">
                                            <i class="bi bi-camera-fill"></i>
                                            <input type="file" name="foto" class="usr-hidden-file" accept="image/jpeg,image/png,image/gif"
                                                   @change="
                                                        const file = $event.target.files[0];
                                                        if (file) {
                                                            const reader = new FileReader();
                                                            reader.onload = e => editUser.preview = e.target.result;
                                                            reader.readAsDataURL(file);
                                                        }
                                                   ">
                                        </label>
                                    </div>

                                    <div class="usr-card-name" style="margin-top:5px; font-size:22.5px;" x-text="editUser.name"></div>
                                    <div class="usr-card-email" style="font-size:14px;" x-text="editUser.email"></div>

                                    <div class="usr-card-badges usr-card-badges--stack">
                                        <span class="rol-badge" :class="editUser.rolClass" x-text="editUser.role ? editUser.role.charAt(0).toUpperCase() + editUser.role.slice(1) : ''"></span>
                                        <span :class="editUser.estado ? 'estado-activo' : 'estado-inactivo'">
                                            <i class="bi bi-circle-fill" style="font-size:7px"></i>
                                            <span x-text="editUser.estado ? 'Activo' : 'Inactivo'"></span>
                                        </span>
                                    </div>
                                </div>

                                <div class="usr-card-main">

                                    <p class="usr-section-label">Datos personales</p>
                                    <div class="usr-field-row">
                                        <div>
                                            <label>Nombre</label>
                                            <input type="text" name="name" x-model="editUser.name" required maxlength="255">
                                        </div>
                                        <div>
                                            <label>Teléfono</label>
                                            <input type="tel" name="telefono" x-model="editUser.telefono" placeholder="Ej: +591 70000000" maxlength="20">
                                        </div>
                                    </div>

                                    <label>Correo electrónico</label>
                                    <input type="email" name="email" x-model="editUser.email" required maxlength="255">

                                    <div class="usr-toggle-row">
                                        <span>Cuenta activa</span>
                                        <label class="usr-switch">
                                            <input type="checkbox" name="estado" value="1" x-model="editUser.estado">
                                            <span class="usr-switch-slider"></span>
                                        </label>
                                    </div>

                                    <p class="usr-section-label">Seguridad</p>
                                    <div class="usr-security-field">
                                        <input :type="showPass ? 'text' : 'password'" name="password"
                                               x-model="editUser.password"
                                               placeholder="Dejar vacío para no cambiar" autocomplete="new-password">
                                        <button type="button" class="usr-security-toggle" @click="showPass = !showPass">
                                            <i class="bi" :class="showPass ? 'bi-eye-slash' : 'bi-eye'"></i>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="editUser = null">Cancelar</button>
                                <button type="submit" class="usr-btn-submit">
                                    <i class="bi bi-check-lg"></i> Actualizar
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="deleteUser" x-cloak
             class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteUser = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteUser = null">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:#fde8e8; color:#b03030;">
                        <i class="bi bi-trash3-fill"></i>
                    </span>
                    <h2 class="usr-modal-title" style="color:#b03030;">
                        Eliminar <span style="color:#b03030;">Usuario</span>
                    </h2>
                </div>
                <template x-if="deleteUser">
                    <div class="usr-modal-body">
                        <p class="usr-delete-msg">
                            ¿Seguro que deseas eliminar a <strong x-text="deleteUser.name"></strong>?
                            El usuario quedará marcado como inactivo y podrás reactivarlo luego editándolo.
                        </p>
                        <form method="POST" :action="'/usuarios/' + deleteUser.id">
                            @csrf @method('DELETE')
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="deleteUser = null">
                                    Cancelar
                                </button>
                                <button type="submit" class="usr-btn-delete">
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