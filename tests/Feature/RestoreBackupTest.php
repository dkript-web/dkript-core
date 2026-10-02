<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RestoreBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedUser;
    protected BackupService $backupService;
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // O. Blindaje estricto de Base de Datos Real: Comprobar que no es dkript_admin_v5
        $currentDb = config('database.connections.' . config('database.default') . '.database');
        $this->assertNotEquals(
            'dkript_admin_v5', 
            $currentDb, 
            'ALERTA CRÍTICA: Los tests de restauración no deben ejecutarse contra la base de datos de producción o desarrollo dkript_admin_v5.'
        );

        $this->seed();
        $this->seed(DemoSeeder::class);

        $this->backupService = app(BackupService::class);
        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
        $this->restrictedUser = User::where('email', 'demo@dkript.com')->first();

        // Limpiar directorio de backups antes de cada prueba
        $backupDir = $this->backupService->getBackupDir();
        if (is_dir($backupDir)) {
            $files = @scandir($backupDir);
            if ($files) {
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..' && $f !== '.gitignore') {
                        @unlink($backupDir . DIRECTORY_SEPARATOR . $f);
                    }
                }
            }
        }
    }

    protected function tearDown(): void
    {
        try {
            // Limpiar cualquier archivo creado en storage/app/backups durante los tests
            foreach ($this->createdFiles as $filename) {
                try {
                    $this->backupService->deleteBackup($filename);
                } catch (\Throwable $e) {}
            }

            // Limpiar archivos temporales residuales en storage/app/backups de forma segura
            $backupDir = $this->backupService->getBackupDir();
            if (is_dir($backupDir)) {
                $files = @scandir($backupDir);
                if ($files) {
                    foreach ($files as $f) {
                        if ($f !== '.' && $f !== '..' && $f !== '.gitignore') {
                            @unlink($backupDir . DIRECTORY_SEPARATOR . $f);
                        }
                    }
                }
            }

            // Limpiar lock si existiese
            $lockFile = storage_path('framework/dkript_restore.lock');
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        } catch (\Throwable $e) {}

        // Asegurar que ninguna transacción de base de datos quede abierta en SQLite
        while (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            \Illuminate\Support\Facades\DB::rollBack();
        }

        parent::tearDown();
    }

    /**
     * A. guest -> Prohibido (redirige a login).
     */
    public function test_guest_cannot_restore_database()
    {
        $response = $this->post(route('backups.restore', ['filename' => 'backup_db_test.sql']));
        $response->assertRedirect(route('login'));

        $jsonResponse = $this->postJson(route('backups.restore', ['filename' => 'backup_db_test.sql']));
        $jsonResponse->assertStatus(401);
    }

    /**
     * B. usuario sin permiso -> Prohibido (403 Forbidden).
     */
    public function test_user_without_permission_cannot_restore_database()
    {
        $response = $this->actingAs($this->restrictedUser)
            ->post(route('backups.restore', ['filename' => 'backup_db_test.sql']));
        $response->assertRedirect(route('dashboard'));

        $jsonResponse = $this->actingAs($this->restrictedUser)
            ->postJson(route('backups.restore', ['filename' => 'backup_db_test.sql']));
        $jsonResponse->assertStatus(403);
    }

    /**
     * C. permiso correcto (SuperAdmin) -> puede solicitar restore.
     */
    public function test_super_admin_can_request_database_restore()
    {
        // Generar un respaldo real en el entorno SQLite de pruebas
        $backup = $this->backupService->generateDatabaseBackup(false);
        $this->createdFiles[] = $backup['filename'];

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => $backup['filename']]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $this->assertNotEmpty($response->json('data.safety_backup'));
    }

    /**
     * D. path traversal -> Rechazado con 403 Forbidden.
     */
    public function test_path_traversal_in_restore_is_blocked()
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.restore', ['filename' => '..%2F..%2F.env']));
        $this->assertEquals(403, $response->getStatusCode());

        $responseJson = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => '../../.env']));
        $this->assertEquals(403, $responseJson->getStatusCode());
    }

    /**
     * E. archivo inexistente -> Rechazado con 404 Not Found.
     */
    public function test_non_existent_file_is_rejected()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => 'backup_db_inexistente_2026.sql']));

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('no existe', $response->json('message'));
    }

    /**
     * F. media zip -> No restaurable (rechazado con 422 Unprocessable Entity).
     */
    public function test_media_zip_backup_is_not_restorable()
    {
        $mediaBackup = $this->backupService->generateMediaBackup();
        $this->createdFiles[] = $mediaBackup['filename'];

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => $mediaBackup['filename']]));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('no es un respaldo de base de datos válido', $response->json('message'));
    }

    /**
     * G. unknown file -> No restaurable (rechazado con 422 Unprocessable Entity).
     */
    public function test_unknown_file_type_is_not_restorable()
    {
        $dummyPath = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . 'malicious_script.php';
        File::put($dummyPath, '<?php echo "test";');
        $this->createdFiles[] = 'malicious_script.php';

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => 'malicious_script.php']));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('no es un respaldo de base de datos válido', $response->json('message'));
    }

    /**
     * G2. Incompatibilidad de Driver -> Rechazado con 422 Unprocessable Entity.
     */
    public function test_driver_mismatch_is_rejected_with_422()
    {
        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . 'backup_db_mysql_dump.sql';
        File::put($path, "-- Dkript Admin Enterprise Database Backup\n-- Motor de Base de Datos: mysql\nSELECT 1;\n");
        $this->createdFiles[] = 'backup_db_mysql_dump.sql';

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => 'backup_db_mysql_dump.sql']));

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertStringContainsString('Incompatibilidad de motor', $response->json('message'));
    }

    /**
     * H. .sql válido -> Aceptado y restaurado en entorno aislado.
     */
    public function test_valid_plain_sql_backup_is_restored_successfully()
    {
        $backup = $this->backupService->generateDatabaseBackup(false);
        $this->createdFiles[] = $backup['filename'];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('backups.restore', ['filename' => $backup['filename']]));

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');
    }

    /**
     * I. .sql.gz válido -> Aceptado y descomprimido vía streaming.
     */
    public function test_valid_gzip_sql_backup_is_restored_successfully()
    {
        $backup = $this->backupService->generateDatabaseBackup(true);
        $this->createdFiles[] = $backup['filename'];

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => $backup['filename']]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $this->assertGreaterThan(0, $response->json('data.statements_executed'));
    }

    /**
     * J. Safety Backup se genera automáticamente antes de ejecutar el restore.
     */
    public function test_safety_backup_is_generated_before_restore()
    {
        $initialBackups = count($this->backupService->listBackups());

        $backup = $this->backupService->generateDatabaseBackup(true);
        $this->createdFiles[] = $backup['filename'];

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => $backup['filename']]));

        $response->assertStatus(200);

        $safetyBackupName = $response->json('data.safety_backup');
        $this->assertNotEmpty($safetyBackupName);
        $this->assertStringEndsWith('.sql.gz', $safetyBackupName);

        $safetyPath = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . $safetyBackupName;
        $this->assertTrue(File::exists($safetyPath));
    }

    /**
     * K. Fallo en Safety Backup aborta el restore inmediatamente con 500 y mensaje seguro.
     */
    public function test_failure_in_safety_backup_aborts_restore()
    {
        $backup = $this->backupService->generateDatabaseBackup(false);
        $this->createdFiles[] = $backup['filename'];

        // Simular que la generación de safety backup arroja excepción
        $dummyBackupService = $this->getMockBuilder(BackupService::class)
            ->onlyMethods(['generateDatabaseBackup'])
            ->getMock();

        $dummyBackupService->expects($this->once())
            ->method('generateDatabaseBackup')
            ->willThrowException(new \Exception("Error simulado de disco en safety backup"));

        $this->app->instance(BackupService::class, $dummyBackupService);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => $backup['filename']]));

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
            'message' => 'No se pudo generar el respaldo de seguridad preventivo (Safety Backup). La restauración ha sido cancelada por seguridad.',
        ]);
    }

    /**
     * L. Fallo en restauración es capturado y reportado sin éxito falso y con mensaje seguro.
     */
    public function test_restore_failure_is_captured_and_reported()
    {
        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . 'backup_db_corrupt.sql';
        // Generar archivo con sintaxis SQL inválida que provocará error en PDO exec
        File::put($path, "-- Dkript Admin Enterprise Database Backup\n-- Motor de Base de Datos: sqlite\nSYNTAX ERROR ILLEGAL STATEMENT;\n");
        $this->createdFiles[] = 'backup_db_corrupt.sql';

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => 'backup_db_corrupt.sql']));

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Ocurrió un error interno al ejecutar la restauración en la base de datos. La operación fue detenida para proteger la integridad del sistema.',
        ]);
        $this->assertFalse(File::exists(storage_path('framework/dkript_restore.lock')));
    }

    /**
     * L2. Regresión Obligatoria: Tras fallo en restauración, lock desaparece y Foreign Keys quedan restauradas.
     */
    public function test_foreign_keys_are_restored_after_failed_restore_and_stream_closed()
    {
        // 1. Asegurar estado inicial de FK en ON
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON;');
        $initialFk = \Illuminate\Support\Facades\DB::select('PRAGMA foreign_keys;');
        $this->assertEquals(1, array_values((array)$initialFk[0])[0]);

        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . 'backup_db_broken_fk.sql';
        File::put($path, "-- Dkript Admin Enterprise Database Backup\n-- Motor de Base de Datos: sqlite\nSYNTAX ERROR FAILING QUERY;\n");
        $this->createdFiles[] = 'backup_db_broken_fk.sql';

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => 'backup_db_broken_fk.sql']));

        $response->assertStatus(500);

        // 2. Comprobar que el lock file fue eliminado en finally
        $this->assertFalse(File::exists(storage_path('framework/dkript_restore.lock')));

        // 3. Comprobar que Foreign Keys quedaron habilitadas en 1 tras el fallo
        $afterFk = \Illuminate\Support\Facades\DB::select('PRAGMA foreign_keys;');
        $this->assertEquals(1, array_values((array)$afterFk[0])[0], 'ALERTA: PRAGMA foreign_keys debe restaurarse a 1 (ON) incluso si el restore falla.');

        // 4. Comprobar que el archivo se puede eliminar sin bloqueo de handle (stream cerrado)
        $this->assertTrue(File::delete($path));
    }

    /**
     * M. Audit Log registrado exitosamente al restaurar.
     */
    public function test_restore_generates_audit_logs()
    {
        $initialCount = AuditLog::count();

        $backup = $this->backupService->generateDatabaseBackup(false);
        $this->createdFiles[] = $backup['filename'];

        $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => $backup['filename']]));

        $this->assertGreaterThan($initialCount, AuditLog::count());

        $restoreLog = AuditLog::where('action', 'RESTORE')
            ->where('module', 'BACKUPS')
            ->latest('id')
            ->first();

        $this->assertNotNull($restoreLog);
        $this->assertStringContainsString('completada exitosamente', $restoreLog->description);
    }

    /**
     * N. Audit Log registrado en caso de fallo de restauración sin exponer SQL sensible.
     */
    public function test_restore_failure_generates_audit_log()
    {
        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . 'backup_db_broken.sql';
        File::put($path, "-- Dkript Admin Enterprise Database Backup\n-- Motor de Base de Datos: sqlite\nINVALID SQL COMM;\n");
        $this->createdFiles[] = 'backup_db_broken.sql';

        $this->actingAs($this->superAdmin)
            ->postJson(route('backups.restore', ['filename' => 'backup_db_broken.sql']));

        $failedLog = AuditLog::where('action', 'RESTORE')
            ->where('module', 'BACKUPS')
            ->where('description', 'like', '%Fallo%')
            ->latest('id')
            ->first();

        $this->assertNotNull($failedLog);
        $this->assertStringNotContainsString('INVALID SQL COMM', $failedLog->description);
    }

    /**
     * O. Protección de base de datos real dkript_admin_v5.
     */
    public function test_real_database_is_strictly_protected()
    {
        $defaultConnection = config('database.default');
        $databaseName = config("database.connections.{$defaultConnection}.database");

        $this->assertNotEquals('dkript_admin_v5', $databaseName);
        $this->assertEquals(':memory:', $databaseName);
    }

    /**
     * P. UI incluye botón de restaurar y modal de confirmación con CSRF.
     */
    public function test_ui_includes_restore_button_for_db_backups_and_modal()
    {
        $backup = $this->backupService->generateDatabaseBackup(true);
        $this->createdFiles[] = $backup['filename'];

        $response = $this->actingAs($this->superAdmin)->get(route('backups.index'));
        $response->assertStatus(200);
        $response->assertSee('openRestoreModal');
        $response->assertSee('modalRestoreBackup');
        $response->assertSee('formRestoreBackup');
        $response->assertSee('Esta operación reemplazará el estado actual de la base de datos');
    }
}

