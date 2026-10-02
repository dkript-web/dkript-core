<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Parameter;
use App\Models\User;
use App\Services\Messaging\WhatsAppCloudDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppCloudDriverTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
        $this->restrictedUser = User::where('email', 'demo@dkript.com')->first();
    }

    public function test_guest_cannot_test_whatsapp()
    {
        $response = $this->post(route('parameters.test-whatsapp'), [
            'test_phone' => '5215512345678',
        ]);
        $response->assertRedirect(route('login'));

        $jsonResponse = $this->postJson(route('parameters.test-whatsapp'), [
            'test_phone' => '5215512345678',
        ]);
        $jsonResponse->assertStatus(401);
    }

    public function test_user_without_permission_cannot_test_whatsapp()
    {
        $response = $this->actingAs($this->restrictedUser)->post(route('parameters.test-whatsapp'), [
            'test_phone' => '5215512345678',
        ]);
        $response->assertRedirect(route('dashboard'));

        $jsonResponse = $this->actingAs($this->restrictedUser)->postJson(route('parameters.test-whatsapp'), [
            'test_phone' => '5215512345678',
        ]);
        $jsonResponse->assertStatus(403);
    }

    public function test_super_admin_can_send_test_whatsapp_in_simulator_mode()
    {
        $parameter = Parameter::first();
        $parameter->sms_provider = 'log';
        $parameter->save();

        $response = $this->actingAs($this->superAdmin)->postJson(route('parameters.test-whatsapp'), [
            'test_phone' => '5215512345678',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'provider' => 'log',
        ]);
        $this->assertStringContainsString('Simulación exitosa', $response->json('message'));
    }

    public function test_whatsapp_cloud_driver_sends_message_successfully()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/109876543210987/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => '5215512345678',
                        'wa_id' => '5215512345678',
                    ],
                ],
                'messages' => [
                    [
                        'id' => 'wamid.HBgLMjUxMjM0NTY3OAIFAAMAAyA=',
                    ],
                ],
            ], 200),
        ]);

        $driver = new WhatsAppCloudDriver(
            '109876543210987',
            'EAAG_TEST_TOKEN_1234567890',
            '987654321098765',
            'v20.0'
        );

        $result = $driver->sendOtp('5215512345678', '948123');

        $this->assertTrue($result['success']);
        $this->assertEquals('meta_whatsapp', $result['provider']);
        $this->assertEquals('wamid.HBgLMjUxMjM0NTY3OAIFAAMAAyA=', $result['message_id']);
        $this->assertEquals('948123', $result['code']);
    }

    public function test_whatsapp_cloud_driver_handles_meta_error_response()
    {
        Http::fake([
            'https://graph.facebook.com/v20.0/109876543210987/messages' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token - Cannot parse access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'fbtrace_id' => 'ArJ9w1K2L3M4N5P',
                ],
            ], 401),
        ]);

        $driver = new WhatsAppCloudDriver(
            '109876543210987',
            'INVALID_TOKEN',
            '987654321098765',
            'v20.0'
        );

        $result = $driver->sendOtp('5215512345678', '948123');

        $this->assertFalse($result['success']);
        $this->assertEquals('meta_whatsapp', $result['provider']);
        $this->assertStringContainsString('Invalid OAuth access token', $result['message']);
    }

    public function test_whatsapp_cloud_driver_fails_when_credentials_are_missing()
    {
        $driver = new WhatsAppCloudDriver(null, null);
        $result = $driver->sendOtp('5215512345678', '123456');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('incompletas', $result['message']);
    }

    public function test_test_whatsapp_endpoint_records_audit_log()
    {
        $initialCount = AuditLog::count();

        $this->actingAs($this->superAdmin)->postJson(route('parameters.test-whatsapp'), [
            'test_phone' => '5215512345678',
        ]);

        $this->assertGreaterThan($initialCount, AuditLog::count());

        $latestLog = AuditLog::latest('id')->first();
        $this->assertEquals('SETTINGS', $latestLog->action);
        $this->assertEquals('SYSTEM', $latestLog->module);
        $this->assertStringContainsString('5215512345678', $latestLog->description);
    }

    public function test_parameters_update_saves_whatsapp_cloud_credentials()
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('parameters.update'), [
            'system_name' => 'Dkript Enterprise QA',
            'records_per_page' => 10,
            'maintenance_mode' => 0,
            'modal_style' => 'corporate',
            'sms_provider' => 'meta_whatsapp',
            'whatsapp_phone_number_id' => '109876543210987',
            'whatsapp_access_token' => 'EAAG_SECRET_TOKEN_XYZ',
            'whatsapp_business_account_id' => '987654321098765',
            'whatsapp_api_version' => 'v20.0',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $parameter = Parameter::first();
        $this->assertEquals('meta_whatsapp', $parameter->sms_provider);
        $this->assertEquals('109876543210987', $parameter->whatsapp_phone_number_id);
        $this->assertEquals('EAAG_SECRET_TOKEN_XYZ', $parameter->whatsapp_access_token);
        $this->assertEquals('987654321098765', $parameter->whatsapp_business_account_id);
    }
}
