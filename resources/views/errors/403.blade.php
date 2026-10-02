@php
    $errorConfig = \App\Services\BrandingService::errorPage('403');
    $isBranded = \App\Services\BrandingService::isDkriptBranded();
@endphp

@extends('errors.layout')

@section('error_code', '403')
@section('title', '403 - ' . $errorConfig['title'])
@section('error_code_badge', $errorConfig['badge'])
@section('badge_class', 'bg-rose-500/10 text-rose-400 border border-rose-500/30')
@section('ping_class', 'bg-rose-500')

@section('fullscreen_video')
@if($errorConfig['has_video'])
<video autoplay loop muted playsinline class="w-full h-full max-w-4xl max-h-[82vh] object-contain opacity-100 pointer-events-none">
    <source src="{{ $errorConfig['video_url'] }}" type="video/mp4">
</video>
@elseif($errorConfig['has_image'])
<img src="{{ $errorConfig['image_url'] }}" alt="{{ $errorConfig['title'] }}" class="w-full h-full max-w-4xl max-h-[82vh] object-contain opacity-100 pointer-events-none">
@endif
@endsection

@section('fullscreen_scene_effects')
<!-- Números Holográficos 403 Monumentales de Fondo -->
<div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-0 opacity-45 pointer-events-none select-none">
    <span class="text-8xl sm:text-[14rem] font-black font-mono-code text-transparent bg-clip-text bg-gradient-to-b from-rose-500 via-rose-700 to-transparent drop-shadow-[0_0_60px_rgba(244,63,94,0.6)]">403</span>
</div>

<!-- Anillos Orbitales Holográficos Panorámicos en Movimiento -->
<div class="w-[360px] sm:w-[500px] aspect-square rounded-full border-2 border-dashed border-rose-500/40 animate-spin pointer-events-none z-10" style="animation-duration: 30s;"></div>
<div class="absolute w-[280px] sm:w-[400px] aspect-square rounded-full border border-rose-500/50 animate-pulse-ring pointer-events-none z-10"></div>

<!-- Láser de Barrido Cuántico Panorámico a Pantalla Completa -->
<div class="fixed inset-x-0 animate-laser-sweep pointer-events-none z-20">
    <div class="w-full h-1 bg-rose-500 shadow-[0_0_20px_#f43f5e,0_0_40px_#e11d48]"></div>
    <div class="w-full h-12 bg-gradient-to-t from-rose-500/20 to-transparent -mt-1"></div>
</div>

<!-- Nodos Orbitantes de Datos en Fullscreen -->
<div class="absolute top-12 left-12 sm:top-20 sm:left-24 w-3.5 h-3.5 rounded-full bg-rose-500 shadow-[0_0_15px_#f43f5e] animate-ping pointer-events-none z-20"></div>
<div class="absolute bottom-28 right-12 sm:bottom-32 sm:right-24 w-3 h-3 rounded-full bg-amber-400 shadow-[0_0_12px_#f59e0b] pointer-events-none z-20"></div>
@endsection

@section('error_content_hud')
<div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
    <div class="min-w-0 space-y-1">
        <div class="flex items-center justify-center sm:justify-start gap-2">
            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono-code font-black bg-rose-500/20 text-rose-400 border border-rose-500/50 shadow-[0_0_10px_rgba(244,63,94,0.4)]">
                {{ $errorConfig['badge'] }}
            </span>
            <span class="text-[10px] font-mono-code text-slate-400 hidden md:inline">| HTTP 403</span>
        </div>
        <h2 class="text-base sm:text-xl font-black text-white tracking-tight">{{ $errorConfig['title'] }}</h2>
        <p class="text-xs text-slate-300 max-w-lg">{{ $errorConfig['message'] }}</p>
    </div>
    <div class="flex items-center gap-2.5 flex-shrink-0">
        <button type="button" onclick="history.back()" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700 border border-slate-700 rounded-xl transition-all flex items-center gap-1.5 shadow-sm">
            <i class="bi bi-arrow-left"></i>
            <span>Volver</span>
        </button>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl transition-all flex items-center gap-1.5 shadow-lg shadow-rose-950/50">
            <i class="bi bi-shield-check"></i>
            <span>Ir al Dashboard</span>
        </a>
    </div>
