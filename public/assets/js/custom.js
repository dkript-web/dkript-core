/**
 * Dkript Inc. Enterprise Starter Kit - Core Client JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Exportación a Excel Nativo en Cliente (#btnExelTop para #tbExel)
    const btnExelTop = document.getElementById('btnExelTop');
    if (btnExelTop) {
        btnExelTop.addEventListener('click', (e) => {
            e.preventDefault();
            exportTableToExcel('tbExel', 'reporte_export_' + new Date().toISOString().slice(0, 10));
        });
    }

    // 2. Buscador en Tiempo Real de Catálogos (#frmHeaderSearch)
    const searchInput = document.getElementById('frmHeaderSearch');
    const searchClearBtn = document.getElementById('btnSearchClear');

    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            const term = searchInput.value.toLowerCase().trim();
            const table = document.getElementById('tbExel') || document.querySelector('.catalog-data-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });

            if (searchClearBtn) {
                searchClearBtn.style.display = term.length > 0 ? 'inline-flex' : 'none';
            }
        });

        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('keyup'));
                searchClearBtn.style.display = 'none';
            });
        }
    }

    // 3. Manejo Global de Tecla Escape para Modales
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-wrapper:not(.hidden)').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // 4. Interceptor Global para Formularios con Confirmación (data-confirm)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const confirmMsg = form.getAttribute('data-confirm');
        if (confirmMsg && !form.dataset.confirmed) {
            e.preventDefault();
            DkriptModal.confirm(confirmMsg, 'Confirmar Acción', {
                type: 'warning',
                confirmText: 'Sí, continuar',
                cancelText: 'Cancelar'
            }).then((confirmed) => {
                if (confirmed) {
                    form.dataset.confirmed = 'true';
                    if (form.matches('form[data-ajax="true"], form.ajax-form')) {
                        DkriptForm.ajax(form);
                    } else {
                        form.submit();
                    }
                }
            });
            return;
        }

        // 5. Interceptor Global para Formularios AJAX (data-ajax="true" o .ajax-form)
        if (form.matches('form[data-ajax="true"], form.ajax-form')) {
            e.preventDefault();
            DkriptForm.ajax(form);
        }
    });
});

/**
 * Sistema de Modal Universal Dkript Inc.
 * Soporta 4 estilos visuales: corporate, glassmorphism, window, minimal.
 * Sustituye los alerts nativos del navegador por una ventana corporativa estilizada.
 */
const DkriptModal = {
    show(options = {}) {
        return new Promise((resolve) => {
            const modal = document.getElementById('globalSystemModal');
            const card = document.getElementById('globalSystemModalCard');
            const iconContainer = document.getElementById('globalSystemModalIconContainer');
            const icon = document.getElementById('globalSystemModalIcon');
            const title = document.getElementById('globalSystemModalTitle');
            const badge = document.getElementById('globalSystemModalBadge');
            const message = document.getElementById('globalSystemModalMessage');
            const cancelBtn = document.getElementById('globalSystemModalCancelBtn');
            const confirmBtn = document.getElementById('globalSystemModalConfirmBtn');
            const windowBar = document.getElementById('globalSystemModalWindowBar');
            const windowTitle = document.getElementById('globalSystemModalWindowTitle');
            const windowCloseDot = document.getElementById('windowCloseDot');
            const modalBody = document.getElementById('globalSystemModalBody');
            const modalFooter = document.getElementById('globalSystemModalFooter');

            if (!modal || !card) {
                // Fallback seguro
                if (options.isConfirm) {
                    resolve(window.confirm(options.message));
                } else {
                    console.warn(options.message);
                    resolve(true);
                }
                return;
            }

            // 1. Determinar el estilo visual activo (corporate, glassmorphism, window, minimal)
            const style = options.style || modal.getAttribute('data-default-style') || 'corporate';
            const type = options.type || 'info'; // 'error', 'warning', 'success', 'info'
            const isConfirm = !!options.isConfirm;

            title.textContent = options.title || (type === 'error' ? 'Error del Sistema' : type === 'warning' ? 'Atención Requerida' : type === 'success' ? 'Operación Exitosa' : 'Notificación');
            message.innerHTML = options.message || '';

            // Resetear clases base
            modal.className = "fixed inset-0 z-[9999] flex items-center justify-center p-4 transition-all duration-300 opacity-0 pointer-events-none";
            const sizeMap = {
                sm: 'max-w-sm',
                md: 'max-w-md',
                lg: 'max-w-lg',
                xl: 'max-w-2xl',
                '2xl': 'max-w-4xl'
            };
            const sizeClass = sizeMap[options.size] || 'max-w-md';
            card.className = `${sizeClass} w-full overflow-hidden transform transition-all duration-300 scale-95 select-none`;
            if (modalBody) modalBody.className = "p-6 pb-5 flex items-start gap-4";
            if (modalFooter) {
                modalFooter.className = "p-4 px-6 border-t flex items-center justify-end gap-2.5";
                if (options.showButtons === false) {
                    modalFooter.classList.add('hidden');
                } else {
                    modalFooter.classList.remove('hidden');
                }
            }
            if (windowBar) windowBar.classList.add('hidden');

            // 2. Aplicar estética según estilo visual
            if (style === 'glassmorphism') {
                // ESTILO 2: Drypt Cyber-Glass (Traslúcido estilo Windows/macOS/Linux acrylic con refracción)
                modal.classList.add('glass-modal-backdrop');
                card.classList.add('glass-modal-card');
                if (modalBody) modalBody.classList.add('glass-modal-body');
                if (modalFooter) modalFooter.classList.add('glass-modal-footer');
                title.className = 'text-base font-black tracking-tight truncate glass-modal-title';
                message.className = 'text-xs mt-2 leading-relaxed break-words font-medium glass-modal-message';
                cancelBtn.className = 'px-4 py-2.5 rounded-xl border border-cyan-500/30 bg-slate-900/60 text-cyan-200 text-xs font-bold hover:bg-slate-800/80 hover:text-white transition-colors backdrop-blur-sm';
            } else if (style === 'window') {
                // ESTILO 3: Classic Studio Window (Ventana OS de software con semáforo y barra)
                modal.classList.add('bg-slate-950/70', 'backdrop-blur-sm');
                card.classList.add('bg-[#0b1329]', 'rounded-2xl', 'shadow-2xl', 'border-2', 'border-[#1e293b]', 'text-slate-200');
                if (windowBar) {
                    windowBar.classList.remove('hidden');
                    if (windowTitle) windowTitle.textContent = options.title ? options.title.toLowerCase().replace(/\s+/g, '-') + '.exe' : 'system.modal';
                    if (windowCloseDot) {
                        windowCloseDot.onclick = () => closeModalHandler(false);
                    }
                }
                if (modalBody) modalBody.classList.add('bg-[#0a1224]');
                if (modalFooter) modalFooter.classList.add('bg-[#071026]', 'border-[#1e293b]');
                title.className = 'text-sm font-black text-white tracking-tight truncate font-mono';
                message.className = 'text-xs text-slate-300 mt-2 leading-relaxed break-words font-mono';
                cancelBtn.className = 'px-4 py-2 rounded-lg border border-[#334155] bg-[#1e293b] text-slate-300 text-xs font-mono font-bold hover:bg-[#334155] transition-colors';
            } else if (style === 'minimal') {
                // ESTILO 4: Neumorphic Clean (Relieve suave, sombras bicromáticas táctiles)
                modal.classList.add('bg-slate-900/40', 'backdrop-blur-xs');
                card.classList.add('bg-[#f0f4f9]', 'rounded-2xl', 'shadow-[12px_12px_30px_#cfd7e3,-12px_-12px_30px_#ffffff]', 'border', 'border-white/80', 'text-slate-800');
                if (modalFooter) modalFooter.classList.add('bg-[#edf2f8]', 'border-slate-200/60');
                title.className = 'text-base font-extrabold text-slate-900 tracking-tight truncate';
                message.className = 'text-xs text-slate-600 mt-2 leading-relaxed break-words font-medium';
                cancelBtn.className = 'px-4 py-2.5 rounded-xl bg-[#f0f4f9] text-slate-600 text-xs font-bold shadow-[3px_3px_8px_#d1d9e6,-3px_-3px_8px_#ffffff] hover:shadow-[inset_2px_2px_4px_#d1d9e6,inset_-2px_-2px_4px_#ffffff] transition-all';
            } else {
                // ESTILO 1: Dkript Executive (Corporativo inmaculado con acentos cobalto)
                modal.classList.add('bg-slate-950/60', 'backdrop-blur-sm');
                card.classList.add('bg-white', 'rounded-3xl', 'shadow-2xl', 'border', 'border-slate-100', 'text-slate-900');
                if (modalFooter) modalFooter.classList.add('bg-[#f8fafd]', 'border-slate-100');
                title.className = 'text-base font-black text-slate-900 tracking-tight truncate';
                message.className = 'text-xs text-slate-600 mt-2 leading-relaxed break-words font-medium';
                cancelBtn.className = 'px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100 transition-colors';
            }

            // 3. Paleta cromática por tipo y estilo
            const themes = {
                error: {
                    iconClass: 'bi-x-circle-fill',
                    badgeText: 'Error',
                    badgeClass: style === 'glassmorphism' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-rose-100 text-rose-800',
                    iconContainer: style === 'glassmorphism' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30 shadow-[0_0_20px_rgba(244,63,94,0.3)]' : style === 'minimal' ? 'bg-[#f0f4f9] text-rose-600 shadow-[inset_3px_3px_6px_#d1d9e6,inset_-3px_-3px_6px_#ffffff]' : 'bg-rose-50 text-rose-600 border border-rose-100',
                    confirmBtn: style === 'minimal' ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-[4px_4px_10px_#d1d9e6,-4px_-4px_10px_#ffffff]' : style === 'glassmorphism' ? 'bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white shadow-lg shadow-rose-600/30' : 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-600/20',
                },
                warning: {
                    iconClass: 'bi-exclamation-triangle-fill',
                    badgeText: 'Atención',
                    badgeClass: style === 'glassmorphism' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-amber-100 text-amber-800',
                    iconContainer: style === 'glassmorphism' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30 shadow-[0_0_20px_rgba(245,158,11,0.3)]' : style === 'minimal' ? 'bg-[#f0f4f9] text-amber-600 shadow-[inset_3px_3px_6px_#d1d9e6,inset_-3px_-3px_6px_#ffffff]' : 'bg-amber-50 text-amber-600 border border-amber-100',
                    confirmBtn: style === 'minimal' ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-[4px_4px_10px_#d1d9e6,-4px_-4px_10px_#ffffff]' : style === 'glassmorphism' ? 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white shadow-lg shadow-amber-500/30' : 'bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20',
                },
                success: {
                    iconClass: 'bi-check-circle-fill',
                    badgeText: 'Completado',
                    badgeClass: style === 'glassmorphism' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-emerald-100 text-emerald-800',
                    iconContainer: style === 'glassmorphism' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.3)]' : style === 'minimal' ? 'bg-[#f0f4f9] text-emerald-600 shadow-[inset_3px_3px_6px_#d1d9e6,inset_-3px_-3px_6px_#ffffff]' : 'bg-emerald-50 text-emerald-600 border border-emerald-100',
                    confirmBtn: style === 'minimal' ? 'bg-[#0062f5] hover:bg-[#0051cc] text-white shadow-[4px_4px_10px_#d1d9e6,-4px_-4px_10px_#ffffff]' : style === 'glassmorphism' ? 'bg-gradient-to-r from-emerald-500 via-teal-500 to-[#00d4ff] text-white shadow-lg shadow-emerald-500/30' : 'bg-[#0062f5] hover:bg-[#0051cc] text-white shadow-blue-600/20',
                },
                info: {
                    iconClass: 'bi-info-circle-fill',
                    badgeText: 'Información',
                    badgeClass: style === 'glassmorphism' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-blue-100 text-[#0062f5]',
                    iconContainer: style === 'glassmorphism' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 shadow-[0_0_20px_rgba(0,212,255,0.3)]' : style === 'minimal' ? 'bg-[#f0f4f9] text-[#0062f5] shadow-[inset_3px_3px_6px_#d1d9e6,inset_-3px_-3px_6px_#ffffff]' : 'bg-[#0062f5]/10 text-[#0062f5] border border-[#0062f5]/20',
                    confirmBtn: style === 'minimal' ? 'bg-gradient-to-r from-[#0062f5] to-[#0051cc] text-white shadow-[4px_4px_10px_#d1d9e6,-4px_-4px_10px_#ffffff]' : style === 'glassmorphism' ? 'bg-gradient-to-r from-[#0062f5] via-[#00d4ff] to-[#7928ca] text-white shadow-lg shadow-cyan-500/30' : 'bg-[#0062f5] hover:bg-[#0051cc] text-white shadow-blue-600/20',
                }
            };

            const theme = themes[type] || themes.info;

            icon.className = `bi ${theme.iconClass}`;
            iconContainer.className = `w-12 h-12 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0 shadow-sm transition-colors ${theme.iconContainer}`;

            if (badge) {
                badge.className = `px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider ${theme.badgeClass}`;
                badge.textContent = theme.badgeText;
                badge.classList.remove('hidden');
            }

            confirmBtn.className = `px-5 py-2.5 rounded-xl text-white text-xs font-bold transition-all shadow-sm flex items-center gap-2 active:scale-95 ${theme.confirmBtn}`;
            const btnSpan = confirmBtn.querySelector('span');
            if (btnSpan) btnSpan.textContent = options.confirmText || (isConfirm ? 'Confirmar' : 'Entendido');

            if (isConfirm) {
                cancelBtn.classList.remove('hidden');
                cancelBtn.textContent = options.cancelText || 'Cancelar';
            } else {
                cancelBtn.classList.add('hidden');
            }

            const closeModalHandler = (result) => {
                modal.classList.add('opacity-0', 'pointer-events-none');
                card.classList.add('scale-95');
                card.classList.remove('scale-100');
                document.body.classList.remove('overflow-hidden');
                confirmBtn.onclick = null;
                cancelBtn.onclick = null;
                document.removeEventListener('keydown', keyHandler);
                resolve(result);
            };

            const keyHandler = (e) => {
                if (e.key === 'Escape') {
                    closeModalHandler(false);
                } else if (e.key === 'Enter') {
                    closeModalHandler(true);
                }
            };

            confirmBtn.onclick = () => closeModalHandler(true);
            cancelBtn.onclick = () => closeModalHandler(false);
            document.addEventListener('keydown', keyHandler);

            modal.classList.remove('opacity-0', 'pointer-events-none');
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
            document.body.classList.add('overflow-hidden');
            confirmBtn.focus();
        });
    },

    open(options = {}) {
        return this.show(options);
    },

    close() {
        const modal = document.getElementById('globalSystemModal');
        const card = document.getElementById('globalSystemModalCard');
        if (modal && card) {
            modal.classList.add('opacity-0', 'pointer-events-none');
            card.classList.add('scale-95');
            card.classList.remove('scale-100');
            document.body.classList.remove('overflow-hidden');
        }
    },

    alert(message, title = 'Notificación', type = 'info') {
        return this.show({ message, title, type, isConfirm: false });
    },

    error(message, title = 'Error') {
        return this.show({ message, title, type: 'error', isConfirm: false });
    },

    warning(message, title = 'Advertencia') {
        return this.show({ message, title, type: 'warning', isConfirm: false });
    },

    success(message, title = 'Éxito') {
        return this.show({ message, title, type: 'success', isConfirm: false });
    },

    confirm(message, title = 'Confirmación', options = {}) {
        return this.show({
            message,
            title,
            type: options.type || 'warning',
            isConfirm: true,
            confirmText: options.confirmText || 'Confirmar',
            cancelText: options.cancelText || 'Cancelar',
            size: options.size || 'md'
        });
    },

    /**
     * Modal de Entrada Rápida de Datos (Prompt nativo del sistema)
     * Reemplaza el window.prompt() con una interfaz visual adaptada al estilo modal activo
     */
    prompt(options = {}) {
        if (typeof options === 'string') {
            options = { message: options };
        }
        const title = options.title || 'Ingreso de Información';
        const message = options.message || '';
        const defaultValue = options.defaultValue !== undefined ? options.defaultValue : (options.value || '');
        const placeholder = options.placeholder || '';
        const inputType = options.inputType || options.type || 'text';
        const inputId = 'globalSystemModalPromptInput';
        const helpText = options.help ? `<p class="text-[11px] text-slate-400 mt-2">${options.help}</p>` : '';

        const escapedDefault = String(defaultValue).replace(/"/g, '&quot;');
        const escapedPlaceholder = String(placeholder).replace(/"/g, '&quot;');

        const promptContent = `
            <div class="text-xs leading-relaxed font-medium mb-3">${message}</div>
            <div class="space-y-1">
                <input type="${inputType}" 
                       id="${inputId}" 
                       value="${escapedDefault}" 
                       placeholder="${escapedPlaceholder}" 
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/25 focus:border-[#0062f5] transition-all bg-white font-medium text-slate-800 modal-prompt-input"
                       autocomplete="off">
                ${helpText}
            </div>
        `;

        return new Promise((resolve) => {
            this.show({
                title: title,
                message: promptContent,
                type: options.dialogType || 'info',
                isConfirm: true,
                confirmText: options.confirmText || 'Aceptar',
                cancelText: options.cancelText || 'Cancelar',
                size: options.size || 'md'
            }).then((confirmed) => {
                if (confirmed) {
                    const input = document.getElementById(inputId);
                    const val = input ? input.value.trim() : '';
                    resolve(val);
                } else {
                    resolve(null);
                }
            });

            setTimeout(() => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.focus();
                    input.select();
                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const confirmBtn = document.getElementById('globalSystemModalConfirmBtn');
                            if (confirmBtn) confirmBtn.click();
                        }
                    });
                }
            }, 60);
        });
    },

    /**
     * Modal con Formulario Dinámico Integrado
     * Genera inputs automáticamente con estilos de Dkript y gestiona validación / callback
     */
    form(options = {}) {
        const fields = options.fields || [];
        let formHtml = `<form id="dynamicModalForm" class="space-y-4 pt-2">`;
        
        fields.forEach(field => {
            const id = field.id || ('dyn_' + field.name);
            const label = field.label || field.name;
            const type = field.type || 'text';
            const value = field.value !== undefined ? field.value : '';
            const required = field.required ? 'required' : '';
            const placeholder = field.placeholder || '';

            formHtml += `<div>`;
            formHtml += `<label for="${id}" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">${label} ${field.required ? '<span class="text-rose-500">*</span>' : ''}</label>`;
            
            if (type === 'select') {
                formHtml += `<select id="${id}" name="${field.name}" ${required} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white font-medium">`;
                (field.options || []).forEach(opt => {
                    const optVal = typeof opt === 'object' ? opt.value : opt;
                    const optText = typeof opt === 'object' ? opt.label : opt;
                    const selected = String(optVal) === String(value) ? 'selected' : '';
                    formHtml += `<option value="${optVal}" ${selected}>${optText}</option>`;
                });
                formHtml += `</select>`;
            } else if (type === 'textarea') {
                formHtml += `<textarea id="${id}" name="${field.name}" placeholder="${placeholder}" ${required} rows="${field.rows || 3}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium">${value}</textarea>`;
            } else {
                formHtml += `<input type="${type}" id="${id}" name="${field.name}" value="${value}" placeholder="${placeholder}" ${required} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-medium">`;
            }

            if (field.help) {
                formHtml += `<p class="text-[11px] text-slate-400 mt-1">${field.help}</p>`;
            }
            formHtml += `</div>`;
        });

        formHtml += `</form>`;

        return new Promise((resolve) => {
            this.show({
                title: options.title || 'Formulario',
                message: (options.intro ? `<p class="text-xs text-slate-500 mb-2">${options.intro}</p>` : '') + formHtml,
                type: options.type || 'info',
                isConfirm: true,
                size: options.size || 'lg',
                confirmText: options.confirmText || 'Guardar',
                cancelText: options.cancelText || 'Cancelar'
            }).then((confirmed) => {
                if (confirmed) {
                    const form = document.getElementById('dynamicModalForm');
                    const formData = form ? new FormData(form) : new FormData();
                    const values = {};
                    formData.forEach((val, key) => values[key] = val);
                    
                    if (typeof options.onSubmit === 'function') {
                        options.onSubmit(values, formData);
                    }
                    resolve(values);
                } else {
                    resolve(null);
                }
            });
        });
    },

    preview(style = 'corporate') {
        const styleNames = {
            corporate: 'Dkript Executive',
            glassmorphism: 'Drypt Cyber-Glass',
            window: 'Classic Studio Window',
            minimal: 'Neumorphic Clean'
        };
        const sampleMessages = {
            corporate: 'Esta es una muestra del estilo <strong>Dkript Executive</strong>: sobrio, profesional y de alta gama con fondos blancos pulidos y acentos en azul cobalto.',
            glassmorphism: 'Esta es una muestra del estilo <strong>Drypt Cyber-Glass</strong>: cristal traslúcido con efecto glassmorphism, resplandor neón cyan y sombras mágicas.',
            window: 'Esta es una muestra del estilo <strong>Classic Studio Window</strong>: emula una ventana de software de escritorio con barra superior de controles y botones de semáforo.',
            minimal: 'Esta es una muestra del estilo <strong>Neumorphic Clean</strong>: minimalismo táctil con relieve suave, sombras extruidas bicromáticas y sensación física zen.'
        };
        return this.show({
            style: style,
            title: styleNames[style] || 'Previsualización de Estilo',
            message: sampleMessages[style] || 'Previsualización del diseño de modal configurado.',
            type: 'info',
            isConfirm: true,
            confirmText: 'Probar este estilo',
            cancelText: 'Cerrar vista'
        });
    }
};

/**
 * Sistema de Toasts / Banners Notificaciones Dkript Inc.
 * Despliega alertas flotantes en la esquina superior derecha con auto-cierre y animaciones suaves.
 */
const DkriptToast = {
    getContainer() {
        let container = document.getElementById('dkriptToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'dkriptToastContainer';
            container.className = 'fixed top-6 right-6 z-[99999] flex flex-col gap-3 pointer-events-none max-w-sm sm:max-w-md w-full';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }
        return container;
    },

    show(options = {}) {
        const type = options.type || 'success';
        const title = options.title || (type === 'success' ? 'Operación Exitosa' : type === 'error' ? 'Error' : type === 'warning' ? 'Atención' : 'Notificación');
        const message = options.message || '';
        const duration = options.duration !== undefined ? options.duration : 4500;

        const container = this.getContainer();

        // Configuraciones temáticas según tipo
        const configs = {
            success: {
                icon: 'bi-check-circle-fill',
                iconColor: 'text-emerald-500',
                iconBg: 'bg-emerald-50 border-emerald-200/80',
                borderColor: 'border-emerald-500/30',
                accentBar: 'bg-emerald-500',
                badgeText: 'Éxito',
                badgeClass: 'bg-emerald-100 text-emerald-800'
            },
            error: {
                icon: 'bi-x-circle-fill',
                iconColor: 'text-rose-500',
                iconBg: 'bg-rose-50 border-rose-200/80',
                borderColor: 'border-rose-500/30',
                accentBar: 'bg-rose-500',
                badgeText: 'Error',
                badgeClass: 'bg-rose-100 text-rose-800'
            },
            warning: {
                icon: 'bi-exclamation-triangle-fill',
                iconColor: 'text-amber-500',
                iconBg: 'bg-amber-50 border-amber-200/80',
                borderColor: 'border-amber-500/30',
                accentBar: 'bg-amber-500',
                badgeText: 'Aviso',
                badgeClass: 'bg-amber-100 text-amber-800'
            },
            info: {
                icon: 'bi-info-circle-fill',
                iconColor: 'text-[#0062f5]',
                iconBg: 'bg-blue-50 border-blue-200/80',
                borderColor: 'border-[#0062f5]/30',
                accentBar: 'bg-[#0062f5]',
                badgeText: 'Info',
                badgeClass: 'bg-blue-100 text-[#0062f5]'
            }
        };

        const config = configs[type] || configs.success;

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto relative overflow-hidden rounded-2xl bg-white/95 backdrop-blur-md border ${config.borderColor} shadow-2xl p-4 transition-all duration-300 transform translate-x-12 opacity-0 select-none`;
        
        toast.innerHTML = `
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl ${config.iconBg} border flex items-center justify-center text-lg flex-shrink-0 shadow-xs">
                    <i class="bi ${config.icon} ${config.iconColor}"></i>
                </div>
                <div class="flex-1 min-w-0 pt-0.5">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h5 class="text-xs font-black text-slate-900 tracking-tight truncate">${title}</h5>
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full ${config.badgeClass}">
                            ${config.badgeText}
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 font-medium leading-relaxed break-words">${message}</p>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition-colors flex-shrink-0" title="Cerrar">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-slate-100 overflow-hidden">
                <div class="toast-progress h-full ${config.accentBar}" style="width: 100%; transition: width ${duration}ms linear;"></div>
            </div>
        `;

        container.appendChild(toast);

        // Animación de entrada (slide-in & fade-in confiable)
        setTimeout(() => {
            toast.classList.remove('translate-x-12', 'opacity-0');
            toast.classList.add('translate-x-0', 'opacity-100');
            const progress = toast.querySelector('.toast-progress');
            if (progress && duration > 0) {
                setTimeout(() => {
                    progress.style.width = '0%';
                }, 50);
            }
        }, 30);

        let timer = null;
        const dismiss = () => {
            if (timer) clearTimeout(timer);
            toast.classList.remove('translate-x-0', 'opacity-100');
            toast.classList.add('translate-x-12', 'opacity-0');
            setTimeout(() => {
                toast.remove();
            }, 300);
        };

        const closeBtn = toast.querySelector('button');
        if (closeBtn) {
            closeBtn.onclick = dismiss;
        }

        if (duration > 0) {
            timer = setTimeout(dismiss, duration);
        }

        return toast;
    },

    success(message, title = 'Operación Exitosa', duration = 4500) {
        return this.show({ message, title, type: 'success', duration });
    },

    error(message, title = 'Error', duration = 5500) {
        return this.show({ message, title, type: 'error', duration });
    },

    warning(message, title = 'Atención', duration = 5000) {
        return this.show({ message, title, type: 'warning', duration });
    },

    info(message, title = 'Notificación', duration = 4500) {
        return this.show({ message, title, type: 'info', duration });
    }
};

window.DkriptToast = DkriptToast;

// Sobrescribir el alert() nativo del explorador por la ventana estilizada corporativa
window.alert = function(message) {
    return DkriptModal.warning(message, 'Atención');
};
window.promptModal = function(message, defaultValue, title) {
    return DkriptModal.prompt({ message, defaultValue, title });
};
window.confirmModal = function(message, title) {
    return DkriptModal.confirm(message, title);
};

/**
 * Gestor AJAX Universal para Formularios Dkript Inc.
 * Maneja envío asíncrono con FormData, headers CSRF/JSON, spinner en botón de envío,
 * notificaciones Toast automáticas y callbacks onSuccess/onError.
 */
const DkriptForm = {
    async ajax(form, options = {}) {
        if (!form) return;

        const submitBtn = options.submitBtn || form.querySelector('button[type="submit"]');
        let originalBtnHtml = '';
        if (submitBtn) {
            originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>${options.loadingText || 'Guardando...'}</span>
            `;
        }

        try {
            const formData = new FormData(form);
            const action = form.getAttribute('action') || window.location.href;
            const method = (form.getAttribute('method') || 'POST').toUpperCase();
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                       || form.querySelector('input[name="_token"]')?.value;

            const response = await fetch(action, {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {})
                },
                body: formData
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && (data.success !== false)) {
                if (options.showToast !== false) {
                    DkriptToast.success(data.message || options.successMessage || 'Datos guardados exitosamente.', 'Operación Exitosa');
                }
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(data, form);
                }
                return data;
            } else {
                let errorMsg = data.message || options.errorMessage || 'Ocurrió un error al procesar la solicitud.';
                if (data.errors) {
                    const firstErr = Object.values(data.errors)[0];
                    if (Array.isArray(firstErr)) {
                        errorMsg = firstErr[0];
                    } else if (typeof firstErr === 'string') {
                        errorMsg = firstErr;
                    }
                }
                if (options.showToast !== false) {
                    DkriptToast.error(errorMsg, 'Error de Validación');
                }
                if (typeof options.onError === 'function') {
                    options.onError(data, response);
                }
                return data;
            }
        } catch (error) {
            console.error('Error en DkriptForm.ajax:', error);
            if (options.showToast !== false) {
                DkriptToast.error('No se pudo completar la solicitud de red. Intenta nuevamente.', 'Error de Conexión');
            }
            if (typeof options.onError === 'function') {
                options.onError(error, null);
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
        }
    }
};

