<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuOption;
use App\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $menuOptions = MenuOption::with(['permissions' => function ($query) use ($search) {
            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }
            $query->orderBy('position', 'asc');
        }])
        ->orderBy('order', 'asc')
        ->get();

        $totalPermissions = Permission::count();

        return view('permissions.index', compact('menuOptions', 'totalPermissions', 'search'));
    }

    public function exportExcel(\App\Services\ExportService $exportService)
    {
        $menuOptions = MenuOption::with(['permissions' => function ($q) {
            $q->orderBy('position', 'asc');
        }])->orderBy('order', 'asc')->get();

        $headers = [
            'ID Permiso',
            'Módulo / Opción',
            'Ruta Interna',
            'Posición RBAC',
            'Permiso Granular',
            'Tipo de Acción',
        ];

        $rows = [];
        foreach ($menuOptions as $option) {
            foreach ($option->permissions as $perm) {
                $actionType = match ($perm->position) {
                    1 => 'Crear (Altas y Duplicar)',
                    2 => 'Editar (Modificación)',
                    3 => 'Eliminar (Bajas)',
                    4 => 'Ver / Exportar PDF',
                    5 => 'Especial / Exportar Excel',
                    default => 'Acción General',
                };

                $rows[] = [
                    $perm->id,
                    $option->name,
                    $option->route_name,
                    $perm->position,
                    $perm->name,
                    $actionType,
                ];
            }
        }

        return $exportService->downloadExcel('Reporte de Permisos y Módulos', $headers, $rows, 'permisos_' . date('Y-m-d'));
    }

    public function exportPdf(Request $request, \App\Services\ExportService $exportService)
    {
        $menuOptions = MenuOption::with(['permissions' => function ($q) {
            $q->orderBy('position', 'asc');
        }])->orderBy('order', 'asc')->get();
        $action = $request->query('action', 'download');

        return $exportService->generatePdf('reports.permissions', [
            'reportTitle' => 'Catálogo General de Módulos y Permisos RBAC',
            'menuOptions' => $menuOptions,
        ], 'reporte_permisos_' . date('Y-m-d'), $action);
    }
}
