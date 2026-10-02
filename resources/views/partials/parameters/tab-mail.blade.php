<!-- PESTAÑA 6: Configuración del Servidor de Correo (SMTP) -->
<div id="tab-mail" class="parameter-tab-pane space-y-6 hidden">
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="icon-squircle-cobalt w-12 h-12 text-2xl flex-shrink-0">
                    <i class="bi bi-envelope-at-fill"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Configuración del Servidor de Correo (SMTP)</h3>
                    <p class="text-xs text-slate-500">Gestione el despacho de correos corporativos, enlaces de recuperación y notificaciones</p>
                </div>
            </div>
            <button type="button" 
                    id="btnTestSmtp"
                    onclick="DkriptParameters.testSmtpConnection('{{ route('parameters.test-smtp') }}', '{{ csrf_token() }}', '{{ $parameter->contact_email ?: (auth()->user()?->email ?: config('mail.from.address', 'notificaciones@ejemplo.com')) }}')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition-all">
                <i class="bi bi-send-check-fill text-[#00d4ff]"></i>
                <span>Probar Conexión SMTP</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Manejador de Correo (Mailer) -->
            <div>
                <label for="mail_mailer" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Controlador de Envío <span class="text-rose-500">*</span>
                </label>
                <select name="mail_mailer" 
                        id="mail_mailer" 
                        onchange="const isSmtp = this.value === 'smtp'; document.getElementById('smtpConfigBox').classList.toggle('hidden', !isSmtp);"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white font-medium text-slate-800">
                    <option value="log" {{ old('mail_mailer', $parameter->mail_mailer ?? 'log') === 'log' ? 'selected' : '' }}>
                        Log Simulator (100% Gratuito / Desarrollo & Pruebas)
                    </option>
                    <option value="smtp" {{ old('mail_mailer', $parameter->mail_mailer ?? 'log') === 'smtp' ? 'selected' : '' }}>
                        Servidor SMTP Estándar (Producción / Gmail, Amazon SES, Mailgun, etc.)
                    </option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                    <i class="bi bi-info-circle"></i> En modo simulador, los correos se registran en los logs de Laravel y generan enlaces de prueba inmediatos.
                </p>
            </div>

            <!-- Info Card de Estado SMTP -->
            <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/60 flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0062f5] flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="bi bi-shield-check text-base"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Cifrado y Despacho Seguro</h4>
                    <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                        Soporta protocolos TLS y SSL con credenciales cifradas. Al cambiar a SMTP, use el botón superior para verificar que el puerto y host respondan con éxito.
                    </p>
                </div>
            </div>
        </div>

        <!-- Contenedor de Opciones SMTP -->
        <div id="smtpConfigBox" class="{{ old('mail_mailer', $parameter->mail_mailer ?? 'log') === 'smtp' ? '' : 'hidden' }} mt-6 pt-6 border-t border-slate-100">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="bi bi-hdd-network-fill text-[#0062f5]"></i>
                <span>Parámetros de Servidor Saliente</span>
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Host -->
                <div>
                    <label for="mail_host" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Host / Servidor SMTP
                    </label>
                    <input type="text" 
                           name="mail_host" 
                           id="mail_host" 
                           value="{{ old('mail_host', $parameter->mail_host ?? '127.0.0.1') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                           placeholder="smtp.mailtrap.io">
                </div>

                <!-- Puerto -->
                <div>
                    <label for="mail_port" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Puerto SMTP
                    </label>
                    <input type="number" 
                           name="mail_port" 
                           id="mail_port" 
                           value="{{ old('mail_port', $parameter->mail_port ?? 587) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                           placeholder="587">
                </div>

                <!-- Cifrado -->
                <div>
                    <label for="mail_encryption" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Tipo de Cifrado
                    </label>
                    <select name="mail_encryption" 
                            id="mail_encryption" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white font-medium text-slate-800">
                        <option value="tls" {{ old('mail_encryption', $parameter->mail_encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (Recomendado)</option>
                        <option value="ssl" {{ old('mail_encryption', $parameter->mail_encryption ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ old('mail_encryption', $parameter->mail_encryption ?? 'tls') === 'none' ? 'selected' : '' }}>Sin Cifrado (none)</option>
                    </select>
                </div>

                <!-- Usuario -->
                <div>
                    <label for="mail_username" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Usuario SMTP
                    </label>
                    <input type="text" 
                           name="mail_username" 
                           id="mail_username" 
                           value="{{ old('mail_username', $parameter->mail_username) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                           placeholder="usuario@dominio.com">
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="mail_password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Contraseña SMTP
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="mail_password" 
                               id="mail_password" 
                               value=""
                               autocomplete="new-password"
                               class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                               placeholder="{{ !empty($parameter->mail_password) ? '•••••••••••••••• (Configurado)' : '••••••••••••••••' }}">
                        <button type="button" 
                                onclick="const inp = document.getElementById('mail_password'); const isPass = inp.type === 'password'; inp.type = isPass ? 'text' : 'password'; this.querySelector('i').className = isPass ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                            <i class="bi bi-eye-fill text-sm"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                        <i class="bi bi-info-circle"></i> Dejar vacío para conservar la contraseña actual.
                    </p>
                </div>

                <!-- Remitente (From Address) -->
                <div>
                    <label for="mail_from_address" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Correo Remitente (From)
                    </label>
                    <input type="email" 
                           name="mail_from_address" 
                           id="mail_from_address" 
                           value="{{ old('mail_from_address', $parameter->mail_from_address ?? config('mail.from.address', 'noreply@dkript.com')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium text-slate-800" 
                           placeholder="{{ config('mail.from.address', 'noreply@dkript.com') }}">
                </div>

                <!-- Nombre Remitente (From Name) -->
                <div class="md:col-span-3">
                    <label for="mail_from_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Nombre del Remitente
                    </label>
                    <input type="text" 
                           name="mail_from_name" 
                           id="mail_from_name" 
                           value="{{ old('mail_from_name', $parameter->mail_from_name ?? ($parameter->system_name ?? config('app.name', 'Dkript Core'))) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium text-slate-800" 
                           placeholder="{{ $parameter->system_name ?? config('app.name', 'Dkript Core') }}">
                </div>
            </div>
        </div>
    </div>
</div>
