<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Profile;
use App\Models\Parameter;
use Database\Seeders\CoreSeeder;
use PDO;
use PDOException;
use Throwable;

class InstallDkriptCommand extends Command
{
    /**
     * Nombre y firma del comando Artisan.
     */
    protected $signature = 'dkript:install
                            {--db-connection= : Motor de base de datos (mysql, mariadb, pgsql, sqlite)}
                            {--db-host= : Host del servidor de base de datos}
                            {--db-port= : Puerto de la base de datos}
                            {--db-database= : Nombre de la base de datos o archivo sqlite}
                            {--db-username= : Usuario de la base de datos}
                            {--db-password= : Contraseña de la base de datos}
                            {--admin-name= : Nombre del Super Administrador}
                            {--admin-email= : Correo electrónico del Super Administrador}
                            {--admin-password= : Contraseña del Super Administrador}
                            {--app-name= : Nombre de la aplicación}
                            {--app-url= : URL de la aplicación}
                            {--force : Forzar instalación aun si se detecta instalación previa}
                            {--skip-migration : Omitir ejecución de migraciones}
                            {--env-file= : Ruta personalizada de archivo .env para pruebas aisladas}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Instala y configura Dkript Core de forma interactiva y reproducible.';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $this->printHeader();

        // 1. Configuración de la Aplicación (APP_NAME, APP_URL)
        $appName = $this->configureApplicationName();
        $appUrl = $this->configureApplicationUrl();

        // 2. Comprobar / Generar APP_KEY
        $this->ensureAppKey();

        // 3. Selección y Configuración de Motor de Base de Datos
        $dbConfig = $this->configureDatabase();
        if ($dbConfig === null) {
            $this->error("Instalación abortada en la configuración de base de datos.");
            return Command::FAILURE;
        }

        // 4. Detección de Instalación Previa
        if ($this->detectPreviousInstallation($dbConfig['driver'])) {
            $this->newLine();
            $this->warn("Dkript Core parece estar instalado.");
            
            $continue = $this->input->isInteractive() && !$this->option('force')
                ? $this->confirm("¿Deseas continuar de todas formas? (Esto no sobrescribirá ni eliminará datos existentes)", false)
                : $this->option('force');

            if (!$continue) {
                $this->info("Instalación cancelada por el usuario sin realizar alteraciones.");
                return Command::SUCCESS;
            }
        }

        // 5. Ejecución Segura de Migraciones
        if (!$this->option('skip-migration')) {
            $runMigrations = $this->input->isInteractive()
                ? $this->confirm("¿Deseas ejecutar las migraciones de la base de datos ahora?", true)
                : true;

            if ($runMigrations) {
                $this->info("Ejecutando migraciones...");
                $exitCode = $this->call('migrate', ['--force' => true]);
                if ($exitCode !== 0) {
                    $this->error("Ocurrió un error al ejecutar las migraciones.");
                    return Command::FAILURE;
                }
                $this->line("Migrations: <info>✓</info>");
            }
        }

        // 6. Carga de Datos Estructurales del Core (CoreSeeder)
        $this->info("Cargando datos estructurales del Core...");
        try {
            DB::connection($dbConfig['driver'])->transaction(function () use ($appName) {
                $seeder = new CoreSeeder();
                $seeder->run();

                // Sincronización explícita del branding configurado en parámetros
                $param = Parameter::find(1);
                if ($param) {
                    $param->system_name = $appName;
                    $param->mail_from_name = $appName;
                    $param->save();
                }
            });
            $this->line("Core data: <info>✓</info>");
        } catch (Throwable $e) {
            $this->error("Error al poblar datos estructurales: " . $this->sanitizeErrorMessage($e->getMessage()));
            return Command::FAILURE;
        }

        // 7. Creación Segura del Primer Super Administrador
        $adminResult = $this->configureSuperAdministrator($dbConfig['driver']);
        if (!$adminResult['success']) {
            $this->error($adminResult['message']);
            return Command::FAILURE;
        }

