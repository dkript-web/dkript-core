<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\MenuOption;
use App\Models\Parameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);
    }

    public function test_security_headers_are_present_in_responses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_rate_limiting_blocks_brute_force_after_5_failed_attempts(): void
    {
        RateLimiter::clear('attacker@example.com|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'attacker@example.com',
                'password' => 'wrongpass',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6to intento debe ser bloqueado por RateLimiter
        $blockedResponse = $this->post('/login', [
            'email' => 'attacker@example.com',
            'password' => 'wrongpass',
        ]);

        $blockedResponse->assertSessionHasErrors([
            'email' => 'Demasiados intentos de acceso fallidos. Por seguridad, intente nuevamente en 60 segundos.',
        ]);
    }

    public function test_path_traversal_is_blocked_on_image_deletion(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)->postJson('/parameters/delete-image', [
            'path' => '../../.env',
            'admin_password' => 'admin123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Acceso denegado: Ruta de archivo no autorizada o inválida.',
        ]);
    }

    public function test_svg_with_script_payload_is_rejected(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("XSS")</script></svg>';
        $file = UploadedFile::fake()->createWithContent('malicious.svg', $maliciousSvg);

        $response = $this->actingAs($admin)->postJson('/parameters/upload-image', [
            'image' => $file,
            'name' => 'Test SVG',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'El archivo SVG contiene scripts o elementos inseguros no permitidos.',
        ]);
    }

    public function test_backend_enforces_rbac_position_authorization(): void
    {
        // El usuario demo (Operador) tiene asignado módulo 2 (Usuarios) pero solo permiso posición 4 (Ver)
        $operator = User::where('email', 'demo@dkript.com')->first();

        // 1. Intentar CREAR usuario (Posición 1 requerida -> Denegado)
        $createResponse = $this->actingAs($operator)->postJson('/users', [
            'name' => 'HackerUser',
            'first_name' => 'Hack',
            'last_name' => 'Er',
            'email' => 'hacker@dkript.com',
            'password' => 'password123',
            'role_id' => 2,
            'status' => 1,
        ]);

        $createResponse->assertStatus(403);
        $createResponse->assertJson([
            'success' => false,
            'error' => 'Acceso Denegado: No cuentas con el privilegio de [Crear] en este módulo.',
        ]);

        // 2. Intentar ELIMINAR usuario (Posición 3 requerida -> Denegado)
        $targetUser = User::where('email', 'demo@dkript.com')->first();
        $deleteResponse = $this->actingAs($operator)->deleteJson("/users/{$targetUser->id}");

        $deleteResponse->assertStatus(403);
        $deleteResponse->assertJson([
            'success' => false,
            'error' => 'Acceso Denegado: No cuentas con el privilegio de [Eliminar] en este módulo.',
        ]);
    }

    public function test_super_admin_bypasses_position_checks(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        // Super Admin puede crear sin restricciones
        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'SecAuditUser',
            'first_name' => 'Security',
            'last_name' => 'Audit',
            'email' => 'secaudit@dkript.com',
            'password' => 'ValidPass123!',
            'role_id' => 2,
            'status' => 1,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'secaudit@dkript.com']);
    }

    public function test_user_password_policy_enforces_complexity(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        // Contraseña demasiado corta (menor a 8 caracteres)
        $responseShort = $this->actingAs($admin)->post('/users', [
            'name' => 'WeakUser',
            'first_name' => 'Weak',
            'last_name' => 'Pass',
            'email' => 'weak@dkript.com',
            'password' => '12345',
            'role_id' => 2,
            'status' => 1,
        ]);

        $responseShort->assertSessionHasErrors('password');

        // Contraseña sin números
        $responseNoNumber = $this->actingAs($admin)->post('/users', [
            'name' => 'WeakUser2',
            'first_name' => 'Weak',
            'last_name' => 'Pass',
            'email' => 'weak2@dkript.com',
            'password' => 'onlyletters',
            'role_id' => 2,
            'status' => 1,
        ]);

        $responseNoNumber->assertSessionHasErrors('password');

        // Contraseña sin caracteres especiales
        $responseNoSymbol = $this->actingAs($admin)->post('/users', [
            'name' => 'WeakUser3',
            'first_name' => 'Weak',
            'last_name' => 'Pass',
            'email' => 'weak3@dkript.com',
            'password' => 'Password123',
            'role_id' => 2,
            'status' => 1,
        ]);

        $responseNoSymbol->assertSessionHasErrors('password');

        // Contraseña sin mayúsculas
        $responseNoUpper = $this->actingAs($admin)->post('/users', [
            'name' => 'WeakUser4',
            'first_name' => 'Weak',
            'last_name' => 'Pass',
            'email' => 'weak4@dkript.com',
            'password' => 'password123!',
            'role_id' => 2,
            'status' => 1,
        ]);

        $responseNoUpper->assertSessionHasErrors('password');
    }
}