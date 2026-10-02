<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\MenuOption;
use App\Models\Permission;
use App\Models\Parameter;
use Illuminate\Support\Facades\Hash;

use Illuminate\Foundation\Testing\RefreshDatabase;

class StarterKitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\DemoSeeder::class);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee(config('app.name', 'Dkript Core'));
        $response->assertSee('Acceso al Sistema');
    }

    public function test_super_admin_can_login_and_gets_full_rbac_session(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@dkript.com',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Verificar carga de sesión RBAC para Super Admin
        $response->assertSessionHas('myoptions');
        $response->assertSessionHas('mypermits', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
    }

    public function test_operator_user_gets_restricted_rbac_session(): void
    {
        $response = $this->post('/login', [
            'email' => 'demo@dkript.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Operador solo debe tener acceso a módulos 1 y 2
        $response->assertSessionHas('myoptions', [1, 2]);
        // Y permiso posición 4 (Ver)
        $response->assertSessionHas('mypermits', [4]);
    }

    public function test_operator_cannot_access_unauthorized_module_roles(): void
    {
        $operator = User::where('email', 'demo@dkript.com')->first();

        // Autenticar como operador y establecer su sesión
        $response = $this->actingAs($operator)
            ->withSession([
                'myoptions' => [1, 2],
                'mypermits' => [4],
            ])
            ->get('/roles');

        // Middleware VerifyOption redirige con error o deniega
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_super_admin_can_access_dashboard_and_roles(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)
            ->withSession([
                'myoptions' => [1, 2, 3, 4, 5],
                'mypermits' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            ])
            ->get('/roles');

        $response->assertStatus(200);
        $response->assertSee('Roles y Permisos RBAC');
    }

    public function test_super_admin_can_create_and_update_role(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        // Crear Rol
        $response = $this->actingAs($admin)
            ->post('/roles', [
                'name' => 'Auditor Financiero',
                'description' => 'Rol de prueba para auditorías',
                'status' => 1,
                'modules' => [1, 2],
                'permissions' => [4, 9], // Permisos de ver
            ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'Auditor Financiero']);

        $role = Role::where('name', 'Auditor Financiero')->first();
        $this->assertCount(2, $role->menuOptions);

        // Actualizar Rol
        $updateResponse = $this->actingAs($admin)
            ->put('/roles/' . $role->id, [
                'name' => 'Auditor Senior',
                'description' => 'Descripción actualizada',
                'status' => 1,
                'modules' => [1],
                'permissions' => [4],
            ]);

        $updateResponse->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'Auditor Senior']);
    }

    public function test_super_admin_can_create_user(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)
            ->post('/users', [
                'name' => 'CarlosDev',
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'email' => 'carlos@dkript.com',
                'password' => 'SecretPass123!',
                'role_id' => 2,
                'phone' => '555-9988',
                'status' => 1,
            ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'carlos@dkript.com']);
        $this->assertDatabaseHas('profiles', ['first_name' => 'Carlos', 'phone' => '555-9988']);
    }

    public function test_users_index_renders_with_create_user_modal_action(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('openCreateUserModal');
        $response->assertSee('modalUserForm');
        $response->assertSee('Nuevo Usuario');
    }

    public function test_super_admin_can_update_parameters(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)
            ->post('/parameters', [
                'system_name' => 'Dkript Enterprise Core',
                'system_logo' => 'assets/images/branding/logo-dkript.png',
                'contact_email' => 'contacto@dkript.com',
                'records_per_page' => 25,
                'maintenance_mode' => 0,
                'modal_style' => 'glassmorphism',
            ]);

        $response->assertRedirect(route('parameters.index'));
        $this->assertDatabaseHas('parameters', [
            'system_name' => 'Dkript Enterprise Core',
            'system_logo' => 'assets/images/branding/logo-dkript.png',
            'contact_email' => 'contacto@dkript.com',
            'records_per_page' => 25,
            'modal_style' => 'glassmorphism',
        ]);
    }

    public function test_modal_style_validation_rejects_invalid_values(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)
            ->post('/parameters', [
                'system_name' => 'Dkript Enterprise Core',
                'records_per_page' => 25,
                'maintenance_mode' => 0,
                'modal_style' => 'estilo_inexistente',
            ]);

        $response->assertSessionHasErrors('modal_style');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@dkript.com',
            'password' => Hash::make('password123'),
            'status' => 0,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactivo@dkript.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_upload_system_image(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $file = \Illuminate\Http\UploadedFile::fake()->image('custom_logo.png', 200, 200);

        $response = $this->actingAs($admin)
            ->postJson('/parameters/upload-image', [
                'image' => $file,
                'name' => 'Custom Brand Logo',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('system_images', [
            'name' => 'Custom Brand Logo',
        ]);
    }

    public function test_super_admin_can_upload_large_image_up_to_25mb(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        // Archivo simulado de 3.5 MB (supera el límite anterior de PHP de 2MB)
        $file = \Illuminate\Http\UploadedFile::fake()->create('large_logo.png', 3584, 'image/png');

        $response = $this->actingAs($admin)
            ->postJson('/parameters/upload-image', [
                'image' => $file,
                'name' => 'Large High-Res Brand Logo',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('system_images', [
            'name' => 'Large High-Res Brand Logo',
        ]);
    }

    public function test_super_admin_can_rename_system_image(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)
            ->postJson('/parameters/rename-image', [
                'path' => 'assets/images/branding/logo-dkript.png',
                'name' => 'Dkript Enterprise Master Logo',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Nombre de la imagen actualizado exitosamente.',
        ]);
        $this->assertDatabaseHas('system_images', [
            'path' => 'assets/images/branding/logo-dkript.png',
            'name' => 'Dkript Enterprise Master Logo',
        ]);
    }

    public function test_super_admin_can_delete_system_image_with_correct_password(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        // Crear una imagen de prueba en la base de datos
        \App\Models\SystemImage::create([
            'name' => 'Imagen Temporal',
            'path' => 'uploads/branding/temp_test_image.png',
            'is_preset' => false,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/parameters/delete-image', [
                'path' => 'uploads/branding/temp_test_image.png',
                'admin_password' => 'admin123',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'deleted_path' => 'uploads/branding/temp_test_image.png',
        ]);
        $this->assertDatabaseMissing('system_images', [
            'path' => 'uploads/branding/temp_test_image.png',
        ]);
    }

    public function test_delete_system_image_fails_with_incorrect_password(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        \App\Models\SystemImage::create([
            'name' => 'Imagen Protegida',
            'path' => 'uploads/branding/protected_image.png',
            'is_preset' => false,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/parameters/delete-image', [
                'path' => 'uploads/branding/protected_image.png',
                'admin_password' => 'wrongpassword',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Contraseña de administrador incorrecta.',
        ]);
        $this->assertDatabaseHas('system_images', [
            'path' => 'uploads/branding/protected_image.png',
        ]);
    }

    public function test_parameters_update_returns_json_for_ajax_request(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)
            ->postJson('/parameters', [
                'system_name' => 'Dkript Enterprise Corp',
                'system_logo' => 'assets/images/branding/logo-dkript.png',
                'contact_email' => 'support@dkript.com',
                'records_per_page' => 25,
                'maintenance_mode' => 0,
                'modal_style' => 'glassmorphism',
                'current_logo_name' => 'Logo Principal',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Parámetros del sistema actualizados exitosamente.',
            'parameter' => [
                'system_name' => 'Dkript Enterprise Corp',
                'modal_style' => 'glassmorphism',
                'records_per_page' => 25,
            ],
        ]);

        $this->assertDatabaseHas('parameters', [
            'system_name' => 'Dkript Enterprise Corp',
            'modal_style' => 'glassmorphism',
            'records_per_page' => 25,
        ]);
    }

    public function test_super_admin_can_toggle_show_brand_text(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)
            ->postJson('/parameters', [
                'system_name' => 'Dkript Enterprise Corp',
                'system_logo' => 'assets/images/branding/logo-dkript.png',
                'show_brand_text' => 0,
                'records_per_page' => 15,
                'maintenance_mode' => 0,
                'modal_style' => 'window',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'show_brand_text' => false,
            'modal_style' => 'window',
        ]);

        $this->assertDatabaseHas('parameters', [
            'show_brand_text' => 0,
            'modal_style' => 'window',
            'records_per_page' => 15,
        ]);

        $dashboardResponse = $this->actingAs($admin)->get('/dashboard');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('max-w-full');
    }

    public function test_users_index_respects_records_per_page_parameter(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        \App\Models\Parameter::updateOrCreate(
            ['id' => 1],
            [
                'system_name' => 'Test System',
                'records_per_page' => 5,
                'maintenance_mode' => 0,
                'modal_style' => 'corporate',
            ]
        );

        $response = $this->actingAs($admin)->get('/users');
        $response->assertOk();
        $response->assertViewHas('users', function ($users) {
            return $users->perPage() === 5;
        });
    }

    public function test_user_can_export_users_to_excel(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)->get(route('users.export.excel'));
        $response->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('content-type'));
        $this->assertStringContainsString('usuarios_', $response->headers->get('content-disposition'));
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('admin@dkript.com', $content);
        $this->assertStringNotContainsString('created_at', $content);
        $this->assertStringNotContainsString('updated_at', $content);
    }

    public function test_user_can_export_users_to_pdf(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)->get(route('users.export.pdf'));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_user_can_export_roles_to_excel_and_pdf(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $excelResponse = $this->actingAs($admin)->get(route('roles.export.excel'));
        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', $excelResponse->headers->get('content-type'));

        $pdfResponse = $this->actingAs($admin)->get(route('roles.export.pdf'));
        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('content-type'));
    }

    public function test_user_can_export_permissions_to_excel_and_pdf(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $excelResponse = $this->actingAs($admin)->get(route('permissions.export.excel'));
        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', $excelResponse->headers->get('content-type'));

        $pdfResponse = $this->actingAs($admin)->get(route('permissions.export.pdf'));
        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('content-type'));
    }

    public function test_error_views_exist_and_render_successfully(): void
    {
        $codes = ['404', '403', '500', '419', '503'];

        foreach ($codes as $code) {
            $rendered = view("errors.{$code}")->render();
            $this->assertNotEmpty($rendered);
            $this->assertStringContainsString('Drypt', $rendered);
            $this->assertStringContainsString('gsap', $rendered);
            $this->assertStringContainsString($code, $rendered);
        }
    }

    public function test_super_admin_can_access_error_preview_routes(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();
        $codes = ['404', '403', '500', '419', '503'];

        foreach ($codes as $code) {
            $response = $this->actingAs($admin)->get(route('parameters.errors.preview', $code));
            $response->assertOk();
            $response->assertSee('Drypt');
            $response->assertSee($code);
        }

        // Invalid code should return 404
        $invalidResponse = $this->actingAs($admin)->get(route('parameters.errors.preview', '999'));
        $invalidResponse->assertNotFound();
    }

    public function test_guest_cannot_access_error_preview_routes(): void
    {
        $response = $this->get(route('parameters.errors.preview', '404'));
        $response->assertRedirect('/login');
    }

    public function test_admin_layout_renders_data_modal_style_and_modal_card_classes(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        \App\Models\Parameter::updateOrCreate(
            ['id' => 1],
            [
                'system_name' => 'Dkript Test System',
                'modal_style' => 'glassmorphism',
                'records_per_page' => 10,
                'maintenance_mode' => 0,
            ]
        );

        $response = $this->actingAs($admin)->get('/users');
        $response->assertOk();
        $response->assertSee('data-modal-style="glassmorphism"', false);
        $response->assertSee('modal-card', false);
        $response->assertSee('modal-window-dots', false);
    }

    public function test_parameters_index_renders_settings_hub_with_7_tabs_and_form_inputs(): void
    {
        $admin = User::where('email', 'admin@dkript.com')->first();

        $response = $this->actingAs($admin)->get(route('parameters.index'));
        $response->assertOk();

        // Validar el formulario contenedor unificado
        $response->assertSee('id="parametersForm"', false);

        // Validar la barra superior (Top Dock flotante transparente) y tooltips superpuestos
        $response->assertSee('parameter-top-dock', false);
        $response->assertSee('parameter-dock-btn', false);
        $response->assertSee('parameter-dock-tooltip', false);

        // Validar las 7 pestañas temáticas en la barra de navegación
        $response->assertSee('data-tab-target="tab-general"', false);
        $response->assertSee('data-tab-target="tab-modals"', false);
        $response->assertSee('data-tab-target="tab-branding"', false);
        $response->assertSee('data-tab-target="tab-errors"', false);
        $response->assertSee('data-tab-target="tab-telephony"', false);
        $response->assertSee('data-tab-target="tab-mail"', false);
        $response->assertSee('data-tab-target="tab-maintenance"', false);

        // Validar títulos concisos aprobados en el menú de navegación
        $response->assertSee('>General<', false);
        $response->assertSee('>Modales<', false);
        $response->assertSee('>Identidad<', false);
        $response->assertSee('>Errores<', false);
        $response->assertSee('>Telefonía<', false);
        $response->assertSee('>Email<', false);
        $response->assertSee('>Mantenimiento<', false);

        // Validar la presencia de los 7 paneles contenedores
        $response->assertSee('id="tab-general"', false);
        $response->assertSee('id="tab-modals"', false);
        $response->assertSee('id="tab-branding"', false);
        $response->assertSee('id="tab-errors"', false);
        $response->assertSee('id="tab-telephony"', false);
        $response->assertSee('id="tab-mail"', false);
        $response->assertSee('id="tab-maintenance"', false);

        // Validar que los campos críticos del formulario persisten íntegros en el HTML
        $response->assertSee('name="system_name"', false);
        $response->assertSee('name="contact_email"', false);
        $response->assertSee('name="records_per_page"', false);
        $response->assertSee('name="session_timeout_minutes"', false);
        $response->assertSee('name="sms_provider"', false);
        $response->assertSee('name="mail_mailer"', false);
    }
}