window.DkriptForm = DkriptForm;

/**
 * Función Universal para Exportar Tabla HTML a Excel (.xls) sin dependencias de servidor
 */
function exportTableToExcel(tableId, filename = 'export') {
    const table = document.getElementById(tableId);
    if (!table) {
        DkriptModal.warning('No se encontró la tabla con id #' + tableId + ' para exportar.', 'Exportación');
        return;
    }

    // Clonar tabla para eliminar botones o elementos no deseados en la exportación
    const cloneTable = table.cloneNode(true);
    cloneTable.querySelectorAll('.no-export, button, .action-buttons').forEach(el => el.remove());

    const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Export</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #000; }
                td { border: 1px solid #ccc; }
            </style>
        </head>
        <body>
            ${cloneTable.outerHTML}
        </body>
        </html>
    `;

    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename + '.xls';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

/**
 * Helpers para apertura y cierre de Modales
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        if (modalId === 'modalUserForm' && !window._isOpeningEditUserModal) {
            if (window.DkriptUser && typeof window.DkriptUser.resetForm === 'function') {
                window.DkriptUser.resetForm();
            }
        }
        if (modalId === 'modalRoleForm' && !window._isOpeningEditRoleModal) {
            if (window.DkriptRole && typeof window.DkriptRole.resetForm === 'function') {
                window.DkriptRole.resetForm();
            }
        }

        const activeStyle = document.body.getAttribute('data-modal-style') || 'corporate';
        modal.setAttribute('data-active-style', activeStyle);

        modal.classList.remove('hidden', 'pointer-events-none');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            const card = modal.querySelector('.modal-card');
            if (card) {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }
        });
        document.body.classList.add('overflow-hidden');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const card = modal.querySelector('.modal-card');
        if (card) {
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
        }
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
        }, 200);

        document.body.classList.remove('overflow-hidden');
        if (modalId === 'modalUserForm' && window.DkriptUser && typeof window.DkriptUser.resetForm === 'function') {
            window.DkriptUser.resetForm();
        }
        if (modalId === 'modalRoleForm' && window.DkriptRole && typeof window.DkriptRole.resetForm === 'function') {
            window.DkriptRole.resetForm();
        }
    }
}

/* ==========================================================================
   1. CONTROLADORES GLOBALES DE INTERFAZ, SIDEBAR Y PERFIL DE USUARIO
   ========================================================================== */

/**
 * Control del Drawer Móvil y Tablet Nativo (Apple, Samsung, Xiaomi, PC)
 */
function toggleMobileSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;

    const isOpening = !sidebar.classList.contains('sidebar-mobile-active');

    if (isOpening) {
        sidebar.classList.add('sidebar-mobile-active');
        if (backdrop) backdrop.classList.add('backdrop-mobile-active');
        document.body.classList.add('overflow-hidden');
    } else {
        sidebar.classList.remove('sidebar-mobile-active');
        if (backdrop) backdrop.classList.remove('backdrop-mobile-active');
        document.body.classList.remove('overflow-hidden');
    }
}
window.toggleMobileSidebar = toggleMobileSidebar;

// Si la pantalla se agranda a desktop (>= 1024px), resetear clases móviles
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        const sidebar = document.getElementById('mainSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (sidebar) sidebar.classList.remove('sidebar-mobile-active');
        if (backdrop) backdrop.classList.remove('backdrop-mobile-active');
    }
});

/**
 * Control de Menú Desplegable de Perfil de Usuario (Cyber-Glass & Mobile Friendly)
 */
function toggleUserProfileMenu() {
    const dropdown = document.getElementById('userProfileDropdown');
    const chevron = document.getElementById('userProfileChevron');
    const btn = document.getElementById('userProfileMenuButton');
    if (!dropdown) return;

    const isHidden = dropdown.classList.contains('hidden');
    if (isHidden) {
        dropdown.classList.remove('hidden');
        requestAnimationFrame(() => {
            dropdown.classList.remove('opacity-0', 'scale-95');
            dropdown.classList.add('opacity-100', 'scale-100');
        });
        if (chevron) chevron.classList.add('rotate-180');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    } else {
        dropdown.classList.remove('opacity-100', 'scale-100');
        dropdown.classList.add('opacity-0', 'scale-95');
        if (chevron) chevron.classList.remove('rotate-180');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        setTimeout(() => dropdown.classList.add('hidden'), 150);
    }
}
window.toggleUserProfileMenu = toggleUserProfileMenu;

function closeUserProfileMenu() {
    const dropdown = document.getElementById('userProfileDropdown');
    const chevron = document.getElementById('userProfileChevron');
    const btn = document.getElementById('userProfileMenuButton');
    if (!dropdown || dropdown.classList.contains('hidden')) return;

    dropdown.classList.remove('opacity-100', 'scale-100');
    dropdown.classList.add('opacity-0', 'scale-95');
    if (chevron) chevron.classList.remove('rotate-180');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    setTimeout(() => dropdown.classList.add('hidden'), 150);
}
window.closeUserProfileMenu = closeUserProfileMenu;

function openUserProfileModal() {
    closeUserProfileMenu();
    const modal = document.getElementById('userProfileModal');
    if (!modal) return;
    modal.classList.remove('hidden', 'pointer-events-none');
    requestAnimationFrame(() => {
        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');
        const card = modal.querySelector('.modal-card');
        if (card) {
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }
        if (window.DkriptUserProfile) {
            if (typeof window.DkriptUserProfile.resetToInitialState === 'function') {
                window.DkriptUserProfile.resetToInitialState();
            }
            window.DkriptUserProfile.initRealtimeValidation();
        }
    });
}
window.openUserProfileModal = openUserProfileModal;

function closeUserProfileModal() {
    const modal = document.getElementById('userProfileModal');
    if (!modal || modal.classList.contains('hidden')) return;
    const card = modal.querySelector('.modal-card');
    if (card) {
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
    }
    modal.classList.remove('opacity-100');
    modal.classList.add('opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden', 'pointer-events-none');
        if (window.DkriptUserProfile && typeof window.DkriptUserProfile.resetToInitialState === 'function') {
            window.DkriptUserProfile.resetToInitialState();
        }
    }, 200);
}
window.closeUserProfileModal = closeUserProfileModal;

// Cierre con click/tap fuera del elemento (compatible con ratón y touch screens)
document.addEventListener('pointerdown', (e) => {
    const container = document.getElementById('userProfileDropdownContainer');
    if (container && !container.contains(e.target)) {
        closeUserProfileMenu();
    }

    const profileModal = document.getElementById('userProfileModal');
    if (profileModal && !profileModal.classList.contains('hidden') && e.target === profileModal) {
        closeUserProfileModal();
    }
});

// Cierre accesible con tecla Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeUserProfileMenu();
        closeUserProfileModal();
    }
});

/* ==========================================================================
   2. HELPERS DE AUTENTICACIÓN
   ========================================================================== */
function fillCredentials(email, pass) {
    const emailEl = document.getElementById('email');
    const passEl = document.getElementById('password');
    if (emailEl) emailEl.value = email;
    if (passEl) passEl.value = pass;
}
window.fillCredentials = fillCredentials;

/* ==========================================================================
   3. MÓDULO DE GESTIÓN DE USUARIOS
   ========================================================================== */
const DkriptUser = {
    resetForm(storeUrl = '/users') {
        const form = document.getElementById('frmUser');
        if (form) form.reset();

        const methodEl = document.getElementById('userFormMethod');
        if (methodEl) methodEl.value = 'POST';

        if (form) form.action = storeUrl;

        const titleEl = document.getElementById('userModalTitle');
        if (titleEl) titleEl.textContent = 'Registrar Nuevo Usuario';

        const nameEl = document.getElementById('user_name');
        if (nameEl) nameEl.value = '';

        const emailEl = document.getElementById('user_email');
        if (emailEl) emailEl.value = '';

        const firstNameEl = document.getElementById('user_first_name');
        if (firstNameEl) firstNameEl.value = '';

        const lastNameEl = document.getElementById('user_last_name');
        if (lastNameEl) lastNameEl.value = '';

        const phoneEl = document.getElementById('user_phone');
        if (phoneEl) phoneEl.value = '';

        const roleEl = document.getElementById('user_role_id');
        if (roleEl && roleEl.options.length > 0) roleEl.selectedIndex = 0;

        const statusEl = document.getElementById('user_status');
        if (statusEl) statusEl.value = '1';

        const passEl = document.getElementById('user_password');
        if (passEl) {
            passEl.value = '';
            passEl.required = true;
        }

        const passHelpEl = document.getElementById('passHelpText');
        if (passHelpEl) passHelpEl.textContent = 'Requerido para nuevos registros';
    },

    openCreateModal(storeUrl = '/users') {
        window._isOpeningEditUserModal = false;
        this.resetForm(storeUrl);
        openModal('modalUserForm');
    },

    openEditModal(user, profile, baseUrl = '/users') {
        window._isOpeningEditUserModal = true;
        const form = document.getElementById('frmUser');
        if (!form) return;
        form.reset();

        const methodEl = document.getElementById('userFormMethod');
        if (methodEl) methodEl.value = 'PUT';
        
        form.action = `${baseUrl}/${user.id}`;
        
        const titleEl = document.getElementById('userModalTitle');
        if (titleEl) titleEl.textContent = 'Editar Usuario: ' + user.name;
        
        const nameEl = document.getElementById('user_name');
        if (nameEl) nameEl.value = user.name;

        const emailEl = document.getElementById('user_email');
        if (emailEl) emailEl.value = user.email;

        const statusEl = document.getElementById('user_status');
        if (statusEl) statusEl.value = user.status;

        const passEl = document.getElementById('user_password');
        if (passEl) passEl.required = false;

        const passHelpEl = document.getElementById('passHelpText');
        if (passHelpEl) passHelpEl.textContent = 'Dejar en blanco para mantener la actual';

        if (profile) {
            const firstNameEl = document.getElementById('user_first_name');
            if (firstNameEl) firstNameEl.value = profile.first_name || '';

            const lastNameEl = document.getElementById('user_last_name');
            if (lastNameEl) lastNameEl.value = profile.last_name || '';

            const phoneEl = document.getElementById('user_phone');
            if (phoneEl) phoneEl.value = profile.phone || '';

            const roleEl = document.getElementById('user_role_id');
            if (roleEl) roleEl.value = profile.role_id || 1;
        }

        openModal('modalUserForm');
        window._isOpeningEditUserModal = false;
    }
};
window.DkriptUser = DkriptUser;
window.openCreateUserModal = function(storeUrl) {
    DkriptUser.openCreateModal(storeUrl);
};
window.resetUserForm = function(storeUrl) {
    DkriptUser.resetForm(storeUrl);
};
window.openEditUserModal = function(user, profile) {
    DkriptUser.openEditModal(user, profile);
};

/* ==========================================================================
   4. MÓDULO DE GESTIÓN DE ROLES Y MATRIZ DE PERMISOS
   ========================================================================== */
const DkriptRole = {
    updateStatusBadge(checkbox) {
        const badge = document.getElementById('roleStatusBadge');
        if (!badge) return;
        if (checkbox.checked) {
            badge.textContent = 'ACTIVO';
            badge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300';
        } else {
            badge.textContent = 'INACTIVO';
            badge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-300';
        }
    },

    onModuleMasterToggle(moduleId, isChecked) {
        const checkboxes = document.querySelectorAll('.module-perm-' + moduleId);
        checkboxes.forEach(cb => {
            cb.disabled = !isChecked;
            if (!isChecked) {
                cb.checked = false;
            }
        });
        this.updateModulePermCount(moduleId);
    },

    toggleModulePerms(moduleId) {
        const masterSwitch = document.getElementById('mod_master_' + moduleId);
        if (masterSwitch && !masterSwitch.checked) {
            masterSwitch.checked = true;
            this.onModuleMasterToggle(moduleId, true);
        }

        const checkboxes = document.querySelectorAll('.module-perm-' + moduleId);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
        this.updateModulePermCount(moduleId);
    },

    updateModulePermCount(moduleId) {
        const checkboxes = document.querySelectorAll('.module-perm-' + moduleId);
        const activeCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const totalCount = checkboxes.length;
        const countLabel = document.getElementById('module_count_' + moduleId);
        if (countLabel) {
            countLabel.textContent = `${activeCount}/${totalCount} act.`;
        }
    },

    toggleAllPermissions(select) {
        document.querySelectorAll('.module-master-switch').forEach(master => {
            master.checked = select;
            const modId = master.dataset.moduleId;
            this.onModuleMasterToggle(modId, select);
        });

        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            cb.checked = select;
        });

        document.querySelectorAll('.module-master-switch').forEach(master => {
            this.updateModulePermCount(master.dataset.moduleId);
        });
    },

    filterModules(term) {
        term = (term || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.role-module-card');
        cards.forEach(card => {
            const name = card.dataset.moduleName || '';
            card.style.display = name.includes(term) ? '' : 'none';
        });
    },

    resetForm(storeUrl = '/roles') {
        const form = document.getElementById('frmRole');
        if (form) form.reset();
        const roleIdEl = document.getElementById('role_id');
        if (roleIdEl) roleIdEl.value = '';
        const methodEl = document.getElementById('roleFormMethod');
        if (methodEl) methodEl.value = 'POST';
        if (form) form.action = storeUrl;
        const titleEl = document.getElementById('roleModalTitle');
        if (titleEl) titleEl.textContent = 'Registrar Nuevo Rol';
        const statusEl = document.getElementById('role_status');
        if (statusEl) {
            statusEl.checked = true;
            this.updateStatusBadge(statusEl);
        }
        this.toggleAllPermissions(false);
    },

    edit(roleId, baseUrl = '/roles') {
        this.resetForm(baseUrl);
        fetch(`${baseUrl}/${roleId}/edit`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const roleIdEl = document.getElementById('role_id');
            if (roleIdEl) roleIdEl.value = data.id;
            const nameEl = document.getElementById('role_name');
            if (nameEl) nameEl.value = data.name;
            const descEl = document.getElementById('role_description');
            if (descEl) descEl.value = data.description || '';
            const methodEl = document.getElementById('roleFormMethod');
            if (methodEl) methodEl.value = 'PUT';
            const form = document.getElementById('frmRole');
            if (form) form.action = `${baseUrl}/${data.id}`;
            const titleEl = document.getElementById('roleModalTitle');
            if (titleEl) titleEl.textContent = 'Editar Rol: ' + data.name;
            
            const statusCb = document.getElementById('role_status');
            if (statusCb) {
                statusCb.checked = (data.status == 1);
                this.updateStatusBadge(statusCb);
            }

            // Activar switches de módulos
            (data.modules || []).forEach(modId => {
                const master = document.getElementById('mod_master_' + modId);
                if (master) {
                    master.checked = true;
                    this.onModuleMasterToggle(modId, true);
                }
            });

            // Activar switches de permisos
            (data.permissions || []).forEach(permId => {
                const permCb = document.getElementById('perm_' + permId);
                if (permCb) {
                    permCb.checked = true;
                }
            });

            // Recalcular conteos
            document.querySelectorAll('.module-master-switch').forEach(master => {
                this.updateModulePermCount(master.dataset.moduleId);
            });

            openModal('modalRoleForm');
        })
        .catch(err => {
            DkriptModal.error('Error al cargar la información del rol.', 'Error de carga');
            console.error(err);
        });
    }
};
window.DkriptRole = DkriptRole;
window.updateRoleStatusBadge = function(cb) { DkriptRole.updateStatusBadge(cb); };
window.onModuleMasterToggle = function(modId, isChecked) { DkriptRole.onModuleMasterToggle(modId, isChecked); };
window.toggleModulePerms = function(modId) { DkriptRole.toggleModulePerms(modId); };
window.updateModulePermCount = function(modId) { DkriptRole.updateModulePermCount(modId); };
window.toggleAllRolePermissions = function(select) { DkriptRole.toggleAllPermissions(select); };
window.filterRoleModules = function(term) { DkriptRole.filterModules(term); };
window.resetRoleForm = function(storeUrl) { DkriptRole.resetForm(storeUrl); };
window.editRole = function(roleId) { DkriptRole.edit(roleId); };

/* ==========================================================================
   5. MÓDULO DE PARÁMETROS, BRANDING Y PREVIEW DE ERRORES (DkriptParameters)
   ========================================================================== */
const DkriptParameters = {
    config: {
        csrfToken: '',
        uploadUrl: '',
        renameUrl: '',
        deleteUrl: '',
        previewBaseUrl: '/parameters/errors/preview'
    },

    state: {
        currentPreviewErrorCode: '404',
        currentPreviewErrorTitle: 'Error 404 · Dimensión No Encontrada',
        currentPreviewBadgeClass: 'bg-[#00d4ff]/10 text-[#00d4ff] border border-[#00d4ff]/30',
        currentPreviewBadgeText: '404 · NOT FOUND',
        activePreviewErrorMode: 'scene'
    },

    init(cfg = {}) {
        this.config = Object.assign({}, this.config, cfg);
        this.initTabs();
        this.initDragAndDrop();
        this.initParametersForm();
        this.initKeyboardShortcuts();
    },

    /**
     * Inicialización del Hub de Pestañas de Parámetros
     */
    initTabs() {
        const validTabs = ['tab-general', 'tab-modals', 'tab-branding', 'tab-errors', 'tab-telephony', 'tab-mail', 'tab-maintenance'];
        let activeTab = 'tab-general';

        // 1. Prioridad: Hash en la URL (#tab-...)
        const hash = window.location.hash ? window.location.hash.replace('#', '') : '';
        if (validTabs.includes(hash)) {
            activeTab = hash;
        } else {
            // 2. Prioridad: LocalStorage guardado previamente
            try {
                const storedTab = localStorage.getItem('dkript_parameters_active_tab');
                if (storedTab && validTabs.includes(storedTab)) {
                    activeTab = storedTab;
                }
            } catch (e) {}
        }

        this.switchTab(activeTab, false);

        // Escuchar cambios de hash por navegación del navegador (atrás/adelante)
        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash ? window.location.hash.replace('#', '') : '';
            if (validTabs.includes(currentHash)) {
                this.switchTab(currentHash, false);
            }
        });
    },

    /**
     * Cambiar de pestaña activa con persistencia y sincronización de URL
     */
    switchTab(tabId, updateHash = true) {
        const validTabs = ['tab-general', 'tab-modals', 'tab-branding', 'tab-errors', 'tab-telephony', 'tab-mail', 'tab-maintenance'];
        if (!validTabs.includes(tabId)) {
            tabId = 'tab-general';
        }

        const tabMetadata = {
            'tab-general': { name: 'General', desc: 'Parámetros globales del sistema, sesiones e inactividad', icon: 'bi-sliders', step: 'Pestaña 1 de 7' },
            'tab-modals': { name: 'Modales', desc: 'Estilo visual de ventanas modales y diálogos (4 temas)', icon: 'bi-window-stack', step: 'Pestaña 2 de 7' },
            'tab-branding': { name: 'Identidad', desc: 'Identidad corporativa, logotipos y catálogo multimedia', icon: 'bi-palette', step: 'Pestaña 3 de 7' },
            'tab-errors': { name: 'Errores', desc: 'Páginas de error cinemáticas Drypt y emulador HTTP', icon: 'bi-film', step: 'Pestaña 4 de 7' },
            'tab-telephony': { name: 'Telefonía', desc: 'Configuración de WhatsApp (Meta API) y SMS (Twilio)', icon: 'bi-chat-dots-fill', step: 'Pestaña 5 de 7' },
            'tab-mail': { name: 'Email', desc: 'Servidor de correo saliente (SMTP) y remitente corporativo', icon: 'bi-envelope-at-fill', step: 'Pestaña 6 de 7' },
            'tab-maintenance': { name: 'Mantenimiento', desc: 'Herramientas de optimización, caché y respaldos', icon: 'bi-tools', step: 'Pestaña 7 de 7' }
        };

        // 1. Mostrar únicamente el panel de la pestaña seleccionada
        const panes = document.querySelectorAll('.parameter-tab-pane');
        panes.forEach(pane => {
            if (pane.id === tabId) {
                pane.classList.remove('hidden');
            } else {
                pane.classList.add('hidden');
            }
        });

        // 2. Actualizar estados visuales de botones
        const navButtons = document.querySelectorAll('[data-tab-target]');
        navButtons.forEach(btn => {
            const isSelected = btn.getAttribute('data-tab-target') === tabId;
            if (isSelected) {
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');
            } else {
                btn.classList.remove('is-active');
                btn.setAttribute('aria-selected', 'false');
            }
        });

        // 3. Actualizar indicador contextual en la cabecera del dock para móvil, tablet y desktop
        const titleEl = document.getElementById('activeTabNameDisplay') || document.getElementById('activeTabContextTitle');
        const descEl = document.getElementById('activeTabDescDisplay') || document.getElementById('activeTabContextDesc');
        const badgeEl = document.getElementById('activeTabBadgeDisplay');
        const iconEl = document.getElementById('activeTabIconDisplay');

        if (tabMetadata[tabId]) {
            if (titleEl) titleEl.textContent = tabMetadata[tabId].name;
            if (descEl) descEl.textContent = tabMetadata[tabId].desc;
            if (badgeEl) badgeEl.textContent = tabMetadata[tabId].step;
            if (iconEl) iconEl.className = 'bi ' + tabMetadata[tabId].icon;
        }

        // 4. Persistir en localStorage
        try {
            localStorage.setItem('dkript_parameters_active_tab', tabId);
        } catch (e) {}

        // 5. Actualizar hash en la URL sin saltos bruscos de desplazamiento
        if (updateHash && window.location.hash !== '#' + tabId) {
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, null, '#' + tabId);
            } else {
                window.location.hash = '#' + tabId;
            }
        }
    },

    updateMaintenanceBadge(checkbox) {
        const badge = document.getElementById('maintenanceBadge');
        if (!badge) return;
        if (checkbox.checked) {
            badge.textContent = 'EN MANTENIMIENTO';
            badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300';
        } else {
            badge.textContent = 'OPERATIVO NORMAL';
            badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-300';
        }
    },

    updateBrandTextBadge(checkbox) {
        const badge = document.getElementById('brandTextBadge');
        if (badge) {
            if (checkbox.checked) {
                badge.textContent = 'VISIBLE';
                badge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-blue-100 text-[#0062f5] border border-blue-200';
            } else {
                badge.textContent = 'OCULTO';
                badge.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-slate-200 text-slate-600 border border-slate-300';
            }
        }

        // Previsualización instantánea en el sidebar
        const brandHeader = document.getElementById('sidebarBrandHeader');
        const sidebarBrandText = document.getElementById('sidebarBrandTextContainer');
        const sidebarBrandLink = document.getElementById('sidebarBrandLink');
        const sidebarLogo = document.getElementById('sidebarSystemLogo');
        if (sidebarBrandText && sidebarLogo) {
            if (checkbox.checked) {
                if (brandHeader) {
                    brandHeader.className = 'h-16 flex items-center justify-between px-5 bg-[#030712] border-b border-[#0b1739] relative overflow-hidden transition-all duration-300';
                }
                sidebarBrandText.classList.remove('hidden');
                if (sidebarBrandLink) {
                    sidebarBrandLink.className = 'flex items-center gap-3 mr-2 group flex-1 min-w-0 h-full';
                }
                sidebarLogo.className = 'w-9 h-9 object-contain rounded-xl p-0.5 bg-[#071026] border border-[#112356] shadow-md shadow-blue-900/30 group-hover:border-[#00d4ff] group-hover:scale-105 transition-all flex-shrink-0';
            } else {
                if (brandHeader) {
                    brandHeader.className = 'min-h-[5.5rem] sm:min-h-[6rem] py-3.5 px-4 flex items-center justify-between bg-[#030712] border-b border-[#0b1739] relative overflow-hidden transition-all duration-300';
                }
                sidebarBrandText.classList.add('hidden');
                if (sidebarBrandLink) {
                    sidebarBrandLink.className = 'flex items-center w-full mr-1 group flex-1 min-w-0 h-full';
                }
                sidebarLogo.className = 'w-full max-w-full h-auto max-h-20 sm:max-h-22 object-contain object-left rounded-lg transition-all duration-300 group-hover:scale-[1.02]';
            }
        }
    },

    selectModalStyle(styleId, cardEl) {
        const radio = document.getElementById('modal_style_' + styleId);
        if (radio) {
            radio.checked = true;
        }

        document.querySelectorAll('.modal-style-card').forEach(card => {
            card.classList.remove('border-[#0062f5]', 'bg-blue-50/20', 'ring-2', 'ring-[#0062f5]/20', 'shadow-md');
            card.classList.add('border-slate-200', 'bg-white');
            const check = card.querySelector('.active-check-icon');
            if (check) check.classList.add('hidden');
        });

        if (cardEl) {
            cardEl.classList.remove('border-slate-200', 'bg-white');
            cardEl.classList.add('border-[#0062f5]', 'bg-blue-50/20', 'ring-2', 'ring-[#0062f5]/20', 'shadow-md');
            const check = cardEl.querySelector('.active-check-icon');
            if (check) check.classList.remove('hidden');
        }

        const globalModal = document.getElementById('globalSystemModal');
        if (globalModal) {
            globalModal.setAttribute('data-default-style', styleId);
        }

        if (document.body) {
            document.body.setAttribute('data-modal-style', styleId);
        }
        window.DKRIPT_MODAL_STYLE = styleId;
    },

    triggerFileUpload() {
        const fileInput = document.getElementById('logoFileInput');
        if (fileInput) {
            fileInput.click();
        }
    },

    showCatalogHover(event, src, name, element) {
        if (window.innerWidth < 768) return;
        const popover = document.getElementById('catalogHoverPreview');
        const img = document.getElementById('hoverPreviewImg');
        const title = document.getElementById('hoverPreviewTitle');
        if (!popover || !img || !title) return;

        img.src = src;
        title.textContent = name;

        const rect = element.getBoundingClientRect();
        const popoverWidth = 140;
        const popoverHeight = 140;

        let top = rect.top - popoverHeight - 10;
        let left = rect.left + (rect.width / 2) - (popoverWidth / 2);

        if (top < 10) top = rect.bottom + 10;
        if (left < 10) left = 10;
        if (left + popoverWidth > window.innerWidth - 10) {
            left = window.innerWidth - popoverWidth - 10;
        }

        popover.style.top = top + 'px';
        popover.style.left = left + 'px';

        popover.classList.remove('opacity-0', 'scale-95');
        popover.classList.add('opacity-100', 'scale-100');
    },

    hideCatalogHover() {
        const popover = document.getElementById('catalogHoverPreview');
        if (popover) {
            popover.classList.remove('opacity-100', 'scale-100');
            popover.classList.add('opacity-0', 'scale-95');
        }
    },

    selectImage(path, name, buttonEl) {
        const systemLogoInput = document.getElementById('system_logo');
        if (systemLogoInput) {
            systemLogoInput.value = path;
        }

        const nameInput = document.getElementById('current_logo_name');
        if (nameInput) {
            nameInput.value = name;
            nameInput.classList.add('ring-2', 'ring-[#0062f5]');
            setTimeout(() => {
                nameInput.classList.remove('ring-2', 'ring-[#0062f5]');
            }, 600);
        }

        const fullLogoPath = path.startsWith('http') || path.startsWith('/') ? path : '/' + path;
        const previewImg = document.getElementById('logoPreviewImg');
        if (previewImg) {
            previewImg.src = fullLogoPath;
        }

        const sidebarLogo = document.getElementById('sidebarSystemLogo');
        if (sidebarLogo) {
            sidebarLogo.src = fullLogoPath;
        }

        const previewBox = document.getElementById('logoPreviewBox');
        if (previewBox) {
            previewBox.classList.add('ring-2', 'ring-[#00d4ff]', 'scale-[1.01]');
            setTimeout(() => {
                previewBox.classList.remove('ring-2', 'ring-[#00d4ff]', 'scale-[1.01]');
            }, 400);
        }

        document.querySelectorAll('.image-card-btn').forEach(btn => {
            btn.classList.remove('border-[#0062f5]', 'ring-2', 'ring-[#0062f5]/20', 'bg-blue-50/30');
            btn.classList.add('border-slate-200');
            const check = btn.querySelector('.active-check-icon');
            if (check) check.classList.add('hidden');
        });

        if (buttonEl) {
            buttonEl.classList.remove('border-slate-200');
            buttonEl.classList.add('border-[#0062f5]', 'ring-2', 'ring-[#0062f5]/20', 'bg-blue-50/30');
            const check = buttonEl.querySelector('.active-check-icon');
            if (check) check.classList.remove('hidden');
        }
    },

    async optimizeImageForUpload(file) {
        const MAX_BYTES = 25 * 1024 * 1024;
        if (file.size > MAX_BYTES) {
            throw new Error('El archivo seleccionado supera el tamaño máximo permitido de 25 MB. Por favor seleccione una imagen más pequeña.');
        }

        if (file.type === 'image/svg+xml' || file.type === 'image/gif') {
            return file;
        }

        if (file.size <= 300 * 1024) {
            return file;
        }

        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;
                    const isSquareOrPortrait = (width / height) <= 1.25;
                    const maxDim = isSquareOrPortrait ? 800 : 1200;

                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, 0, 0, width, height);

                    const isJpeg = file.type === 'image/jpeg' || file.type === 'image/jpg';
                    if (isJpeg) {
                        canvas.toBlob((blob) => {
                            if (!blob) { resolve(file); return; }
                            resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                        }, 'image/jpeg', 0.85);
                        return;
                    }

                    canvas.toBlob((pngBlob) => {
                        if (pngBlob && pngBlob.size < 1.2 * 1024 * 1024 && pngBlob.size < file.size) {
                            resolve(new File([pngBlob], file.name, { type: 'image/png', lastModified: Date.now() }));
                            return;
                        }

                        canvas.toBlob((webpBlob) => {
                            if (webpBlob && webpBlob.size < file.size) {
                                const baseName = file.name.replace(/\.[^/.]+$/, "");
                                resolve(new File([webpBlob], baseName + '.webp', { type: 'image/webp', lastModified: Date.now() }));
                            } else if (pngBlob && pngBlob.size < file.size) {
                                resolve(new File([pngBlob], file.name, { type: 'image/png', lastModified: Date.now() }));
                            } else {
                                resolve(file);
                            }
                        }, 'image/webp', 0.88);
                    }, 'image/png');
                };
                img.onerror = () => resolve(file);
                img.src = e.target.result;
            };
            reader.onerror = () => resolve(file);
            reader.readAsDataURL(file);
        });
    },

    async uploadImageFile(file) {
        if (!file) return;

        const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml', 'image/gif'];
        if (!validTypes.includes(file.type) && !file.type.startsWith('image/')) {
            DkriptModal.warning('Por favor proporcione un archivo de imagen válido (PNG, JPG, WEBP, SVG o GIF).', 'Formato no admitido');
            return;
        }

        const spinner = document.getElementById('uploadSpinner');
        const spinnerText = document.getElementById('uploadSpinnerText');
        if (spinner) {
            if (spinnerText) spinnerText.textContent = 'Optimizando y reduciendo imagen...';
            spinner.classList.remove('hidden');
        }

        try {
            const processedFile = await this.optimizeImageForUpload(file);

            if (spinnerText) spinnerText.textContent = 'Subiendo al servidor...';

            const formData = new FormData();
            formData.append('image', processedFile);
            formData.append('_token', this.config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

            const uploadUrl = this.config.uploadUrl || '/parameters/upload-image';
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const errMsg = (data.errors && Object.values(data.errors).flat().join(' ')) || data.message || 'Error al subir la imagen.';
                throw new Error(errMsg);
            }

            const systemLogoInput = document.getElementById('system_logo');
            if (systemLogoInput) systemLogoInput.value = data.image.path;

            const previewImg = document.getElementById('logoPreviewImg');
            if (previewImg) previewImg.src = data.asset_url;

            const nameInput = document.getElementById('current_logo_name');
            if (nameInput) nameInput.value = data.image.name;

            this.addNewImageToGrid(data.image, data.asset_url);
            this.showNameStatusBadge('¡Imagen optimizada y guardada exitosamente!');

        } catch (err) {
            DkriptModal.error(err.message, 'No se pudo subir la imagen');
        } finally {
            if (spinner) spinner.classList.add('hidden');
            const fileInput = document.getElementById('logoFileInput');
            if (fileInput) fileInput.value = '';
        }
    },

    handleLogoUpload(input) {
        if (!input.files || !input.files[0]) return;
        this.uploadImageFile(input.files[0]);
    },

    initDragAndDrop() {
        const previewBox = document.getElementById('logoPreviewBox');
        const dragOverlay = document.getElementById('dragDropOverlay');

        if (previewBox) {
            ['dragenter', 'dragover'].forEach(eventName => {
                previewBox.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (dragOverlay) dragOverlay.classList.remove('hidden');
                    previewBox.classList.add('ring-2', 'ring-[#00d4ff]', 'scale-[1.02]');
                });
            });

            ['dragleave', 'dragend'].forEach(eventName => {
                previewBox.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (dragOverlay) dragOverlay.classList.add('hidden');
                    previewBox.classList.remove('ring-2', 'ring-[#00d4ff]', 'scale-[1.02]');
                });
            });

            previewBox.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (dragOverlay) dragOverlay.classList.add('hidden');
                previewBox.classList.remove('ring-2', 'ring-[#00d4ff]', 'scale-[1.02]');

                const files = e.dataTransfer ? e.dataTransfer.files : null;
                if (files && files.length > 0) {
                    this.uploadImageFile(files[0]);
                }
            });
        }
    },

    addNewImageToGrid(img, assetUrl) {
        const grid = document.getElementById('availableImagesGrid');
        if (!grid) return;

        document.querySelectorAll('.image-card-btn').forEach(btn => {
            btn.classList.remove('border-[#0062f5]', 'ring-2', 'ring-[#0062f5]/20', 'bg-blue-50/30');
            btn.classList.add('border-slate-200');
            const check = btn.querySelector('.active-check-icon');
            if (check) check.classList.add('hidden');
        });

        const newBtn = document.createElement('button');
        newBtn.type = 'button';
        newBtn.setAttribute('data-path', img.path);
        newBtn.setAttribute('data-name', img.name);
        newBtn.className = 'image-card-btn p-2 border rounded-xl bg-white hover:bg-blue-50/50 transition-all text-left flex items-center gap-2 group relative border-[#0062f5] ring-2 ring-[#0062f5]/20 bg-blue-50/30';
        newBtn.onclick = () => {
            this.selectImage(img.path, newBtn.getAttribute('data-name') || img.name, newBtn);
        };
        newBtn.onmouseenter = (e) => {
            this.showCatalogHover(e, assetUrl, img.name, newBtn);
        };
        newBtn.onmouseleave = () => {
            this.hideCatalogHover();
        };

        newBtn.innerHTML = `
            <div class="w-8 h-8 rounded-lg bg-[#071026] flex items-center justify-center flex-shrink-0 p-1 transition-transform duration-200 group-hover:scale-115">
                <img src="${assetUrl}" alt="${img.name}" class="max-h-full max-w-full object-contain">
            </div>
            <span class="img-label text-[11px] font-semibold text-slate-700 group-hover:text-[#0062f5] truncate flex-1">
                ${img.name}
            </span>
            <span role="button" 
                  tabindex="0"
                  onclick="event.stopPropagation(); DkriptParameters.openDeleteModal('${img.path}', '${img.name.replace(/'/g, "\\'")}', '${assetUrl}')"
                  class="delete-img-btn opacity-60 sm:opacity-0 group-hover:opacity-100 transition-all p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer flex-shrink-0"
                  title="Eliminar imagen del catálogo">
                <i class="bi bi-trash3 text-xs"></i>
            </span>
            <span class="active-check-icon absolute top-1 right-1 w-2 h-2 rounded-full bg-[#0062f5]"></span>
        `;

        grid.prepend(newBtn);

        const scrollContainer = document.getElementById('catalogScrollContainer');
        if (scrollContainer) {
            scrollContainer.scrollTop = 0;
        }

        const countBadge = document.getElementById('imagesCountBadge');
        if (countBadge) {
            const total = grid.querySelectorAll('.image-card-btn').length;
            countBadge.textContent = `${total} recursos`;
        }
    },

    openDeleteModal(path, name, url) {
        DkriptModal.form({
            title: 'Eliminar Recurso Gráfico',
            type: 'warning',
            size: 'md',
            intro: `
                <div class="p-3 mb-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-[#071026] flex items-center justify-center p-1.5 flex-shrink-0 shadow-sm border border-[#112356]">
                        <img src="${url}" alt="${name}" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block text-xs font-bold text-slate-800 truncate">${name}</span>
                        <span class="block text-[11px] text-slate-400">¿Está seguro de eliminar esta imagen del catálogo? Esta acción no se puede deshacer.</span>
                    </div>
                </div>
            `,
            fields: [
                {
                    name: 'admin_password',
                    label: 'Contraseña de Administrador',
                    type: 'password',
                    required: true,
                    placeholder: 'Ingrese su contraseña actual para confirmar...',
                    help: 'Requerida por seguridad para autorizar la eliminación de recursos gráficos.'
                }
            ],
            confirmText: 'Confirmar Eliminación',
            cancelText: 'Cancelar',
            onSubmit: async (values) => {
                if (!values.admin_password) {
                    DkriptToast.error('Debe ingresar su contraseña de administrador.', 'Contraseña Requerida');
                    return;
                }

                try {
                    const deleteUrl = this.config.deleteUrl || '/parameters/delete-image';
                    const response = await fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            path: path,
                            admin_password: values.admin_password
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Contraseña incorrecta o error al eliminar.');
                    }

                    const card = document.querySelector(`.image-card-btn[data-path="${data.deleted_path}"]`);
                    if (card) {
                        card.remove();
                    }

                    const countBadge = document.getElementById('imagesCountBadge');
                    if (countBadge) {
                        countBadge.textContent = `${data.total_count} recursos`;
                    }

                    if (data.new_logo) {
                        const systemLogoInput = document.getElementById('system_logo');
                        if (systemLogoInput) systemLogoInput.value = data.new_logo.path;

                        const previewImg = document.getElementById('logoPreviewImg');
                        if (previewImg) previewImg.src = data.new_logo.url;

                        const nameInput = document.getElementById('current_logo_name');
                        if (nameInput) nameInput.value = data.new_logo.name;

                        const newActiveCard = document.querySelector(`.image-card-btn[data-path="${data.new_logo.path}"]`);
                        if (newActiveCard) {
                            newActiveCard.classList.remove('border-slate-200');
                            newActiveCard.classList.add('border-[#0062f5]', 'ring-2', 'ring-[#0062f5]/20', 'bg-blue-50/30');
                            const check = newActiveCard.querySelector('.active-check-icon');
                            if (check) check.classList.remove('hidden');
                        }
                    }

                    DkriptToast.success('Imagen eliminada exitosamente del catálogo.', 'Operación Exitosa');

                } catch (err) {
                    DkriptToast.error(err.message, 'Error al Eliminar');
                }
            }
        });
    },

    async saveCurrentImageName() {
        const currentPath = document.getElementById('system_logo').value;
        const nameInput = document.getElementById('current_logo_name');
        const newName = nameInput ? nameInput.value.trim() : '';

        if (!newName) {
            DkriptModal.warning('Por favor ingrese un nombre para la imagen.', 'Nombre requerido');
            if (nameInput) nameInput.focus();
            return;
        }

        const btn = document.getElementById('btnSaveName');
        const originalBtnHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<div class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"></div><span>Guardando...</span>';
        }

        try {
            const renameUrl = this.config.renameUrl || '/parameters/rename-image';
            const response = await fetch(renameUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    path: currentPath,
                    name: newName
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al actualizar el nombre.');
            }

            const activeCard = document.querySelector(`.image-card-btn[data-path="${currentPath}"]`);
            if (activeCard) {
                activeCard.setAttribute('data-name', data.image.name);
                const label = activeCard.querySelector('.img-label');
                if (label) {
                    label.textContent = data.image.name;
                }
            }

            this.showNameStatusBadge('Nombre guardado exitosamente');

        } catch (err) {
            DkriptModal.error(err.message, 'Error al cambiar el nombre');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    },

    showNameStatusBadge(text) {
        const badge = document.getElementById('nameStatusBadge');
        if (badge) {
            badge.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${text}`;
            badge.classList.remove('hidden');
            setTimeout(() => {
                badge.classList.add('hidden');
            }, 3500);
        }
    },

    initParametersForm() {
        const parametersForm = document.getElementById('parametersForm');
        if (parametersForm) {
            parametersForm.addEventListener('submit', (e) => {
                e.preventDefault();
                DkriptForm.ajax(parametersForm, {
                    loadingText: 'Guardando...',
                    onSuccess: (data) => {
                        const sidebarLogo = document.getElementById('sidebarSystemLogo');
                        if (sidebarLogo && data.logo_url) {
                            sidebarLogo.src = data.logo_url;
                        }

                        const brandHeader = document.getElementById('sidebarBrandHeader');
                        const sidebarBrandText = document.getElementById('sidebarBrandTextContainer');
                        const sidebarBrandLink = document.getElementById('sidebarBrandLink');
                        if (sidebarBrandText && sidebarLogo) {
                            if (data.show_brand_text) {
                                if (brandHeader) {
                                    brandHeader.className = 'h-16 flex items-center justify-between px-5 bg-[#030712] border-b border-[#0b1739] relative overflow-hidden transition-all duration-300';
                                }
                                sidebarBrandText.classList.remove('hidden');
                                if (sidebarBrandLink) {
                                    sidebarBrandLink.className = 'flex items-center gap-3 mr-2 group flex-1 min-w-0 h-full';
                                }
                                sidebarLogo.className = 'w-9 h-9 object-contain rounded-xl p-0.5 bg-[#071026] border border-[#112356] shadow-md shadow-blue-900/30 group-hover:border-[#00d4ff] group-hover:scale-105 transition-all flex-shrink-0';
                            } else {
                                if (brandHeader) {
                                    brandHeader.className = 'min-h-[5.5rem] sm:min-h-[6rem] py-3.5 px-4 flex items-center justify-between bg-[#030712] border-b border-[#0b1739] relative overflow-hidden transition-all duration-300';
                                }
                                sidebarBrandText.classList.add('hidden');
                                if (sidebarBrandLink) {
                                    sidebarBrandLink.className = 'flex items-center w-full mr-1 group flex-1 min-w-0 h-full';
                                }
                                sidebarLogo.className = 'w-full max-w-full h-auto max-h-20 sm:max-h-22 object-contain object-left rounded-lg transition-all duration-300 group-hover:scale-[1.02]';
                            }
                        }

                        if (data.system_name) {
                            document.title = `${data.system_name} - Parámetros del Sistema`;
                        }

                        const globalModal = document.getElementById('globalSystemModal');
                        if (globalModal && data.modal_style) {
                            globalModal.setAttribute('data-default-style', data.modal_style);
                        }

                        if (data.modal_style && document.body) {
                            document.body.setAttribute('data-modal-style', data.modal_style);
                            window.DKRIPT_MODAL_STYLE = data.modal_style;
                        }

                        if (data.error_display_mode) {
                            this.selectErrorDisplayMode(data.error_display_mode);
                        }
                    }
                });
            });
        }
    },

    selectErrorDisplayMode(mode) {
        const radioScene = document.getElementById('error_mode_scene');
        const radioFullscreen = document.getElementById('error_mode_fullscreen');
        const cardScene = document.getElementById('errorModeCard_scene');
        const cardFullscreen = document.getElementById('errorModeCard_fullscreen');
        const checkScene = document.getElementById('errorModeCheck_scene');
        const checkFullscreen = document.getElementById('errorModeCheck_fullscreen');

        if (mode === 'fullscreen') {
            if (radioFullscreen) radioFullscreen.checked = true;
            if (radioScene) radioScene.checked = false;

            if (cardFullscreen) {
                cardFullscreen.className = 'relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 border-[#0062f5] bg-blue-50/40 shadow-sm flex flex-col justify-between';
            }
            if (cardScene) {
                cardScene.className = 'relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 border-slate-200 bg-white hover:border-slate-300 flex flex-col justify-between';
            }
            if (checkFullscreen) checkFullscreen.classList.remove('hidden');
            if (checkScene) checkScene.classList.add('hidden');
        } else {
            if (radioScene) radioScene.checked = true;
            if (radioFullscreen) radioFullscreen.checked = false;

            if (cardScene) {
                cardScene.className = 'relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 border-[#0062f5] bg-blue-50/40 shadow-sm flex flex-col justify-between';
            }
            if (cardFullscreen) {
                cardFullscreen.className = 'relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300 border-slate-200 bg-white hover:border-slate-300 flex flex-col justify-between';
            }
            if (checkScene) checkScene.classList.remove('hidden');
            if (checkFullscreen) checkFullscreen.classList.add('hidden');
        }
    },

    getSelectedErrorMode() {
        const radioFullscreen = document.getElementById('error_mode_fullscreen');
        return (radioFullscreen && radioFullscreen.checked) ? 'fullscreen' : 'scene';
    },

    openErrorPreviewModal(code, title, badgeClass, badgeText, forcedMode = null) {
        const modal = document.getElementById('errorPreviewModal');
        const iframe = document.getElementById('errorPreviewIframe');
        const titleEl = document.getElementById('errorPreviewTitle');
        const badgeEl = document.getElementById('errorPreviewBadge');
        
        if (!modal || !iframe) return;

        this.state.currentPreviewErrorCode = code;
        this.state.currentPreviewErrorTitle = title;
        this.state.currentPreviewBadgeClass = badgeClass;
        this.state.currentPreviewBadgeText = badgeText;
        this.state.activePreviewErrorMode = forcedMode || this.getSelectedErrorMode();

        this.updatePreviewModalIframe();
        this.updatePreviewModeButtonsUI(this.state.activePreviewErrorMode);

        if (titleEl) titleEl.textContent = title;
        if (badgeEl) {
            badgeEl.className = `px-2 py-0.5 rounded-full text-[10px] font-mono font-extrabold ${badgeClass}`;
            badgeEl.textContent = badgeText;
        }

        this.setPreviewDevice('desktop');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    },

    setPreviewErrorMode(mode) {
        this.state.activePreviewErrorMode = mode;
        this.updatePreviewModeButtonsUI(mode);
        this.updatePreviewModalIframe();
    },

    updatePreviewModeButtonsUI(mode) {
        const btnScene = document.getElementById('previewModeBtnScene');
        const btnFullscreen = document.getElementById('previewModeBtnFullscreen');

        if (mode === 'fullscreen') {
            if (btnFullscreen) {
                btnFullscreen.classList.add('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btnFullscreen.classList.remove('text-slate-400', 'hover:bg-[#112356]');
            }
            if (btnScene) {
                btnScene.classList.remove('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btnScene.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-[#112356]');
            }
        } else {
            if (btnScene) {
                btnScene.classList.add('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btnScene.classList.remove('text-slate-400', 'hover:bg-[#112356]');
            }
            if (btnFullscreen) {
                btnFullscreen.classList.remove('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btnFullscreen.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-[#112356]');
            }
        }
    },

    updatePreviewModalIframe() {
        const iframe = document.getElementById('errorPreviewIframe');
        const newTabBtn = document.getElementById('errorPreviewNewTabBtn');
        const baseUrl = this.config.previewBaseUrl || '/parameters/errors/preview';
        const previewUrl = `${baseUrl}/${this.state.currentPreviewErrorCode}?mode=${this.state.activePreviewErrorMode}`;
        
        if (iframe) iframe.src = previewUrl;
        if (newTabBtn) newTabBtn.href = previewUrl;
    },

    closeErrorPreviewModal() {
        const modal = document.getElementById('errorPreviewModal');
        const iframe = document.getElementById('errorPreviewIframe');
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (iframe) iframe.src = '';
        document.body.style.overflow = '';
    },

    setPreviewDevice(device) {
        const wrapper = document.getElementById('errorPreviewFrameWrapper');
        const btnDesktop = document.getElementById('previewBtnDesktop');
        const btnTablet = document.getElementById('previewBtnTablet');
        const btnMobile = document.getElementById('previewBtnMobile');
        const resText = document.getElementById('previewResIndicator');

        if (!wrapper) return;

        [btnDesktop, btnTablet, btnMobile].forEach(btn => {
            if (btn) {
                btn.classList.remove('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btn.classList.add('text-slate-400', 'hover:text-white', 'hover:bg-[#112356]');
            }
        });

        if (device === 'mobile') {
            wrapper.style.maxWidth = '390px';
            if (btnMobile) {
                btnMobile.classList.add('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btnMobile.classList.remove('text-slate-400', 'hover:bg-[#112356]');
            }
            if (resText) resText.textContent = '390 x 844 px (iPhone / Móvil)';
        } else if (device === 'tablet') {
            wrapper.style.maxWidth = '768px';
            if (btnTablet) {
                btnTablet.classList.add('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btnTablet.classList.remove('text-slate-400', 'hover:bg-[#112356]');
            }
            if (resText) resText.textContent = '768 x 1024 px (iPad / Tablet)';
        } else {
            wrapper.style.maxWidth = '100%';
            if (btnDesktop) {
                btnDesktop.classList.add('bg-[#0062f5]', 'text-white', 'shadow-sm');
                btnDesktop.classList.remove('text-slate-400', 'hover:bg-[#112356]');
            }
            if (resText) resText.textContent = '100% Pantalla Completa (Desktop)';
        }
    },

    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('errorPreviewModal');
                if (modal && !modal.classList.contains('hidden')) {
                    this.closeErrorPreviewModal();
                }
            }
        });
    },

    async uploadErrorMedia(code, type, input) {
        if (!input || !input.files || !input.files[0]) return;
        const file = input.files[0];

        // Validaciones client-side
        if (type === 'video') {
            const maxVideoSize = 50 * 1024 * 1024; // 50MB
            if (file.size > maxVideoSize) {
                if (window.DkriptToast) DkriptToast.error('El video supera el límite máximo permitido de 50 MB.', 'Archivo Demasiado Pesado');
                input.value = '';
                return;
            }
            const allowedVideoTypes = ['video/mp4', 'video/webm'];
            if (!allowedVideoTypes.includes(file.type) && !file.name.match(/\.(mp4|webm)$/i)) {
                if (window.DkriptToast) DkriptToast.error('Formato de video no válido. Se admiten únicamente MP4 o WebM.', 'Formato no permitido');
                input.value = '';
                return;
            }
        } else {
            const maxImageSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxImageSize) {
                if (window.DkriptToast) DkriptToast.error('La imagen supera el límite máximo permitido de 10 MB.', 'Archivo Demasiado Pesado');
                input.value = '';
                return;
            }
            const allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedImageTypes.includes(file.type) && !file.name.match(/\.(jpe?g|png|webp)$/i)) {
                if (window.DkriptToast) DkriptToast.error('Formato de imagen no válido. Se admiten únicamente JPG, PNG o WebP.', 'Formato no permitido');
                input.value = '';
                return;
            }
        }

        const formData = new FormData();
        formData.append('code', code);
        formData.append('type', type);
        formData.append('file', file);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || document.querySelector('input[name="_token"]')?.value;

        try {
            if (window.DkriptToast) DkriptToast.info(`Cargando ${type} para el error ${code}...`, 'Subiendo Archivo', 3000);
            const response = await fetch('/parameters/errors/upload-media', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                },
                body: formData
            });

            const data = await response.json();
            if (response.ok && data.success) {
                if (window.DkriptToast) DkriptToast.success(data.message, 'Operación Exitosa');
                setTimeout(() => window.location.reload(), 700);
            } else {
                if (window.DkriptToast) DkriptToast.error(data.message || 'Error al subir el archivo.', 'Fallo de Carga');
            }
        } catch (error) {
            console.error('Error al subir media de error:', error);
            if (window.DkriptToast) DkriptToast.error('Error de red al subir el archivo multimedia.', 'Error de Conexión');
        } finally {
            input.value = '';
        }
    },

    async deleteErrorMedia(code, type) {
        let confirmed = true;
        if (window.DkriptModal && typeof window.DkriptModal.confirm === 'function') {
            confirmed = await DkriptModal.confirm(
                `¿Estás seguro de que deseas eliminar el ${type} personalizado del error ${code}? La página utilizará el siguiente recurso en la cascada.`,
                `Eliminar ${type.toUpperCase()}`
            );
        } else {
            confirmed = confirm(`¿Estás seguro de que deseas eliminar el ${type} personalizado del error ${code}?`);
        }

        if (!confirmed) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || document.querySelector('input[name="_token"]')?.value;

        try {
            const response = await fetch('/parameters/errors/delete-media', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                },
                body: JSON.stringify({ code, type })
            });

            const data = await response.json();
            if (response.ok && data.success) {
                if (window.DkriptToast) DkriptToast.success(data.message, 'Operación Exitosa');
                setTimeout(() => window.location.reload(), 700);
            } else {
                if (window.DkriptToast) DkriptToast.error(data.message || 'Error al eliminar el recurso multimedia.', 'Fallo');
            }
        } catch (error) {
            console.error('Error al eliminar media de error:', error);
            if (window.DkriptToast) DkriptToast.error('Error de red al eliminar el recurso.', 'Error de Conexión');
        }
    },

    async updateErrorTexts(code) {
        const title = document.getElementById(`error_title_${code}`)?.value || '';
        const badge = document.getElementById(`error_badge_${code}`)?.value || '';
        const message = document.getElementById(`error_message_${code}`)?.value || '';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                       || document.querySelector('input[name="_token"]')?.value;

        try {
            const response = await fetch('/parameters/errors/update', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                },
                body: JSON.stringify({ code, title, badge, message })
            });

            const data = await response.json();
            if (response.ok && data.success) {
                if (window.DkriptToast) DkriptToast.success(data.message, 'Operación Exitosa');
            } else {
                if (window.DkriptToast) DkriptToast.error(data.message || 'Error al actualizar los textos.', 'Error');
            }
        } catch (error) {
            console.error('Error al actualizar textos de error:', error);
            if (window.DkriptToast) DkriptToast.error('Error de red al actualizar los textos.', 'Error de Conexión');
        }
    },

    async testSmtpConnection(testUrl, csrfToken, defaultEmail) {
        const btn = document.getElementById('btnTestSmtp');
        if (!btn) return;

        const recipient = await DkriptModal.prompt({
            title: 'Prueba de Servidor SMTP',
            message: 'Ingresa el correo electrónico destinatario para enviar el mensaje de prueba y diagnóstico:',
            defaultValue: defaultEmail || 'admin@dkript.com',
            placeholder: 'ej. soporte@dkript.com',
            inputType: 'email',
            dialogType: 'info',
            confirmText: 'Enviar Prueba',
            cancelText: 'Cancelar',
            help: 'Se remitirá un correo con la telemetría de conexión del servidor SMTP configurado.'
        });
        if (!recipient) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Probando Conexión...';

        fetch(testUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ test_email: recipient })
        })
        .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data })))
        .then(response => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (response.ok && response.data.success) {
                DkriptToast.success(response.data.message, 'Conexión Exitosa');
            } else {
                DkriptToast.error(response.data.message || 'Error al conectar con el servidor SMTP', 'Fallo SMTP');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            DkriptToast.error('No se pudo establecer comunicación con el servidor: ' + err.message, 'Error de Red');
        });
    },

    async testWhatsAppConnection(testUrl, csrfToken, defaultPhone) {
        const btn = document.getElementById('btnTestWhatsApp');
        if (!btn) return;

        const recipient = await DkriptModal.prompt({
            title: 'Prueba de WhatsApp Cloud API',
            message: 'Ingresa el número de WhatsApp con código de país para la prueba:',
            defaultValue: defaultPhone || '5215512345678',
            placeholder: 'ej. 5215512345678 o +14155238886',
            inputType: 'tel',
            dialogType: 'info',
            confirmText: 'Enviar Mensaje',
            cancelText: 'Cancelar',
            help: 'Formato internacional: código de país seguido del número telefónico sin espacios ni guiones.'
        });
        if (!recipient) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Probando Conexión...';

        fetch(testUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ test_phone: recipient })
        })
        .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data })))
        .then(response => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (response.ok && response.data.success) {
                DkriptToast.success(response.data.message || 'Mensaje de prueba enviado exitosamente.', 'WhatsApp Conectado');
            } else {
                DkriptToast.error(response.data.message || 'Error al conectar con la API de WhatsApp', 'Fallo WhatsApp');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            DkriptToast.error('No se pudo establecer comunicación con el servidor: ' + err.message, 'Error de Red');
        });
    },

    async clearCache(url, csrfToken) {
        const confirmed = await DkriptModal.confirm(
            '¿Está seguro de que desea depurar la memoria caché del sistema? Se limpiará la caché de configuración, rutas, vistas y compilados.',
            'Depurar Caché del Sistema',
            { type: 'warning', confirmText: 'Depurar Caché', cancelText: 'Cancelar' }
        );
        if (!confirmed) {
            return;
        }

        const btn = document.getElementById('btnClearSystemCache');
        if (!btn) return;

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Depurando Caché...';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data })))
        .then(response => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (response.ok && response.data.success) {
                DkriptToast.success(response.data.message || 'Caché depurada exitosamente.', 'Optimización Completada');
            } else {
                DkriptToast.error(response.data.message || 'Error al depurar la memoria caché.', 'Error de Mantenimiento');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            DkriptToast.error('No se pudo comunicar con el servidor: ' + err.message, 'Error de Red');
        });
    }
};

