# 🌐 Dkript Core — Arquitectura de Rutas Modulares Independientes

**Versión del Core:** 0.1.0-dev (Paso 1.6)  
**Ubicación:** `routes/modules/`  
**Gobernanza:** Dkript Inc.  

---

## 1. Visión General y Principios de Diseño

En versiones preliminares, el generador de módulos inyectaba bloques de rutas directamente en `routes/web.php` mediante búsqueda heurística del delimitador `});`. Este patrón tradicional presentaba serios riesgos:
- Fragilidad ante cambios de formateo o saltos de línea en `routes/web.php`.
- Acoplamiento entre el núcleo del sistema y los módulos de negocio.
- Dificultad para empaquetar, versionar o desinstalar módulos de forma limpia.
- Imposibilidad de garantizar la inmutabilidad de la configuración central.

A partir de **Dkript Core v1.0 (Paso 1.6)**, se introduce la **Arquitectura de Rutas Modulares Independientes**:
1. **Inmutabilidad de `routes/web.php`:** `routes/web.php` queda 100% reservado para rutas troncales del Core (Autenticación, 2FA, OTP, Dashboard, Usuarios, Roles, Permisos, Parámetros, Backups, Auditoría, Perfil y Sesiones). Ningún módulo de negocio altera este archivo.
2. **Archivos Aislados por Módulo:** Cada módulo generado o desarrollado reside en su propio archivo independiente dentro de `routes/modules/{routePrefix}.php`.
3. **Descubrimiento Automático:** El pipeline de enrutamiento de Laravel 13 en `bootstrap/app.php` descubre, ordena alfabéticamente y registra todos los archivos `routes/modules/*.php` en tiempo de arranque.
4. **Compatibilidad Nativa con Route Cache:** Todos los controladores y métodos se declaran mediante tuplas fuertemente tipadas `[Controller::class, 'method']`, permitiendo compilación atómica con `php artisan route:cache`.

---

## 2. Convención de Nomenclatura y Directorio

```text
routes/
├── web.php                 <-- Exclusivo del Core (Inmutable)
├── console.php             <-- Comandos de consola
└── modules/                <-- Directorio de rutas modulares
    ├── .gitkeep            <-- Rastreado en control de versiones
    ├── customers.php       <-- Módulo Customers (Prefijo: customers)
    ├── product-categories.php
    └── work-orders.php
```

### Reglas de Nomenclatura:
- El nombre del archivo debe coincidir exactamente con el prefijo de ruta en minúsculas y formato kebab-case (`{routePrefix}.php`).
- Ejemplo: para un módulo `ProductCategory`, el prefijo es `product-categories` y su archivo de rutas es `routes/modules/product-categories.php`.

---

## 3. Carga Dinámica en `bootstrap/app.php`

Laravel 13 centraliza la configuración del aplicativo en `bootstrap/app.php` a través de `ApplicationBuilder`. La carga de rutas modulares se realiza mediante el callback `then:` de `withRouting()`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        $modulesPath = base_path('routes/modules');
        if (is_dir($modulesPath)) {
            $routeFiles = glob($modulesPath . '/*.php') ?: [];
            sort($routeFiles); // Orden determinista entre entornos
            foreach ($routeFiles as $routeFile) {
                Route::middleware('web')->group($routeFile);
            }
        }
    },
)
```

### Garantías Técnicas:
- **Orden Determinista:** `sort($routeFiles)` asegura que las rutas se registren en el mismo orden en Windows, Linux, macOS y contenedores Docker.
- **Middleware Web Centralizado:** Todas las rutas modulares heredan la pila base de middlewares `web` (sesiones, cookies, CSRF, encriptación).
- **Tolerancia a Directorio Vacío:** Si `routes/modules/` no contiene archivos PHP o solo contiene `.gitkeep`, la función finaliza limpiamente sin lanzar advertencias ni errores.

---

## 4. Estructura Canónica de un Archivo de Rutas Modular

Todo archivo generado por `dkript:make-module` sigue esta plantilla institucional fuertemente tipada:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Rutas del Módulo: Customers (ID RBAC: 7)
|--------------------------------------------------------------------------
|
| Rutas modulares independientes generadas por Dkript Core v1.0.
| Gobernadas por la regla inviolable de 5 posiciones del RBAC.
| Cargadas automáticamente desde bootstrap/app.php.
|
*/

Route::middleware(['auth', 'inactivity.timeout', 'verify.option:7'])->group(function () {
    // Posición 4: Ver / PDF
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('verify.position:7,4')->name('customers.show');
    Route::get('/customers/export/pdf', [CustomerController::class, 'exportPdf'])->middleware('verify.position:7,4')->name('customers.export.pdf');

    // Posición 1: Crear
    Route::get('/customers/create', [CustomerController::class, 'create'])->middleware('verify.position:7,1')->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('verify.position:7,1')->name('customers.store');

    // Posición 2: Editar
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('verify.position:7,2')->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('verify.position:7,2')->name('customers.update');

    // Posición 3: Eliminar
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('verify.position:7,3')->name('customers.destroy');

    // Posición 5: Especial / Excel
    Route::get('/customers/export/excel', [CustomerController::class, 'exportExcel'])->middleware('verify.position:7,5')->name('customers.export.excel');
});
```

