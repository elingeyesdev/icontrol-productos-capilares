<x-app-layout>
    @php $usuariosSinRol = $usuarios->filter(fn ($u) => $u->roles->isEmpty()); @endphp
    <div x-data="{ assignRol: null, deleteRol: null }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Administrar Rol Usuario</h1>
                <p>{{ $usuarios->count() }} usuario{{ $usuarios->count() !== 1 ? 's' : '' }} registrado{{ $usuarios->count() !== 1 ? 's' : '' }}</p>
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
                        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o correo…">
                    </div>
                    <button type="submit" class="btn-add-user"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <x-sort-select :sort="$sort" :dir="$dir" :options="[
                    'id|desc' => 'Más recientes primero',
                    'id|asc' => 'Más antiguos primero',
                    'name|asc' => 'Nombre (A-Z)',
                    'name|desc' => 'Nombre (Z-A)',
                    'email|asc' => 'Correo (A-Z)',
                ]" />
            </form>
            <button type="button" class="btn-add-user"
                    @click="assignRol = { esNuevo: true, user_id: '{{ $usuariosSinRol->first()->id ?? '' }}', nombre: '', role: '' }">
                <i class="bi bi-person-fill-add"></i> Añadir
            </button>
        </div>

        <div class="usr-table-card">
            @if($usuarios->isEmpty())
                <p class="mensaje-vacio"><i class="bi bi-people"></i> No hay usuarios registrados.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Rol actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usuarios as $usuario)
                            @php
                                $rolActual = $usuario->roles->first()?->name;
                                $rolClass = match($rolActual) {
                                    'administrador' => 'rol-admin',
                                    'cliente' => 'rol-cliente',
                                    'vendedor' => 'rol-delivery',
                                    default => 'rol-default',
                                };
                            @endphp
                            <tr>
                                <td style="font-weight:700;">{{ $usuario->name }}</td>
                                <td>{{ $usuario->email }}</td>
                                <td>
                                    <span class="rol-badge {{ $rolClass }}">{{ $rolActual ? ucfirst($rolActual) : 'Sin rol' }}</span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; justify-content:center;">
                                        <button type="button" class="btn-editar" title="Editar rol"
                                                @click="assignRol = {
                                                    esNuevo: false,
                                                    user_id: {{ $usuario->id }},
                                                    nombre: '{{ addslashes($usuario->name) }}',
                                                    role: '{{ $rolActual }}',
                                                    rolClass: '{{ $rolClass }}'
                                                }">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        @if($rolActual)
                                            <button type="button" class="btn-eliminar" title="Eliminar rol"
                                                    @click="deleteRol = { id: {{ $usuario->id }}, nombre: '{{ addslashes($usuario->name) }}', role: '{{ $rolActual }}' }">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div x-show="assignRol" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="assignRol = null" class="usr-modal-box usr-modal-box--md">
                <button type="button" class="usr-modal-close" @click="assignRol = null"><i class="bi bi-x-lg"></i></button>
                <template x-if="assignRol">
                    <div>
                        <div class="usr-modal-header">
                            <span class="usr-modal-icon" style="background:var(--purple-bg); color:var(--clr-accent);">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <h2 class="usr-modal-title" x-text="assignRol.esNuevo ? 'Asignar rol' : 'Cambiar rol'"></h2>
                        </div>
                        <div class="usr-modal-body">
                            <form method="POST" action="{{ route('usuario-roles.store') }}" class="usr-card-main">
                                @csrf

                                <template x-if="assignRol.esNuevo">
                                    <div>
                                        <label>Usuario</label>
                                        <select name="user_id" x-model="assignRol.user_id" required>
                                            @forelse($usuariosSinRol as $usuario)
                                                <option value="{{ $usuario->id }}">{{ $usuario->name }} — {{ $usuario->email }}</option>
                                            @empty
                                                <option value="" disabled>No hay usuarios sin rol</option>
                                            @endforelse
                                        </select>
                                    </div>
                                </template>

                                <template x-if="!assignRol.esNuevo">
                                    <div>
                                        <label>Usuario</label>
                                        <input type="text" :value="assignRol.nombre" readonly disabled>
                                        <input type="hidden" name="user_id" :value="assignRol.user_id">
                                    </div>
                                </template>

                                <p class="usr-section-label">Rol</p>
                                <div class="usr-role-pills">
                                    @foreach($roles as $r)
                                        @php
                                            $pillClass = match($r->name) {
                                                'administrador' => 'rol-admin',
                                                'cliente' => 'rol-cliente',
                                                'vendedor' => 'rol-delivery',
                                                default => 'rol-default',
                                            };
                                        @endphp
                                        <button type="button" class="usr-role-pill"
                                                :class="assignRol.role === '{{ $r->name }}' ? 'active' : ''"
                                                @click="assignRol.role = '{{ $r->name }}'; assignRol.rolClass = '{{ $pillClass }}';">
                                            {{ ucfirst($r->name) }}
                                        </button>
                                    @endforeach
                                </div>
                                <input type="hidden" name="role" x-model="assignRol.role">

                                <div class="usr-modal-footer">
                                    <button type="button" class="usr-btn-cancel" @click="assignRol = null">Cancelar</button>
                                    <button type="submit" class="usr-btn-submit"><i class="bi bi-check-lg"></i> Guardar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div x-show="deleteRol" x-cloak class="fixed inset-0 z-[1300] flex items-center justify-center p-4"
             style="background: rgba(50,20,70,.45); backdrop-filter: blur(6px);">
            <div @click.outside="deleteRol = null" class="usr-modal-box usr-modal-box--sm">
                <button type="button" class="usr-modal-close" @click="deleteRol = null"><i class="bi bi-x-lg"></i></button>
                <div class="usr-modal-header">
                    <span class="usr-modal-icon" style="background:#fde8e8; color:#b03030;">
                        <i class="bi bi-trash3-fill"></i>
                    </span>
                    <h2 class="usr-modal-title" style="color:#b03030;">Eliminar rol</h2>
                </div>
                <template x-if="deleteRol">
                    <div class="usr-modal-body">
                        <p class="usr-delete-msg">
                            ¿Seguro que deseas quitarle el rol <strong x-text="deleteRol.role"></strong> a <strong x-text="deleteRol.nombre"></strong>?
                            El usuario quedará sin rol asignado.
                        </p>
                        <form :action="deleteRol ? '{{ url('rol-usuario') }}/' + deleteRol.id : ''" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="usr-modal-footer">
                                <button type="button" class="usr-btn-cancel" @click="deleteRol = null">Cancelar</button>
                                <button type="submit" class="usr-btn-delete"><i class="bi bi-trash3"></i> Eliminar</button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>
