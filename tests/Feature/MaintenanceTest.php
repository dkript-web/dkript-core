<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
        $this->restrictedUser = User::where('email', 'demo@dkript.com')->first();
    }

    public function test_guest_cannot_clear_cache()
    {
        $response = $this->post(route('parameters.clear-cache'));
        $response->assertRedirect(route('login'));

        $jsonResponse = $this->postJson(route('parameters.clear-cache'));
        $jsonResponse->assertStatus(401);
    }

    public function test_restricted_user_cannot_clear_cache()
    {
        $response = $this->actingAs($this->restrictedUser)->post(route('parameters.clear-cache'));
        $response->assertRedirect(route('dashboard'));

        $jsonResponse = $this->actingAs($this->restrictedUser)->postJson(route('parameters.clear-cache'));
        $jsonResponse->assertStatus(403);
    }

    public function test_super_admin_can_clear_cache_via_json()
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('parameters.clear-cache'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertStringContainsString('Caché del sistema depurada exitosamente', $response->json('message'));
    }

    public function test_super_admin_can_clear_cache_via_web_redirect()
    {
        $response = $this->actingAs($this->superAdmin)->post(route('parameters.clear-cache'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_clear_cache_generates_audit_log()
    {
        $initialCount = AuditLog::count();

        $this->actingAs($this->superAdmin)->postJson(route('parameters.clear-cache'));

        $this->assertGreaterThan($initialCount, AuditLog::count());

        $latestLog = AuditLog::latest('id')->first();
        $this->assertEquals('SETTINGS', $latestLog->action);
        $this->assertEquals('SYSTEM', $latestLog->module);
        $this->assertStringContainsString('Limpieza de caché general', $latestLog->description);
    }

    public function test_parameters_page_displays_maintenance_section_and_backup_link()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('parameters.index'));

        $response->assertStatus(200);
        $response->assertSee('Mantenimiento, Caché y Respaldos');
        $response->assertSee('Depuración de Memoria Caché');
        $response->assertSee('Depurar Memoria Caché General');
        $response->assertSee(route('backups.index'));
    }
}
