@extends('reports.layout')

@section('content')
<div class="summary-box">
    <strong>Total de Módulos:</strong> {{ count($menuOptions) }} módulos &bull; 
    <strong>Total de Permisos Asignables:</strong> {{ $menuOptions->sum(fn($opt) => $opt->permissions->count()) }} permisos
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">Módulo / Opción</th>
            <th style="width: 25%;">Ruta Interna</th>
            <th style="width: 10%; text-align: center;">Posición</th>
            <th style="width: 25%;">Permiso Granular</th>
            <th style="width: 15%; text-align: center;">Tipo de Acción</th>
        </tr>
    </thead>
    <tbody>
        @forelse($menuOptions as $option)
            @foreach($option->permissions as $perm)
                <tr>
                    <td class="font-bold">{{ $option->name }}</td>
                    <td class="font-mono" style="font-size: 7.5pt; color: #64748b;">{{ $option->route_name }}</td>
                    <td class="text-center font-bold" style="color: #0062f5;">#{{ $perm->position }}</td>
                    <td class="font-semibold">{{ $perm->name }}</td>
                    <td class="text-center">
                        @if($perm->position == 1)
                            <span class="badge badge-active">1: Crear</span>
                        @elseif($perm->position == 2)
                            <span class="badge" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">2: Editar</span>
                        @elseif($perm->position == 3)
                            <span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">3: Eliminar</span>
                        @elseif($perm->position == 4)
                            <span class="badge badge-primary">4: Ver / PDF</span>
                        @elseif($perm->position == 5)
                            <span class="badge" style="background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff;">5: Especial / Excel</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="5" class="text-center" style="padding: 20px; color: #64748b;">
                    No hay permisos registrados en el catálogo.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
