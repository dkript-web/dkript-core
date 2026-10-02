# 🔍 Diagnóstico y Solución de Problemas (Troubleshooting) — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Problemas de Base de Datos y Entorno

### Incidencia 1.1: Error `Unknown database` al arrancar o instalar
- **Síntoma:** Al ejecutar `php artisan dkript:install` o `migrate`, se produce el error `SQLSTATE[HY000] [1049] Unknown database 'nombre_db'`.
- **Causa Probable:** La base de datos especificada en `.env` no existe aún en el motor MySQL.
- **Verificación:** Conéctese a MySQL mediante CLI o cliente gráfico: `mysql -u root -p -e "SHOW DATABASES;"`.
- **Solución Segura:** Cree la base de datos vacía con el juego de caracteres oficial antes de continuar:
  ```sql
  CREATE DATABASE nombre_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```

---

### Incidencia 1.2: Variables de `.env` no surten efecto
- **Síntoma:** Se modifica una variable en `.env` (ej. `DB_PASSWORD`, `APP_URL`), pero Laravel sigue tomando el valor anterior.
- **Causa Probable:** La configuración del framework se encuentra compilada en caché (`bootstrap/cache/config.php`).
- **Verificación:** Compruebe si existe el archivo `bootstrap/cache/config.php`.
- **Solución Segura:** Limpie la caché de configuración:
  ```bash
  php artisan config:clear
  ```

---

### Incidencia 1.3: Contaminación accidental de base de datos activa por comandos CLI
- **Síntoma:** Aparecen registros temporales o de prueba en la base de datos real local (`dkript_admin_v5` o similar) tras ejecutar comandos de desarrollo o scripts auxiliares.
- **Causa Probable:** Comandos como `dkript:make-module` o scripts ejecutados con `artisan tinker` utilizan la conexión por defecto configurada en `.env`.
- **Verificación:** Revise `DB_DATABASE` en `.env` antes de ejecutar comandos interactivos.
- **Solución Segura:** 
  1. Utilice siempre `--dry-run` para previsualizar módulos sin escribir en disco ni en base de datos.
  2. Si ejecuta scripts de diagnóstico manuales, fuerce explícitamente la conexión a SQLite en memoria antes de instanciar modelos o esquemas:
     ```php
     config(['database.default' => 'sqlite']);
     config(['database.connections.sqlite.database' => ':memory:']);
     ```

---

## 2. Enrutamiento y Módulos

### Incidencia 2.1: Rutas de un nuevo módulo responden 404
- **Síntoma:** Se generó un módulo en `routes/modules/mi-modulo.php`, pero al ingresar al navegador se obtiene HTTP 404.
- **Causa Probable:** 
  1. Las rutas del sistema están compiladas en caché y no han sido refrescadas.
  2. El archivo de rutas no tiene extensión `.php` o no está ubicado directamente en `routes/modules/`.
- **Verificación:** Ejecute `php artisan route:list --path=mi-modulo` para comprobar si el pipeline reconoce las rutas.
- **Solución Segura:**
  ```bash
  php artisan route:clear
  # Si está en producción, regenere la caché:
  php artisan route:cache
  ```

---

### Incidencia 2.2: Colisión al ejecutar `dkript:make-module`
- **Síntoma:** El generador se detiene con el mensaje `ERROR DE COLISIÓN: Se detectaron archivos o componentes preexistentes`.
- **Causa Probable:** Ya existe un modelo, controlador, migración o archivo de rutas con ese nombre.
- **Verificación:** Revise el listado de archivos en conflicto reportado en la consola.
- **Solución Segura:** 
  1. Si se trata de un módulo diferente, elija un nombre que no colisione.
  2. Si intencionalmente desea regenerar el módulo reemplazando los archivos previos, añada la bandera `--force`.

---

### Incidencia 2.3: Error `TypeError: htmlspecialchars(): Argument #1 ($string) must be of type string, array given` en campos JSON
- **Síntoma:** Al visualizar la tabla `index` o el detalle `show` de un módulo con campo `json`, la vista lanza un error 500 de PHP.
- **Causa Probable:** La plantilla Blade intenta renderizar una propiedad casteada como array `{{ $item->metadata }}` directamente como si fuera string.
- **Solución Segura:** Asegúrese de que el generador contenga la corrección del Paso 1.9, la cual representa los campos `json` utilizando `json_encode($item->metadata, JSON_PRETTY_PRINT)` o etiquetas formateadas `<pre><code>`.

---

## 3. Sesión, Mantenimiento y Respaldos

### Incidencia 3.1: Sesión expirada pero no desautenticada
- **Síntoma:** El usuario es redirigido a `/login` pero al volver a cargar la página reaparece como autenticado.
- **Causa Probable:** Se realizó una redirección en frontend sin invocar la ruta oficial de cierre de sesión (`POST /logout`).
- **Solución Segura:** Respete la Regla Inviolable de Gobernanza: Todo cierre por inactividad debe pasar por el flujo oficial de desautenticación (`Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`).

---

### Incidencia 3.2: Error de incompatibilidad de motor en Restauración de Respaldos
- **Síntoma:** Al intentar restaurar un respaldo `.sql` o `.sql.gz`, el sistema responde con error 422: `Incompatibilidad de motor: El respaldo fue generado para [sqlite], pero la base de datos actual utiliza [mysql]`.
- **Causa Probable:** Se intentó cargar un respaldo generado en SQLite dentro de un servidor MySQL, o viceversa.
- **Solución Segura:** Solo restaure copias de seguridad en entornos con el mismo motor de base de datos con el que fueron creadas. Verifique la cabecera del archivo `.sql` antes de proceder.

---

### Incidencia 3.3: Acceso bloqueado por Modo Mantenimiento
- **Síntoma:** Todas las rutas responden HTTP 503 y ningún usuario regular puede acceder al sistema.
- **Causa Probable:** El parámetro `maintenance_mode` fue activado en la base de datos o existe un archivo `dkript_restore.lock` residual.
- **Solución Segura:** 
  1. Inicie sesión con la cuenta del **Super Administrador** (`id: 1`), quien posee bypass automático para labores de recuperación.
  2. Vaya a `/parameters` y desactive el Modo Mantenimiento.
  3. Si la causa es un bloqueo por restauración interrumpida, verifique y retire el archivo `storage/framework/dkript_restore.lock`.