@extends('layouts.admin')

@section('title', 'Notificaciones')
@section('page_title', 'Centro de Notificaciones')

@section('content')
<div class="content-section max-w-5xl w-full mx-auto pb-24 sm:pb-28 space-y-6">
    
    <!-- Encabezado de Navegación / Breadcrumbs -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-[#0062f5] transition-colors flex items-center gap-1">
                    <i class="bi bi-house"></i>
                    <span>Inicio</span>
                </a>
                <span>/</span>
                <span class="text-slate-800">Notificaciones</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Centro de Notificaciones</h2>
            <p class="text-xs sm:text-sm text-slate-500">Historial completo de alertas operativas, eventos de seguridad y actividades del sistema</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" 
                    onclick="DkriptNotification.markAllAsRead(true)" 
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 hover:border-slate-300 transition-all shadow-xs cursor-pointer">
                <i class="bi bi-check2-all text-sm text-[#0062f5]"></i>
                <span>Marcar todas leídas</span>
            </button>
            <button type="button" 
                    onclick="DkriptNotification.clearAll(true)" 
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-rose-200 text-rose-600 text-xs font-bold hover:bg-rose-50 hover:border-rose-300 transition-all shadow-xs cursor-pointer">
                <i class="bi bi-trash3 text-sm"></i>
                <span>Vaciar historial</span>
            </button>
        </div>
    </div>

    <!-- TARJETAS RESUMEN DE ESTADO -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="table-card p-4 sm:p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Registradas</p>
                <p class="text-2xl font-black text-slate-900 mt-0.5">{{ $notifications->total() }}</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-[#0062f5] border border-blue-200/60 flex items-center justify-center text-xl shadow-xs">
                <i class="bi bi-bell"></i>
            </div>
        </div>

        <div class="table-card p-4 sm:p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">No Leídas</p>
                <p class="text-2xl font-black text-amber-600 mt-0.5" id="pageUnreadCount">
                    {{ auth()->user()->unreadNotifications()->count() }}
                </p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/60 flex items-center justify-center text-xl shadow-xs">
                <i class="bi bi-envelope-exclamation"></i>
            </div>
        </div>

        <div class="table-card p-4 sm:p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Leídas</p>
                <p class="text-2xl font-black text-emerald-600 mt-0.5">
                    {{ max(0, $notifications->total() - auth()->user()->unreadNotifications()->count()) }}
                </p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200/60 flex items-center justify-center text-xl shadow-xs">
                <i class="bi bi-check2-circle"></i>
            </div>
        </div>
    </div>

    <!-- LISTA DE NOTIFICACIONES -->
    <div class="table-card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                <i class="bi bi-clock-history text-[#0062f5]"></i>
                <span>Historial de Actividades Recientes</span>
            </h3>
            <span class="text-xs text-slate-500 font-mono-code">Página {{ $notifications->currentPage() }} de {{ $notifications->lastPage() ?: 1 }}</span>
        </div>

        <div class="divide-y divide-slate-100" id="notificationListPageContainer">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                    $type = strtolower($data['type'] ?? 'info');
                    
                    // Colores por tipo
                    $badgeConfig = match($type) {
                        'security', 'warning' => ['bg' => 'bg-amber-500/10', 'border' => 'border-amber-500/30', 'text' => 'text-amber-600', 'badge' => 'bg-amber-50 text-amber-700 border-amber-200'],
                        'system' => ['bg' => 'bg-purple-500/10', 'border' => 'border-purple-500/30', 'text' => 'text-purple-600', 'badge' => 'bg-purple-50 text-purple-700 border-purple-200'],
                        'backup' => ['bg' => 'bg-blue-500/10', 'border' => 'border-blue-500/30', 'text' => 'text-[#0062f5]', 'badge' => 'bg-blue-50 text-blue-700 border-blue-200'],
                        'success' => ['bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/30', 'text' => 'text-emerald-600', 'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                        'error' => ['bg' => 'bg-rose-500/10', 'border' => 'border-rose-500/30', 'text' => 'text-rose-600', 'badge' => 'bg-rose-50 text-rose-700 border-rose-200'],
                        default => ['bg' => 'bg-sky-500/10', 'border' => 'border-sky-500/30', 'text' => 'text-sky-600', 'badge' => 'bg-sky-50 text-sky-700 border-sky-200'],
                    };
                @endphp
                <div id="notification-row-{{ $notification->id }}" 
                     class="p-4 sm:p-5 flex items-start gap-4 transition-colors {{ $isUnread ? 'bg-blue-50/40 hover:bg-blue-50/70' : 'bg-white hover:bg-slate-50/80' }}">
                    
                    <!-- Icono representativo -->
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl {{ $badgeConfig['bg'] }} border {{ $badgeConfig['border'] }} {{ $badgeConfig['text'] }} flex items-center justify-center text-lg sm:text-xl flex-shrink-0 shadow-xs mt-0.5">
                        <i class="bi {{ $data['icon'] ?? 'bi-bell-fill' }}"></i>
                    </div>

                    <!-- Contenido Central -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h4 class="text-sm font-extrabold text-slate-900 {{ $isUnread ? 'text-[#0062f5]' : '' }}">
                                {{ $data['title'] ?? 'Notificación del Sistema' }}
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $badgeConfig['badge'] }}">
                                {{ ucfirst($data['type'] ?? 'info') }}
                            </span>
                            @if($isUnread)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500/10 text-rose-600 border border-rose-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    <span>Nueva</span>
                                </span>
                            @endif
                        </div>

                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-2">
                            {{ $data['message'] ?? '' }}
                        </p>

                        <div class="flex items-center gap-4 text-[11px] text-slate-400 font-mono-code flex-wrap">
                            <span class="flex items-center gap-1">
                                <i class="bi bi-clock"></i>
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                            <span>&bull;</span>
                            <span>{{ $notification->created_at->format('d/m/Y H:i:s') }}</span>

                            @if(!empty($data['action_url']))
                                <a href="{{ $data['action_url'] }}" 
                                   class="text-[#0062f5] hover:text-[#004ecc] font-bold font-sans inline-flex items-center gap-1 hover:underline ml-2">
                                    <span>Ver detalle</span>
                                    <i class="bi bi-box-arrow-up-right text-[10px]"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Botones de Acción Individual -->
                    <div class="flex items-center gap-1.5 flex-shrink-0 self-center sm:self-start">
                        @if($isUnread)
                            <button type="button" 
                                    onclick="DkriptNotification.markAsRead('{{ $notification->id }}', true)" 
                                    class="p-2 rounded-xl text-slate-500 hover:text-[#0062f5] hover:bg-blue-100/50 transition-colors cursor-pointer"
                                    title="Marcar como leída">
                                <i class="bi bi-check2 text-base"></i>
                            </button>
                        @endif
                        <button type="button" 
                                onclick="DkriptNotification.deleteNotification('{{ $notification->id }}', true)" 
                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                title="Eliminar">
                            <i class="bi bi-trash3 text-base"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center px-4 flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mb-3 shadow-inner">
                        <i class="bi bi-bell-slash"></i>
                    </div>
                    <h4 class="text-base font-extrabold text-slate-700">Sin notificaciones por ahora</h4>
                    <p class="text-xs sm:text-sm text-slate-400 max-w-sm mt-1">
                        Tu bandeja está completamente al día. Cualquier evento crítico o aviso administrativo aparecerá reflejado aquí.
                    </p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
