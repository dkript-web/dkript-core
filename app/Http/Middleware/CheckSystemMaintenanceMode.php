<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Parameter;

class CheckSystemMaintenanceMode
{
    /**
     * Intercepta peticiones entrantes según el estado de mantenimiento funcional de Dkript Core
     * o guard temporal de restauración.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Guard de restauración en caliente (maintenance lock temporal durante restore)
        $isRestoring = file_exists(storage_path('framework/dkript_restore.lock'));

        // 2. Consulta de configuración en parameters (tolerante a fallos transitorios)
        $settings = null;
        try {
            $settings = Parameter::getSystemSettings();
        } catch (\Throwable $e) {
            // Si la base de datos se encuentra temporalmente ocupada durante restauración
        }

        $isMaintenanceActive = $settings && (int)($settings->maintenance_mode ?? 0) === 1;

        // Si no está activo el modo mantenimiento ni hay restauración en proceso, continuar normalmente
        if (!$isMaintenanceActive && !$isRestoring) {
            return $next($request);
        }

        // 3. Evaluación de usuarios autenticados
        if (auth()->check()) {
            $user = auth()->user();

            // El Super Administrador siempre conserva acceso total para labores administrativas y de recuperación
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            // Permitir ruta oficial de logout para que usuarios normales puedan desautenticarse limpiamente
            if ($request->is('logout') || $request->routeIs('logout')) {
                return $next($request);
            }

            // Cualquier otro usuario autenticado recibe HTTP 503
            abort(503);
        }

        // 4. Evaluación de usuarios invitados (guest):
        // Durante restauración en caliente, nadie salvo Super Administrador autenticado tiene acceso
        if ($isRestoring) {
            abort(503);
        }

        // En modo mantenimiento general, se permite el acceso al login administrativo
        // con parámetro explícito (?admin=1) o al procesar la autenticación (POST /login o 2FA)
        // para permitir al Super Administrador autenticarse y desactivar el modo mantenimiento
        $isAdminLoginRequest = $request->is('login') && (
            $request->query('admin') == '1' || 
            $request->isMethod('POST')
        );

        $is2FaChallenge = $request->is('two-factor-challenge') || $request->routeIs('two-factor.*');

        if ($isAdminLoginRequest || $is2FaChallenge) {
            return $next($request);
        }

        // Cualquier otro acceso de visitante o usuario anónimo recibe HTTP 503
        abort(503);
    }
}

