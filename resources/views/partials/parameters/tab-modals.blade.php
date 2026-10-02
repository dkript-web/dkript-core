<!-- PESTAÑA 2: Estilo Visual de Ventanas Modales -->
<div id="tab-modals" class="parameter-tab-pane space-y-6 hidden">
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 flex-wrap gap-3">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 border border-purple-500/20 flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                    <i class="bi bi-window-stack"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Estilo Visual de Ventanas Modales</h3>
                    <p class="text-xs text-slate-500">Seleccione la apariencia de los cuadros de diálogo, alertas y modales del sistema</p>
                </div>
            </div>
            <span class="text-[11px] font-bold text-slate-600 bg-slate-100 px-3.5 py-1.5 rounded-full border border-slate-200">
                4 Estilos Disponibles
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $currentModalStyle = old('modal_style', $parameter->modal_style ?? 'corporate');
                $modalStyles = [
                    [
                        'id' => 'corporate',
                        'name' => 'Dkript Executive',
                        'badge' => 'Oficial',
                        'badge_color' => 'bg-blue-100 text-[#0062f5] border-blue-200',
                        'icon' => 'bi-shield-check',
                        'icon_color' => 'text-[#0062f5] bg-[#0062f5]/10 border-[#0062f5]/20',
                        'desc' => 'Fondo blanco inmaculado, bordes suaves y acento azul cobalto ejecutivo.',
                    ],
                    [
                        'id' => 'glassmorphism',
                        'name' => 'Drypt Cyber-Glass',
                        'badge' => 'Traslúcido',
                        'badge_color' => 'bg-cyan-500/20 text-cyan-700 border-cyan-400/30',
                        'icon' => 'bi-stars',
                        'icon_color' => 'text-cyan-500 bg-cyan-500/10 border-cyan-500/30',
                        'desc' => 'Cristal esmerilado translúcido con resplandor neón cyan y sombras mágicas.',
                    ],
                    [
                        'id' => 'window',
                        'name' => 'Classic Window',
                        'badge' => 'Desktop OS',
                        'badge_color' => 'bg-slate-200 text-slate-700 border-slate-300',
                        'icon' => 'bi-window-desktop',
                        'icon_color' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
                        'desc' => 'Ventana de software de escritorio con barra superior y semáforo de controles.',
                    ],
                    [
                        'id' => 'minimal',
                        'name' => 'Neumorphic Clean',
                        'badge' => 'Táctil Soft',
                        'badge_color' => 'bg-purple-100 text-purple-700 border-purple-200',
                        'icon' => 'bi-layers-half',
                        'icon_color' => 'text-purple-600 bg-purple-500/10 border-purple-500/20',
                        'desc' => 'Relieve suave táctil con sombras bicromáticas extruidas y estética zen.',
                    ],
                ];
            @endphp

            @foreach($modalStyles as $mStyle)
                <div class="relative group rounded-2xl border-2 transition-all duration-300 p-4 flex flex-col justify-between cursor-pointer modal-style-card {{ $currentModalStyle === $mStyle['id'] ? 'border-[#0062f5] bg-blue-50/20 ring-2 ring-[#0062f5]/20 shadow-md' : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm' }}"
                     onclick="selectModalStyle('{{ $mStyle['id'] }}', this)">
                    
                    <!-- Input Radio Oculto -->
                    <input type="radio" 
                           name="modal_style" 
                           value="{{ $mStyle['id'] }}" 
                           id="modal_style_{{ $mStyle['id'] }}" 
                           class="sr-only" 
                           {{ $currentModalStyle === $mStyle['id'] ? 'checked' : '' }}>

                    <!-- Encabezado de la Tarjeta con Icono y Badge -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg border {{ $mStyle['icon_color'] }} shadow-xs">
                                <i class="bi {{ $mStyle['icon'] }}"></i>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border {{ $mStyle['badge_color'] }}">
                                {{ $mStyle['badge'] }}
                            </span>
                        </div>

                        <h5 class="text-xs font-black text-slate-900 mb-1 tracking-tight">{{ $mStyle['name'] }}</h5>
                        <p class="text-[11px] text-slate-500 leading-relaxed">{{ $mStyle['desc'] }}</p>
                    </div>

                    <!-- Botón de Previsualización y Check Activo -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <button type="button" 
                                onclick="event.stopPropagation(); DkriptModal.preview('{{ $mStyle['id'] }}')" 
                                class="text-[11px] font-bold text-[#0062f5] hover:text-[#0051cc] flex items-center gap-1 py-1 px-2 rounded-lg hover:bg-blue-50 transition-colors"
                                title="Ver una muestra de este modal en pantalla">
                            <i class="bi bi-eye-fill"></i>
                            <span>Previsualizar</span>
                        </button>

                        <div class="active-check-icon {{ $currentModalStyle === $mStyle['id'] ? '' : 'hidden' }} w-6 h-6 rounded-full bg-[#0062f5] text-white flex items-center justify-center text-xs shadow-sm">
                            <i class="bi bi-check-lg"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
