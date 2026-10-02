<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPermissionPosition
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int|string  $moduleId
     * @param  int|string  $position
     */
    public function handle(Request $request, Closure $next, $moduleId = null, $position = null): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super Admin siempre tiene acceso total
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($moduleId !== null && $position !== null) {
            if (!$user->hasPermission((int)$moduleId, (int)$position)) {
                $actionNames = [
                    1 => 'Crear',
                    2 => 'Editar',
                    3 => 'Eliminar',
                    4 => 'Ver / PDF',
                    5 => 'Especial / Excel',
                ];
                $actionName = $actionNames[(int)$position] ?? "Acción #{$position}";

                \Illuminate\Support\Facades\Log::warning('UNAUTHORIZED_RBAC_ACTION_ATTEMPT', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'module_id' => $moduleId,
                    'position' => $position,
                    'action' => $actionName,
                    'ip' => $request->ip(),
                ]);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => "Acceso Denegado: No cuentas con el privilegio de [{$actionName}] en este módulo.",
                    ], 403);
                }

                return back()->with('error', "Acceso Denegado: No cuentas con el privilegio de [{$actionName}] en este módulo.");
            }
        }

        return $next($request);
    }
}