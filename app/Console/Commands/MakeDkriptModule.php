<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Models\MenuOption;
use App\Models\Permission;
use App\Models\Role;

class MakeDkriptModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dkript:make-module
                            {name : Nombre del módulo en singular (ej. Customer, ProductCategory, Invoice)}
                            {--fields= : Campos separados por coma (ej. name:string,price:decimal(12,2),status:enum(active,inactive),category_id:foreignId(categories:cascade:restrict):nullable)}
                            {--relationships= : Relaciones explícitas (ej. hasMany:Invoice,belongsTo:Category,hasOne:Profile)}
                            {--icon=bi-box : Icono de Bootstrap Icons (ej. bi-box-seam, bi-people, bi-tag)}
                            {--soft-deletes : Habilitar SoftDeletes en migración y modelo}
                            {--no-timestamps : Desactivar timestamps automáticos created_at y updated_at}
                            {--dry-run : Simular la generación sin crear archivos ni registros}
                            {--force : Sobrescribir archivos si ya existen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un nuevo módulo Enterprise completo cumpliendo con la arquitectura RBAC de 5 posiciones de Dkript Core';

    /**
     * Testing seam: permite a las pruebas automatizadas provocar fallos controlados para verificar el rollback.
     * @var string|null
     */
    public static ?string $testingFailAt = null;

    /**
     * Pila de archivos nuevos creados durante la ejecución para eliminación compensatoria en rollback.
     */
    protected array $createdFiles = [];

    /**
     * Pila de directorios nuevos creados durante la ejecución para eliminación compensatoria en rollback.
     */
    protected array $createdDirectories = [];

    /**
     * Mapa de archivos preexistentes sobrescritos con --force [filePath => originalContent] para restauración en rollback.
     */
    protected array $overwrittenFiles = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = trim($this->argument('name'));

        // 1. Validación de Seguridad del Nombre (Parte AH, AI)
        if (!$this->isValidModuleName($rawName)) {
            $this->error("El nombre de módulo '{$rawName}' es inválido o no cumple las directrices de seguridad.");
            $this->line("• Debe iniciar con letra y contener únicamente caracteres alfanuméricos (ej. Customer, ProductCategory, ProjectTask).");
            $this->line("• No se permiten palabras reservadas de PHP ni secuencias de path traversal ('..', '/', '\\').");
            return 1;
        }

        $modelName = Str::studly(Str::singular($rawName));
        $modelPlural = Str::pluralStudly($modelName);
        $tableName = Str::snake($modelPlural);
        $variableSingular = Str::camel($modelName);
        $variablePlural = Str::camel($modelPlural);
        $routePrefix = Str::kebab($modelPlural);
        $viewFolder = Str::kebab($modelPlural);
        $moduleTitle = Str::headline($modelPlural);
        $icon = $this->option('icon') ?: 'bi-box';
        $force = (bool)$this->option('force');
        $dryRun = (bool)$this->option('dry-run');
        $softDeletes = (bool)$this->option('soft-deletes');
        $timestamps = !(bool)$this->option('no-timestamps');

        $this->info("===============================================================");
        $this->info("   🚀 DKRIPT CORE v1.0 — GENERADOR DE MÓDULOS ENTERPRISE       ");
        $this->info("===============================================================");
        $this->line("• Módulo:       <fg=bright-cyan>{$moduleTitle}</>");
        $this->line("• Modelo:       <fg=bright-yellow>{$modelName}</>");
        $this->line("• Tabla:        <fg=bright-green>{$tableName}</>");
        $this->line("• Prefijo Ruta: <fg=bright-magenta>/{$routePrefix}</>");
        $this->line("• Icono:        <fg=bright-blue>{$icon}</>");
        $this->line("• SoftDeletes:  <fg=gray>" . ($softDeletes ? 'Sí' : 'No') . "</>");
        $this->line("• Timestamps:   <fg=gray>" . ($timestamps ? 'Sí' : 'No') . "</>");
        if ($dryRun) {
            $this->line("• Modo:         <fg=bright-red>[DRY-RUN] Simulación activa (Cero cambios en disco/BD)</>");
        }
        $this->newLine();

        // 2. Procesar Definición de Campos y Relaciones
        $fields = $this->parseFields($this->option('fields'));
        $relationships = $this->parseRelationships($this->option('relationships'), $fields);

        $this->info("📋 Campos configurados: " . count($fields));
        foreach ($fields as $f) {
            $mods = [];
            if ($f['nullable']) $mods[] = 'nullable';
            if ($f['unique']) $mods[] = 'unique';
            if ($f['index']) $mods[] = 'index';
            if ($f['unsigned']) $mods[] = 'unsigned';
            if ($f['default'] !== null) $mods[] = 'default:' . $f['default'];
            if ($f['type'] === 'foreignId' && !empty($f['foreign_table'])) {
                $mods[] = "constrained:{$f['foreign_table']}";
            }
            if ($f['type'] === 'enum' && !empty($f['enum_values'])) {
                $mods[] = "values:" . implode(',', $f['enum_values']);
            }
            if ($f['type'] === 'decimal') {
                $mods[] = "precision:{$f['precision']},scale:{$f['scale']}";
            }
            $modText = !empty($mods) ? ' (' . implode(', ', $mods) . ')' : '';
            $this->line("   - <fg=yellow>{$f['name']}</>: <fg=gray>{$f['type']}{$modText}</>");
        }

        if (!empty($relationships)) {
            $this->newLine();
            $this->info("🔗 Relaciones Eloquent: " . count($relationships));
            foreach ($relationships as $rel) {
                $this->line("   - <fg=cyan>{$rel['method']}()</>: <fg=gray>{$rel['type']}(\\App\\Models\\{$rel['model']}::class)</>");
            }
        }
        $this->newLine();

        // 3. Verificación Previa de Colisiones (Parte AE)
        $targetFiles = $this->getTargetFilesList($modelName, $tableName, $viewFolder, $routePrefix);
        if (!$force && !$dryRun) {
            $collisions = $this->detectCollisions($targetFiles, $tableName);
            if (!empty($collisions)) {
                $this->error("Se detectaron colisiones con archivos existentes:");
                foreach ($collisions as $colFile) {
                    $this->line("   - <fg=red>{$colFile}</>");
                }
                $this->warn("Operación abortada para proteger el código preexistente. Use --force para sobrescribir.");
                return 1;
            }
        }

        // 4. Si es DRY-RUN, mostrar resumen y finalizar limpiamente (Parte AF)
        if ($dryRun) {
            $this->info("===============================================================");
            $this->info("   🔍 SIMULACIÓN DRY-RUN COMPLETADA (ARCHIVOS A GENERAR)       ");
            $this->info("===============================================================");
            foreach ($targetFiles as $type => $path) {
                $this->line(" • [{$type}] <fg=green>{$path}</>");
            }
            $this->newLine();
            $this->line("• RBAC: Se registraría MenuOption '{$moduleTitle}' y 5 permisos (Posiciones 1..5).");
            $this->line("• Rutas: Se generaría archivo modular independiente en routes/modules/{$routePrefix}.php.");
            $this->info("✓ Simulación exitosa. No se realizaron modificaciones físicas ni en base de datos.");
            return 0;
        }

        // 5. Ejecución con Rollback Compensatorio Multinivel
        $this->createdFiles = [];
        $this->createdDirectories = [];
        $this->overwrittenFiles = [];

        try {
            DB::beginTransaction();

            // A. Generar Migración
            $migrationFile = $this->generateMigration($tableName, $fields, $softDeletes, $timestamps, $force);
            $this->line("✓ Migración:     <fg=green>{$migrationFile}</>");

            // B. Generar Modelo Eloquent
            $modelFile = $this->generateModel($modelName, $tableName, $fields, $relationships, $softDeletes, $timestamps, $force);
            $this->line("✓ Modelo:        <fg=green>{$modelFile}</>");

            // C. Generar Form Requests
            $storeReqFile = $this->generateStoreRequest($modelName, $tableName, $fields, $force);
            $this->line("✓ Store Request: <fg=green>{$storeReqFile}</>");

            $updateReqFile = $this->generateUpdateRequest($modelName, $tableName, $variableSingular, $fields, $force);
            $this->line("✓ Update Request:<fg=green>{$updateReqFile}</>");

            // D. Generar Policy
            $policyFile = $this->generatePolicy($modelName, $force);
            $this->line("✓ Policy:        <fg=green>{$policyFile}</>");

            // E. Generar Factory
            $factoryFile = $this->generateFactory($modelName, $fields, $force);
            $this->line("✓ Factory:       <fg=green>{$factoryFile}</>");

            // F. Generar Controlador RBAC (5 Posiciones)
            $controllerFile = $this->generateController(
                $modelName,
                $moduleTitle,
                $variableSingular,
                $variablePlural,
                $tableName,
                $viewFolder,
                $routePrefix,
                $fields,
                $softDeletes,
                $force
            );
            $this->line("✓ Controlador:   <fg=green>{$controllerFile}</>");

            // G. Generar Vistas Blade (index, create, edit, show)
            $viewFiles = $this->generateViews(
                $modelName,
                $moduleTitle,
                $variableSingular,
                $variablePlural,
                $routePrefix,
                $viewFolder,
                $fields,
                $icon,
                $softDeletes,
                $force
            );
            foreach ($viewFiles as $vType => $vPath) {
                $this->line("✓ Vista ({$vType}): <fg=green>{$vPath}</>");
            }

            // H. Generar Vista PDF
            $pdfFile = $this->generatePdfView($modelName, $moduleTitle, $variablePlural, $viewFolder, $fields, $force);
            $this->line("✓ Vista PDF:     <fg=green>{$pdfFile}</>");

            // I. Generar Feature Test para el nuevo módulo
            $testFile = $this->generateFeatureTest($modelName, $tableName, $routePrefix, $variableSingular, $fields, $softDeletes, $force);
            $this->line("✓ Test Feature:  <fg=green>{$testFile}</>");

            // J. Registrar Módulo y Permisos RBAC en Base de Datos
            $menuOptionId = $this->registerRbac($moduleTitle, $routePrefix, $icon);

            // K. Generar Archivo de Rutas Modular en routes/modules/{routePrefix}.php
            $routeFile = $this->generateRoutes($modelName, $routePrefix, $variableSingular, $menuOptionId, $moduleTitle, $force);
            $this->line("✓ Rutas Modulares:<fg=green>{$routeFile}</>");

            // Testing Seam para verificar rollback compensatorio en tests automatizados
            if (static::$testingFailAt === 'after_rbac') {
                throw new \RuntimeException("Fallo simulado para pruebas de rollback compensatorio (after_rbac).");
            }

            DB::commit();

            $this->newLine();
            $this->comment("👉 Paso siguiente recomendado: Ejecute 'php artisan migrate' para aplicar la nueva tabla en su base de datos.");

            $this->newLine();
            $this->info("===============================================================");
            $this->info("   ✅ MÓDULO '{$moduleTitle}' GENERADO CON ÉXITO AL 100%   ");
            $this->info("===============================================================");
            $this->line("• Acceso Web:     <fg=bright-cyan>" . url("/{$routePrefix}") . "</>");
            $this->line("• RBAC Option ID: <fg=bright-yellow>{$menuOptionId}</> (5 posiciones vinculadas)");
            $this->line("• Asignado a:     <fg=bright-green>Rol 1 (Super Administrador)</>");
            $this->newLine();

            return 0;

        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->performRollback($e);
            return 1;
        }
    }

    /**
     * Valida que el nombre del módulo sea seguro y sintácticamente válido en PHP.
     */
    protected function isValidModuleName(string $name): bool
    {
        if (empty($name)) return false;

        // Comprobar path traversal o caracteres peligrosos
        if (str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\')) {
            return false;
        }

        // Expresión regular para identificador de clase válido en PHP
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)) {
            return false;
        }

        // Lista de palabras reservadas en PHP
        $reserved = [
            'class', 'function', 'interface', 'trait', 'enum', 'namespace', 'use', 'extends', 'implements',
            'public', 'protected', 'private', 'static', 'final', 'abstract', 'const', 'return', 'yield',
            'try', 'catch', 'finally', 'throw', 'if', 'else', 'elseif', 'switch', 'case', 'default',
            'while', 'do', 'for', 'foreach', 'as', 'break', 'continue', 'new', 'clone', 'instanceof',
            'global', 'var', 'echo', 'print', 'die', 'exit', 'eval', 'empty', 'isset', 'unset',
            'list', 'array', 'callable', 'parent', 'self', 'string', 'int', 'float', 'bool', 'iterable', 'void',
            'null', 'true', 'false', 'match', 'fn', 'readonly'
        ];

        return !in_array(strtolower($name), $reserved);
    }

    /**
     * Parsea la cadena de campos --fields con soporte para tipos enriquecidos y modificadores.
     */
    protected function parseFields(?string $fieldsInput): array
    {
        if (empty($fieldsInput)) {
            return [
                [
                    'name' => 'name',
                    'type' => 'string',
                    'length' => 255,
                    'precision' => null,
                    'scale' => null,
                    'nullable' => false,
                    'unique' => false,
                    'index' => false,
                    'unsigned' => false,
                    'default' => null,
                    'foreign_table' => null,
                    'on_delete' => 'restrict',
                    'on_update' => 'cascade',
                    'enum_values' => [],
                    'label' => 'Nombre',
                ],
                [
                    'name' => 'description',
                    'type' => 'text',
                    'length' => null,
                    'precision' => null,
                    'scale' => null,
                    'nullable' => true,
                    'unique' => false,
                    'index' => false,
                    'unsigned' => false,
                    'default' => null,
                    'foreign_table' => null,
                    'on_delete' => 'restrict',
                    'on_update' => 'cascade',
                    'enum_values' => [],
                    'label' => 'Descripción',
                ],
                [
                    'name' => 'status',
                    'type' => 'boolean',
                    'length' => null,
                    'precision' => null,
                    'scale' => null,
                    'nullable' => false,
                    'unique' => false,
                    'index' => false,
                    'unsigned' => false,
                    'default' => '1',
                    'foreign_table' => null,
                    'on_delete' => 'restrict',
                    'on_update' => 'cascade',
                    'enum_values' => [],
                    'label' => 'Estatus',
                ],
            ];
        }

        $fields = [];
        
        // Separar campos por coma ignorando comas dentro de paréntesis ()
        $items = [];
        $current = '';
        $inParen = 0;
        $len = strlen($fieldsInput);
        for ($i = 0; $i < $len; $i++) {
            $char = $fieldsInput[$i];
            if ($char === '(') {
                $inParen++;
                $current .= $char;
            } elseif ($char === ')') {
                if ($inParen > 0) $inParen--;
                $current .= $char;
            } elseif ($char === ',' && $inParen === 0) {
                if (trim($current) !== '') $items[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        if (trim($current) !== '') {
            $items[] = trim($current);
        }

        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) continue;

            // Separar partes por dos puntos ignorando dos puntos dentro de paréntesis ()
            $parts = [];
            $currentPart = '';
            $inParenPart = 0;
            $pLen = strlen($item);
            for ($j = 0; $j < $pLen; $j++) {
                $pChar = $item[$j];
                if ($pChar === '(') {
                    $inParenPart++;
                    $currentPart .= $pChar;
                } elseif ($pChar === ')') {
                    if ($inParenPart > 0) $inParenPart--;
                    $currentPart .= $pChar;
                } elseif ($pChar === ':' && $inParenPart === 0) {
                    if (trim($currentPart) !== '') $parts[] = trim($currentPart);
                    $currentPart = '';
                } else {
                    $currentPart .= $pChar;
                }
            }
            if (trim($currentPart) !== '') {
                $parts[] = trim($currentPart);
            }

            $rawName = trim($parts[0]);
            $fieldName = Str::snake($rawName);
            $rawType = isset($parts[1]) ? trim($parts[1]) : 'string';

            $type = 'string';
            $length = 255;
            $precision = 12;
            $scale = 2;
            $foreignTable = null;
            $onDelete = 'restrict';
            $onUpdate = 'cascade';
            $enumValues = [];

            // Detectar tipos con parámetros: decimal(p,s), string(len), enum(v1,v2), foreignId(table:onDel:onUp)
            if (preg_match('/^decimal\((\d+),(\d+)\)$/i', $rawType, $m)) {
                $type = 'decimal';
                $precision = (int)$m[1];
                $scale = (int)$m[2];
            } elseif (preg_match('/^string\((\d+)\)$/i', $rawType, $m)) {
                $type = 'string';
                $length = (int)$m[1];
            } elseif (preg_match('/^enum\(([^)]+)\)$/i', $rawType, $m)) {
                $type = 'enum';
                $enumValues = array_map('trim', explode(',', $m[1]));
            } elseif (preg_match('/^foreignId\(([^)]+)\)$/i', $rawType, $m)) {
                $type = 'foreignId';
                $fkParts = explode(':', $m[1]);
                $foreignTable = trim($fkParts[0]);
                if (isset($fkParts[1])) $onDelete = trim($fkParts[1]);
                if (isset($fkParts[2])) $onUpdate = trim($fkParts[2]);
            } else {
                $cleanType = strtolower($rawType);
                if (in_array($cleanType, ['string', 'text', 'integer', 'int', 'biginteger', 'bigint', 'decimal', 'float', 'boolean', 'bool', 'date', 'datetime', 'timestamp', 'json', 'uuid', 'foreignid', 'enum'])) {
                    if ($cleanType === 'int') $type = 'integer';
                    elseif ($cleanType === 'bigint') $type = 'bigInteger';
                    elseif ($cleanType === 'float') $type = 'decimal';
                    elseif ($cleanType === 'bool') $type = 'boolean';
                    elseif ($cleanType === 'datetime') $type = 'dateTime';
                    elseif ($cleanType === 'foreignid') $type = 'foreignId';
                    else $type = $cleanType;
                } else {
                    $type = 'string';
                }
            }

            // Inferencia si es foreignId sin tabla explícita: client_id -> clients
            if ($type === 'foreignId' && empty($foreignTable)) {
                if (str_ends_with($fieldName, '_id')) {
                    $base = substr($fieldName, 0, -3);
                    $foreignTable = Str::snake(Str::pluralStudly($base));
                }
            }

            // Procesar modificadores
            $nullable = false;
            $unique = false;
            $index = false;
            $unsigned = false;
            $default = null;

            for ($i = 2; $i < count($parts); $i++) {
                $modifier = trim($parts[$i]);
                $modLower = strtolower($modifier);

                if ($modLower === 'nullable') {
                    $nullable = true;
                } elseif ($modLower === 'unique') {
                    $unique = true;
                } elseif ($modLower === 'index') {
                    $index = true;
                } elseif ($modLower === 'unsigned') {
                    $unsigned = true;
                } elseif ($modLower === 'default' && isset($parts[$i + 1])) {
                    $default = trim($parts[++$i]);
                } elseif (preg_match('/^default\(([^)]+)\)$/i', $modifier, $dm)) {
                    $default = trim($dm[1]);
                } elseif (str_starts_with($modLower, 'default_')) {
                    $default = substr($modifier, 8);
                } elseif (str_starts_with($modLower, 'default:')) {
                    $default = substr($modifier, 8);
                } elseif ($type === 'enum' && empty($enumValues)) {
                    // Soporte para sintaxis alterna: status:enum:active,inactive
                    $enumValues = array_map('trim', explode(',', $modifier));
                }
            }

            $fields[] = [
                'name' => $fieldName,
                'type' => $type,
                'length' => $length,
                'precision' => $precision,
                'scale' => $scale,
                'nullable' => $nullable,
                'unique' => $unique,
                'index' => $index,
                'unsigned' => $unsigned,
                'default' => $default,
                'foreign_table' => $foreignTable,
                'on_delete' => $onDelete,
                'on_update' => $onUpdate,
                'enum_values' => $enumValues,
                'label' => Str::headline($fieldName),
            ];
        }

        return $fields;
    }

    /**
     * Parsea las relaciones explícitas (--relationships) y deduce las relaciones belongsTo por foreignId.
     */
    protected function parseRelationships(?string $relInput, array $fields): array
    {
        $relationships = [];

        // 1. Relaciones implícitas deducidas de foreignId
        foreach ($fields as $f) {
            if ($f['type'] === 'foreignId' && !empty($f['foreign_table'])) {
                $methodName = Str::camel(Str::singular($f['foreign_table']));
                $relatedModel = Str::studly(Str::singular($f['foreign_table']));
                $relationships[$methodName] = [
                    'method' => $methodName,
                    'type' => 'belongsTo',
                    'model' => $relatedModel,
                    'foreign_key' => $f['name'],
                ];
            }
        }

        // 2. Relaciones explícitas desde --relationships
        if (!empty($relInput)) {
            $items = explode(',', $relInput);
            foreach ($items as $item) {
                $item = trim($item);
                if (empty($item)) continue;

                $parts = explode(':', $item);
                $relType = strtolower(trim($parts[0]));
                $targetModel = isset($parts[1]) ? Str::studly(trim($parts[1])) : null;

                if (empty($targetModel)) continue;

                if (in_array($relType, ['belongsto', 'hasmany', 'hasone'])) {
                    if ($relType === 'hasmany') {
                        $methodName = Str::camel(Str::pluralStudly($targetModel));
                        $actualType = 'hasMany';
                    } elseif ($relType === 'hasone') {
                        $methodName = Str::camel(Str::singular($targetModel));
                        $actualType = 'hasOne';
                    } else {
                        $methodName = Str::camel(Str::singular($targetModel));
                        $actualType = 'belongsTo';
                    }

                    $relationships[$methodName] = [
                        'method' => $methodName,
                        'type' => $actualType,
                        'model' => $targetModel,
                        'foreign_key' => null,
                    ];
                }
            }
        }

        return array_values($relationships);
    }

    /**
     * Retorna el listado completo de rutas de archivo esperadas para el módulo.
     */
    protected function getTargetFilesList(string $modelName, string $tableName, string $viewFolder, string $routePrefix): array
    {
        return [
            'Model' => app_path("Models/{$modelName}.php"),
            'Controller' => app_path("Http/Controllers/{$modelName}Controller.php"),
            'StoreRequest' => app_path("Http/Requests/Store{$modelName}Request.php"),
            'UpdateRequest' => app_path("Http/Requests/Update{$modelName}Request.php"),
            'Policy' => app_path("Policies/{$modelName}Policy.php"),
            'Factory' => database_path("factories/{$modelName}Factory.php"),
            'FeatureTest' => base_path("tests/Feature/{$modelName}Test.php"),
            'ViewIndex' => resource_path("views/{$viewFolder}/index.blade.php"),
            'ViewCreate' => resource_path("views/{$viewFolder}/create.blade.php"),
            'ViewEdit' => resource_path("views/{$viewFolder}/edit.blade.php"),
            'ViewShow' => resource_path("views/{$viewFolder}/show.blade.php"),
            'ViewPdf' => resource_path("views/reports/{$viewFolder}.blade.php"),
            'Routes' => base_path("routes/modules/{$routePrefix}.php"),
        ];
    }

    /**
     * Detecta si alguno de los archivos destino o migración ya existe en disco.
     */
    protected function detectCollisions(array $targetFiles, string $tableName): array
    {
        $collisions = [];
        $existingMig = glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (!empty($existingMig)) {
            $collisions[] = "[Migration] {$existingMig[0]}";
        }
        foreach ($targetFiles as $type => $path) {
            if (File::exists($path)) {
                $collisions[] = "[{$type}] {$path}";
            }
        }
        return $collisions;
    }

    /**
     * Asegura que un directorio exista, registrándolo si es creado nuevo para rollback compensatorio.
     */
    protected function ensureDirectoryExists(string $dir): void
    {
        if (!File::isDirectory($dir)) {
            if (!in_array($dir, $this->createdDirectories)) {
                $this->createdDirectories[] = $dir;
            }
            File::makeDirectory($dir, 0755, true);
        }
    }

    /**
     * Escribe un archivo registrándolo en la pila de rollback compensatorio.
     * Si el archivo ya existía (sobrescritura por --force), respalda su contenido original.
     * Si el archivo no existía previamente, lo registra como nuevo para ser eliminado si ocurre un fallo.
     */
    protected function writeFileWithRollback(string $filePath, string $content): void
    {
        if (File::exists($filePath)) {
            if (!array_key_exists($filePath, $this->overwrittenFiles)) {
                $this->overwrittenFiles[$filePath] = File::get($filePath);
            }
        } else {
            if (!in_array($filePath, $this->createdFiles)) {
                $this->createdFiles[] = $filePath;
            }
        }

        $this->ensureDirectoryExists(dirname($filePath));

        File::put($filePath, $content);
    }

    /**
     * Registra un archivo creado en la pila de rollback (compatibilidad).
     */
    protected function registerCreatedFile(string $filePath): void
    {
        if (!File::exists($filePath) || !in_array($filePath, $this->createdFiles)) {
            $this->createdFiles[] = $filePath;
        }
    }

    /**
     * Ejecuta el rollback compensatorio multinivel ante fallos:
     * 1. Elimina archivos creados que NO existían previamente (incluyendo routes/modules/{module}.php).
     * 2. Elimina directorios creados que NO existían previamente.
     * 3. Restaura el contenido exacto de archivos preexistentes sobrescritos con --force.
     */
    protected function performRollback(\Throwable $e): void
    {
        $this->newLine();
        $this->error("❌ Error crítico durante la generación del módulo: " . $e->getMessage());
        $this->warn("Iniciando reversión compensatoria de cambios en disco y base de datos...");

        // 1. Eliminar archivos nuevos creados en esta ejecución
        foreach (array_reverse($this->createdFiles) as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->line("   - Eliminado archivo nuevo residual: <fg=red>{$file}</>");
            }
        }

        // 2. Eliminar directorios nuevos creados en esta ejecución
        foreach (array_reverse($this->createdDirectories) as $dir) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
                $this->line("   - Eliminado directorio nuevo residual: <fg=red>{$dir}</>");
            }
        }

        // 3. Restaurar archivos preexistentes sobrescritos con --force
        foreach ($this->overwrittenFiles as $file => $originalContent) {
            File::put($file, $originalContent);
            $this->line("   - Restaurado archivo preexistente original: <fg=yellow>{$file}</>");
        }

        $this->error("Reversión compensatoria completada. El repositorio ha quedado en su estado previo seguro.");
    }

    /**
     * Genera la migración de base de datos con soporte completo de tipos y modificadores.
     * Si ya existe una migración para la tabla y se usa --force, reutiliza el mismo archivo
     * para evitar crear archivos timestamped duplicados.
     */
    protected function generateMigration(string $tableName, array $fields, bool $softDeletes, bool $timestamps, bool $force): string
    {
        $existing = glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (!empty($existing)) {
            if (!$force) {
                return $existing[0];
            }
            // Con --force: reutilizar la migración existente para evitar duplicados timestamped
            $filePath = $existing[0];
        } else {
            $timestamp = date('Y_m_d_His');
            $fileName = "{$timestamp}_create_{$tableName}_table.php";
            $filePath = database_path("migrations/{$fileName}");
        }

        $columnDefs = [];
        foreach ($fields as $f) {
            $def = "\$table->";
            $type = $f['type'];
            $name = $f['name'];

            if ($type === 'text') {
                $def .= "text('{$name}')";
            } elseif ($type === 'integer') {
                $def .= "integer('{$name}')";
                if ($f['unsigned']) $def .= "->unsigned()";
            } elseif ($type === 'bigInteger') {
                $def .= "bigInteger('{$name}')";
                if ($f['unsigned']) $def .= "->unsigned()";
            } elseif ($type === 'decimal') {
                $p = $f['precision'] ?? 12;
                $s = $f['scale'] ?? 2;
                $def .= "decimal('{$name}', {$p}, {$s})";
            } elseif ($type === 'boolean') {
                $def .= "boolean('{$name}')";
            } elseif ($type === 'date') {
                $def .= "date('{$name}')";
            } elseif ($type === 'dateTime') {
                $def .= "dateTime('{$name}')";
            } elseif ($type === 'timestamp') {
                $def .= "timestamp('{$name}')";
            } elseif ($type === 'json') {
                $def .= "json('{$name}')";
            } elseif ($type === 'uuid') {
                $def .= "uuid('{$name}')";
            } elseif ($type === 'enum') {
                if (!empty($f['enum_values'])) {
                    $valsExport = "['" . implode("', '", $f['enum_values']) . "']";
                    $def .= "string('{$name}', 50)"; // Portable string en BD, validado con Rule::in()
                } else {
                    $def .= "string('{$name}', 50)";
                }
            } elseif ($type === 'foreignId') {
                $def .= "foreignId('{$name}')";
                if (!empty($f['foreign_table'])) {
                    $def .= "->constrained('{$f['foreign_table']}')";
                    if (!empty($f['on_delete'])) $def .= "->onDelete('{$f['on_delete']}')";
                    if (!empty($f['on_update'])) $def .= "->onUpdate('{$f['on_update']}')";
                }
            } else {
                $len = $f['length'] ?? 255;
                $def .= "string('{$name}', {$len})";
            }

            if ($f['nullable']) {
                $def .= "->nullable()";
            }
            if ($f['unique']) {
                $def .= "->unique()";
            }
            if ($f['index'] && !$f['unique']) {
                $def .= "->index()";
            }
            if ($f['default'] !== null) {
                if (is_numeric($f['default'])) {
                    $def .= "->default({$f['default']})";
                } elseif (in_array(strtolower($f['default']), ['true', 'false'])) {
                    $def .= "->default(" . strtolower($f['default']) . ")";
                } else {
                    $def .= "->default('{$f['default']}')";
                }
            }

            $columnDefs[] = "            {$def};";
        }

        if ($softDeletes) {
            $columnDefs[] = "            \$table->softDeletes();";
        }
        if ($timestamps) {
            $columnDefs[] = "            \$table->timestamps();";
        }

        $columnContent = implode("\n", $columnDefs);

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$columnContent}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera el modelo Eloquent con casts modernos, relaciones y SoftDeletes.
     */
    protected function generateModel(
        string $modelName,
        string $tableName,
        array $fields,
        array $relationships,
        bool $softDeletes,
        bool $timestamps,
        bool $force
    ): string {
        $filePath = app_path("Models/{$modelName}.php");
        if (File::exists($filePath) && !$force) {
            return $filePath;
        }

        $traits = ["HasFactory"];
        $useStatements = [
            "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;",
            "use Illuminate\\Database\\Eloquent\\Model;",
        ];

        if ($softDeletes) {
            $traits[] = "SoftDeletes";
            $useStatements[] = "use Illuminate\\Database\\Eloquent\\SoftDeletes;";
        }

        $traitStr = implode(", ", $traits);
        $useStr = implode("\n", $useStatements);

        $fillable = array_map(fn($f) => "        '{$f['name']}',", $fields);
        $fillableStr = implode("\n", $fillable);

        $casts = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $type = $f['type'];

            if ($type === 'boolean') {
                $casts[] = "            '{$name}' => 'boolean',";
            } elseif ($type === 'decimal') {
                $casts[] = "            '{$name}' => 'decimal:{$f['scale']}',";
            } elseif ($type === 'integer' || $type === 'bigInteger') {
                $casts[] = "            '{$name}' => 'integer',";
            } elseif ($type === 'date') {
                $casts[] = "            '{$name}' => 'date',";
            } elseif ($type === 'dateTime' || $type === 'timestamp') {
                $casts[] = "            '{$name}' => 'datetime',";
            } elseif ($type === 'json') {
                $casts[] = "            '{$name}' => 'array',";
            }
        }
        $castsContent = implode("\n", $casts);

        // Relaciones Eloquent
        $relationMethods = [];
        foreach ($relationships as $rel) {
            $mName = $rel['method'];
            $rType = $rel['type'];
            $rModel = $rel['model'];
            $fKey = $rel['foreign_key'] ? ", '{$rel['foreign_key']}'" : "";

            if ($rType === 'belongsTo') {
                $relationMethods[] = <<<PHP
    public function {$mName}(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return \$this->belongsTo(\\App\\Models\\{$rModel}::class{$fKey});
    }
PHP;
            } elseif ($rType === 'hasMany') {
                $relationMethods[] = <<<PHP
    public function {$mName}(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return \$this->hasMany(\\App\\Models\\{$rModel}::class);
    }
PHP;
            } elseif ($rType === 'hasOne') {
                $relationMethods[] = <<<PHP
    public function {$mName}(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return \$this->hasOne(\\App\\Models\\{$rModel}::class);
    }
PHP;
            }
        }
        $relationsStr = !empty($relationMethods) ? "\n" . implode("\n\n", $relationMethods) . "\n" : "";

        $timestampsProp = !$timestamps ? "\n    public \$timestamps = false;\n" : "";

        $content = <<<PHP