window.DkriptParameters = DkriptParameters;
window.switchParameterTab = function(tabId) { DkriptParameters.switchTab(tabId); };
window.clearSystemCache = function(url, csrfToken) { DkriptParameters.clearCache(url, csrfToken); };
window.testWhatsAppConnection = function(url, csrfToken, defaultPhone) { DkriptParameters.testWhatsAppConnection(url, csrfToken, defaultPhone); };
window.updateMaintenanceBadge = function(cb) { DkriptParameters.updateMaintenanceBadge(cb); };
window.updateBrandTextBadge = function(cb) { DkriptParameters.updateBrandTextBadge(cb); };
window.selectModalStyle = function(styleId, cardEl) { DkriptParameters.selectModalStyle(styleId, cardEl); };
window.triggerFileUpload = function() { DkriptParameters.triggerFileUpload(); };
window.showCatalogHover = function(e, src, name, el) { DkriptParameters.showCatalogHover(e, src, name, el); };
window.hideCatalogHover = function() { DkriptParameters.hideCatalogHover(); };
window.selectImage = function(path, name, btnEl) { DkriptParameters.selectImage(path, name, btnEl); };
window.optimizeImageForUpload = function(file) { return DkriptParameters.optimizeImageForUpload(file); };
window.uploadImageFile = function(file) { return DkriptParameters.uploadImageFile(file); };
window.handleLogoUpload = function(input) { DkriptParameters.handleLogoUpload(input); };
window.addNewImageToGrid = function(img, url) { DkriptParameters.addNewImageToGrid(img, url); };
window.openDeleteModal = function(path, name, url) { DkriptParameters.openDeleteModal(path, name, url); };
window.saveCurrentImageName = function() { return DkriptParameters.saveCurrentImageName(); };
window.showNameStatusBadge = function(txt) { DkriptParameters.showNameStatusBadge(txt); };
window.selectErrorDisplayMode = function(m) { DkriptParameters.selectErrorDisplayMode(m); };
window.getSelectedErrorMode = function() { return DkriptParameters.getSelectedErrorMode(); };
window.openErrorPreviewModal = function(c, t, bc, bt, fm) { DkriptParameters.openErrorPreviewModal(c, t, bc, bt, fm); };
window.setPreviewErrorMode = function(m) { DkriptParameters.setPreviewErrorMode(m); };
window.updatePreviewModeButtonsUI = function(m) { DkriptParameters.updatePreviewModeButtonsUI(m); };
window.updatePreviewModalIframe = function() { DkriptParameters.updatePreviewModalIframe(); };
window.closeErrorPreviewModal = function() { DkriptParameters.closeErrorPreviewModal(); };
window.setPreviewDevice = function(d) { DkriptParameters.setPreviewDevice(d); };
window.uploadErrorMedia = function(c, t, i) { DkriptParameters.uploadErrorMedia(c, t, i); };
window.deleteErrorMedia = function(c, t) { DkriptParameters.deleteErrorMedia(c, t); };
window.updateErrorTexts = function(c) { DkriptParameters.updateErrorTexts(c); };

