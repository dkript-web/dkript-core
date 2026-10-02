<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\DeviceDetectorService;
use App\Services\AuditService;

class UserSessionController extends Controller
{
    /**
     * Lista todas las sesiones activas vinculadas a la cuenta del usuario autenticado
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $currentSessionId = $request->session()->getId();

        $rawSessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->get();

        $sessions = $rawSessions->map(function ($session) use ($currentSessionId) {
            $agentData = DeviceDetectorService::parse($session->user_agent);
            $isCurrent = $session->id === $currentSessionId;
            $carbonDate = Carbon::createFromTimestamp($session->last_activity);

            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address ?: '127.0.0.1',
                'is_current' => $isCurrent,
                'device_type' => $agentData['device_type'],
                'device_name' => $agentData['device_name'],
                'platform' => $agentData['platform'],
                'browser' => $agentData['browser'],
                'icon' => $agentData['icon'],
                'last_activity_timestamp' => $session->last_activity,
                'last_active_human' => $isCurrent ? 'Activa ahora' : $carbonDate->diffForHumans(),
                'last_active_formatted' => $carbonDate->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'current_session_id' => $currentSessionId,
            'total_sessions' => $sessions->count(),
            'sessions' => $sessions,
        ]);
    }

    /**
     * Revoca y elimina una sesión remota específica
     */
    public function destroy(Request $request, string $sessionId)
    {
        $currentSessionId = $request->session()->getId();

        if ($sessionId === $currentSessionId || $sessionId === 'current') {
            return response()->json([
                'status' => 'error',
                'message' => 'No puedes revocar tu sesión activa actual desde este botón. Utiliza "Cerrar Sesión".',
            ], 400);
        }

        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->delete();

        if ($deleted) {
            Log::info('AUTH_SESSION_REVOKED', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'ip' => $request->ip(),
            ]);

            AuditService::log(
                'SECURITY',
                'SESSION',
                'Sesión remota desconectada y revocada exitosamente'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'El dispositivo ha sido desconectado exitosamente.',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'La sesión indicada no fue encontrada o ya ha expirado.',
        ], 404);
    }

    /**
     * Cierra todas las demás sesiones activas en otros dispositivos
     */
    public function logoutOthers(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'La contraseña ingresada es incorrecta.',
            ], 422);
        }

        $currentSessionId = $request->session()->getId();

        // Eliminación directa de la base de datos de todas las sesiones de este usuario excepto la actual
        $deletedCount = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        // Cierre a nivel de framework Laravel
        Auth::logoutOtherDevices($request->input('password'));

        Log::info('AUTH_LOGOUT_OTHER_DEVICES', [
            'user_id' => $user->id,
            'revoked_count' => $deletedCount,
            'ip' => $request->ip(),
        ]);

        AuditService::log(
            'SECURITY',
            'SESSION',
            "Se cerró la sesión en {$deletedCount} otros dispositivos de forma segura"
        );

        return response()->json([
            'status' => 'success',
            'message' => "Se ha cerrado la sesión en {$deletedCount} otros dispositivos exitosamente.",
            'revoked_count' => $deletedCount,
        ]);
    }
}
