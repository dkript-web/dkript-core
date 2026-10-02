<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Recuperar Contraseña - {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</title>
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
            <div class="relative w-24 h-24 sm:w-28 sm:h-28 mb-1 flex items-center justify-center">
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
                         class="h-10 sm:h-12 w-auto object-contain mx-auto drop-shadow-2xl">
                </div>
            @else
                <div class="inline-flex relative mb-2">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl bg-gradient-to-br from-[#0062f5] to-[#00d4ff] text-white shadow-xl shadow-cyan-500/20 border border-[#00d4ff]/40 mx-auto">
                        <span>{{ mb_strtoupper(mb_substr($globalSystemParameter->system_name ?? config('app.name', 'Dkript Core'), 0, 1)) }}</span>
                    </div>
                </div>
            @endif
            <div class="flex items-center justify-center gap-2 mt-0.5">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-[#071026] text-[#00d4ff] border border-[#00d4ff]/40 shadow-sm shadow-cyan-500/10">
                    <span class="w-2 h-2 rounded-full bg-[#00d4ff] animate-pulse"></span>
                    <span>Recuperación Segura</span>
                </span>
            </div>
        </div>

        <!-- Banner del Modo Simulador de Correo si se generó un link de prueba -->
        @if(session('simulator_reset_url'))
            <div class="mb-4 p-4 rounded-2xl otp-simulator-badge text-white relative overflow-hidden">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-2.5 w-2.5 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-cyan-500"></span>
                        </span>
                        <div>
                            <p class="text-[10px] uppercase font-black tracking-widest text-[#00d4ff]">Modo Simulador de Correo</p>
                            <p class="text-xs text-slate-300">Enlace registrado en log de Laravel:</p>
                        </div>
                    </div>
                    <a href="{{ session('simulator_reset_url') }}" 
                       class="px-3 py-1.5 rounded-xl bg-[#0062f5] hover:bg-[#004ecc] text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1 whitespace-nowrap">
                        <i class="bi bi-box-arrow-up-right text-[11px]"></i>
                        <span>Abrir Enlace</span>
                    </a>
                </div>
            </div>
        @endif

        <!-- Card de Recuperación -->
        <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8 border border-slate-200/90 shadow-[0_12px_40px_rgba(7,16,38,0.25)]">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-black text-slate-900 tracking-tight">Recuperar Contraseña</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Elige el método de validación</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200/60 flex items-center justify-center text-[#0062f5] text-lg shadow-sm">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
            </div>

            <!-- Selector de Métodos: Pestañas -->
            <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-100/80 rounded-2xl mb-5">
                <button type="button" 
                        id="tabBtnPhone"
                        onclick="switchRecoveryTab('phone')"
                        class="py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 bg-white text-[#0062f5] shadow-sm">
                    <i class="bi bi-phone"></i>
                    <span>Por Teléfono</span>
                </button>
                <button type="button" 
                        id="tabBtnEmail"
                        onclick="switchRecoveryTab('email')"
                        class="py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900">
                    <i class="bi bi-envelope"></i>
                    <span>Por Correo</span>
                </button>
            </div>

            @if($errors->any())
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-3">
                    <i class="bi bi-exclamation-circle-fill text-base flex-shrink-0 mt-0.5 text-rose-600"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <p class="font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- SECCIÓN A: Recuperación Telefónica (OTP SMS / WhatsApp) -->
            <div id="recoveryPhoneSection">
                <p class="text-xs text-slate-600 leading-relaxed mb-5">
                    Ingresa el número telefónico registrado en tu cuenta corporativa. Te enviaremos un código de seguridad de 6 dígitos.
                </p>

                <form method="POST" action="{{ route('password.send-otp') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Número de Teléfono
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="bi bi-telephone-fill text-sm"></i>
                            </span>
                            <input type="text" 
                                   name="phone" 
                                   id="phone" 
                                   value="{{ old('phone') }}"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400" 
                                   placeholder="Ej. +52 55 1234 5678">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                            <i class="bi bi-info-circle"></i> Puedes incluir código de país (ej. +52) o 10 dígitos locales.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Canal de Recepción
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer transition-all hover:bg-slate-50 has-[:checked]:border-[#0062f5] has-[:checked]:bg-blue-50/50 has-[:checked]:ring-2 has-[:checked]:ring-[#0062f5]/20">
                                <input type="radio" name="channel" value="sms" class="sr-only" {{ old('channel', 'sms') === 'sms' ? 'checked' : '' }}>
                                <div class="w-8 h-8 rounded-lg bg-blue-100/80 text-[#0062f5] flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-chat-text-fill text-sm"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-800">SMS</span>
                                    <span class="block text-[10px] text-slate-500">Mensaje de Texto</span>
                                </div>
                            </label>

                            <label class="relative flex items-center gap-3 p-3 rounded-xl border border-slate-200 cursor-pointer transition-all hover:bg-slate-50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50 has-[:checked]:ring-2 has-[:checked]:ring-emerald-500/20">
                                <input type="radio" name="channel" value="whatsapp" class="sr-only" {{ old('channel') === 'whatsapp' ? 'checked' : '' }}>
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <i class="bi bi-whatsapp text-sm"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-slate-800">WhatsApp</span>
                                    <span class="block text-[10px] text-slate-500">Mensajería Instantánea</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-dkript-primary w-full py-3 text-sm">
                        <span>Enviar Código de Seguridad</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>
            </div>

            <!-- SECCIÓN B: Recuperación Tradicional por Correo Electrónico -->
            <div id="recoveryEmailSection" class="hidden">
                <p class="text-xs text-slate-600 leading-relaxed mb-5">
                    Ingresa el correo corporativo vinculado a tu cuenta. Te enviaremos un enlace seguro para restablecer tu contraseña.
                </p>

                <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Correo Electrónico <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="bi bi-envelope-fill text-sm"></i>
                            </span>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   value="{{ old('email') }}"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400" 
                                   placeholder="usuario@dkript.com">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                            <i class="bi bi-info-circle"></i> El enlace tendrá una vigencia de 60 minutos por seguridad.
                        </p>
                    </div>

                    <button type="submit" class="btn-dkript-primary w-full py-3 text-sm">
                        <span>Enviar Enlace por Correo</span>
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#0062f5] transition-colors">
                    <i class="bi bi-arrow-left"></i>
                    <span>¿Recordaste tu contraseña? Volver al inicio de sesión</span>
                </a>
            </div>
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
        function switchRecoveryTab(method) {
            const phoneSec = document.getElementById('recoveryPhoneSection');
            const emailSec = document.getElementById('recoveryEmailSection');
            const phoneBtn = document.getElementById('tabBtnPhone');
            const emailBtn = document.getElementById('tabBtnEmail');

            if (method === 'email') {
                phoneSec.classList.add('hidden');
                emailSec.classList.remove('hidden');

                emailBtn.className = 'py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 bg-white text-[#0062f5] shadow-sm';
                phoneBtn.className = 'py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900';
            } else {
                emailSec.classList.add('hidden');
                phoneSec.classList.remove('hidden');

                phoneBtn.className = 'py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 bg-white text-[#0062f5] shadow-sm';
                emailBtn.className = 'py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            @if(old('email') || session('simulator_reset_url'))
                switchRecoveryTab('email');
            @endif

            @if(session('success'))
                DkriptToast.success({!! json_encode(session('success'), JSON_UNESCAPED_UNICODE) !!}, 'Operación Exitosa');
            @endif
            @if(session('info'))
                DkriptToast.info({!! json_encode(session('info'), JSON_UNESCAPED_UNICODE) !!}, 'Notificación');
            @endif
            @if(session('error'))
                DkriptToast.error({!! json_encode(session('error'), JSON_UNESCAPED_UNICODE) !!}, 'Error');
            @endif
        });
    </script>
</body>
</html>