@extends('layouts.admin')

@section('title', 'Gestión de Usuarios')
@section('page_title', 'Usuarios y Perfiles')

@section('content')
<div class="content-section">
    <!-- Barra de Herramientas Estándar con Permisos -->
    @include('partials.layouts.page-header', [
        'searchPlaceholder' => 'Buscar por nombre, correo o teléfono...',
        'newOnClick' => "openCreateUserModal('" . route('users.store') . "')",
        'newModalId' => 'modalUserForm',
        'newButtonText' => 'Nuevo Usuario'
    ])

    <!-- Tabla Estilo Tarjeta Blanca -->
    <div class="table-card">
        <div class="overflow-x-auto">
            <table id="tbExel" class="w-full text-left text-sm text-slate-600 catalog-data-table">
                <thead class="bg-[#f8fafd] text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold">Usuario</th>
                        <th class="px-6 py-4 font-bold">Nombre Completo</th>
                        <th class="px-6 py-4 font-bold">Rol</th>
                        <th class="px-6 py-4 font-bold">Teléfono</th>
                        <th class="px-6 py-4 font-bold">Estatus</th>
                        <th class="px-6 py-4 font-bold text-right no-export">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <img src="{{ $user->profile?->avatar_url }}" class="w-10 h-10 rounded-full border border-slate-200 ring-2 ring-[#0062f5]/15 object-cover bg-slate-100">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700">
                                {{ $user->profile?->full_name ?: 'Sin Perfil' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $user->isSuperAdmin() ? 'bg-gradient-to-r from-amber-500/15 to-orange-500/15 text-amber-900 border border-amber-300' : 'bg-[#0062f5]/10 text-[#0062f5] border border-[#0062f5]/20' }}">
                                    {{ $user->role?->name ?: 'Sin Rol' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $user->profile?->phone ?: 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($user->status == 1)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right no-export">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Posición 2: Editar --}}
                                    @if(auth()->user()->isSuperAdmin() || in_array(2, session('mypermits', [])))
                                        <button type="button" 
                                                onclick="openEditUserModal({{ json_encode($user) }}, {{ json_encode($user->profile) }})" 
                                                title="Editar Usuario" 
                                                class="p-2 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition-colors">
                                            <i class="bi bi-pencil-square text-base"></i>
                                        </button>
                                    @endif

                                    {{-- Posición 3: Eliminar --}}
                                    @if(auth()->user()->isSuperAdmin() || in_array(3, session('mypermits', [])))
                                        @if($user->id !== 1 && $user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer." class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        title="Eliminar Usuario" 
                                                        class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                                    <i class="bi bi-trash3-fill text-base"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No se encontraron usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Formulario Usuario -->
<div id="modalUserForm" class="modal-wrapper fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <div class="fixed inset-0 modal-backdrop-blur" onclick="closeModal('modalUserForm')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl z-10 overflow-hidden modal-content-transition modal-card">
        <div class="px-6 py-4 border-b border-[#112356] flex items-center justify-between bg-[#071026] text-white modal-header-bar">
            <div class="flex items-center gap-3">
                <div class="hidden modal-window-dots items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-[#ff5f56]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#ffbd2e]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#27c93f]"></span>
                </div>
                <h3 id="userModalTitle" class="text-lg font-black text-white tracking-tight">Registrar Nuevo Usuario</h3>
            </div>
            <button type="button" onclick="closeModal('modalUserForm')" class="w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-[#0b1739] flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="frmUser" method="POST" action="{{ route('users.store') }}">
            @csrf
            <input type="hidden" name="_method" id="userFormMethod" value="POST">

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nombre de Usuario *</label>
                        <input type="text" name="name" id="user_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Correo Electrónico *</label>
                        <input type="email" name="email" id="user_email" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nombre(s) *</label>
                        <input type="text" name="first_name" id="user_first_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Apellidos *</label>
                        <input type="text" name="last_name" id="user_last_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Rol de Acceso *</label>
                        <select name="role_id" id="user_role_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Teléfono</label>
                        <input type="text" name="phone" id="user_phone" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Contraseña</label>
                        <input type="password" name="password" id="user_password" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all" placeholder="Mínimo 6 caracteres">
                        <span id="passHelpText" class="text-[11px] text-slate-400 mt-1 block">Requerido para nuevos registros</span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Estatus *</label>
                        <select name="status" id="user_status" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-[#f8fafd] flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('modalUserForm')" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition-colors">Cancelar</button>
                <button type="submit" class="btn-dkript-primary px-6 py-2.5 text-sm">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>
@endsection
