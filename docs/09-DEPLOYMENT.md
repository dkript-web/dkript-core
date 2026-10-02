# 🚀 Guía de Despliegue en Producción — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Requisitos del Servidor de Producción

Para garantizar estabilidad y rendimiento óptimo en producción:
- **Sistema Operativo:** Linux (Ubuntu LTS 22.04 / 24.04 o Debian 12 recomendados).
- **Servidor Web:** Nginx (recomendado) o Apache 2.4+.
- **PHP:** `8.3+` (PHP `8.4` recomendado) con PHP-FPM y extensiones (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `fileinfo`, `zip`, `gd`/`imagick`).
- **Base de Datos:** MySQL `8.0+` o MariaDB `10.5+`.
- **Node.js / npm:** Requerido para la fase de construcción (*build stage*) de activos.

---

## 2. Preparación del Despliegue Paso a Paso

### Paso 1: Clonar o Transferir el Código
```bash
git clone https://github.com/tu-organizacion/tu-proyecto.git /var/www/mi-sistema
cd /var/www/mi-sistema
```

### Paso 2: Permisos de Almacenamiento
Asegúrese de que el usuario del servidor web (`www-data` o `nginx`) tenga permisos de escritura en los directorios volátiles:

```bash
sudo chown -R www-data:www-data /var/www/mi-sistema/storage /var/www/mi-sistema/bootstrap/cache
sudo chmod -R 775 /var/www/mi-sistema/storage /var/www/mi-sistema/bootstrap/cache
```

### Paso 3: Instalación de Dependencias de Producción
```bash
# Instalar dependencias de PHP optimizadas para producción:
composer install --no-dev --optimize-autoloader --no-interaction

# Instalar dependencias de Node y compilar activos:
npm ci
npm run build
```

### Paso 4: Configuración del Archivo `.env` en Producción
Ajuste las variables críticas para el entorno de producción:

```ini
APP_NAME="Sistema Corporativo"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sistema.miempresa.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_prod_db
DB_USERNAME=sistema_prod_user
DB_PASSWORD="PasswordExtremadamenteSeguro2026!"

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
```

Genere la clave de cifrado si no existe:
```bash
php artisan key:generate --force
```

### Paso 5: Enlace de Almacenamiento Público
```bash
php artisan storage:link
```

### Paso 6: Inicialización de Base de Datos y Super Administrador
Para una primera instalación limpia en producción, ejecute el instalador en modo no interactivo:

```bash
php artisan dkript:install \
  --no-interaction \
  --force \
  --admin-name="Administrador Principal" \
  --admin-email="admin@miempresa.com" \
  --admin-password="PasswordAdministrativoRobusto!"
```

*Si el sistema ya estaba previamente instalado y solo está desplegando una actualización de código:*
```bash
php artisan migrate --force
```

---

## 3. Optimizaciones de Rendimiento y Caché

En producción, compile los cachés del framework para maximizar la velocidad de respuesta:

```bash
# 1. Caché de configuración:
php artisan config:cache

# 2. Caché de rutas (incluye automáticamente las rutas modulares de routes/modules/):
php artisan route:cache

# 3. Compilación previa de vistas Blade:
php artisan view:cache
```

---

## 4. Configuración del Programador de Tareas (Cron / Scheduler)

Para habilitar respaldos automáticos (`dkript:auto-backup`) y limpieza programada, agregue una entrada al `crontab` del usuario `www-data`:

```bash
sudo crontab -u www-data -e
```

Agregue la siguiente línea:
```cron
* * * * * cd /var/www/mi-sistema && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Mantenimiento: Despliegue vs Aplicación

Es fundamental distinguir los dos tipos de mantenimiento existentes:

| Tipo | Mecanismo | Alcance y Comportamiento |
|---|---|---|
| **Mantenimiento de Despliegue** | `php artisan down` / `php artisan up` | Nivel Framework. Se activa durante actualizaciones del servidor, pull de código o migraciones estructurales pesadas. Muestra la pantalla de mantenimiento estática de Laravel. |
| **Mantenimiento de la Aplicación** | `Parameter.maintenance_mode = 1` | Nivel Negocio. Se activa desde el panel (`/parameters`). Permite al Super Administrador seguir navegando y operando mientras el resto de los usuarios reciben HTTP 503. |