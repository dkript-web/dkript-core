<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Role;
use App\Models\MenuOption;
use App\Models\Permission;
use App\Models\Parameter;
use Database\Seeders\CoreSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    public function test_installer_command_is_registered_in_artisan(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('dkript:install', $commands);
        $this->assertStringContainsString('Dkript Core', $commands['dkript:install']->getDescription());
    }

    public function test_installer_defaults_to_mysql(): void
    {
        $envExampleContent = file_get_contents(base_path('.env.example'));
        $this->assertMatchesRegularExpression('/^DB_CONNECTION=mysql/m', $envExampleContent);

        $configFile = file_get_contents(config_path('database.php'));
        $this->assertMatchesRegularExpression("/'default'\s*=>\s*env\('DB_CONNECTION',\s*'mysql'\)/", $configFile);
    }

    public function test_installer_fails_in_non_interactive_mode_if_required_admin_options_missing(): void
    {
        $exitCode = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--skip-migration' => true,
        ])->run();

        $this->assertSame(1, $exitCode);
    }

    public function test_installer_fails_if_admin_password_has_less_than_12_characters(): void
    {
        $exitCode = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Admin Invalido',
            '--admin-email' => 'admin.test@example.com',
            '--admin-password' => 'shortpass1', // Menor a 12 caracteres
            '--skip-migration' => true,
        ])->run();

        $this->assertSame(1, $exitCode);
    }

    public function test_installer_installs_core_structural_data_and_super_admin(): void
    {
        $exitCode = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Super Administrador Core',
            '--admin-email' => 'coreadmin@dkript-enterprise.test',
            '--admin-password' => 'SecurePassw0rd2026!',
            '--app-name' => 'Dkript Core Test',
            '--app-url' => 'http://localhost:8000',
            '--skip-migration' => true,
        ])->run();

        $this->assertSame(0, $exitCode);

        // 1. Verificar datos estructurales del Core
        $this->assertDatabaseHas('roles', [
            'id' => 1,
            'name' => 'Super Administrador',
        ]);
        $this->assertDatabaseHas('roles', [
            'id' => 2,
            'name' => 'Operador / Usuario Regular',
        ]);
        $this->assertSame(6, MenuOption::count());
        $this->assertSame(30, Permission::count());

        // 2. Verificar Super Administrador creado
        $user = User::where('email', 'coreadmin@dkript-enterprise.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('Super Administrador Core', $user->name);
        $this->assertSame(1, $user->status);

        // 3. Contraseña correctamente hasheada (nunca en texto plano)
        $this->assertNotEquals('SecurePassw0rd2026!', $user->password);
        $this->assertTrue(Hash::check('SecurePassw0rd2026!', $user->password));

        // 4. Verificación de perfil y relación RBAC
        $this->assertNotNull($user->profile);
        $this->assertSame(1, $user->profile->role_id);
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission(1, 1));
        $this->assertTrue($user->hasPermission(5, 5));

        // 5. Marcador de instalación en Parameter
        $param = Parameter::first();
        $this->assertNotNull($param);
        $this->assertNotNull($param->installed_at);
        $this->assertSame('coreadmin@dkript-enterprise.test', $param->contact_email);
    }

    public function test_installer_does_not_create_demo_credentials(): void
    {
        $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Nuevo Super Admin',
            '--admin-email' => 'realadmin@example.com',
            '--admin-password' => 'SafePassword1234!',
            '--skip-migration' => true,
        ])->run();

        // Comprobar ausencia total de usuarios demo
        $this->assertDatabaseMissing('users', ['email' => 'admin@dkript.com']);
        $this->assertDatabaseMissing('users', ['email' => 'demo@dkript.com']);
    }

    public function test_installer_detects_previous_installation_and_prevents_duplication(): void
    {
        // Primera ejecución
        $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Admin Inicial',
            '--admin-email' => 'primer.admin@example.com',
            '--admin-password' => 'InitialPassword123!',
            '--skip-migration' => true,
        ])->run();

        $initialRolesCount = Role::count();
        $initialOptionsCount = MenuOption::count();
        $initialPermissionsCount = Permission::count();

        // Segunda ejecución con flag --force
        $secondRun = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--force' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Admin Inicial',
            '--admin-email' => 'primer.admin@example.com',
            '--admin-password' => 'InitialPassword123!',
            '--skip-migration' => true,
        ]);

        $secondRun->expectsOutputToContain('Dkript Core parece estar instalado.');
        $secondRun->assertExitCode(0);

        // Sin duplicación de datos estructurales
        $this->assertSame($initialRolesCount, Role::count());
        $this->assertSame($initialOptionsCount, MenuOption::count());
        $this->assertSame($initialPermissionsCount, Permission::count());
        $this->assertSame(1, User::where('email', 'primer.admin@example.com')->count());
    }

    public function test_installer_preserves_existing_app_key(): void
    {
        $originalKey = 'base64:TestOriginalKey1234567890abcdefghijklmnopqrstuvwxyz=';
        config(['app.key' => $originalKey]);
        putenv("APP_KEY={$originalKey}");

        $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Admin Key Test',
            '--admin-email' => 'keytest@example.com',
            '--admin-password' => 'TestKeyPassword123!',
            '--skip-migration' => true,
        ])->expectsOutputToContain('APP_KEY: ✓ (Clave existente conservada)');
    }

    public function test_isolation_between_core_seeder_and_demo_seeder(): void
    {
        // CoreSeeder no debe crear usuarios
        $coreSeeder = new CoreSeeder();
        $coreSeeder->run();

        $this->assertSame(0, User::count());
        $this->assertSame(0, Profile::count());
        $this->assertSame(2, Role::count());
        $this->assertSame(6, MenuOption::count());
        $this->assertSame(30, Permission::count());

        // DemoSeeder crea únicamente los usuarios demo
        $demoSeeder = new DemoSeeder();
        $demoSeeder->run();

        $this->assertSame(2, User::count());
        $this->assertSame(2, Profile::count());
        $this->assertDatabaseHas('users', ['email' => 'admin@dkript.com']);
        $this->assertDatabaseHas('users', ['email' => 'demo@dkript.com']);
    }

    public function test_installer_fails_if_admin_email_is_invalid(): void
    {
        $exitCode = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Admin Invalido',
            '--admin-email' => 'not-a-valid-email',
            '--admin-password' => 'ValidPassword123!',
            '--skip-migration' => true,
        ])->run();

        $this->assertSame(1, $exitCode);
    }

    public function test_sqlite_file_only_created_when_sqlite_selected(): void
    {
        $testSqlitePath = database_path('test_sqlite_install.sqlite');
        if (file_exists($testSqlitePath)) {
            @unlink($testSqlitePath);
        }

        try {
            $this->artisan('dkript:install', [
                '--no-interaction' => true,
                '--db-connection' => 'sqlite',
                '--db-database' => $testSqlitePath,
                '--admin-name' => 'Admin SQLite',
                '--admin-email' => 'sqlite.admin@example.com',
                '--admin-password' => 'SqlitePassword123!',
                '--skip-migration' => true,
            ])->run();

            $this->assertFileExists($testSqlitePath);
        } finally {
            if (file_exists($testSqlitePath)) {
                @unlink($testSqlitePath);
            }
        }
    }

    public function test_super_admin_can_login_after_clean_install(): void
    {
        $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Administrador Produccion',
            '--admin-email' => 'prodadmin@dkript.com',
            '--admin-password' => 'SuperProdPass2026!',
            '--skip-migration' => true,
        ])->run();

        // Iniciar sesión con las credenciales recién creadas
        $response = $this->post('/login', [
            'email' => 'prodadmin@dkript.com',
            'password' => 'SuperProdPass2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Verificar que la sesión tiene permisos de Super Admin
        $response->assertSessionHas('permission_matrix', ['*']);
        $response->assertSessionHas('mypermits', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
    }

    /**
     * Test A: Después de php artisan db:seed existen roles, permisos, opciones, parámetros,
     * pero NO existen admin@dkript.com ni demo@dkript.com.
     */
    public function test_a_db_seed_creates_structural_data_without_demo_users(): void
    {
        $exitCode = $this->artisan('db:seed')->run();
        $this->assertSame(0, $exitCode);

        $this->assertDatabaseHas('roles', ['id' => 1, 'name' => 'Super Administrador']);
        $this->assertDatabaseHas('roles', ['id' => 2, 'name' => 'Operador / Usuario Regular']);
        $this->assertSame(6, MenuOption::count());
        $this->assertSame(30, Permission::count());
        $this->assertSame(1, Parameter::count());

        $this->assertDatabaseMissing('users', ['email' => 'admin@dkript.com']);
        $this->assertDatabaseMissing('users', ['email' => 'demo@dkript.com']);
        $this->assertSame(0, User::count());
    }

    /**
     * Test B: Después de php artisan db:seed, Parameter::first()->installed_at debe ser estrictamente null.
     */
    public function test_b_db_seed_leaves_parameter_installed_at_null(): void
    {
        $exitCode = $this->artisan('db:seed')->run();
        $this->assertSame(0, $exitCode);

        $parameter = Parameter::first();
        $this->assertNotNull($parameter);
        $this->assertNull($parameter->installed_at);
    }

    /**
     * Test C: Después de php artisan dkript:install, Parameter::first()->installed_at debe ser NOT NULL.
     */
    public function test_c_installer_sets_parameter_installed_at_timestamp(): void
    {
        $exitCode = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Super Admin Test C',
            '--admin-email' => 'admin.testc@example.com',
            '--admin-password' => 'SafePassword123!',
            '--skip-migration' => true,
        ])->run();

        $this->assertSame(0, $exitCode);

        $parameter = Parameter::first();
        $this->assertNotNull($parameter);
        $this->assertNotNull($parameter->installed_at);
    }

    /**
     * Test D: Después de php artisan db:seed --class=DemoSeeder SÍ existen admin@dkript.com y demo@dkript.com.
     */
    public function test_d_explicit_demo_seeder_creates_demo_users(): void
    {
        // 1. Ejecutar db:seed estándar (solo core)
        $this->artisan('db:seed')->run();
        $this->assertSame(0, User::count());

        // 2. Invocar explícitamente DemoSeeder
        $exitCode = $this->artisan('db:seed', ['--class' => 'DemoSeeder'])->run();
        $this->assertSame(0, $exitCode);

        $this->assertDatabaseHas('users', ['email' => 'admin@dkript.com']);
        $this->assertDatabaseHas('users', ['email' => 'demo@dkript.com']);
        $this->assertSame(2, User::count());
    }

    /**
     * Test E: dkript:install nunca crea usuarios demo por defecto.
     */
    public function test_e_installer_never_creates_demo_users_unless_explicitly_provided(): void
    {
        $exitCode = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Custom Core Administrator',
            '--admin-email' => 'custom.admin@enterprise.internal',
            '--admin-password' => 'EnterpriseSecret2026!',
            '--skip-migration' => true,
        ])->run();

        $this->assertSame(0, $exitCode);

        $this->assertDatabaseMissing('users', ['email' => 'admin@dkript.com']);
        $this->assertDatabaseMissing('users', ['email' => 'demo@dkript.com']);
        $this->assertSame(1, User::count());
        $this->assertDatabaseHas('users', ['email' => 'custom.admin@enterprise.internal']);
    }

    /**
     * Test de Protección: Garantiza que la ejecución del instalador bajo testing jamás altera el archivo .env del desarrollador.
     */
    public function test_installer_execution_in_testing_does_not_modify_developer_env_file(): void
    {
        $realEnvPath = base_path('.env');
        $this->assertFileExists($realEnvPath);

        // 1. Calcular hash SHA-256 inicial de .env real
        $initialHash = hash_file('sha256', $realEnvPath);

        // 2. Ejecutar dkript:install en modo testing
        $exitCode = $this->artisan('dkript:install', [
            '--no-interaction' => true,
            '--db-connection' => 'sqlite',
            '--db-database' => ':memory:',
            '--admin-name' => 'Admin Protection Test',
            '--admin-email' => 'protection.admin@example.com',
            '--admin-password' => 'ProtectionPass1234!',
            '--app-name' => 'Temporary Name Never In Real Env',
            '--app-url' => 'http://temporary-never-in-env.test',
            '--skip-migration' => true,
        ])->run();

        $this->assertSame(0, $exitCode);

        // 3. Comprobar que el hash del .env real permanece 100% idéntico
        $finalHash = hash_file('sha256', $realEnvPath);
        $this->assertSame($initialHash, $finalHash, 'El archivo .env real del desarrollador fue modificado por el test.');

        // 4. Comprobar que no contiene valores del test
        $envContent = file_get_contents($realEnvPath);
        $this->assertStringNotContainsString('Temporary Name Never In Real Env', $envContent);
        $this->assertStringNotContainsString('temporary-never-in-env.test', $envContent);
    }

    /**
     * Test de Aislamiento con archivo temporal: Verifica que el instalador modifica archivos .env aislados
     * cuando se especifica --env-file, sin tocar el .env real.
     */
    public function test_installer_modifies_isolated_temp_env_file_without_affecting_real_env(): void
    {
        $realEnvPath = base_path('.env');
        $initialRealHash = hash_file('sha256', $realEnvPath);

        $tempEnvPath = tempnam(sys_get_temp_dir(), 'dkript_test_env_');
        file_put_contents($tempEnvPath, "APP_NAME=OldAppName\nDB_DATABASE=old_db\n");

        try {
            $exitCode = $this->artisan('dkript:install', [
                '--no-interaction' => true,
                '--db-connection' => 'sqlite',
                '--db-database' => ':memory:',
                '--admin-name' => 'Admin Temp Env',
                '--admin-email' => 'tempenv.admin@example.com',
                '--admin-password' => 'TempEnvPassword123!',
                '--app-name' => 'Isolated Custom App Name',
                '--env-file' => $tempEnvPath,
                '--skip-migration' => true,
            ])->run();

            $this->assertSame(0, $exitCode);

            // El archivo temporal SÍ fue actualizado
            $tempContent = file_get_contents($tempEnvPath);
            $this->assertStringContainsString('APP_NAME="Isolated Custom App Name"', $tempContent);

            // El .env real del desarrollador NO sufrió alteraciones
            $finalRealHash = hash_file('sha256', $realEnvPath);
            $this->assertSame($initialRealHash, $finalRealHash, 'El archivo .env real fue alterado a pesar de usar un archivo temporal.');
        } finally {
            if (file_exists($tempEnvPath)) {
                @unlink($tempEnvPath);
            }
        }
    }
}
