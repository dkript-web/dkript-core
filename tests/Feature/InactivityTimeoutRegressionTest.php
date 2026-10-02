<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Parameter;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suite de Pruebas de Reproducción y Diagnóstico de Regresión:
 * Inactividad de Sesión y Conflicto con Polling en Segundo Plano.
 * 
 * Diseñado estrictamente para reproducir la incidencia reportada por el usuario
 * sin alterar la implementación en código de producción.
 */
class InactivityTimeoutRegressionTest extends TestCase
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
     * A) Timeout configurado -> Actividad reciente -> Usuario permanece autenticado.
     */
    public function test_inactivity_timeout_allows_authenticated_user_when_active(): void
    {
        Parameter::first()->update(['session_timeout_minutes' => 15]);

        $response = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - 300, // 5 min atrás (< 15 min)
            ])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($this->user);
        $this->assertGreaterThanOrEqual(time() - 2, session('last_activity_time'));
    }

    /**
     * B) Timeout configurado -> Inactividad superior al límite -> Usuario pierde sesión.
     */
    public function test_inactivity_timeout_logs_out_when_inactive_beyond_limit(): void
    {
        Parameter::first()->update(['session_timeout_minutes' => 15]);

        $response = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - 1000, // 16.6 min atrás (> 15 min)
            ])
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * C) Cambiar Parameter -> Cambia el límite efectivo de timeout.
     */
    public function test_changing_parameter_changes_effective_timeout_limit(): void
    {
        // 1. Con timeout de 30 minutos: 20 minutos de inactividad son permitidos
        Parameter::first()->update(['session_timeout_minutes' => 30]);

        $response = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - (20 * 60), // 20 min atrás
            ])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($this->user);

        // 2. Con timeout de 10 minutos: los mismos 20 minutos ahora provocan logout
        Parameter::first()->update(['session_timeout_minutes' => 10]);

        $response2 = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time() - (20 * 60), // 20 min atrás
            ])
            ->get(route('dashboard'));

        $response2->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * D) Una petición válida -> Actualiza / reinicia last_activity_time.
     */
    public function test_valid_request_resets_last_activity_time(): void
    {
        $oldTime = time() - 200;

        $response = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => $oldTime,
            ])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $newTime = session('last_activity_time');
        $this->assertGreaterThan($oldTime, $newTime);
        $this->assertGreaterThanOrEqual(time() - 2, $newTime);
    }

    /**
     * E) REPRODUCCIÓN DE LA REGRESIÓN:
     * El polling en segundo plano a /notifications renueva la sesión e IMPIDE
     * que el usuario sea desconectado por inactividad.
     */
    public function test_demonstrate_regression_notification_polling_keeps_session_alive_artificially(): void
    {
        // Administrador configura timeout de 1 minuto (60 segundos)
        Parameter::first()->update(['session_timeout_minutes' => 1]);

        $baseTime = time();

        // 1. El usuario interactúa por última vez en t = 0
        $sessionData = [
            'myoptions' => [1, 2, 3, 4, 5, 6],
            'permission_matrix' => ['*'],
            'last_activity_time' => $baseTime - 50, // Actividad registrada hace 50 segundos
        ];

        // 2. A los 45s de inactividad, el cliente JS ejecuta fetchNotifications() a /notifications
        // En el código actual, InactivityTimeout se ejecuta sobre /notifications y renueva last_activity_time a NOW
        $pollResponse = $this->actingAs($this->user)
            ->withSession($sessionData)
            ->getJson(route('notifications.index'));

        $pollResponse->assertStatus(200);

        // DEMOSTRACIÓN: La petición de polling en segundo plano actualizó last_activity_time
        $lastActivityAfterPoll = session('last_activity_time');
        $this->assertGreaterThanOrEqual($baseTime - 2, $lastActivityAfterPoll, 
            'DIAGNÓSTICO CONFIRMADO: El polling de notificaciones actualizó last_activity_time de la sesión.'
        );

        // 3. A los 75 segundos desde la última interacción real del usuario (superó el timeout de 60s):
        // Pero debido al polling ocurrido en el paso 2, el servidor cree que la actividad fue hace solo unos segundos.
        $dashboardResponse = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => $lastActivityAfterPoll, // Heredado del polling
            ])
            ->get(route('dashboard'));

        // En el estado actual del código: EL USUARIO SIGUE AUTENTICADO (No hubo logout por inactividad)
        // Esto demuestra por qué el usuario observa que "el timeout de inactividad ya no funciona".
        $this->assertTrue(
            \Illuminate\Support\Facades\Auth::check(),
            'REGRESIÓN DETECTADA: El usuario NO fue desconectado a pesar de que el timeout de 1 minuto fue excedido por inactividad real.'
        );
    }

    /**
     * F) REPRODUCCIÓN DE LA REGRESIÓN FRONTEND -> BACKEND:
     * Cuando el timer de JS llega a 0 y redirige a /login?expired=1,
     * AuthController::showLoginForm redirige de vuelta al dashboard porque el backend no ha cerrado la sesión.
     */
    public function test_demonstrate_regression_login_expired_redirects_to_dashboard_if_backend_session_alive(): void
    {
        // Usuario tiene sesión activa en el servidor (gracias al polling o porque frontend solo redirige)
        $response = $this->actingAs($this->user)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5, 6],
                'permission_matrix' => ['*'],
                'last_activity_time' => time(),
            ])
            ->get(route('login', ['expired' => 1]));

        // Comportamiento actual de AuthController::showLoginForm():
        // if (Auth::check()) { return redirect()->route('dashboard'); }
        // DEMOSTRACIÓN: El usuario es rebotado al Dashboard en lugar de ver el login de expiración!
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }
}
