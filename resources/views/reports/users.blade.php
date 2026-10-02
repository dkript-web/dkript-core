@extends('reports.layout')

@section('content')
<div class="summary-box">
    <strong>Total de Registros en Base de Datos:</strong> {{ count($users) }} usuarios &bull; 
    <strong>Activos:</strong> {{ $users->where('status', 1)->count() }} &bull; 
    <strong>Inactivos:</strong> {{ $users->where('status', 0)->count() }}
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%; text-align: center;">ID</th>
            <th style="width: 15%;">Usuario</th>
            <th style="width: 22%;">Nombre Completo</th>
            <th style="width: 23%;">Correo Electrónico</th>
            <th style="width: 14%;">Rol Asignado</th>
            <th style="width: 11%;">Teléfono</th>
            <th style="width: 10%; text-align: center;">Estatus</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td class="text-center font-mono font-bold" style="color: #64748b;">#{{ $user->id }}</td>
                <td class="font-bold">{{ $user->name }}</td>
                <td>{{ $user->profile?->full_name ?: 'Sin perfil registrado' }}</td>
                <td class="font-mono" style="font-size: 7.5pt;">{{ $user->email }}</td>
                <td>
                    <span class="badge badge-primary">
                        {{ $user->role?->name ?: 'Sin Rol' }}
                    </span>
                </td>
                <td style="font-size: 7.5pt;">{{ $user->profile?->phone ?: 'N/A' }}</td>
                <td class="text-center">
                    @if($user->status == 1)
                        <span class="badge badge-active">Activo</span>
                    @else
                        <span class="badge badge-inactive">Inactivo</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                    No hay usuarios registrados en el sistema.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
