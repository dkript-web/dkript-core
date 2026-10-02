# 🚀 Dkript Core — Generador de Módulos Enterprise (`dkript:make-module`)
**Versión del Core:** 0.1.0-dev  
**Ruta:** `app/Console/Commands/MakeDkriptModule.php`  
**Gobernanza:** Dkript Inc.  

---

## 1. Introducción

El comando `dkript:make-module` es la herramienta de andamiaje (*scaffolding*) de Dkript Core diseñada para acelerar el desarrollo de módulos empresariales completos, consistentes y alineados a los más altos estándares de calidad, seguridad y gobernanza.

Genera de manera coordinada todos los componentes necesarios para un módulo productivo:
- **Migración de base de datos** optimizada y portable.
- **Modelo Eloquent** con `$fillable`, `$casts` nativos y relaciones fuertemente tipadas.
- **Form Requests independientes** (`Store{Model}Request` y `Update{Model}Request`) con validaciones avanzadas e ignorado automático de ID en actualizaciones.
- **Policy de autorización** `{Model}Policy` mapeada a la regla de 5 posiciones de RBAC.
- **Factory** con generación sintética de datos contextuales mediante Faker.
- **Controlador RBAC** con los 7 métodos canónicos de recurso, auditoría centralizada con `AuditService`, respuestas AJAX/JSON y exportación a Excel y PDF.
- **Suite de Vistas Blade**: `index.blade.php` (con tabla interactiva y modal integrado), `create.blade.php`, `edit.blade.php` y `show.blade.php`.
- **Plantilla de Reporte PDF** (`resources/views/reports/{kebab}.blade.php`) compatible con Dompdf y diseño institucional.
- **Feature Test automatizado** (`tests/Feature/{Model}Test.php`) con cobertura de permisos y operaciones CRUD.
- **Registro en Base de Datos**: Creación de `MenuOption`, generación de 5 permisos RBAC y asignación al Rol 1 (Super Administrador).
- **Rutas modulares independientes** generadas en `routes/modules/{routePrefix}.php`, cargadas automáticamente por el pipeline de Laravel 13 en `bootstrap/app.php` y vinculadas a la matriz RBAC de 5 posiciones, manteniendo `routes/web.php` 100% inmutable y reservado exclusivamente para el Core.

---

## 2. Sintaxis y Opciones CLI

```bash
php artisan dkript:make-module {name} [opciones]
```

### Argumentos y Opciones

| Opción | Tipo | Valor por Defecto | Descripción |
|---|---|---|---|
| `name` | Argumento | *Requerido* | Nombre del módulo en singular y formato StudlyCase (ej. `Customer`, `ProductCategory`, `WorkOrder`). |
| `--fields` | Opción | `name:string,description:text` | Definición de campos separados por comas, soportando tipos avanzados y modificadores. |
| `--relationships` | Opción | `null` | Relaciones Eloquent explícitas (ej. `hasMany:Invoice,belongsTo:Category,hasOne:Profile`). |
| `--icon` | Opción | `bi-box` | Icono de Bootstrap Icons para el menú de navegación (ej. `bi-people`, `bi-cart-check`, `bi-cash-coin`). |
| `--soft-deletes` | Flag | `false` | Habilita `SoftDeletes` en la migración, modelo, controlador y pruebas automatizadas. |
| `--no-timestamps` | Flag | `false` | Desactiva las marcas de tiempo automáticas `created_at` y `updated_at`. |
| `--dry-run` | Flag | `false` | Modo de simulación: inspecciona los archivos y registros que se crearían sin realizar cambios en disco ni en base de datos. |
| `--force` | Flag | `false` | Permite sobrescribir archivos preexistentes en disco. Con esta bandera, las migraciones existentes se reciclan en lugar de duplicarse. |

> [!IMPORTANT]
> **Eliminación de la opción `--migrate` por diseño arquitectónico:**
> La opción `--migrate` fue retirada permanentemente de `dkript:make-module`. En motores relacionales como MySQL/MariaDB, la ejecución de instrucciones DDL (`CREATE TABLE`) dispara un `COMMIT` implícito automático en el motor, lo que anula cualquier transacción SQL activa. Si la migración fallaba a mitad del proceso, los registros de menú y permisos RBAC no podían ser revertidos mediante `DB::rollBack()`.
> Siguiendo el principio de separación de responsabilidades, el generador prepara los artefactos y el desarrollador ejecuta de manera explícita:
> ```bash
> php artisan migrate
> ```

---

## 3. Tipos de Datos y Modificadores Soportados en `--fields`

El generador incluye un analizador sintáctico O(n) con soporte para anidamiento de argumentos y parámetros complejos:

### Tipos de Datos (12 Tipos Nativos)
- `string` o `string(longitud)` (por defecto 255).
- `text`.
- `integer` o `int`.
- `bigInteger` o `bigint`.
- `decimal(precision,scale)` (ej. `decimal(12,2)`).
- `boolean` o `bool`.
- `date`.
- `dateTime` o `datetime`.
- `timestamp`.
- `json`.
- `uuid`.
- `enum(valor1,valor2,...)` (crea un `string(50)` en BD y valida en Form Request con `Rule::in(...)` para máxima portabilidad).
- `foreignId(tabla_destino:on_delete:on_update)` (ej. `foreignId(categories:cascade:restrict)`).

