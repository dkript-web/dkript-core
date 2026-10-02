@extends('layouts.admin')

@section('title', 'Auditoría del Sistema')
@section('page_title', 'Registros y Bitácora de Auditoría')

@section('content')
<div class="content-section max-w-7xl w-full mx-auto pb-24 sm:pb-28 space-y-6">

    <!-- Encabezado de Navegación / Breadcrumbs -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-[#0062f5] transition-colors flex items-center gap-1">
                    <i class="bi bi-house"></i>
                    <span>Inicio</span>
                </a>
                <span>/</span>
                <span class="text-slate-800">Auditoría</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Bitácora de Eventos y Auditoría</h2>
            <p class="text-xs sm:text-sm text-slate-500">Supervisión en tiempo real de operaciones, accesos, cambios en registros y alertas de seguridad</p>
        </div>

        <!-- Acciones Globales (Exportar Excel, PDF, Purgar) -->
        <div class="flex items-center gap-2 flex-wrap">
            @if(auth()->user()->isSuperAdmin() || in_array('6:5', session('permission_matrix', [])))
                <a href="{{ route('audit-logs.export.excel', request()->query()) }}" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-emerald-300 text-emerald-700 text-xs font-bold hover:bg-emerald-50 transition-all shadow-xs"
                   title="Exportar registros a hoja de cálculo Excel">
                    <i class="bi bi-file-earmark-excel-fill text-emerald-600 text-sm"></i>
                    <span>Exportar Excel</span>
                </a>
            @endif

            @if(auth()->user()->isSuperAdmin() || in_array('6:4', session('permission_matrix', [])))
                <a href="{{ route('audit-logs.export.pdf', request()->query()) }}" 
                   target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-rose-300 text-rose-700 text-xs font-bold hover:bg-rose-50 transition-all shadow-xs"
                   title="Generar e imprimir informe en PDF">
                    <i class="bi bi-file-earmark-pdf-fill text-rose-600 text-sm"></i>
                    <span>Exportar PDF</span>
                </a>
            @endif

            @if(auth()->user()->isSuperAdmin() || in_array('6:3', session('permission_matrix', [])))
                <button type="button" 
                        onclick="openModal('modalAuditPurge')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-xs cursor-pointer">
                    <i class="bi bi-trash3 text-xs"></i>
                    <span>Depurar</span>
                </button>
            @endif
        </div>
    </div>

    <!-- TARJETAS MÉTRICAS DE AUDITORÍA -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Tarjeta 1: Total Registros -->
        <div class="table-card p-4 sm:p-5 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total de Eventos</p>
                <p class="text-2xl font-black text-slate-900 mt-0.5">{{ number_format($metrics['total']) }}</p>
                <span class="text-[10px] text-slate-400 font-mono-code">Histórico acumulado</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-[#0062f5] border border-blue-200/60 flex items-center justify-center text-xl shadow-xs">
                <i class="bi bi-journal-text"></i>
            </div>
        </div>

        <!-- Tarjeta 2: Eventos de Hoy -->
        <div class="table-card p-4 sm:p-5 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Actividad de Hoy</p>
                <p class="text-2xl font-black text-emerald-600 mt-0.5">{{ number_format($metrics['today']) }}</p>
                <span class="text-[10px] text-emerald-600 font-mono-code font-bold">Últimas 24 horas</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200/60 flex items-center justify-center text-xl shadow-xs">
                <i class="bi bi-calendar-check"></i>
            </div>
        </div>

        <!-- Tarjeta 3: Logins / Accesos -->
        <div class="table-card p-4 sm:p-5 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Accesos y Sesiones</p>
                <p class="text-2xl font-black text-sky-600 mt-0.5">{{ number_format($metrics['logins']) }}</p>
                <span class="text-[10px] text-slate-400 font-mono-code">Logins y logouts</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-sky-50 text-sky-600 border border-sky-200/60 flex items-center justify-center text-xl shadow-xs">
                <i class="bi bi-person-check"></i>
            </div>
        </div>

        <!-- Tarjeta 4: Eliminaciones -->
        <div class="table-card p-4 sm:p-5 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Eliminaciones</p>
                <p class="text-2xl font-black text-rose-600 mt-0.5">{{ number_format($metrics['deletes']) }}</p>
                <span class="text-[10px] text-rose-500 font-mono-code font-bold">Eventos destructivos</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200/60 flex items-center justify-center text-xl shadow-xs">
                <i class="bi bi-shield-slash"></i>
            </div>
        </div>
    </div>

    <!-- BARRA DE FILTROS AVANZADOS -->
    <div class="table-card p-5">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Búsqueda General -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Palabra Clave</label>
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="{{ $search }}" 
                               placeholder="Descripción, usuario, IP..." 
                               class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                        <i class="bi bi-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    </div>
                </div>

                <!-- Módulo -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Módulo</label>
                    <select name="module" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                        <option value="">Todos los módulos</option>
                        @foreach($availableModules as $mod)
                            <option value="{{ $mod }}" {{ $module === $mod ? 'selected' : '' }}>{{ $mod }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Acción -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Acción</label>
                    <select name="action" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                        <option value="">Todas las acciones</option>
                        @foreach($availableActions as $act)
                            <option value="{{ $act }}" {{ $action === $act ? 'selected' : '' }}>{{ $act }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Desde Fecha -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Desde</label>
                    <input type="date" 
                           name="date_from" 
                           value="{{ $dateFrom }}" 
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                </div>

                <!-- Hasta Fecha -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Hasta</label>
                    <input type="date" 
                           name="date_to" 
                           value="{{ $dateTo }}" 
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                </div>
            </div>

            <!-- Botones de Acción de Filtros -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <a href="{{ route('audit-logs.index') }}" 
                   class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition-colors">
                    Limpiar
                </a>
                <button type="submit" 
                        class="px-4 py-1.5 rounded-xl bg-[#0062f5] text-white text-xs font-bold hover:bg-[#004ecc] shadow-xs shadow-blue-500/20 transition-all flex items-center gap-1.5">
                    <i class="bi bi-funnel"></i>
                    <span>Aplicar Filtros</span>
                </button>
            </div>
        </form>
    </div>

    <!-- TABLA DE EVENTOS DE AUDITORÍA -->
    <div class="table-card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                <i class="bi bi-list-check text-[#0062f5]"></i>
                <span>Registros Capturados</span>
            </h3>
            <span class="text-xs text-slate-500 font-mono-code">Mostrando {{ $logs->firstItem() ?: 0 }} - {{ $logs->lastItem() ?: 0 }} de {{ $logs->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 border-collapse">
                <thead class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold text-slate-600 uppercase tracking-wider select-none">
                    <tr>
                        <th class="py-3 px-4 w-16 text-center">ID</th>
                        <th class="py-3 px-4 w-36">Fecha / Hora</th>
                        <th class="py-3 px-4 w-44">Usuario</th>
                        <th class="py-3 px-4 w-28">Módulo</th>
                        <th class="py-3 px-4 w-28 text-center">Acción</th>
                        <th class="py-3 px-4">Descripción del Evento</th>
                        <th class="py-3 px-4 w-28 text-center">IP</th>
                        <th class="py-3 px-4 w-20 text-center">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        @php
                            $actionUpper = strtoupper($log->action);
                            $badgeAction = match($actionUpper) {
                                'CREATE', 'STORE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'UPDATE' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'DELETE' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'LOGIN' => 'bg-cyan-50 text-cyan-800 border-cyan-200',
                                'LOGOUT' => 'bg-slate-100 text-slate-700 border-slate-300',
                                'SECURITY', 'THROTTLED' => 'bg-amber-50 text-amber-800 border-amber-300',
                                'BACKUP' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };

                            $moduleUpper = strtoupper($log->module);
                            $badgeModule = match($moduleUpper) {
                                'AUTH' => 'bg-cyan-500/10 text-cyan-700 border-cyan-300/50',
                                'USERS' => 'bg-blue-500/10 text-blue-700 border-blue-300/50',
                                'ROLES', 'PERMISSIONS' => 'bg-purple-500/10 text-purple-700 border-purple-300/50',
                                'PARAMETERS' => 'bg-amber-500/10 text-amber-700 border-amber-300/50',
                                'BACKUPS' => 'bg-indigo-500/10 text-indigo-700 border-indigo-300/50',
                                'NOTIFICATIONS' => 'bg-rose-500/10 text-rose-700 border-rose-300/50',
                                default => 'bg-slate-500/10 text-slate-700 border-slate-300/50',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ (!empty($detailId) && $detailId == $log->id) ? 'bg-blue-50/70 ring-2 ring-[#0062f5]/40' : '' }}">
                            <!-- ID -->
                            <td class="py-3 px-4 text-center font-mono-code font-bold text-slate-400">
                                #{{ $log->id }}
                            </td>

                            <!-- Fecha y Hora -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                <div class="text-[10px] text-slate-400 font-mono-code">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Usuario -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-xl bg-gradient-to-br from-[#00D1FF]/20 to-[#007BFF]/20 border border-[#00D1FF]/40 text-[#0062f5] flex items-center justify-center font-bold text-xs flex-shrink-0">
                                        {{ strtoupper(substr($log->user_name ?: 'S', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-extrabold text-slate-800 truncate leading-snug">{{ $log->user_name ?: 'Sistema' }}</p>
                                        @if($log->user_email)
                                            <p class="text-[10px] text-slate-400 truncate font-mono-code">{{ $log->user_email }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Módulo -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $badgeModule }}">
                                    {{ $log->module }}
                                </span>
                            </td>

                            <!-- Acción -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider border {{ $badgeAction }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <!-- Descripción -->
                            <td class="py-3 px-4">
                                <p class="text-xs text-slate-700 leading-relaxed font-normal">{{ $log->description }}</p>
                            </td>

                            <!-- IP Address -->
                            <td class="py-3 px-4 text-center font-mono-code text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $log->ip_address ?: '127.0.0.1' }}
                            </td>

                            <!-- Botón Inspeccionar -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <button type="button" 
                                        onclick="DkriptAudit.showDetail({{ $log->id }})" 
                                        class="p-1.5 rounded-xl text-slate-500 hover:text-[#0062f5] hover:bg-blue-50 border border-transparent hover:border-blue-200 transition-colors cursor-pointer"
                                        title="Inspeccionar detalle del evento">
                                    <i class="bi bi-eye-fill text-sm"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-2">
                                    <i class="bi bi-journal-x"></i>
                                </div>
                                <p class="font-bold text-slate-600">No se encontraron registros de auditoría</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Prueba cambiando o limpiando los filtros de búsqueda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL DE INSPECCIÓN PROFUNDA DE AUDITORÍA (Cyber-Glass Detail) -->
<div id="modalAuditDetail" 
     class="modal-wrapper fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden" 
     role="dialog" 
     aria-modal="true">
    <div class="fixed inset-0 modal-backdrop-blur" onclick="closeModal('modalAuditDetail')"></div>
    <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 modal-card flex flex-col max-h-[90vh] relative z-10">
        
        <!-- Cabecera del Modal -->
        <div class="px-6 py-4 bg-gradient-to-r from-[#0A0F1C] to-[#101D35] text-white flex items-center justify-between border-b border-[#00D1FF]/30">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#00D1FF]/20 to-[#007BFF]/20 border border-[#00D1FF]/40 text-[#00D1FF] flex items-center justify-center text-base">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold dkript-text-metal tracking-wide">Inspección de Registro de Auditoría</h3>
                    <span id="auditDetailSubtitle" class="text-[10px] text-slate-400 font-mono-code">Cargando...</span>
                </div>
            </div>
            <button type="button" 
                    onclick="closeModal('modalAuditDetail')" 
                    class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <!-- Cuerpo del Modal -->
        <div class="p-6 overflow-y-auto space-y-4 flex-1 custom-catalog-scrollbar">
            <!-- Grid de Metadatos -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Usuario Actor</span>
                    <span id="auditDetailUser" class="font-extrabold text-slate-800 font-mono-code">-</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">IP de Origen</span>
                    <span id="auditDetailIp" class="font-extrabold text-slate-800 font-mono-code">-</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Método HTTP</span>
                    <span id="auditDetailMethod" class="font-extrabold text-slate-800 font-mono-code">-</span>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Fecha Exacta</span>
                    <span id="auditDetailDate" class="font-extrabold text-slate-800 font-mono-code">-</span>
                </div>
            </div>

            <!-- Descripción y Ruta URL -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Descripción Completa:</span>
                    <p id="auditDetailDescription" class="text-slate-800 font-normal leading-relaxed"></p>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Ruta URL Ejecutada:</span>
                    <code id="auditDetailUrl" class="text-[11px] text-[#0062f5] break-all font-mono-code block bg-white p-2 rounded-lg border border-slate-200"></code>
                </div>
                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Agente de Usuario (Browser):</span>
                    <span id="auditDetailUserAgent" class="text-[10px] text-slate-500 font-mono-code break-all block"></span>
                </div>
            </div>

            <!-- Comparador de Estado: Antes vs Después -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Estado Anterior -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 mb-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Estado Anterior (Old Values)</span>
                    </label>
                    <pre id="auditDetailOldValues" class="w-full p-3 rounded-2xl bg-[#0A0F1C] text-rose-300 font-mono-code text-[11px] overflow-x-auto max-h-56 custom-catalog-scrollbar border border-slate-800 select-all"></pre>
                </div>

                <!-- Estado Nuevo -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 mb-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Estado Nuevo (New Values)</span>
                    </label>
                    <pre id="auditDetailNewValues" class="w-full p-3 rounded-2xl bg-[#0A0F1C] text-emerald-300 font-mono-code text-[11px] overflow-x-auto max-h-56 custom-catalog-scrollbar border border-slate-800 select-all"></pre>
                </div>
            </div>
        </div>

        <!-- Pie del Modal -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
            <button type="button" 
                    onclick="closeModal('modalAuditDetail')" 
                    class="px-5 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE DEPURACIÓN / PURGA DE AUDITORÍA -->
<div id="modalAuditPurge" 
     class="modal-wrapper fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden" 
     role="dialog" 
     aria-modal="true">
    <div class="fixed inset-0 modal-backdrop-blur" onclick="closeModal('modalAuditPurge')"></div>
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 modal-card relative z-10">
        
        <div class="px-6 py-4 bg-gradient-to-r from-rose-900 to-[#101D35] text-white flex items-center justify-between border-b border-rose-500/30">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-400 flex items-center justify-center text-base">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-white">Depurar Bitácora de Auditoría</h3>
                    <span class="text-[10px] text-rose-300">Eliminación controlada de registros antiguos</span>
                </div>
            </div>
            <button type="button" 
                    onclick="closeModal('modalAuditPurge')" 
                    class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('audit-logs.clear') }}">
            @csrf
            @method('DELETE')

            <div class="p-6 space-y-4">
                <p class="text-xs text-slate-600 leading-relaxed">
                    Seleccione la ventana de retención deseada para purgar eventos y optimizar el almacenamiento de la base de datos:
                </p>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ventana de Depuración *</label>
                    <select name="days" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white font-bold">
                        <option value="90">Eliminar registros con más de 90 días de antigüedad</option>
                        <option value="60">Eliminar registros con más de 60 días de antigüedad</option>
                        <option value="30" selected>Eliminar registros con más de 30 días de antigüedad</option>
                        <option value="all">Eliminar TODO el historial histórico (Truncar tabla)</option>
                    </select>
                </div>

                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/80 text-[11px] text-amber-800 leading-snug flex items-start gap-2">
                    <i class="bi bi-info-circle-fill text-amber-600 flex-shrink-0 mt-0.5"></i>
                    <span>Esta operación es irreversible. La acción de depuración quedará registrada como un evento nuevo en la bitácora para fines de control.</span>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" 
                        onclick="closeModal('modalAuditPurge')" 
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-100 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-xs shadow-rose-600/30">
                    Confirmar Depuración
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
