<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Services\MailConfigService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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
            'name' => 'Alchemist Master',
            'email' => 'admin@dkript.com',
            'password' => Hash::make('originalPassword123'),
            'status' => 1,
        ]);

        Profile::create([
            'user_id' => $this->user->id,
            'role_id' => $role->id,
            'first_name' => 'Alchemist',
            'last_name' => 'Master',
            'phone' => '+525512345678',
        ]);

        Parameter::create([
            'system_name' => 'Dkript Enterprise',
            'sms_provider' => 'log',
            'mail_mailer' => 'log',
            'mail_host' => '127.0.0.1',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@dkript.com',
            'mail_from_name' => 'Dkript Enterprise',
            'records_per_page' => 10,
            'maintenance_mode' => 0,
            'modal_style' => 'corporate',
            'error_display_mode' => 'scene',
        ]);
    }

    public function test_forgot_password_renders_with_email_tab(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertStatus(200);
        $response->assertSee('Por Correo');
        $response->assertSee(route('password.email'));
    }

    public function test_send_reset_link_fails_for_invalid_or_missing_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'invalid-email-format',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_send_reset_link_fails_for_unregistered_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'unknown@dkript.com',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'No encontramos ninguna cuenta asociada a este correo electrónico.',
        ]);
    }

    public function test_send_reset_link_creates_token_and_dispatches_mail(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'admin@dkript.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('simulator_reset_url');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'admin@dkript.com',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', 'admin@dkript.com')->first();
        $this->assertNotNull($record->token);
    }

    public function test_send_reset_link_enforces_60_second_cooldown(): void
    {
        $this->post(route('password.email'), [
            'email' => 'admin@dkript.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'admin@dkript.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_show_reset_form_renders_with_valid_token(): void
    {
        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'admin@dkript.com',
            'token' => Hash::make($rawToken),
            'created_at' => now(),
        ]);

        $response = $this->get(route('password.reset', [
            'token' => $rawToken,
            'email' => 'admin@dkript.com',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Nueva Contraseña');
        $response->assertSee('admin@dkript.com');
        $response->assertSee($rawToken);
    }

    public function test_show_reset_form_fails_with_expired_token(): void
    {
        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'admin@dkript.com',
            'token' => Hash::make($rawToken),
            'created_at' => Carbon::now()->subMinutes(70),
        ]);

        $response = $this->get(route('password.reset', [
            'token' => $rawToken,
            'email' => 'admin@dkript.com',
        ]));

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_password_validates_requirements(): void
    {
        $response = $this->post(route('password.update-email'), [
            'token' => 'dummy-token',
            'email' => 'admin@dkript.com',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => 'admin@dkript.com',
            'token' => Hash::make('correct-token'),
            'created_at' => now(),
        ]);

        $response = $this->post(route('password.update-email'), [
            'token' => 'wrong-token',
            'email' => 'admin@dkript.com',
            'password' => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_password_updates_password_and_cleans_token(): void
    {
        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => 'admin@dkript.com',
            'token' => Hash::make($rawToken),
            'created_at' => now(),
        ]);

        $response = $this->post(route('password.update-email'), [
            'token' => $rawToken,
            'email' => 'admin@dkript.com',
            'password' => 'BrandNewPass2026!',
            'password_confirmation' => 'BrandNewPass2026!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Verify password updated in DB
        $this->user->refresh();
        $this->assertTrue(Hash::check('BrandNewPass2026!', $this->user->password));

        // Verify token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'admin@dkript.com',
        ]);

        // Verify user can now log in with new password
        $loginResponse = $this->post(route('login.submit'), [
            'email' => 'admin@dkript.com',
            'password' => 'BrandNewPass2026!',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_smtp_parameters_can_be_updated_by_superadmin(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('parameters.update'), [
            'system_name' => 'Dkript Enterprise Updated',
            'records_per_page' => 15,
            'maintenance_mode' => 0,
            'modal_style' => 'corporate',
            'error_display_mode' => 'scene',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.mailgun.org',
            'mail_port' => 587,
            'mail_username' => 'postmaster@mg.dkript.com',
            'mail_password' => 'secretSmtpPass123',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'system@dkript.com',
            'mail_from_name' => 'Dkript Mailer',
        ]);

        $response->assertRedirect(route('parameters.index'));

        $this->assertDatabaseHas('parameters', [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.mailgun.org',
            'mail_port' => 587,
            'mail_username' => 'postmaster@mg.dkript.com',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'system@dkript.com',
            'mail_from_name' => 'Dkript Mailer',
        ]);
    }

    public function test_smtp_test_connection_endpoint(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('parameters.test-smtp'), [
            'test_email' => 'admin@dkript.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'mailer' => 'log',
        ]);
    }

    public function test_mail_config_service_applies_settings(): void
    {
        $param = Parameter::first();
        $param->update([
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 465,
            'mail_username' => 'smtpuser',
            'mail_password' => 'smtppass',
            'mail_encryption' => 'ssl',
            'mail_from_address' => 'alerts@dkript.com',
            'mail_from_name' => 'Dkript Sentinel',
        ]);

        MailConfigService::applyConfig($param);

        $this->assertEquals('smtp', config('mail.default'));
        $this->assertEquals('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertEquals(465, config('mail.mailers.smtp.port'));
        $this->assertEquals('smtpuser', config('mail.mailers.smtp.username'));
        $this->assertEquals('ssl', config('mail.mailers.smtp.encryption'));
        $this->assertEquals('alerts@dkript.com', config('mail.from.address'));
        $this->assertEquals('Dkript Sentinel', config('mail.from.name'));
    }
}