<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Parameter;
use App\Services\AuditService;

class InactivityTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity_time');
            $settings = Parameter::getSystemSettings();
            $timeoutMinutes = (int) ($settings->session_timeout_minutes ?: config('session.lifetime', 15));

            if ($lastActivity && (time() - $lastActivity > ($timeoutMinutes * 60))) {
                $user = Auth::user();

                AuditService::log(
                    'LOGOUT',
                    'AUTH',
                    "Cierre de sesión automático por inactividad del usuario ({$timeoutMinutes} min)",
                    null,
                    [
                        'timeout_minutes' => $timeoutMinutes,
                        'idle_seconds' => time() - $lastActivity,
                    ],
                    $user
                );

                Auth::logout();
                session()->flush();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tu sesión ha expirado por inactividad.',
                        'redirect' => route('login'),
                    ], 401);
                }

                return redirect()->route('login')->with('warning', 'Tu sesión ha expirado por inactividad.');
            }

            session(['last_activity_time' => time()]);
        }

        return $next($request);
    }
}