</div>
@endsection

@section('mascot_scene')
<div class="relative w-full max-w-[420px] aspect-square flex items-center justify-center p-4 hud-overlay-container">
    
    <!-- CAPA 0 (FONDO): Números 3D y Sombra de Energía -->
    <div class="absolute -top-6 -left-4 sm:-top-8 sm:-left-8 z-0 opacity-25 select-none pointer-events-none">
        <span id="holo403Text" class="text-7xl sm:text-9xl font-black font-mono-code tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-rose-500 via-rose-700 to-transparent drop-shadow-[0_0_35px_rgba(244,63,94,0.4)]">
            403
        </span>
    </div>

    <div class="absolute bottom-2 w-48 h-6 bg-rose-600/20 rounded-full blur-xl transform scale-x-125 z-0"></div>

    <!-- CAPA 1 (BASE MEDIA): Video -> Imagen -> Fallback Vectorial Neutro -->
    @if($errorConfig['has_video'])
        <div id="dryptMascot" class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <video id="dryptMascotMedia" 
                   autoplay 
                   loop 
                   muted 
                   playsinline 
                   class="w-full h-full object-contain filter drop-shadow-[0_0_25px_rgba(244,63,94,0.4)] transition-all duration-300 pointer-events-none rounded-3xl">
                <source src="{{ $errorConfig['video_url'] }}" type="video/mp4">
                @if($errorConfig['has_image'])
                    <img id="dryptImg" 
                         src="{{ $errorConfig['image_url'] }}" 
                         alt="{{ $errorConfig['title'] }}" 
                         class="w-full h-full object-contain filter drop-shadow-[0_0_25px_rgba(244,63,94,0.4)] transition-all duration-300">
                @endif
            </video>
        </div>
    @elseif($errorConfig['has_image'])
        <div class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <img src="{{ $errorConfig['image_url'] }}" 
                 alt="{{ $errorConfig['title'] }}" 
                 class="w-full h-full object-contain filter drop-shadow-[0_0_25px_rgba(244,63,94,0.4)] transition-all duration-300 rounded-3xl">
        </div>
    @else
        <!-- Fallback Vectorial Neutro Ligero y Autónomo -->
        <div class="relative z-10 w-72 h-72 sm:w-84 sm:h-84 flex items-center justify-center animate-float-idle">
            <div class="w-64 h-64 sm:w-72 sm:h-72 rounded-3xl bg-[#071026]/95 border-2 border-rose-500/40 p-6 flex flex-col items-center justify-center shadow-[0_0_50px_rgba(244,63,94,0.25)] relative overflow-hidden backdrop-blur-2xl">
                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/15 via-transparent to-rose-900/20 pointer-events-none"></div>
                <div class="w-24 h-24 rounded-2xl bg-gradient-to-tr from-rose-900/40 to-rose-600/20 border border-rose-500/50 flex items-center justify-center text-rose-400 text-5xl mb-3 shadow-[0_0_25px_rgba(244,63,94,0.4)]">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <span class="text-3xl font-black font-mono-code text-white tracking-widest drop-shadow-[0_0_10px_rgba(244,63,94,0.5)]">403</span>
                <span class="text-[10px] font-mono-code text-rose-400 uppercase tracking-wider mt-1 text-center font-bold">ACCESO DENEGADO</span>
            </div>
        </div>
    @endif

    <!-- CAPA 2 (FRENTE ABSOLUTO): Retículas Tácticas Holográficas -->
    <div class="absolute top-2 left-2 w-7 h-7 border-t-2 border-l-2 border-rose-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#f43f5e]"></div>
    <div class="absolute top-2 right-2 w-7 h-7 border-t-2 border-r-2 border-rose-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#f43f5e]"></div>
    <div class="absolute bottom-2 left-2 w-7 h-7 border-b-2 border-l-2 border-rose-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#f43f5e]"></div>
    <div class="absolute bottom-2 right-2 w-7 h-7 border-b-2 border-r-2 border-rose-500 pointer-events-none z-40 hud-front-layer shadow-[0_0_12px_#f43f5e]"></div>

    <!-- Badge Superior Flotante -->
    <div class="absolute -top-1 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-[#071026]/95 border border-rose-500 text-[9px] font-mono-code font-bold text-rose-400 shadow-[0_0_15px_rgba(244,63,94,0.4)] z-40 hud-front-layer flex items-center gap-1.5 pointer-events-none">
        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
        <span>{{ $isBranded ? 'DRYPT // SENTINEL_DEFENSE_403' : 'SYS // HTTP_403_FORBIDDEN' }}</span>
    </div>

    <!-- Láser Holográfico de Barrido Vertical -->
    <div class="absolute inset-x-4 animate-laser-sweep pointer-events-none z-40 hud-front-layer">
        <div class="w-full h-1 bg-rose-500 shadow-[0_0_15px_#f43f5e,0_0_30px_#e11d48] rounded-full"></div>
        <div class="w-full h-10 bg-gradient-to-t from-rose-500/25 to-transparent -mt-1 pointer-events-none"></div>
    </div>

    <!-- Escudo Deflector Hexagonal -->
    <svg class="absolute inset-0 w-full h-full pointer-events-none z-40 hud-front-layer" viewBox="0 0 500 500">
        <polygon points="250,30 440,140 440,360 250,470 60,360 60,140" fill="none" stroke="#f43f5e" stroke-width="3.5" class="opacity-80 filter drop-shadow-[0_0_12px_#f43f5e]" />
        <circle cx="250" cy="250" r="160" fill="none" stroke="#f43f5e" stroke-width="2" opacity="0.6" class="animate-pulse-ring" />
    </svg>

    <div class="absolute -bottom-2 right-10 w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-700 text-white flex items-center justify-center text-xl shadow-[0_0_25px_rgba(244,63,94,0.8)] border border-rose-300/50 z-40 hud-front-layer pointer-events-none">
        <i class="bi bi-shield-lock-fill"></i>
    </div>
