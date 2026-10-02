<!-- MODAL PANORÁMICO DE PREVISUALIZACIÓN DE ERRORES CINEMÁTICOS DRYPT -->
<div id="errorPreviewModal" 
     class="fixed inset-0 z-50 hidden items-center justify-center p-2 sm:p-4 md:p-6 bg-[#030712]/85 backdrop-blur-md transition-all duration-300"
     role="dialog" 
     aria-modal="true">
    <div class="relative w-full max-w-6xl h-[92vh] max-h-[92vh] bg-[#071026] border border-[#112356] rounded-3xl shadow-2xl shadow-blue-950/80 flex flex-col overflow-hidden">
        
        <!-- Barra Superior del Modal: Telemetría, Selector de Dispositivos y Controles -->
        <div class="px-3 sm:px-6 py-3 bg-[#030712]/95 border-b border-[#0b1739] flex items-center justify-between gap-2 sm:gap-4 flex-nowrap flex-shrink-0">
            <!-- Izquierda: Título y Badge del Error -->
            <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-shrink">
                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-[#071026] border border-[#112356] flex items-center justify-center flex-shrink-0 p-1 shadow-inner">
                    <img src="{{ asset('assets/images/branding/drypt-oficial.png') }}" alt="Drypt" class="max-h-full object-contain filter drop-shadow-[0_0_8px_rgba(0,212,255,0.4)]">
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <h3 id="errorPreviewTitle" class="text-xs sm:text-sm md:text-base font-extrabold text-white truncate">Vista Previa</h3>
                        <span id="errorPreviewBadge" class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold bg-blue-500/10 text-blue-400 border border-blue-500/30">
                            HTTP PREVIEW
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 items-center gap-1.5 hidden md:flex">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Simulador de Entorno HTTP · Drypt v5.0</span>
                    </p>
                </div>
            </div>

            <!-- Centro: Selector de Modo y Conmutador de Dispositivos -->
            <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                <!-- Selector de Modo de Composición: Opción A vs Opción B -->
                <div class="flex items-center bg-[#071026] p-0.5 sm:p-1 rounded-xl border border-[#112356] shadow-inner text-xs font-semibold">
                    <button type="button" 
                            id="previewModeBtnScene"
                            onclick="setPreviewErrorMode('scene')"
                            class="px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg flex items-center gap-1 sm:gap-1.5 transition-all text-white bg-[#0062f5] shadow-sm text-[11px] sm:text-xs"
                            title="Ver en Opción A: Escena Focal con Elementos Superpuestos">
                        <i class="bi bi-layout-split"></i>
                        <span class="hidden sm:inline">Opción A (Focal)</span>
                    </button>
                    <button type="button" 
                            id="previewModeBtnFullscreen"
                            onclick="setPreviewErrorMode('fullscreen')"
                            class="px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg flex items-center gap-1 sm:gap-1.5 transition-all text-slate-400 hover:text-white hover:bg-[#112356] text-[11px] sm:text-xs"
                            title="Ver en Opción B: Fondo Panorámico Inmersivo Full-Screen">
                        <i class="bi bi-aspect-ratio"></i>
                        <span class="hidden sm:inline">Opción B (Full-Screen)</span>
                    </button>
                </div>

                <!-- Conmutador de Dispositivos (Desktop, Tablet, Mobile) -->
                <div class="hidden lg:flex items-center bg-[#071026] p-1 rounded-xl border border-[#112356] shadow-inner text-xs font-semibold">
                    <button type="button" 
                            id="previewBtnDesktop"
                            onclick="setPreviewDevice('desktop')"
                            class="px-2.5 py-1 rounded-lg flex items-center gap-1.5 transition-all text-white bg-[#0062f5] shadow-sm"
                            title="Vista Desktop">
                        <i class="bi bi-display"></i>
                        <span class="hidden xl:inline">Desktop</span>
                    </button>
                    <button type="button" 
                            id="previewBtnTablet"
                            onclick="setPreviewDevice('tablet')"
                            class="px-2.5 py-1 rounded-lg flex items-center gap-1.5 transition-all text-slate-400 hover:text-white hover:bg-[#112356]"
                            title="Vista Tablet (768px)">
                        <i class="bi bi-tablet"></i>
                        <span class="hidden xl:inline">Tablet</span>
                    </button>
                    <button type="button" 
                            id="previewBtnMobile"
                            onclick="setPreviewDevice('mobile')"
                            class="px-2.5 py-1 rounded-lg flex items-center gap-1.5 transition-all text-slate-400 hover:text-white hover:bg-[#112356]"
                            title="Vista Móvil (390px)">
                        <i class="bi bi-phone"></i>
                        <span class="hidden xl:inline">Móvil</span>
                    </button>
                </div>
            </div>

            <!-- Derecha: Abrir en pestaña externa y Botón X Fijo y Destacado -->
            <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0 ml-auto">
                <a id="errorPreviewNewTabBtn" 
                   href="#" 
                   target="_blank" 
                   class="px-2 sm:px-3 py-1.5 rounded-xl bg-[#071026] hover:bg-[#112356] text-slate-300 hover:text-white border border-[#112356] text-xs font-semibold transition-all flex items-center gap-1.5"
                   title="Abrir página completa en una pestaña nueva">
                    <i class="bi bi-box-arrow-up-right text-xs"></i>
                    <span class="hidden sm:inline">Pestaña</span>
                </a>
                <button type="button" 
                        onclick="closeErrorPreviewModal()"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-rose-500/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/40 flex items-center justify-center transition-all flex-shrink-0 shadow-md shadow-rose-950/40 cursor-pointer"
                        title="Cerrar modal (Esc)">
                    <i class="bi bi-x-lg text-xs sm:text-sm font-bold"></i>
                </button>
            </div>
        </div>

        <!-- Cuerpo del Modal: Marco de Emulación con Iframe -->
        <div class="flex-1 w-full bg-black relative overflow-hidden flex items-center justify-center p-2 sm:p-4">
            <div id="errorPreviewFrameWrapper" 
                 class="h-full w-full max-w-full transition-all duration-300 rounded-2xl overflow-hidden shadow-2xl border border-slate-800/80 bg-black flex flex-col">
                <iframe id="errorPreviewIframe" 
                        src="" 
                        class="w-full h-full border-0 bg-transparent" 
                        title="Previsualización de Error"></iframe>
            </div>
        </div>

        <!-- Barra Inferior de Telemetría -->
        <div class="px-4 sm:px-6 py-2 bg-[#030712]/95 border-t border-[#0b1739] flex items-center justify-between text-[11px] text-slate-400 flex-shrink-0">
            <div class="flex items-center gap-2">
                <i class="bi bi-cpu text-[#00d4ff]"></i>
                <span id="previewResIndicator" class="font-mono text-slate-300">100% Pantalla Completa (Desktop)</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-slate-500">Presiona <kbd class="px-1.5 py-0.5 text-[10px] bg-[#112356] rounded text-slate-300 font-mono">ESC</kbd> para salir</span>
            </div>
        </div>

    </div>
</div>
