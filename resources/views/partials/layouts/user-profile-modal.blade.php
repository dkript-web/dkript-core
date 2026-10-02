<!-- Modal de Perfil de Usuario Dkript Inc. (Modular) -->
<div id="userProfileModal" 
     class="fixed inset-0 z-[9998] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden"
     role="dialog" 
     aria-modal="true"
     aria-labelledby="userProfileModalTitle">
    <div class="modal-card bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 select-none relative flex flex-col max-h-[90vh]">
        
        <!-- Cabecera Visual con Branding Corporativo y Drypt Touch -->
        <div class="p-6 bg-gradient-to-br from-[#071026] via-[#0b1739] to-[#00246B] text-white relative overflow-hidden flex-shrink-0">
            <div class="absolute -right-8 -bottom-8 w-36 h-36 rounded-full bg-[#00d4ff]/15 blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between relative z-10 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#00d4ff] animate-ping"></span>
                    <span class="text-[10px] font-mono-code font-bold uppercase tracking-widest text-[#00d4ff]">Credencial de Usuario</span>
                </div>
                <button type="button" 
                        onclick="closeUserProfileModal()" 
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors focus:outline-none" 
                        title="Cerrar">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <div class="flex items-center gap-4 relative z-10">
                <div class="relative flex-shrink-0 group cursor-pointer select-none" 
                     onclick="document.getElementById('inputUserProfileAvatar')?.click()" 
                     title="Haga clic para cambiar su foto de perfil">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl border-2 border-[#112356] ring-4 ring-[#00d4ff]/30 overflow-hidden relative shadow-xl bg-[#030712]">
                        <img id="userProfileModalAvatarImg"
                             src="{{ auth()->user()->profile?->avatar_url }}" 
                             alt="Avatar" 
                             class="w-full h-full object-cover transition-all duration-300 group-hover:scale-110">

                        <!-- Capa de oscurecimiento suave en hover (Estilo Logotipo Activo: solo cámara, sin texto ni fondo) -->
                        <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[1px]">
                            <i class="bi bi-camera-fill text-xl sm:text-2xl text-white drop-shadow-md transition-transform duration-300 group-hover:scale-110"></i>
                        </div>

                        <!-- Spinner de subida en progreso -->
                        <div id="avatarUploadSpinner" class="hidden absolute inset-0 bg-slate-950/80 z-20 flex items-center justify-center backdrop-blur-[1px]">
                            <div class="w-5 h-5 sm:w-6 sm:h-6 border-2 border-white/20 border-t-[#00d4ff] rounded-full animate-spin"></div>
                        </div>
                    </div>
                    <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-[#071026] z-10 pointer-events-none"></span>

                    <!-- Input file invisible para disparo inmediato -->
                    <input type="file" 
                           id="inputUserProfileAvatar" 
                           name="avatar" 
                           accept="image/png, image/jpeg, image/webp, image/gif" 
                           class="hidden" 
                           onchange="DkriptUserProfile.uploadAvatar(this)">
                </div>
                <div class="flex-1 min-w-0">
                    <h3 id="userProfileModalTitle" class="text-base sm:text-lg font-black text-white tracking-tight truncate">
                        {{ auth()->user()->profile?->full_name ?: auth()->user()->name }}
                    </h3>
                    <p id="userProfileModalEmail" class="text-xs text-slate-300 font-mono-code truncate mt-0.5">
                        {{ auth()->user()->email }}
                    </p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono-code {{ auth()->user()->isSuperAdmin() ? 'bg-amber-500/20 text-amber-300 border border-amber-400/40' : 'bg-[#00d4ff]/20 text-[#00d4ff] border border-[#00d4ff]/40' }}">
                            <i class="bi {{ auth()->user()->isSuperAdmin() ? 'bi-shield-check text-amber-400' : 'bi-person-check text-[#00d4ff]' }}"></i>
                            <span>{{ auth()->user()->role?->name ?: 'Operador' }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Cuenta Activa</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido Desplazable del Modal -->
        <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1">
            <!-- Formulario de Edición de Perfil de Usuario -->
            <form id="frmUserProfile" onsubmit="event.preventDefault(); DkriptUserProfile.confirmUpdate();">
                @csrf
                <div class="p-4 rounded-2xl border transition-all bg-slate-50 border-slate-200/80 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nombre(s) -->
                        <div class="relative">
                            <label for="profile_first_name" class="contour-label">
                                Nombre(s) <span class="text-rose-500">*</span>
                            </label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                <i class="bi bi-person text-base"></i>
                            </span>
                            <input type="text" 
                                   id="profile_first_name" 
                                   name="first_name" 
                                   value="{{ auth()->user()->profile?->first_name }}" 
                                   required 
                                   placeholder="Nombre"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white">
                        </div>

                        <!-- Apellido(s) -->
                        <div class="relative">
                            <label for="profile_last_name" class="contour-label">
                                Apellido(s) <span class="text-rose-500">*</span>
                            </label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                <i class="bi bi-person-badge text-base"></i>
                            </span>
                            <input type="text" 
                                   id="profile_last_name" 
                                   name="last_name" 
                                   value="{{ auth()->user()->profile?->last_name }}" 
                                   required 
                                   placeholder="Apellido"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white">
                        </div>

                        <!-- Nombre de Usuario -->
                        <div class="relative">
                            <label for="profile_name" class="contour-label">
                                Nombre de Usuario <span class="text-rose-500">*</span>
                            </label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                <i class="bi bi-at text-base"></i>
                            </span>
                            <input type="text" 
                                   id="profile_name" 
                                   name="name" 
                                   value="{{ auth()->user()->name }}" 
                                   required 
                                   placeholder="usuario"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white font-mono-code">
                        </div>

                        <!-- Teléfono de Contacto -->
                        <div class="relative">
                            <label for="profile_phone" class="contour-label">
                                Teléfono de Contacto
                            </label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                <i class="bi bi-telephone text-base"></i>
                            </span>
                            <input type="tel" 
                                   id="profile_phone" 
                                   name="phone" 
                                   value="{{ auth()->user()->profile?->phone }}" 
                                   placeholder="Ej. +52 55 1234 5678"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white">
                        </div>

                        <!-- Correo Electrónico Institucional -->
                        <div class="relative sm:col-span-2">
                            <label for="profile_email" class="contour-label">
                                Correo Electrónico <span class="text-rose-500">*</span>
                            </label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                <i class="bi bi-envelope text-base"></i>
                            </span>
                            <input type="email" 
                                   id="profile_email" 
                                   name="email" 
                                   value="{{ auth()->user()->email }}" 
                                   required 
                                   placeholder="usuario@dkript.com"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white font-mono-code">
                        </div>

                        <!-- Cambio de Contraseña (Acordeón estilo input integrado) -->
                        <div class="sm:col-span-2 relative">
                            <button type="button" 
                                    id="btnTogglePasswordChange"
                                    onclick="DkriptUserProfile.togglePasswordChangeSection()" 
                                    class="input-like-trigger w-full flex items-center justify-between text-left pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all cursor-pointer group shadow-xs">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 group-hover:text-slate-600 z-10 transition-colors">
                                    <i class="bi bi-shield-lock text-base"></i>
                                </span>
                                <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900 transition-colors">
                                    Cambiar Contraseña
                                </span>
                                <i id="iconTogglePasswordChange" class="bi bi-chevron-down text-xs text-slate-400 group-hover:text-slate-600 transition-transform duration-200"></i>
                            </button>
                            <div id="sectionPasswordChange" class="hidden pt-4 mt-1 space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="relative">
                                        <label for="profile_new_password" class="contour-label">
                                            Nueva Contraseña
                                        </label>
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                            <i class="bi bi-lock text-base"></i>
                                        </span>
                                        <input type="password" 
                                               id="profile_new_password" 
                                               name="new_password" 
                                               autocomplete="new-password" 
                                               placeholder="Mínimo 8 caracteres"
                                               class="w-full pl-10 pr-12 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white font-mono-code">
                                        <button type="button" 
                                                onclick="DkriptUserProfile.togglePasswordVisibility('profile_new_password', 'profile_eye_new')" 
                                                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer focus:outline-none" 
                                                title="Mostrar/Ocultar contraseña">
                                            <i id="profile_eye_new" class="bi bi-eye text-base"></i>
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <label for="profile_new_password_confirmation" class="contour-label">
                                            Confirmar Nueva Contraseña
                                        </label>
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                            <i class="bi bi-shield-check text-base"></i>
                                        </span>
                                        <input type="password" 
                                               id="profile_new_password_confirmation" 
                                               name="new_password_confirmation" 
                                               autocomplete="new-password" 
                                               placeholder="Repetir clave"
                                               class="w-full pl-10 pr-12 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white font-mono-code">
                                        <button type="button" 
                                                onclick="DkriptUserProfile.togglePasswordVisibility('profile_new_password_confirmation', 'profile_eye_new_confirm')" 
                                                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer focus:outline-none" 
                                                title="Mostrar/Ocultar contraseña">
                                            <i id="profile_eye_new_confirm" class="bi bi-eye text-base"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-400 flex items-center gap-1.5">
                                    <i class="bi bi-info-circle text-[#0062f5]"></i> Mínimo 8 caracteres combinando números y letras.
                                </p>

                                <!-- Validador Dinámico Interactivo en Tiempo Real -->
                                <div id="passwordValidationChecklist" class="pwd-checklist-container p-3 rounded-xl border border-slate-200/80 bg-slate-50/70 text-xs space-y-2 mt-2">
                                    <p class="text-[11px] font-semibold text-slate-700 flex items-center gap-1.5 pwd-checklist-title">
                                        <i class="bi bi-shield-check text-[#0062f5]"></i> Requisitos de seguridad para la nueva contraseña:
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-[11px]">
                                        <div id="pwd-rule-length" class="pwd-rule-item flex items-center gap-1.5 text-slate-500 transition-colors">
                                            <i class="pwd-rule-icon bi bi-x-circle-fill text-rose-500 text-xs"></i>
                                            <span class="pwd-rule-text">Mínimo 8 caracteres</span>
                                        </div>
                                        <div id="pwd-rule-uppercase" class="pwd-rule-item flex items-center gap-1.5 text-slate-500 transition-colors">
                                            <i class="pwd-rule-icon bi bi-x-circle-fill text-rose-500 text-xs"></i>
                                            <span class="pwd-rule-text">Una Mayúscula</span>
                                        </div>
                                        <div id="pwd-rule-lowercase" class="pwd-rule-item flex items-center gap-1.5 text-slate-500 transition-colors">
                                            <i class="pwd-rule-icon bi bi-x-circle-fill text-rose-500 text-xs"></i>
                                            <span class="pwd-rule-text">Una Minúscula</span>
                                        </div>
                                        <div id="pwd-rule-number" class="pwd-rule-item flex items-center gap-1.5 text-slate-500 transition-colors">
                                            <i class="pwd-rule-icon bi bi-x-circle-fill text-rose-500 text-xs"></i>
                                            <span class="pwd-rule-text">Un Número</span>
                                        </div>
                                        <div id="pwd-rule-symbol" class="pwd-rule-item flex items-center gap-1.5 text-slate-500 transition-colors">
                                            <i class="pwd-rule-icon bi bi-x-circle-fill text-rose-500 text-xs"></i>
                                            <span class="pwd-rule-text">Un Carácter Especial</span>
                                        </div>
                                        <div id="pwd-rule-match" class="pwd-rule-item flex items-center gap-1.5 text-slate-500 transition-colors">
                                            <i class="pwd-rule-icon bi bi-x-circle-fill text-rose-500 text-xs"></i>
                                            <span class="pwd-rule-text">Las contraseñas coinciden</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contraseña Actual (Reactiva: sólo visible al detectar cambios en el formulario) -->
                        <div id="containerCurrentPassword" class="relative sm:col-span-2 hidden transition-all duration-300">
                            <label for="profile_current_password" class="contour-label">
                                Contraseña Actual <span class="text-rose-500">*</span>
                            </label>
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 z-10">
                                <i class="bi bi-key text-base"></i>
                            </span>
                            <input type="password" 
                                   id="profile_current_password" 
                                   name="current_password" 
                                   autocomplete="current-password" 
                                   placeholder="Contraseña actual"
                                   class="w-full pl-10 pr-12 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-white font-mono-code">
                            <button type="button" 
                                    onclick="DkriptUserProfile.togglePasswordVisibility('profile_current_password', 'profile_eye_current')" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer focus:outline-none" 
                                    title="Mostrar/Ocultar contraseña">
                                <i id="profile_eye_current" class="bi bi-eye text-base"></i>
                            </button>
                        </div>

                        <!-- Miembro Desde (Informativo con estilo unificado a Sesión Segura) -->
                        <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-100 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0062f5] flex items-center justify-center text-sm flex-shrink-0">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800">Miembro Desde</p>
                                <p class="text-[11px] text-slate-600 font-medium truncate mt-0.5">{{ auth()->user()->created_at ? auth()->user()->created_at->format('d/m/Y') : 'Fecha de alta' }}</p>
                            </div>
                        </div>

                        <!-- Rol y Nivel RBAC (Informativo con estilo unificado a Sesión Segura) -->
                        <div class="p-3 rounded-xl bg-blue-50/50 border border-blue-100 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0062f5] flex items-center justify-center text-sm flex-shrink-0">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800">Rol y Nivel RBAC</p>
                                <p class="text-[11px] text-slate-600 font-medium truncate mt-0.5">{{ auth()->user()->role?->name ?: 'Operador' }}</p>
                            </div>
                        </div>
                    </div>
                </form>

            <div class="p-3.5 rounded-2xl bg-blue-50/50 border border-blue-100 text-xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0062f5] flex items-center justify-center text-sm flex-shrink-0">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-800">Sesión Segura RBAC Activa</p>
                    <p class="text-[11px] text-slate-600 font-medium truncate">Control de acceso y permisos granulares asignados al rol.</p>
                </div>
            </div>

            <!-- Estado de Seguridad: Autenticación de Dos Factores (2FA) -->
            <div class="p-4 rounded-2xl border transition-all {{ auth()->user()->hasTwoFactorEnabled() ? 'bg-emerald-50/50 border-emerald-200/80' : 'bg-slate-50 border-slate-200/80' }}">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl flex items-center justify-center text-sm {{ auth()->user()->hasTwoFactorEnabled() ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-200 text-slate-600' }}">
                            <i class="bi {{ auth()->user()->hasTwoFactorEnabled() ? 'bi-shield-fill-check' : 'bi-shield-slash' }}"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Autenticación en Dos Pasos (2FA)</h4>
                            <p class="text-[10px] text-slate-500 font-mono-code">RFC 6238 TOTP (Google Authenticator / Authy)</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono-code {{ auth()->user()->hasTwoFactorEnabled() ? 'bg-emerald-500/15 text-emerald-700 border border-emerald-400/40' : 'bg-amber-500/15 text-amber-700 border border-amber-400/40' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->hasTwoFactorEnabled() ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                        <span>{{ auth()->user()->hasTwoFactorEnabled() ? 'Activo' : 'Inactivo' }}</span>
                    </span>
                </div>
                
                <p class="text-[11px] text-slate-600 mb-3 leading-relaxed">
                    {{ auth()->user()->hasTwoFactorEnabled() 
                        ? 'Tu cuenta está protegida. Al iniciar sesión se solicitará un código de 6 dígitos de tu aplicación autenticadora.' 
                        : 'Añade una capa extra de protección a tu cuenta vinculando una app de autenticación móvil.' }}
                </p>

                <div class="flex items-center gap-2 flex-wrap">
                    @if(auth()->user()->hasTwoFactorEnabled())
                        <button type="button" 
                                onclick="DkriptTwoFactor.showRecoveryCodesModal()" 
                                class="px-3 py-1.5 text-[11px] font-bold text-[#0062f5] hover:text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all flex items-center gap-1.5 shadow-xs">
                            <i class="bi bi-key-fill text-xs"></i>
                            <span>Códigos de Recuperación</span>
                        </button>
                        <button type="button" 
                                onclick="DkriptTwoFactor.showDisableModal()" 
                                class="px-3 py-1.5 text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-all flex items-center gap-1.5 shadow-xs">
                            <i class="bi bi-shield-x text-xs"></i>
                            <span>Desactivar 2FA</span>
                        </button>
                    @else
                        <button type="button" 
                                onclick="DkriptTwoFactor.openSetupModal()" 
                                class="px-3.5 py-1.5 text-[11px] font-bold text-white bg-[#0062f5] hover:bg-[#004ecc] rounded-xl transition-all flex items-center gap-1.5 shadow-sm shadow-blue-500/20">
                            <i class="bi bi-qr-code-scan text-xs"></i>
                            <span>Configurar 2FA Ahora</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Gestión de Dispositivos & Sesiones Conectadas -->
            <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50 transition-all">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-blue-100 text-[#0062f5] flex items-center justify-center text-sm">
                            <i class="bi bi-hdd-network"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Dispositivos y Sesiones Activas</h4>
                            <p class="text-[10px] text-slate-500 font-mono-code">Navegadores, tablets y teléfonos vinculados</p>
                        </div>
                    </div>
                </div>
                
                <p class="text-[11px] text-slate-600 mb-3 leading-relaxed">
                    Monitorea los equipos donde tu cuenta está abierta y desconecta sesiones remotas de forma segura.
                </p>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            onclick="DkriptSessions.openSessionsModal()" 
                            class="px-3.5 py-1.5 text-[11px] font-bold text-[#0062f5] hover:text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all flex items-center gap-1.5 shadow-xs">
                        <i class="bi bi-display text-xs"></i>
                        <span>Administrar Dispositivos</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Acciones del Modal (Footer) -->
        <div class="p-4 px-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap sm:flex-nowrap flex-shrink-0">
            <button type="button" 
                    onclick="document.getElementById('userProfileLogoutForm')?.submit()" 
                    class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-all flex items-center gap-1.5 shadow-sm shadow-rose-600/30 min-h-[40px] cursor-pointer"
                    title="Cerrar sesión activa">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </button>

            <button type="button" 
                    id="btnSubmitUserProfile" 
                    onclick="DkriptUserProfile.confirmUpdate()" 
                    class="btn-dkript-primary px-5 py-2 text-xs font-bold min-h-[40px] cursor-pointer flex items-center gap-1.5">
                <i class="bi bi-check2-circle text-sm"></i>
                <span>Actualizar Perfil</span>
            </button>
        </div>

        <!-- Formulario oculto de Logout para evitar formularios anidados -->
        <form id="userProfileLogoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>
    </div>
</div>

<!-- Modal 1: Configurar 2FA (QR, Clave Secreta y Confirmación) -->
<div id="twoFactorSetupModal" 
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden"
     role="dialog" 
     aria-modal="true">
    <div class="modal-card bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 select-none relative">
        <div class="p-5 bg-gradient-to-br from-[#071026] via-[#0b1739] to-[#00246B] text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#00d4ff] animate-ping"></span>
                <span class="text-xs font-mono-code font-bold uppercase tracking-widest text-[#00d4ff]">Seguridad 2FA TOTP</span>
            </div>
            <button type="button" 
                    onclick="DkriptTwoFactor.hideModal('twoFactorSetupModal')" 
                    class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <!-- Paso 1: Escanear QR e ingresar código de prueba -->
        <div id="twoFactorSetupStep1" class="p-6 space-y-4">
            <div class="text-center">
                <h3 class="text-base font-black text-slate-900">Vincular Aplicación Autenticadora</h3>
                <p class="text-xs text-slate-500 mt-1">Escanea el código QR con Google Authenticator, Microsoft Authenticator o Authy.</p>
            </div>

            <!-- Contenedor QR -->
            <div class="flex flex-col items-center justify-center py-2">
                <div class="two-factor-qr-card">
                    <img id="twoFactorQrImage" src="" alt="Código QR 2FA" class="w-44 h-44 object-contain rounded-xl">
                </div>
            </div>

            <!-- Clave de Configuración Manual -->
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold uppercase text-slate-400">¿No puedes escanear? Clave manual</span>
                    <button type="button" 
                            onclick="DkriptTwoFactor.copySecretKey()" 
                            class="text-[11px] font-bold text-[#0062f5] hover:text-blue-700 flex items-center gap-1">
                        <i class="bi bi-clipboard"></i>
                        <span>Copiar</span>
                    </button>
                </div>
                <div id="twoFactorSecretKeyDisplay" class="font-mono-code text-xs font-extrabold text-slate-800 tracking-wider text-center py-1 select-all break-all">
                    --------------------------------
                </div>
            </div>

            <!-- Verificación con Token de 6 Dígitos -->
            <div>
                <label for="twoFactorConfirmCode" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 text-center">
                    Ingresa el código de 6 dígitos generado
                </label>
                <input type="text" 
                       id="twoFactorConfirmCode" 
                       maxlength="6" 
                       inputmode="numeric" 
                       pattern="[0-9]*"
                       placeholder="000000" 
                       class="w-full text-center font-mono-code font-black text-2xl tracking-[0.3em] py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl focus:bg-white focus:border-[#0062f5] focus:outline-none transition-all">
            </div>

            <button type="button" 
                    id="btnConfirmTwoFactorSetup" 
                    onclick="DkriptTwoFactor.confirmSetup()" 
                    class="btn-dkript-primary w-full py-3 text-xs">
                <span>Verificar y Activar</span>
                <i class="bi bi-shield-check"></i>
            </button>
        </div>

        <!-- Paso 2: Códigos de Recuperación de Emergencia Generados -->
        <div id="twoFactorSetupStep2" class="p-6 space-y-4 hidden">
            <div class="text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center text-xl mb-2">
                    <i class="bi bi-shield-fill-check"></i>
                </div>
                <h3 class="text-base font-black text-slate-900">¡2FA Activado Exitosamente!</h3>
                <p class="text-xs text-slate-500 mt-1">Guarda estos 8 códigos de recuperación en un lugar seguro. Te permitirán acceder si pierdes tu dispositivo móvil.</p>
            </div>

            <!-- Grilla de Códigos de Recuperación -->
            <div id="twoFactorSetupCodesGrid" class="grid grid-cols-2 gap-2 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                <!-- Chips inyectados dinámicamente -->
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        onclick="DkriptTwoFactor.copyRecoveryCodes()" 
                        class="flex-1 py-2 text-xs font-bold text-[#0062f5] bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <i class="bi bi-clipboard"></i>
                    <span>Copiar Todos</span>
                </button>
                <button type="button" 
                        onclick="DkriptTwoFactor.downloadRecoveryCodes()" 
                        class="flex-1 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <i class="bi bi-download"></i>
                    <span>Descargar .txt</span>
                </button>
            </div>

            <button type="button" 
                    onclick="DkriptTwoFactor.finishSetup()" 
                    class="btn-dkript-primary w-full py-2.5 text-xs">
                <span>Entendido, Finalizar</span>
                <i class="bi bi-check-lg text-sm"></i>
            </button>
        </div>
    </div>
</div>

<!-- Modal 2: Visualizar y Regenerar Códigos de Recuperación -->
<div id="twoFactorRecoveryModal" 
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden"
     role="dialog" 
     aria-modal="true">
    <div class="modal-card bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 select-none relative">
        <div class="p-5 bg-gradient-to-br from-[#071026] via-[#0b1739] to-[#00246B] text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#00d4ff]"></span>
                <span class="text-xs font-mono-code font-bold uppercase tracking-widest text-[#00d4ff]">Códigos de Recuperación</span>
            </div>
            <button type="button" 
                    onclick="DkriptTwoFactor.hideModal('twoFactorRecoveryModal')" 
                    class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="text-center">
                <h3 class="text-base font-black text-slate-900">Códigos de Emergencia Disponibles</h3>
                <p class="text-xs text-slate-500 mt-1">Cada código es de un solo uso. Si utilizas un código, se invalidará automáticamente.</p>
            </div>

            <!-- Grilla de Códigos de Recuperación Vigentes -->
            <div id="twoFactorViewCodesGrid" class="grid grid-cols-2 gap-2 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                <!-- Inyectados dinámicamente -->
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        onclick="DkriptTwoFactor.copyRecoveryCodes()" 
                        class="flex-1 py-2 text-xs font-bold text-[#0062f5] bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <i class="bi bi-clipboard"></i>
                    <span>Copiar Códigos</span>
                </button>
                <button type="button" 
                        onclick="DkriptTwoFactor.downloadRecoveryCodes()" 
                        class="flex-1 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <i class="bi bi-download"></i>
                    <span>Descargar .txt</span>
                </button>
            </div>

            <!-- Regenerar Códigos -->
            <div class="pt-3 border-t border-slate-100 space-y-2">
                <label for="twoFactorRegeneratePassword" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                    Regenerar nuevo lote (invalida los anteriores)
                </label>
                <div class="flex items-center gap-2">
                    <input type="password" 
                           id="twoFactorRegeneratePassword" 
                           placeholder="Contraseña actual" 
                           class="flex-1 px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-[#0062f5] focus:outline-none">
                    <button type="button" 
                            onclick="DkriptTwoFactor.regenerateRecoveryCodes()" 
                            class="px-3 py-2 text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition-all flex items-center gap-1">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Regenerar</span>
                    </button>
                </div>
            </div>

            <button type="button" 
                    onclick="DkriptTwoFactor.hideModal('twoFactorRecoveryModal')" 
                    class="w-full py-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal 3: Desactivar 2FA -->
<div id="twoFactorDisableModal" 
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden"
     role="dialog" 
     aria-modal="true">
    <div class="modal-card bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 select-none relative">
        <div class="p-5 bg-gradient-to-br from-rose-950 via-rose-900 to-[#071026] text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-ping"></span>
                <span class="text-xs font-mono-code font-bold uppercase tracking-widest text-rose-300">Desactivar 2FA</span>
            </div>
            <button type="button" 
                    onclick="DkriptTwoFactor.hideModal('twoFactorDisableModal')" 
                    class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="text-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 mx-auto flex items-center justify-center text-xl mb-2">
                    <i class="bi bi-shield-x"></i>
                </div>
                <h3 class="text-base font-black text-slate-900">¿Desactivar la Protección 2FA?</h3>
                <p class="text-xs text-slate-500 mt-1">Al desactivarlo, tu cuenta solo estará protegida por contraseña.</p>
            </div>

            <div>
                <label for="twoFactorDisablePassword" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Confirma tu contraseña actual
                </label>
                <input type="password" 
                       id="twoFactorDisablePassword" 
                       placeholder="Ingresa tu contraseña" 
                       class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-rose-500 focus:outline-none transition-all">
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        onclick="DkriptTwoFactor.hideModal('twoFactorDisableModal')" 
                        class="flex-1 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    Cancelar
                </button>
                <button type="button" 
                        id="btnConfirmDisable2Fa" 
                        onclick="DkriptTwoFactor.confirmDisable()" 
                        class="flex-1 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-all flex items-center justify-center gap-1.5 shadow-sm shadow-rose-600/30">
                    <span>Desactivar</span>
                    <i class="bi bi-shield-x"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 4: Gestor de Dispositivos y Sesiones Activas -->
<div id="userSessionsModal" 
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden"
     role="dialog" 
     aria-modal="true">
    <div class="modal-card bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 select-none relative">
        <div class="p-5 bg-gradient-to-br from-[#071026] via-[#0b1739] to-[#00246B] text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#00d4ff] animate-ping"></span>
                <span class="text-xs font-mono-code font-bold uppercase tracking-widest text-[#00d4ff]">Dispositivos Conectados</span>
                <span id="userSessionsCount" class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-mono-code font-extrabold bg-white/10 text-cyan-200 border border-cyan-400/30">
                    --
                </span>
            </div>
            <button type="button" 
                    onclick="DkriptSessions.hideModal('userSessionsModal')" 
                    class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <h3 class="text-base font-black text-slate-900">Control de Sesiones Activas</h3>
                <p class="text-xs text-slate-500 mt-1">Dispositivos y navegadores que tienen una sesión abierta en tu cuenta. Puedes revocar el acceso a cualquier equipo no reconocido.</p>
            </div>

            <!-- Lista de Dispositivos -->
            <div id="userSessionsList" class="space-y-2.5 max-h-[340px] overflow-y-auto custom-scrollbar pr-1">
                <!-- Inyectado dinámicamente vía DkriptSessions.loadSessions() -->
            </div>

            <!-- Acción Masiva: Cerrar en otros dispositivos -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                <button type="button" 
                        onclick="DkriptSessions.promptLogoutOthers()" 
                        class="px-3.5 py-2 text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 rounded-xl transition-all shadow-xs flex items-center gap-1.5">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar sesión en otros dispositivos</span>
                </button>

                <button type="button" 
                        onclick="DkriptSessions.hideModal('userSessionsModal')" 
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 5: Confirmar Cierre en Otros Dispositivos (Requiere Contraseña) -->
<div id="logoutOthersConfirmModal" 
     class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm transition-all duration-300 opacity-0 pointer-events-none hidden"
     role="dialog" 
     aria-modal="true">
    <div class="modal-card bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-slate-100 transform transition-all duration-300 scale-95 select-none relative">
        <div class="p-5 bg-gradient-to-br from-rose-950 via-rose-900 to-[#071026] text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-ping"></span>
                <span class="text-xs font-mono-code font-bold uppercase tracking-widest text-rose-300">Seguridad de Acceso</span>
            </div>
            <button type="button" 
                    onclick="DkriptSessions.hideModal('logoutOthersConfirmModal')" 
                    class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="text-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 mx-auto flex items-center justify-center text-xl mb-2">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h3 class="text-base font-black text-slate-900">¿Cerrar Todas las Otras Sesiones?</h3>
                <p class="text-xs text-slate-500 mt-1">Se cerrará la sesión en todos los demás dispositivos y smartphones de inmediato excepto en este.</p>
            </div>

            <div>
                <label for="logoutOthersPassword" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Confirma tu contraseña actual
                </label>
                <input type="password" 
                       id="logoutOthersPassword" 
                       placeholder="Ingresa tu contraseña" 
                       class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-rose-500 focus:outline-none transition-all">
            </div>

            <div class="flex items-center gap-2">
                <button type="button" 
                        onclick="DkriptSessions.hideModal('logoutOthersConfirmModal')" 
                        class="flex-1 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    Cancelar
                </button>
                <button type="button" 
                        id="btnConfirmLogoutOthers" 
                        onclick="DkriptSessions.confirmLogoutOthers()" 
                        class="flex-1 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-all flex items-center justify-center gap-1.5 shadow-sm shadow-rose-600/30">
                    <span>Desconectar</span>
                    <i class="bi bi-shield-x"></i>
                </button>
            </div>
        </div>
    </div>
</div>


