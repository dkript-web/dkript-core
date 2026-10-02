<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $role;
    protected Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = Role::create([
            'id' => 1,
            'name' => 'Super Administrador',
            'description' => 'Acceso Total',
            'status' => 1,
        ]);

        $this->user = User::create([
            'name' => 'Alchemist Admin',
            'email' => 'admin@dkript.com',
            'password' => Hash::make('password123'),
            'status' => 1,
        ]);

        $this->profile = Profile::create([
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'phone' => '+525512345678',
        ]);

        Parameter::create([
            'system_name' => 'Dkript Enterprise',
            'sms_provider' => 'log',
            'records_per_page' => 10,
            'maintenance_mode' => 0,
            'modal_style' => 'corporate',
            'error_display_mode' => 'scene',
        ]);
    }

    public function test_user_without_2fa_logs_in_normally(): void
    {
        $response = $this->post(route('login.submit'), [
            'email' => 'admin@dkript.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_user_with_2fa_is_redirected_to_two_factor_challenge(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->post(route('login.submit'), [
            'email' => 'admin@dkript.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertEquals($this->user->id, session('login.id'));
    }

    public function test_guest_cannot_access_two_factor_challenge_without_session(): void
    {
        $response = $this->get(route('two-factor.challenge'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_with_session_can_view_two_factor_challenge(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->withSession(['login.id' => $this->user->id])
            ->get(route('two-factor.challenge'));

        $response->assertStatus(200);
        $response->assertSee('Verificación de Seguridad');
        $response->assertSee('twoFactorOtpContainer');
    }

    public function test_user_can_verify_2fa_challenge_with_valid_totp_code(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $validOtp = TwoFactorAuthService::getCurrentOtp($secret);

        $response = $this->withSession(['login.id' => $this->user->id])
            ->post(route('two-factor.verify'), [
                'code' => $validOtp,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
        $this->assertNull(session('login.id'));
    }

    public function test_user_cannot_verify_2fa_challenge_with_invalid_totp_code(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->withSession(['login.id' => $this->user->id])
            ->post(route('two-factor.verify'), [
                'code' => '999999',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_user_can_verify_2fa_challenge_with_recovery_code_and_consumes_it(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $recoveryCodes = ['CODE1-AAAAA', 'CODE2-BBBBB', 'CODE3-CCCCC'];

        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->withSession(['login.id' => $this->user->id])
            ->post(route('two-factor.verify'), [
                'recovery_code' => 'CODE1-AAAAA',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);

        // Verifica que el código fue consumido
        $this->user->refresh();
        $this->assertCount(2, $this->user->two_factor_recovery_codes);
        $this->assertNotContains('CODE1-AAAAA', $this->user->two_factor_recovery_codes);
        $this->assertContains('CODE2-BBBBB', $this->user->two_factor_recovery_codes);
    }

    public function test_authenticated_user_can_enable_2fa(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('user.two-factor.enable'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'secret',
            'formatted_secret',
            'qr_url',
            'recovery_codes',
        ]);

        $this->user->refresh();
        $this->assertTrue($this->user->hasTwoFactorPending());
        $this->assertFalse($this->user->hasTwoFactorEnabled());
        $this->assertCount(8, $this->user->two_factor_recovery_codes);
    }

    public function test_authenticated_user_can_confirm_2fa_with_valid_code(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => null,
        ])->save();

        $validOtp = TwoFactorAuthService::getCurrentOtp($secret);

        $response = $this->actingAs($this->user)
            ->postJson(route('user.two-factor.confirm'), [
                'code' => $validOtp,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => '¡Autenticación de dos factores activada exitosamente!',
        ]);

        $this->user->refresh();
        $this->assertTrue($this->user->hasTwoFactorEnabled());
        $this->assertNotNull($this->user->two_factor_confirmed_at);
    }

    public function test_authenticated_user_cannot_confirm_2fa_with_invalid_code(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => null,
        ])->save();

        $response = $this->actingAs($this->user)
            ->postJson(route('user.two-factor.confirm'), [
                'code' => '000000',
            ]);

        $response->assertStatus(422);
        $this->user->refresh();
        $this->assertFalse($this->user->hasTwoFactorEnabled());
    }

    public function test_authenticated_user_can_disable_2fa_with_valid_password(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->actingAs($this->user)
            ->deleteJson(route('user.two-factor.disable'), [
                'password' => 'password123',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'La autenticación de dos factores ha sido desactivada.',
        ]);

        $this->user->refresh();
        $this->assertFalse($this->user->hasTwoFactorEnabled());
        $this->assertNull($this->user->two_factor_secret);
        $this->assertNull($this->user->two_factor_recovery_codes);
        $this->assertNull($this->user->two_factor_confirmed_at);
    }

    public function test_authenticated_user_cannot_disable_2fa_with_wrong_password(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => TwoFactorAuthService::generateRecoveryCodes(8),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->actingAs($this->user)
            ->deleteJson(route('user.two-factor.disable'), [
                'password' => 'wrongpassword',
            ]);

        $response->assertStatus(422);
        $this->user->refresh();
        $this->assertTrue($this->user->hasTwoFactorEnabled());
    }

    public function test_authenticated_user_can_view_and_regenerate_recovery_codes(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $initialCodes = ['AAA-111', 'BBB-222'];
        $this->user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $initialCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        // 1. Ver códigos
        $viewResponse = $this->actingAs($this->user)
            ->getJson(route('user.two-factor.recovery-codes'));

        $viewResponse->assertStatus(200);
        $viewResponse->assertJson([
            'status' => 'success',
            'recovery_codes' => $initialCodes,
        ]);

        // 2. Regenerar códigos
        $regenResponse = $this->actingAs($this->user)
            ->postJson(route('user.two-factor.regenerate-codes'), [
                'password' => 'password123',
            ]);

        $regenResponse->assertStatus(200);
        $regenResponse->assertJsonStructure([
            'status',
            'message',
            'recovery_codes',
        ]);

        $this->user->refresh();
        $this->assertCount(8, $this->user->two_factor_recovery_codes);
        $this->assertNotEquals($initialCodes, $this->user->two_factor_recovery_codes);
    }

    public function test_totp_rfc6238_algorithm_and_clock_drift(): void
    {
        $secret = TwoFactorAuthService::generateSecretKey(32);
        $currentOtp = TwoFactorAuthService::getCurrentOtp($secret);

        $this->assertEquals(6, strlen($currentOtp));
        $this->assertTrue(is_numeric($currentOtp));

        // Debe verificar correctamente el código actual
        $this->assertTrue(TwoFactorAuthService::verifyKey($secret, $currentOtp, 1));

        // Código incorrecto debe fallar
        $this->assertFalse(TwoFactorAuthService::verifyKey($secret, '999999', 1));
    }
}
