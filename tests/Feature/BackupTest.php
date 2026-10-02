<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedUser;
    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->backupService = app(BackupService::class);
        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
        $this->restrictedUser = User::where('email', 'demo@dkript.com')->first();
    }

    public function test_guest_cannot_access_backups()
    {
        $response = $this->get(route('backups.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_backups()
    {
        // Petición Web regular es redirigida al dashboard
        $response = $this->actingAs($this->restrictedUser)->get(route('backups.index'));
        $response->assertRedirect(route('dashboard'));

        // Petición AJAX/JSON es rechazada con código 403 Forbidden
        $jsonResponse = $this->actingAs($this->restrictedUser)->getJson(route('backups.index'));
        $jsonResponse->assertStatus(403);
    }

    public function test_super_admin_can_view_backups_list()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('backups.index'));
        $response->assertStatus(200);
        $response->assertSee('Centro de Respaldos Corporativos');
    }

    public function test_super_admin_can_generate_compressed_database_backup()
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('backups.database'), [
            'compress' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'compressed' => true,
                'type' => 'sql.gz',
            ],
        ]);

        $filename = $response->json('data.filename');
        $this->assertStringEndsWith('.sql.gz', $filename);

        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . $filename;
        $this->assertTrue(File::exists($path));
        $this->assertGreaterThan(0, File::size($path));

        // Limpiar archivo de prueba
        $this->backupService->deleteBackup($filename);
    }

    public function test_super_admin_can_generate_plain_sql_backup()
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('backups.database'), [
            'compress' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'compressed' => false,
                'type' => 'sql',
            ],
        ]);

        $filename = $response->json('data.filename');
        $this->assertStringEndsWith('.sql', $filename);

        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . $filename;
        $this->assertTrue(File::exists($path));
        $content = File::get($path);
        $this->assertStringContainsString('Dkript Admin Enterprise Database Backup', $content);

        // Limpiar archivo de prueba
        $this->backupService->deleteBackup($filename);
    }

    public function test_super_admin_can_generate_media_backup()
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('backups.media'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'type' => 'zip',
            ],
        ]);

        $filename = $response->json('data.filename');
        $this->assertStringEndsWith('.zip', $filename);

        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . $filename;
        $this->assertTrue(File::exists($path));

        // Limpiar archivo de prueba
        $this->backupService->deleteBackup($filename);
    }

    public function test_path_traversal_in_download_and_delete_is_blocked()
    {
        // Descarga maliciosa con secuencias ..
        $response = $this->actingAs($this->superAdmin)->get(route('backups.download', ['filename' => '..%2F..%2F.env']));
        $this->assertEquals(403, $response->getStatusCode());

        // Eliminación maliciosa
        $delResponse = $this->actingAs($this->superAdmin)->delete(route('backups.destroy', ['filename' => '../../.env']));
        $this->assertEquals(403, $delResponse->getStatusCode());
    }

    public function test_super_admin_can_download_and_delete_backup()
    {
        // Generar respaldo primero
        $backup = $this->backupService->generateDatabaseBackup(false);
        $filename = $backup['filename'];

        // Descargar
        $downloadResponse = $this->actingAs($this->superAdmin)->get(route('backups.download', ['filename' => $filename]));
        $downloadResponse->assertStatus(200);

        // Eliminar
        $deleteResponse = $this->actingAs($this->superAdmin)->deleteJson(route('backups.destroy', ['filename' => $filename]));
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['status' => 'success']);

        $path = $this->backupService->getBackupDir() . DIRECTORY_SEPARATOR . $filename;
        $this->assertFalse(File::exists($path));
    }
}
