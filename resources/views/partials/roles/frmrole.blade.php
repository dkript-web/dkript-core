<!-- Modal Formulario de Rol (frmrole) -->
<div id="modalRoleForm" class="modal-wrapper fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <!-- Backdrop Blur -->
    <div class="fixed inset-0 modal-backdrop-blur" onclick="closeModal('modalRoleForm')"></div>

    <!-- Modal Content Box -->
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl max-h-[92vh] flex flex-col z-10 overflow-hidden modal-content-transition modal-card">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-[#112356] flex items-center justify-between bg-[#071026] text-white modal-header-bar">
            <div class="flex items-center gap-3">
                <div class="hidden modal-window-dots items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-[#ff5f56]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#ffbd2e]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#27c93f]"></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-[#0b1739] border border-[#112356] text-[#00d4ff] flex items-center justify-center text-xl shadow-sm">
                    <i class="bi bi-shield-shaded"></i>
                </div>
                <div>
                    <h3 id="roleModalTitle" class="text-lg font-black text-white tracking-tight">Registrar Nuevo Rol</h3>
                    <p class="text-xs text-[#00d4ff]/80 font-medium">Configure la información y matriz de permisos granulares</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalRoleForm')" class="w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-[#0b1739] flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Formulario -->
        <form id="frmRole" method="POST" action="{{ route('roles.store') }}" class="flex flex-col flex-1 min-h-0">
            @csrf
            <input type="hidden" name="_method" id="roleFormMethod" value="POST">
            <input type="hidden" name="role_id" id="role_id" value="">

            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- 1. Datos Principales del Rol -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50/60 p-5 rounded-2xl border border-slate-200/70">
                    <!-- Nombre del Rol con Floating Label -->
                    <div class="md:col-span-2 floating-input-group">
                        <label for="role_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                            Nombre del Rol <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="role_name" 
                               required 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 transition-all placeholder:text-slate-400 bg-white" 
                               placeholder="Ej. Gerente de Operaciones, Auditor...">
                    </div>

                    <!-- Estatus del Rol con iOS Toggle Switch y Badge Dinámico -->
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                            Estatus del Rol
                        </label>
                        <div class="flex items-center gap-3 mt-1">
                            <label class="role-toggle-switch">
                                <input type="checkbox" name="status" id="role_status" value="1" checked onchange="updateRoleStatusBadge(this)">
                                <span class="role-toggle-track">
                                    <span class="role-toggle-thumb"></span>
                                </span>
                            </label>
                            <span id="roleStatusBadge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                ACTIVO
                            </span>
                        </div>
                    </div>

                    <!-- Descripción Opcional -->
                    <div class="md:col-span-3">
                        <label for="role_description" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                            Descripción / Propósito
                        </label>
                        <textarea name="description" 
                                  id="role_description" 
                                  rows="2" 
                                  class="w-full px-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 transition-all placeholder:text-slate-400 bg-white" 
                                  placeholder="Describe las responsabilidades principales de este rol..."></textarea>
                    </div>
                </div>

                <!-- 2. Matriz de Módulos y Permisos -->
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                        <div>
                            <h4 class="text-base font-bold text-slate-800">Matriz de Módulos y Permisos Granulares</h4>
                            <p class="text-xs text-slate-500">Active los módulos requeridos y asigne permisos por posición (1 a 5)</p>
                        </div>

                        <!-- Buscador Dinámico en Vivo y Acciones Globales -->
                        <div class="flex items-center flex-wrap gap-2">
                            <!-- Buscador en Vivo con Lupa -->
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                                    <i class="bi bi-search text-xs"></i>
                                </span>
                                <input type="text" 
                                       id="roleModuleFilter" 
                                       onkeyup="filterRoleModules(this.value)"
                                       placeholder="Filtrar módulos..." 
                                       class="pl-8 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-blue-800 focus:border-blue-800 bg-white placeholder:text-slate-400 w-44">
                            </div>

                            <!-- Botones Globales -->
                            <button type="button" 
                                    onclick="toggleAllRolePermissions(true)" 
                                    class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                                Marcar Todo
                            </button>
                            <button type="button" 
                                    onclick="toggleAllRolePermissions(false)" 
                                    class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                                Desmarcar Todo
                            </button>
                        </div>
                    </div>

                    <!-- Grid de Tarjetas de Módulos (role-modules-grid) -->
                    <div id="roleModulesGrid" class="role-modules-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($allMenuOptions as $option)
                            <div class="role-module-card bg-white rounded-xl border border-slate-200 p-4 shadow-sm transition-all hover:border-slate-300" data-module-name="{{ strtolower($option->name) }}">
                                <!-- Cabecera de la Tarjeta del Módulo -->
                                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                                            <i class="bi {{ $option->icon }}"></i>
                                        </div>
                                        <div>
                                            <h5 class="text-sm font-bold text-slate-800 leading-tight">{{ $option->name }}</h5>
                                            <span id="module_count_{{ $option->id }}" class="text-[11px] font-semibold text-slate-400">
                                                0/{{ $option->permissions->count() }} act.
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <!-- Botón individual "Todo" por módulo -->
                                        <button type="button" 
                                                onclick="toggleModulePerms({{ $option->id }})" 
                                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                                            Todo
                                        </button>

                                        <!-- Master Switch del Módulo -->
                                        <label class="role-toggle-switch" title="Activar/Desactivar módulo completo">
                                            <input type="checkbox" 
                                                   name="modules[]" 
                                                   value="{{ $option->id }}" 
                                                   id="mod_master_{{ $option->id }}" 
                                                   class="module-master-switch" 
                                                   data-module-id="{{ $option->id }}"
                                                   onchange="onModuleMasterToggle({{ $option->id }}, this.checked)">
                                            <span class="role-toggle-track">
                                                <span class="role-toggle-thumb"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Filas de Permisos por Módulo (Posiciones 1 a 5) -->
                                <div class="space-y-2" id="module_perms_container_{{ $option->id }}">
                                    @foreach($option->permissions as $perm)
                                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-slate-100/80 transition-colors gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                {{-- Badge Temático según Posición --}}
                                                @if($perm->position == 1)
                                                    <span class="badge-pos-1 px-2 py-0.5 rounded text-[11px] font-bold flex-shrink-0 flex items-center gap-1">
                                                        <i class="bi bi-plus-circle-fill text-emerald-500"></i> Crear
                                                    </span>
                                                    <span class="text-xs text-slate-600 hidden sm:inline truncate">Registrar nuevos registros</span>
                                                @elseif($perm->position == 2)
                                                    <span class="badge-pos-2 px-2 py-0.5 rounded text-[11px] font-bold flex-shrink-0 flex items-center gap-1">
                                                        <i class="bi bi-pencil-square text-amber-500"></i> Editar
                                                    </span>
                                                    <span class="text-xs text-slate-600 hidden sm:inline truncate">Modificar existentes</span>
                                                @elseif($perm->position == 3)
                                                    <span class="badge-pos-3 px-2 py-0.5 rounded text-[11px] font-bold flex-shrink-0 flex items-center gap-1">
                                                        <i class="bi bi-trash3-fill text-rose-500"></i> Eliminar
                                                    </span>
                                                    <span class="text-xs text-slate-600 hidden sm:inline truncate">Borrar registros</span>
                                                @elseif($perm->position == 4)
                                                    <span class="badge-pos-4 px-2 py-0.5 rounded text-[11px] font-bold flex-shrink-0 flex items-center gap-1">
                                                        <i class="bi bi-eye-fill text-sky-500"></i> Ver
                                                    </span>
                                                    <span class="text-xs text-slate-600 hidden sm:inline truncate">Consultar / Exportar a PDF</span>
                                                @elseif($perm->position == 5)
                                                    <span class="badge-pos-5 px-2 py-0.5 rounded text-[11px] font-bold flex-shrink-0 flex items-center gap-1">
                                                        <i class="bi bi-shield-check text-purple-500"></i> Especial
                                                    </span>
                                                    <span class="text-xs text-slate-600 hidden sm:inline truncate">Acciones avanzadas / Excel</span>
                                                @endif
                                            </div>

                                            <!-- Switch iOS del Permiso con Touch Target -->
                                            <label class="role-toggle-switch flex-shrink-0">
                                                <input type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $perm->id }}" 
                                                       id="perm_{{ $perm->id }}" 
                                                       class="perm-checkbox module-perm-{{ $option->id }}" 
                                                       data-module-id="{{ $option->id }}"
                                                       disabled
                                                       onchange="updateModulePermCount({{ $option->id }})">
                                                <span class="role-toggle-track">
                                                    <span class="role-toggle-thumb"></span>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 bg-[#f8fafd] flex items-center justify-end gap-3">
                <button type="button" 
                        onclick="closeModal('modalRoleForm')" 
                        class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="btn-dkript-primary px-6 py-2.5 text-sm">
                    <i class="bi bi-check-lg text-lg"></i>
                    <span>Guardar Rol</span>
                </button>
            </div>
        </form>
    </div>
</div>
