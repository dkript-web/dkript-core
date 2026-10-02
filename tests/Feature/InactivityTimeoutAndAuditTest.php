<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Parameter;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactivityTimeoutAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
    }

    public function test_inactivity_timeout_allows_request_when_within_timeout(): void
    {
        Parameter::first()->update(['session_timeout_minutes' => 15]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - 300, // 5 minutos atrás (dentro de los 15)
            ])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($this->superAdmin);
        $this->assertGreaterThanOrEqual(time() - 2, session('last_activity_time'));
    }

    public function test_inactivity_timeout_logs_out_user_and_creates_audit_log_when_exceeded(): void
    {
        Parameter::first()->update(['session_timeout_minutes' => 15]);

        $initialAuditCount = AuditLog::count();

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - 1000, // 16.6 minutos atrás (excedió los 15 min = 900s)
            ])
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('warning', 'Tu sesión ha expirado por inactividad.');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGOUT',
            'module' => 'AUTH',
            'user_email' => 'admin@dkript.com',
        ]);
        $this->assertEquals($initialAuditCount + 1, AuditLog::count());
    }

    public function test_inactivity_timeout_returns_401_json_on_ajax_when_exceeded(): void
    {
        Parameter::first()->update(['session_timeout_minutes' => 15]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - 1000,
            ])
            ->getJson(route('dashboard'));

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Tu sesión ha expirado por inactividad.',
            'redirect' => route('login'),
        ]);
        $this->assertGuest();
    }

    public function test_session_ping_endpoint_refreshes_activity(): void
    {
        Parameter::first()->update(['session_timeout_minutes' => 20]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - 600,
            ])
            ->postJson(route('session.ping'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'timeout_minutes' => 20,
        ]);
        $this->assertGreaterThanOrEqual(time() - 2, session('last_activity_time'));
    }

    public function test_parameter_test_mail_creates_audit_log(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
            ])
            ->postJson(route('parameters.test-smtp'), [
                'test_email' => 'test_audit@dkript.com',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SETTINGS',
            'module' => 'SYSTEM',
        ]);

        $log = AuditLog::where('description', 'like', "%test_audit@dkript.com%")->first();
        $this->assertNotNull($log);
        $this->assertEquals('SETTINGS', $log->action);
        $this->assertEquals('SYSTEM', $log->module);
    }

    public function test_parameter_update_creates_audit_log_and_saves_session_timeout(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
            ])
            ->post(route('parameters.update'), [
                'system_name' => 'Dkript Enterprise QA Pro',
                'records_per_page' => 25,
                'session_timeout_minutes' => 30,
                'maintenance_mode' => 0,
                'modal_style' => 'window',
                'error_display_mode' => 'scene',
            ]);

        $response->assertRedirect(route('parameters.index'));

        $param = Parameter::first();
        $this->assertEquals('Dkript Enterprise QA Pro', $param->system_name);
        $this->assertEquals(30, $param->session_timeout_minutes);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SETTINGS',
            'module' => 'SYSTEM',
            'description' => 'Actualización de configuración institucional del sistema',
        ]);
    }

    public function test_role_crud_creates_audit_logs(): void
    {
        // 1. Crear Rol
        $createResponse = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
            ])
            ->post(route('roles.store'), [
                'name' => 'Rol Auditor Externo',
                'description' => 'Rol de prueba para auditoría',
                'status' => 1,
                'modules' => [1, 2],
            ]);

        $createResponse->assertRedirect(route('roles.index'));
        $role = Role::where('name', 'Rol Auditor Externo')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE',
            'module' => 'ROLES',
            'description' => "Creación de rol 'Rol Auditor Externo'",
        ]);

        // 2. Editar Rol
        $updateResponse = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
            ])
            ->put(route('roles.update', $role), [
                'name' => 'Rol Auditor Senior',
                'description' => 'Rol modificado con nuevos privilegios',
                'status' => 1,
                'modules' => [1, 2, 3],
            ]);

        $updateResponse->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UPDATE',
            'module' => 'ROLES',
            'description' => "Actualización de rol 'Rol Auditor Senior'",
        ]);

        // 3. Eliminar Rol
        $deleteResponse = $this->actingAs($this->superAdmin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
            ])
            ->delete(route('roles.destroy', $role));

        $deleteResponse->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DELETE',
            'module' => 'ROLES',
            'description' => "Eliminación de rol 'Rol Auditor Senior' #{$role->id}",
        ]);
    }

    public function test_login_view_shows_warning_when_expired_flag_present(): void
    {
        $response = $this->get(route('login', ['expired' => 1]));
        $response->assertStatus(200);
        $response->assertSessionHas('warning', 'Tu sesión ha expirado por inactividad. Por favor ingresa tus credenciales nuevamente.');
    }
}
