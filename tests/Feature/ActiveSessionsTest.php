<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Services\DeviceDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActiveSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Role $role;

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

        Profile::create([
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'phone' => '+525512345678',
        ]);

        $this->otherUser = User::create([
            'name' => 'Operator User',
            'email' => 'operator@dkript.com',
            'password' => Hash::make('password123'),
            'status' => 1,
        ]);

        Profile::create([
            'user_id' => $this->otherUser->id,
            'role_id' => $this->role->id,
            'first_name' => 'Operator',
            'last_name' => 'User',
            'phone' => '+525587654321',
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

    public function test_unauthenticated_user_cannot_access_sessions_api(): void
    {
        $response = $this->getJson(route('user.sessions.index'));
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_active_sessions(): void
    {
        $currentSessionId = 'current_session_123';
        $remoteSessionId = 'remote_session_456';

        // Sesión actual (Windows - Chrome)
        DB::table('sessions')->insert([
            'id' => $currentSessionId,
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'payload' => 'dummy_payload',
            'last_activity' => time(),
        ]);

        // Sesión remota (iPhone - Safari)
        DB::table('sessions')->insert([
            'id' => $remoteSessionId,
            'user_id' => $this->user->id,
            'ip_address' => '10.0.0.5',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'payload' => 'dummy_payload_2',
            'last_activity' => time() - 3600,
        ]);

        // Sesión de otro usuario que NO debe aparecer
        DB::table('sessions')->insert([
            'id' => 'other_user_session_789',
            'user_id' => $this->otherUser->id,
            'ip_address' => '172.16.0.2',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0',
            'payload' => 'dummy_payload_3',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test_token'])
            ->getJson(route('user.sessions.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'current_session_id',
            'total_sessions',
            'sessions' => [
                '*' => [
                    'id',
                    'ip_address',
                    'is_current',
                    'device_type',
                    'device_name',
                    'platform',
                    'browser',
                    'icon',
                    'last_activity_timestamp',
                    'last_active_human',
                    'last_active_formatted',
                ],
            ],
        ]);

        $sessions = $response->json('sessions');
        $this->assertCount(2, $sessions);

        // Verificamos detección de iPhone
        $iphoneSession = collect($sessions)->firstWhere('id', $remoteSessionId);
        $this->assertNotNull($iphoneSession);
        $this->assertEquals('iOS (iPhone)', $iphoneSession['platform']);
        $this->assertEquals('Apple Safari', $iphoneSession['browser']);
        $this->assertEquals('phone', $iphoneSession['device_type']);
        $this->assertEquals('bi-phone', $iphoneSession['icon']);
    }

    public function test_user_cannot_revoke_current_session_via_destroy(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson(route('user.sessions.destroy', ['sessionId' => 'current']));

        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
            'message' => 'No puedes revocar tu sesión activa actual desde este botón. Utiliza "Cerrar Sesión".',
        ]);
    }

    public function test_user_can_revoke_remote_session(): void
    {
        $remoteSessionId = 'remote_session_to_delete';

        DB::table('sessions')->insert([
            'id' => $remoteSessionId,
            'user_id' => $this->user->id,
            'ip_address' => '10.0.0.99',
            'user_agent' => 'Android Phone',
            'payload' => 'payload',
            'last_activity' => time() - 500,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('user.sessions.destroy', ['sessionId' => $remoteSessionId]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'El dispositivo ha sido desconectado exitosamente.',
        ]);

        $this->assertDatabaseMissing('sessions', [
            'id' => $remoteSessionId,
        ]);
    }

    public function test_user_cannot_revoke_session_belonging_to_another_user(): void
    {
        $otherSessionId = 'session_of_other_user';

        DB::table('sessions')->insert([
            'id' => $otherSessionId,
            'user_id' => $this->otherUser->id,
            'ip_address' => '10.0.0.88',
            'user_agent' => 'Other Phone',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('user.sessions.destroy', ['sessionId' => $otherSessionId]));

        $response->assertStatus(404);
        $this->assertDatabaseHas('sessions', [
            'id' => $otherSessionId,
        ]);
    }

    public function test_user_can_logout_other_devices_with_valid_password(): void
    {
        // Sesión remota 1
        DB::table('sessions')->insert([
            'id' => 'remote_1',
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.50',
            'user_agent' => 'Chrome Windows',
            'payload' => 'test',
            'last_activity' => time() - 100,
        ]);

        // Sesión remota 2
        DB::table('sessions')->insert([
            'id' => 'remote_2',
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.60',
            'user_agent' => 'Safari Mac',
            'payload' => 'test',
            'last_activity' => time() - 200,
        ]);

        // Sesión de otro usuario
        DB::table('sessions')->insert([
            'id' => 'other_user_sess',
            'user_id' => $this->otherUser->id,
            'ip_address' => '192.168.1.70',
            'user_agent' => 'Firefox Linux',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('user.sessions.logout-others'), [
                'password' => 'password123',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'revoked_count' => 2,
        ]);

        $this->assertDatabaseMissing('sessions', ['id' => 'remote_1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'remote_2']);
        $this->assertDatabaseHas('sessions', ['id' => 'other_user_sess']);
    }

    public function test_user_cannot_logout_other_devices_with_wrong_password(): void
    {
        DB::table('sessions')->insert([
            'id' => 'remote_protected',
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.50',
            'user_agent' => 'Chrome Windows',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('user.sessions.logout-others'), [
                'password' => 'incorrectpassword',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('sessions', ['id' => 'remote_protected']);
    }

    public function test_device_detector_service_parsing(): void
    {
        // 1. Windows Chrome
        $resWin = DeviceDetectorService::parse('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36');
        $this->assertEquals('Windows', $resWin['platform']);
        $this->assertEquals('Google Chrome', $resWin['browser']);
        $this->assertEquals('desktop', $resWin['device_type']);
        $this->assertEquals('bi-laptop', $resWin['icon']);

        // 2. iPhone Safari
        $resIos = DeviceDetectorService::parse('Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1');
        $this->assertEquals('iOS (iPhone)', $resIos['platform']);
        $this->assertEquals('Apple Safari', $resIos['browser']);
        $this->assertEquals('phone', $resIos['device_type']);
        $this->assertEquals('bi-phone', $resIos['icon']);

        // 3. Android Edge
        $resAndroid = DeviceDetectorService::parse('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36 EdgA/128.0.0.0');
        $this->assertEquals('Android', $resAndroid['platform']);
        $this->assertEquals('Microsoft Edge', $resAndroid['browser']);
        $this->assertEquals('phone', $resAndroid['device_type']);

        // 4. Null / Empty fallback
        $resEmpty = DeviceDetectorService::parse(null);
        $this->assertEquals('Desconocido', $resEmpty['platform']);
        $this->assertEquals('desktop', $resEmpty['device_type']);
    }
}
