<x-app-layout>
    <div x-data="{ showCreate: false, editRole: null, permsRole: null }">

        <div class="section-header">
            <div>
                <h1 class="text-3xl mb-1">Administrar Roles</h1>
                <p>{{ $roles->count() }} rol{{ $roles->count() !== 1 ? 'es' : '' }} registrado{{ $roles->count() !== 1 ? 's' : '' }}</p>
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

        <div class="flex justify-end mb-4">
            <button @click="showCreate = true" type="button" class="btn-asignar-permiso">
                <i class="bi bi-shield-plus"></i> Nuevo Rol
            </button>
        </div>

        @if($roles->isEmpty())
            <div class="empty-state">
                <i class="bi bi-shield-slash"></i>
                <p>No hay roles registrados.</p>
            </div>
        @else
            <div class="roles-grid">
                @foreach($roles as $role)
                    @php
                        $permisosDelRol = $role->permissions;
                        $porModulo = $permisosDelRol->groupBy(function ($p) {
                            $partes = explode('-', $p->name, 2);
                            return $partes[1] ?? 'general';
                        });
                    @endphp
                    <div class="rol-card">
                        <div class="rol-card-header">
                            <div class="rol-card-icon"><i class="bi bi-shield-fill-check"></i></div>
                            <div class="rol-card-info">
                                <h3 class="rol-card-nombre">{{ ucfirst($role->name) }}</h3>
                                <p class="rol-card-desc">{{ $permisosDelRol->count() }} permiso{{ $permisosDelRol->count() !== 1 ? 's' : '' }} asignado{{ $permisosDelRol->count() !== 1 ? 's' : '' }}</p>
                            </div>
                            <div class="rol-card-actions">
                                <button @click="editRole = { id: {{ $role->id }}, name: '{{ $role->name }}' }"
                                        type="button" class="btn-editar" title="Editar rol">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                      onsubmit="return confirm('¿Eliminar el rol {{ $role->name }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-eliminar" title="Eliminar rol">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="rol-permisos-header">
                            <span class="rol-permisos-count">
                                <i class="bi bi-key-fill"></i> {{ $permisosDelRol->count() }} permiso{{ $permisosDelRol->count() !== 1 ? 's' : '' }}
                            </span>
                            <button type="button" class="btn-asignar-permiso"
                                    @click="permsRole = { id: {{ $role->id }}, name: '{{ $role->name }}', ids: {{ $permisosDelRol->pluck('id')->toJson() }} }">
                                <i class="bi bi-pencil-fill"></i> Editar permisos
                            </button>
                        </div>

                        <div class="rol-permisos-body">
                            @if($permisosDelRol->isEmpty())
                                <p class="sin-permisos"><i class="bi bi-lock"></i> Sin permisos asignados</p>
                            @else
                                @foreach($porModulo as $modulo => $lista)
                                    <div class="permiso-modulo">
                                        <div class="permiso-modulo-titulo">
                                            <i class="bi bi-folder2-open"></i> {{ ucfirst($modulo) }}
                                        </div>
                                        <div class="permiso-chips">
                                            @foreach($lista as $permiso)
                                                <span class="permiso-chip">
                                                    {{ $permiso->name }}
                                                    <form method="POST" action="{{ route('roles.permisos', $role) }}" style="display:inline;">
                                                        @csrf
                                                        @foreach($permisosDelRol->pluck('id')->diff([$permiso->id]) as $keepId)
                                                            <input type="hidden" name="permisos[]" value="{{ $keepId }}">
                                                        @endforeach
                                                        <button type="submit" class="permiso-chip-remove" title="Revocar permiso">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </form>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div x-show="showCreate" x-cloak
             class="fixed inset-0 bg-black/40 z-[1300] flex items-center justify-center p-4">
            <div @click.outside="showCreate = false" class="bg-brand-white rounded-2xl shadow-card w-full max-w-sm p-6">
                <h2 class="text-lg font-extrabold text-brand-text mb-4 flex items-center gap-2">
                    <i class="bi bi-shield-plus text-brand-accent"></i> Nuevo Rol
                </h2>
                <form method="POST" action="{{ route('roles.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Nombre del rol</label>
                        <input type="text" name="name" placeholder="ej. supervisor" required
                               style="background:#fff; color:var(--clr-text); border-color:rgba(180,138,212,.4);">
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" @click="showCreate = false"
                                class="px-4 py-2 rounded-lg text-sm font-bold text-brand-sub bg-brand-sidebar">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-login" style="margin-top:0; width:auto; padding:8px 18px;">
                            Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="editRole" x-cloak
             class="fixed inset-0 bg-black/40 z-[1300] flex items-center justify-center p-4">
            <div @click.outside="editRole = null" class="bg-brand-white rounded-2xl shadow-card w-full max-w-sm p-6" x-cloak>
                <h2 class="text-lg font-extrabold text-brand-text mb-4 flex items-center gap-2">
                    <i class="bi bi-pencil-fill text-brand-accent"></i> Editar Rol
                </h2>
                <template x-if="editRole">
                    <form method="POST" :action="'/roles/' + editRole.id">
                        @csrf @method('PUT')
                        <div class="form-group">
                            <label>Nombre del rol</label>
                            <input type="text" name="name" :value="editRole.name" required
                                   style="background:#fff; color:var(--clr-text); border-color:rgba(180,138,212,.4);">
                        </div>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" @click="editRole = null"
                                    class="px-4 py-2 rounded-lg text-sm font-bold text-brand-sub bg-brand-sidebar">
                                Cancelar
                            </button>
                            <button type="submit" class="btn-login" style="margin-top:0; width:auto; padding:8px 18px;">
                                Guardar
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <div x-show="permsRole" x-cloak
             class="fixed inset-0 bg-black/40 z-[1300] flex items-center justify-center p-4">
            <div @click.outside="permsRole = null" class="bg-brand-white rounded-2xl shadow-card w-full max-w-md p-6 max-h-[85vh] overflow-y-auto">
                <template x-if="permsRole">
                    <div>
                        <h2 class="text-lg font-extrabold text-brand-text mb-1 flex items-center gap-2">
                            <i class="bi bi-key-fill text-brand-accent"></i> Permisos del Rol
                        </h2>
                        <p class="text-sm text-brand-sub mb-4" x-text="permsRole.name"></p>

                        <form method="POST" :action="'/roles/' + permsRole.id + '/permisos'">
                            @csrf
                            <div class="permiso-checklist">
                                @foreach($permisosPorModulo as $modulo => $lista)
                                    <div class="permiso-checklist-grupo">
                                        <div class="permiso-checklist-titulo">
                                            <i class="bi bi-folder2-open"></i> {{ ucfirst($modulo) }}
                                        </div>
                                        @foreach($lista as $permiso)
                                            <label class="permiso-check-item">
                                                <input type="checkbox" name="permisos[]" value="{{ $permiso->id }}"
                                                       :checked="permsRole.ids.includes({{ $permiso->id }})">
                                                <span>{{ $permiso->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" @click="permsRole = null"
                                        class="px-4 py-2 rounded-lg text-sm font-bold text-brand-sub bg-brand-sidebar">
                                    Cancelar
                                </button>
                                <button type="submit" class="btn-login" style="margin-top:0; width:auto; padding:8px 18px;">
                                    <i class="bi bi-check-lg"></i> Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        </div>

    </div>
</x-app-layout>