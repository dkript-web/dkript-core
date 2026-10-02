@extends('reports.layout')

@section('content')
<div class="summary-box">
    <strong>Total de Roles en Base de Datos:</strong> {{ count($roles) }} roles &bull; 
    <strong>Activos:</strong> {{ $roles->where('status', 1)->count() }} &bull; 
    <strong>Inactivos:</strong> {{ $roles->where('status', 0)->count() }}
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
            <th style="width: 25%;">Nombre del Rol</th>
            <th style="width: 35%;">Descripción</th>
            <th style="width: 12%; text-align: center;">Usuarios</th>
            <th style="width: 10%; text-align: center;">Permisos</th>
            <th style="width: 10%; text-align: center;">Estatus</th>
        </tr>
    </thead>
    <tbody>
        @forelse($roles as $role)
            <tr>
                <td class="text-center font-mono font-bold" style="color: #64748b;">#{{ $role->id }}</td>
                <td class="font-bold">{{ $role->name }}</td>
                <td style="color: #475569; font-size: 8pt;">{{ $role->description ?: 'Sin descripción' }}</td>
                <td class="text-center font-bold" style="color: #0062f5;">{{ $role->profiles_count ?? 0 }}</td>
                <td class="text-center font-bold" style="color: #7928ca;">{{ $role->permissions_count ?? 0 }}</td>
                <td class="text-center">
                    @if($role->status == 1)
                        <span class="badge badge-active">Activo</span>
                    @else
                        <span class="badge badge-inactive">Inactivo</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 20px; color: #64748b;">
                    No hay roles registrados en el sistema.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
