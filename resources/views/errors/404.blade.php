@php
    $errorConfig = \App\Services\BrandingService::errorPage('404');
    $isBranded = \App\Services\BrandingService::isDkriptBranded();
@endphp

@extends('errors.layout')

@section('error_code', '404')
@section('title', '404 - ' . $errorConfig['title'])
@section('error_code_badge', $errorConfig['badge'])
@section('badge_class', 'bg-[#00d4ff]/10 text-[#00d4ff] border border-[#00d4ff]/30')
@section('ping_class', 'bg-[#00d4ff]')

@section('fullscreen_video')
@if($errorConfig['has_video'])
<video autoplay loop muted playsinline class="w-full h-full max-w-4xl max-h-[82vh] object-contain opacity-100 transition-all pointer-events-none">
    <source src="{{ $errorConfig['video_url'] }}" type="video/mp4">
</video>
@elseif($errorConfig['has_image'])
<img src="{{ $errorConfig['image_url'] }}" alt="{{ $errorConfig['title'] }}" class="w-full h-full max-w-4xl max-h-[82vh] object-contain opacity-100 transition-all pointer-events-none">
@endif
@endsection

@section('fullscreen_scene_effects')
<!-- Números Holográficos 404 Monumentales de Fondo -->
<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-0 opacity-45 pointer-events-none select-none">
    <span class="text-8xl sm:text-[14rem] font-black font-mono-code text-transparent bg-clip-text bg-gradient-to-b from-[#00d4ff] via-[#0062f5] to-transparent drop-shadow-[0_0_60px_rgba(0,212,255,0.6)]">404</span>
</div>

<!-- Anillos Orbitales Holográficos Panorámicos en Movimiento -->
<div class="w-[360px] sm:w-[500px] aspect-square rounded-full border-2 border-dashed border-[#00d4ff]/40 animate-spin pointer-events-none z-10" style="animation-duration: 30s;"></div>
<div class="absolute w-[280px] sm:w-[400px] aspect-square rounded-full border border-[#0062f5]/50 animate-pulse-ring pointer-events-none z-10"></div>

<!-- Láser de Barrido Cuántico Panorámico a Pantalla Completa -->
<div class="fixed inset-x-0 animate-laser-sweep pointer-events-none z-20">
    <div class="w-full h-1 bg-[#00d4ff] shadow-[0_0_20px_#00d4ff,0_0_40px_#0062f5]"></div>
    <div class="w-full h-12 bg-gradient-to-t from-[#00d4ff]/20 to-transparent -mt-1"></div>
</div>

<!-- Nodos Orbitantes de Datos en Fullscreen -->
<div class="absolute top-12 left-12 sm:top-20 sm:left-24 w-3.5 h-3.5 rounded-full bg-[#00d4ff] shadow-[0_0_15px_#00d4ff] animate-ping pointer-events-none z-20"></div>
<div class="absolute bottom-28 right-12 sm:bottom-32 sm:right-24 w-3 h-3 rounded-full bg-[#60a5fa] shadow-[0_0_12px_#60a5fa] pointer-events-none z-20"></div>
@endsection

