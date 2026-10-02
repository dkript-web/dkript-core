@php
    $errorConfig = \App\Services\BrandingService::errorPage('503');
    $isBranded = \App\Services\BrandingService::isDkriptBranded();
@endphp

@extends('errors.layout')

@section('error_code', '503')
@section('title', '503 - ' . $errorConfig['title'])
@section('error_code_badge', $errorConfig['badge'])
@section('badge_class', 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30')
@section('ping_class', 'bg-emerald-500')

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
<!-- Números Holográficos 503 Monumentales de Fondo -->
<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-0 opacity-45 pointer-events-none select-none">
    <span class="text-8xl sm:text-[14rem] font-black font-mono-code text-transparent bg-clip-text bg-gradient-to-b from-emerald-400 via-teal-600 to-transparent drop-shadow-[0_0_60px_rgba(16,185,129,0.6)]">503</span>
</div>

<!-- Anillos Orbitales Holográficos Panorámicos en Movimiento -->
<div class="w-[360px] sm:w-[500px] aspect-square rounded-full border-2 border-dashed border-emerald-500/40 animate-spin pointer-events-none z-10" style="animation-duration: 25s;"></div>
<div class="absolute w-[280px] sm:w-[400px] aspect-square rounded-full border border-emerald-400/50 animate-pulse-ring pointer-events-none z-10"></div>

<!-- Láser de Barrido Panorámico a Pantalla Completa -->
<div class="fixed inset-x-0 animate-laser-sweep pointer-events-none z-20">
    <div class="w-full h-1 bg-emerald-400 shadow-[0_0_20px_#10b981,0_0_40px_#00d4ff]"></div>
    <div class="w-full h-12 bg-gradient-to-t from-emerald-500/20 to-transparent -mt-1"></div>
</div>

<!-- Nodos Orbitantes de Datos en Fullscreen -->
<div class="absolute top-12 left-12 sm:top-20 sm:left-24 w-3.5 h-3.5 rounded-full bg-emerald-400 shadow-[0_0_15px_#10b981] animate-ping pointer-events-none z-20"></div>
<div class="absolute bottom-28 right-12 sm:bottom-32 sm:right-24 w-3 h-3 rounded-full bg-teal-400 shadow-[0_0_12px_#2dd4bf] pointer-events-none z-20"></div>
@endsection

@section('error_content_hud')
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
    <div class="min-w-0 space-y-1">
        <div class="flex items-center justify-center sm:justify-start gap-2">
            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono-code font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/50 shadow-[0_0_10px_rgba(16,185,129,0.4)]">
                {{ $errorConfig['badge'] }}
            </span>
            <span class="text-[10px] font-mono-code text-slate-400 hidden md:inline">| HTTP 503</span>
        </div>
        <h2 class="text-base sm:text-xl font-black text-white tracking-tight">{{ $errorConfig['title'] }}</h2>
        <p class="text-xs text-slate-300 max-w-lg">{{ $errorConfig['message'] }}</p>
    </div>
    <div class="flex items-center gap-2.5 flex-shrink-0">
        <button type="button" onclick="location.reload()" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700 border border-slate-700 rounded-xl transition-all flex items-center gap-1.5 shadow-sm">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Verificar Estado</span>
        </button>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl transition-all flex items-center gap-1.5 shadow-lg shadow-emerald-950/40">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
    </div>
</div>
@endsection

@section('mascot_scene')
<div class="relative w-full max-w-[420px] aspect-square flex items-center justify-center p-4 hud-overlay-container">
    
    <!-- CAPA 0 (FONDO): Números 3D y Sombra de Energía -->
    <div class="absolute -top-6 -left-4 sm:-top-8 sm:-left-8 z-0 opacity-25 select-none pointer-events-none">
        <span id="holo503Text" class="text-7xl sm:text-9xl font-black font-mono-code tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-emerald-400 via-teal-600 to-transparent drop-shadow-[0_0_35px_rgba(16,185,129,0.4)]">
            503
        </span>
    </div>

    <div class="absolute bottom-2 w-48 h-6 bg-emerald-500/20 rounded-full blur-xl transform scale-x-125 z-0"></div>

    <!-- CAPA 1 (BASE MEDIA): Video -> Imagen -> Fallback Vectorial Neutro -->
    @if($errorConfig['has_video'])
        <div id="dryptMascot" class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <video id="dryptMascotMedia" 
                   autoplay 
                   loop 
                   muted 
                   playsinline 
                   class="w-full h-full object-contain filter drop-shadow-[0_0_25px_rgba(16,185,129,0.4)] pointer-events-none mix-blend-screen group-hover:scale-105 transition-all rounded-3xl">
                <source src="{{ $errorConfig['video_url'] }}" type="video/mp4">
                @if($errorConfig['has_image'])
                    <img id="dryptImg" 
                         src="{{ $errorConfig['image_url'] }}" 
                         alt="{{ $errorConfig['title'] }}" 
                         class="w-full h-full object-contain filter drop-shadow-[0_0_25px_rgba(16,185,129,0.4)] transition-all duration-300">
                @endif
            </video>
        </div>
    @elseif($errorConfig['has_image'])
        <div class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <img src="{{ $errorConfig['image_url'] }}" 
                 alt="{{ $errorConfig['title'] }}" 
                 class="w-full h-full object-contain filter drop-shadow-[0_0_25px_rgba(16,185,129,0.4)] transition-all duration-300 rounded-3xl">
        </div>
    @else
        <!-- Fallback Vectorial Neutro Ligero y Autónomo -->
        <div class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <div class="w-64 h-64 sm:w-72 sm:h-72 rounded-3xl bg-[#071026]/95 border-2 border-emerald-500/40 p-6 flex flex-col items-center justify-center shadow-[0_0_50px_rgba(16,185,129,0.25)] relative overflow-hidden backdrop-blur-2xl">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/15 via-transparent to-teal-950/20 pointer-events-none"></div>
                <div class="w-24 h-24 rounded-2xl bg-gradient-to-tr from-teal-900/40 to-emerald-500/20 border border-emerald-500/50 flex items-center justify-center text-emerald-400 text-5xl mb-3 shadow-[0_0_25px_rgba(16,185,129,0.4)]">
                    <i class="bi bi-tools animate-pulse"></i>
                </div>
                <span class="text-3xl font-black font-mono-code text-white tracking-widest drop-shadow-[0_0_10px_rgba(16,185,129,0.5)]">503</span>
                <span class="text-[10px] font-mono-code text-emerald-400 uppercase tracking-wider mt-1 text-center font-bold">MANTENIMIENTO DEL SISTEMA</span>
            </div>
        </div>
    @endif

    <!-- CAPA 2 (FRENTE ABSOLUTO): Retículas Tácticas Holográficas -->
    <div class="absolute top-2 left-2 w-7 h-7 border-t-2 border-l-2 border-emerald-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#10b981]"></div>
    <div class="absolute top-2 right-2 w-7 h-7 border-t-2 border-r-2 border-emerald-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#10b981]"></div>
    <div class="absolute bottom-2 left-2 w-7 h-7 border-b-2 border-l-2 border-emerald-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#10b981]"></div>
    <div class="absolute bottom-2 right-2 w-7 h-7 border-b-2 border-r-2 border-emerald-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#10b981]"></div>

    <!-- Badge Superior Flotante -->
    <div class="absolute -top-1 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-[#071026]/95 border border-emerald-500 text-[9px] font-mono-code font-bold text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.4)] z-40 hud-front-layer flex items-center gap-1.5 pointer-events-none">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
        <span>{{ $isBranded ? 'DRYPT // STASIS_CHAMBER_503' : 'SYS // HTTP_503_SERVICE_UNAVAILABLE' }}</span>
    </div>

    <!-- Láser Holográfico de Barrido Vertical -->
    <div class="absolute inset-x-4 animate-laser-sweep pointer-events-none z-40 hud-front-layer">
        <div class="w-full h-1 bg-emerald-400 shadow-[0_0_15px_#10b981,0_0_30px_#00d4ff] rounded-full"></div>
        <div class="w-full h-10 bg-gradient-to-t from-emerald-500/25 to-transparent -mt-1 pointer-events-none"></div>
    </div>

    <div class="absolute -bottom-2 right-10 w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-slate-950 flex items-center justify-center text-xl shadow-[0_0_25px_rgba(16,185,129,0.7)] border border-emerald-300/50 z-40 hud-front-layer pointer-events-none">
        <i class="bi bi-gear-wide-connected animate-spin" style="animation-duration: 8s;"></i>
    </div>
</div>
@endsection

@section('error_content')
<!-- Insignia y Título -->
<div class="space-y-3">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-mono-code font-bold">
        <i class="bi bi-tools text-sm"></i>
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
<div class="w-full max-w-lg p-4 rounded-2xl bg-[#071026]/90 border border-emerald-900/40 text-left space-y-2 backdrop-blur-md">
    <div class="flex items-center justify-between text-xs font-mono-code text-slate-400 border-b border-[#112356] pb-2">
        <span class="flex items-center gap-1.5 text-emerald-400 font-bold">
            <i class="bi bi-wrench-adjustable-circle-fill"></i> MANTENIMIENTO PROGRAMADO
        </span>
        <span class="text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded">STATUS_CODE: 503</span>
    </div>
    <div class="grid grid-cols-2 gap-2 text-[11px] font-mono-code text-slate-400 pt-1">
        <div><span class="text-slate-500">Módulo:</span> <span class="text-emerald-400">Infraestructura Core</span></div>
        <div><span class="text-slate-500">Modo:</span> <span class="text-[#00d4ff]">{{ $errorConfig['has_media'] ? 'Multimedia' : 'Estándar' }}</span></div>
        <div><span class="text-slate-500">Protocolo:</span> <span class="text-slate-300">HTTPS / REST</span></div>
        <div><span class="text-slate-500">Acción:</span> <span class="text-emerald-400">Calibración Activa</span></div>
    </div>
</div>

<!-- Botones de Acción -->
<div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto pt-2">
    <button type="button" onclick="location.reload()" class="btn-dkript-primary w-full sm:w-auto px-6 py-3 text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/40">
        <i class="bi bi-arrow-clockwise"></i>
        <span>Verificar Disponibilidad</span>
    </button>
    <a href="{{ route('dashboard') }}" class="btn-dkript-dark w-full sm:w-auto px-6 py-3 text-sm font-semibold flex items-center justify-center gap-2">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>
</div>
@endsection

