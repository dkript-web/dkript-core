# 📦 Guía de Instalación y Puesta en Marcha — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  
**Estado:** Estable / Pre-1.0

---

## 1. Requisitos del Sistema

Antes de iniciar la instalación de Dkript Core, asegúrese de que el entorno cumpla con los siguientes prerrequisitos técnicos:

### Entorno de Ejecución Backend
- **PHP:** `^8.3` (probado y certificado en **PHP 8.4+**).
- **Extensiones PHP Obligatorias:**
  - `pdo`, `pdo_mysql` (para base de datos principal MySQL/MariaDB).
  - `pdo_sqlite` (requerido para aislamiento de pruebas automatizadas en memoria).
  - `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `fileinfo`.
  - `zip` (necesaria para respaldos y descargas comprimidas en `BackupService`).
  - `gd` o `imagick` (para procesamiento y validación de avatares y logotipos).
- **Gestor de Paquetes:** Composer `2.2+`.

### Entorno Frontend y Herramientas de Compilación
- **Node.js:** Versión `18.x` o superior (`20.x LTS` recomendada).
- **npm:** Versión `9.x` o superior.
- **Herramientas de Compilación:** Vite `8.x`, Tailwind CSS `v4.x`.

### Motores de Base de Datos Soportados
- **MySQL 8.0+** (Motor principal recomendado para producción).
- **MariaDB 10.5+** (Soportado mediante el driver mysql de Laravel).
- **PostgreSQL 14+** (Soportado para tablas y datos; advertencia: backups/restore de DB con `BackupService` no soportan pgsql).
- **SQLite 3.35+** (Utilizado por defecto en la suite de pruebas `:memory:` y entornos de evaluación rápida).

---

## 2. Obtención del Código

Clone o copie el repositorio base en su directorio de trabajo:

```bash
# Fuente A: Repositorio oficial publicado (cuando esté disponible)
git clone <URL-OFICIAL-DKRIPT-CORE> mi-proyecto
cd mi-proyecto

# Fuente B: Copia limpia local pre-publicación (Utilizada para validación y Paso 1.11)
# Copiar el directorio del Core excluyendo: .git/, .env, vendor/, node_modules/, public/build/,
# storage/app/backups/*, storage/logs/*, storage/framework/cache/*, storage/framework/sessions/*,
# storage/framework/views/* (conservando la estructura de directorios de storage/).
```

> **Nota de Gobernanza para Proyectos Derivados:** Si va a iniciar un nuevo producto comercial o cliente a partir de Dkript Core, consulte previamente la [Guía de Creación de Nuevos Proyectos](11-CREATE-A-NEW-DKRIPT-PROJECT.md). No conserve el historial `.git` del Core en su repositorio derivado.

---

## 3. Instalación de Dependencias

Instale las dependencias de backend y frontend:

```bash
# 1. Dependencias de PHP
composer install

# 2. Dependencias de JavaScript / CSS (Instalación reproducible desde package-lock.json)
npm ci
```
> [!NOTE]
> Se recomienda `npm ci` para instalaciones limpias y reproducibles a partir de las versiones congeladas en `package-lock.json`. Reserve `npm install` para escenarios de desarrollo donde deliberadamente se agreguen o actualicen paquetes.

---

## 4. Configuración del Archivo de Entorno (`.env`)

Copie el archivo de ejemplo si `.env` no existe:

```bash
# En Windows PowerShell:
Copy-Item .env.example .env

# En Linux / macOS:
cp .env.example .env
```

Genere la clave de cifrado de la aplicación:

```bash
php artisan key:generate
```

Configure las variables de conexión a su servidor de base de datos en `.env`:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mi_proyecto_db
DB_USERNAME=root
DB_PASSWORD=mi_password_seguro
```

> **Asegúrese de haber creado la base de datos vacía** en su gestor MySQL antes de ejecutar el instalador:
> ```sql
> CREATE DATABASE mi_proyecto_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> ```

---

## 5. Ejecución del Instalador Oficial (`dkript:install`)

Dkript Core incluye un comando CLI especializado (`InstallDkriptCommand`) que orquesta la configuración inicial, ejecuta las migraciones seguras, siembra los datos estructurales del Core y registra el primer Super Administrador.

