<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
        $this->operatorUser = User::where('email', 'demo@dkript.com')->first();
    }

    public function test_guest_cannot_access_audit_logs(): void
    {
        $response = $this->get(route('audit-logs.index'));
        $response->assertRedirect(route('login'));

        $jsonResponse = $this->getJson(route('audit-logs.index'));
        $jsonResponse->assertStatus(401);
    }

    public function test_user_without_module_permission_cannot_access_audit_logs(): void
    {
        // Usuario operador sin módulo 6
        $response = $this->actingAs($this->operatorUser)->get(route('audit-logs.index'));
        $response->assertRedirect(route('dashboard'));

        $jsonResponse = $this->actingAs($this->operatorUser)->getJson(route('audit-logs.index'));
        $jsonResponse->assertStatus(403);
    }

    public function test_super_admin_can_view_audit_logs_index(): void
    {
        AuditService::log('LOGIN', 'AUTH', 'Acceso de prueba');

        $response = $this->actingAs($this->superAdmin)->get(route('audit-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Bitácora de Eventos y Auditoría');
        $response->assertSee('Acceso de prueba');
    }

    public function test_super_admin_can_filter_audit_logs_by_module_and_action(): void
    {
        AuditService::log('LOGIN', 'AUTH', 'Evento login 1');
        AuditService::log('CREATE', 'USERS', 'Usuario creado de prueba');
        AuditService::log('DELETE', 'BACKUPS', 'Respaldo eliminado de prueba');

        // Filtrar por Módulo USERS
        $response = $this->actingAs($this->superAdmin)->get(route('audit-logs.index', ['module' => 'USERS']));
        $response->assertStatus(200);
        $response->assertSee('Usuario creado de prueba');
        $response->assertDontSee('Evento login 1');

        // Filtrar por Acción DELETE
        $responseAction = $this->actingAs($this->superAdmin)->get(route('audit-logs.index', ['action' => 'DELETE']));
        $responseAction->assertStatus(200);
        $responseAction->assertSee('Respaldo eliminado de prueba');
        $responseAction->assertDontSee('Usuario creado de prueba');
    }

    public function test_super_admin_can_view_audit_log_json_detail(): void
    {
        $log = AuditService::log('UPDATE', 'PARAMETERS', 'Ajuste de parámetros', ['theme' => 'light'], ['theme' => 'dark']);
        $this->assertNotNull($log);

        $response = $this->actingAs($this->superAdmin)->getJson(route('audit-logs.show', $log->id));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'log' => [
                'id' => $log->id,
                'action' => 'UPDATE',
                'module' => 'PARAMETERS',
                'description' => 'Ajuste de parámetros',
                'old_values' => ['theme' => 'light'],
                'new_values' => ['theme' => 'dark'],
            ],
        ]);
    }

    public function test_super_admin_can_export_audit_logs_to_excel(): void
    {
        AuditService::log('CREATE', 'ROLES', 'Rol auditor creado');

        $response = $this->actingAs($this->superAdmin)->get(route('audit-logs.export.excel'));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'auditoria_'));
    }

    public function test_super_admin_can_export_audit_logs_to_pdf(): void
    {
        AuditService::log('SECURITY', 'AUTH', 'Bloqueo temporal de IP');

        $response = $this->actingAs($this->superAdmin)->get(route('audit-logs.export.pdf'));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'application/pdf'));
    }

    public function test_super_admin_can_delete_audit_log(): void
    {
        $log = AuditService::log('LOGIN', 'AUTH', 'Evento efímero');

        $response = $this->actingAs($this->superAdmin)->deleteJson(route('audit-logs.destroy', $log->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('audit_logs', ['id' => $log->id]);
    }

    public function test_super_admin_can_purge_audit_logs(): void
    {
        AuditService::log('LOGIN', 'AUTH', 'Log A');
        AuditService::log('LOGOUT', 'AUTH', 'Log B');

        $this->assertGreaterThanOrEqual(2, AuditLog::count());

        $response = $this->actingAs($this->superAdmin)->delete(route('audit-logs.clear'), [
            'days' => 'all',
        ]);

        $response->assertSessionHas('success');

        // La purga crea 1 nuevo registro de auditoría indicando que se truncó
        $this->assertEquals(1, AuditLog::count());
        $this->assertEquals('DELETE', AuditLog::first()->action);
        $this->assertEquals('AUDIT', AuditLog::first()->module);
    }

    public function test_audit_service_captures_actor_and_ip_automatically(): void
    {
        $log = AuditService::log(
            'SETTINGS',
            'SYSTEM',
            'Prueba de servicio directo',
            null,
            null,
            $this->superAdmin
        );

        $this->assertNotNull($log);
        $this->assertEquals($this->superAdmin->id, $log->user_id);
        $this->assertEquals($this->superAdmin->name, $log->user_name);
        $this->assertEquals($this->superAdmin->email, $log->user_email);
        $this->assertEquals('SETTINGS', $log->action);
        $this->assertEquals('SYSTEM', $log->module);
    }
}
