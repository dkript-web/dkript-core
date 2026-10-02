<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
        $this->normalUser = User::where('email', 'demo@dkript.com')->first();
    }

    /**
     * A. maintenance 0 -> guest normal funciona según ruta pública elegida.
     */
    public function test_when_maintenance_is_disabled_guest_can_access_normally()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 0]);

        $responseHome = $this->get('/');
        $responseHome->assertRedirect(route('login'));

        $responseLogin = $this->get(route('login'));
        $responseLogin->assertStatus(200);
    }

    /**
     * B. maintenance 1 -> guest recibe 503 en rutas normales.
     */
    public function test_when_maintenance_is_active_guest_receives_http_503()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        $responseHome = $this->get('/');
        $responseHome->assertStatus(503);

        $responseLoginPlain = $this->get(route('login'));
        $responseLoginPlain->assertStatus(503);
    }

    /**
     * C. maintenance 1 -> usuario normal recibe 503.
     */
    public function test_when_maintenance_is_active_normal_user_receives_http_503()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        $responseDashboard = $this->actingAs($this->normalUser)->get(route('dashboard'));
        $responseDashboard->assertStatus(503);

        $responseUsers = $this->actingAs($this->normalUser)->get(route('users.index'));
        $responseUsers->assertStatus(503);
    }

    /**
     * D. maintenance 1 -> SuperAdmin conserva acceso administrativo total.
     */
    public function test_when_maintenance_is_active_super_admin_retains_access()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        $responseDashboard = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $responseDashboard->assertStatus(200);

        $responseUsers = $this->actingAs($this->superAdmin)->get(route('users.index'));
        $responseUsers->assertStatus(200);
    }

    /**
     * E. SuperAdmin puede llegar a Parámetros.
     */
    public function test_super_admin_can_access_parameters_during_maintenance()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        $responseParameters = $this->actingAs($this->superAdmin)->get(route('parameters.index'));
        $responseParameters->assertStatus(200);
        $responseParameters->assertSee('Modo Mantenimiento');
    }

    /**
     * F. Desactivar maintenance restaura acceso a todos los usuarios.
     */
    public function test_disabling_maintenance_restores_access()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        // SuperAdmin desactiva mantenimiento
        $param->update(['maintenance_mode' => 0]);

        // Usuario normal recupera acceso
        $responseNormal = $this->actingAs($this->normalUser)->get(route('dashboard'));
        $responseNormal->assertStatus(200);

        // Guest recupera acceso
        auth()->logout();
        $responseGuest = $this->get(route('login'));
        $responseGuest->assertStatus(200);
    }

    /**
     * G. Status HTTP realmente 503.
     */
    public function test_maintenance_mode_produces_exact_http_status_503()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        $response = $this->get('/');
        $this->assertEquals(503, $response->getStatusCode());
    }

    /**
     * H. Error 503 existente sigue funcionando y renderiza vista de error personalizada.
     */
    public function test_error_503_renders_custom_view_with_branding()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertSee('503');
    }

    /**
     * I. InactivityTimeout no se rompe con el middleware de mantenimiento.
     */
    public function test_inactivity_timeout_functions_normally_with_maintenance_middleware()
    {
        $param = Parameter::first();
        $param->update([
            'maintenance_mode' => 0,
            'session_timeout_minutes' => 15,
        ]);

        $response = $this->actingAs($this->superAdmin)->postJson('/session/ping');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /**
     * J. Flujo de login y logout aprobado funciona correctamente.
     */
    public function test_admin_login_and_logout_flow_during_maintenance()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        // SuperAdmin accede al formulario con query parameter ?admin=1
        $loginFormResponse = $this->get('/login?admin=1');
        $loginFormResponse->assertStatus(200);

        // SuperAdmin envía formulario de login
        $loginSubmitResponse = $this->post(route('login.submit'), [
            'email' => 'admin@dkript.com',
            'password' => 'admin123',
        ]);
        $loginSubmitResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->superAdmin);

        // Acceso al dashboard confirmado
        $this->get(route('dashboard'))->assertStatus(200);

        // Logout exitoso
        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Usuario normal que intenta autenticarse durante mantenimiento es bloqueado con 503 al ingresar.
     */
    public function test_normal_user_login_during_maintenance_is_blocked_with_503()
    {
        $param = Parameter::first();
        $param->update(['maintenance_mode' => 1]);

        $loginResponse = $this->post(route('login.submit'), [
            'email' => 'demo@dkript.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));

        // Tras ser redirigido, la siguiente solicitud recibe 503
        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertStatus(503);
    }
}
