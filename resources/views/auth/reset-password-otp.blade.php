<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Establecer Nueva Contraseña - {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</title>
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
            <div class="relative w-20 h-20 sm:w-24 sm:h-24 mb-1 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border border-emerald-400/20 animate-ping opacity-25 pointer-events-none"></div>
                <div class="absolute inset-2 rounded-full bg-gradient-to-b from-emerald-400/10 to-transparent blur-md pointer-events-none"></div>
                <video autoplay loop muted playsinline class="w-full h-full object-contain filter drop-shadow-[0_0_20px_rgba(16,185,129,0.5)] pointer-events-none mix-blend-screen rounded-full" style="-webkit-mask-image: radial-gradient(circle at center, black 60%, transparent 98%); mask-image: radial-gradient(circle at center, black 60%, transparent 98%);">
                    <source src="{{ asset('assets/images/animations/mp4/drypt-orb-welcome.mp4') }}" type="video/mp4">
                    <img src="{{ asset('assets/images/branding/icon-drypt-front.png') }}" alt="Drypt" class="w-full h-full object-contain">
                </video>
            </div>

            @if(!empty($globalSystemParameter->system_logo))
                <div class="inline-flex relative mb-2">
                    <img src="{{ asset($globalSystemParameter->system_logo) }}" 
                         alt="{{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}" 
                         class="h-9 sm:h-11 w-auto object-contain mx-auto drop-shadow-2xl">
                </div>
            @else
                <div class="inline-flex relative mb-2">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center font-black text-lg bg-gradient-to-br from-[#0062f5] to-[#00d4ff] text-white shadow-xl shadow-cyan-500/20 border border-[#00d4ff]/40 mx-auto">
                        <span>{{ mb_strtoupper(mb_substr($globalSystemParameter->system_name ?? config('app.name', 'Dkript Core'), 0, 1)) }}</span>
                    </div>
                </div>
            @endif
            <div class="flex items-center justify-center gap-2 mt-0.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-[#071026] text-emerald-400 border border-emerald-400/40 shadow-sm shadow-emerald-500/10">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Identidad Verificada</span>
                </span>
            </div>
        </div>

        <!-- Card de Nueva Contraseña -->
        <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8 border border-slate-200/90 shadow-[0_12px_40px_rgba(7,16,38,0.25)]">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-black text-slate-900 tracking-tight">Nueva Contraseña</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Define tus nuevas credenciales de acceso</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/60 flex items-center justify-center text-emerald-600 text-lg shadow-sm">
                    <i class="bi bi-key-fill"></i>
                </div>
            </div>

            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5">
                    <i class="bi bi-exclamation-circle-fill text-base flex-shrink-0 mt-0.5 text-rose-600"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Nueva Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-lock-fill text-sm"></i>
                        </span>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               required 
                               autofocus
                               class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400" 
                               placeholder="Mínimo 8 caracteres">
                        <button type="button" 
                                onclick="const inp = document.getElementById('password'); const isPass = inp.type === 'password'; inp.type = isPass ? 'text' : 'password'; this.querySelector('i').className = isPass ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition-colors">
                            <i class="bi bi-eye-fill text-sm"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Confirmar Nueva Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-shield-check text-sm"></i>
                        </span>
                        <input type="password" 
                               name="password_confirmation" 
                               id="password_confirmation" 
                               required 
                               class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400" 
                               placeholder="Repite la nueva contraseña">
                        <button type="button" 
                                onclick="const inp = document.getElementById('password_confirmation'); const isPass = inp.type === 'password'; inp.type = isPass ? 'text' : 'password'; this.querySelector('i').className = isPass ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition-colors">
                            <i class="bi bi-eye-fill text-sm"></i>
                        </button>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-600 text-xs space-y-1">
                    <p class="font-bold text-slate-700">Requisitos de seguridad:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-500">
                        <li>Longitud mínima de 8 caracteres</li>
                        <li>Al menos una letra y al menos un número</li>
                    </ul>
                </div>

                <button type="submit" class="btn-dkript-primary w-full py-3 text-sm mt-2">
                    <span>Guardar y Entrar al Sistema</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-center gap-2 text-xs text-slate-400 mt-6">
            <img src="{{ asset('assets/images/branding/icon-drypt-side.png') }}" alt="Drypt" class="w-5 h-5 object-contain opacity-80">
            <p>&copy; {{ date('Y') }} {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}. Todos los derechos reservados.</p>
        </div>
    </div>

    <!-- Contenedor Global de Toasts -->
    <div id="dkriptToastContainer" 
         class="fixed top-6 right-6 z-[99999] flex flex-col gap-3 pointer-events-none max-w-sm sm:max-w-md w-full"
         aria-live="polite" 
         aria-atomic="true">
    </div>

    <script src="{{ asset('assets/js/custom.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
                DkriptToast.success({!! json_encode(session('success'), JSON_UNESCAPED_UNICODE) !!}, 'Éxito');
            @endif
            @if(session('error'))
                DkriptToast.error({!! json_encode(session('error'), JSON_UNESCAPED_UNICODE) !!}, 'Error');
            @endif
        });
    </script>
</body>
</html>