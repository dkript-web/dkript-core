@extends('layouts.admin')

@section('title', 'Catálogo de Permisos')
@section('page_title', 'Catálogo de Módulos y Permisos')

@section('content')
<div class="content-section">
    <!-- Barra de Herramientas Estándar -->
    @include('partials.layouts.page-header', [
        'searchPlaceholder' => 'Buscar permisos por nombre o módulo...'
    ])

    <!-- Tabla de Permisos por Módulo -->
    <div class="table-card">
        <div class="overflow-x-auto">
            <table id="tbExel" class="w-full text-left text-sm text-slate-600 catalog-data-table">
                <thead class="bg-[#f8fafd] text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold">Módulo / Opción</th>
                        <th class="px-6 py-4 font-bold">Ruta Interna</th>
                        <th class="px-6 py-4 font-bold">Posición</th>
                        <th class="px-6 py-4 font-bold">Permiso Granular</th>
                        <th class="px-6 py-4 font-bold">Tipo de Acción</th>
                        <th class="px-6 py-4 font-bold">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($menuOptions as $option)
                        @foreach($option->permissions as $perm)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-[#0062f5]/10 text-[#0062f5] border border-[#0062f5]/20 flex items-center justify-center text-sm">
                                            <i class="bi {{ $option->icon }}"></i>
                                        </div>
                                        <span class="font-bold text-slate-900">{{ $option->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 font-mono text-xs text-slate-500">
                                    {{ $option->route_name }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="w-6 h-6 rounded-full bg-[#071026] text-[#00d4ff] font-bold text-xs inline-flex items-center justify-center shadow-sm">
                                        {{ $perm->position }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 font-semibold text-slate-800">
                                    {{ $perm->name }}
                                </td>
                                <td class="px-6 py-3.5">
                                    @if($perm->position == 1)
                                        <span class="badge-pos-1 px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                            <i class="bi bi-plus-circle-fill text-emerald-500"></i> Crear (Altas y Duplicar)
                                        </span>
                                    @elseif($perm->position == 2)
                                        <span class="badge-pos-2 px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                            <i class="bi bi-pencil-square text-amber-500"></i> Editar (Modificación)
                                        </span>
                                    @elseif($perm->position == 3)
                                        <span class="badge-pos-3 px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                            <i class="bi bi-trash3-fill text-rose-500"></i> Eliminar (Bajas)
                                        </span>
                                    @elseif($perm->position == 4)
                                        <span class="badge-pos-4 px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                            <i class="bi bi-eye-fill text-sky-500"></i> Ver / Exportar a PDF
                                        </span>
                                    @elseif($perm->position == 5)
                                        <span class="badge-pos-5 px-2.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                            <i class="bi bi-shield-check text-purple-500"></i> Especial / Exportar a Excel
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No se encontraron permisos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
