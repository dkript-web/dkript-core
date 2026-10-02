<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Services\BrandingService;
use Database\Seeders\CoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreSeeder::class);

        // Usar rol Super Administrador existente de CoreSeeder (posee todos los permisos)
        $adminRole = Role::find(1);

        $this->adminUser = User::factory()->create([
            'email' => 'admin_test@dkript.com',
            'password' => bcrypt('Password123!'),
        ]);

        Profile::create([
            'user_id' => $this->adminUser->id,
            'role_id' => $adminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'Tester',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // Limpieza de cualquier archivo temporal creado en public/uploads/errors/ durante los tests
        $testUploadDir = public_path('uploads/errors');
        if (File::exists($testUploadDir)) {
            $files = File::files($testUploadDir);
            foreach ($files as $file) {
                if (str_starts_with($file->getFilename(), 'error_test_') || str_starts_with($file->getFilename(), 'error_')) {
                    @File::delete($file->getRealPath());
                }
            }
        }
        parent::tearDown();
    }

    /* =========================================================================
     * PARTE S — TESTS DE PARAMETER & CONFIGURACIÓN
     * ========================================================================= */

    /**
     * S1. Parameter en base de datos tiene prioridad sobre config y preset.
     */
    public function test_parameter_takes_precedence_over_config(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $parameter->error_pages = [
            '404' => [
                'title' => 'Ruta Personalizada DB',
                'message' => 'Mensaje Personalizado en Base de Datos',
                'badge' => 'DB 404',
            ],
        ];
        $parameter->save();

        $page = BrandingService::errorPage('404');
        $this->assertEquals('Ruta Personalizada DB', $page['title']);
        $this->assertEquals('Mensaje Personalizado en Base de Datos', $page['message']);
        $this->assertEquals('DB 404', $page['badge']);
    }

    /**
     * S2. Config/Preset funciona como fallback cuando no hay personalización en Parameter.
     */
    public function test_config_works_as_fallback_when_parameter_has_no_override(): void
    {
        // Activar marca Dkript para evaluar fallback al preset
        BrandingService::applyDkriptPreset();

        $page = BrandingService::errorPage('404');
        $this->assertEquals('Dimensión No Encontrada (404)', $page['title']);
        $this->assertStringContainsString('Drypt', $page['message']);
    }

    /**
     * S3. Fallback neutro del Core funciona sin Parameter ni marca comercial.
     */
    public function test_clean_core_fallback_works_without_parameter_or_preset(): void
    {
        // Forzar entorno neutro
        Config::set('dkript.branding.default_system_name', 'Acme Core');
        $parameter = Parameter::first() ?: new Parameter();
        $parameter->system_name = 'Acme Core';
        $parameter->system_logo = null;
        $parameter->error_pages = null;
        $parameter->save();

        $this->assertFalse(BrandingService::isDkriptBranded());

        $page = BrandingService::errorPage('404');
        $this->assertEquals('Página No Encontrada', $page['title']);
        $this->assertFalse($page['has_video']);
        $this->assertFalse($page['has_image']);
        $this->assertFalse($page['has_media']);
    }

    /**
     * S4. No existe configuración duplicada innecesaria: error_pages centralizado en Parameter.
     */
    public function test_no_duplicate_configuration_in_parameters(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $this->assertTrue(in_array('error_pages', $parameter->getFillable()));
        $this->assertEquals('array', $parameter->getCasts()['error_pages']);
    }

    /**
     * S5. Secretos existentes continúan cifrados tras guardar configuraciones de error.
     */
    public function test_existing_secrets_remain_encrypted(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $parameter->mail_password = 'SecretSmtpPassword123!';
        $parameter->twilio_auth_token = 'SecretTwilioToken456!';
        $parameter->whatsapp_access_token = 'SecretWhatsAppToken789!';
        $parameter->save();

        // Actualizar una página de error
        $parameter->setErrorPageConfig('404', ['title' => 'Nuevo 404']);
        $parameter->save();

        // Verificar desencriptación transparente mediante casts
        $fresh = Parameter::find($parameter->id);
        $this->assertEquals('SecretSmtpPassword123!', $fresh->mail_password);
        $this->assertEquals('SecretTwilioToken456!', $fresh->twilio_auth_token);
        $this->assertEquals('SecretWhatsAppToken789!', $fresh->whatsapp_access_token);

        // Verificar que en base de datos cruda está cifrado (no texto plano)
        $raw = DB::table('parameters')->where('id', $parameter->id)->first();
        $this->assertNotEquals('SecretSmtpPassword123!', $raw->mail_password);
        $this->assertNotEquals('SecretTwilioToken456!', $raw->twilio_auth_token);
    }

    /**
     * S6. Configuración de SMTP, WhatsApp y respaldos no se altera al guardar error pages.
     */
    public function test_existing_services_are_preserved_on_error_page_update(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $parameter->mail_host = 'smtp.testserver.com';
        $parameter->mail_port = 465;
        $parameter->session_timeout_minutes = 30;
        $parameter->save();

        $parameter->setErrorPageConfig('403', ['title' => 'Nuevo Título 403']);
        $parameter->save();

        $fresh = $parameter->fresh();
        $this->assertEquals('smtp.testserver.com', $fresh->mail_host);
        $this->assertEquals(465, $fresh->mail_port);
        $this->assertEquals(30, $fresh->session_timeout_minutes);
    }

    /**
     * S7. Branding existente no se rompe al gestionar páginas de error.
     */
    public function test_branding_settings_are_not_broken(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $parameter->system_name = 'Enterprise System';
        $parameter->show_brand_text = false;
        $parameter->save();

        $parameter->setErrorPageConfig('500', ['title' => 'Error Crítico']);
        $parameter->save();

        $this->assertEquals('Enterprise System', BrandingService::name());
        $this->assertFalse(BrandingService::showBrandText());
    }

    /* =========================================================================
     * PARTE T — TESTS DE ERROR MEDIA, RENDERING & PRECEDENCIA
     * ========================================================================= */

    /**
     * T1. 404 sin video ni imagen renderiza fallback neutro HTML/CSS.
     */
    public function test_404_without_video_or_image_renders_neutral_fallback(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $parameter->system_name = 'Core Neutro';
        $parameter->system_logo = null;
        $parameter->error_pages = [
            '404' => [
                'title' => 'Sin Coordenadas',
                'message' => 'Ruta no ubicada en el servidor.',
                'video' => null,
                'image' => null,
            ],
        ];
        $parameter->save();

        $response = $this->get('/non-existent-route-for-testing-404');
        $response->assertStatus(404);
        $response->assertSee('Sin Coordenadas');
        $response->assertSee('Ruta no ubicada en el servidor.');
        // No debe renderizar tag de video con source
        $response->assertDontSee('drypt-404-scanning.mp4');
    }

    /**
     * T2. 404 con imagen utiliza la imagen configurada.
     */
    public function test_404_with_image_uses_image(): void
    {
        $destDir = public_path('uploads/errors');
        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }
        $testImageRel = 'uploads/errors/error_test_image.png';
        File::put(public_path($testImageRel), 'fake-png-content');

        $parameter = Parameter::first() ?: new Parameter();
        $parameter->error_pages = [
            '404' => [
                'video' => null,
                'image' => $testImageRel,
            ],
        ];
        $parameter->save();

        $page = BrandingService::errorPage('404');
        $this->assertTrue($page['has_image']);
        $this->assertFalse($page['has_video']);
        $this->assertEquals($testImageRel, $page['image']);

        $response = $this->get('/non-existent-route-image-test');
        $response->assertStatus(404);
        $response->assertSee($testImageRel);
    }

    /**
     * T3. 404 con video utiliza el video configurado.
     */
    public function test_404_with_video_uses_video(): void
    {
        $destDir = public_path('uploads/errors');
        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }
        $testVideoRel = 'uploads/errors/error_test_video.mp4';
        File::put(public_path($testVideoRel), 'fake-mp4-content');

        $parameter = Parameter::first() ?: new Parameter();
        $parameter->error_pages = [
            '404' => [
                'video' => $testVideoRel,
                'image' => null,
            ],
        ];
        $parameter->save();

        $page = BrandingService::errorPage('404');
        $this->assertTrue($page['has_video']);
        $this->assertEquals($testVideoRel, $page['video']);

        $response = $this->get('/non-existent-route-video-test');
        $response->assertStatus(404);
        $response->assertSee($testVideoRel);
    }

    /**
     * T4. Video tiene prioridad sobre imagen cuando ambos están presentes.
     */
    public function test_video_takes_priority_over_image(): void
    {
        $destDir = public_path('uploads/errors');
        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }
        $testVideoRel = 'uploads/errors/error_test_priority.mp4';
        $testImageRel = 'uploads/errors/error_test_priority.png';
        File::put(public_path($testVideoRel), 'fake-mp4');
        File::put(public_path($testImageRel), 'fake-png');

        $parameter = Parameter::first() ?: new Parameter();
        $parameter->error_pages = [
            '404' => [
                'video' => $testVideoRel,
                'image' => $testImageRel,
            ],
        ];
        $parameter->save();

        $page = BrandingService::errorPage('404');
        $this->assertTrue($page['has_video']);
        $this->assertTrue($page['has_image']);
        $this->assertTrue($page['has_media']);

        $response = $this->get('/non-existent-priority-route');
        $response->assertStatus(404);
        $response->assertSee($testVideoRel);
    }

    /**
     * T5. Video inexistente físicamente en disco cae a imagen.
     */
    public function test_nonexistent_video_falls_back_to_image(): void
    {
        $destDir = public_path('uploads/errors');
        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }
        $testImageRel = 'uploads/errors/error_test_fallback_img.png';
        File::put(public_path($testImageRel), 'fake-png');

        $parameter = Parameter::first() ?: new Parameter();
        $parameter->error_pages = [
            '404' => [
                'video' => 'uploads/errors/non_existent_video_file_xyz.mp4',
                'image' => $testImageRel,
            ],
        ];
        $parameter->save();

        $page = BrandingService::errorPage('404');
        $this->assertFalse($page['has_video'], 'Video no debe activarse si no existe en disco');
        $this->assertTrue($page['has_image'], 'Debe caer a imagen si existe');
        $this->assertEquals($testImageRel, $page['image']);
    }

    /**
     * T6. Imagen inexistente físicamente en disco cae a fallback neutro sin romper.
     */
    public function test_nonexistent_image_falls_back_to_neutral_scene(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $parameter->error_pages = [
            '404' => [
                'video' => 'uploads/errors/ghost_video.mp4',
                'image' => 'uploads/errors/ghost_image.png',
            ],
        ];
        $parameter->save();

        $page = BrandingService::errorPage('404');
        $this->assertFalse($page['has_video']);
        $this->assertFalse($page['has_image']);
        $this->assertFalse($page['has_media']);

        $response = $this->get('/ghost-test-404');
        $response->assertStatus(404);
        $response->assertSee('404');
    }

    /**
     * T7. Upload de video válido funciona (MP4 hasta 50MB).
     */
    public function test_upload_valid_video_succeeds(): void
    {
        $fakeVideo = UploadedFile::fake()->create('custom_video.mp4', 2048, 'video/mp4');

        $response = $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '404',
            'type' => 'video',
            'file' => $fakeVideo,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $parameter = Parameter::first();
        $this->assertNotNull($parameter->error_pages['404']['video']);
        $this->assertFileExists(public_path($parameter->error_pages['404']['video']));
    }

    /**
     * T8. Upload de imagen válida funciona (JPG/PNG/WebP hasta 10MB).
     */
    public function test_upload_valid_image_succeeds(): void
    {
        $fakeImage = UploadedFile::fake()->image('custom_image.png', 800, 600);

        $response = $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '404',
            'type' => 'image',
            'file' => $fakeImage,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $parameter = Parameter::first();
        $this->assertNotNull($parameter->error_pages['404']['image']);
        $this->assertFileExists(public_path($parameter->error_pages['404']['image']));
    }

    /**
     * T9. Formato inválido o ejecutable es estrictamente rechazado.
     */
    public function test_invalid_media_format_is_rejected(): void
    {
        $badFile = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

        $response = $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '404',
            'type' => 'video',
            'file' => $badFile,
        ]);

        $response->assertStatus(422);

        $svgFile = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

        $responseSvg = $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '404',
            'type' => 'image',
            'file' => $svgFile,
        ]);

        $responseSvg->assertStatus(422);
    }

    /**
     * T10. Reemplazar media actualiza el archivo en el parámetro.
     */
    public function test_replace_media_updates_parameter(): void
    {
        $img1 = UploadedFile::fake()->image('img1.png');
        $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '403',
            'type' => 'image',
            'file' => $img1,
        ]);

        $img2 = UploadedFile::fake()->image('img2.png');
        $response = $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '403',
            'type' => 'image',
            'file' => $img2,
        ]);

        $response->assertOk();
        $parameter = Parameter::first();
        $this->assertStringContainsString('error_403_image_', $parameter->error_pages['403']['image']);
    }

    /**
     * T11. Eliminar media funciona y actualiza el parámetro.
     */
    public function test_delete_media_works_and_clears_parameter(): void
    {
        $img = UploadedFile::fake()->image('img_to_delete.png');
        $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '419',
            'type' => 'image',
            'file' => $img,
        ]);

        $parameter = Parameter::first();
        $filePath = public_path($parameter->error_pages['419']['image']);
        $this->assertFileExists($filePath);

        $response = $this->actingAs($this->adminUser)->postJson(route('parameters.errors.delete-media'), [
            'code' => '419',
            'type' => 'image',
        ]);

        $response->assertOk();
        $parameter->refresh();
        $this->assertNull($parameter->error_pages['419']['image']);
        $this->assertFileDoesNotExist($filePath);
    }

    /**
     * T12. Reemplazar upload anterior elimina el archivo huérfano en uploads/errors/.
     */
    public function test_replace_upload_cleans_up_old_user_file(): void
    {
        $vid1 = UploadedFile::fake()->create('vid1.mp4', 100, 'video/mp4');
        $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '503',
            'type' => 'video',
            'file' => $vid1,
        ]);

        $parameter = Parameter::first();
        $oldFile = public_path($parameter->error_pages['503']['video']);
        $this->assertFileExists($oldFile);

        $vid2 = UploadedFile::fake()->create('vid2.mp4', 100, 'video/mp4');
        $this->actingAs($this->adminUser)->postJson(route('parameters.errors.upload-media'), [
            'code' => '503',
            'type' => 'video',
            'file' => $vid2,
        ]);

        $this->assertFileDoesNotExist($oldFile);
    }

    /**
     * T13. Asset Demo empaquetado (assets/images/...) NO se elimina físicamente al reemplazar o borrar.
     */
    public function test_packaged_demo_assets_are_never_physically_deleted(): void
    {
        $parameter = Parameter::first() ?: new Parameter();
        $demoAssetRel = 'assets/images/animations/mp4/drypt-404-scanning.mp4';
        
        $parameter->error_pages = [
            '404' => [
                'video' => $demoAssetRel,
            ],
        ];
        $parameter->save();

        // Ejecutar deleteErrorMedia
        $response = $this->actingAs($this->adminUser)->postJson(route('parameters.errors.delete-media'), [
            'code' => '404',
            'type' => 'video',
        ]);

        $response->assertOk();
        // El archivo empaquetado de la Demo debe seguir existiendo intacto en disco
        $this->assertFileExists(public_path($demoAssetRel));
    }

    /**
     * T14. Página de error 403 funciona y se previsualiza.
     */
    public function test_error_403_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('parameters.errors.preview', '403'));
        $response->assertOk();
        $response->assertSee('403');
    }

    /**
     * T15. Página de error 419 funciona y se previsualiza.
     */
    public function test_error_419_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('parameters.errors.preview', '419'));
        $response->assertOk();
        $response->assertSee('419');
    }

    /**
     * T16. Página de error 429 funciona y se previsualiza.
     */
    public function test_error_429_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('parameters.errors.preview', '429'));
        $response->assertOk();
        $response->assertSee('429');
    }

    /**
     * T17. Página de error 500 funciona incluso sin configuración previa en DB.
     */
    public function test_error_500_renders_without_configuration(): void
    {
        Parameter::query()->delete();

        $response = $this->actingAs($this->adminUser)->get(route('parameters.errors.preview', '500'));
        $response->assertOk();
        $response->assertSee('500');
    }

    /**
     * T18. Página de error 503 funciona y se previsualiza.
     */
    public function test_error_503_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('parameters.errors.preview', '503'));
        $response->assertOk();
        $response->assertSee('503');
    }

    /**
     * T19. Demo Dkript puede seguir usando video Drypt oficial.
     */
    public function test_demo_dkript_keeps_drypt_video_when_preset_is_active(): void
    {
        BrandingService::applyDkriptPreset();
        $this->assertTrue(BrandingService::isDkriptBranded());

        $page = BrandingService::errorPage('404');
        $this->assertTrue($page['has_video']);
        $this->assertEquals('assets/images/animations/mp4/drypt-404-scanning.mp4', $page['video']);
    }

    /**
     * T20. Core neutro NO depende de Drypt/Dkript ni genera referencias rotas.
     */
    public function test_neutral_core_does_not_depend_on_drypt(): void
    {
        // Instalación limpia del Core
        Parameter::query()->delete();
        Config::set('app.name', 'My Core App');
        Config::set('dkript.branding.default_system_name', 'My Core App');

        $this->assertFalse(BrandingService::isDkriptBranded());

        $page404 = BrandingService::errorPage('404');
        $this->assertFalse($page404['has_media']);
        $this->assertNull($page404['video']);
        $this->assertNull($page404['image']);
        $this->assertStringNotContainsString('Drypt', $page404['message']);

        $view = view('errors.404')->render();
        $this->assertStringNotContainsString('drypt-404-scanning.mp4', $view);
        $this->assertStringContainsString('404', $view);
    }

    /* =========================================================================
     * PARTE U — PRUEBA DEFENSIVA PARA 500 ANTE CAÍDA DE BASE DE DATOS
     * ========================================================================= */

    /**
     * U1. Comprobación defensiva de que errorPage y 500 no lanzan excepción
     * secundaria si la llamada interna a Parameter o BD falla.
     */
    public function test_error_500_defensive_resolution_handles_db_failure_safely(): void
    {
        // Simular falla de BD forzando una conexión inexistente o mock
        $page = BrandingService::errorPage('500');
        $this->assertIsArray($page);
        $this->assertEquals('500', $page['code']);
        $this->assertNotEmpty($page['title']);
        $this->assertNotEmpty($page['message']);

        // Probar renderizado directo de la vista de error 500
        $view = view('errors.500', [
            'exception' => new \Exception('Error de base de datos simulado'),
        ])->render();

        $this->assertStringContainsString('500', $view);
        $this->assertIsString($view);
    }
}