---

## 5. Mapeo con la Matriz RBAC de 5 Posiciones

Las rutas modulares implementan una defensa en profundidad de dos capas:

1. **Capa Perimetral de Módulo:**  
   `verify.option:{optionId}` verifica que el usuario autenticado pertenezca a un rol con acceso al módulo en `role_menu_option`. Si no tiene acceso, se aborta con error `403 Prohibido`.
2. **Capa Granular de Posición:**  
   `verify.position:{optionId},{posicion}` valida que el rol cuente con el bit de acción requerido en la matriz RBAC inviolable:

| Posición | Nombre del Permiso | Middleware | Verbo HTTP / Acción |
|:---:|---|---|---|
| **1** | Crear | `verify.position:{id},1` | `GET /create`, `POST /` |
| **2** | Editar | `verify.position:{id},2` | `GET /{id}/edit`, `PUT /{id}` |
| **3** | Eliminar | `verify.position:{id},3` | `DELETE /{id}` |
| **4** | Ver / PDF | `verify.position:{id},4` | `GET /{id}`, `GET /export/pdf` |
| **5** | Especial / Excel | `verify.position:{id},5` | `GET /export/excel` |

---

## 6. Rendimiento y Caché de Rutas (`route:cache`)

Dkript Core v1.0 está diseñado para alto desempeño en producción. Las rutas modulares cumplen al 100% con los requisitos de serialización de rutas de Laravel:

### Verificación del Caché de Rutas:
```bash
# Limpiar caché de rutas previo
php artisan route:clear

# Compilar rutas modulares y de core a caché
php artisan route:cache

# Listar todas las rutas registradas y activas
php artisan route:list
```

### Reglas para Desarrolladores (Anti-Broken-Cache):
- **Cero Closures / Funciones Anónimas:** Ninguna ruta modular debe utilizar closures (`function () {}`). Todo endpoint debe apuntar a un controlador con método (`[Controller::class, 'method']`).
- **Nombres Únicos de Rutas:** Usar siempre nombres con prefijo del recurso (ej. `customers.index`, `customers.store`) para evitar colisiones con el Core u otros módulos.

---

## 7. Ciclo de Vida, Detección de Colisiones y Rollback

El comando `dkript:make-module` integra `routes/modules/{routePrefix}.php` dentro de su sistema de resiliencia:

1. **Detección de Colisiones:**  
   Si `routes/modules/{routePrefix}.php` ya existe en disco y no se suministra la bandera `--force`, el generador aborta antes de modificar la base de datos o el sistema de archivos.
2. **Simulación con `--dry-run`:**  
   Muestra la ruta absoluta del archivo que se crearía sin escribir datos en disco.
3. **Rollback Compensatorio por Error:**  
   - Si el archivo fue **creado de cero** en la ejecución actual y ocurre un error posterior (ej. fallo SQL o interrupción), el archivo se elimina automáticamente.
   - Si el archivo era **preexistente y fue sobrescrito con `--force`**, su contenido original respaldado en memoria se restaura byte por byte.

---

## 8. Preguntas Frecuentes y Resolución de Problemas

### ¿Qué ocurre si elimino manualmente un módulo?
Para eliminar un módulo por completo:
1. Elimine el archivo de rutas `routes/modules/{prefix}.php`.
2. Ejecute `php artisan route:clear`.
3. Elimine el modelo, controlador, requests, vistas y registros en `menu_options` y `permissions`.

### ¿Por qué mi nueva ruta modular no aparece en `route:list`?
1. Verifique que el archivo termine con la extensión `.php` y resida en `routes/modules/`.
2. Asegúrese de que el archivo comience con la etiqueta `<?php`.
3. Si ejecutó previamente `php artisan route:cache`, debe ejecutar `php artisan route:clear` para refrescar el caché local.
