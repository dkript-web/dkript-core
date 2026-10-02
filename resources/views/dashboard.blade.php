@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard Analítico')

@section('content')
<div class="space-y-0">
    <!-- Banner Ejecutivo de Identidad Dkript & Drypt -->
    <div class="bg-gradient-to-r from-[#071026] via-[#09173d] to-[#071026] rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-slate-900/10 mb-8 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6 border border-[#112356]">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-[#00d4ff]/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 -top-10 w-60 h-60 bg-[#0062f5]/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#00d4ff]/15 border border-[#00d4ff]/30 text-[#00d4ff] text-xs font-bold mb-3">
                <img src="{{ asset('assets/images/branding/icon-drypt-single.png') }}" alt="Drypt" class="w-4 h-4 object-contain">
                <span>Dkript Inc. Platform Core</span>
            </div>
            <h2 class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight">Panel Ejecutivo <span class="text-gradient-dkript">Dkript Inc.</span></h2>
            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                Plataforma base para administración y despliegue de soluciones empresariales. Monitorea métricas en tiempo real con RosenCharts y administra permisos granulares de forma centralizada.
            </p>
        </div>

        <div class="relative z-10 flex items-center gap-4 flex-shrink-0">
            <img src="{{ asset('assets/images/branding/drypt-oficial.png') }}" 
                 alt="Drypt - The Digital Alchemist" 
                 class="h-28 sm:h-36 w-auto object-contain drop-shadow-2xl hover:scale-105 transition-transform">
        </div>
    </div>

    <!-- 1. Tarjetas de Métricas Principales con Squircles Corporativos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 main-section-block">
        <!-- Card 1: Total Usuarios -->
        <div class="stat-card-elevate bg-white rounded-2xl p-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Usuarios</p>
                <h3 class="text-3xl font-black text-slate-900 mt-2">{{ $totalUsers }}</h3>
                <p class="text-xs text-emerald-600 font-bold mt-1 flex items-center gap-1">
                    <i class="bi bi-arrow-up-short text-base"></i> {{ $activeUsers }} activos
                </p>
            </div>
            <div class="icon-squircle-cobalt w-14 h-14 text-2xl flex-shrink-0">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>

        <!-- Card 2: Roles Configurados -->
        <div class="stat-card-elevate bg-white rounded-2xl p-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Roles RBAC</p>
                <h3 class="text-3xl font-black text-slate-900 mt-2">{{ $totalRoles }}</h3>
                <p class="text-xs text-slate-500 font-medium mt-1">Perfiles de seguridad</p>
            </div>
            <div class="icon-squircle-violet w-14 h-14 text-2xl flex-shrink-0">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
        </div>

        <!-- Card 3: Módulos Activos -->
        <div class="stat-card-elevate bg-white rounded-2xl p-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Módulos del Sistema</p>
                <h3 class="text-3xl font-black text-slate-900 mt-2">{{ $totalModules }}</h3>
                <p class="text-xs text-[#0062f5] font-semibold mt-1">Opciones de menú</p>
            </div>
            <div class="icon-squircle-emerald w-14 h-14 text-2xl flex-shrink-0">
                <i class="bi bi-grid-fill"></i>
            </div>
        </div>

        <!-- Card 4: Estatus de Seguridad -->
        <div class="stat-card-elevate bg-white rounded-2xl p-6 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Control de Acceso</p>
                <h3 class="text-2xl font-black text-slate-900 mt-2">5 Niveles</h3>
                <p class="text-xs text-slate-500 font-medium mt-1">Crear, Editar, Borrar, Ver, Esp.</p>
            </div>
            <div class="icon-squircle-amber w-14 h-14 text-2xl flex-shrink-0">
                <i class="bi bi-key-fill"></i>
            </div>
        </div>
    </div>

    <!-- 2. Panel Analítico con RosenCharts (D3.js) -->
    <div class="stats-panel-block grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 sm:gap-6">
        <!-- Gráfica 1: Donut Chart (Distribución de Usuarios) -->
        <div class="stat-card-elevate bg-white rounded-2xl p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-bold text-slate-900 text-sm sm:text-base">Distribución de Usuarios</h4>
                    <p class="text-xs text-slate-500">Activos vs Inactivos</p>
                </div>
                <span class="p-2 rounded-xl bg-[#0062f5]/10 text-[#0062f5] border border-[#0062f5]/20 text-sm"><i class="bi bi-pie-chart-fill"></i></span>
            </div>
            <div id="donutChartContainer" class="rosen-chart-container"></div>
        </div>

        <!-- Gráfica 2: Area / Bar Chart (Actividad en el Tiempo) -->
        <div class="stat-card-elevate bg-white rounded-2xl p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-bold text-slate-900 text-sm sm:text-base">Volumen de Actividad</h4>
                    <p class="text-xs text-slate-500">Registros mensuales</p>
                </div>
                <span class="p-2 rounded-xl bg-[#00d4ff]/10 text-[#0284c7] border border-[#00d4ff]/30 text-sm"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
            <div id="areaChartContainer" class="rosen-chart-container"></div>
        </div>

        <!-- Gráfica 3: Line Chart (Tendencias de Acceso) -->
        <div class="stat-card-elevate bg-white rounded-2xl p-4 sm:p-6 md:col-span-2 xl:col-span-1">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-bold text-slate-900 text-sm sm:text-base">Tendencias de Sesión</h4>
                    <p class="text-xs text-slate-500">Monitoreo por semana</p>
                </div>
                <span class="p-2 rounded-xl bg-[#7928ca]/10 text-[#7928ca] border border-[#7928ca]/30 text-sm"><i class="bi bi-activity"></i></span>
            </div>
            <div id="lineChartContainer" class="rosen-chart-container"></div>
        </div>
    </div>

    <!-- 3. Tabla de Actividad y Usuarios Recientes -->
    <div class="content-section table-card">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-slate-900 text-base">Usuarios Recientes</h4>
                <p class="text-xs text-slate-500">Últimos accesos y registros en el sistema</p>
            </div>
            <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-[#0062f5] hover:bg-[#0062f5]/10 border border-[#0062f5]/20 transition-all">
                <span>Ver todos</span>
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-[#f8fafd] text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold">Usuario</th>
                        <th class="px-6 py-4 font-bold">Rol</th>
                        <th class="px-6 py-4 font-bold">Estatus</th>
                        <th class="px-6 py-4 font-bold">Registrado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentUsers as $user)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <img src="{{ $user->profile?->avatar_url }}" class="w-9 h-9 rounded-full border border-slate-200 ring-1 ring-slate-300/50 object-cover">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $user->role?->name ?: 'Sin Rol' }}
                                </span>
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
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $user->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                No se encontraron usuarios recientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Inicialización de RosenCharts
        const donutData = @json($donutData);
        const areaData = @json($areaData);
        const lineData = @json($lineData);

        // 1. Donut Chart
        RosenCharts.renderDonut('donutChartContainer', donutData, {
            centerLabel: 'Usuarios',
            emptyMessage: 'Las métricas se activarán automáticamente al registrar los primeros datos.'
        });

        // 2. Area Chart
        RosenCharts.renderArea('areaChartContainer', areaData, {
            emptyMessage: 'Las métricas se activarán automáticamente al registrar los primeros datos.'
        });

        // 3. Line Chart
        RosenCharts.renderLine('lineChartContainer', lineData, {
            emptyMessage: 'Las métricas se activarán automáticamente al registrar los primeros datos.'
        });
    });
</script>
@endpush
