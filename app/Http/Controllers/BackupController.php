<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use App\Services\NotificationService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Listado de copias de seguridad
     */
    public function index(Request $request)
    {
        $backups = $this->backupService->listBackups();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $backups,
            ]);
        }

        return view('backups.index', [
            'backups' => $backups,
            'backupDir' => $this->backupService->getBackupDir(),
            'settings' => \App\Models\Parameter::getSystemSettings(),
        ]);
    }

    /**
     * Generar respaldo de base de datos
     */
    public function createDatabaseBackup(Request $request)
    {
        $compress = $request->boolean('compress', true);

        try {
            $backup = $this->backupService->generateDatabaseBackup($compress);

            Log::info("BACKUP_DATABASE_GENERATED: Respaldo de base de datos generado", [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()?->email,
                'filename' => $backup['filename'],
                'size' => $backup['size_formatted'],
                'compressed' => $compress,
                'ip' => $request->ip(),
            ]);

            NotificationService::broadcastToAdmins(
                'Copia de Seguridad Generada',
                "Se generó exitosamente el volcado de base de datos '{$backup['filename']}' ({$backup['size_formatted']}).",
                'backup',
                route('backups.index')
            );

            AuditService::log('BACKUP', 'BACKUPS', "Generación de respaldo de base de datos '{$backup['filename']}' ({$backup['size_formatted']})");

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Respaldo de base de datos generado exitosamente (' . $backup['size_formatted'] . ').',
                    'data' => $backup,
                ]);
            }

            return redirect()->back()->with('success', 'Respaldo de base de datos generado exitosamente (' . $backup['filename'] . ').');
        } catch (\Throwable $e) {
            Log::error("BACKUP_DATABASE_ERROR: Error al generar respaldo", [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al generar el respaldo: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error al generar el respaldo: ' . $e->getMessage());
        }
    }

    /**
     * Generar respaldo de archivos multimedia
     */
    public function createMediaBackup(Request $request)
    {
        try {
            $backup = $this->backupService->generateMediaBackup();

            Log::info("BACKUP_MEDIA_GENERATED: Respaldo multimedia generado", [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()?->email,
                'filename' => $backup['filename'],
                'size' => $backup['size_formatted'],
                'ip' => $request->ip(),
            ]);

            NotificationService::broadcastToAdmins(
                'Respaldo Multimedia Creado',
                "Se generó el paquete multimedia comprimido '{$backup['filename']}' ({$backup['size_formatted']}).",
                'backup',
                route('backups.index')
            );

            AuditService::log('BACKUP', 'BACKUPS', "Generación de respaldo multimedia '{$backup['filename']}' ({$backup['size_formatted']})");

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Respaldo de multimedia generado exitosamente (' . $backup['size_formatted'] . ').',
                    'data' => $backup,
                ]);
            }

            return redirect()->back()->with('success', 'Respaldo de multimedia generado exitosamente (' . $backup['filename'] . ').');
        } catch (\Throwable $e) {
            Log::error("BACKUP_MEDIA_ERROR: Error al generar respaldo de multimedia", [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al generar el respaldo multimedia: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error al generar el respaldo multimedia: ' . $e->getMessage());
        }
    }

    /**
     * Descargar archivo de respaldo
     */
    public function download(Request $request, string $filename)
    {
        $sanitized = basename($filename);

        if (str_contains($filename, '..') || $sanitized !== $filename) {
            Log::warning("PATH_TRAVERSAL_ATTEMPT_BLOCKED: Intento de descarga de archivo sospechoso", [
                'user_id' => Auth::id(),
                'input_filename' => $filename,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Acceso no autorizado.');
        }

        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . $sanitized;

        if (!File::exists($path)) {
            abort(404, 'El archivo de respaldo solicitado no existe.');
        }

        Log::info("BACKUP_DOWNLOADED: Archivo de respaldo descargado", [
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'filename' => $sanitized,
            'ip' => $request->ip(),
        ]);

        return response()->download($path, $sanitized);
    }

    /**
     * Eliminar archivo de respaldo
     */
    public function destroy(Request $request, string $filename)
    {
        $sanitized = basename($filename);

        if (str_contains($filename, '..') || $sanitized !== $filename) {
            Log::warning("PATH_TRAVERSAL_ATTEMPT_BLOCKED: Intento de eliminación de archivo sospechoso", [
                'user_id' => Auth::id(),
                'input_filename' => $filename,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Acceso no autorizado.');
        }

        try {
            $deleted = $this->backupService->deleteBackup($sanitized);

            if ($deleted) {
                Log::info("BACKUP_DELETED: Archivo de respaldo eliminado", [
                    'user_id' => Auth::id(),
                    'user_email' => Auth::user()?->email,
                    'filename' => $sanitized,
                    'ip' => $request->ip(),
                ]);

                AuditService::log('DELETE', 'BACKUPS', "Eliminación de archivo de respaldo '{$sanitized}'");

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Respaldo eliminado exitosamente.',
                    ]);
                }

                return redirect()->back()->with('success', 'Respaldo eliminado exitosamente.');
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontró el archivo para eliminar.',
                ], 404);
            }

            return redirect()->back()->with('error', 'No se encontró el archivo de respaldo.');
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al eliminar el respaldo: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    /**
     * Estado del Programador Cron y Tareas Programadas
     */
    public function schedulerStatus(Request $request)
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $events = [];

        foreach ($schedule->events() as $event) {
            $command = $event->command;
            if ($command) {
                $command = preg_replace('/^.*?artisan\s+/', 'artisan ', $command);
            } else {
                $command = $event->getSummaryForDisplay();
            }

            $events[] = [
                'command' => $command,
                'expression' => $event->expression,
                'description' => $event->description,
                'next_run' => $event->nextRunDate()->format('Y-m-d H:i:s'),
                'next_run_human' => $event->nextRunDate()->diffForHumans(),
                'timezone' => $event->timezone ? (is_object($event->timezone) ? $event->timezone->getName() : (string)$event->timezone) : config('app.timezone'),
            ];
        }

        $settings = \App\Models\Parameter::getSystemSettings();

        return response()->json([
            'status' => 'success',
            'server_time' => now()->format('Y-m-d H:i:s T'),
            'settings' => [
                'auto_backup_enabled' => (bool)$settings->auto_backup_enabled,
                'auto_backup_frequency' => $settings->auto_backup_frequency ?? 'daily',
                'auto_backup_time' => $settings->auto_backup_time ?? '02:00',
                'auto_backup_type' => $settings->auto_backup_type ?? 'database',
                'auto_backup_max_retention' => (int)($settings->auto_backup_max_retention ?? 7),
                'auto_backup_last_run_at' => $settings->auto_backup_last_run_at ? $settings->auto_backup_last_run_at->format('Y-m-d H:i:s') : null,
                'auto_backup_last_run_human' => $settings->auto_backup_last_run_at ? $settings->auto_backup_last_run_at->diffForHumans() : 'Nunca ejecutado',
            ],
            'events' => $events,
            'cron_command' => '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1',
        ]);
    }

    /**
     * Actualizar configuración de respaldos automáticos
     */
    public function updateAutoBackupSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_backup_enabled' => 'required|boolean',
            'auto_backup_frequency' => 'required|in:daily,weekly,monthly',
            'auto_backup_time' => ['required', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'auto_backup_type' => 'required|in:database,all',
            'auto_backup_max_retention' => 'required|integer|min:1|max:365',
        ]);

        $parameter = \App\Models\Parameter::first();
        if (!$parameter) {
            $parameter = new \App\Models\Parameter();
        }

        $parameter->fill($validated);
        $parameter->save();

        Log::info("AUTO_BACKUP_SETTINGS_UPDATED: Configuración de respaldos automáticos actualizada", [
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'settings' => $validated,
            'ip' => $request->ip(),
        ]);

        AuditService::log('UPDATE', 'BACKUPS', 'Actualización de configuración de respaldos automáticos programados');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Configuración de respaldos automáticos guardada exitosamente.',
                'settings' => [
                    'auto_backup_enabled' => (bool)$parameter->auto_backup_enabled,
                    'auto_backup_frequency' => $parameter->auto_backup_frequency,
                    'auto_backup_time' => $parameter->auto_backup_time,
                    'auto_backup_type' => $parameter->auto_backup_type,
                    'auto_backup_max_retention' => (int)$parameter->auto_backup_max_retention,
                    'auto_backup_last_run_at' => $parameter->auto_backup_last_run_at ? $parameter->auto_backup_last_run_at->format('Y-m-d H:i:s') : null,
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Configuración de respaldos automáticos guardada exitosamente.');
    }

    /**
     * Ejecutar respaldo automático de inmediato de forma manual/forzada
     */
    public function runAutoBackupNow(Request $request)
    {
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('dkript:auto-backup', ['--force' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();

            $settings = \App\Models\Parameter::getSystemSettings();
            $backups = $this->backupService->listBackups();

            Log::info("RUN_AUTO_BACKUP_NOW: Respaldo automático ejecutado manualmente", [
                'user_id' => Auth::id(),
                'exit_code' => $exitCode,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => $exitCode === 0 ? 'success' : 'error',
                'message' => $exitCode === 0 
                    ? 'Respaldo automático ejecutado exitosamente.' 
                    : 'Ocurrió un error al ejecutar el respaldo automático.',
                'output' => trim($output),
                'last_run_at' => $settings->auto_backup_last_run_at ? $settings->auto_backup_last_run_at->format('Y-m-d H:i:s') : null,
                'last_run_human' => $settings->auto_backup_last_run_at ? $settings->auto_backup_last_run_at->diffForHumans() : 'Recién ejecutado',
                'backups' => $backups,
            ]);
        } catch (\Throwable $e) {
            Log::error("RUN_AUTO_BACKUP_ERROR: " . $e->getMessage(), [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al ejecutar el respaldo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurar una copia de seguridad de Base de Datos
     */
    public function restore(Request $request, string $filename)
    {
        $sanitized = basename($filename);

        if (str_contains($filename, '..') || $sanitized !== $filename) {
            Log::warning("PATH_TRAVERSAL_ATTEMPT_BLOCKED: Intento de restauración de archivo sospechoso", [
                'user_id' => Auth::id(),
                'input_filename' => $filename,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Acceso no autorizado.');
        }

        try {
            AuditService::log(
                'RESTORE',
                'BACKUPS',
                "Solicitud de restauración de base de datos para el archivo '{$sanitized}'"
            );

            $result = $this->backupService->restoreDatabase($sanitized);

            Log::info("DATABASE_RESTORE_COMPLETED: Base de datos restaurada exitosamente", [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()?->email,
                'filename' => $sanitized,
                'safety_backup' => $result['safety_backup'] ?? null,
                'statements_executed' => $result['statements_executed'] ?? 0,
                'ip' => $request->ip(),
            ]);

            AuditService::log(
                'RESTORE',
                'BACKUPS',
                "Restauración de base de datos completada exitosamente desde '{$sanitized}'. Safety backup: '{$result['safety_backup']}'"
            );

            NotificationService::broadcastToAdmins(
                'Base de Datos Restaurada',
                "Se restauró exitosamente la base de datos desde '{$sanitized}'. Se generó el safety backup '{$result['safety_backup']}'.",
                'backup',
                route('backups.index')
            );

            $msg = "Base de datos restaurada exitosamente desde '{$sanitized}'. Se generó una copia preventiva previa: {$result['safety_backup']}.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $msg,
                    'data' => $result,
                ]);
            }

            return redirect()->route('backups.index')->with('success', $msg);

        } catch (\Throwable $e) {
            Log::error("DATABASE_RESTORE_FAILED: Fallo al restaurar base de datos", [
                'user_id' => Auth::id(),
                'filename' => $sanitized,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            // Determinar código HTTP según clasificación
            $rawCode = (int)$e->getCode();
            $statusCode = in_array($rawCode, [403, 404, 422, 500], true) ? $rawCode : 500;

            if ($e instanceof \PDOException) {
                $statusCode = 500;
            }

            // Mensajes seguros según la naturaleza del error:
            // Validación conocida (403, 404, 422): mensaje descriptivo controlado
            // Error operativo interno (500 o PDO): mensaje seguro genérico que no filtra SQL ni credenciales
            if ($statusCode === 500) {
                if (str_contains($e->getMessage(), 'Safety Backup')) {
                    $userMessage = 'No se pudo generar el respaldo de seguridad preventivo (Safety Backup). La restauración ha sido cancelada por seguridad.';
                } else {
                    $userMessage = 'Ocurrió un error interno al ejecutar la restauración en la base de datos. La operación fue detenida para proteger la integridad del sistema.';
                }
            } else {
                $userMessage = $e->getMessage();
            }

            try {
                AuditService::log(
                    'RESTORE',
                    'BACKUPS',
                    "Fallo en restauración de base de datos desde '{$sanitized}': {$userMessage}"
                );
            } catch (\Throwable $auditEx) {
                // Preservar en caso de fallo de DB
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $userMessage,
                ], $statusCode);
            }

            return redirect()->back()->with('error', $userMessage);
        }
    }
}

