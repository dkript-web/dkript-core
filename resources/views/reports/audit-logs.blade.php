@extends('reports.layout')

@section('content')
<div class="summary-box">
    <strong>Total de Registros en el Reporte:</strong> {{ count($logs) }} eventos &bull; 
    <strong>Rango de Consulta:</strong> {{ count($logs) > 0 ? $logs->last()->created_at->format('d/m/Y') . ' al ' . $logs->first()->created_at->format('d/m/Y') : 'Sin registros' }}
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%; text-align: center;">ID</th>
            <th style="width: 14%;">Fecha / Hora</th>
            <th style="width: 16%;">Usuario Responsable</th>
            <th style="width: 12%;">Módulo</th>
            <th style="width: 11%; text-align: center;">Acción</th>
            <th style="width: 32%;">Descripción del Evento</th>
            <th style="width: 10%; text-align: center;">IP</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $log)
            @php
                $actionUpper = strtoupper($log->action);
                $badgeClass = match($actionUpper) {
                    'LOGIN', 'LOGOUT' => 'badge-primary',
                    'CREATE', 'STORE' => 'badge-active',
                    'UPDATE' => 'badge-primary',
                    'DELETE' => 'badge-inactive',
                    'BACKUP' => 'badge-primary',
                    default => 'badge-primary',
                };
            @endphp
            <tr>
                <td class="text-center font-mono font-bold" style="color: #64748b;">#{{ $log->id }}</td>
                <td class="font-mono" style="font-size: 7.5pt;">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td>
                    <div class="font-bold">{{ $log->user_name ?: 'Sistema' }}</div>
                    @if($log->user_email)
                        <div class="font-mono" style="font-size: 6.5pt; color: #64748b;">{{ $log->user_email }}</div>
                    @endif
                </td>
                <td>
                    <span class="font-bold" style="color: #1e3a8a;">{{ $log->module }}</span>
                </td>
                <td class="text-center">
                    <span class="badge {{ $badgeClass }}">{{ $log->action }}</span>
                </td>
                <td style="font-size: 7.5pt;">{{ $log->description }}</td>
                <td class="text-center font-mono" style="font-size: 7pt; color: #64748b;">{{ $log->ip_address ?: '127.0.0.1' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                    No se encontraron registros de auditoría bajo los criterios seleccionados.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
