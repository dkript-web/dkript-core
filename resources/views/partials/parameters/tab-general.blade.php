<!-- PESTAÑA 1: Parámetros Globales del Sistema & Sesiones -->
<div id="tab-general" class="parameter-tab-pane space-y-6">
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center gap-4 pb-6 mb-6 border-b border-slate-100">
            <div class="icon-squircle-cobalt w-12 h-12 text-2xl flex-shrink-0">
                <i class="bi bi-sliders"></i>
            </div>
            <div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Parámetros Globales del Sistema</h3>
                <p class="text-xs text-slate-500">Ajuste la identidad y configuraciones operativas de la plataforma</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre del Sistema -->
            <div>
                <label for="system_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Nombre del Sistema / Empresa <span class="text-rose-500">*</span>
                </label>
                <input type="text" 
                       name="system_name" 
                       id="system_name" 
                       required 
                       value="{{ old('system_name', $parameter->system_name) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium text-slate-800">
            </div>

            <!-- Correo Electrónico de Contacto -->
            <div>
                <label for="contact_email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Correo Electrónico de Contacto / Soporte
                </label>
                <input type="email" 
                       name="contact_email" 
                       id="contact_email" 
                       value="{{ old('contact_email', $parameter->contact_email) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium text-slate-800">
            </div>

            <!-- Registros por Página -->
            <div>
                <label for="records_per_page" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Registros por Página por Defecto <span class="text-rose-500">*</span>
                </label>
                <select name="records_per_page" 
                        id="records_per_page" 
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white font-medium text-slate-800">
                    @foreach([5, 10, 15, 20, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ old('records_per_page', $parameter->records_per_page) == $n ? 'selected' : '' }}>
                            {{ $n }} registros
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tiempo de Inactividad de Sesión -->
            <div>
                <label for="session_timeout_minutes" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Inactividad de Sesión (Minutos) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" 
                           name="session_timeout_minutes" 
                           id="session_timeout_minutes" 
                           min="1" 
                           max="1440" 
                           value="{{ old('session_timeout_minutes', $parameter->session_timeout_minutes ?? 15) }}"
                           class="w-full pl-4 pr-12 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium text-slate-800"
                           required>
                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">min</span>
                </div>
            </div>

            <!-- Modo Mantenimiento con iOS Toggle Switch -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Modo Mantenimiento
                </label>
                <div class="flex items-center gap-4 mt-2">
                    <label class="role-toggle-switch">
                        <input type="hidden" name="maintenance_mode" value="0">
                        <input type="checkbox" 
                               name="maintenance_mode" 
                               id="maintenance_mode" 
                               value="1" 
                               {{ old('maintenance_mode', $parameter->maintenance_mode) == 1 ? 'checked' : '' }}
                               onchange="updateMaintenanceBadge(this)">
                        <span class="role-toggle-track">
                            <span class="role-toggle-thumb"></span>
                        </span>
                    </label>
                    <span id="maintenanceBadge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $parameter->maintenance_mode == 1 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-600 border border-slate-300' }}">
                        {{ $parameter->maintenance_mode == 1 ? 'EN MANTENIMIENTO' : 'OPERATIVO NORMAL' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
