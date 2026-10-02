<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-timeout" content="{{ ($globalSystemParameter->session_timeout_minutes ?? 15) * 60 }}">
    <title>@yield('title', 'Admin Base') - {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</title>
 
     <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 
     <!-- Tailwind CSS CDN & Dkript Theme Config -->
     <script src="https://cdn.tailwindcss.com"></script>
     <script>
         tailwind.config = {
             theme: {
                 extend: {
                     colors: {
                         dknavy: {
                             950: '#030712',
                             900: '#071026',
                             850: '#091432',
                             800: '#0b1739',
                             700: '#112356',
                             600: '#173682',
                         },
                         dkblue: {
                             primary: '#0062f5',
                             hover: '#004ecc',
                         },
                         dkcyan: {
                             glow: '#00d4ff',
                             subtle: 'rgba(0, 212, 255, 0.12)',
                         },
                         dkviolet: '#7928ca',
                     }
                 }
             }
         }
     </script>
 
     <!-- Bootstrap Icons -->
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 
     <!-- D3.js para RosenCharts -->
     <script src="https://d3js.org/d3.v7.min.js"></script>
 
     <!-- Estilos de RosenCharts y Custom Dkript -->
     <link rel="stylesheet" href="{{ asset('assets/css/rosen-charts.css') }}">
     <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
 
     @stack('styles')
     @php
         $globalSystemParameter = \App\Models\Parameter::getSystemSettings();
     @endphp
 </head>
 <body data-modal-style="{{ $globalSystemParameter->modal_style ?? 'corporate' }}" class="bg-[#f0f4f9] text-slate-800 font-sans antialiased min-h-screen flex flex-col safe-area-body">
 
     <div class="flex flex-1 min-h-screen relative overflow-x-clip lg:overflow-x-visible">
        <!-- Backdrop para Móviles y Tablets -->
        <div id="sidebarBackdrop" 
             onclick="toggleMobileSidebar()" 
             class="fixed inset-0 bg-[#0A0F1C]/75 backdrop-blur-sm z-40 transition-opacity duration-300"></div>

        <!-- Sidebar Responsivo Modular Dkript Inc. -->
        @include('partials.layouts.sidebar')

        <!-- Contenido Principal -->
        <div class="flex-1 flex flex-col min-w-0 w-full">
            <!-- Navbar Superior Responsivo -->
            <header class="h-16 bg-white/95 backdrop-blur-md border-b border-slate-200/80 flex items-center justify-between px-4 sm:px-6 lg:px-8 sticky top-0 z-30 shadow-sm shadow-slate-900/5">
                <!-- Izquierda: Botón Hamburguesa Móvil/Tablet + Título -->
                <div class="flex items-center gap-3 min-w-0 mr-2 sm:mr-4">
                    <button type="button" 
                            id="btnToggleSidebar"
                            onclick="toggleMobileSidebar()" 
                            class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-[#0062f5]/20 flex-shrink-0" 
                            aria-label="Abrir Menú">
                        <i class="bi bi-list text-2xl leading-none"></i>
                    </button>

                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-[#0062f5] hidden sm:inline-block shadow-sm shadow-blue-500/50 flex-shrink-0"></span>
                        <h2 class="text-base sm:text-lg md:text-xl font-extrabold text-slate-900 tracking-tight truncate">
                            @yield('page_title', 'Administración')
                        </h2>
                    </div>
                </div>

                <!-- Derecha: Rol y Perfil -->
                <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                    <!-- Badge de Rol Activo con Estilo Corporativo (Oculto en Móvil, Visible en Tablet y Desktop) -->
                    <span class="hidden md:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ auth()->user()->isSuperAdmin() ? 'bg-gradient-to-r from-amber-500/15 to-orange-500/15 text-amber-900 border border-amber-300 shadow-sm' : 'bg-gradient-to-r from-[#0062f5]/15 to-[#00d4ff]/15 text-[#0062f5] border border-[#00d4ff]/40 shadow-sm' }} max-w-[170px]">
                        <i class="bi {{ auth()->user()->isSuperAdmin() ? 'bi-shield-check text-amber-600' : 'bi-person-check text-[#0062f5]' }} flex-shrink-0"></i>
                        <span class="truncate">{{ auth()->user()->role?->name ?: 'Operador' }}</span>
                    </span>

                    <div class="h-6 w-px bg-slate-200 hidden md:block"></div>

                    <!-- Centro de Notificaciones In-App (Dropdown Modular) -->
                    @include('partials.layouts.notification-bell')

                    <!-- Menú Desplegable de Perfil de Usuario (Dropdown Modular) -->
                    @include('partials.layouts.user-profile-dropdown')
                </div>
            </header>



            <!-- Contenido de la Página Adaptativo -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 w-full max-w-full overflow-x-hidden">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Contenedor Global de Toasts / Banners Notificaciones (Esquina Superior Derecha - Vista del Usuario) -->
    <div id="dkriptToastContainer" 
         class="fixed top-6 right-6 z-[99999] flex flex-col gap-3 pointer-events-none max-w-sm sm:max-w-md w-full"
         aria-live="polite" 
         aria-atomic="true">
    </div>

    <!-- Modal Universal del Sistema Dkript (Reemplaza los alerts nativos del navegador con 4 estilos dinámicos) -->
    <div id="globalSystemModal" 
         data-default-style="{{ $globalSystemParameter->modal_style ?? 'corporate' }}"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none"
         role="dialog" 
         aria-modal="true">
        <div id="globalSystemModalCard" 
             class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 select-none">
            
            <!-- Barra Superior estilo Ventana OS (Solo visible para estilo 'window') -->
            <div id="globalSystemModalWindowBar" class="hidden flex items-center justify-between px-4 py-2.5 bg-[#071026] border-b border-[#1e293b] select-none">
                <div class="flex items-center gap-2">
                    <button type="button" id="windowCloseDot" class="w-3 h-3 rounded-full bg-[#ff5f56] hover:brightness-110 transition-all focus:outline-none" title="Cerrar"></button>
                    <span class="w-3 h-3 rounded-full bg-[#ffbd2e]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#27c93f]"></span>
                </div>
                <div class="flex items-center gap-1.5 text-[11px] font-mono font-bold text-slate-400 tracking-wider">
                    <i class="bi bi-terminal text-cyan-400"></i>
                    <span id="globalSystemModalWindowTitle">system.modal</span>
                </div>
                <div class="w-12 text-right">
                    <span class="text-[10px] font-mono text-slate-500">v5.0</span>
                </div>
            </div>

            <!-- Cabecera y cuerpo -->
            <div id="globalSystemModalBody" class="p-6 pb-5 flex items-start gap-4">
                <div id="globalSystemModalIconContainer" class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0 shadow-sm border transition-colors">
                    <i id="globalSystemModalIcon" class="bi bi-info-circle-fill"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h3 id="globalSystemModalTitle" class="text-base font-black text-slate-900 tracking-tight truncate">Notificación</h3>
                        <span id="globalSystemModalBadge" class="hidden px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"></span>
                    </div>
                    <div id="globalSystemModalMessage" class="text-xs text-slate-600 mt-2 leading-relaxed break-words font-medium"></div>
                </div>
            </div>

            <!-- Acciones / Botones -->
            <div id="globalSystemModalFooter" class="p-4 px-6 bg-[#f8fafd] border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" 
                        id="globalSystemModalCancelBtn" 
                        class="hidden px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100 transition-colors">
                    Cancelar
                </button>
                <button type="button" 
                        id="globalSystemModalConfirmBtn" 
                        class="px-5 py-2.5 rounded-xl text-white text-xs font-bold transition-all shadow-sm flex items-center gap-2 active:scale-95">
                    <span>Entendido</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Preventivo de Inactividad de Sesión Dkript Inc. -->
    <div id="modalSessionIdleWarning" class="modal-wrapper hidden fixed inset-0 z-[99999] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity"></div>
        <div class="modal-card relative w-full max-w-md rounded-2xl bg-white shadow-2xl border border-amber-200 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
            <!-- Puntos estilo macOS para modal_style="window" -->
            <div class="modal-window-dots">
                <span class="dot-red"></span>
                <span class="dot-yellow"></span>
                <span class="dot-green"></span>
            </div>

            <div class="p-6 text-center">
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center text-2xl shadow-inner animate-pulse">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="text-base font-extrabold text-slate-800">¿Sigues interactuando en el sistema?</h3>
                <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                    Tu sesión se cerrará automáticamente por inactividad para proteger tus credenciales y datos de seguridad en:
                </p>
                <div class="my-4 py-2 px-4 rounded-xl bg-amber-50/80 border border-amber-200 inline-block font-mono-code font-black text-amber-700 text-lg tracking-widest" id="sessionIdleCountdown">
                    60s
                </div>
                <p class="text-[11px] text-slate-400">
                    Cualquier interacción o clic en el botón inferior renovará tu sesión automáticamente.
                </p>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-center gap-3">
                <button type="button" 
                        id="btnSessionIdleLogoutNow" 
                        onclick="DkriptIdle.logoutNow()" 
                        class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-bold hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-colors">
                    Cerrar Sesión Ahora
                </button>
                <button type="button" 
                        id="btnSessionIdleStay" 
                        onclick="DkriptIdle.stayConnected()" 
                        class="px-5 py-2.5 rounded-xl bg-[#0062f5] text-white text-xs font-bold hover:bg-[#004ecc] shadow-xs shadow-blue-500/20 transition-all flex items-center gap-2 active:scale-95">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Mantener Sesión Activa</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Formulario Canónico de Logout para el Sistema y DkriptIdle -->
    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>

    <!-- Modal de Perfil de Usuario Dkript Inc. (Modular) -->
    @include('partials.layouts.user-profile-modal')

    <!-- Scripts Base -->
    <script src="{{ asset('assets/js/custom.js') }}"></script>
    <script src="{{ asset('assets/js/rosen-charts.js') }}"></script>

    <!-- Dispatcher Global de Notificaciones Flash (Banners en Esquina Superior Derecha) -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.DkriptToast) {
                @if(session('success'))
                    DkriptToast.success({!! json_encode(session('success'), JSON_UNESCAPED_UNICODE) !!}, 'Operación Exitosa');
                @endif
                @if(session('error'))
                    DkriptToast.error({!! json_encode(session('error'), JSON_UNESCAPED_UNICODE) !!}, 'Error');
                @endif
                @if(session('info'))
                    DkriptToast.info({!! json_encode(session('info'), JSON_UNESCAPED_UNICODE) !!}, 'Notificación');
                @endif
                @if(session('warning'))
                    DkriptToast.warning({!! json_encode(session('warning'), JSON_UNESCAPED_UNICODE) !!}, 'Aviso');
                @endif
                @if(session('status'))
                    DkriptToast.info({!! json_encode(session('status'), JSON_UNESCAPED_UNICODE) !!}, 'Estado');
                @endif
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