/* ==========================================================================
   6. MOTOR DE ESCENAS CINEMÁTICAS DE ERROR & PARTÍCULAS (DkriptErrorScene)
   ========================================================================== */
const DkriptErrorScene = {
    init(errorCode = '404') {
        this.initCyberCanvas();
        this.initParallax();
        if (window.gsap) {
            this.initTimeline(errorCode);
        }
    },

    initCyberCanvas() {
        const canvas = document.getElementById('cyberCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        const particles = [];
        const particleCount = Math.min(width > 768 ? 55 : 25, 60);

        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                radius: Math.random() * 1.8 + 0.5,
                alpha: Math.random() * 0.6 + 0.2,
                color: Math.random() > 0.3 ? '#00d4ff' : '#60a5fa'
            });
        }

        function render() {
            ctx.clearRect(0, 0, width, height);
            for (let i = 0; i < particles.length; i++) {
                const p = particles[i];
                p.x += p.vx;
                p.y += p.vy;

                if (p.x < 0) p.x = width;
                if (p.x > width) p.x = 0;
                if (p.y < 0) p.y = height;
                if (p.y > height) p.y = 0;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = p.color;
                ctx.globalAlpha = p.alpha;
                ctx.shadowBlur = 8;
                ctx.shadowColor = p.color;
                ctx.fill();
            }
            requestAnimationFrame(render);
        }
        render();

        window.addEventListener('resize', () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });
    },

    initParallax() {
        document.addEventListener('mousemove', (e) => {
            const mascot = document.getElementById('dryptMascot');
            if (!mascot || !window.gsap) return;
            const x = (e.clientX / window.innerWidth - 0.5) * 15;
            const y = (e.clientY / window.innerHeight - 0.5) * 15;
            gsap.to(mascot, { duration: 0.8, x: x, y: y, ease: "power1.out" });
        });
    },

    initTimeline(code) {
        const strCode = String(code);
        const drypt = document.getElementById('dryptMascotMedia') || document.getElementById('dryptImg');

        if (strCode === '404') {
            const tl = gsap.timeline({ repeat: -1 });
            const beam = document.getElementById('scannerBeamCone');
            const holo404 = document.getElementById('holo404Text');
            const node1 = document.getElementById('orbitNode1');

            function beamFlash() {
                if (beam) gsap.to(beam, { duration: 0.1, opacity: 0.95, scale: 1.1, yoyo: true, repeat: 3 });
                if (drypt) gsap.to(drypt, { duration: 0.1, filter: 'drop-shadow(0 0 35px rgba(0, 212, 255, 0.9))', yoyo: true, repeat: 3 });
                if (holo404) gsap.to(holo404, { duration: 0.1, opacity: 0.6, yoyo: true, repeat: 3 });
            }

            function beamOff() {
                if (beam) gsap.to(beam, { duration: 0.3, opacity: 0.45 });
                if (drypt) gsap.to(drypt, { duration: 0.3, filter: 'drop-shadow(0 0 15px rgba(0, 212, 255, 0.3))' });
                if (holo404) gsap.to(holo404, { duration: 0.3, opacity: 0.25 });
            }

            if (beam) {
                tl.to(beam, { duration: 2.5, rotation: 18, transformOrigin: "200px 190px", ease: "power1.inOut" }, 0)
                  .call(beamFlash, null, 2.5)
                  .to(beam, { duration: 2.5, rotation: -12, transformOrigin: "200px 190px", ease: "power1.inOut" }, 3)
                  .call(beamOff, null, 5.5)
                  .to(beam, { duration: 1.5, rotation: 0, transformOrigin: "200px 190px", ease: "power2.out" }, 6)
                  .call(beamFlash, null, 7.5);
            }

            if (node1) {
                gsap.to(node1, {
                    duration: 4,
                    rotation: 360,
                    transformOrigin: "120px 120px",
                    repeat: -1,
                    ease: "none"
                });
            }
        } else if (strCode === '403') {
            const tl = gsap.timeline({ repeat: -1 });
            const shield = document.getElementById('hexShieldRing');
            const wave = document.getElementById('deflectorWave');
            const lock = document.getElementById('securityLockBadge');

            if (wave) tl.to(wave, { duration: 1.8, r: 230, opacity: 0, ease: "power2.out" }, 0);
            if (drypt) tl.to(drypt, { duration: 0.15, filter: 'drop-shadow(0 0 40px rgba(244, 63, 94, 0.9))', yoyo: true, repeat: 5 }, 1.5);
            if (lock) tl.to(lock, { duration: 0.2, scale: 1.25, yoyo: true, repeat: 3 }, 1.5);
            if (shield) tl.to(shield, { duration: 0.2, stroke: '#ffffff', yoyo: true, repeat: 2 }, 1.8);
            if (wave) tl.set(wave, { r: 140, opacity: 0.4 }, 3.5);
        } else if (strCode === '500') {
            const tl = gsap.timeline({ repeat: -1 });
            const arc1 = document.getElementById('electricArc1');
            const arc2 = document.getElementById('electricArc2');
            const arc3 = document.getElementById('electricArc3');
            const core = document.getElementById('coreWarning');

            if (arc1) tl.to(arc1, { duration: 0.08, opacity: 1, yoyo: true, repeat: 5 }, 1);
            if (drypt) tl.to(drypt, { duration: 0.05, x: 5, y: -4, yoyo: true, repeat: 7 }, 1);
            if (arc2) tl.to(arc2, { duration: 0.06, opacity: 1, yoyo: true, repeat: 4 }, 1.8);
            if (arc3) tl.to(arc3, { duration: 0.08, opacity: 1, yoyo: true, repeat: 6 }, 2.5);
            if (core) tl.to(core, { duration: 0.3, scale: 1.15, opacity: 0.8, yoyo: true, repeat: 2 }, 2.5);
        } else if (strCode === '419') {
            const tl = gsap.timeline({ repeat: -1 });
            const badge = document.getElementById('chronoBadge');

            if (drypt) {
                tl.to(drypt, { duration: 2, scale: 1.05, filter: 'drop-shadow(0 0 35px rgba(168, 85, 247, 0.8))', ease: "power1.inOut" }, 0)
                  .to(drypt, { duration: 2, scale: 0.98, filter: 'drop-shadow(0 0 15px rgba(168, 85, 247, 0.3))', ease: "power1.inOut" }, 2);
            }
            if (badge) {
                tl.to(badge, { duration: 0.5, rotation: 180, ease: "back.out(1.7)" }, 3.5);
            }
        } else if (strCode === '503') {
            const tl = gsap.timeline({ repeat: -1 });
            const ring = document.getElementById('rechargeRing');

            if (drypt) tl.to(drypt, { duration: 1.5, filter: 'drop-shadow(0 0 40px rgba(16, 185, 129, 0.85))', ease: "power2.inOut" }, 0);
            if (ring) tl.to(ring, { duration: 1.5, strokeWidth: 3.5, opacity: 0.8, ease: "power2.inOut" }, 0);
            if (drypt) tl.to(drypt, { duration: 1.5, filter: 'drop-shadow(0 0 15px rgba(16, 185, 129, 0.3))', ease: "power2.inOut" }, 1.5);
            if (ring) tl.to(ring, { duration: 1.5, strokeWidth: 2, opacity: 0.4, ease: "power2.inOut" }, 1.5);
        }
    }
};