</div>
@endsection

@section('error_content')
<!-- Insignia y Título -->
<div class="space-y-3">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-mono-code font-bold">
        <i class="bi bi-shield-exclamation text-sm animate-pulse"></i>
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
<div class="w-full max-w-lg p-4 rounded-2xl bg-[#071026]/90 border border-rose-900/40 text-left space-y-2 backdrop-blur-md">
    <div class="flex items-center justify-between text-xs font-mono-code text-slate-400 border-b border-[#112356] pb-2">
        <span class="flex items-center gap-1.5 text-rose-400 font-bold">
            <i class="bi bi-shield-slash-fill"></i> REGISTRO DE AUDITORÍA
        </span>
        <span class="text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded">STATUS_CODE: 403</span>
    </div>
    <div class="grid grid-cols-2 gap-2 text-[11px] font-mono-code text-slate-400 pt-1">
        <div><span class="text-slate-500">Usuario:</span> <span class="text-slate-300 truncate inline-block max-w-[160px] align-bottom">{{ auth()->check() ? auth()->user()->email : 'No autenticado' }}</span></div>
        <div><span class="text-slate-500">Rol:</span> <span class="text-rose-400">{{ auth()->check() && auth()->user()->profile ? auth()->user()->profile->role?->name : 'Público' }}</span></div>
        <div><span class="text-slate-500">Recurso:</span> <span class="text-slate-300 truncate inline-block max-w-[160px] align-bottom">{{ request()->path() }}</span></div>
        <div><span class="text-slate-500">Permiso:</span> <span class="text-rose-400">DENEGADO (RBAC)</span></div>
    </div>
</div>

<!-- Botones de Acción -->
<div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto pt-2">
    <a href="{{ route('dashboard') }}" class="btn-dkript-primary w-full sm:w-auto px-6 py-3 text-sm flex items-center justify-center gap-2 shadow-lg shadow-rose-900/30">
        <i class="bi bi-speedometer2"></i>
        <span>Volver a Zona Segura</span>
    </a>
    <button type="button" onclick="history.back()" class="btn-dkript-dark w-full sm:w-auto px-6 py-3 text-sm font-semibold flex items-center justify-center gap-2">
        <i class="bi bi-arrow-left"></i>
        <span>Página Anterior</span>
    </button>
</div>
@endsection

