<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Devuelve las notificaciones del usuario activo (JSON o Vista Completa).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($request->expectsJson() || $request->ajax()) {
            $notifications = $user->notifications()
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'title' => $n->data['title'] ?? 'Notificación del Sistema',
                        'message' => $n->data['message'] ?? '',
                        'type' => $n->data['type'] ?? 'info',
                        'icon' => $n->data['icon'] ?? 'bi-bell-fill',
                        'action_url' => $this->resolveActionUrl($n->data['action_url'] ?? null, $n->data),
                        'read_at' => $n->read_at,
                        'is_read' => !is_null($n->read_at),
                        'created_at_human' => $n->created_at ? $n->created_at->diffForHumans() : '',
                    ];
                });

            return response()->json([
                'success' => true,
                'unread_count' => $user->unreadNotifications()->count(),
                'notifications' => $notifications,
            ]);
        }

        $notifications = $user->notifications()->latest()->paginate(15);
        $notifications->getCollection()->transform(function ($n) {
            $data = $n->data;
            if (!empty($data['action_url'])) {
                $data['action_url'] = $this->resolveActionUrl($data['action_url'], $data);
                $n->data = $data;
            }
            return $n;
        });

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Resuelve la URL de acción garantizando que dirija a la incidencia correspondiente.
     */
    protected function resolveActionUrl(?string $actionUrl, array $data = []): ?string
    {
        if (!$actionUrl) {
            return null;
        }

        $title = $data['title'] ?? '';
        $isParameterNotification = (
            str_contains($actionUrl, '/parameters') ||
            str_contains($actionUrl, 'search=configuraci') ||
            str_contains(strtolower($title), 'parámetro') ||
            str_contains(strtolower($title), 'parametro')
        );

        if ($isParameterNotification) {
            // Si ya cuenta con un detailId válido existente, respetarlo
            if (preg_match('/[?&]detail=(\d+)/', $actionUrl, $matches)) {
                $detailId = (int)$matches[1];
                if (\App\Models\AuditLog::where('id', $detailId)->exists()) {
                    return route('audit-logs.index', ['detail' => $detailId]);
                }
            }

            // Buscar el registro de auditoría más relevante para este evento
            $latestAudit = \App\Models\AuditLog::where('module', 'SYSTEM')
                ->where('action', 'SETTINGS')
                ->latest('id')
                ->first();

            if (!$latestAudit) {
                $latestAudit = \App\Models\AuditLog::where('module', 'SYSTEM')
                    ->latest('id')
                    ->first();
            }

            return $latestAudit 
                ? route('audit-logs.index', ['detail' => $latestAudit->id])
                : route('audit-logs.index');
        }

        // Si la URL contiene una búsqueda en auditoría con search= que no tenga resultados, remover el filtro fallido
        if (str_contains($actionUrl, 'audit-logs') && str_contains($actionUrl, 'search=')) {
            $parsedUrl = parse_url($actionUrl);
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            if (!empty($queryParams['search'])) {
                $matchesCount = \App\Models\AuditLog::search($queryParams['search'])->count();
                if ($matchesCount === 0) {
                    unset($queryParams['search']);
                    return route('audit-logs.index', $queryParams);
                }
            }
        }

        return $actionUrl;
    }

    /**
     * Marca una notificación específica como leída.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'message' => 'Notificación marcada como leída.',
        ]);
    }

    /**
     * Marca todas las notificaciones pendientes como leídas.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
            'message' => 'Todas las notificaciones han sido marcadas como leídas.',
        ]);
    }

    /**
     * Elimina una notificación específica.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'message' => 'Notificación eliminada exitosamente.',
        ]);
    }

    /**
     * Elimina todas las notificaciones del usuario.
     */
    public function clearAll(Request $request)
    {
        $user = $request->user();
        $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
            'message' => 'Bandeja de notificaciones vaciada exitosamente.',
        ]);
    }
}