@php
    $myPermits = session('mypermits', []);
    $isSuper = auth()->user()->isSuperAdmin();
@endphp

<div class="page-header-toolbar bg-white rounded-2xl border border-slate-200 p-3 sm:p-4 mb-6 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
    <!-- Buscador en Tiempo Real Adaptativo -->
    <div class="relative w-full md:max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
            <i class="bi bi-search text-sm"></i>
        </span>
        <input type="text" 
               id="frmHeaderSearch" 
               class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all placeholder:text-slate-400 bg-slate-50/50 focus:bg-white" 
               placeholder="{{ $searchPlaceholder ?? 'Buscar en tiempo real...' }}">
        <button type="button" 
                id="btnSearchClear" 
                style="display: none;" 
                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition-colors"
                aria-label="Limpiar búsqueda">
            <i class="bi bi-x-circle-fill text-base"></i>
        </button>
    </div>

    <!-- Botones de Acción Condicionados por session('mypermits') con Touch Targets -->
    <div class="flex items-center flex-wrap gap-2 w-full md:w-auto justify-end">
        {{-- Posición 1: Crear (Nuevo) --}}
        @if($isSuper || in_array(1, $myPermits))
            @if(isset($newOnClick) || isset($newModalId))
                <button type="button" 
                        onclick="{{ $newOnClick ?? "openModal('{$newModalId}')" }}" 
                        class="flex-1 sm:flex-none btn-dkript-primary px-4 py-2.5 text-sm min-h-[42px]">
                    <i class="bi bi-plus-lg text-base"></i>
                    <span>{{ $newButtonText ?? 'Nuevo' }}</span>
                </button>
            @endif
        @endif

        {{-- Posición 4: Ver / Exportar PDF --}}
        @if($isSuper || in_array(4, $myPermits))
            @php
                $currentRoute = Route::currentRouteName();
                $defaultPdfRoute = $currentRoute ? str_replace('.index', '.export.pdf', $currentRoute) : null;
                $pdfUrl = $exportPdfUrl ?? (Route::has($defaultPdfRoute) ? route($defaultPdfRoute) : null);
            @endphp
            @if($pdfUrl)
                <div class="inline-flex items-center rounded-xl shadow-sm border border-slate-300 overflow-hidden bg-white min-h-[42px]">
                    <a href="{{ $pdfUrl }}" 
                       title="Descargar Reporte en PDF (Toda la información en BD)" 
                       class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium transition-colors active:scale-95">
                        <i class="bi bi-file-earmark-pdf text-rose-600 text-base"></i>
                        <span class="hidden xs:inline">PDF</span>
                    </a>
                    <a href="{{ $pdfUrl }}?action=print" 
                       target="_blank" 
                       title="Imprimir / Vista Previa de Impresión" 
                       class="inline-flex items-center justify-center px-2.5 py-2.5 bg-slate-50 hover:bg-slate-100 border-l border-slate-200 text-slate-500 hover:text-slate-800 text-xs font-medium transition-colors">
                        <i class="bi bi-printer text-slate-600 text-sm"></i>
                    </a>
                </div>
            @else
                <button type="button" 
                        onclick="window.print()" 
                        title="Exportar a PDF / Imprimir" 
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl transition-colors shadow-sm active:scale-95 min-h-[42px]">
                    <i class="bi bi-file-earmark-pdf text-rose-600 text-base"></i>
                    <span class="hidden xs:inline">PDF</span>
                </button>
            @endif
        @endif

        {{-- Posición 5: Especial / Exportar Excel --}}
        @if($isSuper || in_array(5, $myPermits))
            @php
                $currentRoute = Route::currentRouteName();
                $defaultExcelRoute = $currentRoute ? str_replace('.index', '.export.excel', $currentRoute) : null;
                $excelUrl = $exportExcelUrl ?? (Route::has($defaultExcelRoute) ? route($defaultExcelRoute) : null);
            @endphp
            @if($excelUrl)
                <a href="{{ $excelUrl }}" 
                   title="Descargar Hoja Excel (.xls) con toda la información en BD" 
                   class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl transition-colors shadow-sm active:scale-95 min-h-[42px]">
                    <i class="bi bi-file-earmark-excel text-emerald-600 text-base"></i>
                    <span class="hidden xs:inline">Excel</span>
                </a>
            @else
                <button type="button" 
                        id="btnExelTop" 
                        title="Exportar a Excel (.xls)" 
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl transition-colors shadow-sm active:scale-95 min-h-[42px]">
                    <i class="bi bi-file-earmark-excel text-emerald-600 text-base"></i>
                    <span class="hidden xs:inline">Excel</span>
                </button>
            @endif
        @endif
    </div>
</div>
