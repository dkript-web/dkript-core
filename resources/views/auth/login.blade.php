<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Iniciar Sesión - {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
</head>
<body class="bg-[#030712] min-h-screen flex items-center justify-center p-4 font-sans relative overflow-x-hidden safe-area-body">
    <!-- Fondo Atmosférico con Branding Aprobado -->
    <div class="fixed inset-0 z-0 opacity-25 bg-cover bg-center pointer-events-none" style="background-image: url('{{ asset('assets/images/branding/bg-developer-workspace.png') }}');"></div>
    <div class="fixed inset-0 z-0 bg-gradient-to-b from-[#030712]/80 via-[#071026]/90 to-[#030712] pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10 py-6">
        <!-- Brand Header con Logo Oficial y Drypt Animado -->
        <div class="text-center mb-6 flex flex-col items-center">
            <!-- Avatar Alquimista Drypt con Orbe Cuántico -->
            <div class="relative w-28 h-28 sm:w-32 sm:h-32 mb-1 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border border-[#00d4ff]/20 animate-ping opacity-25 pointer-events-none"></div>
                <div class="absolute inset-2 rounded-full bg-gradient-to-b from-[#00d4ff]/10 to-transparent blur-md pointer-events-none"></div>
                <video autoplay loop muted playsinline class="w-full h-full object-contain filter drop-shadow-[0_0_20px_rgba(0,212,255,0.5)] pointer-events-none mix-blend-screen rounded-full" style="-webkit-mask-image: radial-gradient(circle at center, black 60%, transparent 98%); mask-image: radial-gradient(circle at center, black 60%, transparent 98%);">
                    <source src="{{ asset('assets/images/animations/mp4/drypt-orb-welcome.mp4') }}" type="video/mp4">
                    <img src="{{ asset('assets/images/branding/icon-drypt-front.png') }}" alt="Drypt" class="w-full h-full object-contain">
                </video>
            </div>

            @if(!empty($globalSystemParameter->system_logo))
                <div class="inline-flex relative mb-2">
                    <img src="{{ asset($globalSystemParameter->system_logo) }}" 
                         alt="{{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}" 
                         class="h-12 sm:h-14 w-auto object-contain mx-auto drop-shadow-2xl">
                </div>
            @else
                <div class="inline-flex relative mb-2">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-black text-2xl bg-gradient-to-br from-[#0062f5] to-[#00d4ff] text-white shadow-xl shadow-cyan-500/20 border border-[#00d4ff]/40 mx-auto">
                        <span>{{ mb_strtoupper(mb_substr($globalSystemParameter->system_name ?? config('app.name', 'Dkript Core'), 0, 1)) }}</span>
                    </div>
                </div>
            @endif
            <div class="flex items-center justify-center gap-2 mt-0.5">
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-[#071026] text-[#00d4ff] border border-[#00d4ff]/40 shadow-sm shadow-cyan-500/10">
                    <span class="w-2 h-2 rounded-full bg-[#00d4ff] animate-pulse"></span>
                    <span>{{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</span>
                </span>
            </div>
        </div>

        <!-- Login Card Elegante -->
        <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8 border border-slate-200/90 shadow-[0_12px_40px_rgba(7,16,38,0.25)]">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-black text-slate-900 tracking-tight">Acceso al Sistema</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Ingresa tus credenciales corporativas</p>
                </div>
                @if(!empty($globalSystemParameter->system_logo))
                    <img src="{{ asset($globalSystemParameter->system_logo) }}" alt="Logo" class="w-9 h-9 object-contain rounded-xl p-1 bg-[#071026] border border-[#112356] shadow-sm">
                @else
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm bg-gradient-to-br from-[#0062f5] to-[#00d4ff] text-white shadow-sm border border-[#00d4ff]/30">
                        <span>{{ mb_strtoupper(mb_substr($globalSystemParameter->system_name ?? config('app.name', 'Dkript Core'), 0, 1)) }}</span>
                    </div>
                @endif
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3">
                    <i class="bi bi-exclamation-circle-fill text-lg flex-shrink-0 mt-0.5 text-rose-600"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4 sm:space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-envelope text-sm"></i>
                        </span>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               autofocus
                               value="{{ old('email') }}"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400" 
                                placeholder="usuario@ejemplo.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-lock text-sm"></i>
                        </span>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               required 
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400" 
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-[#0062f5] focus:ring-[#0062f5] border-slate-300">
                        <span class="text-slate-600 text-xs font-medium">Recordarme en este equipo</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#0062f5] hover:text-[#004ec2] hover:underline transition-all">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button type="submit" 
                        class="btn-dkript-primary w-full py-3 text-sm">
                    <span>Ingresar a la Plataforma</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Footer con Mascota Drypt -->
        <div class="flex items-center justify-center gap-2 text-xs text-slate-400 mt-6">
            <img src="{{ asset('assets/images/branding/icon-drypt-side.png') }}" alt="Drypt" class="w-5 h-5 object-contain opacity-80">
            <p>&copy; {{ date('Y') }} {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}@if(config('dkript.branding.default_company_name')) &bull; {{ config('dkript.branding.default_company_name') }}@endif. Todos los derechos reservados.</p>
        </div>
    </div>

    <!-- Contenedor Global de Toasts / Banners Notificaciones (Esquina Superior Derecha) -->
    <div id="dkriptToastContainer" 
         class="fixed top-6 right-6 z-[99999] flex flex-col gap-3 pointer-events-none max-w-sm sm:max-w-md w-full"
         aria-live="polite" 
         aria-atomic="true">
    </div>

    <script src="{{ asset('assets/js/custom.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
                DkriptToast.success({!! json_encode(session('success'), JSON_UNESCAPED_UNICODE) !!}, 'Operación Exitosa');
            @endif
            @if(session('info'))
                DkriptToast.info({!! json_encode(session('info'), JSON_UNESCAPED_UNICODE) !!}, 'Notificación');
            @endif
            @if(session('error'))
                DkriptToast.error({!! json_encode(session('error'), JSON_UNESCAPED_UNICODE) !!}, 'Error');
            @endif
            @if(session('warning'))
                DkriptToast.warning({!! json_encode(session('warning'), JSON_UNESCAPED_UNICODE) !!}, 'Aviso');
            @endif
            @if(session('status'))
                DkriptToast.info({!! json_encode(session('status'), JSON_UNESCAPED_UNICODE) !!}, 'Estado');
            @endif
        });
    </script>
</body>
</html>
