<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOption
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int|string  $optionId
     */
    public function handle(Request $request, Closure $next, $optionId = null): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super Admin siempre tiene acceso total
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $myOptions = session('myoptions');
        if ($myOptions === null) {
            $myOptions = $user->profile?->role?->menuOptions()
                ->where('menu_options.status', 1)
                ->pluck('menu_options.id')
                ->toArray() ?? [];
            session(['myoptions' => $myOptions]);
        }

        if ($optionId && !in_array((int)$optionId, $myOptions)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Acceso Denegado: No cuentas con privilegios para esta opción.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Acceso Denegado: No cuentas con privilegios para acceder a este módulo.');
        }

        return $next($request);
    }
}