window.DkriptErrorScene = DkriptErrorScene;

/**
 * DkriptOtp: Módulo para gestión de verificación OTP, auto-enfoque de 6 cajas,
 * pegado de portapapeles, sincronización de campo oculto y temporizador de enfriamiento.
 */
const DkriptOtp = {
    timerInterval: null,

    init() {
        const otpContainer = document.getElementById('dkriptOtpContainer');
        if (!otpContainer) return;

        const inputs = otpContainer.querySelectorAll('.otp-input-field');
        const hiddenCode = document.getElementById('otpHiddenCode');
        const form = document.getElementById('frmVerifyOtp');

        if (!inputs.length) return;

        // Auto-focus al primer campo disponible
        setTimeout(() => {
            const firstEmpty = Array.from(inputs).find(input => !input.value) || inputs[0];
            firstEmpty.focus();
        }, 150);

        const updateHiddenCode = () => {
            let fullCode = '';
            inputs.forEach(inp => {
                fullCode += inp.value;
                if (inp.value) {
                    inp.classList.add('is-filled');
                } else {
                    inp.classList.remove('is-filled');
                }
            });
            if (hiddenCode) {
                hiddenCode.value = fullCode;
            }
            return fullCode;
        };

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val ? val[val.length - 1] : '';

                const code = updateHiddenCode();

                if (e.target.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                    inputs[index + 1].select();
                }

                // Auto-submit si se completan los 6 dígitos
                if (code.length === 6 && form) {
                    form.submit();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace') {
                    if (!input.value && index > 0) {
                        inputs[index - 1].focus();
                        inputs[index - 1].value = '';
                        updateHiddenCode();
                    } else {
                        input.value = '';
                        updateHiddenCode();
                    }
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    inputs[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text').trim().replace(/\D/g, '');
                if (!pastedData) return;

                const chars = pastedData.slice(0, inputs.length).split('');
                chars.forEach((char, i) => {
                    if (inputs[i]) {
                        inputs[i].value = char;
                    }
                });

                const code = updateHiddenCode();
                const nextIndex = Math.min(chars.length, inputs.length - 1);
                inputs[nextIndex].focus();

                if (code.length === 6 && form) {
                    form.submit();
                }
            });
        });

        // Inicializar estado de campos si vienen pre-llenados
        updateHiddenCode();
    },

    startCountdown(seconds, displayElementId, resendBtnId) {
        let remaining = seconds;
        const display = document.getElementById(displayElementId);
        const btn = document.getElementById(resendBtnId);

        if (!display || !btn) return;

        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }

        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');

        const tick = () => {
            if (remaining <= 0) {
                clearInterval(DkriptOtp.timerInterval);
                display.innerText = '00:00';
                display.parentElement?.classList.add('hidden');
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
            } else {
                const mins = String(Math.floor(remaining / 60)).padStart(2, '0');
                const secs = String(remaining % 60).padStart(2, '0');
                display.innerText = `${mins}:${secs}`;
                remaining--;
            }
        };

        tick();
        this.timerInterval = setInterval(tick, 1000);
    },

    fillCode(code) {
        const otpContainer = document.getElementById('dkriptOtpContainer');
        if (!otpContainer) return;
        const inputs = otpContainer.querySelectorAll('.otp-input-field');
        const digits = String(code).split('');

        inputs.forEach((inp, idx) => {
            inp.value = digits[idx] || '';
            if (inp.value) inp.classList.add('is-filled');
        });

        const hiddenCode = document.getElementById('otpHiddenCode');
        if (hiddenCode) hiddenCode.value = String(code);

        const form = document.getElementById('frmVerifyOtp');
        if (form && digits.length === 6) {
            if (window.DkriptToast) {
                DkriptToast.info('Código aplicado desde simulador. Verificando...', 'Simulador');
            }
            setTimeout(() => form.submit(), 400);
        }
    }
};

window.DkriptOtp = DkriptOtp;

// Auto-inicializar DkriptOtp en carga
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => DkriptOtp.init());
} else {
    DkriptOtp.init();
}

/* ==========================================================================
   MÓDULO DE CENTRO DE NOTIFICACIONES IN-APP (DKRIPT NOTIFICATION)
   ========================================================================== */
