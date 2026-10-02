<?php

namespace Tests\Feature;

use App\Console\Commands\MakeDkriptModule;
use App\Models\MenuOption;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeDkriptModuleTest extends TestCase
{
    use RefreshDatabase;

    protected string $originalRoutes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $routesFile = base_path('routes/web.php');
        $this->originalRoutes = File::exists($routesFile) ? File::get($routesFile) : '';

        // Reset de testing seam
        MakeDkriptModule::$testingFailAt = null;

        // Limpieza preventiva previa a los tests
        $this->cleanupAllTestModules();
    }

    protected function tearDown(): void
    {
        // Reset de testing seam
        MakeDkriptModule::$testingFailAt = null;

        // Limpieza de archivos generados por las pruebas
        $this->cleanupAllTestModules();

        // Restaurar routes/web.php original
        if (!empty($this->originalRoutes)) {
            File::put(base_path('routes/web.php'), $this->originalRoutes);
        }

        parent::tearDown();
    }

    protected function cleanupAllTestModules(): void
    {
        $this->cleanupModule('TestArticle', 'test-articles', 'test_articles');
        $this->cleanupModule('TestProduct', 'test-products', 'test_products');
        $this->cleanupModule('TestArchive', 'test-archives', 'test_archives');
        $this->cleanupModule('TestDryRun', 'test-dry-runs', 'test_dry_runs');
        $this->cleanupModule('TestCollision', 'test-collisions', 'test_collisions');
        $this->cleanupModule('TestRollbackNew', 'test-rollback-news', 'test_rollback_news');
        $this->cleanupModule('TestRollbackForce', 'test-rollback-forces', 'test_rollback_forces');
        $this->cleanupModule('TestRollbackRoutes', 'test-rollback-routes', 'test_rollback_routes');
        $this->cleanupModule('TestMigForce', 'test-mig-forces', 'test_mig_forces');
        $this->cleanupModule('TestJsonDoc', 'test-json-docs', 'test_json_docs');
    }

    protected function cleanupModule(string $modelName, string $kebabName, string $tableName): void
    {
        $singularModel = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($modelName));
        $names = array_unique([$modelName, $singularModel]);

        foreach ($names as $name) {
            // Modelo
            $modelPath = app_path("Models/{$name}.php");
            if (File::exists($modelPath)) {
                File::delete($modelPath);
            }

            // Controlador
            $controllerPath = app_path("Http/Controllers/{$name}Controller.php");
            if (File::exists($controllerPath)) {
                File::delete($controllerPath);
            }

            // Form Requests
            $storeReq = app_path("Http/Requests/Store{$name}Request.php");
            if (File::exists($storeReq)) {
                File::delete($storeReq);
            }
            $updateReq = app_path("Http/Requests/Update{$name}Request.php");
            if (File::exists($updateReq)) {
                File::delete($updateReq);
            }

            // Policy
            $policyPath = app_path("Policies/{$name}Policy.php");
            if (File::exists($policyPath)) {
                File::delete($policyPath);
            }

            // Factory
            $factoryPath = database_path("factories/{$name}Factory.php");
            if (File::exists($factoryPath)) {
                File::delete($factoryPath);
            }

            // Feature Test
            $testPath = base_path("tests/Feature/{$name}Test.php");
            if (File::exists($testPath)) {
                File::delete($testPath);
            }
        }

        // Vistas
        $viewsDir = resource_path("views/{$kebabName}");
        if (File::isDirectory($viewsDir)) {
            File::deleteDirectory($viewsDir);
        }

        // Vista Reporte PDF
        $pdfPath = resource_path("views/reports/{$kebabName}.blade.php");
        if (File::exists($pdfPath)) {
            File::delete($pdfPath);
        }

        // Archivo de Rutas Modulares
        $routePath = base_path("routes/modules/{$kebabName}.php");
        if (File::exists($routePath)) {
            File::delete($routePath);
        }

        // Migración
        $migrations = glob(database_path("migrations/*_create_{$tableName}_table.php"));
        foreach ($migrations as $mig) {
            if (File::exists($mig)) {
                File::delete($mig);
            }
        }
    }

    /**
     * Prueba la generación completa de un módulo empresarial estándar con Form Requests, Policy, Factory y Vistas.
     */
    public function test_make_module_command_creates_complete_enterprise_module(): void
    {
        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestArticle',
            '--fields' => 'title:string,content:text:nullable,price:decimal,is_published:boolean',
            '--icon' => 'bi-newspaper',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // 1. Verificar creación de Migración
        $migrations = glob(database_path('migrations/*_create_test_articles_table.php'));
        $this->assertNotEmpty($migrations, 'La migración no fue creada.');
        $migrationContent = File::get($migrations[0]);
        $this->assertStringContainsString("Schema::create('test_articles'", $migrationContent);
        $this->assertStringContainsString("string('title', 255)", $migrationContent);
        $this->assertStringContainsString("text('content')->nullable()", $migrationContent);
        $this->assertStringContainsString("decimal('price', 12, 2)", $migrationContent);
        $this->assertStringContainsString("boolean('is_published')", $migrationContent);

        // 2. Verificar creación de Modelo
        $modelPath = app_path('Models/TestArticle.php');
        $this->assertTrue(File::exists($modelPath), 'El modelo TestArticle.php no fue creado.');
        $modelContent = File::get($modelPath);
        $this->assertStringContainsString("protected \$table = 'test_articles';", $modelContent);
        $this->assertStringContainsString("'title'", $modelContent);
        $this->assertStringContainsString("'content'", $modelContent);
        $this->assertStringContainsString("'price'", $modelContent);
        $this->assertStringContainsString("'is_published'", $modelContent);
        $this->assertStringContainsString("'is_published' => 'boolean'", $modelContent);
        $this->assertStringContainsString("'price' => 'decimal:2'", $modelContent);

        // 3. Verificar creación de Form Requests
        $storeReqPath = app_path('Http/Requests/StoreTestArticleRequest.php');
        $this->assertTrue(File::exists($storeReqPath), 'StoreTestArticleRequest no fue creado.');
        $storeReqContent = File::get($storeReqPath);
        $this->assertStringContainsString("class StoreTestArticleRequest extends FormRequest", $storeReqContent);
        $this->assertStringContainsString("'title' => ['required'", $storeReqContent);
        $this->assertStringContainsString("'content' => ['nullable'", $storeReqContent);

        $updateReqPath = app_path('Http/Requests/UpdateTestArticleRequest.php');
        $this->assertTrue(File::exists($updateReqPath), 'UpdateTestArticleRequest no fue creado.');
        $updateReqContent = File::get($updateReqPath);
        $this->assertStringContainsString("class UpdateTestArticleRequest extends FormRequest", $updateReqContent);

        // 4. Verificar creación de Policy
        $policyPath = app_path('Policies/TestArticlePolicy.php');
        $this->assertTrue(File::exists($policyPath), 'TestArticlePolicy no fue creado.');
        $policyContent = File::get($policyPath);
        $this->assertStringContainsString('class TestArticlePolicy', $policyContent);
        $this->assertStringContainsString('public function before(User $user, string $ability)', $policyContent);
        $this->assertStringContainsString('in_array(4, session(\'mypermits\', []))', $policyContent);

        // 5. Verificar creación de Factory
        $factoryPath = database_path('factories/TestArticleFactory.php');
        $this->assertTrue(File::exists($factoryPath), 'TestArticleFactory no fue creado.');
        $factoryContent = File::get($factoryPath);
        $this->assertStringContainsString('class TestArticleFactory extends Factory', $factoryContent);

        // 6. Verificar creación de Controlador con inyección de Form Requests
        $controllerPath = app_path('Http/Controllers/TestArticleController.php');
        $this->assertTrue(File::exists($controllerPath), 'El controlador TestArticleController.php no fue creado.');
        $controllerContent = File::get($controllerPath);
        $this->assertStringContainsString('public function index(Request $request)', $controllerContent);
        $this->assertStringContainsString('public function create()', $controllerContent);
        $this->assertStringContainsString('public function store(StoreTestArticleRequest $request)', $controllerContent);
        $this->assertStringContainsString('public function show(TestArticle $testArticle)', $controllerContent);
        $this->assertStringContainsString('public function edit(TestArticle $testArticle)', $controllerContent);
        $this->assertStringContainsString('public function update(UpdateTestArticleRequest $request, TestArticle $testArticle)', $controllerContent);
        $this->assertStringContainsString('public function destroy(Request $request, TestArticle $testArticle)', $controllerContent);
        $this->assertStringContainsString('public function exportExcel(ExportService $exportService)', $controllerContent);
        $this->assertStringContainsString('public function exportPdf(Request $request, ExportService $exportService)', $controllerContent);
        $this->assertStringContainsString("AuditService::log('CREATE', 'TEST_ARTICLES'", $controllerContent);
        $this->assertStringContainsString("AuditService::log('UPDATE', 'TEST_ARTICLES'", $controllerContent);
        $this->assertStringContainsString("AuditService::log('DELETE', 'TEST_ARTICLES'", $controllerContent);

        // 7. Verificar creación de Vistas Blade (4 vistas)
        $this->assertTrue(File::exists(resource_path('views/test-articles/index.blade.php')));
        $this->assertTrue(File::exists(resource_path('views/test-articles/create.blade.php')));
        $this->assertTrue(File::exists(resource_path('views/test-articles/edit.blade.php')));
        $this->assertTrue(File::exists(resource_path('views/test-articles/show.blade.php')));

        $indexViewContent = File::get(resource_path('views/test-articles/index.blade.php'));
        $this->assertStringContainsString("@extends('layouts.admin')", $indexViewContent);
        $this->assertStringContainsString("@include('partials.layouts.page-header'", $indexViewContent);
        $this->assertStringContainsString('modalTestArticleForm', $indexViewContent);

        // 8. Verificar creación de Vista PDF
        $pdfPath = resource_path('views/reports/test-articles.blade.php');
        $this->assertTrue(File::exists($pdfPath), 'La vista de reporte PDF no fue creada.');
        $pdfContent = File::get($pdfPath);
        $this->assertStringContainsString("@extends('reports.layout')", $pdfContent);
        $this->assertStringContainsString('Total de Registros en Base de Datos', $pdfContent);

        // 9. Verificar creación de Feature Test
        $featureTestPath = base_path('tests/Feature/TestArticleTest.php');
        $this->assertTrue(File::exists($featureTestPath), 'El test de integración TestArticleTest.php no fue creado.');
        $testFileContent = File::get($featureTestPath);
        $this->assertStringContainsString('class TestArticleTest extends TestCase', $testFileContent);

        // 10. Verificar Registro en Base de Datos (MenuOption y 5 Permisos RBAC)
        $menuOption = MenuOption::where('route_name', 'test-articles.index')->first();
        $this->assertNotNull($menuOption, 'La opción de menú no fue registrada en BD.');
        $this->assertEquals('bi-newspaper', $menuOption->icon);

        $permissions = Permission::where('menu_option_id', $menuOption->id)->get();
        $this->assertCount(5, $permissions, 'El módulo debe tener exactamente 5 posiciones RBAC.');

        $positions = $permissions->pluck('position')->toArray();
        sort($positions);
        $this->assertEquals([1, 2, 3, 4, 5], $positions);

        // 11. Verificar asignación a Super Administrador (Rol 1)
        $superAdminRole = Role::find(1);
        $this->assertTrue($superAdminRole->menuOptions->contains('id', $menuOption->id));
        foreach ($permissions as $p) {
            $this->assertTrue($superAdminRole->permissions->contains('id', $p->id));
        }

        // 12. Verificar creación de Archivo de Rutas Modular Independiente (Paso 1.6)
        $routePath = base_path('routes/modules/test-articles.php');
        $this->assertTrue(File::exists($routePath), 'El archivo de rutas modular routes/modules/test-articles.php no fue creado.');
        $moduleRoutesContent = File::get($routePath);
        $this->assertStringContainsString("use App\Http\Controllers\TestArticleController;", $moduleRoutesContent);
        $this->assertStringContainsString("verify.option:{$menuOption->id}", $moduleRoutesContent);
        $this->assertStringContainsString("TestArticleController::class, 'index'", $moduleRoutesContent);
        $this->assertStringContainsString("verify.position:{$menuOption->id},1", $moduleRoutesContent);
        $this->assertStringContainsString("verify.position:{$menuOption->id},2", $moduleRoutesContent);
        $this->assertStringContainsString("verify.position:{$menuOption->id},3", $moduleRoutesContent);
        $this->assertStringContainsString("verify.position:{$menuOption->id},4", $moduleRoutesContent);
        $this->assertStringContainsString("verify.position:{$menuOption->id},5", $moduleRoutesContent);

        // 13. Verificar que routes/web.php permanece 100% INTACTO (Inmutabilidad byte-for-byte)
        $routesContent = File::get(base_path('routes/web.php'));
        $this->assertStringNotContainsString("TestArticleController::class", $routesContent, 'routes/web.php NUNCA debe ser modificado por módulos generados.');
    }

    /**
     * Prueba soporte integral de tipos enriquecidos (enum, json, uuid, decimal, foreignId) y relaciones.
     */
    public function test_make_module_command_with_rich_types_and_relationships(): void
    {
        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestProduct',
            '--fields' => 'sku:string(50):unique,summary:text:nullable,price:decimal(10,2),status:enum(draft,published,archived):default:draft,tags:json:nullable,uuid:uuid:unique,user_id:foreignId(users:cascade:cascade):index',
            '--icon' => 'bi-box',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // 1. Verificar Migración con tipos enriquecidos
        $migrations = glob(database_path('migrations/*_create_test_products_table.php'));
        $this->assertNotEmpty($migrations);
        $migContent = File::get($migrations[0]);

        $this->assertStringContainsString("string('sku', 50)->unique()", $migContent);
        $this->assertStringContainsString("text('summary')->nullable()", $migContent);
        $this->assertStringContainsString("decimal('price', 10, 2)", $migContent);
        $this->assertStringContainsString("string('status', 50)->default('draft')", $migContent);
        $this->assertStringContainsString("json('tags')->nullable()", $migContent);
        $this->assertStringContainsString("uuid('uuid')->unique()", $migContent);
        $this->assertStringContainsString("foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade')->index()", $migContent);

        // 2. Verificar Modelo con casts y relación inferida belongsTo(User)
        $modelPath = app_path('Models/TestProduct.php');
        $this->assertTrue(File::exists($modelPath));
        $modelContent = File::get($modelPath);

        $this->assertStringContainsString("'tags' => 'array'", $modelContent);
        $this->assertStringContainsString("'price' => 'decimal:2'", $modelContent);
        $this->assertStringContainsString('public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo', $modelContent);
        $this->assertStringContainsString("return \$this->belongsTo(\App\Models\User::class, 'user_id');", $modelContent);

        // 3. Verificar Form Requests con validaciones avanzadas
        $storeReqPath = app_path('Http/Requests/StoreTestProductRequest.php');
        $this->assertTrue(File::exists($storeReqPath));
        $storeReqContent = File::get($storeReqPath);

        $this->assertStringContainsString("Rule::in(['draft', 'published', 'archived'])", $storeReqContent);
        $this->assertStringContainsString("'unique:test_products,sku'", $storeReqContent);
        $this->assertStringContainsString("'exists:users,id'", $storeReqContent);
    }

    /**
     * Prueba opciones de soft-deletes y no-timestamps.
     */
    public function test_make_module_with_soft_deletes_and_no_timestamps(): void
    {
        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestArchive',
            '--fields' => 'label:string,metadata:text:nullable',
            '--soft-deletes' => true,
            '--no-timestamps' => true,
            '--icon' => 'bi-archive',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Verificar Modelo
        $modelPath = app_path('Models/TestArchive.php');
        $this->assertTrue(File::exists($modelPath));
        $modelContent = File::get($modelPath);
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\SoftDeletes;', $modelContent);
        $this->assertStringContainsString('use HasFactory, SoftDeletes;', $modelContent);
        $this->assertStringContainsString('public $timestamps = false;', $modelContent);

        // Verificar Migración
        $migrations = glob(database_path('migrations/*_create_test_archives_table.php'));
        $this->assertNotEmpty($migrations);
        $migContent = File::get($migrations[0]);
        $this->assertStringContainsString('$table->softDeletes();', $migContent);
        $this->assertStringNotContainsString('$table->timestamps();', $migContent);
    }

    /**
     * Prueba simulación --dry-run (no debe escribir archivos ni registros en base de datos).
     */
    public function test_make_module_dry_run_creates_no_files_and_no_db_records(): void
    {
        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestDryRun',
            '--fields' => 'name:string',
            '--dry-run' => true,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Verificar que ningún archivo fue creado
        $this->assertFalse(File::exists(app_path('Models/TestDryRun.php')));
        $this->assertFalse(File::exists(app_path('Http/Controllers/TestDryRunController.php')));
        $this->assertFalse(File::exists(app_path('Http/Requests/StoreTestDryRunRequest.php')));
        $this->assertFalse(File::isDirectory(resource_path('views/test-dry-runs')));
        $this->assertFalse(File::exists(base_path('routes/modules/test-dry-runs.php')), 'El archivo modular de rutas no debe crearse en dry-run.');
        $migrations = glob(database_path('migrations/*_create_test_dry_runs_table.php'));
        $this->assertEmpty($migrations);

        // Verificar que no se insertó nada en MenuOption
        $this->assertNull(MenuOption::where('route_name', 'test-dry-runs.index')->first());
    }

    /**
     * Prueba protección contra colisiones sin bandera --force.
     */
    public function test_make_module_collision_detection_aborts_without_force(): void
    {
        // 1. Crear módulo inicial con --force
        $exitCode1 = Artisan::call('dkript:make-module', [
            'name' => 'TestCollision',
            '--fields' => 'name:string',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode1);

        // 2. Intentar crear nuevamente sin --force debe abortar
        $exitCode2 = Artisan::call('dkript:make-module', [
            'name' => 'TestCollision',
            '--fields' => 'name:string',
        ]);

        $this->assertEquals(1, $exitCode2);
    }

    /**
     * Prueba validación y rechazo seguro de nombres inválidos o palabras reservadas.
     */
    public function test_make_module_rejects_invalid_or_reserved_names(): void
    {
        // Palabra reservada de PHP
        $exitCodeClass = Artisan::call('dkript:make-module', [
            'name' => 'Class',
        ]);
        $this->assertEquals(1, $exitCodeClass);

        // Path Traversal
        $exitCodeTraversal = Artisan::call('dkript:make-module', [
            'name' => '../EvilModule',
        ]);
        $this->assertEquals(1, $exitCodeTraversal);

        // Nombre que no comienza con letra
        $exitCodeNumber = Artisan::call('dkript:make-module', [
            'name' => '123Invalid',
        ]);
        $this->assertEquals(1, $exitCodeNumber);

        // Verificar que no se creó ningún archivo corrupto
        $this->assertFalse(File::exists(app_path('Models/Class.php')));
        $this->assertFalse(File::exists(app_path('Models/123Invalid.php')));
    }

    /**
     * Prueba rollback compensatorio en Base de Datos: ante un fallo tras el registro RBAC,
     * la transacción se revierte por completo y no deja MenuOption, Permissions ni asignaciones.
     */
    public function test_make_module_compensatory_rollback_reverts_database_records_on_failure_after_rbac(): void
    {
        MakeDkriptModule::$testingFailAt = 'after_rbac';

        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestRollbackNew',
            '--fields' => 'name:string',
            '--force' => true,
        ]);

        $this->assertEquals(1, $exitCode);

        // 1. MenuOption no debe existir en BD
        $menuOption = MenuOption::where('route_name', 'test-rollback-news.index')->first();
        $this->assertNull($menuOption, 'MenuOption no debió persistirse tras el rollback.');

        // 2. Permissions no deben existir en BD
        $permissions = Permission::where('name', 'like', '%TestRollbackNews%')->get();
        $this->assertCount(0, $permissions, 'No deben quedar permisos huérfanos en BD tras el rollback.');

        // 3. Asignaciones al Rol 1 (Super Admin) no deben existir
        $superAdmin = Role::find(1);
        $this->assertFalse($superAdmin->menuOptions->contains('route_name', 'test-rollback-news.index'));

        // 4. Archivos generados no deben existir (fueron eliminados por rollback)
        $this->assertFalse(File::exists(app_path('Models/TestRollbackNew.php')));
        $this->assertFalse(File::exists(app_path('Http/Controllers/TestRollbackNewController.php')));
        $this->assertFalse(File::exists(app_path('Http/Requests/StoreTestRollbackNewRequest.php')));
        $this->assertFalse(File::isDirectory(resource_path('views/test-rollback-news')));
        $this->assertFalse(File::exists(base_path('routes/modules/test-rollback-news.php')), 'El archivo modular de rutas debió ser eliminado por el rollback.');
        $migrations = glob(database_path('migrations/*_create_test_rollback_news_table.php'));
        $this->assertEmpty($migrations, 'La migración debió ser eliminada por el rollback.');
    }

    /**
     * Prueba protección contra destrucción de archivos preexistentes con --force:
     * Si un archivo ya existía antes de ejecutar el comando con --force, y ocurre un fallo posterior,
     * el archivo NO debe ser eliminado, sino que su contenido original debe ser restaurado intacto.
     */
    public function test_make_module_compensatory_rollback_restores_preexisting_files_overwritten_with_force(): void
    {
        $preexistingModelPath = app_path('Models/TestRollbackForce.php');
        $originalModelContent = "<?php\n\n// ORIGINAL_CONTENT_PREEXISTING_V1\nnamespace App\Models;\n\nclass TestRollbackForce {}\n";
        File::put($preexistingModelPath, $originalModelContent);

        $preexistingRoutePath = base_path('routes/modules/test-rollback-forces.php');
        $originalRouteContent = "<?php\n\n// ORIGINAL_ROUTE_PREEXISTING_V1\n";
        File::put($preexistingRoutePath, $originalRouteContent);

        $this->assertTrue(File::exists($preexistingModelPath));
        $this->assertEquals($originalModelContent, File::get($preexistingModelPath));
        $this->assertTrue(File::exists($preexistingRoutePath));
        $this->assertEquals($originalRouteContent, File::get($preexistingRoutePath));

        MakeDkriptModule::$testingFailAt = 'after_rbac';

        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestRollbackForce',
            '--fields' => 'title:string',
            '--force' => true,
        ]);

        $this->assertEquals(1, $exitCode);

        // Los archivos preexistentes DEBEN seguir existiendo
        $this->assertTrue(File::exists($preexistingModelPath), 'El modelo preexistente no debió ser borrado.');
        $this->assertEquals($originalModelContent, File::get($preexistingModelPath), 'El contenido del modelo preexistente debió ser restaurado intacto.');

        $this->assertTrue(File::exists($preexistingRoutePath), 'El archivo de rutas preexistente no debió ser borrado.');
        $this->assertEquals($originalRouteContent, File::get($preexistingRoutePath), 'El contenido de rutas preexistente debió ser restaurado intacto.');
    }

    /**
     * Prueba inmutabilidad estricta byte-for-byte de routes/web.php (Paso 1.6):
     * routes/web.php NUNCA es modificado ni en ejecución exitosa ni en fallo/rollback.
     */
    public function test_make_module_preserves_routes_web_php_byte_for_byte_immutability(): void
    {
        $routesPath = base_path('routes/web.php');
        $originalRoutesHash = hash_file('sha256', $routesPath);
        $originalRoutesContent = File::get($routesPath);

        // 1. Caso de fallo con rollback
        MakeDkriptModule::$testingFailAt = 'after_rbac';

        $exitCodeFail = Artisan::call('dkript:make-module', [
            'name' => 'TestRollbackRoutes',
            '--fields' => 'name:string',
            '--force' => true,
        ]);

        $this->assertEquals(1, $exitCodeFail);
        $this->assertEquals($originalRoutesHash, hash_file('sha256', $routesPath), 'El hash de routes/web.php no debió alterarse tras rollback.');
        $this->assertEquals($originalRoutesContent, File::get($routesPath));
        $this->assertFalse(File::exists(base_path('routes/modules/test-rollback-routes.php')));

        // 2. Caso de ejecución exitosa
        MakeDkriptModule::$testingFailAt = null;

        $exitCodeSuccess = Artisan::call('dkript:make-module', [
            'name' => 'TestRollbackRoutes',
            '--fields' => 'name:string',
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCodeSuccess);
        $this->assertEquals($originalRoutesHash, hash_file('sha256', $routesPath), 'El hash de routes/web.php no debió alterarse tras generación exitosa.');
        $this->assertEquals($originalRoutesContent, File::get($routesPath));
        $this->assertTrue(File::exists(base_path('routes/modules/test-rollback-routes.php')));
    }

    /**
     * Prueba que las rutas modulares independientes son descubiertas y son 100% cacheables (Paso 1.6).
     */
    public function test_modular_routes_are_discovered_by_route_list_and_cacheable(): void
    {
        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestProduct',
            '--fields' => 'title:string,price:decimal',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        // Verificar archivo de rutas generado
        $modularRoutePath = base_path('routes/modules/test-products.php');
        $this->assertTrue(File::exists($modularRoutePath));

        // Verificar que route:cache compila sin excepciones
        $cacheExit = Artisan::call('route:cache');
        $this->assertEquals(0, $cacheExit, 'route:cache debe ejecutarse exitosamente con rutas modulares.');

        // Limpiar cache para el resto de la suite
        Artisan::call('route:clear');
    }

    /**
     * Prueba que --force no crea migraciones duplicadas con diferentes timestamps para la misma tabla.
     */
    public function test_make_module_force_does_not_create_duplicate_timestamped_migrations(): void
    {
        // 1. Crear módulo inicial con --force
        $exitCode1 = Artisan::call('dkript:make-module', [
            'name' => 'TestMigForce',
            '--fields' => 'name:string',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode1);

        $migrations1 = glob(database_path('migrations/*_create_test_mig_forces_table.php'));
        $this->assertCount(1, $migrations1);
        $initialMigrationPath = $migrations1[0];

        // 2. Ejecutar nuevamente con --force para el mismo módulo
        $exitCode2 = Artisan::call('dkript:make-module', [
            'name' => 'TestMigForce',
            '--fields' => 'name:string,description:text:nullable',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode2);

        $migrations2 = glob(database_path('migrations/*_create_test_mig_forces_table.php'));
        $this->assertCount(1, $migrations2, 'No se deben crear migraciones timestamped duplicadas para la misma tabla con --force.');
        $this->assertEquals($initialMigrationPath, $migrations2[0], 'Debe reutilizar y sobrescribir el archivo de migración preexistente.');
        $this->assertStringContainsString("text('description')->nullable()", File::get($migrations2[0]));
    }

    /**
     * Prueba que dkript:make-module genera soporte seguro para campos tipo json en todos los artefactos.
     */
    public function test_make_module_handles_json_fields_safely_across_all_artifacts(): void
    {
        $exitCode = Artisan::call('dkript:make-module', [
            'name' => 'TestJsonDoc',
            '--fields' => 'title:string,payload:json:nullable',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        // 1. Migración
        $migrations = glob(database_path('migrations/*_create_test_json_docs_table.php'));
        $this->assertNotEmpty($migrations);
        $this->assertStringContainsString("json('payload')->nullable()", File::get($migrations[0]));

        // 2. Modelo
        $modelContent = File::get(app_path('Models/TestJsonDoc.php'));
        $this->assertStringContainsString("'payload' => 'array'", $modelContent);

        // 3. Form Requests (Store & Update)
        $storeReq = File::get(app_path('Http/Requests/StoreTestJsonDocRequest.php'));
        $this->assertStringContainsString('prepareForValidation', $storeReq);
        $this->assertStringContainsString('json_decode($trimmed, true)', $storeReq);
        $this->assertStringContainsString("'payload.array'", $storeReq);

        $updateReq = File::get(app_path('Http/Requests/UpdateTestJsonDocRequest.php'));
        $this->assertStringContainsString('prepareForValidation', $updateReq);

        // 4. Controlador
        $controllerContent = File::get(app_path('Http/Controllers/TestJsonDocController.php'));
        $this->assertStringContainsString("isset(\$validated['payload']) && is_string(\$validated['payload'])", $controllerContent);
        $this->assertStringContainsString("is_array(\$testJsonDoc->payload)", $controllerContent);

        // 5. Index View
        $indexContent = File::get(resource_path('views/test-json-docs/index.blade.php'));
        $this->assertStringContainsString("is_array(\$testJsonDoc->payload)", $indexContent);
        $this->assertStringContainsString('<textarea name="payload"', $indexContent);
        $this->assertStringContainsString("JSON.stringify(item.payload, null, 2)", $indexContent);

        // 6. Create View
        $createContent = File::get(resource_path('views/test-json-docs/create.blade.php'));
        $this->assertStringContainsString('<textarea name="payload"', $createContent);

        // 7. Edit View
        $editContent = File::get(resource_path('views/test-json-docs/edit.blade.php'));
        $this->assertStringContainsString('<textarea name="payload"', $editContent);
        $this->assertStringContainsString("is_array(\$__v = old('payload', \$testJsonDoc->payload))", $editContent);

        // 8. Show View
        $showContent = File::get(resource_path('views/test-json-docs/show.blade.php'));
        $this->assertStringContainsString("is_array(\$testJsonDoc->payload)", $showContent);

        // 9. PDF View
        $pdfContent = File::get(resource_path('views/reports/test-json-docs.blade.php'));
        $this->assertStringContainsString("is_array(\$item->payload)", $pdfContent);
    }
}

