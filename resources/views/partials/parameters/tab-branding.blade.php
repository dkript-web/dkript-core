<!-- PESTAÑA 3: Identidad Visual & Logotipo Corporativo -->
<div id="tab-branding" class="parameter-tab-pane space-y-6 hidden">
    <div class="table-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100 flex-wrap gap-3">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-[#0062f5]/10 text-[#0062f5] border border-[#0062f5]/20 flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                    <i class="bi bi-image"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Identidad Visual & Logotipo</h3>
                    <p class="text-xs text-slate-500">Gestione la imagen corporativa, suba nuevos artes o elija del catálogo disponible</p>
                </div>
            </div>

            <!-- Botón de acción rápida para subir nueva imagen -->
            <button type="button" 
                    onclick="triggerFileUpload()"
                    class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-[#0062f5] hover:text-white text-slate-700 text-xs font-bold transition-all flex items-center gap-2 shadow-sm border border-slate-200 hover:border-transparent active:scale-95">
                <i class="bi bi-cloud-arrow-up text-base"></i>
                <span>Subir Nueva Imagen</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Columna Izquierda: Vista previa activa con efecto hover estilo Facebook -->
            <div class="lg:col-span-4 space-y-2">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Logotipo Activo:</span>
                
                <div id="logoPreviewBox" 
                     onclick="triggerFileUpload()"
                     class="relative group rounded-2xl overflow-hidden cursor-pointer bg-[#071026] border border-[#112356] shadow-md transition-all duration-300 hover:border-[#0062f5]/60 hover:shadow-lg select-none"
                     title="Haga clic, pase el cursor o arrastre una imagen aquí para cambiarla">
                    
                    <!-- Resplandor decorativo de fondo -->
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-[#00d4ff]/10 rounded-full blur-xl pointer-events-none"></div>

                    <!-- Contenedor de la Imagen -->
                    <div class="relative w-full h-44 flex items-center justify-center p-6">
                        <img id="logoPreviewImg" 
                             src="{{ asset($parameter->system_logo ?: 'assets/images/branding/logo-dkript.png') }}" 
                             alt="Logo Preview" 
                             class="max-h-28 max-w-full object-contain transition-all duration-300 group-hover:scale-105">

                        <!-- Capa de oscurecimiento suave en hover (Estilo Facebook) -->
                        <div class="absolute inset-0 bg-slate-950/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[1px]">
                            <span class="text-xs font-semibold text-white/95 bg-slate-900/80 px-3.5 py-1.5 rounded-full border border-white/10 shadow-lg flex items-center gap-1.5">
                                <i class="bi bi-cloud-arrow-up text-sm text-[#00d4ff]"></i>
                                <span>Cambiar foto</span>
                            </span>
                        </div>

                        <!-- Icono de cámara en la esquina inferior izquierda del recuadro (Estilo Facebook) -->
                        <div class="absolute bottom-3 left-3 z-20 flex items-center gap-2 bg-slate-900/90 hover:bg-[#0062f5] text-white px-3 py-1.5 rounded-full shadow-lg border border-white/20 transition-all transform group-hover:scale-105"
                             onclick="event.stopPropagation(); triggerFileUpload();"
                             title="Subir nueva foto al servidor">
                            <i class="bi bi-camera-fill text-sm text-[#00d4ff] group-hover:text-white transition-colors"></i>
                            <span class="text-[11px] font-bold tracking-wide">Actualizar</span>
                        </div>

                        <!-- Overlay activo al arrastrar archivo (Drag & Drop) -->
                        <div id="dragDropOverlay" class="hidden absolute inset-0 bg-slate-950/90 z-25 flex flex-col items-center justify-center text-white border-2 border-dashed border-[#00d4ff] rounded-2xl backdrop-blur-sm pointer-events-none transition-all">
                            <div class="w-11 h-11 rounded-full bg-[#00d4ff]/20 text-[#00d4ff] flex items-center justify-center text-xl mb-1.5 animate-bounce">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                            </div>
                            <span class="text-xs font-bold text-white tracking-wide">Suelte la imagen aquí</span>
                            <span class="text-[10px] text-[#00d4ff] mt-0.5">Se subirá y guardará de inmediato</span>
                        </div>

                        <!-- Spinner de subida en progreso -->
                        <div id="uploadSpinner" class="hidden absolute inset-0 bg-slate-950/85 z-30 flex flex-col items-center justify-center text-white backdrop-blur-[2px]">
                            <div class="w-8 h-8 border-3 border-white/20 border-t-[#00d4ff] rounded-full animate-spin mb-2"></div>
                            <span id="uploadSpinnerText" class="text-xs font-bold text-[#00d4ff] tracking-wide">Subiendo al servidor...</span>
                        </div>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 text-center">
                    Pase el cursor, haga clic o <span class="text-[#0062f5] font-semibold">arrastre una imagen aquí</span> para actualizarla.
                </p>
            </div>

            <!-- Columna Derecha: Recuadro del nombre (sustituye al input de la ruta) y Galería de Imágenes -->
            <div class="lg:col-span-8 space-y-5">
                
                <!-- Campo oculto para la ruta técnica (no se muestra al usuario) -->
                <input type="hidden" 
                       name="system_logo" 
                       id="system_logo" 
                       value="{{ old('system_logo', $parameter->system_logo ?: 'assets/images/branding/logo-dkript.png') }}">

                <!-- Recuadro del Nombre y Opción de Cambiarlo -->
                <div class="space-y-2 p-4 rounded-2xl bg-slate-50/80 border border-slate-200">
                    <div class="flex items-center justify-between">
                        <label for="current_logo_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-tag-fill text-[#0062f5]"></i>
                            <span>Nombre de la Imagen Seleccionada</span>
                        </label>
                        <span id="nameStatusBadge" class="text-[11px] font-semibold text-emerald-600 hidden flex items-center gap-1 transition-all">
                            <i class="bi bi-check-circle-fill"></i> Nombre guardado
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <input type="text" 
                                   id="current_logo_name" 
                                   name="current_logo_name" 
                                   value="{{ old('current_logo_name', $currentImageName) }}"
                                   placeholder="Nombre descriptivo de la imagen..."
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all font-semibold text-slate-800 bg-white shadow-sm"
                                   onkeydown="if(event.key === 'Enter'){ event.preventDefault(); saveCurrentImageName(); }">
                        </div>
                        <button type="button" 
                                id="btnSaveName"
                                onclick="saveCurrentImageName()"
                                class="px-4 py-2.5 rounded-xl bg-[#0062f5] hover:bg-[#0052cc] text-white text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm whitespace-nowrap active:scale-95">
                            <i class="bi bi-pencil-square"></i>
                            <span>Cambiar Nombre</span>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Al seleccionar una imagen, este recuadro le permite personalizar su nombre para identificarla fácilmente.
                    </p>
                </div>

                <!-- Toggle: Mostrar Texto de Marca junto al Logotipo en Sidebar -->
                <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 flex items-center justify-between gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Texto de Marca en Barra Lateral
                        </label>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Muestra u oculta el nombre del sistema y versión junto al logotipo en la barra lateral.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <label class="role-toggle-switch">
                            <input type="hidden" name="show_brand_text" value="0">
                            <input type="checkbox" 
                                   name="show_brand_text" 
                                   id="show_brand_text" 
                                   value="1" 
                                   {{ old('show_brand_text', $parameter->show_brand_text ?? 1) == 1 ? 'checked' : '' }}
                                   onchange="updateBrandTextBadge(this)">
                            <span class="role-toggle-track">
                                <span class="role-toggle-thumb"></span>
                            </span>
                        </label>
                        <span id="brandTextBadge" class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase {{ old('show_brand_text', $parameter->show_brand_text ?? 1) == 1 ? 'bg-blue-100 text-[#0062f5] border border-blue-200' : 'bg-slate-200 text-slate-600 border border-slate-300' }}">
                            {{ old('show_brand_text', $parameter->show_brand_text ?? 1) == 1 ? 'VISIBLE' : 'OCULTO' }}
                        </span>
                    </div>
                </div>

                <!-- Catálogo de Imágenes -->
                <div>
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                            Catálogo de Imágenes
                        </span>
                        <span id="imagesCountBadge" class="text-[10px] font-semibold text-slate-400">
                            {{ $images->count() }} recursos
                        </span>
                    </div>

                    <!-- Ventana con scroll para máximo 3 hileras -->
                    <div class="catalog-window rounded-2xl bg-slate-50/90 border border-slate-200/90 p-3 shadow-inner relative">
                        <div id="catalogScrollContainer" 
                             class="max-h-[210px] overflow-y-auto pr-1.5 custom-catalog-scrollbar"
                             style="-webkit-overflow-scrolling: touch; scroll-behavior: smooth;">
                            <div id="availableImagesGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                @foreach($images as $img)
                                    @php
                                        $isSelected = ($parameter->system_logo == $img->path) || (!$parameter->system_logo && $loop->first);
                                    @endphp
                                    <button type="button" 
                                            data-path="{{ $img->path }}"
                                            data-name="{{ $img->name }}"
                                            onclick="selectImage('{{ $img->path }}', '{{ addslashes($img->name) }}', this)"
                                            onmouseenter="showCatalogHover(event, '{{ asset($img->path) }}', '{{ addslashes($img->name) }}', this)"
                                            onmouseleave="hideCatalogHover()"
                                            class="image-card-btn p-2 border rounded-xl bg-white hover:bg-blue-50/50 transition-all text-left flex items-center gap-2 group relative {{ $isSelected ? 'border-[#0062f5] ring-2 ring-[#0062f5]/20 bg-blue-50/30' : 'border-slate-200 hover:border-slate-300' }}">
                                        
                                        <div class="w-8 h-8 rounded-lg bg-[#071026] flex items-center justify-center flex-shrink-0 p-1 transition-transform duration-200 group-hover:scale-115">
                                            <img src="{{ asset($img->path) }}" 
                                                 alt="{{ $img->name }}" 
                                                 class="max-h-full max-w-full object-contain">
                                        </div>

                                        <span class="img-label text-[11px] font-semibold text-slate-700 group-hover:text-[#0062f5] truncate flex-1">
                                            {{ $img->name }}
                                        </span>

                                        <!-- Botón de eliminar (ícono papelera) -->
                                        <span role="button" 
                                              tabindex="0"
                                              onclick="event.stopPropagation(); openDeleteModal('{{ $img->path }}', '{{ addslashes($img->name) }}', '{{ asset($img->path) }}')"
                                              class="delete-img-btn opacity-60 sm:opacity-0 group-hover:opacity-100 transition-all p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer flex-shrink-0"
                                              title="Eliminar imagen del catálogo">
                                            <i class="bi bi-trash3 text-xs"></i>
                                        </span>

                                        <span class="active-check-icon absolute top-1 right-1 w-2 h-2 rounded-full bg-[#0062f5] {{ $isSelected ? '' : 'hidden' }}"></span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
