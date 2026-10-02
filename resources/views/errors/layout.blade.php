<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>@yield('title', 'Error del Sistema') | {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;700;800&display=swap" rel="stylesheet">

    @stack('styles')
</head>
<body class="min-h-screen text-slate-100 flex flex-col justify-between relative select-none bg-black overflow-x-hidden" style="font-family: 'Plus Jakarta Sans', sans-serif;" data-error-code="@yield('error_code', '404')">

    <!-- Canvas de Partículas Estelares / Datos Cuánticos -->
    <canvas id="cyberCanvas" class="fixed inset-0 pointer-events-none z-0 opacity-70"></canvas>

    <!-- Fondo de Rejilla Holográfica -->
    <div class="fixed inset-0 cyber-grid pointer-events-none z-0 opacity-40"></div>

    <!-- Luces de Acento Ambiental -->
    <div id="ambientAura1" class="fixed -top-32 -left-32 w-96 h-96 rounded-full blur-[140px] pointer-events-none z-0 opacity-30 transition-all duration-700"></div>
    <div id="ambientAura2" class="fixed -bottom-32 -right-32 w-96 h-96 rounded-full blur-[140px] pointer-events-none z-0 opacity-30 transition-all duration-700"></div>

    <!-- Barra Superior Corporativa -->
    <header class="relative z-20 w-full px-6 py-5 flex items-center justify-between border-b border-slate-800/80 backdrop-blur-md bg-black/70">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
            @if(!empty($globalSystemParameter->system_logo))
                <img src="{{ asset($globalSystemParameter->system_logo) }}" alt="{{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}" class="w-8 h-8 object-contain rounded-xl p-0.5 bg-[#071026] border border-[#112356] shadow-md group-hover:scale-105 transition-all">
            @else
                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs bg-gradient-to-br from-[#0062f5] to-[#00d4ff] text-white shadow-md border border-[#00d4ff]/40 group-hover:scale-105 transition-all">
                    <span>{{ mb_strtoupper(mb_substr($globalSystemParameter->system_name ?? config('app.name', 'Dkript Core'), 0, 1)) }}</span>
                </div>
            @endif
            <div class="flex flex-col">
                <span class="text-white font-extrabold text-sm tracking-wider flex items-center gap-1">
                    <span>{{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</span>
                </span>
                <span class="text-[10px] text-slate-400 font-mono-code -mt-0.5">{{ config('dkript.edition', 'Core') }} v{{ config('dkript.version', '1.0') }}</span>
            </div>
        </a>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono-code font-bold @yield('badge_class', 'bg-[#00d4ff]/10 text-[#00d4ff] border border-[#00d4ff]/30')">
                <span class="w-1.5 h-1.5 rounded-full animate-ping @yield('ping_class', 'bg-[#00d4ff]')"></span>
                <span>@yield('error_code_badge', 'HTTP ERROR')</span>
            </span>
            <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-[#071026] hover:bg-[#0b1739] border border-[#112356] transition-all">
                <i class="bi bi-house-door-fill text-[#00d4ff]"></i>
                <span>Inicio</span>
            </a>
        </div>
    </header>

    @php
        $errorDisplayMode = request('mode', $previewMode ?? ($globalSystemParameter->error_display_mode ?? 'scene'));
    @endphp

    @if ($errorDisplayMode === 'fullscreen')
        <!-- ======================================================== -->
        <!-- OPCIÓN B: FONDO PANORÁMICO INMERSIVO FULL-SCREEN         -->
        <!-- ======================================================== -->
        <!-- Video Panorámico Protagonista: Centrado, Limpio y Luminoso -->
        <div class="fixed inset-0 z-0 flex items-center justify-center overflow-hidden pointer-events-none">
            @yield('fullscreen_video')
            <!-- Viñeta perimetral muy suave sólo en los bordes extremos -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_75%,rgba(0,0,0,0.6)_100%)] pointer-events-none"></div>
        </div>

        <!-- Zona Principal: Drypt es el protagonista central libre -->
        <main class="relative z-10 flex-1 flex flex-col justify-between items-center p-4 sm:p-6 lg:p-8 min-h-0 pointer-events-none">
            <!-- Zona Central Superior: Efectos de Animación JS/CSS Cinemáticos en Fullscreen -->
            <div class="w-full flex-1 flex items-center justify-center relative">
                @yield('fullscreen_scene_effects')
            </div>

            <!-- DOCK HUD FLOTANTE NÍTIDO, LEGIBLE Y DE ALTO CONTRASTE AL PIE -->
            <div class="w-full max-w-2xl mx-auto pointer-events-auto backdrop-blur-2xl bg-[#071026]/92 hover:bg-[#071026]/98 border border-[#112356] hover:border-[#00d4ff]/50 rounded-2xl p-4 sm:px-6 sm:py-4 shadow-2xl shadow-blue-950/80 transition-all duration-300 mb-3">
                @yield('error_content_hud')
            </div>
        </main>
    @else
        <!-- ======================================================== -->
        <!-- OPCIÓN A: ESCENA FOCAL CON ELEMENTOS SUPERPUESTOS        -->
        <!-- ======================================================== -->
        <main class="relative z-10 flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-10">
            <div class="w-full max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- Columna Izquierda / Central: Escena Animada de Drypt -->
                <div class="lg:col-span-6 flex flex-col items-center justify-center relative">
                    @yield('mascot_scene')
                </div>

                <!-- Columna Derecha: Narrativa y Acciones -->
                <div class="lg:col-span-6 flex flex-col items-center lg:items-start text-center lg:text-left space-y-5">
                    @yield('error_content')
                </div>

            </div>
        </main>
    @endif

    @php
        $isDkriptBranded = false;
        $systemName = config('app.name', 'Dkript Core');
        $companyName = null;
        try {
            $isDkriptBranded = \App\Services\BrandingService::isDkriptBranded();
            $systemName = $globalSystemParameter->system_name ?? \App\Services\BrandingService::name();
            $companyName = \App\Services\BrandingService::companyName();
        } catch (\Throwable $e) {
            $systemName = config('app.name', 'Dkript Core');
        }
    @endphp

    <!-- Pie de Página Corporativo -->
    <footer class="relative z-20 w-full px-6 py-4 border-t border-slate-800/80 backdrop-blur-md bg-black/70 text-center text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <i class="bi bi-shield-check text-[#00d4ff]"></i>
            @if($isDkriptBranded)
                <span>Drypt The Digital Alchemist &copy; {{ date('Y') }} {{ $companyName ?? 'Dkript Inc.' }}. Todos los derechos reservados.</span>
            @elseif(!empty($companyName))
                <span>&copy; {{ date('Y') }} {{ $companyName }}. Todos los derechos reservados.</span>
            @else
                <span>&copy; {{ date('Y') }} {{ $systemName }}. Todos los derechos reservados.</span>
            @endif
        </div>
        <div class="flex items-center gap-4 text-[11px] font-mono-code text-slate-400">
            <span>SEC-ZONE: PROD-V5</span>
            <span>NODE-STATUS: ACTIVE</span>
        </div>
    </footer>

    <!-- GSAP 3 Motor de Animación Oficial & Custom JS -->
    <script src="https://unpkg.com/gsap@3/dist/gsap.min.js"></script>
    <script src="{{ asset('assets/js/custom.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.DkriptErrorScene) {
                DkriptErrorScene.init('@yield("error_code", "404")');
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
