<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Parameter;
use App\Notifications\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $this->superAdmin = User::where('email', 'admin@dkript.com')->first();
        $this->regularUser = User::where('email', 'demo@dkript.com')->first();
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $response = $this->get(route('notifications.index'));
        $response->assertRedirect(route('login'));

        $jsonResponse = $this->getJson(route('notifications.index'));
        $jsonResponse->assertStatus(401);
    }

    public function test_authenticated_user_can_view_notifications_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('notifications.index'));
        $response->assertStatus(200);
        $response->assertSee('Centro de Notificaciones');
    }

    public function test_authenticated_user_can_view_notifications_json(): void
    {
        NotificationService::send(
            $this->superAdmin,
            'Test Notif Title',
            'Test message for user',
            'info'
        );

        $response = $this->actingAs($this->superAdmin)->getJson(route('notifications.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'unread_count',
            'notifications' => [
                '*' => [
                    'id',
                    'title',
                    'message',
                    'type',
                    'icon',
                    'action_url',
                    'read_at',
                    'is_read',
                    'created_at_human',
                ]
            ]
        ]);

        $this->assertEquals(1, $response->json('unread_count'));
        $this->assertEquals('Test Notif Title', $response->json('notifications.0.title'));
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        NotificationService::send(
            $this->superAdmin,
            'Unread Notice',
            'Unread content',
            'warning'
        );

        $notification = $this->superAdmin->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($this->superAdmin)->postJson(route('notifications.read', $notification->id));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'unread_count' => 0,
        ]);

        $this->assertEquals(0, $this->superAdmin->fresh()->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        NotificationService::send(
            $this->superAdmin,
            'SuperAdmin Secret',
            'Confidential alert',
            'security'
        );

        $adminNotification = $this->superAdmin->unreadNotifications()->first();

        // El usuario regular intenta marcar la notificación del superAdmin
        $response = $this->actingAs($this->regularUser)->postJson(route('notifications.read', $adminNotification->id));
        $response->assertStatus(404);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        NotificationService::send($this->superAdmin, 'Notif 1', 'Content 1');
        NotificationService::send($this->superAdmin, 'Notif 2', 'Content 2');
        NotificationService::send($this->superAdmin, 'Notif 3', 'Content 3');

        $this->assertEquals(3, $this->superAdmin->unreadNotifications()->count());

        $response = $this->actingAs($this->superAdmin)->postJson(route('notifications.read-all'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'unread_count' => 0,
        ]);

        $this->assertEquals(0, $this->superAdmin->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_delete_single_notification(): void
    {
        NotificationService::send($this->superAdmin, 'To Delete', 'Message to delete');
        $notification = $this->superAdmin->notifications()->first();

        $response = $this->actingAs($this->superAdmin)->deleteJson(route('notifications.destroy', $notification->id));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_user_can_clear_all_notifications(): void
    {
        NotificationService::send($this->superAdmin, 'A', 'Msg A');
        NotificationService::send($this->superAdmin, 'B', 'Msg B');

        $this->assertEquals(2, $this->superAdmin->notifications()->count());

        $response = $this->actingAs($this->superAdmin)->deleteJson(route('notifications.clear-all'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'unread_count' => 0,
        ]);

        $this->assertEquals(0, $this->superAdmin->fresh()->notifications()->count());
    }

    public function test_notification_service_broadcast_to_admins(): void
    {
        // Limpiamos notificaciones previas
        $this->superAdmin->notifications()->delete();
        $this->regularUser->notifications()->delete();

        NotificationService::broadcastToAdmins(
            'Alerta Global para Admins',
            'Mensaje de prueba para Super Administradores',
            'system'
        );

        // SuperAdmin debe haber recibido la notificación
        $this->assertEquals(1, $this->superAdmin->fresh()->notifications()->count());
        $this->assertEquals('Alerta Global para Admins', $this->superAdmin->fresh()->notifications()->first()->data['title']);

        // Usuario regular (rol no 1) NO debe haber recibido la notificación
        $this->assertEquals(0, $this->regularUser->fresh()->notifications()->count());
    }

    public function test_parameter_update_triggers_admin_notification(): void
    {
        $this->superAdmin->notifications()->delete();

        $response = $this->actingAs($this->superAdmin)->post(route('parameters.update'), [
            'system_name' => 'Dkript Enterprise Test',
            'records_per_page' => 15,
            'maintenance_mode' => '0',
            'modal_style' => 'corporate',
        ]);

        $response->assertSessionHasNoErrors();

        // Debe existir notificación de parámetros
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->superAdmin->id,
            'type' => SystemNotification::class,
        ]);

        $latestNotif = $this->superAdmin->fresh()->notifications()->first();
        $this->assertEquals('Parámetros Actualizados', $latestNotif->data['title']);
        $this->assertStringContainsString('audit-logs', $latestNotif->data['action_url']);
        $this->assertStringContainsString('detail=', $latestNotif->data['action_url']);
    }

    public function test_notifications_index_resolves_legacy_parameter_url_to_audit_incident(): void
    {
        $this->superAdmin->notifications()->delete();

        // Crear una auditoría de sistema
        $audit = \App\Services\AuditService::log('SETTINGS', 'SYSTEM', 'Actualización de prueba');

        // Enviar notificación con action_url heredado hacia /parameters
        NotificationService::send(
            $this->superAdmin,
            'Parámetros Actualizados',
            'Modificación previa',
            'system',
            route('parameters.index')
        );

        // Al consultar JSON de notificaciones, debe resolverlo automáticamente a audit-logs con detail
        $response = $this->actingAs($this->superAdmin)->getJson(route('notifications.index'));
        $response->assertStatus(200);

        $notifications = $response->json('notifications');
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('audit-logs', $notifications[0]['action_url']);
        $this->assertStringContainsString('detail=' . $audit->id, $notifications[0]['action_url']);
    }

    public function test_audit_logs_index_prioritizes_detail_id_even_with_search_filter(): void
    {
        $auditTarget = \App\Services\AuditService::log('SETTINGS', 'SYSTEM', 'Registro específico buscado');
        $auditOther = \App\Services\AuditService::log('LOGIN', 'AUTH', 'Otro evento');

        // Petición con detail y búsqueda que no coincida con el target
        $response = $this->actingAs($this->superAdmin)->get(route('audit-logs.index', [
            'detail' => $auditTarget->id,
            'search' => 'InexistenteTerminoXYZ',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Registro específico buscado');
    }
}
