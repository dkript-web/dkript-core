# 🧪 Estrategia de Pruebas y Quality Gate — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Filosofía de Calidad y Pruebas Automatizadas

En Dkript Core, la suite de pruebas automatizadas es el **Quality Gate mandatorio** para garantizar que ninguna intervención técnica introduzca regresiones, debilite la seguridad o altere comportamientos previamente aprobados.

Antes de dar por concluida cualquier fase o registrar cambios en la Bitácora del proyecto, el **100% de las pruebas debe pasar con éxito (0 fallos, 0 errores)**.

---

## 2. Entorno Aislado de Pruebas (`phpunit.xml`)

La configuración oficial de pruebas reside en `phpunit.xml`:

```xml
<php>
    <ini name="memory_limit" value="512M"/>
    <env name="APP_ENV" value="testing"/>
    <env name="APP_MAINTENANCE_DRIVER" value="file"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
</php>
```

### Características Clave del Aislamiento
1. **Base de Datos en Memoria (`:memory:`):** Las pruebas operan sobre SQLite en memoria volátil. Esto garantiza ejecuciones ultrarrápidas y aislamiento absoluto respecto a la base de datos MySQL local (`dkript_admin_v5` o similar).
2. **Rondas de Hashing Reducidas:** `BCRYPT_ROUNDS=4` acelera significativamente las pruebas de autenticación sin restar rigor funcional.
3. **Controladores en Array / Sync:** Sesiones, caché y colas operan en memoria sin tocar el disco ni dependencias externas.
4. **Aislamiento del Directorio de Respaldos (Gobernanza H-03):** Los tests automatizados del subsistema de restauración (`tests/Feature/RestoreBackupTest.php`) limpian y purgan el directorio `storage/app/backups/` durante su fase de inicialización (`setUp()`) para garantizar un entorno aislado y determinista. Por tanto, los respaldos manuales utilizados para verificación operativa deben generarse **DESPUÉS** de ejecutar la suite completa de pruebas.

---

## 3. Regla Inviolable: Aislamiento del Archivo `.env`

> [!CAUTION]
> **PROTECCIÓN ESTRICTA DEL ENTORNO LOCAL:**  
> Ninguna prueba automatizada, script auxiliar o herramienta de diagnóstico debe alterar, modificar o eliminar permanentemente el archivo `.env` del desarrollador.  
> Toda prueba que requiera simular modificaciones de configuración (como el instalador `dkript:install`) debe utilizar entornos temporales aislados mediante el parámetro `--env-file` o restaurar exactamente los valores originales.

---

## 4. Comandos de Ejecución de Pruebas

### Ejecución de la Suite Completa
```bash
php artisan test
```
*También disponible mediante el comando de Composer:*
```bash
composer test
```

### Ejecución de Pruebas Específicas
Para validar módulos o subsistemas individuales:

```bash
# Probar el generador de módulos y regresión JSON:
php artisan test tests/Feature/MakeDkriptModuleTest.php

# Probar la seguridad y blindaje OWASP:
php artisan test tests/Feature/SecurityCoreTest.php

# Probar el motor de respaldos y restauración:
php artisan test tests/Feature/BackupRestoreTest.php

# Probar el instalador oficial:
php artisan test tests/Feature/DkriptInstallCommandTest.php
```

---

## 5. Métricas del Baseline Actual del Core

Tras el retiro de los módulos temporales de validación, el núcleo Dkript Core mantiene el siguiente estándar de calidad:

- **Pruebas Totales:** ~300 tests.
- **Aserciones:** ~1,496 aserciones.
- **Fallos (Failures):** 0.
- **Errores (Errors):** 0.
- **Omitidos (Skipped):** 0.
- **Tasa de Éxito:** 100% verde (GREEN).

> **Nota:** El número de tests y aserciones puede incrementarse a medida que se agreguen nuevos módulos de negocio en proyectos derivados. El criterio inquebrantable del Quality Gate es mantener **cero fallos y cero errores**, no una cifra estática fija.