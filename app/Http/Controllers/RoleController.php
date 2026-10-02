<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Role;
use App\Models\MenuOption;
use App\Models\Permission;
use App\Services\AuditService;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $roles = Role::with(['menuOptions', 'permissions', 'profiles.user'])
            ->withCount(['profiles', 'permissions'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('id', 'asc')
            ->paginate(\App\Models\Parameter::getSystemSettings()->records_per_page ?? 10);

        $allMenuOptions = MenuOption::with(['permissions' => function ($q) {
            $q->orderBy('position', 'asc');
        }])->where('status', 1)->orderBy('order', 'asc')->get();

        return view('roles.index', compact('roles', 'allMenuOptions', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:0,1'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['integer', 'exists:menu_options,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        $modules = $request->input('modules', []);
        $permissions = $request->input('permissions', []);

        $role->menuOptions()->sync($modules);
        $role->permissions()->sync($permissions);

        AuditService::log('CREATE', 'ROLES', "Creación de rol '{$role->name}'", null, [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'status' => $role->status,
            'modules_count' => count($modules),
            'permissions_count' => count($permissions),
        ]);

        Log::info('ROLE_CREATED', [
            'actor_id' => auth()->id(),
            'role_id' => $role->id,
            'role_name' => $role->name,
            'ip' => $request->ip(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Rol registrado exitosamente.']);
        }

        return redirect()->route('roles.index')->with('success', 'Rol registrado exitosamente.');
    }

    public function edit(Role $role)
    {
        $role->load(['menuOptions', 'permissions']);

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'status' => $role->status,
            'modules' => $role->menuOptions->pluck('id')->toArray(),
            'permissions' => $role->permissions->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:0,1'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['integer', 'exists:menu_options,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $oldData = [
            'name' => $role->name,
            'description' => $role->description,
            'status' => $role->status,
            'modules' => $role->menuOptions()->pluck('menu_options.id')->toArray(),
            'permissions' => $role->permissions()->pluck('permissions.id')->toArray(),
        ];

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        $modules = $request->input('modules', []);
        $permissions = $request->input('permissions', []);

        $role->menuOptions()->sync($modules);
        $role->permissions()->sync($permissions);

        AuditService::log('UPDATE', 'ROLES', "Actualización de rol '{$role->name}'", $oldData, [
            'name' => $role->name,
            'description' => $role->description,
            'status' => $role->status,
            'modules' => $modules,
            'permissions' => $permissions,
        ]);

        Log::info('ROLE_UPDATED', [
            'actor_id' => auth()->id(),
            'role_id' => $role->id,
            'role_name' => $role->name,
            'ip' => $request->ip(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Rol actualizado exitosamente.']);
        }

        return redirect()->route('roles.index')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy(Role $role)
    {
        if ($role->id === 1) {
            return back()->with('error', 'No se puede eliminar el rol de Super Administrador.');
        }

        if ($role->profiles()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados.');
        }

        $deletedId = $role->id;
        $deletedName = $role->name;
        $deletedDescription = $role->description;

        $role->menuOptions()->detach();
        $role->permissions()->detach();
        $role->delete();

        AuditService::log('DELETE', 'ROLES', "Eliminación de rol '{$deletedName}' #{$deletedId}", [
            'id' => $deletedId,
            'name' => $deletedName,
            'description' => $deletedDescription,
        ], null);

        Log::info('ROLE_DELETED', [
            'actor_id' => auth()->id(),
            'role_id' => $deletedId,
            'role_name' => $deletedName,
            'ip' => request()->ip(),
        ]);

        return redirect()->route('roles.index')->with('success', 'Rol eliminado exitosamente.');
    }

    public function exportExcel(\App\Services\ExportService $exportService)
    {
        $roles = Role::withCount(['profiles', 'permissions'])->orderBy('id', 'asc')->get();

        $headers = [
            'ID',
            'Nombre del Rol',
            'Descripción',
            'Usuarios Asignados',
            'Permisos Asignados',
            'Estatus',
        ];

        $rows = [];
        foreach ($roles as $role) {
            $rows[] = [
                $role->id,
                $role->name,
                $role->description ?: 'Sin descripción',
                $role->profiles_count ?? 0,
                $role->permissions_count ?? 0,
                $role->status == 1 ? 'Activo' : 'Inactivo',
            ];
        }

        return $exportService->downloadExcel('Reporte de Roles', $headers, $rows, 'roles_' . date('Y-m-d'));
    }

    public function exportPdf(Request $request, \App\Services\ExportService $exportService)
    {
        $roles = Role::withCount(['profiles', 'permissions'])->orderBy('id', 'asc')->get();
        $action = $request->query('action', 'download');

        return $exportService->generatePdf('reports.roles', [
            'reportTitle' => 'Catálogo General de Roles RBAC',
            'roles' => $roles,
        ], 'reporte_roles_' . date('Y-m-d'), $action);
    }
}
