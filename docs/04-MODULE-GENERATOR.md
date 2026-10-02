# 🛠️ Generador de Módulos Enterprise (`dkript:make-module`)

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Propósito y Filosofía

El comando `dkript:make-module` es la herramienta oficial de andamiaje (*scaffolding*) de Dkript Core. Su propósito es generar módulos de negocio completos, listos para producción y plenamente integrados a la arquitectura de 5 posiciones de RBAC, sin requerir parches manuales en el núcleo ni alteraciones a archivos troncales como `routes/web.php`.

---

## 2. ⚠️ ADVERTENCIA CRÍTICA: Conexión Activa de Base de Datos

> [!WARNING]
> **COMPORTAMIENTO DE BASE DE DATOS ACTIVA:**  
> `dkript:make-module` interactúa con la base de datos para registrar la opción de menú en `menu_options`, crear los 5 permisos en `permissions` y vincularlos al Rol 1 (`Super Administrador`) en la tabla `role_option`.  
> 
> Dicho registro **utiliza la conexión de base de datos activa configurada en `.env`**.  
> **Antes de ejecutar el comando:**
> 1. Verifique siempre las variables `APP_ENV`, `DB_CONNECTION` y `DB_DATABASE` en su `.env`.
> 2. Nunca ejecute generaciones de prueba o experimentales asumiendo que el comando se aislará automáticamente.
> 3. Para simular sin escribir en disco ni en base de datos, utilice siempre la bandera `--dry-run`.
> 4. Si requiere probar la generación en un script automatizado, fuerce previamente la conexión a SQLite en memoria (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).

---

## 3. Sintaxis y Opciones CLI

```bash
php artisan dkript:make-module <name> [opciones]
```

### Argumento Principal
- `<name>`: Nombre del modelo en singular y formato StudlyCase (ej. `ProductCategory`, `WorkOrder`, `Customer`).

### Opciones Disponibles

| Opción | Tipo | Valor por Defecto | Descripción |
|---|---|---|---|
| `--fields` | String | `name:string,description:text` | Definición de campos separados por comas, admitiendo tipos y modificadores. |
| `--relationships`| String | `null` | Relaciones Eloquent explícitas (ej. `hasMany:Invoice,belongsTo:Category`). |
| `--icon` | String | `bi-box` | Icono de Bootstrap Icons para la navegación (ej. `bi-tag`, `bi-people-fill`). |
| `--soft-deletes` | Flag | `false` | Agrega `softDeletes()` en la migración y el trait `SoftDeletes` en el modelo. |
| `--no-timestamps`| Flag | `false` | Omite las columnas automáticas `created_at` y `updated_at`. |
| `--dry-run` | Flag | `false` | Modo simulación: analiza y reporta lo que generaría sin escribir archivos ni tocar la base de datos. |
| `--force` | Flag | `false` | Sobrescribe los archivos destino si ya existen (requiere precaución). |

---

## 4. Tipos de Campo Soportados y Modificadores

La opción `--help` del comando muestra un ejemplo sintáctico representativo:
```text
--fields[=FIELDS]  Campos separados por coma (ej. name:string,price:decimal(12,2),status:enum(active,inactive),category_id:foreignId(categories:cascade:restrict):nullable)
```

Internamente, el analizador sintáctico (*parser*) en `MakeDkriptModule.php` reconoce y normaliza el siguiente catálogo canónico de tipos de datos y sus alias:

| Tipo | Sintaxis CLI y Alias | Mapeo en Migración | Cast en Modelo | Componente de Formulario |
|---|---|---|---|---|
| Texto corto | `name:string` o `code:string(50)` | `$table->string('name', 50)` | Ninguno | `<input type="text">` |
| Texto largo | `description:text` | `$table->text('description')` | Ninguno | `<textarea>` |
| Entero | `quantity:integer` o `order:int` | `$table->integer('quantity')` | `'integer'` | `<input type="number">` |
| Entero grande | `views:bigInteger` | `$table->bigInteger('views')` | `'integer'` | `<input type="number">` |
| Decimal / Moneda | `price:decimal(12,2)` | `$table->decimal('price', 12, 2)` | `'decimal:2'` | `<input type="number" step="0.01">` |
| Booleano / Switch | `is_active:boolean` | `$table->boolean('is_active')` | `'boolean'` | Toggle Switch estilo iOS |
| Fecha | `birth_date:date` | `$table->date('birth_date')` | `'date'` | `<input type="date">` |
| Fecha y Hora | `published_at:datetime` | `$table->dateTime('published_at')` | `'datetime'` | `<input type="datetime-local">` |
| Timestamp | `verified_at:timestamp` | `$table->timestamp('verified_at')` | `'datetime'` | `<input type="datetime-local">` |
| **JSON Estructurado** | `metadata:json` | `$table->json('metadata')` | `'array'` | `<textarea>` con validación JSON y pretty-print |
| UUID | `reference_uuid:uuid` | `$table->uuid('reference_uuid')` | Ninguno | `<input type="text">` |
| Llave Foránea | `category_id:foreignId(categories:cascade:restrict)` | `$table->foreignId('category_id')->constrained('categories')` | `'integer'` | `<input type="number">` o selector |
| Enumeración | `status:enum(active,inactive,pending)` | `$table->enum('status', ['active',...])` | Ninguno | `<select>` con opciones |

