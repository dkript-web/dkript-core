<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Exception;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true, true);
        }
    }

    /**
     * Obtener ruta del directorio de respaldos
     */
    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * Generar respaldo completo de base de datos
     *
     * @param bool $compress Si es true genera .sql.gz, si es false genera .sql
     * @return array Metadata del archivo generado
     */
    public function generateDatabaseBackup(bool $compress = true, string $prefix = 'backup_db_'): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $timestamp = date('Y-m-d_H-i-s');
        $suffix = ($prefix !== 'backup_db_') ? '_' . substr(md5(microtime()), 0, 4) : '';
        $baseFilename = "{$prefix}{$timestamp}{$suffix}";
        $sqlFilename = "{$baseFilename}.sql";
        $finalFilename = $compress ? "{$baseFilename}.sql.gz" : $sqlFilename;
        $sqlPath = $this->backupDir . DIRECTORY_SEPARATOR . $sqlFilename;
        $finalPath = $this->backupDir . DIRECTORY_SEPARATOR . $finalFilename;

        $handle = fopen($sqlPath, 'w+');
        if (!$handle) {
            throw new Exception("No se pudo crear el archivo de respaldo en: {$sqlPath}");
        }

        // Header SQL
        fwrite($handle, "-- ========================================================\n");
        fwrite($handle, "-- Dkript Admin Enterprise Database Backup\n");
        fwrite($handle, "-- Fecha de Generación: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Motor de Base de Datos: {$driver}\n");
        fwrite($handle, "-- ========================================================\n\n");

        if ($driver === 'mysql') {
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

            $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $key = "Tables_in_" . $connection->getDatabaseName();

            foreach ($tables as $t) {
                $tableName = $t->$key ?? array_values((array)$t)[0];

                // Table structure
                fwrite($handle, "\n-- --------------------------------------------------------\n");
                fwrite($handle, "-- Estructura de tabla: `{$tableName}`\n");
                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createTable[0]->{'Create Table'} ?? '';
                fwrite($handle, "{$createSql};\n\n");

                // Table data
                $this->dumpTableDataMysql($handle, $tableName);
            }

            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        } elseif ($driver === 'sqlite') {
            fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");

            $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            foreach ($tables as $table) {
                $tableName = $table->name;
                fwrite($handle, "\n-- Estructura de tabla: `{$tableName}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS \"{$tableName}\";\n");
                fwrite($handle, "{$table->sql};\n\n");

                $this->dumpTableDataSqlite($handle, $tableName);
            }

            fwrite($handle, "\nPRAGMA foreign_keys = ON;\n");
        } else {
            fclose($handle);
            throw new Exception("Driver de base de datos no soportado para respaldos: {$driver}");
        }

        fclose($handle);

        // Si se solicitó comprimido (.sql.gz)
        if ($compress) {
            $this->compressGzip($sqlPath, $finalPath);
            if (File::exists($sqlPath)) {
                File::delete($sqlPath);
            }
        }

        $size = File::size($finalPath);

        return [
            'filename' => $finalFilename,
            'path' => $finalPath,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'compressed' => $compress,
            'type' => $compress ? 'sql.gz' : 'sql',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Volcado de datos para MySQL
     */
    protected function dumpTableDataMysql($handle, string $tableName): void
    {
        $pdo = DB::connection()->getPdo();
        $count = DB::table($tableName)->count();
        if ($count === 0) return;

        fwrite($handle, "-- Volcado de datos para la tabla `{$tableName}` ({$count} registros)\n");

        foreach (DB::table($tableName)->cursor() as $row) {
            $rowArray = (array)$row;
            $columns = array_map(fn($col) => "`{$col}`", array_keys($rowArray));
            $values = array_map(function ($val) use ($pdo) {
                if (is_null($val)) return 'NULL';
                if (is_numeric($val) && !is_string($val)) return $val;
                return $pdo->quote($val);
            }, array_values($rowArray));

            $line = "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            fwrite($handle, $line);
        }

        fwrite($handle, "\n");
    }

    /**
     * Volcado de datos para SQLite
     */
    protected function dumpTableDataSqlite($handle, string $tableName): void
    {
        $pdo = DB::connection()->getPdo();
        $count = DB::table($tableName)->count();
        if ($count === 0) return;

        fwrite($handle, "-- Volcado de datos para la tabla \"{$tableName}\"\n");

        foreach (DB::table($tableName)->cursor() as $row) {
            $rowArray = (array)$row;
            $columns = array_map(fn($col) => "\"{$col}\"", array_keys($rowArray));
            $values = array_map(function ($val) use ($pdo) {
                if (is_null($val)) return 'NULL';
                if (is_numeric($val) && !is_string($val)) return $val;
                return $pdo->quote($val);
            }, array_values($rowArray));

            $line = "INSERT INTO \"{$tableName}\" (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            fwrite($handle, $line);
        }

        fwrite($handle, "\n");
    }

    /**
     * Comprimir archivo SQL a Gzip (.sql.gz)
     */
    protected function compressGzip(string $sourcePath, string $targetPath): void
    {
        $srcHandle = fopen($sourcePath, 'rb');
        $gzHandle = gzopen($targetPath, 'wb9');

        if (!$srcHandle || !$gzHandle) {
            throw new Exception("Error al inicializar streams de compresión Gzip.");
        }

        while (!feof($srcHandle)) {
            gzwrite($gzHandle, fread($srcHandle, 1024 * 512));
        }

        fclose($srcHandle);
        gzclose($gzHandle);
    }

    /**
     * Generar respaldo de archivos multimedia en .zip
     */
    public function generateMediaBackup(): array
    {
        $mediaDir = public_path('uploads');
        if (!File::exists($mediaDir)) {
            File::makeDirectory($mediaDir, 0755, true, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_media_{$timestamp}.zip";
        $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("No se pudo crear el archivo ZIP en: {$zipPath}");
        }

        $files = File::allFiles($mediaDir);
        if (empty($files)) {
            $zip->addFromString('uploads/.gitkeep', '');
        } else {
            foreach ($files as $file) {
                $relativePath = 'uploads/' . $file->getRelativePathname();
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }

        $zip->close();
        clearstatcache(true, $zipPath);

        $size = File::size($zipPath);

        return [
            'filename' => $filename,
            'path' => $zipPath,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'type' => 'zip',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Listar todos los respaldos existentes
     */
    public function listBackups(): array
    {
        if (!File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            try {
                if (!file_exists($file->getPathname())) {
                    continue;
                }
                $size = @$file->getSize() ?: 0;
                $mtime = @$file->getMTime() ?: time();
            } catch (\Throwable) {
                continue;
            }

            $name = $file->getFilename();
            $ext = strtolower($file->getExtension());
            $isGz = str_ends_with($name, '.sql.gz');
            
            $type = 'unknown';
            if ($isGz) {
                $type = 'database_compressed';
            } elseif ($ext === 'sql') {
                $type = 'database_plain';
            } elseif ($ext === 'zip') {
                $type = 'media_zip';
            }

            $backups[] = [
                'filename' => $name,
                'path' => $file->getRealPath() ?: $file->getPathname(),
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'type' => $type,
                'is_compressed' => $isGz,
                'created_at' => date('Y-m-d H:i:s', $mtime),
                'timestamp' => $mtime,
            ];
        }

        // Ordenar por fecha descendente (el más reciente primero)
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Eliminar un archivo de respaldo con prevención estricta de Path Traversal
     */
    public function deleteBackup(string $filename): bool
    {
        $sanitized = basename($filename);
        if (str_contains($filename, '..') || $sanitized !== $filename) {
            throw new Exception("Nombre de archivo inválido.");
        }

        $path = $this->backupDir . DIRECTORY_SEPARATOR . $sanitized;
        if (File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    /**
     * Formatear bytes a unidad legible
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Purga los respaldos más antiguos que excedan el límite de retención especificado.
     * Retorna la cantidad de archivos eliminados.
     */
    public function pruneBackups(int $maxRetention = 7): int
    {
        if ($maxRetention <= 0) {
            return 0;
        }

        $allBackups = $this->listBackups();
        if (count($allBackups) <= $maxRetention) {
            return 0;
        }

        $backupsToDelete = array_slice($allBackups, $maxRetention);
        $deletedCount = 0;

        foreach ($backupsToDelete as $backup) {
            if ($this->deleteBackup($backup['filename'])) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Restaurar la base de datos a partir de un archivo de respaldo generado por Dkript Core.
     * Soportado exclusivamente para MySQL y SQLite con archivos .sql y .sql.gz.
     *
     * @param string $filename
     * @return array Metadata del resultado de la restauración
     * @throws Exception
     */
    public function restoreDatabase(string $filename): array
    {
        // 1. Sanitización estricta y prevención de Path Traversal
        $sanitized = basename($filename);
        if (str_contains($filename, '..') || $sanitized !== $filename) {
            throw new Exception("Nombre de archivo inválido o intento de navegación no permitido.", 403);
        }

        // 2. Validación de patrón y extensión de archivo (Solo backups de DB generados por Core)
        $isGz = str_ends_with($sanitized, '.sql.gz');
        $isSql = str_ends_with($sanitized, '.sql');
        if ((!$isGz && !$isSql) || !str_starts_with($sanitized, 'backup_db_')) {
            throw new Exception("El archivo seleccionado no es un respaldo de base de datos válido de Dkript Core (.sql o .sql.gz).", 422);
        }

        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $sanitized;
        if (!File::exists($filePath) || !is_file($filePath)) {
            throw new Exception("El archivo de respaldo '{$sanitized}' no existe en el almacenamiento del sistema.", 404);
        }

        // 3. Validación de Driver de Base de Datos
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if ($driver !== 'mysql' && $driver !== 'sqlite') {
            throw new Exception("El motor de base de datos '{$driver}' no está soportado para restauración. Solo se admite MySQL y SQLite.", 422);
        }

        // 4. Verificación de Cabecera del Archivo para compatibilidad de Driver
        $headerLine = $this->readFirstLines($filePath, $isGz, 15);
        if (preg_match('/-- Motor de Base de Datos:\s*([a-zA-Z0-9_]+)/i', $headerLine, $matches)) {
            $dumpDriver = strtolower(trim($matches[1]));
            if ($dumpDriver !== $driver) {
                throw new Exception("Incompatibilidad de motor: El respaldo fue generado para [{$dumpDriver}], pero la base de datos actual utiliza [{$driver}].", 422);
            }
        }

        // 5. SAFETY BACKUP OBLIGATORIO PREVIO A CUALQUIER ALTERACIÓN
        try {
            $safetyBackup = $this->generateDatabaseBackup(true, 'backup_db_safety_');
            if (empty($safetyBackup['filename']) || !File::exists($safetyBackup['path'])) {
                throw new Exception("No se pudo generar el Safety Backup preventivo. La restauración ha sido cancelada por seguridad.", 500);
            }
        } catch (\Throwable $safetyEx) {
            throw new Exception("No se pudo generar el Safety Backup preventivo. La restauración ha sido cancelada por seguridad: " . $safetyEx->getMessage(), 500);
        }

        // 6. Maintenance Guard (Bloqueo temporal de concurrencia durante la restauración)
        $lockFile = storage_path('framework/dkript_restore.lock');
        $statementsExecuted = 0;
        $handle = null;
        $pdo = null;

        try {
            touch($lockFile);

            // 7. Apertura de Stream (Streaming seguro para memoria)
            $handle = $isGz ? gzopen($filePath, 'rb') : fopen($filePath, 'r');
            if (!$handle) {
                throw new Exception("No se pudo abrir el archivo de respaldo para lectura.", 500);
            }

            $pdo = $connection->getPdo();

            // Desactivar temporalmente revisión de llaves foráneas según driver
            if ($driver === 'mysql') {
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
                $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
            } elseif ($driver === 'sqlite') {
                $pdo->exec("PRAGMA foreign_keys = OFF;");
            }

            $statementBuffer = '';

            while (!($isGz ? gzeof($handle) : feof($handle))) {
                $line = $isGz ? gzgets($handle, 1048576) : fgets($handle, 1048576);
                if ($line === false) break;

                $trimmed = trim($line);
                // Omitir líneas vacías y comentarios de cabecera
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                    continue;
                }

                $statementBuffer .= $line;

                // El contrato de generateDatabaseBackup() garantiza que cada sentencia culmina en ';' al final de línea
                if (str_ends_with($trimmed, ';')) {
                    $pdo->exec($statementBuffer);
                    $statementsExecuted++;
                    $statementBuffer = '';
                }
            }

            // Procesar residuo si existiese
            $finalBuffer = trim($statementBuffer);
            if ($finalBuffer !== '' && str_ends_with($finalBuffer, ';')) {
                $pdo->exec($finalBuffer);
                $statementsExecuted++;
            }

        } finally {
            // Cleanup garantizado:
            // 1. Cerrar stream abierto si continúa abierto
            if ($handle) {
                try {
                    if ($isGz) {
                        gzclose($handle);
                    } else {
                        fclose($handle);
                    }
                } catch (\Throwable $closeEx) {
                    Log::warning("RESTORE_CLEANUP_STREAM_WARNING: No se pudo cerrar el stream de respaldo: " . $closeEx->getMessage());
                }
            }

            // 2. Intentar restaurar estado de Foreign Key Checks
            if ($pdo) {
                try {
                    if ($driver === 'mysql') {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
                    } elseif ($driver === 'sqlite') {
                        $pdo->exec("PRAGMA foreign_keys = ON;");
                    }
                } catch (\Throwable $fkEx) {
                    Log::error("RESTORE_CLEANUP_FK_ERROR: Fallo al restaurar Foreign Keys tras restore: " . $fkEx->getMessage());
                }
            }

            // 3. Eliminar restore lock temporal al salir
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        }

        return [
            'filename' => $sanitized,
            'safety_backup' => $safetyBackup['filename'],
            'safety_backup_path' => $safetyBackup['path'],
            'statements_executed' => $statementsExecuted,
            'driver' => $driver,
            'restored_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Lee las primeras N líneas de un archivo plano o Gzip para inspección de cabeceras
     */
    protected function readFirstLines(string $filePath, bool $isGz, int $maxLines = 15): string
    {
        $handle = $isGz ? @gzopen($filePath, 'rb') : @fopen($filePath, 'r');
        if (!$handle) return '';

        $content = '';
        $lineCount = 0;
        while (!($isGz ? gzeof($handle) : feof($handle)) && $lineCount < $maxLines) {
            $line = $isGz ? gzgets($handle, 4096) : fgets($handle, 4096);
            if ($line === false) break;
            $content .= $line;
            $lineCount++;
        }

        if ($isGz) {
            gzclose($handle);
        } else {
            fclose($handle);
        }

        return $content;
    }
}