<?php

namespace App\Models;

{$useStr}

class {$modelName} extends Model
{
    use {$traitStr};

    protected \$table = '{$tableName}';
{$timestampsProp}
    protected \$fillable = [
{$fillableStr}
    ];

    protected function casts(): array
    {
        return [
{$castsContent}
        ];
    }
{$relationsStr}}
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera Form Request para la acción Store (Crear).
     */
    protected function generateStoreRequest(string $modelName, string $tableName, array $fields, bool $force): string
    {
        $dir = app_path('Http/Requests');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);

        $filePath = "{$dir}/Store{$modelName}Request.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $rules = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $fRules = [];
            $fRules[] = $f['nullable'] ? "'nullable'" : "'required'";

            $this->appendTypeValidationRules($fRules, $f, $tableName, false);

            $rules[] = "            '{$name}' => [" . implode(', ', $fRules) . "],";
        }
        $rulesStr = implode("\n", $rules);

        $jsonFields = array_filter($fields, fn($f) => $f['type'] === 'json');
        $extraMethods = "";
        if (!empty($jsonFields)) {
            $prepLines = [];
            $msgLines = [];
            foreach ($jsonFields as $jf) {
                $jName = $jf['name'];
                $prepLines[] = <<<PHP
        if (\$this->has('{$jName}')) {
            \$val = \$this->input('{$jName}');
            if (is_string(\$val)) {
                \$trimmed = trim(\$val);
                if (\$trimmed === '') {
                    \$this->merge(['{$jName}' => null]);
                } else {
                    \$decoded = json_decode(\$trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        \$this->merge(['{$jName}' => \$decoded]);
                    }
                }
            }
        }
PHP;
                $msgLines[] = "            '{$jName}.array' => 'El campo {$jf['label']} debe ser un JSON o arreglo válido.',";
            }
            $prepStr = implode("\n", $prepLines);
            $msgStr = implode("\n", $msgLines);

            $extraMethods = <<<PHP


    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
{$prepStr}
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
{$msgStr}
        ];
    }