const DkriptNotification = {
    pollInterval: null,
    isFetching: false,

    init() {
        const bellBtn = document.getElementById('notificationBellBtn');
        if (!bellBtn) return;

        // Carga inicial de notificaciones para actualizar badge
        this.fetchNotifications(false);

        // Polling cada 45 segundos para mantener actualizado el contador en tiempo real
        if (!this.pollInterval) {
            this.pollInterval = setInterval(() => {
                this.fetchNotifications(false);
            }, 45000);
        }

        // Cierre con click/tap fuera del contenedor
        document.addEventListener('pointerdown', (e) => {
            const container = document.getElementById('notificationDropdownContainer');
            if (container && !container.contains(e.target)) {
                this.closeDropdown();
            }
        });

        // Cierre con tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeDropdown();
            }
        });
    },

    toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        const isHidden = dropdown.classList.contains('hidden');
        if (isHidden) {
            this.openDropdown();
        } else {
            this.closeDropdown();
        }
    },

    openDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        const bellBtn = document.getElementById('notificationBellBtn');
        if (!dropdown) return;

        dropdown.classList.remove('hidden');
        requestAnimationFrame(() => {
            dropdown.classList.remove('opacity-0', 'scale-95');
            dropdown.classList.add('opacity-100', 'scale-100');
        });
        if (bellBtn) bellBtn.setAttribute('aria-expanded', 'true');

        // Refrescar contenido cada vez que se abre
        this.fetchNotifications(true);
    },

    closeDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        const bellBtn = document.getElementById('notificationBellBtn');
        if (!dropdown || dropdown.classList.contains('hidden')) return;

        dropdown.classList.remove('opacity-100', 'scale-100');
        dropdown.classList.add('opacity-0', 'scale-95');
        if (bellBtn) bellBtn.setAttribute('aria-expanded', 'false');
        setTimeout(() => dropdown.classList.add('hidden'), 150);
    },

    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },

    async fetchNotifications(renderList = false) {
        if (this.isFetching) return;
        this.isFetching = true;

        try {
            const response = await fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            this.updateBadge(data.unread_count || 0);

            if (renderList) {
                this.renderDropdownList(data.notifications || [], data.unread_count || 0);
            }
        } catch (error) {
            console.error('Error al sincronizar notificaciones:', error);
        } finally {
            this.isFetching = false;
        }
    },

    updateBadge(count) {
        const badge = document.getElementById('notificationBadge');
        const subtitle = document.getElementById('notificationDropdownSubtitle');
        const pageCount = document.getElementById('pageUnreadCount');

        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
                badge.textContent = '0';
            }
        }

        if (subtitle) {
            subtitle.textContent = count === 1 ? '1 pendiente' : `${count} pendientes`;
        }

        if (pageCount) {
            pageCount.textContent = count;
        }
    },

    renderDropdownList(notifications, unreadCount) {
        const listEl = document.getElementById('notificationList');
        if (!listEl) return;

        if (notifications.length === 0) {
            listEl.innerHTML = `
                <div class="py-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-2 select-none">
                    <div class="w-10 h-10 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 text-lg">
                        <i class="bi bi-bell-slash"></i>
                    </div>
                    <span class="font-extrabold text-slate-200">Sin notificaciones pendientes</span>
                    <span class="text-[10px] text-slate-400">Todo el sistema se encuentra al día.</span>
                </div>
            `;
            return;
        }

        const html = notifications.map(n => {
            const isUnread = !n.is_read;
            const icon = n.icon || 'bi-bell-fill';
            const type = (n.type || 'info').toLowerCase();

            let iconTheme = 'bg-sky-500/20 text-sky-400 border-sky-500/40';
            if (['security', 'warning'].includes(type)) iconTheme = 'bg-amber-500/20 text-amber-400 border-amber-500/40';
            else if (type === 'system') iconTheme = 'bg-purple-500/20 text-purple-400 border-purple-500/40';
            else if (type === 'backup') iconTheme = 'bg-blue-500/20 text-[#00D1FF] border-[#00D1FF]/40';
            else if (type === 'success') iconTheme = 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40';
            else if (type === 'error') iconTheme = 'bg-rose-500/20 text-rose-400 border-rose-500/40';

            return `
                <div id="dropdown-notif-${n.id}" class="p-2.5 rounded-2xl transition-all ${isUnread ? 'bg-[#00D1FF]/10 border border-[#00D1FF]/30' : 'bg-white/5 border border-white/5 hover:bg-white/10'} group/item relative">
                    <div class="flex items-start gap-2.5">
                        <div class="w-8 h-8 rounded-xl ${iconTheme} border flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
                            <i class="bi ${icon}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <h5 class="text-xs font-black truncate text-slate-200 ${isUnread ? 'text-[#00D1FF]' : ''}">${n.title}</h5>
                                <span class="text-[9px] font-mono-code text-slate-400 flex-shrink-0">${n.created_at_human}</span>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-snug line-clamp-2 mb-1.5 font-normal">${n.message}</p>
                            
                            <div class="flex items-center justify-between gap-2 pt-1 border-t border-white/5">
                                <div>
                                    ${n.action_url ? `
                                        <a href="${n.action_url}" onclick="DkriptNotification.markAsRead('${n.id}', false)" class="text-[10px] text-[#00D1FF] hover:underline font-bold inline-flex items-center gap-1">
                                            <span>Ver detalle</span>
                                            <i class="bi bi-box-arrow-up-right text-[9px]"></i>
                                        </a>
                                    ` : ''}
                                </div>
                                <div class="flex items-center gap-1">
                                    ${isUnread ? `
                                        <button type="button" onclick="DkriptNotification.markAsRead('${n.id}', false)" 
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold text-slate-400 hover:text-[#00D1FF] hover:bg-white/10 transition-colors cursor-pointer" title="Marcar como leída">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                    ` : ''}
                                    <button type="button" onclick="DkriptNotification.deleteNotification('${n.id}', false)" 
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold text-slate-400 hover:text-rose-400 hover:bg-rose-500/20 transition-colors cursor-pointer" title="Eliminar">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        listEl.innerHTML = html;
    },

    async markAsRead(id, refreshPage = false) {
        try {
            const response = await fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Error al actualizar notificación');
            const data = await response.json();
            this.updateBadge(data.unread_count || 0);

            if (refreshPage) {
                const row = document.getElementById(`notification-row-${id}`);
                if (row) {
                    row.classList.remove('bg-blue-50/40');
                    row.classList.add('bg-white');
                    const badge = row.querySelector('.text-rose-600');
                    if (badge) badge.remove();
                    const checkBtn = row.querySelector('button[title="Marcar como leída"]');
                    if (checkBtn) checkBtn.remove();
                } else {
                    window.location.reload();
                }
            } else {
                this.fetchNotifications(true);
            }

            if (window.DkriptToast) {
                DkriptToast.success('Notificación marcada como leída', 'Centro de Alertas');
            }
        } catch (e) {
            console.error(e);
            if (window.DkriptToast) DkriptToast.error('No se pudo marcar la notificación', 'Error');
        }
    },

    async markAllAsRead(refreshPage = false) {
        try {
            const response = await fetch('/notifications/read-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Error al marcar todas');
            const data = await response.json();
            this.updateBadge(0);

            if (refreshPage) {
                window.location.reload();
            } else {
                this.fetchNotifications(true);
            }

            if (window.DkriptToast) {
                DkriptToast.success('Todas las notificaciones marcadas como leídas', 'Centro de Alertas');
            }
        } catch (e) {
            console.error(e);
            if (window.DkriptToast) DkriptToast.error('No se pudo procesar la solicitud', 'Error');
        }
    },

    async deleteNotification(id, refreshPage = false) {
        try {
            const response = await fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Error al eliminar notificación');
            const data = await response.json();
            this.updateBadge(data.unread_count || 0);

            if (refreshPage) {
                const row = document.getElementById(`notification-row-${id}`);
                if (row) {
                    row.remove();
                } else {
                    window.location.reload();
                }
            } else {
                const item = document.getElementById(`dropdown-notif-${id}`);
                if (item) item.remove();
                this.fetchNotifications(true);
            }

            if (window.DkriptToast) {
                DkriptToast.info('Notificación eliminada', 'Centro de Alertas');
            }
        } catch (e) {
            console.error(e);
            if (window.DkriptToast) DkriptToast.error('No se pudo eliminar la notificación', 'Error');
        }
    },

    async clearAll(refreshPage = false) {
        const confirmed = await DkriptModal.confirm(
            '¿Desea vaciar permanentemente todas las notificaciones de su bandeja?',
            'Vaciar Notificaciones',
            { type: 'warning', confirmText: 'Vaciar Bandeja', cancelText: 'Cancelar' }
        );
        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch('/notifications', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Error al vaciar bandeja');
            this.updateBadge(0);

            if (refreshPage) {
                window.location.reload();
            } else {
                this.renderDropdownList([], 0);
            }

            if (window.DkriptToast) {
                DkriptToast.success('Bandeja de notificaciones vaciada', 'Centro de Alertas');
            }
        } catch (e) {
            console.error(e);
            if (window.DkriptToast) DkriptToast.error('No se pudo vaciar la bandeja', 'Error');
        }
    }
};

window.DkriptNotification = DkriptNotification;

// Auto-inicializar DkriptNotification en carga
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => DkriptNotification.init());
} else {
    DkriptNotification.init();
}

/* ==========================================================================
   MÓDULO DE BITÁCORA Y REGISTROS DE AUDITORÍA (DKRIPT AUDIT)
   ========================================================================== */
const DkriptAudit = {
    init() {
        const modal = document.getElementById('modalAuditDetail');
        if (!modal) return;

        const urlParams = new URLSearchParams(window.location.search);
        const detailId = urlParams.get('detail');
        if (detailId) {
            setTimeout(() => {
                this.showDetail(detailId);
            }, 120);
        }
    },

    async showDetail(auditId) {
        const modal = document.getElementById('modalAuditDetail');
        if (!modal) return;

        // Reset fields to loading state
        const subEl = document.getElementById('auditDetailSubtitle');
        const userEl = document.getElementById('auditDetailUser');
        const ipEl = document.getElementById('auditDetailIp');
        const methEl = document.getElementById('auditDetailMethod');
        const dateEl = document.getElementById('auditDetailDate');
        const descEl = document.getElementById('auditDetailDescription');
        const urlEl = document.getElementById('auditDetailUrl');
        const agentEl = document.getElementById('auditDetailUserAgent');
        const oldEl = document.getElementById('auditDetailOldValues');
        const newEl = document.getElementById('auditDetailNewValues');

        if (subEl) subEl.textContent = `Cargando registro #${auditId}...`;
        if (userEl) userEl.textContent = '...';
        if (ipEl) ipEl.textContent = '...';
        if (methEl) methEl.textContent = '...';
        if (dateEl) dateEl.textContent = '...';
        if (descEl) descEl.textContent = 'Consultando información detallada del evento...';
        if (urlEl) urlEl.textContent = '...';
        if (agentEl) agentEl.textContent = '...';
        if (oldEl) oldEl.textContent = '// Cargando...';
        if (newEl) newEl.textContent = '// Cargando...';

        openModal('modalAuditDetail');

        try {
            const response = await fetch(`/audit-logs/${auditId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) throw new Error('No se pudo cargar el registro.');

            const data = await response.json();
            const log = data.log;

            if (subEl) subEl.textContent = `Registro #${log.id} — Módulo ${log.module} (${log.action})`;
            if (userEl) userEl.textContent = `${log.user_name} (${log.user_email})`;
            if (ipEl) ipEl.textContent = log.ip_address;
            if (methEl) methEl.textContent = log.method;
            if (dateEl) dateEl.textContent = `${log.created_at_formatted} (${log.created_at_human})`;
            if (descEl) descEl.textContent = log.description;
            if (urlEl) urlEl.textContent = log.url;
            if (agentEl) agentEl.textContent = log.user_agent;

            // Formato JSON legible
            const oldStr = log.old_values ? JSON.stringify(log.old_values, null, 2) : '// Sin estado previo registrado (Operación de creación o solo lectura)';
            const newStr = log.new_values ? JSON.stringify(log.new_values, null, 2) : '// Sin nuevo estado registrado (Operación de eliminación)';

            if (oldEl) oldEl.textContent = oldStr;
            if (newEl) newEl.textContent = newStr;
        } catch (error) {
            console.error(error);
            if (subEl) subEl.textContent = 'Error al cargar detalles';
            if (descEl) descEl.textContent = 'Ocurrió un error al intentar consultar los metadatos de este registro.';
            if (window.DkriptToast) {
                DkriptToast.error('No se pudo cargar el detalle de la auditoría', 'Error');
            }
        }
    }
};

window.DkriptAudit = DkriptAudit;

// Auto-inicializar DkriptAudit en carga para apertura de incidencias (?detail=ID)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => DkriptAudit.init());
} else {
    DkriptAudit.init();
}

/* ==========================================================================
   MÓDULO: AUTENTICACIÓN DE DOS FACTORES (2FA / MFA TOTP RFC 6238)
   ========================================================================== */
const DkriptTwoFactor = {
    isRecoveryMode: false,
    activeRecoveryCodes: [],

    /**
     * Inicializa los listeners y comportamiento del desafío 2FA en pantalla de login
     */
    initChallenge: function() {
        const container = document.getElementById('twoFactorOtpContainer');
        const hiddenInput = document.getElementById('twoFactorHiddenCode');
        const form = document.getElementById('frmTwoFactorChallenge');

        if (!container || !hiddenInput || !form) return;

        const inputs = container.querySelectorAll('.otp-input-field');

        inputs.forEach((input, idx) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = val ? val[0] : '';

                if (e.target.value) {
                    input.classList.add('is-filled');
                    if (idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                    }
                } else {
                    input.classList.remove('is-filled');
                }

                this.updateChallengeHiddenCode(inputs, hiddenInput, form);
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                    inputs[idx - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim().replace(/[^0-9]/g, '');
                if (pasteData) {
                    for (let i = 0; i < Math.min(pasteData.length, inputs.length); i++) {
                        inputs[i].value = pasteData[i];
                        inputs[i].classList.add('is-filled');
                    }
                    const nextIdx = Math.min(pasteData.length, inputs.length - 1);
                    inputs[nextIdx].focus();
                    this.updateChallengeHiddenCode(inputs, hiddenInput, form);
                }
            });
        });
    },

    updateChallengeHiddenCode: function(inputs, hiddenInput, form) {
        let code = '';
        inputs.forEach(inp => code += inp.value);
        hiddenInput.value = code;
        if (code.length === 6) {
            form.submit();
        }
    },

    /**
     * Conmuta entre código de 6 dígitos de la app autenticadora y código de recuperación
     */
    toggleChallengeMode: function() {
        this.isRecoveryMode = !this.isRecoveryMode;
        const totpSection = document.getElementById('totpSection');
        const recoverySection = document.getElementById('recoverySection');
        const toggleText = document.getElementById('toggleModeText');
        const toggleIcon = document.getElementById('toggleModeIcon');
        const title = document.getElementById('twoFactorTitle');
        const subtitle = document.getElementById('twoFactorSubtitle');
        const hiddenCode = document.getElementById('twoFactorHiddenCode');
        const recoveryInput = document.getElementById('recoveryCodeInput');

        if (!totpSection || !recoverySection) return;

        if (this.isRecoveryMode) {
            totpSection.classList.add('hidden');
            recoverySection.classList.remove('hidden');
            if (toggleText) toggleText.innerText = 'Volver al código de la app autenticadora';
            if (toggleIcon) toggleIcon.className = 'bi bi-phone text-sm';
            if (title) title.innerText = 'Código de Recuperación';
            if (subtitle) subtitle.innerText = 'Ingresa uno de tus 8 códigos de emergencia de un solo uso.';
            if (hiddenCode) hiddenCode.value = '';
            if (recoveryInput) recoveryInput.focus();
        } else {
            recoverySection.classList.add('hidden');
            totpSection.classList.remove('hidden');
            if (toggleText) toggleText.innerText = '¿No tienes tu teléfono? Usar código de recuperación';
            if (toggleIcon) toggleIcon.className = 'bi bi-key text-sm';
            if (title) title.innerText = 'Verificación de Seguridad';
            if (subtitle) subtitle.innerText = 'Ingresa el código temporal de 6 dígitos de tu app autenticadora vinculada.';
            if (recoveryInput) recoveryInput.value = '';
            const firstDigit = document.querySelector('#twoFactorOtpContainer .otp-input-field');
            if (firstDigit) firstDigit.focus();
        }
    },

    /**
     * Inicia la configuración de 2FA solicitando el secreto y QR al backend
     */
    openSetupModal: async function() {
        const modal = document.getElementById('twoFactorSetupModal');
        if (!modal) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Restablecer vistas del modal
        const step1 = document.getElementById('twoFactorSetupStep1');
        const step2 = document.getElementById('twoFactorSetupStep2');
        if (step1) step1.classList.remove('hidden');
        if (step2) step2.classList.add('hidden');

        const qrImg = document.getElementById('twoFactorQrImage');
        const secretKeyText = document.getElementById('twoFactorSecretKeyDisplay');
        const codeInput = document.getElementById('twoFactorConfirmCode');
        if (codeInput) codeInput.value = '';

        try {
            if (window.DkriptToast) DkriptToast.info('Generando clave de seguridad 2FA...', 'Configuración');

            const response = await fetch('/user/two-factor/enable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error al iniciar la configuración de 2FA.');
            }

            if (qrImg) qrImg.src = data.qr_url;
            if (secretKeyText) secretKeyText.innerText = data.formatted_secret;
            this.activeRecoveryCodes = data.recovery_codes || [];

            this.showModal('twoFactorSetupModal');
        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'No se pudo generar la clave 2FA.', 'Error');
            }
        }
    },

    /**
     * Confirma la vinculación de 2FA mediante la validación del código ingresado
     */
    confirmSetup: async function() {
        const codeInput = document.getElementById('twoFactorConfirmCode');
        const code = codeInput ? codeInput.value.trim() : '';

        if (!code || code.length !== 6) {
            if (window.DkriptToast) DkriptToast.error('Por favor ingresa un código válido de 6 dígitos.', 'Código Requerido');
            if (codeInput) codeInput.focus();
            return;
        }

        const btn = document.getElementById('btnConfirmTwoFactorSetup');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span><span>Verificando...</span>';
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch('/user/two-factor/confirm', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ code: code })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Código de verificación incorrecto.');
            }

            if (window.DkriptToast) {
                DkriptToast.success('¡Autenticación de dos factores activada con éxito!', '2FA Activado');
            }

            // Mostrar el Paso 2 con los códigos de recuperación
            const step1 = document.getElementById('twoFactorSetupStep1');
            const step2 = document.getElementById('twoFactorSetupStep2');
            if (step1) step1.classList.add('hidden');
            if (step2) step2.classList.remove('hidden');

            this.activeRecoveryCodes = data.recovery_codes || this.activeRecoveryCodes;
            this.renderRecoveryCodesGrid('twoFactorSetupCodesGrid', this.activeRecoveryCodes);

        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'No se pudo verificar el código.', 'Error de Verificación');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>Verificar y Activar</span><i class="bi bi-shield-check"></i>';
            }
        }
    },

    /**
     * Muestra la lista de códigos de recuperación vigentes
     */
    showRecoveryCodesModal: async function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch('/user/two-factor/recovery-codes', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Error al obtener códigos.');

            this.activeRecoveryCodes = data.recovery_codes || [];
            this.renderRecoveryCodesGrid('twoFactorViewCodesGrid', this.activeRecoveryCodes);
            this.showModal('twoFactorRecoveryModal');
        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'No se pudieron consultar los códigos de recuperación.', 'Error');
            }
        }
    },

    /**
     * Regenera un nuevo juego de 8 códigos de recuperación
     */
    regenerateRecoveryCodes: async function() {
        const passwordInput = document.getElementById('twoFactorRegeneratePassword');
        const password = passwordInput ? passwordInput.value : '';

        if (!password) {
            if (window.DkriptToast) DkriptToast.error('Ingresa tu contraseña actual para autorizar la regeneración.', 'Contraseña Requerida');
            if (passwordInput) passwordInput.focus();
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch('/user/two-factor/recovery-codes/regenerate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ password: password })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Contraseña incorrecta.');

            if (window.DkriptToast) {
                DkriptToast.success('Nuevos códigos generados con éxito.', 'Códigos Actualizados');
            }

            if (passwordInput) passwordInput.value = '';
            this.activeRecoveryCodes = data.recovery_codes || [];
            this.renderRecoveryCodesGrid('twoFactorViewCodesGrid', this.activeRecoveryCodes);
        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'Error al regenerar códigos.', 'Error');
            }
        }
    },

    /**
     * Abre el modal para desactivar 2FA
     */
    showDisableModal: function() {
        const passwordInput = document.getElementById('twoFactorDisablePassword');
        if (passwordInput) passwordInput.value = '';
        this.showModal('twoFactorDisableModal');
    },

    /**
     * Confirma la desactivación de 2FA
     */
    confirmDisable: async function() {
        const passwordInput = document.getElementById('twoFactorDisablePassword');
        const password = passwordInput ? passwordInput.value : '';

        if (!password) {
            if (window.DkriptToast) DkriptToast.error('Por favor confirma tu contraseña actual.', 'Contraseña Requerida');
            if (passwordInput) passwordInput.focus();
            return;
        }

        const btn = document.getElementById('btnConfirmDisable2Fa');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span><span>Desactivando...</span>';
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch('/user/two-factor/disable', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ password: password })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Contraseña incorrecta.');

            if (window.DkriptToast) {
                DkriptToast.success('La autenticación de dos factores ha sido desactivada.', '2FA Desactivado');
            }

            this.hideModal('twoFactorDisableModal');
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'Error al desactivar 2FA.', 'Error');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>Confirmar Desactivación</span><i class="bi bi-shield-x"></i>';
            }
        }
    },

    renderRecoveryCodesGrid: function(containerId, codes) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        if (!codes || codes.length === 0) {
            container.innerHTML = '<p class="text-xs text-slate-400 col-span-2 text-center py-2">No quedan códigos de recuperación activos. Puedes regenerar un nuevo lote.</p>';
            return;
        }

        codes.forEach(code => {
            const chip = document.createElement('div');
            chip.className = 'recovery-code-chip';
            chip.innerText = code;
            container.appendChild(chip);
        });
    },

    copyRecoveryCodes: function() {
        if (!this.activeRecoveryCodes || this.activeRecoveryCodes.length === 0) return;
        const text = this.activeRecoveryCodes.join('\n');
        navigator.clipboard.writeText(text).then(() => {
            if (window.DkriptToast) DkriptToast.success('Códigos de recuperación copiados al portapapeles.', 'Copiado');
        });
    },

    downloadRecoveryCodes: function() {
        if (!this.activeRecoveryCodes || this.activeRecoveryCodes.length === 0) return;
        const content = "DKRIPT INC. - CÓDIGOS DE RECUPERACIÓN 2FA DE EMERGENCIA\n" +
                        "Fecha de emisión: " + new Date().toLocaleString() + "\n\n" +
                        "Guarde estos códigos en un lugar seguro y secreto.\n" +
                        "Cada código solo puede ser utilizado una sola vez para acceder al sistema si pierde acceso a su dispositivo de autenticación.\n\n" +
                        this.activeRecoveryCodes.join("\n") + "\n\n" +
                        "Fin del archivo de recuperación.";

        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'dkript-recovery-codes-' + new Date().toISOString().slice(0, 10) + '.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        if (window.DkriptToast) DkriptToast.info('Descargando archivo de códigos...', 'Descarga Iniciada');
    },

    copySecretKey: function() {
        const el = document.getElementById('twoFactorSecretKeyDisplay');
        if (!el) return;
        const text = el.innerText.replace(/\s+/g, '');
        navigator.clipboard.writeText(text).then(() => {
            if (window.DkriptToast) DkriptToast.success('Clave secreta copiada al portapapeles.', 'Copiado');
        });
    },

    finishSetup: function() {
        this.hideModal('twoFactorSetupModal');
        setTimeout(() => {
            window.location.reload();
        }, 300);
    },

    showModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('hidden', 'pointer-events-none');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            const card = modal.querySelector('.modal-card');
            if (card) {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }
        });
    },

    hideModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal || modal.classList.contains('hidden')) return;
        const card = modal.querySelector('.modal-card');
        if (card) {
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
        }
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
        }, 200);
    }
};

