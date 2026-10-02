<!-- PESTAÑA 5: Configuración de Telefonía (SMS y WhatsApp) -->
<div id="tab-telephony" class="parameter-tab-pane space-y-6 hidden">
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="icon-squircle-cobalt w-12 h-12 text-2xl flex-shrink-0">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Configuración de Telefonía (SMS y WhatsApp)</h3>
                    <p class="text-xs text-slate-500">Administre el canal y las credenciales para validación de usuarios y recuperación de contraseñas vía OTP</p>
                </div>
            </div>
            <button type="button" 
                    id="btnTestWhatsApp"
                    onclick="DkriptParameters.testWhatsAppConnection('{{ route('parameters.test-whatsapp') }}', '{{ csrf_token() }}', '{{ auth()->user()?->profile?->phone ?: '5215512345678' }}')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#25D366] hover:bg-[#20ba59] text-white text-xs font-bold shadow-sm transition-all active:scale-95">
                <i class="bi bi-whatsapp text-white text-sm"></i>
                <span>Probar Conexión WhatsApp</span>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Proveedor de Telefonía -->
            <div>
                <label for="sms_provider" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                    Proveedor de Mensajería Activo <span class="text-rose-500">*</span>
                </label>
                <select name="sms_provider" 
                        id="sms_provider" 
                        onchange="const val = this.value; document.getElementById('twilioConfigBox').classList.toggle('hidden', val !== 'twilio'); document.getElementById('metaWhatsAppConfigBox').classList.toggle('hidden', val !== 'meta_whatsapp');"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white font-medium text-slate-800">
                    <option value="log" {{ old('sms_provider', $parameter->sms_provider ?? 'log') === 'log' ? 'selected' : '' }}>
                        Log Simulator (100% Gratuito / Desarrollo & Pruebas)
                    </option>
                    <option value="meta_whatsapp" {{ old('sms_provider', $parameter->sms_provider ?? 'log') === 'meta_whatsapp' ? 'selected' : '' }}>
                        Meta WhatsApp Cloud API Oficial (Sin intermediarios / Meta Graph API)
                    </option>
                    <option value="twilio" {{ old('sms_provider', $parameter->sms_provider ?? 'log') === 'twilio' ? 'selected' : '' }}>
                        Twilio Cloud API (Producción / SMS & WhatsApp)
                    </option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1">
                    <i class="bi bi-info-circle"></i> En modo simulador, los códigos se envían al log y se muestran en la pantalla de verificación.
                </p>
            </div>

            <!-- Info Card de Estado -->
            <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/60 flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0062f5] flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="bi bi-shield-check text-base"></i>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Arquitectura Híbrida Inteligente</h4>
                    <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                        Diseñado para operar en entornos locales sin costos de infraestructura. Cuando esté listo para producción, conecte directamente la API Oficial de Meta WhatsApp Cloud o Twilio.
                    </p>
                </div>
            </div>
        </div>

        <!-- Contenedor de Parámetros de Meta WhatsApp Cloud API -->
        <div id="metaWhatsAppConfigBox" class="{{ old('sms_provider', $parameter->sms_provider ?? 'log') === 'meta_whatsapp' ? '' : 'hidden' }} mt-6 pt-6 border-t border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="bi bi-whatsapp text-[#25D366]"></i>
                    <span>Credenciales de Meta WhatsApp Cloud API (Graph API)</span>
                </h4>
                <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                    Oficial Meta
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Phone Number ID -->
                <div>
                    <label for="whatsapp_phone_number_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        WhatsApp Phone Number ID
                    </label>
                    <input type="text" 
                           name="whatsapp_phone_number_id" 
                           id="whatsapp_phone_number_id" 
                           value="{{ old('whatsapp_phone_number_id', $parameter->whatsapp_phone_number_id) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                           placeholder="Ej. 109876543210987">
                    <p class="text-[11px] text-slate-400 mt-1">Identificador numérico asignado en Meta Developers.</p>
                </div>

                <!-- Business Account ID (WABA ID) -->
                <div>
                    <label for="whatsapp_business_account_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        WhatsApp Business Account ID (WABA)
                    </label>
                    <input type="text" 
                           name="whatsapp_business_account_id" 
                           id="whatsapp_business_account_id" 
                           value="{{ old('whatsapp_business_account_id', $parameter->whatsapp_business_account_id) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                           placeholder="Ej. 987654321098765">
                    <p class="text-[11px] text-slate-400 mt-1">ID de cuenta comercial en Meta Business Suite.</p>
                </div>

                <!-- Meta Access Token -->
                <div class="md:col-span-2">
                    <label for="whatsapp_access_token" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Meta Permanent Access Token (Bearer Token)
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="whatsapp_access_token" 
                               id="whatsapp_access_token" 
                               value=""
                               autocomplete="new-password"
                               class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                               placeholder="{{ !empty($parameter->whatsapp_access_token) ? '•••••••••••••••• (Configurado)' : 'EAAGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx...' }}">
                        <button type="button" 
                                onclick="const inp = document.getElementById('whatsapp_access_token'); const isPass = inp.type === 'password'; inp.type = isPass ? 'text' : 'password'; this.querySelector('i').className = isPass ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                            <i class="bi bi-eye-fill text-sm"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                        <i class="bi bi-info-circle"></i> Dejar vacío para conservar el token actual. Token de acceso permanente con permiso <code>whatsapp_business_messaging</code>.
                    </p>
                </div>

                <!-- API Version -->
                <div>
                    <label for="whatsapp_api_version" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Versión de Graph API
                    </label>
                    <input type="text" 
                           name="whatsapp_api_version" 
                           id="whatsapp_api_version" 
                           value="{{ old('whatsapp_api_version', $parameter->whatsapp_api_version ?? 'v20.0') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                           placeholder="v20.0">
                </div>
            </div>
        </div>

        <!-- Contenedor de Parámetros de Twilio -->
        <div id="twilioConfigBox" class="{{ old('sms_provider', $parameter->sms_provider ?? 'log') === 'twilio' ? '' : 'hidden' }} mt-6 pt-6 border-t border-slate-100">
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="bi bi-key-fill text-[#0062f5]"></i>
                <span>Credenciales de Twilio REST API</span>
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Account SID -->
                <div>
                    <label for="twilio_account_sid" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Twilio Account SID
                    </label>
                    <input type="text" 
                           name="twilio_account_sid" 
                           id="twilio_account_sid" 
                           value="{{ old('twilio_account_sid', $parameter->twilio_account_sid) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                           placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                </div>

                <!-- Auth Token -->
                <div>
                    <label for="twilio_auth_token" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Twilio Auth Token
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="twilio_auth_token" 
                               id="twilio_auth_token" 
                               value=""
                               autocomplete="new-password"
                               class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-mono text-slate-800" 
                               placeholder="{{ !empty($parameter->twilio_auth_token) ? '•••••••••••••••• (Configurado)' : '••••••••••••••••••••••••••••••••' }}">
                        <button type="button" 
                                onclick="const inp = document.getElementById('twilio_auth_token'); const isPass = inp.type === 'password'; inp.type = isPass ? 'text' : 'password'; this.querySelector('i').className = isPass ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                            <i class="bi bi-eye-fill text-sm"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                        <i class="bi bi-info-circle"></i> Dejar vacío para conservar el token actual.
                    </p>
                </div>

                <!-- Twilio Phone Number (SMS) -->
                <div>
                    <label for="twilio_phone_number" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Número Emisor Twilio (SMS)
                    </label>
                    <input type="text" 
                           name="twilio_phone_number" 
                           id="twilio_phone_number" 
                           value="{{ old('twilio_phone_number', $parameter->twilio_phone_number) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium text-slate-800" 
                           placeholder="+1234567890">
                </div>

                <!-- Twilio WhatsApp Number -->
                <div>
                    <label for="twilio_whatsapp_number" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                        Número Emisor Twilio (WhatsApp)
                    </label>
                    <input type="text" 
                           name="twilio_whatsapp_number" 
                           id="twilio_whatsapp_number" 
                           value="{{ old('twilio_whatsapp_number', $parameter->twilio_whatsapp_number) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium text-slate-800" 
                           placeholder="+14155238886">
                </div>
            </div>
        </div>
    </div>
</div>