PHP;
        }

        $content = <<<PHP
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Store{$modelName}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
{$rulesStr}
        ];
    }{$extraMethods}
}
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera Form Request para la acción Update (Actualizar).
     */
    protected function generateUpdateRequest(string $modelName, string $tableName, string $varSingular, array $fields, bool $force): string
    {
        $dir = app_path('Http/Requests');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);

        $filePath = "{$dir}/Update{$modelName}Request.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $rules = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $fRules = [];
            $fRules[] = $f['nullable'] ? "'nullable'" : "'required'";

            $this->appendTypeValidationRules($fRules, $f, $tableName, true, $varSingular);

            $rules[] = "            '{$name}' => [" . implode(', ', $fRules) . "],";
        }
        $rulesStr = implode("\n", $rules);

        $jsonFields = array_filter($fields, fn($f) => $f['type'] === 'json');
        $extraMethods = "";
        if (!empty($jsonFields)) {
            $prepLines = [];
            $msgLines = [];
            foreach ($jsonFields as $jf) {
                $jName = $jf['name'];
                $prepLines[] = <<<PHP
        if (\$this->has('{$jName}')) {
            \$val = \$this->input('{$jName}');
            if (is_string(\$val)) {
                \$trimmed = trim(\$val);
                if (\$trimmed === '') {
                    \$this->merge(['{$jName}' => null]);
                } else {
                    \$decoded = json_decode(\$trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        \$this->merge(['{$jName}' => \$decoded]);
                    }
                }
            }
        }
PHP;
                $msgLines[] = "            '{$jName}.array' => 'El campo {$jf['label']} debe ser un JSON o arreglo válido.',";
            }
            $prepStr = implode("\n", $prepLines);
            $msgStr = implode("\n", $msgLines);

            $extraMethods = <<<PHP


    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
{$prepStr}
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
{$msgStr}
        ];
    }
