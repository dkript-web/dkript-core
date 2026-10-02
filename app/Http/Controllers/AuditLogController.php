<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Parameter;
use App\Services\ExportService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    /**
     * Muestra el visor interactivo de registros de auditoría.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $module = $request->input('module');
        $action = $request->input('action');
        $userId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $detailId = $request->input('detail');

        $baseQuery = AuditLog::query();

        if ($detailId && AuditLog::where('id', $detailId)->exists()) {
            $query = $baseQuery->where(function ($q) use ($search, $module, $action, $dateFrom, $dateTo, $userId, $detailId) {
                $q->where('id', $detailId)
                  ->orWhere(function ($sub) use ($search, $module, $action, $dateFrom, $dateTo, $userId) {
                      $sub->search($search)
                          ->filterByModule($module)
                          ->filterByAction($action)
                          ->dateRange($dateFrom, $dateTo)
                          ->when($userId, fn($u) => $u->where('user_id', $userId));
                  });
            })
            ->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [(int)$detailId]);
        } else {
            $query = $baseQuery
                ->search($search)
                ->filterByModule($module)
                ->filterByAction($action)
                ->dateRange($dateFrom, $dateTo)
                ->when($userId, fn($q) => $q->where('user_id', $userId));
        }

        $query->latest('id');

        $systemSettings = Parameter::getSystemSettings();
        $perPage = $systemSettings->records_per_page ?? 15;

        // Si es AJAX/JSON devuelve los registros estructurados
        if ($request->expectsJson() || $request->ajax()) {
            $logs = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        }

        $logs = $query->paginate($perPage)->withQueryString();

        // Opciones para listas desplegables de filtros
        $availableModules = AuditLog::distinct()->pluck('module')->filter()->sort()->values();
        $availableActions = AuditLog::distinct()->pluck('action')->filter()->sort()->values();
        $availableUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        // Métricas de auditoría
        $metrics = [
            'total' => AuditLog::count(),
            'today' => AuditLog::whereDate('created_at', Carbon::today())->count(),
            'logins' => AuditLog::whereIn('action', ['LOGIN', 'LOGOUT'])->count(),
            'deletes' => AuditLog::where('action', 'DELETE')->count(),
        ];

        return view('audit.index', compact(
            'logs',
            'availableModules',
            'availableActions',
            'availableUsers',
            'metrics',
            'search',
            'module',
            'action',
            'userId',
            'dateFrom',
            'dateTo',
            'detailId'
        ));
    }

    /**
     * Devuelve el detalle JSON de un registro de auditoría para inspección profunda.
     */
    public function show(Request $request, AuditLog $auditLog)
    {
        if (!$request->expectsJson() && !$request->ajax()) {
            return redirect()->route('audit-logs.index', ['detail' => $auditLog->id]);
        }

        return response()->json([
            'success' => true,
            'log' => [
                'id' => $auditLog->id,
                'user_name' => $auditLog->user_name ?: 'Sistema',
                'user_email' => $auditLog->user_email ?: 'N/A',
                'action' => $auditLog->action,
                'module' => $auditLog->module,
                'description' => $auditLog->description,
                'ip_address' => $auditLog->ip_address ?: '127.0.0.1',
                'user_agent' => $auditLog->user_agent ?: 'N/A',
                'old_values' => $auditLog->old_values,
                'new_values' => $auditLog->new_values,
                'url' => $auditLog->url ?: 'N/A',
                'method' => $auditLog->method ?: 'N/A',
                'created_at_formatted' => $auditLog->created_at->format('d/m/Y H:i:s'),
                'created_at_human' => $auditLog->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Exportación de registros de auditoría a Excel (.xls) (Posición RBAC [6,5]).
     */
    public function exportExcel(Request $request, ExportService $exportService)
    {
        $search = $request->input('search');
        $module = $request->input('module');
        $action = $request->input('action');
        $userId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $logs = AuditLog::query()
            ->search($search)
            ->filterByModule($module)
            ->filterByAction($action)
            ->dateRange($dateFrom, $dateTo)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('id')
            ->limit(1000)
            ->get();

        $headers = [
            'ID',
            'Fecha y Hora',
            'Usuario',
            'Correo',
            'Módulo',
            'Acción',
            'Descripción del Evento',
            'Dirección IP',
            'Método HTTP',
        ];

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                $log->id,
                $log->created_at->format('d/m/Y H:i:s'),
                $log->user_name ?: 'Sistema',
                $log->user_email ?: 'N/A',
                $log->module,
                $log->action,
                $log->description,
                $log->ip_address ?: '127.0.0.1',
                $log->method ?: 'N/A',
            ];
        }

        return $exportService->downloadExcel(
            'Reporte de Registros de Auditoría',
            $headers,
            $rows,
            'auditoria_' . date('Y-m-d_His')
        );
    }

    /**
     * Exportación de registros de auditoría a PDF (Posición RBAC [6,4]).
     */
    public function exportPdf(Request $request, ExportService $exportService)
    {
        $search = $request->input('search');
        $module = $request->input('module');
        $action = $request->input('action');
        $userId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $actionType = $request->query('action', 'download');

        $logs = AuditLog::query()
            ->search($search)
            ->filterByModule($module)
            ->filterByAction($action)
            ->dateRange($dateFrom, $dateTo)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('id')
            ->limit(250)
            ->get();

        return $exportService->generatePdf('reports.audit-logs', [
            'reportTitle' => 'Bitácora General de Auditoría del Sistema',
            'logs' => $logs,
        ], 'auditoria_' . date('Y-m-d_His'), $actionType);
    }

    /**
     * Elimina un registro específico de auditoría (Posición RBAC [6,3]).
     */
    public function destroy(Request $request, AuditLog $auditLog)
    {
        $id = $auditLog->id;
        $auditLog->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Registro de auditoría #{$id} eliminado exitosamente.",
            ]);
        }

        return redirect()->route('audit-logs.index')->with('success', "Registro de auditoría #{$id} eliminado.");
    }

    /**
     * Purgar registros antiguos según periodo de retención (Posición RBAC [6,3]).
     */
    public function clear(Request $request)
    {
        $days = $request->input('days', 30);

        if ($days === 'all') {
            $count = AuditLog::count();
            AuditLog::truncate();
            $msg = "Se purgaron todos los registros ({$count} entradas).";
        } else {
            $daysInt = (int)$days;
            $cutoff = Carbon::now()->subDays($daysInt);
            $count = AuditLog::where('created_at', '<', $cutoff)->delete();
            $msg = "Se eliminaron {$count} registros de auditoría anteriores a {$daysInt} días.";
        }

        AuditService::log('DELETE', 'AUDIT', $msg);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('audit-logs.index')->with('success', $msg);
    }
}
