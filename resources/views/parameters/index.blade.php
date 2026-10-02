@extends('layouts.admin')

@section('title', 'Parámetros del Sistema')
@section('page_title', 'Configuración General')

@section('content')
<div class="content-section max-w-7xl w-full mx-auto pb-24 sm:pb-28">
    <form method="POST" action="{{ route('parameters.update') }}" id="parametersForm">
        @csrf

        <!-- Barra Superior de Navegación por Iconos (Top Dock) -->
        @include('partials.parameters.nav-tabs')

        <!-- Contenedor Principal de Paneles por Pestaña (100% Ancho) -->
        <main class="parameter-main-content dock-content-offset w-full min-w-0 relative z-10">
            @include('partials.parameters.tab-general')
            @include('partials.parameters.tab-modals')
            @include('partials.parameters.tab-branding')
            @include('partials.parameters.tab-errors')
            @include('partials.parameters.tab-telephony')
            @include('partials.parameters.tab-mail')
            @include('partials.parameters.tab-maintenance')
        </main>

        <!-- Input invisible para subida de imágenes al hacer clic -->
        <input type="file" 
               id="logoFileInput" 
               class="hidden" 
               accept="image/png,image/jpeg,image/webp,image/svg+xml,image/gif" 
               onchange="handleLogoUpload(this)">

        <!-- BARRA DE ACCIÓN INFERIOR / GUARDAR (Siempre visible fija al fondo de la pantalla) -->
        <div class="fixed bottom-0 left-0 lg:left-64 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_25px_rgba(0,0,0,0.08)] py-3 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                <!-- Texto explicativo (oculto en modo celular) -->
                <div class="hidden sm:flex items-center gap-2.5 text-xs text-slate-500">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#0062f5] flex items-center justify-center flex-shrink-0 border border-blue-100">
                        <i class="bi bi-shield-check text-xs"></i>
                    </div>
                    <span>Los cambios se aplicarán de inmediato en toda la plataforma al guardar.</span>
                </div>

                <!-- Botón de Guardar: En modo celular solo dice "Guardar" con icono de guardado -->
                <button type="submit" 
                        class="btn-dkript-primary px-6 py-2.5 text-xs sm:text-sm flex items-center justify-center gap-2 shadow-md shadow-blue-500/20 active:scale-95 whitespace-nowrap w-full sm:w-auto">
                    <i class="bi bi-floppy-fill text-sm sm:text-base"></i>
                    <span class="sm:hidden">Guardar</span>
                    <span class="hidden sm:inline">Guardar Parámetros</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Componentes Flotantes y Modales -->
    @include('partials.parameters.image-hover-popover')
    @include('partials.parameters.error-preview-modal')
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        DkriptParameters.init({
            csrfToken: '{{ csrf_token() }}',
            uploadUrl: '{{ route("parameters.upload-image") }}',
            renameUrl: '{{ route("parameters.rename-image") }}',
            deleteUrl: '{{ route("parameters.delete-image") }}',
            previewBaseUrl: '{{ url("/parameters/errors/preview") }}'
        });
    });
</script>
@endsection