PHP;
        }

        $content = <<<PHP
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Update{$modelName}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
{$rulesStr}
        ];
    }{$extraMethods}
}
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Auxiliar para añadir reglas de validación según el tipo y modificadores.
     */
    protected function appendTypeValidationRules(array &$fRules, array $f, string $tableName, bool $isUpdate, string $varSingular = ''): void
    {
        $type = $f['type'];
        $name = $f['name'];

        if ($type === 'integer' || $type === 'bigInteger') {
            $fRules[] = "'integer'";
        } elseif ($type === 'decimal') {
            $fRules[] = "'numeric'";
        } elseif ($type === 'boolean') {
            $fRules[] = "'boolean'";
        } elseif ($type === 'date') {
            $fRules[] = "'date'";
        } elseif ($type === 'dateTime' || $type === 'timestamp') {
            $fRules[] = "'date'";
        } elseif ($type === 'json') {
            $fRules[] = "'array'";
        } elseif ($type === 'uuid') {
            $fRules[] = "'uuid'";
        } elseif ($type === 'enum' && !empty($f['enum_values'])) {
            $fRules[] = "Rule::in(['" . implode("', '", $f['enum_values']) . "'])";
        } elseif ($type === 'foreignId' && !empty($f['foreign_table'])) {
            $fRules[] = "'exists:{$f['foreign_table']},id'";
        } else {
            $len = $f['length'] ?? 255;
            $fRules[] = "'string'";
            if ($type !== 'text') {
                $fRules[] = "'max:{$len}'";
            }
        }

        if ($f['unique']) {
            if ($isUpdate) {
                $fRules[] = "Rule::unique('{$tableName}', '{$name}')->ignore(\$this->route('{$varSingular}'))";
            } else {
                $fRules[] = "'unique:{$tableName},{$name}'";
            }
        }
    }

    /**
     * Genera Policy de autorización alineada al RBAC de 5 posiciones de Dkript Core.
     */
    protected function generatePolicy(string $modelName, bool $force): string
    {
        $dir = app_path('Policies');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);

        $filePath = "{$dir}/{$modelName}Policy.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $content = <<<PHP
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\\{$modelName};
use Illuminate\Auth\Access\Response;

class {$modelName}Policy
{
    /**
     * Bypass universal para Super Administradores.
     */
    public function before(User \$user, string \$ability): bool|null
    {
        if (\$user->isSuperAdmin()) {
            return true;
        }
        return null;
    }