window.DkriptTwoFactor = DkriptTwoFactor;
window.toggleTwoFactorMode = () => DkriptTwoFactor.toggleChallengeMode();

/* ==========================================================================
   MÓDULO: GESTOR DE SESIONES ACTIVAS Y DISPOSITIVOS CONECTADOS
   ========================================================================== */
const DkriptSessions = {
    cachedSessions: [],

    openSessionsModal: async function() {
        this.showModal('userSessionsModal');
        await this.loadSessions();
    },

    loadSessions: async function() {
        const listEl = document.getElementById('userSessionsList');
        const countEl = document.getElementById('userSessionsCount');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!listEl) return;
        listEl.innerHTML = '<div class="p-6 text-center text-xs text-slate-400"><span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat text-base"></i></span>Cargando dispositivos conectados...</div>';

        try {
            const response = await fetch('/user/sessions', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Error al consultar sesiones.');

            this.cachedSessions = data.sessions || [];
            if (countEl) countEl.innerText = `${data.total_sessions} ${data.total_sessions === 1 ? 'dispositivo' : 'dispositivos'}`;

            if (this.cachedSessions.length === 0) {
                listEl.innerHTML = '<div class="p-6 text-center text-xs text-slate-400">No hay sesiones registradas.</div>';
                return;
            }

            listEl.innerHTML = '';
            this.cachedSessions.forEach(session => {
                const card = document.createElement('div');
                card.className = `session-device-card ${session.is_current ? 'is-current-device' : ''}`;

                const iconBg = session.is_current ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-600';

                let actionHtml = '';
                if (session.is_current) {
                    actionHtml = `
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase font-mono-code bg-emerald-500/15 text-emerald-700 border border-emerald-400/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Este Dispositivo</span>
                        </span>
                    `;
                } else {
                    actionHtml = `
                        <button type="button" 
                                onclick="DkriptSessions.revokeSession('${session.id}')" 
                                class="px-2.5 py-1.5 text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 rounded-xl transition-all shadow-xs flex items-center gap-1"
                                title="Desconectar este dispositivo">
                            <i class="bi bi-x-circle text-xs"></i>
                            <span class="hidden sm:inline">Desconectar</span>
                        </button>
                    `;
                }

                card.innerHTML = `
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-2xl ${iconBg} flex items-center justify-center text-lg flex-shrink-0 shadow-xs">
                            <i class="bi ${session.icon}"></i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 truncate flex items-center gap-1.5">
                                <span>${session.device_name}</span>
                            </h4>
                            <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5 font-mono-code flex-wrap">
                                <span>IP: <strong class="text-slate-700">${session.ip_address}</strong></span>
                                <span>•</span>
                                <span title="${session.last_active_formatted}">Última actividad: ${session.last_active_human}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        ${actionHtml}
                    </div>
                `;

                listEl.appendChild(card);
            });

        } catch (error) {
            console.error(error);
            listEl.innerHTML = `<div class="p-6 text-center text-xs text-rose-600 font-bold">${error.message || 'Error al cargar sesiones.'}</div>`;
        }
    },

    revokeSession: async function(sessionId) {
        const confirmed = await DkriptModal.confirm(
            '¿Estás seguro de que deseas desconectar este dispositivo de forma remota? Se cerrará la sesión en dicho equipo inmediatamente.',
            'Cerrar Sesión Remota',
            { type: 'warning', confirmText: 'Desconectar', cancelText: 'Cancelar' }
        );
        if (!confirmed) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch(`/user/sessions/${sessionId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            if (status !== 200) {
                throw new Error(body.message || 'Error al revocar la sesión.');
            }
            if (window.DkriptToast) {
                DkriptToast.success(body.message, 'Sesión Desconectada');
            }
            this.loadSessions();
        })
        .catch(err => {
            console.error(err);
            if (window.DkriptToast) {
                DkriptToast.error(err.message || 'No se pudo revocar la sesión.', 'Error');
            }
        });
    },

    promptLogoutOthers: function() {
        const input = document.getElementById('logoutOthersPassword');
        if (input) input.value = '';
        this.showModal('logoutOthersConfirmModal');
        setTimeout(() => { if (input) input.focus(); }, 150);
    },

    confirmLogoutOthers: async function() {
        const input = document.getElementById('logoutOthersPassword');
        const password = input ? input.value : '';

        if (!password) {
            if (window.DkriptToast) DkriptToast.error('Por favor ingresa tu contraseña actual.', 'Contraseña Requerida');
            if (input) input.focus();
            return;
        }

        const btn = document.getElementById('btnConfirmLogoutOthers');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span><span>Cerrando sesiones...</span>';
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const response = await fetch('/user/sessions/logout-others', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ password: password })
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Contraseña incorrecta.');

            if (window.DkriptToast) {
                DkriptToast.success(data.message, 'Sesiones Revocadas');
            }

            this.hideModal('logoutOthersConfirmModal');
            await this.loadSessions();
        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'No se pudieron cerrar las sesiones.', 'Error');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>Desconectar Otros Dispositivos</span><i class="bi bi-shield-x"></i>';
            }
        }
    },

    showModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('hidden', 'pointer-events-none');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            const card = modal.querySelector('.modal-card');
            if (card) {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }
        });
    },

    hideModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal || modal.classList.contains('hidden')) return;
        const card = modal.querySelector('.modal-card');
        if (card) {
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
        }
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden', 'pointer-events-none');
        }, 200);
    }
};

window.DkriptSessions = DkriptSessions;

/**
 * ==========================================================================
 * NAMESPACE: DkriptBackups
 * Programador Automático de Respaldos y Monitor de Tareas Cron del Sistema
 * ==========================================================================
 */
const DkriptBackups = {
    init: function() {
        const form = document.getElementById('autoBackupSettingsForm');
        if (form) {
            form.addEventListener('submit', (e) => this.saveAutoBackupSettings(e));
        }

        const btnRun = document.getElementById('btnRunAutoBackupNow');
        if (btnRun) {
            btnRun.addEventListener('click', () => this.runAutoBackupNow());
        }

        const btnRefresh = document.getElementById('btnRefreshScheduler');
        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => this.loadSchedulerStatus(true));
        }

        const btnCopy = document.getElementById('btnCopyCronCommand');
        if (btnCopy) {
            btnCopy.addEventListener('click', () => this.copyCronCommand());
        }

        if (document.getElementById('schedulerTasksTable')) {
            this.loadSchedulerStatus();
        }
    },

    saveAutoBackupSettings: async function(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btnSaveAutoBackupSettings');
        const originalHtml = btn ? btn.innerHTML : '';

        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin text-sm"></i> Guardando...';
            }

            const formData = new FormData(form);
            const data = {
                auto_backup_enabled: formData.get('auto_backup_enabled') === '1' ? 1 : 0,
                auto_backup_frequency: formData.get('auto_backup_frequency'),
                auto_backup_time: formData.get('auto_backup_time'),
                auto_backup_type: formData.get('auto_backup_type'),
                auto_backup_max_retention: parseInt(formData.get('auto_backup_max_retention'), 10) || 7,
            };

            const tokenEl = document.querySelector('meta[name="csrf-token"]') || form.querySelector('input[name="_token"]');
            const token = tokenEl ? (tokenEl.getAttribute('content') || tokenEl.value) : '';

            const response = await fetch(form.action || '/backups/auto-settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Error al guardar la configuración.');
            }

            if (window.DkriptToast) {
                DkriptToast.success(result.message || 'Configuración guardada correctamente.', 'Auto-Respaldos');
            }

            // Actualizar status visual y refrescar scheduler
            this.loadSchedulerStatus();

        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'Error inesperado al guardar la configuración.', 'Error');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    },

    runAutoBackupNow: async function() {
        const confirmed = await DkriptModal.confirm(
            '¿Desea ejecutar inmediatamente el ciclo completo de respaldo programado (DB / Medios y purga de retención)? Esta operación puede tardar unos segundos.',
            'Ejecutar Auto-Respaldo Ahora',
            {
                type: 'info',
                confirmText: 'Sí, ejecutar ahora',
                cancelText: 'Cancelar'
            }
        );

        if (!confirmed) return;

        const btn = document.getElementById('btnRunAutoBackupNow');
        const originalHtml = btn ? btn.innerHTML : '';

        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin text-sm"></i> Ejecutando respaldo...';
            }

            const tokenEl = document.querySelector('meta[name="csrf-token"]');
            const token = tokenEl ? tokenEl.getAttribute('content') : '';

            const response = await fetch('/backups/run-auto', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Error al ejecutar el respaldo automático.');
            }

            if (window.DkriptToast) {
                DkriptToast.success(result.message || 'Respaldo automático completado con éxito.', 'Completado');
            }

            // Si hay salida de consola, mostrarla en un modal informativo
            if (result.output) {
                DkriptModal.alert(
                    `<div class="text-left font-mono text-xs bg-slate-900 text-emerald-400 p-4 rounded-xl overflow-x-auto whitespace-pre-wrap">${result.output}</div>`,
                    'Registro de Ejecución del Respaldo'
                );
            }

            // Actualizar información en pantalla
            const lastRunBadge = document.getElementById('autoBackupLastRunBadge');
            if (lastRunBadge && result.last_run_human) {
                lastRunBadge.textContent = result.last_run_human;
            }

            // Recargar página o recargar tabla tras 1.5s para ver los nuevos archivos
            setTimeout(() => {
                window.location.reload();
            }, 1800);

        } catch (error) {
            console.error(error);
            if (window.DkriptToast) {
                DkriptToast.error(error.message || 'Fallo durante la ejecución del respaldo.', 'Error');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    },

    loadSchedulerStatus: async function(showFeedback = false) {
        const tableBody = document.getElementById('schedulerTasksBody');
        const serverTimeEl = document.getElementById('schedulerServerTime');
        const btnRefresh = document.getElementById('btnRefreshScheduler');

        if (btnRefresh && showFeedback) {
            btnRefresh.classList.add('animate-spin');
        }

        try {
            const response = await fetch('/backups/scheduler-status', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) return;

            const data = await response.json();

            if (serverTimeEl && data.server_time) {
                serverTimeEl.textContent = data.server_time;
            }

            if (tableBody && Array.isArray(data.events)) {
                if (data.events.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-6 text-slate-400 text-xs italic">
                                No se encontraron tareas programadas registradas en el scheduler.
                            </td>
                        </tr>
                    `;
                } else {
                    tableBody.innerHTML = data.events.map(ev => `
                        <tr class="scheduler-task-row border-b border-slate-100 last:border-0">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="cron-pulse-dot flex-shrink-0"></span>
                                    <span class="font-mono font-bold text-slate-800 text-xs">${ev.command}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-slate-700 font-mono text-[11px] font-bold">
                                    ${ev.expression}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs font-mono text-slate-600">
                                <span class="font-bold text-slate-800">${ev.next_run}</span>
                                <span class="text-slate-400 text-[11px] block">(${ev.next_run_human})</span>
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                ${ev.description || 'Tarea programada'}
                            </td>
                        </tr>
                    `).join('');
                }
            }

            if (showFeedback && window.DkriptToast) {
                DkriptToast.info('Estado del programador actualizado.', 'Cron Scheduler');
            }

        } catch (err) {
            console.error('Error al consultar scheduler:', err);
        } finally {
            if (btnRefresh && showFeedback) {
                btnRefresh.classList.remove('animate-spin');
            }
        }
    },

    copyCronCommand: function() {
        const textEl = document.getElementById('cronCommandText');
        if (!textEl) return;
        const text = textEl.textContent.trim();

        navigator.clipboard.writeText(text).then(() => {
            if (window.DkriptToast) {
                DkriptToast.success('Comando crontab copiado al portapapeles.', 'Copiado');
            }
        }).catch(() => {
            const temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            if (window.DkriptToast) {
                DkriptToast.success('Comando crontab copiado al portapapeles.', 'Copiado');
            }
        });
    },

    openRestoreModal: function(filename, createdAt, sizeFormatted) {
        const modal = document.getElementById('modalRestoreBackup');
        const form = document.getElementById('formRestoreBackup');
        const filenameEl = document.getElementById('restoreModalFilename');
        const dateEl = document.getElementById('restoreModalDate');
        const sizeEl = document.getElementById('restoreModalSize');
        const checkEl = document.getElementById('checkConfirmRestore');
        const btnSubmit = document.getElementById('btnSubmitRestore');

        if (!modal || !form) return;

        if (filenameEl) filenameEl.textContent = filename;
        if (dateEl) dateEl.textContent = createdAt;
        if (sizeEl) sizeEl.textContent = sizeFormatted;
        form.action = `/backups/restore/${encodeURIComponent(filename)}`;

        if (checkEl) checkEl.checked = false;
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i><span>Ejecutar Restauración</span>';
        }

        if (!form._restoreSubmitAttached) {
            form.addEventListener('submit', function() {
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i><span>Restaurando base de datos...</span>';
                }
            });
            form._restoreSubmitAttached = true;
        }

        modal.classList.remove('hidden');
    },

    closeRestoreModal: function() {
        const modal = document.getElementById('modalRestoreBackup');
        if (modal) modal.classList.add('hidden');
    }
};

window.DkriptBackups = DkriptBackups;
window.openRestoreModal = function(f, c, s) { DkriptBackups.openRestoreModal(f, c, s); };
window.closeRestoreModal = function() { DkriptBackups.closeRestoreModal(); };

/**
 * ==========================================================================
 * NAMESPACE: DkriptIdle
 * Monitor Inteligente de Inactividad y Caducidad de Sesión de Usuario
 * ==========================================================================
 */
const DkriptIdle = {
    timeoutSeconds: 900, // 15 minutos por defecto (15 * 60)
    warningSeconds: 60,  // Mostrar advertencia 60 segundos antes de expirar
    idleTimer: null,
    countdownTimer: null,
    countdownRemaining: 60,
    isWarningOpen: false,
    lastActivityTime: Date.now(),
    isThrottled: false,
    isLoggingOut: false,

    init: function() {
        const modalEl = document.getElementById('modalSessionIdleWarning');
        const timeoutMeta = document.querySelector('meta[name="session-timeout"]');
        if (timeoutMeta && timeoutMeta.content) {
            const parsed = parseInt(timeoutMeta.content, 10);
            if (!isNaN(parsed) && parsed > 0) {
                this.timeoutSeconds = parsed;
            }
        }

        // Si el timeout es muy corto (ej. pruebas o desarrollo <= 60s), ajustar warning
        if (this.timeoutSeconds <= 60) {
            this.warningSeconds = Math.max(5, Math.floor(this.timeoutSeconds / 2));
        } else {
            this.warningSeconds = 60;
        }

        if (!modalEl && !timeoutMeta) return;

        this.bindEvents();
        this.resetTimer();
    },

    bindEvents: function() {
        const events = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'];
        const activityHandler = () => {
            if (this.isWarningOpen) return;

            const now = Date.now();
            if (!this.isThrottled) {
                this.isThrottled = true;
                this.lastActivityTime = now;
                this.resetTimer();
                setTimeout(() => {
                    this.isThrottled = false;
                }, 3000);
            }
        };

        events.forEach(evt => {
            window.addEventListener(evt, activityHandler, { passive: true });
        });
    },

    resetTimer: function() {
        if (this.idleTimer) clearTimeout(this.idleTimer);
        if (this.countdownTimer) clearInterval(this.countdownTimer);

        const warningDelay = (this.timeoutSeconds - this.warningSeconds) * 1000;
        this.idleTimer = setTimeout(() => {
            this.showWarning();
        }, Math.max(warningDelay, 1000));
    },

    showWarning: function() {
        this.isWarningOpen = true;
        this.countdownRemaining = this.warningSeconds;

        const modalEl = document.getElementById('modalSessionIdleWarning');
        const countdownEl = document.getElementById('sessionIdleCountdown');
        if (countdownEl) {
            countdownEl.textContent = `${this.countdownRemaining}s`;
        }

        if (modalEl) {
            modalEl.classList.remove('hidden');
        }

        if (this.countdownTimer) clearInterval(this.countdownTimer);
        this.countdownTimer = setInterval(() => {
            this.countdownRemaining--;
            if (countdownEl) {
                countdownEl.textContent = `${this.countdownRemaining}s`;
            }

            if (this.countdownRemaining <= 0) {
                clearInterval(this.countdownTimer);
                this.expireSession();
            }
        }, 1000);
    },

    stayConnected: async function() {
        const modalEl = document.getElementById('modalSessionIdleWarning');
        const btnStay = document.getElementById('btnSessionIdleStay');
        if (btnStay) {
            btnStay.disabled = true;
            btnStay.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Conectando...';
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/session/ping', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'Accept': 'application/json',
                }
            });

            if (res.ok) {
                const data = await res.json();
                if (data.timeout_minutes) {
                    this.timeoutSeconds = data.timeout_minutes * 60;
                }
                this.isWarningOpen = false;
                if (modalEl) modalEl.classList.add('hidden');
                if (window.DkriptToast) {
                    DkriptToast.success('Tu sesión ha sido renovada con éxito.', 'Sesión Activa');
                }
                this.resetTimer();
            } else {
                this.expireSession();
            }
        } catch (e) {
            this.expireSession();
        } finally {
            if (btnStay) {
                btnStay.disabled = false;
                btnStay.innerHTML = '<i class="bi bi-arrow-repeat"></i><span>Mantener Sesión Activa</span>';
            }
        }
    },

    logoutNow: function() {
        if (this.isLoggingOut) return;
        this.isLoggingOut = true;

        if (this.countdownTimer) clearInterval(this.countdownTimer);
        if (this.idleTimer) clearTimeout(this.idleTimer);

        const modalEl = document.getElementById('modalSessionIdleWarning');
        if (modalEl) modalEl.classList.add('hidden');

        this.performLogout(false);
    },

    expireSession: function() {
        if (this.isLoggingOut) return;
        this.isLoggingOut = true;

        if (this.countdownTimer) clearInterval(this.countdownTimer);
        if (this.idleTimer) clearTimeout(this.idleTimer);

        const modalEl = document.getElementById('modalSessionIdleWarning');
        if (modalEl) modalEl.classList.add('hidden');

        this.performLogout(true);
    },

    performLogout: function(isExpired = false) {
        // 1. Reutilizar formulario de logout existente en el DOM (Preferencia arquitectónica)
        const form = document.getElementById('logout-form')
            || document.getElementById('headerLogoutForm')
            || document.getElementById('userProfileLogoutForm')
            || document.querySelector('form[action*="/logout"]');

        if (form) {
            if (isExpired) {
                let expiredInput = form.querySelector('input[name="expired"]');
                if (!expiredInput) {
                    expiredInput = document.createElement('input');
                    expiredInput.type = 'hidden';
                    expiredInput.name = 'expired';
                    form.appendChild(expiredInput);
                }
                expiredInput.value = '1';

                if (form.action && !form.action.includes('expired=')) {
                    const separator = form.action.includes('?') ? '&' : '?';
                    form.action = form.action + separator + 'expired=1';
                }
            }
            form.submit();
            return;
        }

        // 2. Fallback seguro si no existe formulario en el DOM (Construcción POST con CSRF)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = isExpired ? '/logout?expired=1' : '/logout';
        tempForm.style.display = 'none';

        if (csrfToken) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            tempForm.appendChild(tokenInput);
        }

        if (isExpired) {
            const expiredInput = document.createElement('input');
            expiredInput.type = 'hidden';
            expiredInput.name = 'expired';
            expiredInput.value = '1';
            tempForm.appendChild(expiredInput);
        }

        document.body.appendChild(tempForm);
        tempForm.submit();
    }
};

window.DkriptIdle = DkriptIdle;

/**
 * ==========================================================================
 * NAMESPACE: DkriptUserProfile
 * Gestión de Edición de Perfil de Usuario con Verificación Obligatoria de Contraseña
 * ==========================================================================
 */
