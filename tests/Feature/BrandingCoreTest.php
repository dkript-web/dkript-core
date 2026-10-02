<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\Profile;
use App\Models\User;
use App\Services\BrandingService;
use Database\Seeders\CoreSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BrandingCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed structural core data
        $this->seed(CoreSeeder::class);
    }

    /**
     * 1. Core limpio toma "Dkript Core" por defecto si no hay branding personalizado (Fallback de 3 niveles).
     */
    public function test_clean_core_defaults_to_dkript_core_as_fallback(): void
    {
        // Limpiamos parámetros en DB para probar fallback
        Parameter::query()->delete();

        // Nivel 2: Si no hay DB pero config('app.name') tiene un valor
        Config::set('app.name', 'Sistema Base');
        $this->assertEquals('Sistema Base', BrandingService::name());

        // Nivel 3: Si no hay DB y app.name es null o Laravel
        Config::set('app.name', null);
        $this->assertEquals('Dkript Core', BrandingService::name());

        Config::set('app.name', 'Laravel');
        $this->assertEquals('Dkript Core', BrandingService::name());

        // Nivel 1: Cuando existe registro en DB con system_name, tiene máxima prioridad
        Parameter::create([
            'system_name' => 'Empresa Prioridad DB',
            'system_logo' => null,
            'contact_email' => null,
            'records_per_page' => 10,
            'session_timeout_minutes' => 15,
            'maintenance_mode' => 0,
            'mail_mailer' => 'log',
            'mail_host' => '127.0.0.1',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Empresa Prioridad DB',
        ]);

        Config::set('app.name', 'Nombre Ignorado de Config');
        $this->assertEquals('Empresa Prioridad DB', BrandingService::name());
    }

    /**
     * 2. (Requisito 9.A, 9.B, 9.C): Sin Parameter y sin preset, los defaults comerciales son neutros.
     */
    public function test_clean_core_has_neutral_commercial_defaults_without_parameter_or_preset(): void
    {
        Parameter::query()->delete();

        // 9.A: BrandingService::name() respeta config('app.name') o fallback neutral
        $expectedName = config('app.name', 'Dkript Core');
        $this->assertEquals($expectedName, BrandingService::name());

        // 9.B: BrandingService::logo() -> null, NO logo-dkript.png
        $this->assertNull(BrandingService::logo());
        $this->assertNull(BrandingService::logoUrl());
        $this->assertFalse(BrandingService::hasLogo());
        $this->assertNotEquals('assets/images/branding/logo-dkript.png', BrandingService::logo());

        // 9.C: BrandingService::contactEmail() -> null, NO soporte@dkript.com
        $this->assertNull(BrandingService::contactEmail());
        $this->assertNotEquals('soporte@dkript.com', BrandingService::contactEmail());

        // Icono y empresa neutros
        $this->assertNull(BrandingService::icon());
        $this->assertNull(BrandingService::iconUrl());
        $this->assertFalse(BrandingService::hasIcon());
        $this->assertNull(BrandingService::companyName());

        // Iniciales neutras derivadas del nombre configurado
        $words = preg_split('/\s+/', trim($expectedName));
        $expectedInitials = count($words) >= 2 ? mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1)) : mb_strtoupper(mb_substr($expectedName, 0, 2));
        $this->assertEquals($expectedInitials, BrandingService::initials());
    }

    /**
     * 3. (Requisito 9.D, 9.E): CoreSeeder nuevo genera parámetros estrictamente neutros.
     */
    public function test_core_seeder_creates_strictly_neutral_parameters(): void
    {
        Parameter::query()->delete();

        // Ejecución de CoreSeeder
        $this->seed(CoreSeeder::class);

        $parameter = Parameter::first();
        $this->assertNotNull($parameter);
        $this->assertEquals(config('app.name', 'Dkript Core'), $parameter->system_name);

        // 9.D: NO introduce logo-dkript.png (es null)
        $this->assertNull($parameter->system_logo);
        $this->assertNotEquals('assets/images/branding/logo-dkript.png', $parameter->system_logo);

        // 9.E: NO introduce soporte@dkript.com (es null)
        $this->assertNull($parameter->contact_email);
        $this->assertNotEquals('soporte@dkript.com', $parameter->contact_email);
    }

    /**
     * 4. Modificar system_name en Parameter cambia el nombre mostrado en el sistema.
     */
    public function test_custom_system_name_in_parameter_updates_displayed_name(): void
    {
        $param = Parameter::first();
        $param->system_name = 'Plataforma Corporativa Orion';
        $param->save();

        $this->assertEquals('Plataforma Corporativa Orion', BrandingService::name());
        $this->assertEquals('Plataforma Corporativa Orion', Parameter::getSystemSettings()->system_name);
        $this->assertEquals('PC', BrandingService::initials());
    }

    /**
     * 5. El logo personalizado se utiliza cuando está presente.
     */
    public function test_custom_logo_is_used_when_present(): void
    {
        $param = Parameter::first();
        $param->system_logo = 'uploads/branding/custom-logo-2026.png';
        $param->save();

        $this->assertEquals('uploads/branding/custom-logo-2026.png', BrandingService::logo());
        $this->assertEquals(asset('uploads/branding/custom-logo-2026.png'), BrandingService::logoUrl());
        $this->assertTrue(BrandingService::hasLogo());
    }

    /**
     * 6. La ausencia de logo personalizado utiliza fallback neutro sin asumir identidad Dkript.
     */
    public function test_absence_of_custom_logo_uses_neutral_fallback_without_dkript_commercial_identity(): void
    {
        $param = Parameter::first();
        $param->system_logo = null;
        $param->save();

        $this->assertNull(BrandingService::logo());
        $this->assertNull(BrandingService::logoUrl());
        $this->assertFalse(BrandingService::hasLogo());
        $this->assertNotEquals('assets/images/branding/logo-dkript.png', BrandingService::logo());
    }

    /**
     * 7. La vista de Login muestra el fallback visual neutro (iniciales) cuando el logo es null.
     */
    public function test_login_view_renders_neutral_initial_squircle_when_logo_is_null(): void
    {
        $param = Parameter::first();
        $param->system_name = 'Plataforma Alpha';
        $param->system_logo = null;
        $param->save();

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Plataforma Alpha');
        // El logo oficial Dkript NO se renderiza cuando logo es null
        $response->assertDontSee('assets/images/branding/logo-dkript.png');
        // Debe mostrar la inicial 'P'
        $response->assertSee('P');
    }

    /**
     * 8. La vista de Login renderiza el logotipo personalizado cuando existe.
     */
    public function test_login_view_renders_custom_logo_when_configured(): void
    {
        $param = Parameter::first();
        $param->system_name = 'Portal Bancario Seguro';
        $param->system_logo = 'uploads/logos/bank-shield.png';
        $param->save();

        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Portal Bancario Seguro');
        $response->assertSee('uploads/logos/bank-shield.png');
    }

    /**
     * 9. El layout general (sidebar) utiliza fallback neutro cuando el logo es null.
     */
    public function test_layout_sidebar_renders_neutral_fallback_when_logo_is_null(): void
    {
        $adminUser = User::factory()->create([
            'email' => 'superadmin.test@dkript.io',
        ]);

        Profile::create([
            'user_id' => $adminUser->id,
            'role_id' => 1, // Super Administrador
            'first_name' => 'Admin',
            'last_name' => 'Tester',
        ]);

        $param = Parameter::first();
        $param->system_name = 'Enterprise Shield Core';
        $param->system_logo = null;
        $param->save();

        $response = $this->actingAs($adminUser)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Enterprise Shield Core');
        $response->assertDontSee('assets/images/branding/logo-dkript.png');
        $response->assertDontSee('Dkript Enterprise');
    }

    /**
     * 10. La exportación PDF utiliza el branding configurable.
     */
    public function test_pdf_export_uses_configurable_branding(): void
    {
        $param = Parameter::first();
        $param->system_name = 'Reportes Globales S.A.';
        $param->save();

        $renderedHtml = view('reports.layout', [
            'reportTitle' => 'Informe de Gestión Anual',
            'records' => [],
            'headers' => ['ID', 'Nombre'],
            'fields' => ['id', 'name'],
            'systemParameter' => $param,
            'reportGeneratedAt' => '30/09/2026 12:00:00',
            'reportEmittedBy' => 'Admin Test',
        ])->render();

        $this->assertStringContainsString('Reportes Globales S.A.', $renderedHtml);
    }

    /**
     * 11. No existe "Dkript Enterprise" hardcodeado en vistas normales de la interfaz de usuario.
     */
    public function test_no_hardcoded_dkript_enterprise_in_ui_views(): void
    {
        $viewsToCheck = [
            resource_path('views/layouts/admin.blade.php'),
            resource_path('views/partials/layouts/sidebar.blade.php'),
            resource_path('views/auth/login.blade.php'),
            resource_path('views/reports/layout.blade.php'),
            resource_path('views/errors/layout.blade.php'),
        ];

        foreach ($viewsToCheck as $viewPath) {
            $this->assertFileExists($viewPath);
            $content = file_get_contents($viewPath);
            $this->assertStringNotContainsString(
                'Dkript Enterprise',
                $content,
                "Se detectó 'Dkript Enterprise' hardcodeado en {$viewPath}"
            );
        }
    }

    /**
     * 12. El instalador (dkript:install) sincroniza el nombre de la app inicial con Parameter.system_name.
     */
    public function test_installer_synchronizes_initial_app_name_with_parameter(): void
    {
        $tempEnv = tempnam(sys_get_temp_dir(), 'dkript_branding_test_env_');
        file_put_contents($tempEnv, "APP_NAME=OldAppName\n");

        try {
            $exitCode = $this->artisan('dkript:install', [
                '--no-interaction' => true,
                '--db-connection' => 'sqlite',
                '--db-database' => ':memory:',
                '--admin-name' => 'Instalador Admin',
                '--admin-email' => 'installer.admin@example.com',
                '--admin-password' => 'SecurePass2026!#',
                '--app-name' => 'SaaS Logistics Core',
                '--env-file' => $tempEnv,
                '--skip-migration' => true,
            ])->run();

            $this->assertSame(0, $exitCode);

            $parameter = Parameter::first();
            $this->assertNotNull($parameter);
            $this->assertEquals('SaaS Logistics Core', $parameter->system_name);
            $this->assertEquals('SaaS Logistics Core', $parameter->mail_from_name);
        } finally {
            if (file_exists($tempEnv)) {
                @unlink($tempEnv);
            }
        }
    }

    /**
     * 13. (Requisito 9.F, 9.G): applyDkriptPreset() aplica explícitamente el branding Dkript y la UI lo utiliza.
     */
    public function test_apply_dkript_preset_explicitly_configures_dkript_branding(): void
    {
        $param = Parameter::first();
        $param->system_name = 'Custom App Neutral';
        $param->system_logo = null;
        $param->contact_email = null;
        $param->save();

        // 9.F: applyDkriptPreset() SÍ aplica logo Dkript y contacto Dkript
        $result = BrandingService::applyDkriptPreset();
        $this->assertInstanceOf(Parameter::class, $result);

        $fresh = Parameter::first();
        $this->assertEquals('Dkript Enterprise', $fresh->system_name);
        $this->assertEquals('assets/images/branding/logo-dkript.png', $fresh->system_logo);
        $this->assertEquals('soporte@dkript.com', $fresh->contact_email);
        $this->assertEquals('Dkript Enterprise', $fresh->mail_from_name);

        // 9.G: Después de aplicar preset, la UI utiliza el branding Dkript
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('assets/images/branding/logo-dkript.png');
        $response->assertSee('Dkript Enterprise');

        // Idempotencia: segunda llamada mantiene el mismo estado
        $result2 = BrandingService::applyDkriptPreset();
        $this->assertInstanceOf(Parameter::class, $result2);
        $this->assertSame($fresh->id, Parameter::first()->id);
    }

    /**
     * 14. (Requisito 9.H): DemoSeeder continúa existiendo y se ejecuta explícitamente.
     */
    public function test_demo_seeder_continues_to_exist_and_functions(): void
    {
        $this->assertTrue(
            class_exists(DemoSeeder::class),
            'DemoSeeder no existe o fue eliminado indebidamente.'
        );

        User::query()->delete();

        // Ejecución explícita de DemoSeeder
        $this->seed(DemoSeeder::class);

        $this->assertTrue(User::where('email', 'admin@dkript.com')->exists());
        $this->assertTrue(User::where('email', 'demo@dkript.com')->exists());
        $this->assertSame(2, User::count());
    }

    /**
     * 15. DatabaseSeeder NO ejecuta DemoSeeder.
     */
    public function test_database_seeder_does_not_run_demo_seeder(): void
    {
        User::query()->delete();

        // Ejecutar DatabaseSeeder por defecto
        $this->seed(DatabaseSeeder::class);

        $this->assertFalse(User::where('email', 'admin@dkript.com')->exists());
        $this->assertFalse(User::where('email', 'demo@dkript.com')->exists());
        $this->assertSame(0, User::count(), 'DatabaseSeeder no debe crear usuarios por defecto.');
    }

    /**
     * 16. Los assets de la Demo oficial (24 archivos multimedia) existen y no fueron eliminados.
     */
    public function test_demo_assets_exist_and_were_not_deleted(): void
    {
        $demoAssets = [
            // 7 Videos MP4
            'assets/images/animations/mp4/drypt-403-shield.mp4',
            'assets/images/animations/mp4/drypt-404-scanning.mp4',
            'assets/images/animations/mp4/drypt-419-conjuring.mp4',
            'assets/images/animations/mp4/drypt-500-overload.mp4',
            'assets/images/animations/mp4/drypt-503-stasis.mp4',
            'assets/images/animations/mp4/drypt-orb-welcome-red.mp4',
            'assets/images/animations/mp4/drypt-orb-welcome.mp4',

            // 7 Animaciones WebP
            'assets/images/animations/webp/Cybernetic_dragon_conjuring_ener_1080p_20260919042339-ezgif.com-video-to-webp-converter.webp',
            'assets/images/animations/webp/Cybernetic_dragon_floating_in_st_20260921015753-ezgif.com-video-to-webp-converter.webp',
            'assets/images/animations/webp/Cybernetic_dragon_manipulating_g_1080p_20260919042919-ezgif.com-video-to-webp-converter.webp',
            'assets/images/animations/webp/Cybernetic_dragon_manipulating_g_1080p_20260919043429-ezgif.com-video-to-webp-converter.webp',
            'assets/images/animations/webp/Cybernetic_dragon_scanning_with__20260919042012-ezgif.com-video-to-webp-converter.webp',
            'assets/images/animations/webp/Dragon_alchemist_core_overloads_1080p_20260919042612-ezgif.com-video-to-webp-converter.webp',
            'assets/images/animations/webp/ezgif.com-video-to-webp-converter.webp',

            // 10 Gráficos y Branding PNG
            'assets/images/branding/banner-drypt.png',
            'assets/images/branding/bg-developer-workspace.png',
            'assets/images/branding/drypt-oficial.png',
            'assets/images/branding/drypt-the-digital-alchemist.png',
            'assets/images/branding/icon-dkript.png',
            'assets/images/branding/icon-drypt-front.png',
            'assets/images/branding/icon-drypt-side.png',
            'assets/images/branding/icon-drypt-single.png',
            'assets/images/branding/logo-dkript-profile.png',
            'assets/images/branding/logo-dkript.png',
        ];

        $this->assertCount(24, $demoAssets);

        foreach ($demoAssets as $assetPath) {
            $fullPath = public_path($assetPath);
            $this->assertFileExists(
                $fullPath,
                "El asset oficial de la Demo '{$assetPath}' no se encuentra en public/assets/images/"
            );
        }
    }

    /**
     * 17. (Microcorrección 1.4): En un Core neutro, el layout de error NO contiene "Dkript Inc." ni "Drypt The Digital Alchemist".
     */
    public function test_neutral_core_error_layout_does_not_contain_commercial_branding(): void
    {
        Parameter::query()->delete();

        // Renderizado de la vista de error 404 en entorno neutro
        $renderedHtml = view('errors.404', [
            'globalSystemParameter' => Parameter::getSystemSettings(),
        ])->render();

        // No debe contener "Dkript Inc." ni "Drypt The Digital Alchemist"
        $this->assertStringNotContainsString('Dkript Inc.', $renderedHtml);
        $this->assertStringNotContainsString('Drypt The Digital Alchemist', $renderedHtml);

        // Debe contener el nombre neutro o configurado de la aplicación
        $this->assertStringContainsString(config('app.name', 'Dkript Core'), $renderedHtml);
    }

    /**
     * 18. (Microcorrección 1.4): Con el preset Dkript aplicado, el layout de error muestra la identidad corporativa Dkript.
     */
    public function test_dkript_branded_error_layout_displays_drypt_and_dkript_inc(): void
    {
        BrandingService::applyDkriptPreset();

        $renderedHtml = view('errors.404', [
            'globalSystemParameter' => Parameter::first(),
        ])->render();

        $this->assertStringContainsString('Dkript Inc.', $renderedHtml);
        $this->assertStringContainsString('Drypt The Digital Alchemist', $renderedHtml);
        $this->assertStringContainsString('Dkript Enterprise', $renderedHtml);
    }

    /**
     * 19. (Microcorrección 1.4): BrandingService::isDkriptBranded() y resolución segura de icon y companyName desde config.
     */
    public function test_branding_service_is_dkript_branded_and_preset_resolution(): void
    {
        // 1. Estado Neutro
        Parameter::query()->delete();
        $this->assertFalse(BrandingService::isDkriptBranded());
        $this->assertNull(BrandingService::companyName());
        $this->assertNull(BrandingService::icon());

        // 2. Estado Dkript Preset
        BrandingService::applyDkriptPreset();
        $this->assertTrue(BrandingService::isDkriptBranded());
        $this->assertEquals('Dkript Inc.', BrandingService::companyName());
        $this->assertEquals('assets/images/branding/icon-dkript.png', BrandingService::icon());

        // 3. Cliente Personalizado (No Dkript)
        $param = Parameter::first();
        $param->system_name = 'Plataforma Corporativa Acero';
        $param->system_logo = 'uploads/acero-logo.png';
        $param->save();

        $this->assertFalse(BrandingService::isDkriptBranded());
        $this->assertNull(BrandingService::companyName());
        $this->assertEquals('uploads/acero-logo.png', BrandingService::icon());
    }
}