    /**
     * Posición 4: Ver Catálogo Principal.
     */
    public function viewAny(User \$user): bool
    {
        return in_array(4, session('mypermits', []));
    }

    /**
     * Posición 4: Ver Detalle del Registro.
     */
    public function view(User \$user, {$modelName} \$item): bool
    {
        return in_array(4, session('mypermits', []));
    }

    /**
     * Posición 1: Crear Nuevo Registro.
     */
    public function create(User \$user): bool
    {
        return in_array(1, session('mypermits', []));
    }

    /**
     * Posición 2: Editar Registro.
     */
    public function update(User \$user, {$modelName} \$item): bool
    {
        return in_array(2, session('mypermits', []));
    }

    /**
     * Posición 3: Eliminar Registro.
     */
    public function delete(User \$user, {$modelName} \$item): bool
    {
        return in_array(3, session('mypermits', []));
    }
}
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera Factory para el módulo.
     */
    protected function generateFactory(string $modelName, array $fields, bool $force): string
    {
        $dir = database_path('factories');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);

        $filePath = "{$dir}/{$modelName}Factory.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $definitions = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $type = $f['type'];

            if ($name === 'email' || str_contains($name, 'email')) {
                $val = "fake()->safeEmail()";
            } elseif ($name === 'phone' || str_contains($name, 'phone')) {
                $val = "fake()->phoneNumber()";
            } elseif ($type === 'boolean') {
                $val = "fake()->boolean()";
            } elseif ($type === 'integer' || $type === 'bigInteger') {
                $val = "fake()->numberBetween(1, 100)";
            } elseif ($type === 'decimal') {
                $val = "fake()->randomFloat({$f['scale']}, 10, 1000)";
            } elseif ($type === 'date') {
                $val = "fake()->date()";
            } elseif ($type === 'dateTime' || $type === 'timestamp') {
                $val = "fake()->dateTime()";
            } elseif ($type === 'json') {
                $val = "['meta' => fake()->word()]";
            } elseif ($type === 'uuid') {
                $val = "fake()->uuid()";
            } elseif ($type === 'enum' && !empty($f['enum_values'])) {
                $val = "fake()->randomElement(['" . implode("', '", $f['enum_values']) . "'])";
            } elseif ($type === 'foreignId') {
                $val = "1";
            } elseif ($type === 'text') {
                $val = "fake()->paragraph()";
            } else {
                $val = "fake()->words(2, true)";
            }

            $definitions[] = "            '{$name}' => {$val},";
        }
        $defStr = implode("\n", $definitions);

        $content = <<<PHP
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\\{$modelName};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\\{$modelName}>
 */
class {$modelName}Factory extends Factory
{
    protected \$model = {$modelName}::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
{$defStr}
        ];
    }
}
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera el controlador profesional inyectando Form Requests y soportando CRUD integral.
     */
    protected function generateController(
        string $modelName,
        string $moduleTitle,
        string $varSingular,
        string $varPlural,
        string $tableName,
        string $viewFolder,
        string $routePrefix,
        array $fields,
        bool $softDeletes,
        bool $force
    ): string {
        $filePath = app_path("Http/Controllers/{$modelName}Controller.php");
        if (File::exists($filePath) && !$force) {
            return $filePath;
        }

        // Búsqueda en Index
        $searchableCols = [];
        foreach ($fields as $f) {
            if (in_array($f['type'], ['string', 'text'])) {
                $searchableCols[] = $f['name'];
            }
        }

        $searchLogic = "";
        if (!empty($searchableCols)) {
            $firstCol = array_shift($searchableCols);
            $searchParts = ["\$query->where('{$firstCol}', 'like', \"%{\$search}%\")"];
            foreach ($searchableCols as $sc) {
                $searchParts[] = "->orWhere('{$sc}', 'like', \"%{\$search}%\")";
            }
            $searchChain = implode("\n                      ", $searchParts);
            $searchLogic = <<<PHP
            ->when(\$search, function (\$query, \$search) {
                {$searchChain};
            })
PHP;
        }

        // Columnas para Excel
        $excelHeaders = ["'ID'"];
        $excelRowValues = ["\${$varSingular}->id"];
        $jsonFields = [];
        foreach ($fields as $f) {
            $excelHeaders[] = "'{$f['label']}'";
            if ($f['type'] === 'boolean') {
                $excelRowValues[] = "\${$varSingular}->{$f['name']} ? 'Activo' : 'Inactivo'";
            } elseif ($f['type'] === 'json') {
                $excelRowValues[] = "is_array(\${$varSingular}->{$f['name']}) ? json_encode(\${$varSingular}->{$f['name']}, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (\${$varSingular}->{$f['name']} ?: 'N/A')";
            } else {
                $excelRowValues[] = "\${$varSingular}->{$f['name']}";
            }
            if ($f['type'] === 'json') {
                $jsonFields[] = $f['name'];
            }
        }
        $headersStr = implode(", ", $excelHeaders);
        $rowsStr = implode(",\n                ", $excelRowValues);

        $jsonDecodeLines = [];
        foreach ($jsonFields as $jf) {
            $jsonDecodeLines[] = "        if (isset(\$validated['{$jf}']) && is_string(\$validated['{$jf}'])) {";
            $jsonDecodeLines[] = "            \$decoded = json_decode(\$validated['{$jf}'], true);";
            $jsonDecodeLines[] = "            if (json_last_error() === JSON_ERROR_NONE) {";
            $jsonDecodeLines[] = "                \$validated['{$jf}'] = \$decoded;";
            $jsonDecodeLines[] = "            }";
            $jsonDecodeLines[] = "        }";
        }
        $jsonDecodeLogic = !empty($jsonDecodeLines) ? implode("\n", $jsonDecodeLines) . "\n" : "";

        $auditCategory = strtoupper($tableName);

        $content = <<<PHP
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\\{$modelName};
use App\Models\Parameter;
use App\Services\AuditService;
use App\Services\ExportService;
use App\Http\Requests\Store{$modelName}Request;
use App\Http\Requests\Update{$modelName}Request;

class {$modelName}Controller extends Controller
{
    /**
     * Posición 4: Ver Catálogo Principal
     */
    public function index(Request \$request)
    {
        \$search = \$request->input('search');

        \${$varPlural} = {$modelName}::query()
{$searchLogic}
            ->orderBy('id', 'desc')
            ->paginate(Parameter::getSystemSettings()->records_per_page ?? 10);

        return view('{$viewFolder}.index', compact('{$varPlural}', 'search'));
    }

    /**
     * Posición 1: Mostrar Formulario de Creación
     */
    public function create()
    {
        return view('{$viewFolder}.create');
    }

    /**
     * Posición 1: Crear Nuevo Registro
     */
    public function store(Store{$modelName}Request \$request)
    {
        \$validated = \$request->validated();
{$jsonDecodeLogic}        \${$varSingular} = {$modelName}::create(\$validated);

        AuditService::log('CREATE', '{$auditCategory}', "Creación de {$modelName} #{\${$varSingular}->id}", null, \${$varSingular}->toArray());

        if (\$request->wantsJson() || \$request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '{$modelName} creado exitosamente.',
                'item' => \${$varSingular},
            ]);
        }

        return redirect()->route('{$routePrefix}.index')->with('success', '{$modelName} creado exitosamente.');
    }

    /**
     * Posición 4: Ver Detalle del Registro
     */
    public function show({$modelName} \${$varSingular})
    {
        return view('{$viewFolder}.show', compact('{$varSingular}'));
    }

    /**
     * Posición 2: Mostrar Formulario de Edición
     */
    public function edit({$modelName} \${$varSingular})
    {
        return view('{$viewFolder}.edit', compact('{$varSingular}'));
    }

    /**
     * Posición 2: Actualizar Registro Existente
     */
    public function update(Update{$modelName}Request \$request, {$modelName} \${$varSingular})
    {
        \$validated = \$request->validated();
{$jsonDecodeLogic}        \$oldData = \${$varSingular}->toArray();
        \${$varSingular}->update(\$validated);

        AuditService::log('UPDATE', '{$auditCategory}', "Actualización de {$modelName} #{\${$varSingular}->id}", \$oldData, \${$varSingular}->fresh()->toArray());

        if (\$request->wantsJson() || \$request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '{$modelName} actualizado exitosamente.',
                'item' => \${$varSingular},
            ]);
        }

        return redirect()->route('{$routePrefix}.index')->with('success', '{$modelName} actualizado exitosamente.');
    }

    /**
     * Posición 3: Eliminar Registro
     */
    public function destroy(Request \$request, {$modelName} \${$varSingular})
    {
        \$oldData = \${$varSingular}->toArray();
        \$deletedId = \${$varSingular}->id;

        \${$varSingular}->delete();

        AuditService::log('DELETE', '{$auditCategory}', "Eliminación de {$modelName} #{\$deletedId}", \$oldData, null);

        if (\$request->wantsJson() || \$request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '{$modelName} eliminado exitosamente.',
            ]);
        }

        return redirect()->route('{$routePrefix}.index')->with('success', '{$modelName} eliminado exitosamente.');
    }

    /**
     * Posición 5: Exportar a Excel
     */
    public function exportExcel(ExportService \$exportService)
    {
        \${$varPlural} = {$modelName}::orderBy('id', 'asc')->get();
        \$headers = [{$headersStr}];

        \$rows = [];
        foreach (\${$varPlural} as \${$varSingular}) {
            \$rows[] = [
                {$rowsStr}
            ];
        }

        return \$exportService->downloadExcel('Reporte de {$moduleTitle}', \$headers, \$rows, '{$routePrefix}_' . date('Y-m-d'));
    }

    /**
     * Posición 4: Exportar a PDF
     */
    public function exportPdf(Request \$request, ExportService \$exportService)
    {
        \${$varPlural} = {$modelName}::orderBy('id', 'asc')->get();
        \$action = \$request->query('action', 'download');

        return \$exportService->generatePdf('reports.{$viewFolder}', [
            'reportTitle' => 'Catálogo General de {$moduleTitle}',
            '{$varPlural}' => \${$varPlural},
        ], 'reporte_{$routePrefix}_' . date('Y-m-d'), \$action);
    }
}
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera las 4 vistas Blade: index, create, edit, show.
     */
    protected function generateViews(
        string $modelName,
        string $moduleTitle,
        string $varSingular,
        string $varPlural,
        string $routePrefix,
        string $viewFolder,
        array $fields,
        string $icon,
        bool $softDeletes,
        bool $force
    ): array {
        $dirPath = resource_path("views/{$viewFolder}");
        $this->ensureDirectoryExists($dirPath);

        $generated = [];

        // 1. index.blade.php
        $generated['index'] = $this->generateIndexView($modelName, $moduleTitle, $varSingular, $varPlural, $routePrefix, $dirPath, $fields, $icon, $force);

        // 2. create.blade.php
        $generated['create'] = $this->generateCreateView($modelName, $moduleTitle, $routePrefix, $dirPath, $fields, $icon, $force);

        // 3. edit.blade.php
        $generated['edit'] = $this->generateEditView($modelName, $moduleTitle, $varSingular, $routePrefix, $dirPath, $fields, $icon, $force);

        // 4. show.blade.php
        $generated['show'] = $this->generateShowView($modelName, $moduleTitle, $varSingular, $routePrefix, $dirPath, $fields, $icon, $force);

        return $generated;
    }

    /**
     * Genera index.blade.php con tabla, búsqueda, paginación y modal integrado.
     */
    protected function generateIndexView(
        string $modelName,
        string $moduleTitle,
        string $varSingular,
        string $varPlural,
        string $routePrefix,
        string $dirPath,
        array $fields,
        string $icon,
        bool $force
    ): string {
        $filePath = "{$dirPath}/index.blade.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $tableHeaders = [];
        $tableCols = [];
        $formInputs = [];
        $jsSetValues = [];
        $jsResetValues = [];

        foreach ($fields as $f) {
            $tableHeaders[] = "                        <th class=\"px-6 py-4 font-bold\">{$f['label']}</th>";
            $name = $f['name'];
            $type = $f['type'];

            if ($type === 'boolean') {
                $tableCols[] = <<<BLADE
                            <td class="px-6 py-4">
                                @if(\${$varSingular}->{$name})
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactivo
                                    </span>
                                @endif
                            </td>
BLADE;
                $formInputs[] = <<<HTML
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                        <select name="{$name}" id="input_{$name}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
HTML;
                $jsSetValues[] = "        document.getElementById('input_{$name}').value = item.{$name} ? '1' : '0';";
                $jsResetValues[] = "        document.getElementById('input_{$name}').value = '1';";
            } elseif ($type === 'decimal') {
                $tableCols[] = <<<BLADE
                            <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                $ {{ number_format(\${$varSingular}->{$name}, {$f['scale']}) }}
                            </td>
BLADE;
                $reqAttr = $f['nullable'] ? '' : 'required';
                $formInputs[] = <<<HTML
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                        <input type="number" step="0.01" name="{$name}" id="input_{$name}" {$reqAttr} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                    </div>
HTML;
                $jsSetValues[] = "        document.getElementById('input_{$name}').value = item.{$name} || '';";
                $jsResetValues[] = "        document.getElementById('input_{$name}').value = '';";
            } elseif ($type === 'text') {
                $tableCols[] = <<<BLADE
                            <td class="px-6 py-4 text-xs text-slate-600 max-w-xs truncate">
                                {{ \${$varSingular}->{$name} ?: 'N/A' }}
                            </td>
BLADE;
                $reqAttr = $f['nullable'] ? '' : 'required';
                $formInputs[] = <<<HTML
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                        <textarea name="{$name}" id="input_{$name}" rows="3" {$reqAttr} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all"></textarea>
                    </div>
HTML;
                $jsSetValues[] = "        document.getElementById('input_{$name}').value = item.{$name} || '';";
                $jsResetValues[] = "        document.getElementById('input_{$name}').value = '';";
            } elseif ($type === 'json') {
                $tableCols[] = <<<BLADE
                            <td class="px-6 py-4 text-xs font-mono text-slate-600 max-w-xs truncate" title="{{ is_array(\${$varSingular}->{$name}) ? json_encode(\${$varSingular}->{$name}, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : \${$varSingular}->{$name} }}">
                                {{ is_array(\${$varSingular}->{$name}) ? json_encode(\${$varSingular}->{$name}, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (\${$varSingular}->{$name} ?: 'N/A') }}
                            </td>
BLADE;
                $reqAttr = $f['nullable'] ? '' : 'required';
                $formInputs[] = <<<HTML
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                        <textarea name="{$name}" id="input_{$name}" rows="3" {$reqAttr} placeholder='{"key": "value"}' class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all"></textarea>
                    </div>
HTML;
                $jsSetValues[] = "        document.getElementById('input_{$name}').value = (typeof item.{$name} === 'object' && item.{$name} !== null) ? JSON.stringify(item.{$name}, null, 2) : (item.{$name} || '');";
                $jsResetValues[] = "        document.getElementById('input_{$name}').value = '';";
            } elseif ($type === 'enum' && !empty($f['enum_values'])) {
                $tableCols[] = <<<BLADE
                            <td class="px-6 py-4 font-medium text-slate-800">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ \${$varSingular}->{$name} ?: 'N/A' }}
                                </span>
                            </td>
BLADE;
                $optionsHtml = "";
                foreach ($f['enum_values'] as $ev) {
                    $optionsHtml .= "                            <option value=\"{$ev}\">" . Str::headline($ev) . "</option>\n";
                }
                $formInputs[] = <<<HTML
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                        <select name="{$name}" id="input_{$name}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
{$optionsHtml}                        </select>
                    </div>
HTML;
                $defaultEnumVal = $f['enum_values'][0] ?? '';
                $jsSetValues[] = "        document.getElementById('input_{$name}').value = item.{$name} || '{$defaultEnumVal}';";
                $jsResetValues[] = "        document.getElementById('input_{$name}').value = '{$defaultEnumVal}';";
            } else {
                $tableCols[] = <<<BLADE
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ \${$varSingular}->{$name} ?: 'N/A' }}
                            </td>