        // 8. Resumen Final
        $this->printSummary($appName, $dbConfig, $adminResult['email']);

        return Command::SUCCESS;
    }

    /**
     * Muestra la cabecera del instalador.
     */
    protected function printHeader(): void
    {
        $this->newLine();
        $this->line("<fg=cyan>--------------------------------------------------</>");
        $this->line("<fg=cyan;options=bold> DKRIPT CORE</>");
        $this->line("<fg=white> Application Installer</>");
        $this->line("<fg=cyan>--------------------------------------------------</>");
        $this->newLine();
    }

    /**
     * Configura el nombre de la aplicación.
     */
    protected function configureApplicationName(): string
    {
        $defaultName = env('APP_NAME', 'Dkript Core');
        if ($this->option('app-name')) {
            $name = (string) $this->option('app-name');
        } elseif ($this->input->isInteractive()) {
            $name = $this->ask('Nombre de la aplicación', $defaultName);
        } else {
            $name = $defaultName;
        }

        $this->updateEnvFile(['APP_NAME' => $name]);
        config(['app.name' => $name]);

        return $name;
    }

    /**
     * Configura la URL de la aplicación.
     */
    protected function configureApplicationUrl(): string
    {
        $defaultUrl = env('APP_URL', 'http://localhost:8000');
        if ($this->option('app-url')) {
            $url = (string) $this->option('app-url');
        } elseif ($this->input->isInteractive()) {
            $url = $this->ask('URL de la aplicación', $defaultUrl);
        } else {
            $url = $defaultUrl;
        }

        $this->updateEnvFile(['APP_URL' => $url]);
        config(['app.url' => $url]);

        return $url;
    }

    /**
     * Comprueba y genera la APP_KEY sin reemplazarla si ya existe.
     */
    protected function ensureAppKey(): void
    {
        $currentKey = env('APP_KEY') ?: config('app.key');
        if (empty($currentKey)) {
            $this->info("Generando clave de cifrado de la aplicación (APP_KEY)...");
            $this->call('key:generate', ['--force' => true]);
            $this->line("APP_KEY: <info>✓</info> (Generada exitosamente)");
        } else {
            $this->line("APP_KEY: <info>✓</info> (Clave existente conservada)");
        }
    }

    /**
     * Configuración del motor de base de datos.
     */
    protected function configureDatabase(): ?array
    {
        $engines = [
            'MySQL' => 'mysql',
            'MariaDB' => 'mariadb',
            'PostgreSQL' => 'pgsql',
            'SQLite' => 'sqlite',
        ];

        while (true) {
            $connectionOption = $this->option('db-connection');
            if ($connectionOption && in_array(strtolower($connectionOption), array_values($engines))) {
                $driver = strtolower($connectionOption);
            } elseif ($this->input->isInteractive()) {
                $selected = $this->choice(
                    'Motor de base de datos',
                    ['MySQL (Predeterminado)', 'MariaDB', 'PostgreSQL', 'SQLite'],
                    0
                );

                if (str_starts_with($selected, 'MySQL')) {
                    $driver = 'mysql';
                } elseif (str_starts_with($selected, 'MariaDB')) {
                    $driver = 'mariadb';
                } elseif (str_starts_with($selected, 'PostgreSQL')) {
                    $driver = 'pgsql';
                } else {
                    $driver = 'sqlite';
                }
            } else {
                $driver = 'mysql';
            }

            // Recolección de parámetros específicos del motor
            if ($driver === 'sqlite') {
                $dbPath = $this->option('db-database')
                    ?: ($this->input->isInteractive()
                        ? $this->ask('Ruta de la base de datos SQLite', 'database/database.sqlite')
                        : 'database/database.sqlite');

                if ($dbPath === ':memory:') {
                    $resolvedPath = ':memory:';
                } else {
                    $resolvedPath = str_starts_with($dbPath, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $dbPath)
                        ? $dbPath
                        : base_path($dbPath);

                    // Crear archivo SQLite si no existe
                    $dir = dirname($resolvedPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    if (!file_exists($resolvedPath)) {
                        touch($resolvedPath);
                        $this->info("Archivo SQLite creado en: {$resolvedPath}");
                    }
                }

                $config = [
                    'driver' => 'sqlite',
                    'database' => $resolvedPath,
                    'host' => '',
                    'port' => '',
                    'username' => '',
                    'password' => '',
                ];
            } else {
                $defaultPort = $driver === 'pgsql' ? '5432' : '3306';
                $defaultUser = $driver === 'pgsql' ? 'postgres' : 'root';

                $host = $this->option('db-host')
                    ?: ($this->input->isInteractive() ? $this->ask('Host de base de datos', '127.0.0.1') : '127.0.0.1');

                $port = $this->option('db-port')
                    ?: ($this->input->isInteractive() ? $this->ask('Puerto de base de datos', $defaultPort) : $defaultPort);

                $database = $this->option('db-database')
                    ?: ($this->input->isInteractive() ? $this->ask('Nombre de la base de datos', 'dkript_core') : 'dkript_core');

                $username = $this->option('db-username')
                    ?: ($this->input->isInteractive() ? $this->ask('Usuario de base de datos', $defaultUser) : $defaultUser);

                $password = $this->option('db-password') !== null
                    ? (string) $this->option('db-password')
                    : ($this->input->isInteractive() ? (string) $this->secret('Contraseña de base de datos (opcional)') : '');

                $config = [
                    'driver' => $driver,
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                ];
            }

            // Validación de Conexión
            $validated = $this->validateAndPrepareDatabase($config);
            if ($validated) {
                // Guardar en .env
                $envUpdates = [
                    'DB_CONNECTION' => $config['driver'],
                ];
                if ($config['driver'] === 'sqlite') {
                    $envUpdates['DB_DATABASE'] = $config['database'];
                } else {
                    $envUpdates['DB_HOST'] = $config['host'];
                    $envUpdates['DB_PORT'] = $config['port'];
                    $envUpdates['DB_DATABASE'] = $config['database'];
                    $envUpdates['DB_USERNAME'] = $config['username'];
                    $envUpdates['DB_PASSWORD'] = $config['password'];
                }

                $this->updateEnvFile($envUpdates);

                // Configurar conexión en runtime
                $this->applyRuntimeDatabaseConfig($config);

                $this->line("Database connection: <info>✓</info>");
                return $config;
            }

            if (!$this->input->isInteractive()) {
                return null;
            }

            if (!$this->confirm('¿Deseas volver a intentar la configuración de base de datos?', true)) {
                return null;
            }
        }
    }

    /**
     * Valida la conexión a la base de datos y permite crear la base MySQL si no existe.
     */
    protected function validateAndPrepareDatabase(array &$config): bool
    {
        $driver = $config['driver'];

        if ($driver === 'sqlite') {
            if ($config['database'] === ':memory:') {
                return true;
            }
            try {
                $pdo = new PDO("sqlite:" . $config['database']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return true;
            } catch (Throwable $e) {
                $this->error("Error al conectar con SQLite: " . $this->sanitizeErrorMessage($e->getMessage()));
                return false;
            }
        }

        $host = $config['host'];
        $port = $config['port'];
        $dbName = $config['database'];
        $user = $config['username'];
        $pass = $config['password'];

        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                return true;
            } catch (PDOException $e) {
                // Código 1049: Base de datos desconocida (servidor y credenciales válidos)
                if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                    $this->warn("El servidor MySQL es accesible pero la base de datos '{$dbName}' no existe.");
                    
                    $createDb = $this->input->isInteractive()
                        ? $this->confirm("¿Deseas crear la base de datos '{$dbName}' ahora?", true)
                        : false;

                    if ($createDb) {
                        try {
                            $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                            $serverPdo = new PDO($serverDsn, $user, $pass, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_TIMEOUT => 5,
                            ]);
                            $serverPdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                            $this->info("Base de datos '{$dbName}' creada exitosamente en MySQL.");
                            return true;
                        } catch (PDOException $createEx) {
                            $this->error("No se pudo crear la base de datos automáticamente (permisos insuficientes).");
                            $this->line("Por favor crea la base de datos manualmente ejecutando en MySQL:");
                            $this->line("  <fg=yellow>CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</>");
                            return false;
                        }
                    } else {
                        $this->line("Por favor crea la base de datos manualmente ejecutando en MySQL:");
                        $this->line("  <fg=yellow>CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</>");
                        return false;
                    }
                }

                $this->error("Error de conexión a {$driver}: " . $this->sanitizeErrorMessage($e->getMessage()));
                return false;
            }
        }

        if ($driver === 'pgsql') {
            try {
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                return true;
            } catch (PDOException $e) {
                // Código 3D000: Database does not exist
                if ($e->getCode() === '3D000' || str_contains($e->getMessage(), 'does not exist')) {
                    $this->warn("La base de datos PostgreSQL '{$dbName}' no existe.");
                    $createDb = $this->input->isInteractive()
                        ? $this->confirm("¿Deseas intentar crear la base de datos '{$dbName}'?", true)
                        : false;

                    if ($createDb) {
                        try {
                            $serverDsn = "pgsql:host={$host};port={$port};dbname=postgres";
                            $serverPdo = new PDO($serverDsn, $user, $pass, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_TIMEOUT => 5,
                            ]);
                            $serverPdo->exec("CREATE DATABASE \"{$dbName}\" ENCODING 'UTF8';");
                            $this->info("Base de datos '{$dbName}' creada exitosamente en PostgreSQL.");
                            return true;
                        } catch (Throwable $createEx) {
                            $this->error("No se pudo crear la base de datos automáticamente.");
                            $this->line("Por favor créala manualmente ejecutando: CREATE DATABASE \"{$dbName}\";");
                            return false;
                        }
                    }
                }

                $this->error("Error de conexión a PostgreSQL: " . $this->sanitizeErrorMessage($e->getMessage()));
                return false;
            }
        }

        return false;
    }

    /**
     * Aplica la configuración de base de datos en tiempo de ejecución.
     */
    protected function applyRuntimeDatabaseConfig(array $config): void
    {
        $driver = $config['driver'];
        config(['database.default' => $driver]);

        if (app()->environment('testing') && config('database.connections.sqlite.database') === ':memory:') {
            return;
        }

        if ($driver === 'sqlite') {
            config(['database.connections.sqlite.database' => $config['database']]);
        } else {
            config([
                "database.connections.{$driver}.host" => $config['host'],
                "database.connections.{$driver}.port" => $config['port'],
                "database.connections.{$driver}.database" => $config['database'],
                "database.connections.{$driver}.username" => $config['username'],
                "database.connections.{$driver}.password" => $config['password'],
            ]);
        }

        DB::purge($driver);
    }

    /**
     * Detecta si Dkript Core ya fue instalado previamente en la base de datos conectada.
     */
    protected function detectPreviousInstallation(string $driver): bool
    {
        try {
            if (!Schema::connection($driver)->hasTable('users') || !Schema::connection($driver)->hasTable('parameters')) {
                return false;
            }

            $hasSuperAdmin = User::on($driver)
                ->whereHas('profile', fn($q) => $q->where('role_id', 1))
                ->exists();

            $param = Parameter::on($driver)->first();
            $hasInstalledAt = $param && !empty($param->installed_at);

            return $hasSuperAdmin || $hasInstalledAt;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Configuración y creación del primer Super Administrador.
     */
    protected function configureSuperAdministrator(string $driver): array
    {
        $existingAdmin = null;
        try {
            if (Schema::connection($driver)->hasTable('users')) {
                $existingAdmin = User::on($driver)
                    ->whereHas('profile', fn($q) => $q->where('role_id', 1))
                    ->first();
            }
        } catch (Throwable $e) {
            // No tables yet
        }

        if ($existingAdmin) {
            $this->info("Ya existe un Super Administrador registrado en el sistema: <comment>{$existingAdmin->email}</comment>");

            $createAdditional = $this->input->isInteractive() && !$this->option('force')
                ? $this->confirm("¿Deseas registrar un Super Administrador adicional?", false)
                : false;

            if (!$createAdditional) {
                return [
                    'success' => true,
                    'email' => $existingAdmin->email,
                    'message' => 'Super Administrador existente conservado.',
                ];
            }
        }

        // Obtener Nombre
        if ($this->option('admin-name')) {
            $name = (string) $this->option('admin-name');
        } elseif ($this->input->isInteractive()) {
            $name = $this->ask('Nombre del Super Administrador', 'Administrador');
        } else {
            $name = 'Administrador';
        }

        // Obtener Correo Electrónico
        $email = null;
        if ($this->option('admin-email')) {
            $email = (string) $this->option('admin-email');
            $validator = Validator::make(['email' => $email], [
                'email' => ['required', 'email', 'unique:users,email'],
            ]);
            if ($validator->fails()) {
                return [
                    'success' => false,
                    'email' => '',
                    'message' => 'El correo electrónico proporcionado en --admin-email no es válido o ya está en uso: ' . $validator->errors()->first('email'),
                ];
            }
        } elseif ($this->input->isInteractive()) {
            do {
                $emailInput = $this->ask('Correo electrónico del Super Administrador');
                $validator = Validator::make(['email' => $emailInput], [
                    'email' => ['required', 'email', 'unique:users,email'],
                ]);
                if ($validator->fails()) {
                    $this->error($validator->errors()->first('email'));
                } else {
                    $email = $emailInput;
                }
            } while (!$email);
        } else {
            return [
                'success' => false,
                'email' => '',
                'message' => 'En modo no interactivo debes proporcionar --admin-email y --admin-password.',
            ];
        }

        // Obtener Contraseña
        $password = null;
        if ($this->option('admin-password')) {
            $password = (string) $this->option('admin-password');
            if (strlen($password) < 12) {
                return [
                    'success' => false,
                    'email' => '',
                    'message' => 'La contraseña proporcionada en --admin-password debe tener al menos 12 caracteres.',
                ];
            }
        } elseif ($this->input->isInteractive()) {
            do {
                $passInput = $this->secret('Contraseña del Super Administrador (mínimo 12 caracteres)');
                if (strlen($passInput) < 12) {
                    $this->error('Por política de seguridad, la contraseña debe contener al menos 12 caracteres.');
                    continue;
                }
                $confirmInput = $this->secret('Confirmar contraseña');
                if ($passInput !== $confirmInput) {
                    $this->error('Las contraseñas no coinciden. Por favor intenta nuevamente.');
                    continue;
                }
                $password = $passInput;
            } while (!$password);
        } else {
            return [
                'success' => false,
                'email' => '',
                'message' => 'En modo no interactivo debes proporcionar --admin-email y --admin-password.',
            ];
        }

        // Creación Transaccional del Administrador y Perfil RBAC
        try {
            DB::connection($driver)->transaction(function () use ($driver, $name, $email, $password) {
                $user = User::on($driver)->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'status' => 1,
                ]);

                $parts = explode(' ', trim($name), 2);
                $firstName = $parts[0];
                $lastName = $parts[1] ?? 'Administrador';

                Profile::on($driver)->create([
                    'user_id' => $user->id,
                    'role_id' => 1, // Super Administrador
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'avatar' => 'assets/images/branding/logo-dkript-profile.png',
                ]);

                // Actualizar Parámetros con el correo de contacto y fecha de instalación
                $param = Parameter::on($driver)->first();
                if ($param) {
                    $param->installed_at = now();
                    if ($param->contact_email === 'soporte@dkript.com' || empty($param->contact_email)) {
                        $param->contact_email = $email;
                    }
                    $param->save();
                }
            });

            $this->line("Super Administrator: <info>✓</info> ({$email})");
            $this->line("RBAC: <info>✓</info> (Rol 'Super Administrador' asignado con acceso absoluto)");

            return [
                'success' => true,
                'email' => $email,
                'message' => 'Super Administrador creado exitosamente.',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'email' => '',
                'message' => 'Error al crear el Super Administrador: ' . $this->sanitizeErrorMessage($e->getMessage()),
            ];
        }
    }

    /**
     * Muestra el resumen de la instalación.
     */
    protected function printSummary(string $appName, array $dbConfig, string $adminEmail): void
    {
        $driverName = strtoupper($dbConfig['driver']);
        $dbTarget = $dbConfig['driver'] === 'sqlite' ? $dbConfig['database'] : $dbConfig['database'];

        $this->newLine();
        $this->line("<fg=cyan>--------------------------------------------------</>");
        $this->line("<fg=cyan;options=bold> DKRIPT CORE — Resumen de Instalación</>");
        $this->line("<fg=cyan>--------------------------------------------------</>");
        $this->line("Application:          <info>{$appName}</info>");
        $this->line("Database:             <info>{$driverName} ({$dbTarget})</info>");
        $this->line("Database connection:  <info>✓</info>");
        $this->line("Migrations:           <info>✓</info>");
        $this->line("Core data:            <info>✓</info>");
        $this->line("Super Administrator:  <info>✓ ({$adminEmail})</info>");
        $this->line("RBAC:                 <info>✓</info>");
        $this->line("<fg=cyan>--------------------------------------------------</>");
        $this->newLine();
        $this->info("Instalación completada exitosamente.");
        $this->line("Para iniciar el servidor de desarrollo, ejecuta:");
        $this->newLine();
        $this->line("  <fg=yellow>php artisan serve</>");
        $this->newLine();
    }

    /**
     * Actualiza variables en el archivo .env de forma no destructiva.
     */
    protected function updateEnvFile(array $keyValues): void
    {
        $customEnv = $this->hasOption('env-file') ? $this->option('env-file') : null;

        // Si estamos en entorno de testing y no se especificó un archivo temporal aislado, NUNCA tocar .env
        if (app()->environment('testing') && empty($customEnv)) {
            return;
        }

        $envPath = !empty($customEnv) ? $customEnv : base_path('.env');

        // Salvaguarda inviolable: en entorno testing jamás modificar el .env real del desarrollador
        if (app()->environment('testing') && realpath($envPath) === realpath(base_path('.env'))) {
            return;
        }

        if (!file_exists($envPath)) {
            $examplePath = base_path('.env.example');
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                touch($envPath);
            }
        }

        $content = file_get_contents($envPath);

        foreach ($keyValues as $key => $value) {
            // Formatear valor: comillas si contiene espacios o caracteres especiales
            $formattedValue = $this->formatEnvValue((string) $value);

            // Reemplazar si existe la variable
            $pattern = "/^" . preg_quote($key, '/') . "=.*$/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$formattedValue}", $content);
            } else {
                // Agregar al final si no existe
                $content = rtrim($content) . "\n{$key}={$formattedValue}\n";
            }
        }

        file_put_contents($envPath, $content);
    }

    /**
     * Formatea un valor para .env.
     */
    protected function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        // Normalizar separadores de ruta en Windows a forward slashes para compatibilidad con dotenv
        $value = str_replace('\\', '/', $value);

        if (preg_match('/[\s"\'#=]/', $value)) {
            $escaped = str_replace('"', '\"', $value);
            return "\"{$escaped}\"";
        }

        return $value;
    }

    /**
     * Sanitiza los mensajes de error para no exponer contraseñas en consola.
     */
    protected function sanitizeErrorMessage(string $message): string
    {
        // Reemplazar cualquier patrón 'password=...' o 'pwd=...'
        $clean = preg_replace('/password=[^;\s&]+/i', 'password=******', $message);
        $clean = preg_replace('/pwd=[^;\s&]+/i', 'pwd=******', $clean);
        return $clean;
    }
}