const DkriptUserProfile = {
    /**
     * Alterna la visibilidad de un campo de contraseña (mostrar/ocultar texto y conmutar icono)
     */
    togglePasswordVisibility: function(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            if (icon) {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        } else {
            input.type = 'password';
            if (icon) {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    },

    /**
     * Alterna la visibilidad del acordeón colapsable para cambio opcional de contraseña
     */
    togglePasswordChangeSection: function() {
        const section = document.getElementById('sectionPasswordChange');
        const icon = document.getElementById('iconTogglePasswordChange');
        if (!section) return;

        const isHidden = section.classList.contains('hidden');
        if (isHidden) {
            section.classList.remove('hidden');
            if (icon) icon.classList.add('rotate-180');
            const newPassInput = document.getElementById('profile_new_password');
            if (newPassInput) newPassInput.focus();
        } else {
            section.classList.add('hidden');
            if (icon) icon.classList.remove('rotate-180');
        }
    },

    /**
     * Campos de texto editables autoreversibles a su valor previo si quedan vacíos
     */
    restorableFields: {
        'profile_first_name': 'first_name',
        'profile_last_name': 'last_name',
        'profile_name': 'name',
        'profile_email': 'email',
        'profile_phone': 'phone'
    },

    /**
     * Estado inicial del formulario para Dirty Checking
     */
    initialState: {},

    /**
     * Captura los valores originales del formulario
     */
    captureInitialState: function(force = false) {
        if (!force && this.initialState && Object.keys(this.initialState).length > 0) {
            return;
        }
        const firstNameEl = document.getElementById('profile_first_name');
        const lastNameEl = document.getElementById('profile_last_name');
        const nameEl = document.getElementById('profile_name');
        const emailEl = document.getElementById('profile_email');
        const phoneEl = document.getElementById('profile_phone');

        this.initialState = {
            first_name: firstNameEl ? firstNameEl.value.trim() : '',
            last_name: lastNameEl ? lastNameEl.value.trim() : '',
            name: nameEl ? nameEl.value.trim() : '',
            email: emailEl ? emailEl.value.trim() : '',
            phone: phoneEl ? phoneEl.value.trim() : ''
        };
    },

    /**
     * Restablece todos los campos a su estado inicial almacenado
     */
    resetToInitialState: function() {
        if (!this.initialState || Object.keys(this.initialState).length === 0) return;

        for (const [id, key] of Object.entries(this.restorableFields)) {
            const el = document.getElementById(id);
            if (el && this.initialState[key] !== undefined) {
                el.value = this.initialState[key];
            }
        }

        const currentPassEl = document.getElementById('profile_current_password');
        const newPassEl = document.getElementById('profile_new_password');
        const newPassConfirmEl = document.getElementById('profile_new_password_confirmation');
        if (currentPassEl) currentPassEl.value = '';
        if (newPassEl) newPassEl.value = '';
        if (newPassConfirmEl) newPassConfirmEl.value = '';

        const passSection = document.getElementById('sectionPasswordChange');
        const passIcon = document.getElementById('iconTogglePasswordChange');
        if (passSection && !passSection.classList.contains('hidden')) {
            passSection.classList.add('hidden');
            if (passIcon) passIcon.classList.remove('rotate-180');
        }

        this.checkDirtyState();
        this.validatePasswordRules();
    },

    /**
     * Evalúa si algún campo ha cambiado respecto al estado original y controla la visibilidad de la contraseña actual
     */
    checkDirtyState: function() {
        if (!this.initialState || Object.keys(this.initialState).length === 0) {
            this.captureInitialState();
        }

        const firstNameEl = document.getElementById('profile_first_name');
        const lastNameEl = document.getElementById('profile_last_name');
        const nameEl = document.getElementById('profile_name');
        const emailEl = document.getElementById('profile_email');
        const phoneEl = document.getElementById('profile_phone');
        const newPassEl = document.getElementById('profile_new_password');
        const confirmPassEl = document.getElementById('profile_new_password_confirmation');
        const currentPassContainer = document.getElementById('containerCurrentPassword');
        const currentPassInput = document.getElementById('profile_current_password');

        const isDirty = (
            (firstNameEl && firstNameEl.value.trim() !== this.initialState.first_name) ||
            (lastNameEl && lastNameEl.value.trim() !== this.initialState.last_name) ||
            (nameEl && nameEl.value.trim() !== this.initialState.name) ||
            (emailEl && emailEl.value.trim() !== this.initialState.email) ||
            (phoneEl && phoneEl.value.trim() !== this.initialState.phone) ||
            (newPassEl && newPassEl.value.length > 0) ||
            (confirmPassEl && confirmPassEl.value.length > 0)
        );

        if (currentPassContainer) {
            if (isDirty) {
                currentPassContainer.classList.remove('hidden');
                if (currentPassInput) currentPassInput.required = true;
            } else {
                currentPassContainer.classList.add('hidden');
                if (currentPassInput) {
                    currentPassInput.required = false;
                    currentPassInput.value = '';
                }
            }
        }

        return isDirty;
    },

    /**
     * Inicializa los escuchadores en tiempo real para validar dirty check y requisitos de nueva contraseña
     */
    initRealtimeValidation: function() {
        this.captureInitialState();

        const inputIds = [
            'profile_first_name',
            'profile_last_name',
            'profile_name',
            'profile_email',
            'profile_phone',
            'profile_new_password',
            'profile_new_password_confirmation'
        ];

        if (!this._realtimeListenersAttached) {
            const dirtyHandler = () => {
                this.checkDirtyState();
                this.validatePasswordRules();
            };

            inputIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', dirtyHandler);
                    el.addEventListener('keyup', dirtyHandler);
                    el.addEventListener('change', dirtyHandler);
                }
            });

            // Auto-restaurar valor previo en campos editables si quedan vacíos al perder el foco (blur)
            for (const [id, key] of Object.entries(this.restorableFields)) {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('blur', () => {
                        if (el.value.trim() === '') {
                            if (this.initialState && this.initialState[key] !== undefined && this.initialState[key] !== '') {
                                el.value = this.initialState[key];
                                this.checkDirtyState();
                                this.validatePasswordRules();
                                if (window.DkriptToast) {
                                    DkriptToast.info('Se restauró la información previa al dejar el campo vacío.', 'Campo Restaurado');
                                }
                            }
                        }
                    });
                }
            }

            // Soporte interactivo de Drag & Drop para el avatar
            const avatarContainer = document.getElementById('userProfileModalAvatarImg')?.closest('.group');
            if (avatarContainer) {
                avatarContainer.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    avatarContainer.classList.add('ring-[#0062f5]');
                });
                avatarContainer.addEventListener('dragleave', () => {
                    avatarContainer.classList.remove('ring-[#0062f5]');
                });
                avatarContainer.addEventListener('drop', (e) => {
                    e.preventDefault();
                    avatarContainer.classList.remove('ring-[#0062f5]');
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        this.uploadAvatar({ files: e.dataTransfer.files, value: '' });
                    }
                });
            }

            this._realtimeListenersAttached = true;
        }

        // Evaluar estado visual inicial
        this.checkDirtyState();
        this.validatePasswordRules();
    },

    /**
     * Valida en tiempo real las 6 reglas corporativas de contraseña y actualiza la UI
     */
    validatePasswordRules: function() {
        const newPassEl = document.getElementById('profile_new_password');
        const confirmPassEl = document.getElementById('profile_new_password_confirmation');
        if (!newPassEl) return { isValid: true, allEmpty: true, rules: {} };

        const val = newPassEl.value || '';
        const confirmVal = confirmPassEl ? confirmPassEl.value || '' : '';

        const rules = {
            'pwd-rule-length': val.length >= 8,
            'pwd-rule-uppercase': /[A-Z]/.test(val),
            'pwd-rule-lowercase': /[a-z]/.test(val),
            'pwd-rule-number': /[0-9]/.test(val),
            'pwd-rule-symbol': /[^A-Za-z0-9]/.test(val),
            'pwd-rule-match': val.length > 0 && confirmVal.length > 0 && val === confirmVal
        };

        let allValid = true;
        for (const [ruleId, isValid] of Object.entries(rules)) {
            if (!isValid) allValid = false;
            const item = document.getElementById(ruleId);
            if (!item) continue;

            const icon = item.querySelector('.pwd-rule-icon');
            if (isValid) {
                item.classList.remove('is-invalid');
                item.classList.add('is-valid');
                if (icon) {
                    icon.className = 'pwd-rule-icon bi bi-check-circle-fill text-emerald-500 text-xs transition-colors';
                }
            } else {
                item.classList.remove('is-valid');
                item.classList.add('is-invalid');
                if (icon) {
                    icon.className = 'pwd-rule-icon bi bi-x-circle-fill text-rose-500 text-xs transition-colors';
                }
            }
        }

        return {
            isValid: allValid,
            allEmpty: val.length === 0 && confirmVal.length === 0,
            rules: rules
        };
    },

    /**
     * Valida los datos en cliente y despliega el diálogo de advertencia corporativa antes de proceder
     */
    confirmUpdate: async function() {
        // Auto-restaurar cualquier campo de texto que haya quedado vacío antes de validar
        for (const [id, key] of Object.entries(this.restorableFields)) {
            const el = document.getElementById(id);
            if (el && el.value.trim() === '' && this.initialState && this.initialState[key]) {
                el.value = this.initialState[key];
            }
        }

        // 1. Verificar si el usuario ha realizado alguna modificación
        const isDirty = this.checkDirtyState();
        if (!isDirty) {
            if (window.DkriptToast) {
                DkriptToast.info('No has realizado ninguna modificación en los datos de tu perfil.', 'Sin Cambios');
            }
            return;
        }

        const firstNameEl = document.getElementById('profile_first_name');
        const lastNameEl = document.getElementById('profile_last_name');
        const nameEl = document.getElementById('profile_name');
        const emailEl = document.getElementById('profile_email');
        const currentPassEl = document.getElementById('profile_current_password');
        const newPassEl = document.getElementById('profile_new_password');
        const newPassConfirmEl = document.getElementById('profile_new_password_confirmation');

        const firstName = firstNameEl ? firstNameEl.value.trim() : '';
        const lastName = lastNameEl ? lastNameEl.value.trim() : '';
        const name = nameEl ? nameEl.value.trim() : '';
        const email = emailEl ? emailEl.value.trim() : '';
        const currentPass = currentPassEl ? currentPassEl.value : '';
        const newPass = newPassEl ? newPassEl.value : '';
        const newPassConfirm = newPassConfirmEl ? newPassConfirmEl.value : '';

        // Validaciones requeridas básicas
        if (!firstName) {
            if (window.DkriptToast) DkriptToast.warning('El nombre es obligatorio.', 'Datos Incompletos');
            firstNameEl?.focus();
            return;
        }

        if (!lastName) {
            if (window.DkriptToast) DkriptToast.warning('El apellido es obligatorio.', 'Datos Incompletos');
            lastNameEl?.focus();
            return;
        }

        if (!name) {
            if (window.DkriptToast) DkriptToast.warning('El nombre de usuario es obligatorio.', 'Datos Incompletos');
            nameEl?.focus();
            return;
        }

        if (!email) {
            if (window.DkriptToast) DkriptToast.warning('El correo electrónico es obligatorio.', 'Datos Incompletos');
            emailEl?.focus();
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            if (window.DkriptToast) DkriptToast.warning('Ingresa un formato de correo electrónico válido.', 'Correo Inválido');
            emailEl?.focus();
            return;
        }

        // VALIDACIÓN OBLIGATORIA: Contraseña actual
        if (!currentPass) {
            if (window.DkriptToast) {
                DkriptToast.warning('Debes ingresar tu contraseña actual para autorizar los cambios en tu perfil corporativo.', 'Contraseña Requerida');
            }
            if (currentPassEl) {
                currentPassEl.focus();
                const container = document.getElementById('containerCurrentPassword');
                if (container) {
                    container.classList.add('ring-2', 'ring-rose-500', 'rounded-xl');
                    setTimeout(() => {
                        container.classList.remove('ring-2', 'ring-rose-500', 'rounded-xl');
                    }, 2500);
                }
            }
            return;
        }

        // Validación estricta de nueva contraseña (si se ingresó)
        if (newPass || newPassConfirm) {
            const validation = this.validatePasswordRules();
            if (!validation.isValid) {
                let errorMsg = 'La nueva contraseña debe cumplir con todos los requisitos de seguridad:';
                if (!validation.rules['pwd-rule-length']) {
                    errorMsg = 'La nueva contraseña debe tener al menos 8 caracteres.';
                } else if (!validation.rules['pwd-rule-uppercase']) {
                    errorMsg = 'La nueva contraseña debe incluir al menos una letra mayúscula.';
                } else if (!validation.rules['pwd-rule-lowercase']) {
                    errorMsg = 'La nueva contraseña debe incluir al menos una letra minúscula.';
                } else if (!validation.rules['pwd-rule-number']) {
                    errorMsg = 'La nueva contraseña debe incluir al menos un número.';
                } else if (!validation.rules['pwd-rule-symbol']) {
                    errorMsg = 'La nueva contraseña debe incluir al menos un carácter especial.';
                } else if (!validation.rules['pwd-rule-match']) {
                    errorMsg = 'La nueva contraseña y su confirmación no coinciden.';
                }

                if (window.DkriptToast) {
                    DkriptToast.warning(errorMsg, 'Requisitos de Contraseña');
                }
                newPassEl?.focus();
                return;
            }
        }

        // Diálogo de Advertencia y Confirmación Corporativa
        const warningMessage = `
            <div class="text-left space-y-2 text-slate-700 text-xs sm:text-sm">
                <p>Estás a punto de actualizar tus datos de identidad corporativa en el sistema.</p>
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-xs">
                    <p class="font-bold flex items-center gap-1 mb-1">
                        <i class="bi bi-shield-exclamation text-sm text-amber-600"></i>
                        Aviso de Seguridad y Trazabilidad
                    </p>
                    <p>Esta acción verificará tu contraseña actual y registrará un evento forense en la bitácora de auditoría con tu dirección IP y marca de tiempo.</p>
                </div>
                <p class="font-bold text-slate-900 mt-2">¿Deseas aplicar estos cambios a tu cuenta ahora?</p>
            </div>
        `;

        const confirmed = await DkriptModal.confirm(
            warningMessage,
            'Confirmar Actualización de Perfil',
            {
                type: 'warning',
                confirmText: 'Sí, actualizar perfil',
                cancelText: 'Revisar datos'
            }
        );

        if (!confirmed) return;

        await this.submitUpdate();
    },

    /**
     * Envía la solicitud asíncrona PUT /user/profile al backend
     */
    submitUpdate: async function() {
        const btn = document.getElementById('btnSubmitUserProfile');
        const originalHtml = btn ? btn.innerHTML : '';

        const form = document.getElementById('frmUserProfile');
        if (!form) return;

        const formData = new FormData(form);
        const data = {
            name: formData.get('name'),
            first_name: formData.get('first_name'),
            last_name: formData.get('last_name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password') || null,
            new_password_confirmation: formData.get('new_password_confirmation') || null,
        };

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
            || formData.get('_token') 
            || '';

        try {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin text-sm"></i> <span>Actualizando...</span>';
            }

            const response = await fetch('/user/profile', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                const errorMsg = result.message || 'Error al actualizar el perfil de usuario.';
                if (window.DkriptToast) {
                    DkriptToast.error(errorMsg, 'Actualización Denegada');
                }

                // Si falló por contraseña incorrecta, enfocar y resaltar el campo de contraseña actual
                if (result.errors && result.errors.current_password) {
                    const currentPassEl = document.getElementById('profile_current_password');
                    if (currentPassEl) {
                        currentPassEl.value = '';
                        currentPassEl.focus();
                        const card = currentPassEl.closest('.rounded-2xl');
                        if (card) {
                            card.classList.add('ring-2', 'ring-rose-500', 'border-rose-500');
                            setTimeout(() => {
                                card.classList.remove('ring-2', 'ring-rose-500', 'border-rose-500');
                            }, 3000);
                        }
                    }
                }
                return;
            }

            // Éxito: notificar al usuario
            if (window.DkriptToast) {
                DkriptToast.success(result.message || 'Perfil de usuario actualizado exitosamente.', 'Operación Exitosa');
            }

            // Limpiar campos sensibles de contraseña
            const currentPassEl = document.getElementById('profile_current_password');
            const newPassEl = document.getElementById('profile_new_password');
            const newPassConfirmEl = document.getElementById('profile_new_password_confirmation');
            if (currentPassEl) currentPassEl.value = '';
            if (newPassEl) newPassEl.value = '';
            if (newPassConfirmEl) newPassConfirmEl.value = '';
            this.validatePasswordRules();

            // Colapsar sección de cambio de contraseña si estaba abierta
            const passSection = document.getElementById('sectionPasswordChange');
            const passIcon = document.getElementById('iconTogglePasswordChange');
            if (passSection && !passSection.classList.contains('hidden')) {
                passSection.classList.add('hidden');
                if (passIcon) passIcon.classList.remove('rotate-180');
            }

            // Actualización reactiva del DOM (nombres, correos en modal y header)
            if (result.user) {
                const modalTitle = document.getElementById('userProfileModalTitle');
                if (modalTitle) modalTitle.textContent = result.user.full_name || result.user.name;

                const modalEmail = document.getElementById('userProfileModalEmail');
                if (modalEmail) modalEmail.textContent = result.user.email;

                // Actualizar textos en botón de menú y cabecera de dropdown si existen
                const headerUserName = document.getElementById('headerUserNameText');
                if (headerUserName) headerUserName.textContent = result.user.full_name || result.user.name;

                const dropdownUserName = document.getElementById('dropdownUserNameText');
                if (dropdownUserName) dropdownUserName.textContent = result.user.full_name || result.user.name;

                const dropdownUserEmail = document.getElementById('dropdownUserEmailText');
                if (dropdownUserEmail) dropdownUserEmail.textContent = result.user.email;
            }

            // Recapturar estado original con los datos guardados y sincronizar dirty check
            this.captureInitialState(true);
            this.checkDirtyState();

        } catch (error) {
            console.error('Error al actualizar perfil:', error);
            if (window.DkriptToast) {
                DkriptToast.error('No se pudo conectar con el servidor para actualizar el perfil.', 'Error de Conexión');
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }
    },

    /**
     * Sube y actualiza automáticamente la foto de perfil del usuario de forma inmediata (sin requerir contraseña)
     */
    uploadAvatar: async function(input) {
        if (!input || !input.files || input.files.length === 0) return;

        const file = input.files[0];
        const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
        if (!validTypes.includes(file.type.toLowerCase())) {
            if (window.DkriptToast) {
                DkriptToast.warning('Por favor selecciona una imagen válida (PNG, JPG, JPEG, WEBP o GIF).', 'Formato Inválido');
            }
            input.value = '';
            return;
        }

        // Validación de tamaño máximo (5MB)
        if (file.size > 5 * 1024 * 1024) {
            if (window.DkriptToast) {
                DkriptToast.warning('La imagen seleccionada supera el límite máximo permitido de 5 MB.', 'Archivo Demasiado Grande');
            }
            input.value = '';
            return;
        }

        const spinner = document.getElementById('avatarUploadSpinner');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const formData = new FormData();
        formData.append('avatar', file);

        try {
            if (spinner) spinner.classList.remove('hidden');

            const response = await fetch('/user/profile/avatar', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                const errorMsg = result.message || 'Error al actualizar la foto de perfil.';
                if (window.DkriptToast) {
                    DkriptToast.error(errorMsg, 'Error de Carga');
                }
                return;
            }

            const newAvatarUrl = result.data?.avatar_url;
            if (newAvatarUrl) {
                // 1. Actualizar miniatura en modal
                const modalAvatar = document.getElementById('userProfileModalAvatarImg');
                if (modalAvatar) modalAvatar.src = newAvatarUrl;

                // 2. Actualizar miniatura en botón de barra superior
                const headerAvatar = document.getElementById('headerUserAvatarImg');
                if (headerAvatar) headerAvatar.src = newAvatarUrl;

                // 3. Actualizar miniatura en dropdown
                const dropdownAvatar = document.getElementById('dropdownUserAvatarImg');
                if (dropdownAvatar) dropdownAvatar.src = newAvatarUrl;
            }

            if (window.DkriptToast) {
                DkriptToast.success(result.message || 'Foto de perfil actualizada exitosamente.', 'Foto Actualizada');
            }

        } catch (error) {
            console.error('Error al subir foto de perfil:', error);
            if (window.DkriptToast) {
                DkriptToast.error('No se pudo conectar con el servidor para subir la foto de perfil.', 'Error de Conexión');
            }
        } finally {
            if (spinner) spinner.classList.add('hidden');
            input.value = '';
        }
    }
};

window.DkriptUserProfile = DkriptUserProfile;

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('autoBackupSettingsForm') || document.getElementById('schedulerTasksTable')) {
        DkriptBackups.init();
    }
    if (document.getElementById('modalSessionIdleWarning') || document.querySelector('meta[name="session-timeout"]')) {
        DkriptIdle.init();
    }
    if (document.getElementById('userProfileModal') || document.getElementById('profile_new_password')) {
        DkriptUserProfile.initRealtimeValidation();
    }
});