BLADE;
                $inputHtmlType = 'text';
                if ($type === 'integer' || $type === 'bigInteger') $inputHtmlType = 'number';
                elseif ($type === 'date') $inputHtmlType = 'date';
                elseif ($type === 'dateTime' || $type === 'timestamp') $inputHtmlType = 'datetime-local';

                $reqAttr = $f['nullable'] ? '' : 'required';
                $formInputs[] = <<<HTML
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                        <input type="{$inputHtmlType}" name="{$name}" id="input_{$name}" {$reqAttr} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
                    </div>
HTML;
                $jsSetValues[] = "        document.getElementById('input_{$name}').value = item.{$name} || '';";
                $jsResetValues[] = "        document.getElementById('input_{$name}').value = '';";
            }
        }

        $headersHtml = implode("\n", $tableHeaders);
        $colsHtml = implode("\n", $tableCols);
        $inputsHtml = implode("\n", $formInputs);
        $jsSetValuesStr = implode("\n", $jsSetValues);
        $jsResetValuesStr = implode("\n", $jsResetValues);
        $totalColSpan = count($fields) + 2;
        $modalId = "modal{$modelName}Form";

        $content = <<<BLADE
@extends('layouts.admin')

@section('title', 'Gestión de {$moduleTitle}')
@section('page_title', '{$moduleTitle}')

