<!-- BARRA SUPERIOR DE NAVEGACIÓN FLOTANTE (TOP DOCK SETTINGS HUB) -->
<div class="parameter-top-dock dock-fixed-top w-full mb-8 overflow-visible" role="region" aria-label="Navegación de Parámetros">
    <!-- Riel de 7 Iconos Flotantes Alineados a la Izquierda (Sin scroll interno, tamaño uniforme, tooltips flotantes superpuestos) -->
    <div class="flex items-center justify-start gap-2.5 sm:gap-3.5 py-1 relative overflow-visible" role="tablist">
        
        <!-- 1. General -->
        <button type="button" 
                onclick="DkriptParameters.switchTab('tab-general')" 
                data-tab-target="tab-general" 
                data-tab-name="General"
                data-tab-desc="Parámetros globales del sistema, sesiones e inactividad"
                data-tab-icon="bi-sliders"
                role="tab"
                aria-label="General"
                aria-selected="true"
                class="parameter-dock-btn relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl transition-all cursor-pointer is-active">
            <i class="bi bi-sliders"></i>
            
            <!-- Tooltip Flotante para Desktop (Superpuesto al div inferior sin recortes) -->
            <div class="parameter-dock-tooltip">
                <span class="font-extrabold text-white block text-xs tracking-wide">General</span>
                <span class="text-[10px] text-slate-300 block font-normal whitespace-nowrap">Parámetros globales e inactividad</span>
            </div>
        </button>

        <!-- 2. Modales -->
        <button type="button" 
                onclick="DkriptParameters.switchTab('tab-modals')" 
                data-tab-target="tab-modals" 
                data-tab-name="Modales"
                data-tab-desc="Estilo visual de ventanas modales y diálogos"
                data-tab-icon="bi-window-stack"
                role="tab"
                aria-label="Modales"
                aria-selected="false"
                class="parameter-dock-btn relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl transition-all cursor-pointer">
            <i class="bi bi-window-stack"></i>
            
            <div class="parameter-dock-tooltip">
                <span class="font-extrabold text-white block text-xs tracking-wide">Modales</span>
                <span class="text-[10px] text-slate-300 block font-normal whitespace-nowrap">Estilo visual de ventanas (4 temas)</span>
            </div>
        </button>

        <!-- 3. Identidad -->
        <button type="button" 
                onclick="DkriptParameters.switchTab('tab-branding')" 
                data-tab-target="tab-branding" 
                data-tab-name="Identidad"
                data-tab-desc="Identidad corporativa, logotipos y catálogo multimedia"
                data-tab-icon="bi-palette"
                role="tab"
                aria-label="Identidad"
                aria-selected="false"
                class="parameter-dock-btn relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl transition-all cursor-pointer">
            <i class="bi bi-palette"></i>
            
            <div class="parameter-dock-tooltip">
                <span class="font-extrabold text-white block text-xs tracking-wide">Identidad</span>
                <span class="text-[10px] text-slate-300 block font-normal whitespace-nowrap">Logotipo, catálogo y marca</span>
            </div>
        </button>

        <!-- 4. Errores -->
        <button type="button" 
                onclick="DkriptParameters.switchTab('tab-errors')" 
                data-tab-target="tab-errors" 
                data-tab-name="Errores"
                data-tab-desc="Páginas de error cinemáticas Drypt y emulador HTTP"
                data-tab-icon="bi-film"
                role="tab"
                aria-label="Errores"
                aria-selected="false"
                class="parameter-dock-btn relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl transition-all cursor-pointer">
            <i class="bi bi-film"></i>
            
            <div class="parameter-dock-tooltip">
                <span class="font-extrabold text-white block text-xs tracking-wide">Errores</span>
                <span class="text-[10px] text-slate-300 block font-normal whitespace-nowrap">Cinemáticas y emulador Drypt</span>
            </div>
        </button>

        <!-- 5. Telefonía -->
        <button type="button" 
                onclick="DkriptParameters.switchTab('tab-telephony')" 
                data-tab-target="tab-telephony" 
                data-tab-name="Telefonía"
                data-tab-desc="Configuración de WhatsApp (Meta API) y SMS (Twilio)"
                data-tab-icon="bi-chat-dots-fill"
                role="tab"
                aria-label="Telefonía"
                aria-selected="false"
                class="parameter-dock-btn relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl transition-all cursor-pointer">
            <i class="bi bi-chat-dots-fill"></i>
            
            <div class="parameter-dock-tooltip">
                <span class="font-extrabold text-white block text-xs tracking-wide">Telefonía</span>
                <span class="text-[10px] text-slate-300 block font-normal whitespace-nowrap">Meta Cloud API y Twilio</span>
            </div>
        </button>

        <!-- 6. Email -->
        <button type="button" 
                onclick="DkriptParameters.switchTab('tab-mail')" 
                data-tab-target="tab-mail" 
                data-tab-name="Email"
                data-tab-desc="Servidor de correo saliente (SMTP) y remitente corporativo"
                data-tab-icon="bi-envelope-at-fill"
                role="tab"
                aria-label="Email"
                aria-selected="false"
                class="parameter-dock-btn relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl transition-all cursor-pointer">
            <i class="bi bi-envelope-at-fill"></i>
            
            <div class="parameter-dock-tooltip">
                <span class="font-extrabold text-white block text-xs tracking-wide">Email</span>
                <span class="text-[10px] text-slate-300 block font-normal whitespace-nowrap">Protocolo SMTP y remitente</span>
            </div>
        </button>

        <!-- 7. Mantenimiento -->
        <button type="button" 
                onclick="DkriptParameters.switchTab('tab-maintenance')" 
                data-tab-target="tab-maintenance" 
                data-tab-name="Mantenimiento"
                data-tab-desc="Herramientas de optimización, caché y respaldos"
                data-tab-icon="bi-tools"
                role="tab"
                aria-label="Mantenimiento"
                aria-selected="false"
                class="parameter-dock-btn relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center text-lg sm:text-xl transition-all cursor-pointer">
            <i class="bi bi-tools"></i>
            
            <div class="parameter-dock-tooltip">
                <span class="font-extrabold text-white block text-xs tracking-wide">Mantenimiento</span>
                <span class="text-[10px] text-slate-300 block font-normal whitespace-nowrap">Caché y Centro de Respaldos</span>
            </div>
        </button>

    </div>
</div>
