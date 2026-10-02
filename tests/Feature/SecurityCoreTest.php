<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parameter;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1 & 2. Parámetros sensibles se guardan cifrados en DB y se descifran automáticamente en Eloquent.
     */
    public function test_parameter_secrets_are_stored_encrypted_in_database_and_decrypted_by_eloquent(): void
    {
        $parameter = Parameter::first();
        $parameter->mail_password = 'MySuperSecretMailPassword123!';
        $parameter->twilio_auth_token = 'TwilioSecretAuthTokenXYZ456!';
        $parameter->whatsapp_access_token = 'EAAGMetaPermanentToken789!';
        $parameter->save();

        // 1. Verificar directamente en la base de datos (DB query sin Eloquent casts)
        $rawRow = DB::table('parameters')->where('id', $parameter->id)->first();

        // No deben ser iguales al texto plano
        $this->assertNotEquals('MySuperSecretMailPassword123!', $rawRow->mail_password);
        $this->assertNotEquals('TwilioSecretAuthTokenXYZ456!', $rawRow->twilio_auth_token);
        $this->assertNotEquals('EAAGMetaPermanentToken789!', $rawRow->whatsapp_access_token);

        // Deben ser descifrables usando Crypt
        $this->assertEquals('MySuperSecretMailPassword123!', Crypt::decrypt($rawRow->mail_password, false));
        $this->assertEquals('TwilioSecretAuthTokenXYZ456!', Crypt::decrypt($rawRow->twilio_auth_token, false));
        $this->assertEquals('EAAGMetaPermanentToken789!', Crypt::decrypt($rawRow->whatsapp_access_token, false));

        // 2. Al leer vía Eloquent, el valor descifrado coincide automáticamente con el original
        $fresh = Parameter::find($parameter->id);
        $this->assertEquals('MySuperSecretMailPassword123!', $fresh->mail_password);
        $this->assertEquals('TwilioSecretAuthTokenXYZ456!', $fresh->twilio_auth_token);
        $this->assertEquals('EAAGMetaPermanentToken789!', $fresh->whatsapp_access_token);
    }

    /**
     * 3. Campos no sensibles NO están cifrados en base de datos.
     */
    public function test_non_sensitive_parameter_fields_are_not_encrypted(): void
    {
        $parameter = Parameter::first();
        $parameter->system_name = 'Dkript Core Unencrypted Test';
        $parameter->mail_host = 'smtp.sendgrid.net';
        $parameter->contact_email = 'info@dkript.com';
        $parameter->save();

        $rawRow = DB::table('parameters')->where('id', $parameter->id)->first();

        $this->assertEquals('Dkript Core Unencrypted Test', $rawRow->system_name);
        $this->assertEquals('smtp.sendgrid.net', $rawRow->mail_host);
        $this->assertEquals('info@dkript.com', $rawRow->contact_email);
    }

    /**
     * 4. La serialización (toArray/toJson) de Parameter oculta los secrets.
     */
    public function test_parameter_serialization_hides_sensitive_secrets(): void
    {
        $parameter = Parameter::first();
        $parameter->mail_password = 'SecretPasswordToHide';
        $parameter->twilio_auth_token = 'SecretTokenToHide';
        $parameter->whatsapp_access_token = 'SecretMetaTokenToHide';
        $parameter->save();

        $array = $parameter->toArray();
        $json = json_encode($parameter);

        $this->assertArrayNotHasKey('mail_password', $array);
        $this->assertArrayNotHasKey('twilio_auth_token', $array);
        $this->assertArrayNotHasKey('whatsapp_access_token', $array);

        $this->assertStringNotContainsString('SecretPasswordToHide', $json);
        $this->assertStringNotContainsString('SecretTokenToHide', $json);
        $this->assertStringNotContainsString('SecretMetaTokenToHide', $json);
    }

    /**
     * 5 & 6. two_factor_secret y two_factor_recovery_codes se guardan cifrados en DB y se manejan transparentemente.
     */
    public function test_two_factor_secrets_and_recovery_codes_are_stored_encrypted(): void
    {
        $user = User::create([
            'name' => '2FA Security User',
            'email' => '2fa-sec@dkript.com',
            'password' => Hash::make('SecretPass12345'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => ['CODE1-AAAAA', 'CODE2-BBBBB', 'CODE3-CCCCC'],
            'two_factor_confirmed_at' => now(),
        ]);

        // Verificar directo en DB
        $rawUser = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotEquals('JBSWY3DPEHPK3PXP', $rawUser->two_factor_secret);
        $this->assertNotEquals('["CODE1-AAAAA","CODE2-BBBBB","CODE3-CCCCC"]', $rawUser->two_factor_recovery_codes);

        // Descifrable vía Crypt
        $this->assertEquals('JBSWY3DPEHPK3PXP', Crypt::decrypt($rawUser->two_factor_secret, false));
        $this->assertEquals(['CODE1-AAAAA', 'CODE2-BBBBB', 'CODE3-CCCCC'], json_decode(Crypt::decrypt($rawUser->two_factor_recovery_codes, false), true));

        // Eloquent descifra automáticamente
        $freshUser = User::find($user->id);
        $this->assertEquals('JBSWY3DPEHPK3PXP', $freshUser->two_factor_secret);
        $this->assertEquals(['CODE1-AAAAA', 'CODE2-BBBBB', 'CODE3-CCCCC'], $freshUser->two_factor_recovery_codes);
        $this->assertTrue($freshUser->hasTwoFactorEnabled());
    }

    /**
     * 7. La serialización de User oculta two_factor_secret y two_factor_recovery_codes.
     */
    public function test_user_serialization_hides_two_factor_secrets(): void
    {
        $user = User::create([
            'name' => 'Serialization User',
            'email' => 'serial@dkript.com',
            'password' => Hash::make('SecretPass12345'),
            'two_factor_secret' => 'SECRET2FAKEYXYZ',
            'two_factor_recovery_codes' => ['REC1-AAAA', 'REC2-BBBB'],
        ]);

        $array = $user->toArray();
        $json = json_encode($user);

        $this->assertArrayNotHasKey('two_factor_secret', $array);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $array);
        $this->assertArrayNotHasKey('password', $array);

        $this->assertStringNotContainsString('SECRET2FAKEYXYZ', $json);
        $this->assertStringNotContainsString('REC1-AAAA', $json);
    }

    /**
     * 8. Consumo de código de recuperación 2FA funciona con casts cifrados.
     */
    public function test_two_factor_recovery_code_can_be_consumed_with_encrypted_cast(): void
    {
        $user = User::create([
            'name' => 'Recovery Code User',
            'email' => 'recovery@dkript.com',
            'password' => Hash::make('SecretPass12345'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => ['VALID-CODE1', 'VALID-CODE2'],
            'two_factor_confirmed_at' => now(),
        ]);

        $consumed = TwoFactorAuthService::verifyAndConsumeRecoveryCode($user, 'VALID-CODE1');
        $this->assertTrue($consumed);

        $freshUser = User::find($user->id);
        $this->assertEquals(['VALID-CODE2'], $freshUser->two_factor_recovery_codes);

        // El código ya consumido no puede usarse una segunda vez
        $reused = TwoFactorAuthService::verifyAndConsumeRecoveryCode($freshUser, 'VALID-CODE1');
        $this->assertFalse($reused);
    }

    /**
     * 9. AuditService sanitiza recursivamente claves sensibles en old_values y new_values.
     */
    public function test_audit_service_sanitizes_sensitive_keys_recursively(): void
    {
        $oldData = [
            'system_name' => 'Old Name',
            'password' => 'supersecretpass',
            'nested' => [
                'mail_password' => 'secretmailpass',
                'token' => 'nested_token_123',
                'safe_field' => 'visible_value',
            ],
            'twilio_auth_token' => 'twilio_secret_val',
        ];

        $newData = [
            'system_name' => 'New Name',
            'password' => 'newpassword123',
            'two_factor_secret' => '2fa_secret_key',
            'recovery_codes' => ['CODE-1', 'CODE-2'],
            'nested' => [
                'api_key' => 'key_xyz_789',
                'description' => 'updated description',
            ],
        ];

        $log = AuditService::log('UPDATE', 'TEST', 'Sanitization Test Event', $oldData, $newData);

        $this->assertNotNull($log);
        $freshLog = AuditLog::find($log->id);

        // Verificar datos antiguos sanitizados
        $this->assertEquals('Old Name', $freshLog->old_values['system_name']);
        $this->assertEquals('[REDACTED]', $freshLog->old_values['password']);
        $this->assertEquals('[REDACTED]', $freshLog->old_values['nested']['mail_password']);
        $this->assertEquals('[REDACTED]', $freshLog->old_values['nested']['token']);
        $this->assertEquals('visible_value', $freshLog->old_values['nested']['safe_field']);
        $this->assertEquals('[REDACTED]', $freshLog->old_values['twilio_auth_token']);

        // Verificar datos nuevos sanitizados
        $this->assertEquals('New Name', $freshLog->new_values['system_name']);
        $this->assertEquals('[REDACTED]', $freshLog->new_values['password']);
        $this->assertEquals('[REDACTED]', $freshLog->new_values['two_factor_secret']);
        $this->assertEquals('[REDACTED]', $freshLog->new_values['recovery_codes']);
        $this->assertEquals('[REDACTED]', $freshLog->new_values['nested']['api_key']);
        $this->assertEquals('updated description', $freshLog->new_values['nested']['description']);
    }

    /**
     * 10. La pantalla de login no contiene contraseñas predeterminadas ni autocompletadas en el HTML.
     */
    public function test_login_view_does_not_contain_default_or_autocompleted_credentials(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $content = $response->getContent();

        // No debe contener valores pre-cargados
        $this->assertStringNotContainsString('value="admin@dkript.com"', $content);
        $this->assertStringNotContainsString('value="admin123"', $content);
        $this->assertStringNotContainsString('value="demo@dkript.com"', $content);
        $this->assertStringNotContainsString('value="password123"', $content);

        // No debe contener botones o sección de acceso rápido
        $this->assertStringNotContainsString('Accesos Rápidos (Semillero)', $content);
        $this->assertStringNotContainsString('fillCredentials', $content);
    }

    /**
     * 11. Los formularios de parámetros no exponen passwords ni tokens existentes en value="...".
     */
    public function test_parameter_views_do_not_expose_secrets_in_input_values(): void
    {
        $parameter = Parameter::first();
        $parameter->mail_password = 'ExistingSecretMailPass';
        $parameter->twilio_auth_token = 'ExistingTwilioToken';
        $parameter->whatsapp_access_token = 'ExistingWhatsAppToken';
        $parameter->save();

        $this->seed(\Database\Seeders\DemoSeeder::class);
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)->get('/parameters');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringNotContainsString('ExistingSecretMailPass', $content);
        $this->assertStringNotContainsString('ExistingTwilioToken', $content);
        $this->assertStringNotContainsString('ExistingWhatsAppToken', $content);

        // Debe contener el placeholder indicativo de configuración
        $this->assertStringContainsString('•••••••••••••••• (Configurado)', $content);
    }

    /**
     * 12. Actualización de parámetros sin modificar contraseña conserva el valor existente encriptado.
     */
    public function test_parameter_update_keeps_existing_secrets_when_inputs_are_empty(): void
    {
        $parameter = Parameter::first();
        $parameter->mail_password = 'OriginalSecretMailPassword';
        $parameter->twilio_auth_token = 'OriginalTwilioAuthToken';
        $parameter->whatsapp_access_token = 'OriginalWhatsAppAccessToken';
        $parameter->save();

        $this->seed(\Database\Seeders\DemoSeeder::class);
        $admin = User::where('email', 'admin@dkript.com')->first();

        // Enviar actualización con campos de contraseña/token vacíos
        $response = $this->actingAs($admin)->post('/parameters', [
            'system_name' => 'Dkript Core Updated Name',
            'system_logo' => 'assets/images/branding/logo-dkript.png',
            'records_per_page' => 20,
            'maintenance_mode' => 0,
            'modal_style' => 'corporate',
            'mail_password' => '',
            'twilio_auth_token' => '',
            'whatsapp_access_token' => '',
        ]);

        $response->assertRedirect(route('parameters.index'));

        $fresh = Parameter::first();
        $this->assertEquals('Dkript Core Updated Name', $fresh->system_name);
        $this->assertEquals('OriginalSecretMailPassword', $fresh->mail_password);
        $this->assertEquals('OriginalTwilioAuthToken', $fresh->twilio_auth_token);
        $this->assertEquals('OriginalWhatsAppAccessToken', $fresh->whatsapp_access_token);
    }

    /**
     * 13. Email password reset registra evento PASSWORD_RESET en AuditService y excluye secretos.
     */
    public function test_email_password_reset_is_audited_and_secrets_are_excluded(): void
    {
        $user = User::create([
            'name' => 'Email Reset User',
            'email' => 'reset-email@dkript.com',
            'password' => Hash::make('OldPassword123!'),
            'status' => 1,
        ]);

        $rawToken = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($rawToken),
            'created_at' => now(),
        ]);

        $newPassword = 'BrandNewPassword2026!';

        $response = $this->post(route('password.update-email'), [
            'token' => $rawToken,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Redirección y mensaje preservados
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Password cambia
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));

        // Audit PASSWORD_RESET/AUTH existe
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PASSWORD_RESET',
            'module' => 'AUTH',
            'user_id' => $user->id,
        ]);

        $auditLog = AuditLog::where('action', 'PASSWORD_RESET')
            ->where('module', 'AUTH')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals(['method' => 'EMAIL'], $auditLog->new_values);

        // No contiene password, password_confirmation ni token
        $allValues = array_merge((array) $auditLog->old_values, (array) $auditLog->new_values);
        $this->assertArrayNotHasKey('password', $allValues);
        $this->assertArrayNotHasKey('password_confirmation', $allValues);
        $this->assertArrayNotHasKey('token', $allValues);

        $jsonLog = json_encode($auditLog);
        $this->assertStringNotContainsString($newPassword, $jsonLog);
        $this->assertStringNotContainsString($rawToken, $jsonLog);
    }

    /**
     * 14. OTP password reset registra evento PASSWORD_RESET en AuditService y excluye secretos.
     */
    public function test_otp_password_reset_is_audited_and_secrets_are_excluded(): void
    {
        $user = User::create([
            'name' => 'OTP Reset User',
            'email' => 'reset-otp@dkript.com',
            'password' => Hash::make('OldPassword123!'),
            'status' => 1,
        ]);

        $newPassword = 'BrandNewOtpPass2026!';

        $response = $this->withSession([
            'password_reset_verified' => true,
            'password_reset_user_id' => $user->id,
            'otp_phone' => '+525512345678',
            'otp_channel' => 'whatsapp',
        ])->post(route('password.update'), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        // Redirección y mensaje preservados
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        // Password cambia
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));

        // Audit PASSWORD_RESET/AUTH existe
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PASSWORD_RESET',
            'module' => 'AUTH',
            'user_id' => $user->id,
        ]);

        $auditLog = AuditLog::where('action', 'PASSWORD_RESET')
            ->where('module', 'AUTH')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals(['method' => 'OTP', 'channel' => 'whatsapp'], $auditLog->new_values);

        // No contiene password, password_confirmation, otp_code ni token
        $allValues = array_merge((array) $auditLog->old_values, (array) $auditLog->new_values);
        $this->assertArrayNotHasKey('password', $allValues);
        $this->assertArrayNotHasKey('password_confirmation', $allValues);
        $this->assertArrayNotHasKey('otp_code', $allValues);
        $this->assertArrayNotHasKey('token', $allValues);

        $jsonLog = json_encode($auditLog);
        $this->assertStringNotContainsString($newPassword, $jsonLog);
    }
}
