@extends('layouts.admin')

@section('title', 'Gestión de Roles')
@section('page_title', 'Roles y Permisos RBAC')

@section('content')
<div class="content-section">
    <!-- Barra de Herramientas Estándar con Permisos -->
    @include('partials.layouts.page-header', [
        'searchPlaceholder' => 'Buscar por nombre o descripción de rol...',
        'newOnClick' => "resetRoleForm('" . route('roles.store') . "'); openModal('modalRoleForm')",
        'newModalId' => 'modalRoleForm',
        'newButtonText' => 'Nuevo Rol'
    ])

    <!-- Tabla Estilo Tarjeta Blanca -->
    <div class="table-card">
        <div class="overflow-x-auto">
            <table id="tbExel" class="w-full text-left text-sm text-slate-600 catalog-data-table">
                <thead class="bg-[#f8fafd] text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold">ID</th>
                        <th class="px-6 py-4 font-bold">Rol</th>
                        <th class="px-6 py-4 font-bold">Descripción</th>
                        <th class="px-6 py-4 font-bold">Usuarios</th>
                        <th class="px-6 py-4 font-bold">Permisos Asignados</th>
                        <th class="px-6 py-4 font-bold">Estatus</th>
                        <th class="px-6 py-4 font-bold text-right no-export">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($roles as $role)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-400 text-xs">#{{ $role->id }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-shield-shaded text-[#7928ca] text-base"></i>
                                    <span class="font-bold text-slate-900">{{ $role->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                                {{ $role->description ?: 'Sin descripción adicional' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#0062f5]/10 text-[#0062f5] border border-[#0062f5]/20">
                                    <i class="bi bi-person"></i> {{ $role->profiles_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#7928ca]/10 text-[#7928ca] border border-[#7928ca]/20">
                                    <i class="bi bi-key"></i> {{ $role->permissions_count }} activos
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($role->status == 1)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> ACTIVO
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> INACTIVO
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right no-export">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Posición 2: Editar --}}
                                    @if(auth()->user()->isSuperAdmin() || in_array(2, session('mypermits', [])))
                                        <button type="button" 
                                                onclick="editRole({{ $role->id }})" 
                                                title="Editar Rol y Permisos" 
                                                class="p-2 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition-colors">
                                            <i class="bi bi-pencil-square text-base"></i>
                                        </button>
                                    @endif

                                    {{-- Posición 3: Eliminar --}}
                                    @if(auth()->user()->isSuperAdmin() || in_array(3, session('mypermits', [])))
                                        @if($role->id !== 1)
                                            <form method="POST" action="{{ route('roles.destroy', $role) }}" data-confirm="¿Estás seguro de eliminar este rol? Esta acción no se puede deshacer." class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        title="Eliminar Rol" 
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
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                No se encontraron roles registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($roles->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Incluir Modal Formulario frmrole -->
@include('partials.roles.frmrole')

@endsection
