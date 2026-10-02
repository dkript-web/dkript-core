<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Parameter;
use App\Services\BackupService;
use App\Services\AuditService;
use App\Services\NotificationService;
use Exception;

class AutoBackupCommand extends Command
{
    /**
     * Nombre y firma del comando Artisan.
     */
    protected $signature = 'dkript:auto-backup {--force : Ejecutar el respaldo incluso si la configuración automática está inactiva}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Ejecuta el respaldo programado del sistema (base de datos y/o medios) y purga copias antiguas según la retención configurada.';

    /**
     * Ejecuta el comando.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info("Iniciando motor de respaldos automáticos Dkript Inc...");

        $settings = Parameter::getSystemSettings();
        $isForced = (bool)$this->option('force');

        if (!$settings->auto_backup_enabled && !$isForced) {
            $this->warn("Los respaldos automáticos se encuentran DESACTIVADOS en la configuración del sistema. Omitiendo ejecución.");
            return self::SUCCESS;
        }

        try {
            $type = $settings->auto_backup_type ?? 'database';
            $generatedFiles = [];

            // 1. Generar respaldo de Base de Datos
            $dbBackup = $backupService->generateDatabaseBackup(true);
            $generatedFiles[] = "DB: " . $dbBackup['filename'] . " (" . $dbBackup['size_formatted'] . ")";
            $this->info("✓ Respaldo de Base de Datos generado: {$dbBackup['filename']}");

            // 2. Si el tipo es 'all', generar también respaldo de archivos multimedia
            if ($type === 'all') {
                $mediaBackup = $backupService->generateMediaBackup();
                $generatedFiles[] = "Medios: " . $mediaBackup['filename'] . " (" . $mediaBackup['size_formatted'] . ")";
                $this->info("✓ Respaldo de Archivos Multimedia generado: {$mediaBackup['filename']}");
            }

            // 3. Purgar respaldos antiguos según política de retención
            $retentionLimit = (int)($settings->auto_backup_max_retention ?: 7);
            $prunedCount = $backupService->pruneBackups($retentionLimit);
            if ($prunedCount > 0) {
                $this->info("✓ Política de retención aplicada ({$retentionLimit} máx): {$prunedCount} respaldos antiguos purgados.");
            }

            // 4. Actualizar fecha de última ejecución
            if ($settings->exists) {
                $settings->update([
                    'auto_backup_last_run_at' => now(),
                ]);
            }

            $summary = implode(' | ', $generatedFiles);

            // 5. Auditoría del Sistema
            AuditService::log(
                'SYSTEM',
                'BACKUP',
                "Copia de seguridad automática completada exitosamente. {$summary}. Purgados: {$prunedCount}"
            );

            // 6. Notificación In-App para Super Administradores
            NotificationService::sendToRole(
                1,
                "Respaldo automático del sistema completado con éxito ({$summary}).",
                'system'
            );

            $this->info("★ Respaldo automático completado con éxito.");
            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Error crítico durante la ejecución del auto-respaldo: " . $e->getMessage());

            AuditService::log(
                'SYSTEM',
                'BACKUP',
                "Fallo crítico en respaldo automático programado: " . $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}
