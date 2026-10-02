@extends('layouts.admin')

@section('title', 'Copias de Seguridad')
@section('page_title', 'Copias de Seguridad y Respaldos')

@section('content')
<div class="content-section max-w-6xl w-full mx-auto pb-24 sm:pb-28 space-y-8">
    
    <!-- Encabezado de Navegación / Breadcrumbs -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('parameters.index') }}" class="hover:text-[#0062f5] transition-colors flex items-center gap-1">
                    <i class="bi bi-sliders"></i>
                    <span>Parámetros</span>
                </a>
                <span>/</span>
                <span class="text-slate-800">Copias de Seguridad</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Centro de Respaldos Corporativos</h2>
            <p class="text-xs sm:text-sm text-slate-500">Genere, descargue y administre volcados de base de datos MySQL y paquetes multimedia</p>
        </div>

        <a href="{{ route('parameters.index') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 hover:border-slate-300 transition-all shadow-xs">
            <i class="bi bi-arrow-left"></i>
            <span>Volver a Parámetros</span>
        </a>
    </div>

    <!-- TARJETAS DE ACCIÓN RÁPIDA -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Tarjeta 1: Respaldo de Base de Datos -->
        <div class="table-card p-6 sm:p-7 relative overflow-hidden flex flex-col justify-between group">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#00D1FF]/20 to-[#007BFF]/20 border border-[#00D1FF]/40 text-[#0062f5] flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                    <i class="bi bi-database-down"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Respaldo de Base de Datos</h3>
                    <p class="text-xs text-slate-500 mt-1">Genera un volcado completo de todas las tablas, esquemas, índices y registros del sistema.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('backups.database') }}" class="space-y-4 pt-2">
                @csrf
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="text-xs font-bold text-slate-700 block uppercase tracking-wider">Formato de Exportación:</label>
                    <div class="flex items-center gap-4 flex-wrap">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="radio" name="compress" value="1" checked class="text-[#0062f5] focus:ring-[#0062f5]/30">
                            <span>Comprimido <strong>.sql.gz</strong> (Recomendado, 85-90% más ligero)</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="radio" name="compress" value="0" class="text-[#0062f5] focus:ring-[#0062f5]/30">
                            <span>Texto plano <strong>.sql</strong></span>
                        </label>
                    </div>
                </div>

                @if(auth()->user()->isSuperAdmin() || in_array(1, session('mypermits', [])))
                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-[#0062f5] to-[#004ecc] hover:from-[#004ecc] hover:to-[#003bb3] text-white text-xs font-extrabold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <i class="bi bi-cloud-arrow-down-fill text-sm"></i>
                        <span>Generar Respaldo de Base de Datos</span>
                    </button>
                @else
                    <button type="button" disabled class="w-full px-5 py-3 rounded-xl bg-slate-200 text-slate-400 text-xs font-bold cursor-not-allowed">
                        <span>Sin permiso para crear respaldos</span>
                    </button>
                @endif
            </form>
        </div>

        <!-- Tarjeta 2: Respaldo Multimedia (Uploads / Branding) -->
        <div class="table-card p-6 sm:p-7 relative overflow-hidden flex flex-col justify-between group">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#7A3CFF]/20 to-[#007BFF]/20 border border-[#7A3CFF]/40 text-[#7A3CFF] flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                    <i class="bi bi-file-earmark-zip"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Respaldo Multimedia y Logotipos</h3>
                    <p class="text-xs text-slate-500 mt-1">Empaqueta todos los logotipos, banners e imágenes de marca almacenadas en la carpeta de subidas públicas.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('backups.media') }}" class="space-y-4 pt-2">
                @csrf
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <i class="bi bi-info-circle text-[#7A3CFF]"></i>
                        <span>Se empaquetará la carpeta <code>public/uploads/</code> en un archivo comprimido <strong>.zip</strong>.</span>
                    </div>
                </div>

                @if(auth()->user()->isSuperAdmin() || in_array(1, session('mypermits', [])))
                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-[#7A3CFF] to-[#6020D8] hover:from-[#6020D8] hover:to-[#4C18B0] text-white text-xs font-extrabold shadow-md shadow-purple-500/20 transition-all cursor-pointer">
                        <i class="bi bi-archive-fill text-sm"></i>
                        <span>Generar Respaldo Multimedia (.zip)</span>
                    </button>
                @else
                    <button type="button" disabled class="w-full px-5 py-3 rounded-xl bg-slate-200 text-slate-400 text-xs font-bold cursor-not-allowed">
                        <span>Sin permiso para crear respaldos</span>
                    </button>
                @endif
            </form>
        </div>

    </div>

    <!-- PROGRAMACIÓN AUTOMÁTICA DE RESPALDOS & MONITOR DE TAREAS CRON -->
    <div class="table-card p-6 sm:p-8 space-y-6">
        <!-- Encabezado de la Sección -->
        <div class="flex items-center justify-between pb-5 border-b border-slate-100 flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 border border-emerald-500/40 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0 shadow-xs">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Programación Automática & Monitor de Tareas Cron</h3>
                    <p class="text-xs text-slate-500">Automatice la generación de respaldos periódicos y supervise las tareas del motor de tareas programadas</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                    <span class="cron-pulse-dot"></span>
                    <span class="text-slate-500 font-medium">Hora Servidor:</span>
                    <span id="schedulerServerTime" class="font-mono font-bold text-slate-700">--:--:--</span>
                </div>
                <button type="button" id="btnRefreshScheduler" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-[#0062f5] hover:border-blue-200 transition-all text-xs font-bold shadow-2xs cursor-pointer" title="Actualizar estado del programador">
                    <i class="bi bi-arrow-clockwise text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Formulario de Configuración de Respaldos Automáticos -->
        <form id="autoBackupSettingsForm" action="{{ route('backups.auto-settings') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Switch Habilitar / Deshabilitar -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="auto_backup_enabled" class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                            Estado del Servicio
                        </label>
                        <input type="checkbox" id="auto_backup_enabled" name="auto_backup_enabled" value="1" 
                               {{ ($settings->auto_backup_enabled ?? false) ? 'checked' : '' }} 
                               class="w-4 h-4 text-[#0062f5] border-slate-300 rounded focus:ring-[#0062f5]/30 cursor-pointer">
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Habilita la ejecución automática del comando de respaldo en los ciclos de cron del sistema.
                    </p>
                    <div class="pt-1 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Última ejecución:</span>
                        <span id="autoBackupLastRunBadge" class="font-mono font-bold text-slate-700">
                            {{ $settings->auto_backup_last_run_at ? $settings->auto_backup_last_run_at->diffForHumans() : 'Nunca ejecutado' }}
                        </span>
                    </div>
                </div>

                <!-- Frecuencia y Hora -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <label class="text-xs font-extrabold text-slate-800 uppercase tracking-wider block">
                        Frecuencia & Hora
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Periodicidad</label>
                            <select name="auto_backup_frequency" class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:border-[#0062f5] focus:ring focus:ring-[#0062f5]/20 py-1.5 px-2 bg-white">
                                <option value="daily" {{ ($settings->auto_backup_frequency ?? 'daily') === 'daily' ? 'selected' : '' }}>Diario</option>
                                <option value="weekly" {{ ($settings->auto_backup_frequency ?? '') === 'weekly' ? 'selected' : '' }}>Semanal (Lunes)</option>
                                <option value="monthly" {{ ($settings->auto_backup_frequency ?? '') === 'monthly' ? 'selected' : '' }}>Mensual (Día 1)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Hora (24h)</label>
                            <input type="text" name="auto_backup_time" value="{{ $settings->auto_backup_time ?? '02:00' }}" placeholder="02:00"
                                   pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$"
                                   class="w-full text-xs font-mono font-bold rounded-lg border-slate-200 focus:border-[#0062f5] focus:ring focus:ring-[#0062f5]/20 py-1.5 px-2 bg-white text-center">
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 block">Formato de 24 horas (ej. 02:00 para 2:00 AM).</span>
                </div>

                <!-- Tipo y Retención -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <label class="text-xs font-extrabold text-slate-800 uppercase tracking-wider block">
                        Alcance & Retención
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Contenido</label>
                            <select name="auto_backup_type" class="w-full text-xs font-semibold rounded-lg border-slate-200 focus:border-[#0062f5] focus:ring focus:ring-[#0062f5]/20 py-1.5 px-2 bg-white">
                                <option value="database" {{ ($settings->auto_backup_type ?? 'database') === 'database' ? 'selected' : '' }}>Solo Base de Datos</option>
                                <option value="all" {{ ($settings->auto_backup_type ?? '') === 'all' ? 'selected' : '' }}>Completo (DB + Medios)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Máx. Retención</label>
                            <input type="number" name="auto_backup_max_retention" min="1" max="365" value="{{ $settings->auto_backup_max_retention ?? 7 }}"
                                   class="w-full text-xs font-mono font-bold rounded-lg border-slate-200 focus:border-[#0062f5] focus:ring focus:ring-[#0062f5]/20 py-1.5 px-2 bg-white text-center">
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 block">Número máximo de copias antes de depurar las más antiguas.</span>
                </div>
            </div>

            <!-- Botones de Acción de Configuración -->
            <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
                <div class="flex items-center gap-3">
                    @if(auth()->user()->isSuperAdmin() || in_array(2, session('mypermits', [])))
                        <button type="submit" id="btnSaveAutoBackupSettings"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold shadow-sm transition-all cursor-pointer">
                            <i class="bi bi-floppy2-fill"></i>
                            <span>Guardar Configuración de Auto-Respaldo</span>
                        </button>
                    @endif

                    @if(auth()->user()->isSuperAdmin() || in_array(1, session('mypermits', [])))
                        <button type="button" id="btnRunAutoBackupNow"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm transition-all cursor-pointer">
                            <i class="bi bi-play-circle-fill"></i>
                            <span>Ejecutar Auto-Respaldo Ahora</span>
                        </button>
                    @endif
                </div>

                <div class="text-xs text-slate-500 italic">
                    * Los respaldos programados conservan la integridad referencial y limpian las copias viejas automáticamente.
                </div>
            </div>
        </form>

        <!-- Monitor de Tareas del Scheduler Laravel (schedule:list) -->
        <div class="pt-4 border-t border-slate-100 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-black text-slate-800 uppercase tracking-wider">Tareas Programadas del Sistema (Scheduler)</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">En Vivo</span>
                </div>
                <span class="text-[11px] text-slate-400 font-mono">Kernel Artisan Schedule</span>
            </div>

            <div class="overflow-x-auto border border-slate-200/80 rounded-xl" id="schedulerTasksTable">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50/75">
                            <th class="py-2.5 px-4">Comando Artisan</th>
                            <th class="py-2.5 px-4">Expresión Cron</th>
                            <th class="py-2.5 px-4">Próxima Ejecución</th>
                            <th class="py-2.5 px-4">Descripción</th>
                        </tr>
                    </thead>
                    <tbody id="schedulerTasksBody" class="divide-y divide-slate-100 text-xs">
                        <tr>
                            <td colspan="4" class="text-center py-6 text-slate-400 text-xs">
                                <i class="bi bi-arrow-repeat animate-spin inline-block mr-1"></i> Cargando tareas programadas del sistema...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Terminal Crontab Command Box -->
            <div class="space-y-2 pt-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-700 flex items-center gap-1.5">
                        <i class="bi bi-terminal-fill text-slate-900"></i> Entrada Crontab para el Servidor (Linux / VPS / cPanel / Cloud):
                    </span>
                    <button type="button" id="btnCopyCronCommand" class="text-[11px] font-bold text-[#0062f5] hover:underline flex items-center gap-1 cursor-pointer">
                        <i class="bi bi-clipboard"></i> Copiar Comando
                    </button>
                </div>
                <div class="cron-code-box">
                    <code id="cronCommandText">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
                    <i class="bi bi-hdd-network text-slate-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE HISTORIAL DE RESPALDOS -->
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="icon-squircle-cobalt w-10 h-10 text-xl flex-shrink-0">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Historial de Respaldos Disponibles</h3>
                    <p class="text-xs text-slate-500">Archivos almacenados en el servidor listos para su descarga o restauración</p>
                </div>
            </div>

            <div class="text-xs font-mono text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                Total: <span class="font-bold text-slate-800">{{ count($backups) }}</span> archivos
            </div>
        </div>

        @if(count($backups) === 0)
            <div class="text-center py-12 px-4 rounded-2xl bg-slate-50 border border-dashed border-slate-200">
                <div class="w-14 h-14 rounded-full bg-slate-200/70 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                    <i class="bi bi-inbox"></i>
                </div>
                <h4 class="text-sm font-extrabold text-slate-800">Aún no se han generado copias de seguridad</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Utilice los botones superiores para generar su primer respaldo de base de datos o multimedia.</p>
            </div>
        @else
            <div class="overflow-x-auto custom-catalog-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50/75">
                            <th class="py-3 px-4">Tipo</th>
                            <th class="py-3 px-4">Nombre del Archivo</th>
                            <th class="py-3 px-4">Tamaño</th>
                            <th class="py-3 px-4">Fecha de Generación</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($backups as $b)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Tipo -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($b['type'] === 'database_compressed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-extrabold font-mono uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="bi bi-file-earmark-zip-fill text-emerald-600"></i>
                                            <span>BD Gzip (.sql.gz)</span>
                                        </span>
                                    @elseif($b['type'] === 'database_plain')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-extrabold font-mono uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="bi bi-filetype-sql text-blue-600"></i>
                                            <span>BD SQL Plano</span>
                                        </span>
                                    @elseif($b['type'] === 'media_zip')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-extrabold font-mono uppercase bg-purple-50 text-purple-700 border border-purple-200">
                                            <i class="bi bi-images text-purple-600"></i>
                                            <span>Multimedia (.zip)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600">
                                            <span>Archivo</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Nombre -->
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800 break-all">
                                    {{ $b['filename'] }}
                                </td>

                                <!-- Tamaño -->
                                <td class="py-3.5 px-4 font-mono text-slate-600 whitespace-nowrap">
                                    {{ $b['size_formatted'] }}
                                </td>

                                <!-- Fecha -->
                                <td class="py-3.5 px-4 font-mono text-slate-500 whitespace-nowrap">
                                    {{ $b['created_at'] }}
                                </td>

                                <!-- Acciones -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Restaurar Base de Datos (Posición 5 / SuperAdmin) -->
                                        @if(($b['type'] === 'database_compressed' || $b['type'] === 'database_plain') && (auth()->user()->isSuperAdmin() || in_array(5, session('mypermits', []))))
                                            <button type="button" 
                                                    onclick="DkriptBackups.openRestoreModal('{{ $b['filename'] }}', '{{ $b['created_at'] }}', '{{ $b['size_formatted'] }}')"
                                                    class="p-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white border border-amber-200 transition-all text-xs font-bold inline-flex items-center gap-1 cursor-pointer"
                                                    title="Restaurar base de datos">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                <span class="hidden sm:inline">Restaurar</span>
                                            </button>
                                        @endif

                                        <!-- Descargar -->
                                        @if(auth()->user()->isSuperAdmin() || in_array(4, session('mypermits', [])))
                                            <a href="{{ route('backups.download', ['filename' => $b['filename']]) }}" 
                                               class="p-2 rounded-lg bg-blue-50 text-[#0062f5] hover:bg-[#0062f5] hover:text-white border border-blue-200 transition-all text-xs font-bold inline-flex items-center gap-1"
                                               title="Descargar respaldo">
                                                <i class="bi bi-download"></i>
                                                <span class="hidden sm:inline">Descargar</span>
                                            </a>
                                        @endif

                                        <!-- Eliminar -->
                                        @if(auth()->user()->isSuperAdmin() || in_array(3, session('mypermits', [])))
                                            <form method="POST" 
                                                  action="{{ route('backups.destroy', ['filename' => $b['filename']]) }}"
                                                  onsubmit="return confirm('¿Está seguro de eliminar permanentemente este archivo de respaldo ({{ $b['filename'] }})? Esta acción no se puede deshacer.');"
                                                  class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 transition-all text-xs font-bold inline-flex items-center gap-1 cursor-pointer"
                                                        title="Eliminar archivo">
                                                    <i class="bi bi-trash3"></i>
                                                    <span class="hidden sm:inline">Eliminar</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE RESTAURACIÓN -->
    <div id="modalRestoreBackup" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 hidden" role="dialog" aria-modal="true">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 transform transition-all">
            <!-- Modal Header -->
            <div class="p-5 bg-gradient-to-r from-amber-600 to-amber-700 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl text-white">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-white">Confirmar Restauración</h4>
                        <p class="text-xs text-amber-100">Operación crítica de recuperación de base de datos</p>
                    </div>
                </div>
                <button type="button" onclick="DkriptBackups.closeRestoreModal()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <form id="formRestoreBackup" method="POST" action="" class="p-6 space-y-4">
                @csrf
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs space-y-2">
                    <p class="font-bold flex items-center gap-1.5">
                        <i class="bi bi-shield-alert text-amber-600"></i>
                        <span>¡Advertencia de Integridad!</span>
                    </p>
                    <p>Esta operación reemplazará el estado actual de la base de datos con los datos contenidos en el respaldo seleccionado. Los cambios posteriores a la fecha del respaldo serán sobrescritos.</p>
                    <p class="text-[11px] text-amber-700 font-semibold">* El sistema generará automáticamente un <strong>Safety Backup</strong> preventivo antes de aplicar cualquier cambio.</p>
                </div>

                <!-- Detalles del Respaldo -->
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 text-xs space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium">Archivo:</span>
                        <span id="restoreModalFilename" class="font-mono font-bold text-slate-800 break-all">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium">Fecha:</span>
                        <span id="restoreModalDate" class="font-mono text-slate-700">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium">Tamaño:</span>
                        <span id="restoreModalSize" class="font-mono text-slate-700">--</span>
                    </div>
                </div>

                <!-- Checkbox de Confirmación -->
                <div class="flex items-start gap-2 pt-2">
                    <input type="checkbox" id="checkConfirmRestore" onchange="document.getElementById('btnSubmitRestore').disabled = !this.checked;" class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <label for="checkConfirmRestore" class="text-xs text-slate-700 font-medium cursor-pointer">
                        Comprendo el impacto de esta acción y deseo proceder con la restauración de la base de datos.
                    </label>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="DkriptBackups.closeRestoreModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition-all">
                        Cancelar
                    </button>
                    <button type="submit" id="btnSubmitRestore" disabled class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-extrabold shadow-sm transition-all inline-flex items-center gap-2 cursor-pointer">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Ejecutar Restauración</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