@section('content')
<div class="content-section">
    <!-- Barra de Herramientas Estándar con Permisos -->
    @include('partials.layouts.page-header', [
        'searchPlaceholder' => 'Buscar en {$moduleTitle}...',
        'newModalId' => '{$modalId}',
        'newButtonText' => 'Nuevo {$modelName}'
    ])

    <!-- Tabla Estilo Tarjeta Cyber-Glass -->
    <div class="table-card">
        <div class="overflow-x-auto">
            <table id="tbExel" class="w-full text-left text-sm text-slate-600 catalog-data-table">
                <thead class="bg-[#f8fafd] text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold" style="width: 80px;">ID</th>
{$headersHtml}
                        <th class="px-6 py-4 font-bold text-right no-export">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(\${$varPlural} as \${$varSingular})
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-slate-400">
                                #{{ \${$varSingular}->id }}
                            </td>
{$colsHtml}
                            <td class="px-6 py-4 text-right whitespace-nowrap no-export">
                                <div class="inline-flex items-center gap-1.5">
                                    {{-- Posición 4: Ver Detalle --}}
                                    @if(auth()->user()->isSuperAdmin() || in_array(4, session('mypermits', [])))
                                        <a href="{{ route('{$routePrefix}.show', \${$varSingular}) }}" 
                                           title="Ver Detalle" 
                                           class="p-2 rounded-lg text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors">
                                            <i class="bi bi-eye text-base"></i>
                                        </a>
                                    @endif

                                    {{-- Posición 2: Editar --}}
                                    @if(auth()->user()->isSuperAdmin() || in_array(2, session('mypermits', [])))
                                        <button type="button" 
                                                onclick='editItem(@json(\${$varSingular}))'
                                                title="Editar Registro" 
                                                class="p-2 rounded-lg text-slate-400 hover:text-[#0062f5] hover:bg-blue-50 transition-colors">
                                            <i class="bi bi-pencil-square text-base"></i>
                                        </button>
                                    @endif

                                    {{-- Posición 3: Eliminar --}}
                                    @if(auth()->user()->isSuperAdmin() || in_array(3, session('mypermits', [])))
                                        <form method="POST" action="{{ route('{$routePrefix}.destroy', \${$varSingular}) }}" data-confirm="¿Estás seguro de eliminar este registro? Esta acción es irreversible." class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    title="Eliminar Registro" 
                                                    class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                                <i class="bi bi-trash3-fill text-base"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{$totalColSpan}" class="px-6 py-8 text-center text-slate-400">
                                No se encontraron registros de {$moduleTitle}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(\${$varPlural}->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ \${$varPlural}->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Formulario Dinámico Crear/Editar -->
<div id="{$modalId}" class="modal-wrapper fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
    <div class="fixed inset-0 modal-backdrop-blur" onclick="closeModal('{$modalId}')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl z-10 overflow-hidden modal-content-transition">
        <div class="px-6 py-4 border-b border-[#112356] flex items-center justify-between bg-[#071026] text-white">
            <h3 id="modalTitle" class="text-lg font-black text-white tracking-tight">Registrar Nuevo {$modelName}</h3>
            <button type="button" onclick="closeModal('{$modalId}')" class="w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-[#0b1739] flex items-center justify-center transition-colors">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="frmModule" method="POST" action="{{ route('{$routePrefix}.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
{$inputsHtml}
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-[#f8fafd] flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('{$modalId}')" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition-colors">Cancelar</button>
                <button type="submit" class="btn-dkript-primary px-6 py-2.5 text-sm">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function resetModuleForm() {
        const form = document.getElementById('frmModule');
        form.action = "{{ route('{$routePrefix}.store') }}";
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('modalTitle').textContent = 'Registrar Nuevo {$modelName}';
{$jsResetValuesStr}
    }

    function editItem(item) {
        resetModuleForm();
        const form = document.getElementById('frmModule');
        form.action = "{{ url('/' . '{$routePrefix}') }}/" + item.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('modalTitle').textContent = 'Editar {$modelName} #' + item.id;

{$jsSetValuesStr}

        openModal('{$modalId}');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnNew = document.querySelector('[onclick*="{$modalId}"]');
        if (btnNew) {
            btnNew.addEventListener('click', resetModuleForm);
        }
    });
</script>
@endsection
BLADE;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera create.blade.php.
     */
    protected function generateCreateView(string $modelName, string $moduleTitle, string $routePrefix, string $dirPath, array $fields, string $icon, bool $force): string
    {
        $filePath = "{$dirPath}/create.blade.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $inputs = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $type = $f['type'];
            $req = $f['nullable'] ? '' : 'required';

            if ($type === 'boolean') {
                $inputs[] = <<<HTML
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <select name="{$name}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                    <option value="1" {{ old('{$name}', '1') == '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ old('{$name}') === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
HTML;
            } elseif ($type === 'text') {
                $inputs[] = <<<HTML
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <textarea name="{$name}" rows="3" {$req} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">{{ old('{$name}') }}</textarea>
            </div>
HTML;
            } elseif ($type === 'json') {
                $inputs[] = <<<HTML
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <textarea name="{$name}" rows="3" {$req} placeholder='{"key": "value"}' class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">{{ is_array(\$__v = old('{$name}')) ? json_encode(\$__v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : \$__v }}</textarea>
            </div>
HTML;
            } else {
                $inputHtmlType = 'text';
                if ($type === 'integer' || $type === 'bigInteger') $inputHtmlType = 'number';
                elseif ($type === 'decimal') $inputHtmlType = 'number';
                elseif ($type === 'date') $inputHtmlType = 'date';
                elseif ($type === 'dateTime' || $type === 'timestamp') $inputHtmlType = 'datetime-local';
                $stepAttr = ($type === 'decimal') ? 'step="0.01"' : '';

                $inputs[] = <<<HTML
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <input type="{$inputHtmlType}" {$stepAttr} name="{$name}" value="{{ old('{$name}') }}" {$req} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
            </div>
HTML;
            }
        }
        $inputsStr = implode("\n", $inputs);

        $content = <<<BLADE
@extends('layouts.admin')

@section('title', 'Crear {$modelName}')
@section('page_title', 'Crear {$modelName}')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Nuevo {$modelName}</h2>
            <p class="text-xs text-slate-500">Complete el formulario para registrar un nuevo elemento en {$moduleTitle}.</p>
        </div>
        <a href="{{ route('{$routePrefix}.index') }}" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
            Volver al Catálogo
        </a>
    </div>

    <div class="table-card p-6 sm:p-8">
        <form method="POST" action="{{ route('{$routePrefix}.store') }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
{$inputsStr}
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('{$routePrefix}.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition-colors">Cancelar</a>
                <button type="submit" class="btn-dkript-primary px-6 py-2.5 text-sm">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>
@endsection
BLADE;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera edit.blade.php.
     */
    protected function generateEditView(string $modelName, string $moduleTitle, string $varSingular, string $routePrefix, string $dirPath, array $fields, string $icon, bool $force): string
    {
        $filePath = "{$dirPath}/edit.blade.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $inputs = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $type = $f['type'];
            $req = $f['nullable'] ? '' : 'required';

            if ($type === 'boolean') {
                $inputs[] = <<<HTML
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <select name="{$name}" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all bg-white">
                    <option value="1" {{ old('{$name}', \${$varSingular}->{$name} ? '1' : '0') == '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ old('{$name}', \${$varSingular}->{$name} ? '1' : '0') == '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
HTML;
            } elseif ($type === 'text') {
                $inputs[] = <<<HTML
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <textarea name="{$name}" rows="3" {$req} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">{{ old('{$name}', \${$varSingular}->{$name}) }}</textarea>
            </div>
HTML;
            } elseif ($type === 'json') {
                $inputs[] = <<<HTML
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <textarea name="{$name}" rows="3" {$req} placeholder='{"key": "value"}' class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-mono focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">{{ is_array(\$__v = old('{$name}', \${$varSingular}->{$name})) ? json_encode(\$__v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : \$__v }}</textarea>
            </div>
HTML;
            } else {
                $inputHtmlType = 'text';
                if ($type === 'integer' || $type === 'bigInteger') $inputHtmlType = 'number';
                elseif ($type === 'decimal') $inputHtmlType = 'number';
                elseif ($type === 'date') $inputHtmlType = 'date';
                elseif ($type === 'dateTime' || $type === 'timestamp') $inputHtmlType = 'datetime-local';
                $stepAttr = ($type === 'decimal') ? 'step="0.01"' : '';

                $inputs[] = <<<HTML
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">{$f['label']}</label>
                <input type="{$inputHtmlType}" {$stepAttr} name="{$name}" value="{{ old('{$name}', \${$varSingular}->{$name}) }}" {$req} class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-[#0062f5]/20 focus:border-[#0062f5] transition-all">
            </div>
HTML;
            }
        }
        $inputsStr = implode("\n", $inputs);

        $content = <<<BLADE
@extends('layouts.admin')

@section('title', 'Editar {$modelName} #' . \${$varSingular}->id)
@section('page_title', 'Editar {$modelName}')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Editar {$modelName} #{{ \${$varSingular}->id }}</h2>
            <p class="text-xs text-slate-500">Actualice la información de este registro.</p>
        </div>
        <a href="{{ route('{$routePrefix}.index') }}" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
            Volver al Catálogo
        </a>
    </div>

    <div class="table-card p-6 sm:p-8">
        <form method="POST" action="{{ route('{$routePrefix}.update', \${$varSingular}) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
{$inputsStr}
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('{$routePrefix}.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-100 transition-colors">Cancelar</a>
                <button type="submit" class="btn-dkript-primary px-6 py-2.5 text-sm">Actualizar Registro</button>
            </div>
        </form>
    </div>
</div>
@endsection
BLADE;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera show.blade.php.
     */
    protected function generateShowView(string $modelName, string $moduleTitle, string $varSingular, string $routePrefix, string $dirPath, array $fields, string $icon, bool $force): string
    {
        $filePath = "{$dirPath}/show.blade.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $items = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $type = $f['type'];

            if ($type === 'boolean') {
                $items[] = <<<HTML
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{$f['label']}</span>
                @if(\${$varSingular}->{$name})
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Activo</span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">Inactivo</span>
                @endif
            </div>
HTML;
            } elseif ($type === 'json') {
                $items[] = <<<HTML
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 sm:col-span-2 md:col-span-3">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{$f['label']}</span>
                <pre class="font-mono text-xs text-slate-700 bg-white p-3 rounded-lg border border-slate-200 overflow-x-auto whitespace-pre-wrap">{{ is_array(\${$varSingular}->{$name}) ? json_encode(\${$varSingular}->{$name}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (\${$varSingular}->{$name} ?: 'N/A') }}</pre>
            </div>
HTML;
            } else {
                $items[] = <<<HTML
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{$f['label']}</span>
                <span class="text-sm font-semibold text-slate-800">{{ \${$varSingular}->{$name} ?: 'N/A' }}</span>
            </div>
HTML;
            }
        }
        $itemsStr = implode("\n", $items);

        $content = <<<BLADE
@extends('layouts.admin')

@section('title', 'Detalle de {$modelName} #' . \${$varSingular}->id)
@section('page_title', 'Detalle de {$modelName}')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">{$modelName} #{{ \${$varSingular}->id }}</h2>
            <p class="text-xs text-slate-500">Consulta de información detallada.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('{$routePrefix}.index') }}" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                Volver
            </a>
            @if(auth()->user()->isSuperAdmin() || in_array(2, session('mypermits', [])))
                <a href="{{ route('{$routePrefix}.edit', \${$varSingular}) }}" class="btn-dkript-primary px-4 py-2 text-xs">
                    Editar
                </a>
            @endif
        </div>
    </div>

    <div class="table-card p-6 sm:p-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 font-mono">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">ID</span>
                <span class="text-sm font-bold text-[#0062f5]">#{{ \${$varSingular}->id }}</span>
            </div>
{$itemsStr}
        </div>
    </div>
</div>
@endsection
BLADE;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera la plantilla PDF para Dompdf.
     */
    protected function generatePdfView(string $modelName, string $moduleTitle, string $varPlural, string $viewFolder, array $fields, bool $force): string
    {
        $dirPath = resource_path("views/reports");
        if (!File::isDirectory($dirPath)) File::makeDirectory($dirPath, 0755, true);

        $filePath = "{$dirPath}/{$viewFolder}.blade.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $pdfHeaders = [];
        $pdfCols = [];
        foreach ($fields as $f) {
            $pdfHeaders[] = "            <th>{$f['label']}</th>";
            $name = $f['name'];
            if ($f['type'] === 'boolean') {
                $pdfCols[] = <<<BLADE
                <td class="text-center">
                    @if(\$item->{$name})
                        <span class="badge badge-active">Activo</span>
                    @else
                        <span class="badge badge-inactive">Inactivo</span>
                    @endif
                </td>
BLADE;
            } elseif ($f['type'] === 'decimal') {
                $pdfCols[] = "                <td class=\"font-mono\">$ {{ number_format(\$item->{$name}, {$f['scale']}) }}</td>";
            } elseif ($f['type'] === 'json') {
                $pdfCols[] = "                <td class=\"font-mono text-xs\">{{ is_array(\$item->{$name}) ? json_encode(\$item->{$name}, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (\$item->{$name} ?: 'N/A') }}</td>";
            } else {
                $pdfCols[] = "                <td>{{ \$item->{$name} ?: 'N/A' }}</td>";
            }
        }

        $pdfHeadersStr = implode("\n", $pdfHeaders);
        $pdfColsStr = implode("\n", $pdfCols);
        $totalCols = count($fields) + 1;

        $content = <<<BLADE
@extends('reports.layout')

@section('content')
<div class="summary-box">
    <strong>Total de Registros en Base de Datos:</strong> {{ count(\${$varPlural}) }} registros &bull; 
    <strong>Fecha de Emisión:</strong> {{ date('d/m/Y H:i') }}
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">ID</th>
{$pdfHeadersStr}
        </tr>
    </thead>
    <tbody>
        @forelse(\${$varPlural} as \$item)
            <tr>
                <td class="text-center font-mono font-bold" style="color: #64748b;">#{{ \$item->id }}</td>
{$pdfColsStr}
            </tr>
        @empty
            <tr>
                <td colspan="{$totalCols}" class="text-center" style="padding: 20px; color: #64748b;">
                    No hay registros de {$moduleTitle} en el sistema.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
BLADE;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Genera Feature Test para el nuevo módulo.
     */
    protected function generateFeatureTest(
        string $modelName,
        string $tableName,
        string $routePrefix,
        string $varSingular,
        array $fields,
        bool $softDeletes,
        bool $force
    ): string {
        $dir = base_path('tests/Feature');
        if (!File::isDirectory($dir)) File::makeDirectory($dir, 0755, true);

        $filePath = "{$dir}/{$modelName}Test.php";
        if (File::exists($filePath) && !$force) return $filePath;

        $softDeleteCheck = "";
        if ($softDeletes) {
            $softDeleteCheck = <<<PHP
        \$this->assertSoftDeleted(\$item);
PHP;
        }

        $content = <<<PHP
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Models\\{$modelName};
use Illuminate\Foundation\Testing\RefreshDatabase;

class {$modelName}Test extends TestCase
{
    use RefreshDatabase;

    protected User \$adminUser;
    protected User \$standardUser;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->seed();

        \$this->adminUser = User::factory()->create();
        Profile::create([
            'user_id' => \$this->adminUser->id,
            'role_id' => 1,
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);

        \$this->standardUser = User::factory()->create();
        Profile::create([
            'user_id' => \$this->standardUser->id,
            'role_id' => 2,
            'first_name' => 'Standard',
            'last_name' => 'User',
        ]);
    }

    public function test_index_can_be_rendered_for_authorized_users(): void
    {
        \$response = \$this->actingAs(\$this->adminUser)
            ->get(route('{$routePrefix}.index'));

        \$response->assertStatus(200);
        \$response->assertViewIs('{$routePrefix}.index');
    }

    public function test_store_creates_record_and_audits(): void
    {
        \$data = {$modelName}::factory()->make()->toArray();

        \$response = \$this->actingAs(\$this->adminUser)
            ->post(route('{$routePrefix}.store'), \$data);

        \$response->assertRedirect(route('{$routePrefix}.index'));
        \$this->assertDatabaseHas('{$tableName}', [
            'id' => 1,
        ]);
    }

    public function test_destroy_deletes_record(): void
    {
        \$item = {$modelName}::factory()->create();

        \$response = \$this->actingAs(\$this->adminUser)
            ->delete(route('{$routePrefix}.destroy', \$item));

        \$response->assertRedirect(route('{$routePrefix}.index'));
{$softDeleteCheck}
    }
}
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }

    /**
     * Registra el módulo y sus 5 posiciones RBAC en la BD.
     */
    protected function registerRbac(string $moduleTitle, string $routePrefix, string $icon): int
    {
        $routeName = "{$routePrefix}.index";

        $menuOption = MenuOption::firstOrNew(['route_name' => $routeName]);
        if (!$menuOption->exists) {
            $maxId = (int)MenuOption::max('id');
            $menuOption->id = $maxId + 1;
            $menuOption->name = $moduleTitle;
            $menuOption->icon = $icon;
            $menuOption->route_name = $routeName;
            $menuOption->order = (int)MenuOption::max('order') + 1;
            $menuOption->status = 1;
            $menuOption->save();
        }

        $optionId = $menuOption->id;

        // 5 Posiciones RBAC Inviolables
        $standardPermissions = [
            ['position' => 1, 'name' => "Crear {$moduleTitle}"],
            ['position' => 2, 'name' => "Editar {$moduleTitle}"],
            ['position' => 3, 'name' => "Eliminar {$moduleTitle}"],
            ['position' => 4, 'name' => "Ver / PDF {$moduleTitle}"],
            ['position' => 5, 'name' => "Especial / Excel {$moduleTitle}"],
        ];

        $permIds = [];
        foreach ($standardPermissions as $p) {
            $perm = Permission::firstOrCreate(
                [
                    'menu_option_id' => $optionId,
                    'position' => $p['position'],
                ],
                [
                    'name' => $p['name'],
                    'status' => 1,
                ]
            );
            $permIds[] = $perm->id;
        }

        // Asignar al Rol 1 (Super Administrador)
        $superAdmin = Role::find(1);
        if ($superAdmin) {
            $superAdmin->menuOptions()->syncWithoutDetaching([$optionId]);
            $superAdmin->permissions()->syncWithoutDetaching($permIds);
        }

        $this->line("✓ RBAC:          <fg=green>Módulo #{$optionId} registrado con 5 posiciones y asignado a Super Admin</>");
        return $optionId;
    }

    /**
     * Genera el archivo de rutas modular independiente en routes/modules/{routePrefix}.php.
     * Cumple con la arquitectura de rutas modulares de Dkript Core (Paso 1.6),
     * preservando routes/web.php 100% intacto y reservado exclusivamente para rutas Core.
     */
    protected function generateRoutes(
        string $modelName,
        string $routePrefix,
        string $varSingular,
        int $menuOptionId,
        string $moduleTitle,
        bool $force
    ): string {
        $dir = base_path('routes/modules');
        $this->ensureDirectoryExists($dir);

        $filePath = "{$dir}/{$routePrefix}.php";
        if (File::exists($filePath) && !$force) {
            return $filePath;
        }

        $content = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\\{$modelName}Controller;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo: {$moduleTitle} (ID RBAC: {$menuOptionId})
|--------------------------------------------------------------------------
|
| Rutas modulares independientes generadas por Dkript Core v1.0.
| Gobernadas por la regla inviolable de 5 posiciones del RBAC.
| Cargadas automáticamente desde bootstrap/app.php.
|
*/

Route::middleware(['auth', 'inactivity.timeout', 'verify.option:{$menuOptionId}'])->group(function () {
    Route::get('/{$routePrefix}', [{$modelName}Controller::class, 'index'])->name('{$routePrefix}.index');
    Route::get('/{$routePrefix}/create', [{$modelName}Controller::class, 'create'])->middleware('verify.position:{$menuOptionId},1')->name('{$routePrefix}.create');
    Route::get('/{$routePrefix}/export/excel', [{$modelName}Controller::class, 'exportExcel'])->middleware('verify.position:{$menuOptionId},5')->name('{$routePrefix}.export.excel');
    Route::get('/{$routePrefix}/export/pdf', [{$modelName}Controller::class, 'exportPdf'])->middleware('verify.position:{$menuOptionId},4')->name('{$routePrefix}.export.pdf');
    Route::post('/{$routePrefix}', [{$modelName}Controller::class, 'store'])->middleware('verify.position:{$menuOptionId},1')->name('{$routePrefix}.store');
    Route::get('/{$routePrefix}/{{$varSingular}}', [{$modelName}Controller::class, 'show'])->middleware('verify.position:{$menuOptionId},4')->name('{$routePrefix}.show');
    Route::get('/{$routePrefix}/{{$varSingular}}/edit', [{$modelName}Controller::class, 'edit'])->middleware('verify.position:{$menuOptionId},2')->name('{$routePrefix}.edit');
    Route::put('/{$routePrefix}/{{$varSingular}}', [{$modelName}Controller::class, 'update'])->middleware('verify.position:{$menuOptionId},2')->name('{$routePrefix}.update');
    Route::delete('/{$routePrefix}/{{$varSingular}}', [{$modelName}Controller::class, 'destroy'])->middleware('verify.position:{$menuOptionId},3')->name('{$routePrefix}.destroy');
});
PHP;

        $this->writeFileWithRollback($filePath, $content);
        return $filePath;
    }
}