### Modificadores de Columna
- `nullable`: Permite valores nulos.
- `unique`: Aplica restricción de unicidad y regla de validación única.
- `index`: Crea un índice en base de datos.
- `unsigned`: Aplica modificador no negativo a enteros.
- `default:valor` o `default(valor)`: Establece un valor predeterminado.

---

## 4. Gobernanza RBAC de 5 Posiciones

Todo módulo generado se integra automáticamente en la matriz RBAC inviolable de Dkript Core:

| Posición | Acción RBAC | Verbo HTTP / Ruta | Método de Controlador | Cobertura en Policy |
|:---:|---|---|---|---|
| **1** | **Crear** | `GET /create`, `POST /` | `create()`, `store()` | `create()` |
| **2** | **Editar** | `GET /{id}/edit`, `PUT/PATCH /{id}` | `edit()`, `update()` | `update()` |
| **3** | **Eliminar** | `DELETE /{id}` | `destroy()` | `delete()` |
| **4** | **Ver / PDF** | `GET /`, `GET /{id}`, `GET /export-pdf` | `index()`, `show()`, `exportPdf()` | `viewAny()`, `view()` |
| **5** | **Especial / Excel** | `GET /export-excel` | `exportExcel()` | `exportExcel()` |

El generador crea la opción de menú en `menu_options`, registra los 5 permisos en `permissions` y los asigna automáticamente al Rol 1 (Super Administrador) dentro de una transacción de base de datos segura.

---

## 5. Garantía de Rollback Compensatorio y Protección de `--force`

A diferencia de generadores convencionales que dejan archivos residuales o registros huérfanos cuando ocurre un imprevisto, `dkript:make-module` implementa un **Rollback Compensatorio Multinivel**:

1. **Reversión Transaccional en Base de Datos:**
   Si ocurre cualquier error antes del `DB::commit()`, se ejecuta `DB::rollBack()` de inmediato, garantizando cero registros huérfanos en `menu_options`, `permissions`, `role_menu_option` o `role_permission`.
2. **Eliminación Selectiva de Archivos Nuevos:**
   Los archivos que no existían antes de la ejecución y fueron creados durante el proceso se registran en `$createdFiles` y se eliminan físicamente en caso de fallo.
3. **Limpieza de Directorios Nuevos:**
   Las carpetas creadas exclusivamente para el módulo (como `resources/views/{kebab}`) se registran en `$createdDirectories` y se eliminan en caso de error, evitando dejar directorios vacíos o corruptos.
4. **Protección de Archivos Preexistentes con `--force`:**
   Si se utilizó `--force` para sobrescribir archivos y la operación falla más adelante, los archivos originales **NO se destruyen**. Su contenido previo es respaldado en memoria antes de la sobrescritura y restaurado íntegramente byte por byte en el rollback.
5. **Inmutabilidad de `routes/web.php` y Aislamiento de Rutas Modulares:**
   A partir de la versión 1.0 (Paso 1.6), las rutas de los módulos ya no se inyectan en `routes/web.php`. Se generan como un archivo independiente en `routes/modules/{routePrefix}.php`. En consecuencia, `routes/web.php` permanece 100% inmutable byte por byte. Si ocurre un fallo en la generación, el archivo modular en `routes/modules/` se elimina de inmediato o se restaura a su versión original previa si se utilizó `--force`.
6. **Deduplicación de Migraciones:**
   Al ejecutar con `--force`, el generador detecta si ya existe una migración previa para la tabla (`migrations/*_create_{table}_table.php`) y la reutiliza en lugar de generar una migración con un timestamp nuevo, evitando duplicados conflictivos en `database/migrations/`.

---

## 6. Ejemplos Prácticos

### Ejemplo 1: Simulación Previa con `--dry-run`
Permite inspeccionar los componentes sin tocar el disco ni la base de datos:
```bash
php artisan dkript:make-module Customer \
  --fields="name:string(150),email:string:unique,phone:string(20):nullable,status:enum(active,inactive):default:active" \
  --icon="bi-people" \
  --dry-run
```

### Ejemplo 2: Generación Completa con Relaciones y Soft Deletes
```bash
php artisan dkript:make-module ProductCategory \
  --fields="name:string(100):unique,slug:string(120):unique:index,description:text:nullable,is_active:boolean:default:1" \
  --relationships="hasMany:Product" \
  --icon="bi-tags" \
  --soft-deletes
```

Tras la generación, aplique la migración manualmente:
```bash
php artisan migrate
```

### Ejemplo 3: Actualización Forzada de un Módulo Preexistente
```bash
php artisan dkript:make-module Invoice \
  --fields="invoice_number:string(50):unique,customer_id:foreignId(customers:cascade:restrict),total_amount:decimal(12,2),status:enum(draft,paid,void):default:draft" \
  --relationships="belongsTo:Customer,hasMany:InvoiceItem" \
  --icon="bi-receipt" \
  --force
```

---

## 7. Reglas de Validación de Seguridad
El generador protege el sistema contra entradas maliciosas o nombres incompatibles con PHP:
- **Identificador Válido:** Debe comenzar con letra y contener únicamente caracteres alfanuméricos.
- **Protección contra Path Traversal:** Se rechazan nombres que contengan `..`, `/` o `\`.
- **Palabras Reservadas de PHP:** Se bloquean nombres como `Class`, `Function`, `Interface`, `Trait`, `Return`, `Match`, `Global`, `Default`, etc.
