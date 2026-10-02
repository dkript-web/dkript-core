<!-- PESTAÑA 4: Personalización y Experiencias de Páginas de Error (Dkript Core) -->
<div id="tab-errors" class="parameter-tab-pane space-y-6 hidden">
    <div class="card-block bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/90 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#00d4ff]/20 to-[#0062f5]/20 text-[#0062f5] border border-[#0062f5]/30 flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Páginas de Error del Sistema</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#00d4ff]/10 text-[#0062f5] border border-[#00d4ff]/30 font-bold font-mono">Personalizables</span>
                    </h3>
                    <p class="text-xs text-slate-500">Configuración modular de textos, videos, imágenes y fallbacks neutros para todos los códigos HTTP de error</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 hidden sm:inline">6 Páginas Soportadas</span>
            </div>
        </div>

        <!-- SELECTOR DE MODO DE COMPOSICIÓN: OPCIÓN A vs OPCIÓN B -->
        @php
            $currentErrorMode = $parameter->error_display_mode ?? 'scene';
            $errorPages = \App\Services\BrandingService::errorPages();
        @endphp
        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="bi bi-layers-fill text-[#0062f5]"></i>
                        <span>Modo de Composición Visual de Errores</span>
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5">Defina cómo se integrará el video MP4/imagen con las animaciones en las pantallas de error del sistema</p>
                </div>
                <span class="text-[11px] font-mono font-bold text-[#0062f5] bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200/60 self-start sm:self-auto">
                    Guardado al pulsar "Guardar Parámetros"
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Tarjeta Opción A -->
                <div id="errorModeCard_scene" 
                     onclick="selectErrorDisplayMode('scene')" 
                     class="relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 {{ $currentErrorMode === 'scene' ? 'border-[#0062f5] bg-blue-50/40 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300' }} flex flex-col justify-between">
                    <input type="radio" name="error_display_mode" value="scene" id="error_mode_scene" class="hidden" {{ $currentErrorMode === 'scene' ? 'checked' : '' }}>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-extrabold bg-[#0062f5]/10 text-[#0062f5] border border-[#0062f5]/20">
                                OPCIÓN A · RECOMENDADA
                            </span>
                            <div id="errorModeCheck_scene" class="{{ $currentErrorMode === 'scene' ? '' : 'hidden' }} w-5 h-5 rounded-full bg-[#0062f5] text-white flex items-center justify-center text-xs shadow-xs">
                                <i class="bi bi-check-lg"></i>
                            </div>
                        </div>
                        <h5 class="text-sm font-black text-slate-900 mb-1 flex items-center gap-1.5">
                            <i class="bi bi-layout-split text-[#0062f5]"></i>
                            <span>Escena Focal con Elementos Superpuestos</span>
                        </h5>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Personaje o recurso situado en la columna focal izquierda con el <strong>video o imagen como capa base</strong> y los <strong>anillos cuánticos, escáner láser SVG y badges flotantes superpuestos</strong> en capas 3D.
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <button type="button" 
                                onclick="event.stopPropagation(); openErrorPreviewModal('404', 'Error 404 · Vista Previa', 'bg-[#00d4ff]/10 text-[#00d4ff] border border-[#00d4ff]/30', '404 · NOT FOUND', 'scene')"
                                class="text-xs font-bold text-[#0062f5] hover:text-[#0052cc] flex items-center gap-1 py-1 px-2.5 rounded-lg hover:bg-blue-100/60 transition-colors">
                            <i class="bi bi-eye-fill"></i>
                            <span>Previsualizar Opción A</span>
                        </button>
                        <span class="text-[11px] text-slate-400 font-mono">Disposición 2 Columnas</span>
                    </div>
                </div>

                <!-- Tarjeta Opción B -->
                <div id="errorModeCard_fullscreen" 
                     onclick="selectErrorDisplayMode('fullscreen')" 
                     class="relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 {{ $currentErrorMode === 'fullscreen' ? 'border-[#0062f5] bg-blue-50/40 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300' }} flex flex-col justify-between">
                    <input type="radio" name="error_display_mode" value="fullscreen" id="error_mode_fullscreen" class="hidden" {{ $currentErrorMode === 'fullscreen' ? 'checked' : '' }}>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-extrabold bg-purple-500/10 text-purple-600 border border-purple-500/20">
                                OPCIÓN B · FULLSCREEN
                            </span>
                            <div id="errorModeCheck_fullscreen" class="{{ $currentErrorMode === 'fullscreen' ? '' : 'hidden' }} w-5 h-5 rounded-full bg-[#0062f5] text-white flex items-center justify-center text-xs shadow-xs">
                                <i class="bi bi-check-lg"></i>
                            </div>
                        </div>
                        <h5 class="text-sm font-black text-slate-900 mb-1 flex items-center gap-1.5">
                            <i class="bi bi-aspect-ratio text-purple-600"></i>
                            <span>Fondo Panorámico Inmersivo Full-Screen</span>
                        </h5>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            El video se proyecta en <strong>toda la pantalla de fondo</strong> con viñetado ambiental oscuro y una <strong>consola de control translúcida centrada al frente</strong> con efectos de luz cósmica.
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <button type="button" 
                                onclick="event.stopPropagation(); openErrorPreviewModal('404', 'Error 404 · Vista Previa', 'bg-[#00d4ff]/10 text-[#00d4ff] border border-[#00d4ff]/30', '404 · NOT FOUND', 'fullscreen')"
                                class="text-xs font-bold text-[#0062f5] hover:text-[#0052cc] flex items-center gap-1 py-1 px-2.5 rounded-lg hover:bg-blue-100/60 transition-colors">
                            <i class="bi bi-eye-fill"></i>
                            <span>Previsualizar Opción B</span>
                        </button>
                        <span class="text-[11px] text-slate-400 font-mono">Consola Centrada Flotante</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- GUÍA TÉCNICA DE UPLOADS Y CASCADA DE FALLBACK -->
        <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs text-slate-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0062f5] flex items-center justify-center text-base flex-shrink-0">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <span class="font-bold text-slate-900">Cascada de Visualización:</span>
                    <span>Video (MP4/WebM &le; 50 MB) &rarr; Imagen (JPG/PNG/WebP &le; 10 MB) &rarr; Fallback Vectorial Neutro HTML/CSS.</span>
                </div>
            </div>
            <span class="text-[11px] font-mono text-[#0062f5] bg-white px-2.5 py-1 rounded-lg border border-blue-200 flex-shrink-0">
                100% Autónomo sin dependencias Dkript
            </span>
        </div>

        <!-- CUADRÍCULA DE TARJETAS DE ERROR PERSONALIZABLES -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach(['404', '403', '500', '419', '429', '503'] as $code)
                @php
                    $page = $errorPages[$code] ?? [];
                    $codeColors = [
                        '404' => ['border' => 'border-[#00d4ff]/40', 'accent' => 'text-[#00d4ff]', 'bg' => 'bg-[#00d4ff]/10', 'btn' => 'bg-[#0062f5] hover:bg-[#0052cc]'],
                        '403' => ['border' => 'border-rose-500/40', 'accent' => 'text-rose-400', 'bg' => 'bg-rose-500/10', 'btn' => 'bg-rose-600 hover:bg-rose-700'],
                        '500' => ['border' => 'border-amber-500/40', 'accent' => 'text-amber-400', 'bg' => 'bg-amber-500/10', 'btn' => 'bg-amber-600 hover:bg-amber-700'],
                        '419' => ['border' => 'border-purple-500/40', 'accent' => 'text-purple-400', 'bg' => 'bg-purple-500/10', 'btn' => 'bg-purple-600 hover:bg-purple-700'],
                        '429' => ['border' => 'border-orange-500/40', 'accent' => 'text-orange-400', 'bg' => 'bg-orange-500/10', 'btn' => 'bg-orange-600 hover:bg-orange-700'],
                        '503' => ['border' => 'border-emerald-500/40', 'accent' => 'text-emerald-400', 'bg' => 'bg-emerald-500/10', 'btn' => 'bg-emerald-600 hover:bg-emerald-700'],
                    ][$code];
                @endphp

                <div id="errorCard_{{ $code }}" class="rounded-3xl p-5 bg-[#071026] border {{ $codeColors['border'] }} shadow-md flex flex-col justify-between space-y-4">
                    <!-- Cabecera de la Tarjeta -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-extrabold {{ $codeColors['bg'] }} {{ $codeColors['accent'] }} border {{ $codeColors['border'] }}">
                                HTTP {{ $code }}
                            </span>
                            <div class="flex items-center gap-1.5">
                                @if($page['has_video'])
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30" title="Video activo">
                                        <i class="bi bi-play-circle-fill"></i> VIDEO
                                    </span>
                                @elseif($page['has_image'])
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30" title="Imagen activa">
                                        <i class="bi bi-image"></i> IMAGEN
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-mono font-bold bg-slate-700/50 text-slate-300 border border-slate-600" title="Fallback vectorial">
                                        <i class="bi bi-vector-pen"></i> NEUTRO
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Miniatura Visual de Previsualización -->
                        <div class="w-full h-32 flex items-center justify-center p-2 mb-3 bg-black rounded-2xl border border-[#0b1739] overflow-hidden relative">
                            @if($page['has_video'])
                                <video autoplay loop muted playsinline class="max-h-full w-auto object-contain filter drop-shadow-md pointer-events-none rounded-xl">
                                    <source src="{{ $page['video_url'] }}" type="video/mp4">
                                </video>
                            @elseif($page['has_image'])
                                <img src="{{ $page['image_url'] }}" alt="{{ $page['title'] }}" class="max-h-full w-auto object-contain rounded-xl">
                            @else
                                <div class="flex flex-col items-center justify-center text-center p-2">
                                    <i class="bi bi-display text-2xl {{ $codeColors['accent'] }} mb-1"></i>
                                    <span class="text-[10px] font-mono text-slate-400 font-bold">Fallback Vectorial Activo</span>
                                    <span class="text-[9px] text-slate-500">Sin multimedia externa</span>
                                </div>
                            @endif
                        </div>

                        <!-- Edición de Textos -->
                        <div class="space-y-2 pt-1">
                            <div>
                                <label class="block text-[10px] font-mono text-slate-400 uppercase font-bold mb-0.5">Título</label>
                                <input type="text" 
                                       id="error_title_{{ $code }}" 
                                       value="{{ $page['title'] }}" 
                                       class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:ring-1 focus:ring-[#00d4ff] focus:border-[#00d4ff] transition-all">
                            </div>

                            <div>
                                <label class="block text-[10px] font-mono text-slate-400 uppercase font-bold mb-0.5">Insignia / Badge</label>
                                <input type="text" 
                                       id="error_badge_{{ $code }}" 
                                       value="{{ $page['badge'] }}" 
                                       class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:ring-1 focus:ring-[#00d4ff] focus:border-[#00d4ff] transition-all">
                            </div>

                            <div>
                                <label class="block text-[10px] font-mono text-slate-400 uppercase font-bold mb-0.5">Mensaje</label>
                                <textarea id="error_message_{{ $code }}" 
                                          rows="2" 
                                          class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-xs text-slate-300 focus:ring-1 focus:ring-[#00d4ff] focus:border-[#00d4ff] transition-all leading-snug">{{ $page['message'] }}</textarea>
                            </div>
                            
                            <button type="button" 
                                    onclick="updateErrorTexts('{{ $code }}')" 
                                    class="w-full py-1.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition-all flex items-center justify-center gap-1.5 border border-slate-700">
                                <i class="bi bi-check2"></i>
                                <span>Guardar Textos</span>
                            </button>
                        </div>
                    </div>

                    <!-- Controles Multimedia y Acciones -->
                    <div class="pt-3 border-t border-[#0b1739] space-y-2">
                        <!-- Botones Multimedia -->
                        <div class="grid grid-cols-2 gap-2">
                            <!-- Video Upload/Replace/Delete -->
                            <div>
                                <input type="file" 
                                       id="error_video_input_{{ $code }}" 
                                       accept="video/mp4,video/webm" 
                                       class="hidden" 
                                       onchange="uploadErrorMedia('{{ $code }}', 'video', this)">
                                
                                @if($page['video'])
                                    <div class="flex items-center gap-1">
                                        <button type="button" 
                                                onclick="document.getElementById('error_video_input_{{ $code }}').click()" 
                                                class="flex-1 py-1 px-1.5 rounded-lg bg-blue-600/30 hover:bg-blue-600/50 text-blue-300 border border-blue-500/40 text-[10px] font-bold transition-all truncate" 
                                                title="Reemplazar Video">
                                            <i class="bi bi-arrow-repeat"></i> Video
                                        </button>
                                        <button type="button" 
                                                onclick="deleteErrorMedia('{{ $code }}', 'video')" 
                                                class="p-1 rounded-lg bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/40 text-[10px] transition-all" 
                                                title="Eliminar Video">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @else
                                    <button type="button" 
                                            onclick="document.getElementById('error_video_input_{{ $code }}').click()" 
                                            class="w-full py-1 px-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[10px] font-bold transition-all flex items-center justify-center gap-1">
                                        <i class="bi bi-upload"></i> + Video
                                    </button>
                                @endif
                            </div>

                            <!-- Image Upload/Replace/Delete -->
                            <div>
                                <input type="file" 
                                       id="error_image_input_{{ $code }}" 
                                       accept="image/png,image/jpeg,image/webp" 
                                       class="hidden" 
                                       onchange="uploadErrorMedia('{{ $code }}', 'image', this)">
                                
                                @if($page['image'])
                                    <div class="flex items-center gap-1">
                                        <button type="button" 
                                                onclick="document.getElementById('error_image_input_{{ $code }}').click()" 
                                                class="flex-1 py-1 px-1.5 rounded-lg bg-blue-600/30 hover:bg-blue-600/50 text-blue-300 border border-blue-500/40 text-[10px] font-bold transition-all truncate" 
                                                title="Reemplazar Imagen">
                                            <i class="bi bi-arrow-repeat"></i> Imagen
                                        </button>
                                        <button type="button" 
                                                onclick="deleteErrorMedia('{{ $code }}', 'image')" 
                                                class="p-1 rounded-lg bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/40 text-[10px] transition-all" 
                                                title="Eliminar Imagen">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @else
                                    <button type="button" 
                                            onclick="document.getElementById('error_image_input_{{ $code }}').click()" 
                                            class="w-full py-1 px-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[10px] font-bold transition-all flex items-center justify-center gap-1">
                                        <i class="bi bi-upload"></i> + Imagen
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Barra de Previsualización -->
                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" 
                                    onclick="openErrorPreviewModal('{{ $code }}', 'Error {{ $code }} · {{ addslashes($page['title']) }}', '{{ $codeColors['bg'] }} {{ $codeColors['accent'] }} border {{ $codeColors['border'] }}', '{{ addslashes($page['badge']) }}')"
                                    class="flex-1 py-2 px-3 rounded-xl {{ $codeColors['btn'] }} text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-md">
                                <i class="bi bi-eye-fill"></i>
                                <span>Previsualizar</span>
                            </button>
                            <a href="{{ route('parameters.errors.preview', $code) }}" target="_blank" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-[#112356] border border-[#112356] transition-colors" title="Abrir en pestaña nueva">
                                <i class="bi bi-box-arrow-up-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

