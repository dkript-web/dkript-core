<!-- Sidebar Responsivo Modular con Paleta Oscuro Tecnológico Dkript, Borde Neón y Acentos Luminosos -->
<aside id="mainSidebar" 
       class="bg-gradient-to-b from-[#0A0F1C] via-[#0d172e] to-[#0A0F1C] text-slate-200 flex flex-col flex-shrink-0 relative shadow-2xl overflow-hidden border-r border-[#00D1FF]/30 h-screen max-h-screen sticky top-0">
    
    <!-- Borde Perimetral Vertical Dinámico con Flujo de Energía Dkript -->
    <div class="absolute top-0 right-0 w-[2.5px] h-full dkript-gradient-border z-30 shadow-[0_0_15px_rgba(0,209,255,0.8),0_0_30px_rgba(122,60,255,0.6)]"></div>

    <!-- Auras Atmosféricas Neón de Fondo (Cian-Violeta) -->
    <div class="absolute -top-16 -left-16 w-48 h-48 rounded-full dkript-glow-aura blur-3xl pointer-events-none opacity-85"></div>
    <div class="absolute top-1/2 -right-16 w-44 h-44 rounded-full bg-gradient-to-tr from-[#7A3CFF]/30 via-[#007BFF]/25 to-transparent blur-3xl pointer-events-none animate-pulse"></div>
    <div class="absolute -bottom-10 -left-10 w-40 h-40 rounded-full bg-gradient-to-br from-[#00D1FF]/25 to-[#7A3CFF]/20 blur-2xl pointer-events-none"></div>

    <!-- Brand Header Oficial (Soporta modos show_brand_text: true y false) -->
    <div id="sidebarBrandHeader" class="{{ ($globalSystemParameter->show_brand_text ?? true) 
        ? 'h-16 flex items-center justify-between px-5 bg-[#030712] border-b border-[#0b1739] relative overflow-hidden transition-all duration-300 flex-shrink-0' 
        : 'min-h-[5.5rem] sm:min-h-[6rem] py-3.5 px-4 flex items-center justify-between bg-[#030712] border-b border-[#0b1739] relative overflow-hidden transition-all duration-300 flex-shrink-0' }}">
        <!-- Sutil resplandor de acento Drypt -->
        <div class="absolute -right-6 -top-6 w-20 h-20 bg-[#00d4ff]/10 rounded-full blur-xl pointer-events-none"></div>

        <a href="{{ route('dashboard') }}" 
           id="sidebarBrandLink" 
           class="flex items-center {{ ($globalSystemParameter->show_brand_text ?? true) ? 'gap-3 mr-2' : 'w-full mr-1' }} group flex-1 min-w-0 h-full">
            @if(!empty($globalSystemParameter->system_logo))
                <img id="sidebarSystemLogo" 
                     src="{{ asset($globalSystemParameter->system_logo) }}" 
                     alt="{{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}" 
                     class="{{ ($globalSystemParameter->show_brand_text ?? true) 
                        ? 'w-9 h-9 object-contain rounded-xl p-0.5 bg-[#071026] border border-[#112356] shadow-md shadow-blue-900/30 group-hover:border-[#00d4ff] group-hover:scale-105 transition-all flex-shrink-0' 
                        : 'w-full max-w-full h-auto max-h-20 sm:max-h-22 object-contain object-left rounded-lg transition-all duration-300 group-hover:scale-[1.02]' }}">
            @else
                <div id="sidebarSystemLogo" 
                     class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm bg-gradient-to-br from-[#0062f5] to-[#00d4ff] text-white shadow-md shadow-blue-900/30 border border-[#00d4ff]/40 flex-shrink-0 group-hover:scale-105 transition-all">
                    <span>{{ mb_strtoupper(mb_substr($globalSystemParameter->system_name ?? config('app.name', 'Dkript Core'), 0, 1)) }}</span>
                </div>
            @endif
            <div id="sidebarBrandTextContainer" class="{{ ($globalSystemParameter->show_brand_text ?? true) ? '' : 'hidden' }}">
                <h1 id="sidebarSystemName" class="text-white font-extrabold text-base leading-tight tracking-wide group-hover:text-[#00d4ff] transition-colors truncate max-w-[150px]">
                    {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}
                </h1>
                <span class="text-[11px] text-[#00d4ff] font-semibold tracking-wider uppercase flex items-center gap-1.5">
                    <span>{{ config('dkript.edition', 'Core') }}</span>
                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-[#0b1739] text-[#00d4ff] border border-[#00d4ff]/30 font-mono">v{{ config('dkript.version', '1.0') }}</span>
                </span>
            </div>
        </a>

        <!-- Botón Cerrar en Móvil y Tablets -->
        <button type="button" 
                id="sidebarCloseBtn"
                onclick="toggleMobileSidebar()" 
                class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-[#0b1739] transition-colors flex-shrink-0 ml-1" 
                aria-label="Cerrar menú">
            <i class="bi bi-x-lg text-lg"></i>
        </button>
    </div>

    <!-- Menú de Opciones con Cajas de Íconos Neón y Scroll Interno Fluido -->
    <nav class="flex-1 min-h-0 px-3 py-4 space-y-2 overflow-y-auto overscroll-contain relative z-10 custom-scrollbar">
        @php
            $myOptions = session('myoptions', []);
            $activeMenuItems = \App\Models\MenuOption::where('status', 1)->orderBy('order', 'asc')->get();
        @endphp

        <div class="px-3 py-2 text-[11px] font-bold text-[#BDEFFF]/80 uppercase tracking-wider flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-[#00D1FF] shadow-[0_0_8px_#00D1FF] animate-pulse"></span>
            <span>Navegación Principal</span>
        </div>

        @foreach($activeMenuItems as $item)
            @if(auth()->user()->isSuperAdmin() || in_array($item->id, $myOptions))
                @php
                    $isActive = request()->routeIs($item->route_name . '*');
                @endphp
                <a href="{{ route($item->route_name) }}" 
                   class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-xs font-bold transition-all min-h-[48px] group relative select-none {{ $isActive 
                        ? 'bg-gradient-to-r from-[#00D1FF]/20 via-[#007BFF]/25 to-[#7A3CFF]/20 border border-[#00D1FF]/60 shadow-[0_4px_20px_rgba(0,123,255,0.4),0_0_15px_rgba(0,209,255,0.3)] text-white' 
                        : 'text-slate-300 hover:text-white hover:bg-gradient-to-r hover:from-[#00D1FF]/18 hover:via-[#007BFF]/12 hover:to-transparent border border-transparent hover:border-[#00D1FF]/35 active:bg-[#00D1FF]/25' }}">
                    
                    <!-- Caja de Ícono con Estilo Neón Idéntico al Dropdown -->
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm transition-all shadow-xs flex-shrink-0 {{ $isActive 
                        ? 'bg-gradient-to-br from-[#00D1FF] to-[#007BFF] text-[#0A0F1C] shadow-[0_0_14px_rgba(0,209,255,0.7)] font-bold' 
                        : 'bg-gradient-to-br from-[#00D1FF]/20 to-[#007BFF]/20 border border-[#00D1FF]/40 text-[#00D1FF] group-hover:from-[#00D1FF] group-hover:to-[#007BFF] group-hover:text-[#0A0F1C] group-hover:shadow-[0_0_16px_rgba(0,209,255,0.6)]' }}">
                        <i class="bi {{ $item->icon }}"></i>
                    </div>

                    <!-- Textos del Módulo -->
                    <div class="flex-1 min-w-0">
                        <span class="block leading-tight truncate {{ $isActive ? 'text-white font-extrabold tracking-wide' : 'text-slate-200 group-hover:text-[#BDEFFF] transition-colors font-bold' }}">
                            {{ $item->name }}
                        </span>
                        <span class="block text-[10px] truncate {{ $isActive ? 'text-[#BDEFFF] font-medium' : 'text-slate-400 group-hover:text-slate-200 transition-colors' }}">
                            {{ $isActive ? 'Módulo Activo' : 'Acceso al Módulo' }}
                        </span>
                    </div>

                    <!-- Indicador de Navegación -->
                    @if($isActive)
                        <span class="flex items-center gap-1 flex-shrink-0">
                            <span class="w-2 h-2 rounded-full bg-[#00D1FF] shadow-[0_0_8px_#00D1FF] animate-ping"></span>
                        </span>
                    @else
                        <i class="bi bi-chevron-right text-xs text-slate-500 group-hover:text-[#00D1FF] group-hover:translate-x-0.5 transition-all flex-shrink-0"></i>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>

    <!-- User Footer con Tarjeta Holográfica Fijo Permanentemente al Fondo Izquierdo del Viewport -->
    <div id="sidebarUserFooter" class="p-3 border-t border-[#00D1FF]/30 bg-gradient-to-br from-[#101D35]/95 via-[#0A0F1C]/95 to-[#101D35]/90 backdrop-blur-md relative z-20 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="relative flex-shrink-0 p-[2px] rounded-2xl bg-gradient-to-br from-[#00D1FF] via-[#007BFF] to-[#7A3CFF] shadow-[0_0_12px_rgba(0,209,255,0.45)]">
                <img src="{{ auth()->user()->profile?->avatar_url }}" 
                     alt="Avatar" 
                     class="w-10 h-10 rounded-[14px] object-cover bg-[#0A0F1C]">
                <span class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-[#0A0F1C]"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-black dkript-text-metal truncate leading-snug">
                    {{ auth()->user()->profile?->full_name ?: auth()->user()->name }}
                </p>
                <p class="text-[11px] text-[#BDEFFF]/90 truncate font-mono-code mb-1">
                    {{ auth()->user()->email }}
                </p>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-extrabold font-mono-code uppercase tracking-wider {{ auth()->user()->isSuperAdmin() ? 'bg-amber-500/20 text-amber-300 border border-amber-400/50 shadow-xs' : 'bg-gradient-to-r from-[#00D1FF]/20 via-[#007BFF]/20 to-[#7A3CFF]/20 text-[#00D1FF] border border-[#00D1FF]/45 shadow-[0_0_10px_rgba(0,209,255,0.25)]' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->isSuperAdmin() ? 'bg-amber-400' : 'bg-[#00D1FF]' }} animate-ping"></span>
                    <span>{{ auth()->user()->role?->name ?: 'Operador' }}</span>
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                @csrf
                <button type="submit" title="Cerrar Sesión" class="w-9 h-9 flex items-center justify-center text-rose-400 hover:text-white bg-rose-500/10 hover:bg-rose-600 border border-rose-500/30 rounded-xl transition-all shadow-xs group">
                    <i class="bi bi-box-arrow-right text-base group-hover:translate-x-0.5 transition-transform"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
