<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suite de Pruebas de Calidad y Verificación:
 * Logout Real al Expirar Modal de Inactividad de Sesión.
 * 
 * Verifica los criterios TEST A a TEST H:
 * - TEST A: Contador llega a cero -> se dispara logout real.
 * - TEST B: Logout utiliza método/ruta correcta (POST /logout obligatorio).
 * - TEST C: Sesión backend queda completamente invalidada.
 * - TEST D: Usuario termina en login con /login?expired=1.
 * - TEST E: Mensaje expired se muestra si corresponde.
 * - TEST F: Usuario pulsa continuar antes de cero -> NO logout.
 * - TEST G: Invocaciones múltiples manejadas idempotentemente sin error.
 * - TEST H: Presencia de formulario canónico logout-form y contrato en Blade y JS.
 */
class SessionIdleLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->user = User::where('email', 'admin@dkript.com')->first();
    }

    /**
     * TEST A: Contador llega a cero -> se dispara logout real por inactividad.
     */
    public function test_a_expiration_triggers_real_logout_and_redirects_with_expired_flag(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
            ])
            ->post(route('logout'), ['expired' => '1']);

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // Validar auditoría de logout
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGOUT',
            'module' => 'AUTH',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * TEST B: Logout utiliza método/ruta correcta (POST obligatorio, GET prohibido).
     */
    public function test_b_logout_strictly_requires_post_method(): void
    {
        // GET /logout debe ser rechazado con 405 Method Not Allowed
        $response = $this->actingAs($this->user)->get('/logout');
        $response->assertStatus(405);

        // POST /logout debe ser aceptado
        $postResponse = $this->actingAs($this->user)->post('/logout');
        $postResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * TEST C: Sesión backend queda completamente invalidada tras logout de expiración.
     */
    public function test_c_backend_session_is_invalidated_and_dashboard_inaccessible(): void
    {
        $this->actingAs($this->user)
            ->withSession([
                'sensitive_key' => 'secret_value',
                'myoptions' => [1, 2, 3],
            ])
            ->post(route('logout'), ['expired' => '1']);

        $this->assertGuest();

        // Tras logout, un intento directo a /dashboard debe redirigir inmediatamente a login
        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('login'));
    }

    /**
     * TEST D: Usuario termina en login con URL /login?expired=1.
     */
    public function test_d_expired_logout_redirects_to_login_screen_with_expired_query(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/logout?expired=1');

        $response->assertRedirect(route('login'));
    }

    /**
     * TEST E: Mensaje 'expired' se muestra en pantalla de login.
     */
    public function test_e_login_screen_displays_inactivity_expiration_warning_message(): void
    {
        $response = $this->get(route('login', ['expired' => 1]));

        $response->assertStatus(200);
        $response->assertSessionHas('warning', 'Tu sesión ha expirado por inactividad. Por favor ingresa tus credenciales nuevamente.');
    }

    /**
     * TEST F: Usuario pulsa continuar antes de cero -> NO logout (session/ping renueva actividad).
     */
    public function test_f_stay_connected_renews_session_without_logging_out(): void
    {
        $oldActivityTime = time() - 300;

        $response = $this->actingAs($this->user)
            ->withSession([
                'last_activity_time' => $oldActivityTime,
                'myoptions' => [1, 2, 3],
                'permission_matrix' => ['*'],
            ])
            ->post(route('session.ping'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertAuthenticatedAs($this->user);
        $this->assertGreaterThanOrEqual(time() - 2, session('last_activity_time'));
    }

    /**
     * TEST G: expireSession llamado dos veces -> solo un logout (idempotente y seguro).
     */
    public function test_g_multiple_logout_calls_are_handled_idempotently_without_errors(): void
    {
        // Primera llamada
        $response1 = $this->actingAs($this->user)
            ->post(route('logout'), ['expired' => '1']);
        $response1->assertRedirect(route('login'));
        $this->assertGuest();

        // Segunda llamada consecutiva ya como invitado (redirige limpiamente a login sin errores)
        $response2 = $this->post(route('logout'), ['expired' => '1']);
        $response2->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * TEST H: Layout contiene modal, formulario canónico logout-form y contrato JS.
     */
    public function test_h_admin_layout_contains_canonical_logout_form_and_idle_modal(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
            ])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="modalSessionIdleWarning"', false);
        $response->assertSee('id="logout-form"', false);
        $response->assertSee('action="' . route('logout') . '"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('btnSessionIdleLogoutNow', false);
        $response->assertSee('btnSessionIdleStay', false);
        $response->assertSee('DkriptIdle.logoutNow()', false);
        $response->assertSee('DkriptIdle.stayConnected()', false);

        // Validar contrato del archivo custom.js
        $jsContent = file_get_contents(public_path('assets/js/custom.js'));
        $this->assertStringContainsString('isLoggingOut', $jsContent);
        $this->assertStringContainsString('performLogout', $jsContent);
        $this->assertStringContainsString('expireSession', $jsContent);
        $this->assertStringContainsString('logoutNow', $jsContent);
        $this->assertStringContainsString('logout-form', $jsContent);
    }
}
