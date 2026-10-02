# 💾 Respaldos y Restauración del Sistema — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Arquitectura del Motor de Respaldos (`BackupService`)

Dkript Core integra un motor nativo de copias de seguridad (`App\Services\BackupService`) capaz de generar y administrar respaldos completos de la base de datos y de los archivos multimedia de la aplicación, sin depender de binarios externos como `mysqldump` en el servidor.

Todos los respaldos se almacenan de forma segura en `storage/app/backups/`.

---

## 2. Compatibilidad de Motores de Base de Datos

> [!IMPORTANT]
> **SOPORTE POR MOTOR EN BACKUP / RESTORE:**  
> - **MySQL / MariaDB:** Totalmente soportado para generación (`.sql`, `.sql.gz`) y restauración.  
> - **SQLite:** Totalmente soportado para entornos de prueba y desarrollo.  
> - **PostgreSQL:** **NO SOPORTADO** actualmente para generación ni restauración en `BackupService`. Si utiliza PostgreSQL como motor de base de datos, debe gestionar los respaldos mediante herramientas externas como `pg_dump` y `pg_restore`.

---

## 3. Tipos de Respaldos Soportados

### A. Respaldos de Base de Datos
- **Formato Comprimido (`.sql.gz` - Recomendado):** Archivo SQL comprimido mediante `gzencode()`, reduciendo hasta un 85% el espacio en disco.
- **Formato Plano (`.sql`):** Script SQL puro que incluye instrucciones `DROP TABLE`, `CREATE TABLE` e `INSERT` por lotes con desactivación temporal de llaves foráneas.
- **Identificador de Motor:** Cada respaldo incluye en su cabecera el motor con el que fue creado (ej. `-- Motor de Base de Datos: mysql`), impidiendo restauraciones cruzadas entre motores incompatibles.

### B. Respaldos de Medios y Archivos (`.zip`)
- Empaqueta recursivamente los directorios de archivos subidos por los usuarios (`public/uploads/` y `storage/app/public/`), excluyendo temporales y respaldos previos para evitar recursión infinita.

---

## 4. Automatización y Programación (`dkript:auto-backup`)

Dkript Core incluye el comando de consola:

```bash
php artisan dkript:auto-backup [opciones]
```

### Opciones
- `--force`: Ejecuta el respaldo programado de inmediato, incluso si la bandera de automatización se encuentra inactiva en los parámetros del sistema.

### Política de Retención y Purga Automática
El comando evalúa los parámetros de retención configurados en el panel (`/backups`):
1. Genera el respaldo del día (base de datos y/o medios).
2. Inspecciona los archivos existentes en `storage/app/backups/`.
3. Purga automáticamente aquellos que excedan la antigüedad máxima configurada (ej. eliminar copias con más de 30 días).

Para ejecutarlo diariamente, configure la tarea en el programador de tareas (*Scheduler*) de su servidor:
```bash
* * * * * cd /ruta-a-su-proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Proceso de Restauración Segura (Restore)

La restauración de base de datos es una operación de alto riesgo administrada con estrictos mecanismos de contención:

```
[Solicitud de Restore]
         │
         ▼
[1. Validación Anti-Path Traversal]
         │
         ▼
[2. Validación de Motor Compatible (Cabecera SQL vs Driver Activo)]
         │
         ▼
[3. Safety Backup Preventivo Obligatorio (backup_db_safety_*)]
   ├── Si falla ──▶ ABORTA LA RESTAURACIÓN (Cero cambios en BD)
   └── Si éxito ──▶ Continúa
         │
         ▼
[4. Activación de Restore Lock (storage/framework/dkript_restore.lock)]
   └── Pone al sistema en HTTP 503 para usuarios normales
         │
         ▼
[5. Streaming SQL Seguro por Lotes (Foreign Keys OFF)]
         │
         ▼
[6. Liberación de Lock y Reactivación del Sistema]
```

### Garantías de Seguridad Implementadas
1. **Safety Backup Obligatorio:** Antes de tocar una sola tabla, el sistema genera automáticamente un respaldo preventivo (`backup_db_safety_*`). Si este respaldo preventivo no se puede escribir, la restauración se cancela de inmediato.
2. **Restore Lock (`dkript_restore.lock`):** Crea un archivo de bloqueo en `storage/framework/`. El middleware `CheckSystemMaintenanceMode` intercepta las peticiones web y responde HTTP 503 a todos los usuarios salvo al Super Administrador.
3. **Validación contra Manipulación:** Solo se admiten archivos generados oficialmente por Dkript Core. Se bloquea cualquier intento de inyección de nombres con `..` o rutas relativas.

> [!WARNING]
> **Limitación de Concurrencia:** El *Restore Lock* protege el tráfico HTTP entrante en la aplicación web. No suspende automáticamente workers de colas en segundo plano, tareas CLI activas ni procesos externos concurrentes en el servidor.

---

## 6. Consideración Operativa: Pruebas Automatizadas y Directorio de Respaldos (Gobernanza H-03)

> [!NOTE]
> **Interacción con la Suite de Pruebas Automatizadas:**  
> Las pruebas automatizadas del subsistema de restauración (`tests/Feature/RestoreBackupTest.php`) limpian y purgan el directorio `storage/app/backups/` durante su fase de inicialización (`setUp()`) para garantizar aislamiento absoluto y determinismo entre ejecuciones.  
> Por tanto, cualquier respaldo manual generado con fines de auditoría o validación operativa debe realizarse **DESPUÉS** de ejecutar la suite completa de pruebas (`php artisan test`). La ausencia de archivos en dicho directorio tras una corrida de tests es el comportamiento de aislamiento esperado y no constituye un defecto en `BackupService`.