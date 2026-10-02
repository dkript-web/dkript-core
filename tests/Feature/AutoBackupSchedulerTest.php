<?php

namespace Tests\Feature;


use App\Models\Parameter;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AutoBackupSchedulerTest extends TestCase
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

    public function test_guest_cannot_access_scheduler_or_update_settings()
    {
        $this->getJson(route('backups.scheduler.status'))->assertStatus(401);
        $this->postJson(route('backups.auto-settings'), [])->assertStatus(401);
        $this->postJson(route('backups.run-auto'))->assertStatus(401);
    }

    public function test_user_without_permission_cannot_access_scheduler_or_settings()
    {
        $this->actingAs($this->restrictedUser)
            ->getJson(route('backups.scheduler.status'))
            ->assertStatus(403);

        $this->actingAs($this->restrictedUser)
            ->postJson(route('backups.auto-settings'), [
                'auto_backup_enabled' => 1,
                'auto_backup_frequency' => 'daily',
                'auto_backup_time' => '03:00',
                'auto_backup_type' => 'database',
                'auto_backup_max_retention' => 5,
            ])
            ->assertStatus(403);

        $this->actingAs($this->restrictedUser)
            ->postJson(route('backups.run-auto'))
            ->assertStatus(403);
    }

    public function test_super_admin_can_get_scheduler_status()
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('backups.scheduler.status'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'server_time',
            'settings' => [
                'auto_backup_enabled',
                'auto_backup_frequency',
                'auto_backup_time',
                'auto_backup_type',
                'auto_backup_max_retention',
                'auto_backup_last_run_at',
            ],
            'events',
            'cron_command',
        ]);

        $this->assertEquals('success', $response->json('status'));
        $events = $response->json('events');
        $this->assertIsArray($events);

        $commands = array_column($events, 'command');
        $hasBackupCommand = false;
        foreach ($commands as $cmd) {
            if (str_contains($cmd, 'dkript:auto-backup')) {
                $hasBackupCommand = true;
                break;
            }
        }
        $this->assertTrue($hasBackupCommand, 'El comando dkript:auto-backup debe figurar en la lista del scheduler.');
    }

    public function test_super_admin_can_update_auto_backup_settings()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.auto-settings'), [
                'auto_backup_enabled' => 1,
                'auto_backup_frequency' => 'weekly',
                'auto_backup_time' => '04:30',
                'auto_backup_type' => 'all',
                'auto_backup_max_retention' => 15,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'settings' => [
                'auto_backup_enabled' => true,
                'auto_backup_frequency' => 'weekly',
                'auto_backup_time' => '04:30',
                'auto_backup_type' => 'all',
                'auto_backup_max_retention' => 15,
            ]
        ]);

        $this->assertDatabaseHas('parameters', [
            'auto_backup_enabled' => 1,
            'auto_backup_frequency' => 'weekly',
            'auto_backup_time' => '04:30',
            'auto_backup_type' => 'all',
            'auto_backup_max_retention' => 15,
        ]);
    }

    public function test_auto_backup_settings_validation_errors()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.auto-settings'), [
                'auto_backup_enabled' => 'invalid_bool',
                'auto_backup_frequency' => 'yearly',
                'auto_backup_time' => '25:99',
                'auto_backup_type' => 'none',
                'auto_backup_max_retention' => 500,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'auto_backup_enabled',
            'auto_backup_frequency',
            'auto_backup_time',
            'auto_backup_type',
            'auto_backup_max_retention',
        ]);
    }

    public function test_auto_backup_command_skips_when_disabled_without_force()
    {
        $parameter = Parameter::first();
        if ($parameter) {
            $parameter->update(['auto_backup_enabled' => false]);
        }

        $exitCode = Artisan::call('dkript:auto-backup');
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('DESACTIVADOS', $output);
    }

    public function test_auto_backup_command_runs_with_force_and_prunes()
    {
        $parameter = Parameter::first();
        if ($parameter) {
            $parameter->update([
                'auto_backup_enabled' => false,
                'auto_backup_type' => 'database',
                'auto_backup_max_retention' => 5,
            ]);
        }

        $exitCode = Artisan::call('dkript:auto-backup', ['--force' => true]);
        $this->assertEquals(0, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('completado', $output);

        $parameter->refresh();
        $this->assertNotNull($parameter->auto_backup_last_run_at);

        $backups = $this->backupService->listBackups();
        foreach ($backups as $b) {
            $this->backupService->deleteBackup($b['filename']);
        }
    }

    public function test_super_admin_can_trigger_run_auto_backup_now_endpoint()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('backups.run-auto'));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $this->assertNotNull($response->json('output'));
        $this->assertNotNull($response->json('last_run_at'));

        $backups = $this->backupService->listBackups();
        foreach ($backups as $b) {
            $this->backupService->deleteBackup($b['filename']);
        }
    }
}
