<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Verificar Código OTP - {{ $globalSystemParameter->system_name ?? config('app.name', 'Dkript Core') }}</title>
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
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-[#071026] text-[#00d4ff] border border-[#00d4ff]/40 shadow-sm shadow-cyan-500/10">
                    <span class="w-2 h-2 rounded-full bg-[#00d4ff] animate-pulse"></span>
                    <span>Validación en 2 Pasos</span>
                </span>
            </div>
        </div>

        <!-- Banner del Modo Simulador si está activo -->
        @if(!empty($simulatorOtp))
            <div class="mb-4 p-4 rounded-2xl otp-simulator-badge text-white relative overflow-hidden group">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-2.5 w-2.5 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-cyan-500"></span>
                        </span>
                        <div>
                            <p class="text-[10px] uppercase font-black tracking-widest text-[#00d4ff]">Modo Simulador Activo</p>
                            <p class="text-xs text-slate-300">Código de prueba generado en log:</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-black/40 font-mono text-base font-extrabold text-cyan-300 tracking-widest border border-cyan-500/30">
                            {{ $simulatorOtp }}
                        </span>
                        <button type="button" 
                                onclick="DkriptOtp.fillCode('{{ $simulatorOtp }}')"
                                class="px-2.5 py-1 rounded-lg bg-[#0062f5] hover:bg-[#004ecc] text-white text-[11px] font-bold transition-all shadow-sm flex items-center gap-1">
                            <i class="bi bi-box-arrow-in-down-right"></i>
                            <span>Pegar</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Card de Verificación -->
        <div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-8 border border-slate-200/90 shadow-[0_12px_40px_rgba(7,16,38,0.25)]">
            <div class="text-center pb-3 mb-5 border-b border-slate-100">
                <h2 class="text-xl font-black text-slate-900 tracking-tight">Ingresa el Código OTP</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Enviado a <span class="font-bold text-slate-800">{{ $maskedPhone }}</span> vía 
                    <span class="font-bold text-[#0062f5]">{{ $channel === 'whatsapp' ? 'WhatsApp' : 'SMS' }}</span>
                </p>
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

            <form method="POST" action="{{ route('password.verify') }}" id="frmVerifyOtp" class="space-y-6">
                @csrf
                <input type="hidden" name="code" id="otpHiddenCode" value="">

                <!-- Contenedor de 6 Dígitos -->
                <div>
                    <label class="block text-center text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                        Código de Seguridad (6 dígitos)
                    </label>
                    <div id="dkriptOtpContainer" class="flex items-center justify-center gap-2 sm:gap-3">
                        @for($i = 0; $i < 6; $i++)
                            <input type="text" 
                                   name="digits[]" 
                                   class="otp-input-field" 
                                   maxlength="1" 
                                   inputmode="numeric" 
                                   pattern="[0-9]*" 
                                   autocomplete="one-time-code"
                                   data-index="{{ $i }}"
                                   required>
                        @endfor
                    </div>
                </div>

                <button type="submit" class="btn-dkript-primary w-full py-3 text-sm">
                    <span>Verificar y Continuar</span>
                    <i class="bi bi-shield-check"></i>
                </button>
            </form>

            <!-- Reenvío con Cooldown de 60s -->
            <div class="mt-6 pt-5 border-t border-slate-100 flex flex-col items-center gap-3 text-center">
                <div class="text-xs text-slate-500 flex items-center gap-1.5" id="otpTimerWrapper">
                    <i class="bi bi-stopwatch text-slate-400"></i>
                    <span>Podrás reenviar un nuevo código en: </span>
                    <span id="otpCountdown" class="font-bold text-[#0062f5] font-mono">01:00</span>
                </div>

                <form method="POST" action="{{ route('password.resend-otp') }}" id="frmResendOtp">
                    @csrf
                    <button type="submit" 
                            id="btnResendOtp" 
                            disabled 
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0062f5] hover:text-blue-700 transition-all opacity-50 cursor-not-allowed pointer-events-none">
                        <i class="bi bi-arrow-clockwise text-sm"></i>
                        <span>Reenviar código de verificación</span>
                    </button>
                </form>

                <div class="pt-2">
                    <a href="{{ route('password.request') }}" class="text-[11px] font-medium text-slate-400 hover:text-slate-600 transition-colors">
                        ¿Número incorrecto? Cambiar número o canal
                    </a>
                </div>
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
        document.addEventListener('DOMContentLoaded', () => {
            DkriptOtp.startCountdown(60, 'otpCountdown', 'btnResendOtp');

            @if(session('success'))
                DkriptToast.success({!! json_encode(session('success'), JSON_UNESCAPED_UNICODE) !!}, 'Código Enviado');
            @endif
            @if(session('info'))
                DkriptToast.info({!! json_encode(session('info'), JSON_UNESCAPED_UNICODE) !!}, 'Aviso');
            @endif
            @if(session('error'))
                DkriptToast.error({!! json_encode(session('error'), JSON_UNESCAPED_UNICODE) !!}, 'Verificación Fallida');
            @endif
        });
    </script>
</body>
</html>