@section('error_content_hud')
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
    <div class="min-w-0 space-y-1">
        <div class="flex items-center justify-center sm:justify-start gap-2">
            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono-code font-black bg-[#00d4ff]/20 text-[#00d4ff] border border-[#00d4ff]/50 shadow-[0_0_10px_rgba(0,212,255,0.4)]">
                {{ $errorConfig['badge'] }}
            </span>
            <span class="text-[10px] font-mono-code text-slate-400 hidden md:inline">| HTTP 404</span>
        </div>
        <h2 class="text-base sm:text-xl font-black text-white tracking-tight">{{ $errorConfig['title'] }}</h2>
        <p class="text-xs text-slate-300 max-w-lg">{{ $errorConfig['message'] }}</p>
    </div>
    <div class="flex items-center gap-2.5 flex-shrink-0">
        <button type="button" onclick="history.back()" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700 border border-slate-700 rounded-xl transition-all flex items-center gap-1.5 shadow-sm">
            <i class="bi bi-arrow-left"></i>
            <span>Volver</span>
        </button>
        <a href="{{ route('dashboard') }}" class="btn-dkript-primary px-4 py-2 text-xs font-bold flex items-center gap-1.5 shadow-lg shadow-blue-900/40">
            <i class="bi bi-speedometer2"></i>
            <span>Ir al Dashboard</span>
        </a>
    </div>
</div>
@endsection

@section('mascot_scene')
<div class="relative w-full max-w-[420px] aspect-square flex items-center justify-center p-4 hud-overlay-container">
    
    <!-- CAPA 0 (FONDO): Números 3D y Sombra de Energía -->
    <div class="absolute -top-6 -left-4 sm:-top-8 sm:-left-8 z-0 opacity-25 select-none pointer-events-none">
        <span id="holo404Text" class="text-7xl sm:text-9xl font-black font-mono-code tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-[#00d4ff] via-[#0062f5] to-transparent drop-shadow-[0_0_35px_rgba(0,212,255,0.4)]">
            404
        </span>
    </div>

    <div class="absolute bottom-2 w-48 h-6 bg-[#00d4ff]/20 rounded-full blur-xl transform scale-x-125 z-0"></div>

    <!-- CAPA 1 (BASE MEDIA): Video -> Imagen -> Fallback Vectorial Neutro -->
    @if($errorConfig['has_video'])
        <div id="dryptMascot" class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <video id="dryptMascotMedia" 
                   autoplay 
                   loop 
                   muted 
                   playsinline 
                   class="w-full h-full object-contain glow-cyan filter transition-all duration-300 pointer-events-none rounded-3xl">
                <source src="{{ $errorConfig['video_url'] }}" type="video/mp4">
                @if($errorConfig['has_image'])
                    <img id="dryptImg" 
                         src="{{ $errorConfig['image_url'] }}" 
                         alt="{{ $errorConfig['title'] }}" 
                         class="w-full h-full object-contain glow-cyan filter transition-all duration-300 transform scale-100">
                @endif
            </video>
        </div>
    @elseif($errorConfig['has_image'])
        <div class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <img src="{{ $errorConfig['image_url'] }}" 
                 alt="{{ $errorConfig['title'] }}" 
                 class="w-full h-full object-contain glow-cyan filter drop-shadow-[0_0_25px_rgba(0,212,255,0.4)] transition-all duration-300 transform scale-100 rounded-3xl">
        </div>
    @else
        <!-- Fallback Vectorial Neutro Ligero y Autónomo -->
        <div class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <div class="w-64 h-64 sm:w-72 sm:h-72 rounded-3xl bg-[#071026]/95 border-2 border-[#00d4ff]/40 p-6 flex flex-col items-center justify-center shadow-[0_0_50px_rgba(0,212,255,0.25)] relative overflow-hidden backdrop-blur-2xl">
                <div class="absolute inset-0 bg-gradient-to-br from-[#00d4ff]/15 via-transparent to-[#0062f5]/20 pointer-events-none"></div>
                <div class="w-24 h-24 rounded-2xl bg-gradient-to-tr from-[#0062f5]/30 to-[#00d4ff]/20 border border-[#00d4ff]/50 flex items-center justify-center text-[#00d4ff] text-5xl mb-3 shadow-[0_0_25px_rgba(0,212,255,0.4)]">
                    <i class="bi bi-radar animate-spin" style="animation-duration: 8s;"></i>
                </div>
                <span class="text-3xl font-black font-mono-code text-white tracking-widest drop-shadow-[0_0_10px_rgba(0,212,255,0.5)]">404</span>
                <span class="text-[10px] font-mono-code text-[#00d4ff] uppercase tracking-wider mt-1 text-center font-bold">RUTA NO LOCALIZADA</span>
            </div>
        </div>
    @endif

    <!-- CAPA 2 (FRENTE ABSOLUTO): Retículas Tácticas Holográficas -->
    <div class="absolute top-2 left-2 w-7 h-7 border-t-2 border-l-2 border-[#00d4ff] pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#00d4ff]"></div>
    <div class="absolute top-2 right-2 w-7 h-7 border-t-2 border-r-2 border-[#00d4ff] pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#00d4ff]"></div>
    <div class="absolute bottom-2 left-2 w-7 h-7 border-b-2 border-l-2 border-[#00d4ff] pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#00d4ff]"></div>
    <div class="absolute bottom-2 right-2 w-7 h-7 border-b-2 border-r-2 border-[#00d4ff] pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#00d4ff]"></div>

    <!-- Badge Superior Flotante -->
    <div class="absolute -top-1 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-[#071026]/95 border border-[#00d4ff] text-[9px] font-mono-code font-bold text-[#00d4ff] shadow-[0_0_15px_rgba(0,212,255,0.4)] z-40 hud-front-layer flex items-center gap-1.5 pointer-events-none">
        <span class="w-1.5 h-1.5 rounded-full bg-[#00d4ff] animate-ping"></span>
        <span>{{ $isBranded ? 'DRYPT // TARGET_LOCK_404' : 'SYS // HTTP_404_NOT_FOUND' }}</span>
    </div>

    <!-- Láser Holográfico de Barrido Vertical -->
    <div class="absolute inset-x-4 animate-laser-sweep pointer-events-none z-40 hud-front-layer">
        <div class="w-full h-1 bg-[#00d4ff] shadow-[0_0_15px_#00d4ff,0_0_30px_#0062f5] rounded-full"></div>
        <div class="w-full h-10 bg-gradient-to-t from-[#00d4ff]/25 to-transparent -mt-1 pointer-events-none"></div>
    </div>

    <!-- Retícula Central -->
    <div class="absolute inset-10 pointer-events-none z-40 hud-front-layer flex items-center justify-center">
        <div class="w-56 h-56 rounded-full border border-dashed border-[#00d4ff]/60 animate-spin" style="animation-duration: 20s;"></div>
        <div class="absolute w-40 h-40 rounded-full border border-[#0062f5]/60 animate-pulse-ring"></div>
        <div class="absolute w-6 h-6 flex items-center justify-center">
            <div class="w-full h-[2px] bg-[#00d4ff] shadow-[0_0_8px_#00d4ff]"></div>
            <div class="absolute h-full w-[2px] bg-[#00d4ff] shadow-[0_0_8px_#00d4ff]"></div>
        </div>
    </div>

    @if($isBranded)
        <!-- Haz de Escaneo Cuántico Proyectado -->
        <svg id="scannerBeamSVG" class="absolute inset-0 w-full h-full pointer-events-none z-40 hud-front-layer" viewBox="0 0 500 500">
            <defs>
                <radialGradient id="beamGrad" cx="40%" cy="38%" r="60%">
                    <stop offset="0%" stop-color="#00d4ff" stop-opacity="0.95" />
                    <stop offset="45%" stop-color="#0062f5" stop-opacity="0.6" />
                    <stop offset="100%" stop-color="#000000" stop-opacity="0" />
                </radialGradient>
            </defs>
            <polygon id="scannerBeamCone" points="200,190 480,80 490,440" fill="url(#beamGrad)" class="scanner-beam" />
        </svg>
    @endif

    <div id="orbitNode1" class="absolute top-6 left-6 w-3.5 h-3.5 rounded-full bg-[#00d4ff] shadow-[0_0_15px_#00d4ff] animate-ping z-40 hud-front-layer pointer-events-none"></div>
    <div id="orbitNode2" class="absolute bottom-8 right-8 w-3 h-3 rounded-full bg-[#60a5fa] shadow-[0_0_12px_#60a5fa] z-40 hud-front-layer pointer-events-none"></div>
</div>
@endsection

@section('error_content')
<!-- Insignia y Título -->
<div class="space-y-3">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-[#00d4ff]/10 border border-[#00d4ff]/30 text-[#00d4ff] text-xs font-mono-code font-bold">
        <i class="bi bi-radar text-sm animate-spin" style="animation-duration: 4s;"></i>
        <span>{{ $errorConfig['badge'] }}</span>
    </div>
    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight">
        {{ $errorConfig['title'] }}
    </h1>
</div>

<!-- Mensaje Narrativo -->
<p class="text-sm sm:text-base text-slate-400 leading-relaxed max-w-lg">
    {{ $errorConfig['message'] }}
</p>

<!-- Tarjeta de Diagnóstico Técnico Rápido -->
<div class="w-full max-w-lg p-4 rounded-2xl bg-[#071026]/90 border border-[#112356] text-left space-y-2 backdrop-blur-md">
    <div class="flex items-center justify-between text-xs font-mono-code text-slate-400 border-b border-[#112356] pb-2">
        <span class="flex items-center gap-1.5 text-slate-300 font-bold">
            <i class="bi bi-terminal-fill text-[#00d4ff]"></i> TELEMETRÍA DE RED
        </span>
        <span class="text-[#00d4ff] bg-[#00d4ff]/10 px-2 py-0.5 rounded">STATUS_CODE: 404</span>
    </div>
    <div class="grid grid-cols-2 gap-2 text-[11px] font-mono-code text-slate-400 pt-1">
        <div><span class="text-slate-500">Ruta:</span> <span class="text-slate-300 truncate inline-block max-w-[160px] align-bottom">{{ request()->path() }}</span></div>
        <div><span class="text-slate-500">Modo:</span> <span class="text-[#00d4ff]">{{ $errorConfig['has_media'] ? 'Multimedia' : 'Estándar' }}</span></div>
        <div><span class="text-slate-500">Protocolo:</span> <span class="text-slate-300">HTTPS / REST</span></div>
        <div><span class="text-slate-500">Acción:</span> <span class="text-emerald-400">Reubicar Navegación</span></div>
    </div>
</div>

<!-- Botones de Acción -->
<div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto pt-2">
    <a href="{{ route('dashboard') }}" class="btn-dkript-primary w-full sm:w-auto px-6 py-3 text-sm flex items-center justify-center gap-2 shadow-lg shadow-blue-900/30">
        <i class="bi bi-speedometer2"></i>
        <span>Regresar al Dashboard</span>
    </a>
    <button type="button" onclick="history.back()" class="btn-dkript-dark w-full sm:w-auto px-6 py-3 text-sm font-semibold flex items-center justify-center gap-2">
        <i class="bi bi-arrow-left"></i>
        <span>Página Anterior</span>
    </button>
</div>
@endsection

