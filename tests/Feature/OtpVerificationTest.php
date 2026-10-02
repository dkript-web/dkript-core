<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\PhoneVerification;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Services\Messaging\LogSimulatorDriver;
use App\Services\Messaging\MessagingService;
use App\Services\Messaging\TwilioDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'id' => 1,
            'name' => 'Super Administrador',
            'description' => 'Acceso Total',
            'status' => 1,
        ]);

        $this->user = User::create([
            'name' => 'Alchemist Admin',
            'email' => 'admin@dkript.com',
            'password' => Hash::make('oldpassword123'),
            'status' => 1,
        ]);

        $this->profile = Profile::create([
            'user_id' => $this->user->id,
            'role_id' => $role->id,
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

    public function test_forgot_password_screen_renders_successfully(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
        $response->assertSee('Recuperar Contraseña');
        $response->assertSee('phone');
        $response->assertSee('channel');
    }

    public function test_send_otp_validation_fails_for_missing_or_invalid_phone(): void
    {
        $response = $this->post(route('password.send-otp'), [
            'phone' => '',
            'channel' => 'sms',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_send_otp_fails_if_phone_does_not_belong_to_any_user(): void
    {
        $response = $this->post(route('password.send-otp'), [
            'phone' => '+525599999999',
            'channel' => 'sms',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $response->assertSessionHasErrors([
            'phone' => 'No encontramos ninguna cuenta asociada al número telefónico ingresado.',
        ]);
    }

    public function test_send_otp_succeeds_for_registered_user_in_simulator_mode(): void
    {
        $response = $this->post(route('password.send-otp'), [
            'phone' => '+52 55 1234 5678',
            'channel' => 'sms',
        ]);

        $response->assertRedirect(route('password.verify-otp'));
        $response->assertSessionHas('otp_phone', '+525512345678');
        $response->assertSessionHas('otp_channel', 'sms');
        $response->assertSessionHas('otp_verification_id');
        $response->assertSessionHas('simulator_otp_code');

        $this->assertDatabaseHas('phone_verifications', [
            'user_id' => $this->user->id,
            'phone' => '+525512345678',
            'channel' => 'sms',
            'purpose' => 'password_reset',
            'attempts' => 0,
        ]);
    }

    public function test_send_otp_enforces_60_second_cooldown(): void
    {
        // First OTP request
        $this->post(route('password.send-otp'), [
            'phone' => '+525512345678',
            'channel' => 'sms',
        ]);

        // Immediate second OTP request
        $response = $this->post(route('password.send-otp'), [
            'phone' => '+525512345678',
            'channel' => 'sms',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_verify_otp_screen_renders_with_masked_phone(): void
    {
        $this->withSession([
            'otp_phone' => '+525512345678',
            'otp_channel' => 'sms',
            'otp_verification_id' => 1,
            'simulator_otp_code' => '654321',
        ]);

        $response = $this->get(route('password.verify-otp'));
        $response->assertStatus(200);
        $response->assertSee('Ingresa el Código OTP');
        $response->assertSee('654321'); // Dev simulator badge
    }

    public function test_verify_otp_fails_with_invalid_code_and_increments_attempts(): void
    {
        $verification = PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '+525512345678',
            'otp_code' => '123456',
            'channel' => 'sms',
            'purpose' => 'password_reset',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession([
            'otp_phone' => '+525512345678',
            'otp_channel' => 'sms',
            'otp_verification_id' => $verification->id,
        ]);

        $response = $this->post(route('password.verify'), [
            'code' => '999999',
        ]);

        $response->assertSessionHasErrors(['otp_code']);
        $verification->refresh();
        $this->assertEquals(1, $verification->attempts);
        $this->assertNull($verification->verified_at);
    }

    public function test_verify_otp_blocks_after_5_failed_attempts(): void
    {
        $verification = PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '+525512345678',
            'otp_code' => '123456',
            'channel' => 'sms',
            'purpose' => 'password_reset',
            'attempts' => 5,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession([
            'otp_phone' => '+525512345678',
            'otp_channel' => 'sms',
            'otp_verification_id' => $verification->id,
        ]);

        $response = $this->post(route('password.verify'), [
            'code' => '123456',
        ]);

        $response->assertSessionHasErrors(['otp_code']);
        $this->assertNull($verification->fresh()->verified_at);
    }

    public function test_verify_otp_fails_when_expired(): void
    {
        $verification = PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '+525512345678',
            'otp_code' => '123456',
            'channel' => 'sms',
            'purpose' => 'password_reset',
            'attempts' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $this->withSession([
            'otp_phone' => '+525512345678',
            'otp_channel' => 'sms',
            'otp_verification_id' => $verification->id,
        ]);

        $response = $this->post(route('password.verify'), [
            'code' => '123456',
        ]);

        $response->assertSessionHasErrors(['otp_code']);
    }

    public function test_verify_otp_succeeds_with_correct_code(): void
    {
        $verification = PhoneVerification::create([
            'user_id' => $this->user->id,
            'phone' => '+525512345678',
            'otp_code' => '123456',
            'channel' => 'sms',
            'purpose' => 'password_reset',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession([
            'otp_phone' => '+525512345678',
            'otp_channel' => 'sms',
            'otp_verification_id' => $verification->id,
        ]);

        $response = $this->post(route('password.verify'), [
            'code' => '123456',
        ]);

        $response->assertRedirect(route('password.reset-form'));
        $response->assertSessionHas('password_reset_verified', true);
        $response->assertSessionHas('password_reset_user_id', $this->user->id);

        $verification->refresh();
        $this->assertNotNull($verification->verified_at);
    }

    public function test_reset_password_validates_minimum_requirements(): void
    {
        $this->withSession([
            'password_reset_verified' => true,
            'password_reset_user_id' => $this->user->id,
            'otp_phone' => '+525512345678',
        ]);

        $response = $this->post(route('password.update'), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_reset_password_updates_user_password_and_clears_session(): void
    {
        $this->withSession([
            'password_reset_verified' => true,
            'password_reset_user_id' => $this->user->id,
            'otp_phone' => '+525512345678',
        ]);

        $response = $this->post(route('password.update'), [
            'password' => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('password_reset_verified');
        $response->assertSessionMissing('password_reset_user_id');

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass2026!', $this->user->password));

        // Test login with the new password
        $loginResponse = $this->post(route('login.submit'), [
            'email' => 'admin@dkript.com',
            'password' => 'NewSecurePass2026!',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_telephony_parameters_can_be_saved(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('parameters.update'), [
            'system_name' => 'Dkript Enterprise Updated',
            'records_per_page' => 25,
            'maintenance_mode' => 0,
            'modal_style' => 'corporate',
            'error_display_mode' => 'scene',
            'sms_provider' => 'twilio',
            'twilio_account_sid' => 'AC1234567890abcdef1234567890abcdef',
            'twilio_auth_token' => 'token1234567890abcdef1234567890',
            'twilio_phone_number' => '+15005550006',
            'twilio_whatsapp_number' => '+14155238886',
        ]);

        $response->assertRedirect(route('parameters.index'));

        $this->assertDatabaseHas('parameters', [
            'sms_provider' => 'twilio',
            'twilio_account_sid' => 'AC1234567890abcdef1234567890abcdef',
            'twilio_phone_number' => '+15005550006',
            'twilio_whatsapp_number' => '+14155238886',
        ]);
    }

    public function test_messaging_service_resolves_correct_driver(): void
    {
        // Default parameter has sms_provider = 'log'
        $driver = MessagingService::getDriver();
        $this->assertInstanceOf(LogSimulatorDriver::class, $driver);
        $this->assertTrue(MessagingService::isSimulator());

        // Update parameter to twilio
        Parameter::first()->update([
            'sms_provider' => 'twilio',
            'twilio_account_sid' => 'ACtest',
            'twilio_auth_token' => 'tokentest',
            'twilio_phone_number' => '+1111111111',
            'twilio_whatsapp_number' => '+2222222222',
        ]);

        $driver = MessagingService::getDriver();
        $this->assertInstanceOf(TwilioDriver::class, $driver);
        $this->assertFalse(MessagingService::isSimulator());
    }
}