### Modo A: Instalación Interactiva (Recomendado)

Ejecute el comando interactivo:

```bash
php artisan dkript:install
```

El instalador lo guiará paso a paso:
1. **Nombre de la Aplicación (`APP_NAME`):** Nombre comercial del proyecto (ej. `Sistema de Gestión Enterprise`).
2. **URL de la Aplicación (`APP_URL`):** URL base local o de red (ej. `http://localhost:8000`).
3. **Comprobación de clave de cifrado (`APP_KEY`):** Se verifica o genera automáticamente.
4. **Validación de Conexión a Base de Datos:** Comprueba la conectividad vía PDO con los parámetros de `.env` o permite modificarlos en caliente.
5. **Detección de Instalación Previa:** Previene sobreescrituras accidentales si ya existen tablas de usuarios o parámetros.
6. **Ejecución de Migraciones:** Ejecuta `php artisan migrate --force`.
7. **Carga de Datos Estructurales (`CoreSeeder`):** Inicializa los parámetros globales del sistema, los roles base (`Super Administrador` y `Operador`) y las 6 opciones de menú troncales con sus 30 permisos base (5 posiciones RBAC).
8. **Alta del Super Administrador:** Solicita interactivamente el Nombre, Correo y Contraseña administrativa (con hashing `bcrypt` y validación estricta de seguridad).

---

### Modo B: Instalación No Interactiva (Automatizada / CI/CD)

Para pipelines automatizados o aprovisionamiento desatendido, puede suministrar todos los argumentos mediante banderas CLI:

```bash
php artisan dkript:install \
  --no-interaction \
  --app-name="Mi Empresa Core" \
  --app-url="http://localhost:8000" \
  --db-connection=mysql \
  --db-host=127.0.0.1 \
  --db-port=3306 \
  --db-database=mi_proyecto_db \
  --db-username=root \
  --db-password="PasswordSeguro123!" \
  --admin-name="Admin General" \
  --admin-email="admin@miempresa.com" \
  --admin-password="SuperPassword2026!"
```

### Tabla de Opciones Disponibles en `dkript:install`

| Opción | Tipo | Descripción |
|---|---|---|
| `--db-connection` | String | Motor de base de datos (`mysql`, `mariadb`, `pgsql`, `sqlite`). |
| `--db-host` | String | Host del servidor de base de datos. |
| `--db-port` | Integer | Puerto de conexión a la base de datos. |
| `--db-database` | String | Nombre de la base de datos o archivo sqlite. |
| `--db-username` | String | Usuario de la base de datos. |
| `--db-password` | String | Contraseña de la base de datos. |
| `--admin-name` | String | Nombre completo del primer Super Administrador. |
| `--admin-email` | String | Correo electrónico para acceso del Super Administrador. |
| `--admin-password` | String | Contraseña segura para el Super Administrador. |
| `--app-name` | String | Nombre oficial del sistema. |
| `--app-url` | String | URL pública o de red local del sistema. |
| `--force` | Flag | Fuerza la instalación aun si detecta instalación previa existente. |
| `--skip-migration` | Flag | Omite la ejecución del comando `migrate`. |
| `--env-file` | String | Ruta a un archivo `.env` alternativo para pruebas aisladas sin tocar el `.env` local. |

---

## 6. Compilación de Activos y Arranque

Compile los estilos y scripts finales con Vite:

```bash
# Compilación para producción:
npm run build

# O bien, servidor de desarrollo con recarga rápida (HMR):
npm run dev
```

Inicie el servidor web de Laravel:

```bash
php artisan serve
```

Abra su navegador en [http://localhost:8000/login](http://localhost:8000/login) e ingrese con las credenciales del Super Administrador registradas durante la instalación.

---

## 7. Verificación del Quality Gate Post-Instalación

Para verificar que su entorno recién instalado aprueba las pruebas automatizadas del Core:

```bash
# 1. Validar integridad de paquetes y autoload
composer validate

# 2. Ejecutar la suite completa de pruebas
php artisan test
```

> **Criterio de Aceptación:** La suite de pruebas debe finalizar con **0 fallos, 0 errores** (300 tests / ~1496 aserciones en el baseline actual).