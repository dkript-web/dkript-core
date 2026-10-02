<!-- Menú Desplegable de Perfil de Usuario (Dropdown Modular) -->
<div class="relative" id="userProfileDropdownContainer">
    <button type="button" 
            id="userProfileMenuButton"
            onclick="toggleUserProfileMenu()"
            class="flex items-center gap-2 sm:gap-2.5 p-1 sm:px-2.5 sm:py-1.5 rounded-2xl hover:bg-slate-100/90 active:bg-slate-200/80 border border-transparent hover:border-[#00D1FF]/40 transition-all focus:outline-none focus:ring-2 focus:ring-[#007BFF]/30 select-none cursor-pointer group min-h-[44px]"
            aria-expanded="false"
            aria-haspopup="true">
        <div class="relative flex-shrink-0 p-[2px] rounded-full bg-gradient-to-br from-[#00D1FF] via-[#007BFF] to-[#7A3CFF] shadow-[0_0_10px_rgba(0,209,255,0.4)] group-hover:shadow-[0_0_16px_rgba(0,209,255,0.7)] transition-all">
            <img id="headerUserAvatarImg"
                 src="{{ auth()->user()->profile?->avatar_url }}" 
                 alt="Avatar" 
                 class="w-8 h-8 sm:w-8.5 sm:h-8.5 rounded-full object-cover bg-[#0A0F1C] transition-all">
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
        </div>
        <span class="text-xs sm:text-sm font-bold text-slate-800 hidden md:inline truncate max-w-[140px] group-hover:text-[#007BFF] transition-colors">
            {{ auth()->user()->profile?->full_name ?: auth()->user()->name }}
        </span>
        <i id="userProfileChevron" class="bi bi-chevron-down text-xs text-slate-400 group-hover:text-[#007BFF] transition-transform duration-200 flex-shrink-0"></i>
    </button>

    <!-- Dropdown Panel Flotante Estilizado con Energía Dkript y Cyber-Glass Traslúcido -->
    <div id="userProfileDropdown" 
         class="hidden absolute right-0 mt-2 w-72 sm:w-80 max-w-[calc(100vw-2rem)] rounded-3xl p-[1.5px] dkript-gradient-border shadow-[0_20px_50px_rgba(10,15,28,0.85),0_0_30px_rgba(0,209,255,0.35),0_0_18px_rgba(122,60,255,0.25)] z-50 transform transition-all duration-200 origin-top-right opacity-0 scale-95 select-none"
         role="menu" 
         aria-orientation="vertical" 
         aria-labelledby="userProfileMenuButton">
        
        <!-- Contenedor Traslúcido Cyber-Glass Interior -->
        <div class="rounded-[22.5px] dkript-cyber-glass p-2 relative overflow-hidden">
            <!-- Auras de Energía Neón Luminosa (Cian-Violeta) -->
            <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full dkript-glow-aura blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -left-10 w-28 h-28 rounded-full bg-gradient-to-tr from-[#7A3CFF]/30 via-[#007BFF]/25 to-transparent blur-xl pointer-events-none"></div>

            <!-- Tarjeta de Identidad de Usuario en Cabecera del Dropdown -->
            <div class="p-3.5 rounded-2xl bg-gradient-to-br from-[#101D35]/85 via-[#0A0F1C]/90 to-[#1F2937]/75 backdrop-blur-md text-white border border-[#00D1FF]/30 shadow-inner mb-1.5 relative overflow-hidden group/card">
                <div class="absolute -right-4 -bottom-4 w-20 h-20 rounded-full bg-gradient-to-br from-[#00D1FF]/20 to-[#7A3CFF]/20 blur-xl pointer-events-none"></div>
                <div class="flex items-center gap-3 relative z-10">
                    <div class="relative flex-shrink-0 p-[2px] rounded-2xl bg-gradient-to-br from-[#00D1FF] via-[#007BFF] to-[#7A3CFF] shadow-[0_0_12px_rgba(0,209,255,0.45)]">
                        <img id="dropdownUserAvatarImg"
                             src="{{ auth()->user()->profile?->avatar_url }}" 
                             alt="Avatar" 
                             class="w-11 h-11 rounded-[14px] object-cover bg-[#0A0F1C]">
                        <span class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-[#0A0F1C]"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-black dkript-text-metal truncate leading-snug tracking-wide">
                            {{ auth()->user()->profile?->full_name ?: auth()->user()->name }}
                        </p>
                        <p class="text-[11px] text-[#BDEFFF]/90 truncate font-mono-code mb-1.5">
                            {{ auth()->user()->email }}
                        </p>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[9px] font-extrabold font-mono-code uppercase tracking-wider {{ auth()->user()->isSuperAdmin() ? 'bg-amber-500/20 text-amber-300 border border-amber-400/50 shadow-xs' : 'bg-gradient-to-r from-[#00D1FF]/20 via-[#007BFF]/20 to-[#7A3CFF]/20 text-[#00D1FF] border border-[#00D1FF]/45 shadow-[0_0_10px_rgba(0,209,255,0.25)]' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->isSuperAdmin() ? 'bg-amber-400' : 'bg-[#00D1FF]' }} animate-ping"></span>
                            <span>{{ auth()->user()->role?->name ?: 'Operador' }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Opciones Interactivas del Dropdown -->
            <div class="space-y-1 py-1 relative z-10">
                <!-- Opción: Perfil de Usuario -->
                <button type="button" 
                        onclick="openUserProfileModal()" 
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-left text-xs font-bold text-slate-200 hover:text-white hover:bg-gradient-to-r hover:from-[#00D1FF]/20 hover:via-[#007BFF]/15 hover:to-transparent border border-transparent hover:border-[#00D1FF]/35 active:bg-[#00D1FF]/25 transition-all group min-h-[44px]">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#00D1FF]/20 to-[#007BFF]/20 border border-[#00D1FF]/40 text-[#00D1FF] group-hover:from-[#00D1FF] group-hover:to-[#007BFF] group-hover:text-[#0A0F1C] group-hover:shadow-[0_0_16px_rgba(0,209,255,0.6)] flex items-center justify-center text-sm transition-all shadow-xs flex-shrink-0">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block leading-tight text-slate-100 group-hover:text-[#BDEFFF] transition-colors">Perfil de Usuario</span>
                        <span class="block text-[10px] text-slate-400 font-normal truncate group-hover:text-slate-200 transition-colors">Ver detalles de la cuenta y permisos</span>
                    </div>
                    <i class="bi bi-chevron-right text-xs text-slate-500 group-hover:text-[#00D1FF] group-hover:translate-x-1 transition-all"></i>
                </button>

                @if(auth()->user()->isSuperAdmin() || in_array(5, session('myoptions', [])))
                    <!-- Opción: Parámetros del Sistema -->
                    <a href="{{ route('parameters.index') }}" 
                       class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-left text-xs font-bold text-slate-200 hover:text-white hover:bg-gradient-to-r hover:from-[#7A3CFF]/20 hover:via-[#007BFF]/15 hover:to-transparent border border-transparent hover:border-[#7A3CFF]/35 active:bg-[#7A3CFF]/25 transition-all group min-h-[44px]">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#7A3CFF]/25 to-[#00D1FF]/20 border border-[#7A3CFF]/40 text-[#BDEFFF] group-hover:from-[#7A3CFF] group-hover:to-[#00D1FF] group-hover:text-white group-hover:shadow-[0_0_16px_rgba(122,60,255,0.6)] flex items-center justify-center text-sm transition-all shadow-xs flex-shrink-0">
                            <i class="bi bi-sliders2"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="block leading-tight text-slate-100 group-hover:text-[#BDEFFF] transition-colors">Parámetros del Sistema</span>
                            <span class="block text-[10px] text-slate-400 font-normal truncate group-hover:text-slate-200 transition-colors">Configuración general y temas</span>
                        </div>
                        <i class="bi bi-chevron-right text-xs text-slate-500 group-hover:text-[#7A3CFF] group-hover:translate-x-1 transition-all"></i>
                    </a>
                @endif
            </div>

            <div class="h-px bg-gradient-to-r from-transparent via-[#00D1FF]/40 via-[#007BFF]/40 to-transparent my-1.5"></div>

            <!-- Opción: Cerrar Sesión -->
            <form method="POST" action="{{ route('logout') }}" id="headerLogoutForm" class="relative z-10">
                @csrf
                <button type="submit" 
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-left text-xs font-bold text-rose-300 hover:text-rose-100 hover:bg-gradient-to-r hover:from-rose-500/20 hover:via-rose-600/15 hover:to-transparent border border-transparent hover:border-rose-500/35 active:bg-rose-500/30 transition-all group min-h-[44px]">
                    <div class="w-8 h-8 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-400 group-hover:bg-rose-600 group-hover:text-white group-hover:shadow-[0_0_16px_rgba(244,63,94,0.5)] flex items-center justify-center text-sm transition-all shadow-xs flex-shrink-0">
                        <i class="bi bi-box-arrow-right"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block leading-tight text-rose-200 group-hover:text-white transition-colors">Cerrar Sesión</span>
                        <span class="block text-[10px] text-rose-400/80 font-normal truncate group-hover:text-rose-300 transition-colors">Finalizar sesión activa con seguridad</span>
                    </div>
                    <i class="bi bi-arrow-right text-xs text-rose-400 group-hover:translate-x-1.5 group-hover:text-rose-200 transition-transform"></i>
                </button>
            </form>
        </div>
    </div>
</div>
