<!-- PESTAÑA 7: Mantenimiento, Caché y Respaldos -->
<div id="tab-maintenance" class="parameter-tab-pane space-y-6 hidden">
    <!-- Depuración de Memoria Caché y Enlace a Respaldos -->
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="icon-squircle-cobalt w-12 h-12 text-2xl flex-shrink-0">
                    <i class="bi bi-tools"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Mantenimiento y Rendimiento</h3>
                    <p class="text-xs text-slate-500">Optimice el rendimiento del servidor y acceda al centro integral de copias de seguridad</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Depurar Memoria Caché -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900">Depuración de Memoria Caché</h4>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Ejecuta una limpieza completa del framework (<code class="text-xs bg-slate-200 px-1 py-0.5 rounded text-slate-800">optimize:clear</code>): vacía la caché de configuración, eventos, rutas y plantillas Blade compiladas. Útil tras realizar ajustes directos en el servidor.
                    </p>
                </div>
                <div>
                    <button type="button"
                            id="btnClearSystemCache"
                            onclick="DkriptParameters.clearCache('{{ route('parameters.clear-cache') }}', '{{ csrf_token() }}')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition-all active:scale-95">
                        <i class="bi bi-trash3-fill"></i>
                        <span>Depurar Memoria Caché General</span>
                    </button>
                </div>
            </div>

            <!-- Enlace al Centro de Respaldos -->
            <div class="p-5 rounded-2xl bg-blue-50/50 border border-blue-200/70 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-[#0062f5] flex items-center justify-center font-bold">
                            <i class="bi bi-database-fill-gear"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900">Copias de Seguridad del Sistema</h4>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Genere y restaure volcados completos de la base de datos MySQL/SQLite (<code class="text-xs bg-blue-100 px-1 py-0.5 rounded text-blue-900">.sql</code>, <code class="text-xs bg-blue-100 px-1 py-0.5 rounded text-blue-900">.sql.gz</code>) o empaquete archivos multimedia (<code class="text-xs bg-blue-100 px-1 py-0.5 rounded text-blue-900">.zip</code>) con un solo clic.
                    </p>
                </div>
                <div>
                    <a href="{{ route('backups.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#071026] hover:bg-[#112356] text-white text-xs font-bold shadow-sm transition-all active:scale-95">
                        <i class="bi bi-arrow-right-circle-fill text-[#00d4ff]"></i>
                        <span>Ir al Centro de Respaldos</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Copias de Seguridad y Respaldo del Sistema -->
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="icon-squircle-cobalt w-12 h-12 text-2xl flex-shrink-0">
                    <i class="bi bi-cloud-arrow-down-fill"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Formatos y Tipos de Respaldo</h3>
                    <p class="text-xs text-slate-500">Genere y administre volcados de base de datos MySQL (.sql y .sql.gz) y paquetes multimedia (.zip)</p>
                </div>
            </div>
            <a href="{{ route('backups.index') }}" 
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0062f5] to-[#004ecc] hover:from-[#004ecc] hover:to-[#003bb3] text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all">
                <i class="bi bi-hdd-network-fill"></i>
                <span>Administrar Respaldos</span>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="bi bi-file-earmark-zip-fill"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-800 block">Compresión Gzip (.sql.gz)</span>
                    <span class="text-[11px] text-slate-500">Reduce hasta 90% el tamaño</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="bi bi-filetype-sql"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-800 block">SQL Plano (.sql)</span>
                    <span class="text-[11px] text-slate-500">Texto legible e importación directa</span>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="bi bi-images"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-800 block">Multimedia (.zip)</span>
                    <span class="text-[11px] text-slate-500">Logotipos y assets de marca</span>
                </div>
            </div>
        </div>
    </div>
</div>
