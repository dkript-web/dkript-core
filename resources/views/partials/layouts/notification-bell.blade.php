<!-- Centro de Notificaciones In-App (Dropdown Modular con Cyber-Glass) -->
<div class="relative" id="notificationDropdownContainer">
    <!-- Botón Campana Disparador -->
    <button type="button" 
            id="notificationBellBtn" 
            onclick="DkriptNotification.toggleDropdown()"
            class="relative flex items-center justify-center w-10 h-10 rounded-2xl bg-white/80 hover:bg-slate-100 border border-slate-200/80 hover:border-[#00D1FF]/40 text-slate-600 hover:text-[#007BFF] transition-all focus:outline-none focus:ring-2 focus:ring-[#007BFF]/30 select-none cursor-pointer group shadow-xs min-h-[40px] min-w-[40px]"
            aria-label="Abrir centro de notificaciones"
            aria-expanded="false"
            aria-haspopup="true">
        <i id="notificationBellIcon" class="bi bi-bell text-lg text-slate-600 group-hover:text-[#007BFF] group-hover:scale-110 transition-transform"></i>
        
        <!-- Indicador de Notificaciones No Leídas (Pulsante Neón) -->
        <span id="notificationBadge" 
              class="hidden absolute -top-1 -right-1 min-w-[19px] h-[19px] px-1 rounded-full bg-gradient-to-r from-rose-500 to-red-600 text-white font-mono-code text-[10px] font-black flex items-center justify-center shadow-[0_0_10px_rgba(244,63,94,0.6)] border-2 border-white leading-none">
            0
        </span>
    </button>

    <!-- Panel Desplegable Cyber-Glass con Borde de Energía Dkript -->
    <div id="notificationDropdown" 
         class="hidden absolute right-0 mt-2 w-80 sm:w-96 max-w-[calc(100vw-1.5rem)] rounded-3xl p-[1.5px] dkript-gradient-border shadow-[0_20px_50px_rgba(10,15,28,0.85),0_0_30px_rgba(0,209,255,0.35),0_0_18px_rgba(122,60,255,0.25)] z-50 transform transition-all duration-200 origin-top-right opacity-0 scale-95 select-none"
         role="menu"
         aria-labelledby="notificationBellBtn">
        
        <!-- Contenedor Traslúcido Cyber-Glass Interior -->
        <div class="rounded-[22.5px] dkript-cyber-glass p-3 relative overflow-hidden flex flex-col max-h-[85vh]">
            <!-- Auras de Energía Neón Luminosa (Cian-Violeta) -->
            <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full dkript-glow-aura blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -left-10 w-28 h-28 rounded-full bg-gradient-to-tr from-[#7A3CFF]/30 via-[#007BFF]/25 to-transparent blur-xl pointer-events-none"></div>

            <!-- Cabecera del Centro de Notificaciones -->
            <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-[#00D1FF]/20 relative z-10">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#00D1FF]/20 to-[#007BFF]/20 border border-[#00D1FF]/40 text-[#00D1FF] flex items-center justify-center text-sm shadow-[0_0_10px_rgba(0,209,255,0.3)]">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black dkript-text-metal tracking-wide">Notificaciones</h4>
                        <span id="notificationDropdownSubtitle" class="text-[10px] text-slate-400 font-mono-code">0 pendientes</span>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <button type="button" 
                            id="btnMarkAllRead"
                            onclick="DkriptNotification.markAllAsRead()" 
                            class="px-2 py-1 rounded-lg text-[10px] font-bold text-[#00D1FF] hover:text-white hover:bg-[#00D1FF]/20 transition-all border border-transparent hover:border-[#00D1FF]/30 flex items-center gap-1 cursor-pointer"
                            title="Marcar todas como leídas">
                        <i class="bi bi-check2-all text-xs"></i>
                        <span class="hidden sm:inline">Leídas</span>
                    </button>
                    <button type="button" 
                            onclick="DkriptNotification.closeDropdown()" 
                            class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                            aria-label="Cerrar">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Lista Scrollable de Notificaciones -->
            <div id="notificationList" class="flex-1 overflow-y-auto space-y-1.5 pr-1 relative z-10 max-h-[360px] custom-catalog-scrollbar">
                <!-- Se llena dinámicamente mediante DkriptNotification.render() -->
                <div id="notificationLoadingState" class="py-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2">
                    <div class="w-6 h-6 border-2 border-[#00D1FF] border-t-transparent rounded-full animate-spin"></div>
                    <span class="font-mono-code text-[11px]">Sincronizando notificaciones...</span>
                </div>
            </div>

            <!-- Pie del Desplegable (Acciones Generales) -->
            <div class="pt-2.5 mt-2 border-t border-[#00D1FF]/20 flex items-center justify-between relative z-10 text-[11px]">
                <button type="button" 
                        onclick="DkriptNotification.clearAll()" 
                        class="text-rose-400 hover:text-rose-300 transition-colors flex items-center gap-1 font-bold text-[10px] hover:underline cursor-pointer">
                    <i class="bi bi-trash3 text-xs"></i>
                    <span>Vaciar</span>
                </button>

                <a href="{{ route('notifications.index') }}" 
                   class="text-[#00D1FF] hover:text-white font-bold flex items-center gap-1 group/link">
                    <span>Ver todas</span>
                    <i class="bi bi-arrow-right text-xs group-hover/link:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
    </div>
</div>