### Modificadores de Campo Disponibles
- `nullable`: El campo acepta valores nulos en BD y validación (`name:string:nullable`).
- `unique`: Aplica restricción de unicidad en migración y validación única (`sku:string:unique`).
- `index`: Crea un índice en base de datos (`customer_id:foreignId:index`).
- `unsigned`: Restringe enteros a positivos (`priority:integer:unsigned`).
- `default(valor)` o `default:valor`: Define un valor por defecto (`is_active:boolean:default(1)`).

---

## 5. Manejo Robusto de Campos JSON (Corrección Aprobada 1.9)

Dkript Core maneja los campos tipo `json` de forma segura de extremo a extremo:
1. **Form Requests (`Store/Update`):**
   - Implementa `prepareForValidation()` con `json_decode()` para normalizar strings provenientes del formulario a arrays nativos de PHP.
   - Aplica validación para rechazar JSON malformado con mensajes contextuales amigables.
2. **Modelo Eloquent:** Declara automáticamente `'campo' => 'array'` en `$casts`.
3. **Vistas Blade:**
   - **Formulario (Create / Edit):** Renderiza un `<textarea>` que muestra el JSON formateado con `json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)`.
   - **Detalle e Índice (Show / Index):** Evita el error `TypeError (htmlspecialchars array)` representando los datos como bloques `<pre><code>` o etiquetas formateadas.
   - **Reporte PDF y Excel:** Serializa el contenido a JSON legible sin corromper la exportación.

---

## 6. Separación Estricta: Generación vs Migración

> [!IMPORTANT]
> **`dkript:make-module` NO ejecuta `php artisan migrate` automáticamente.**  
> El generador produce el archivo de migración en `database/migrations/` de forma no destructiva. Es responsabilidad del desarrollador aplicar la migración en el entorno adecuado ejecutando:
> ```bash
> php artisan migrate
> ```

---

## 7. Protección contra Colisiones y Rollback Compensatorio

1. **Detección Previa de Colisiones:** Antes de escribir cualquier archivo, el generador comprueba si el modelo, controlador, requests, vistas o rutas ya existen. Si detecta conflictos, se detiene y advierte al usuario, solicitando usar `--force` solo si conscientemente desea sobrescribir.
2. **Rollback Compensatorio Atómico:** Si ocurre un error inesperado a mitad del proceso (por ejemplo, fallo de permisos en disco o error en base de datos), el generador:
   - Revierte la transacción en base de datos (`DB::rollBack()`).
   - Elimina en disco los archivos que ya hubiesen sido creados durante esa ejecución.
   - Restablece el sistema al estado limpio previo.

---

## 8. Ejemplo Práctico Completo: Módulo `ProductCategory`

### Paso 1: Simulación con `--dry-run`
```bash
php artisan dkript:make-module ProductCategory \
  --fields="name:string:unique,description:text:nullable,is_active:boolean:default(1),metadata:json:nullable" \
  --icon="bi-tags-fill" \
  --dry-run
```

### Paso 2: Generación Real
```bash
php artisan dkript:make-module ProductCategory \
  --fields="name:string:unique,description:text:nullable,is_active:boolean:default(1),metadata:json:nullable" \
  --icon="bi-tags-fill"
```

### Paso 3: Aplicar Migración
```bash
php artisan migrate
```

### Paso 4: Verificar Rutas y Pruebas
```bash
# Verificar que las nuevas rutas se cargaron en el pipeline modular:
php artisan route:list --path=product-categories

# Ejecutar el Feature Test generado para el módulo:
php artisan test tests/Feature/ProductCategoryTest.php
```