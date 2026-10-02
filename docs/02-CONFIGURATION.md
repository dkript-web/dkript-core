# ⚙️ Arquitectura de Configuración y Parámetros — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Jerarquía de Configuración de 3 Niveles

En Dkript Core, la configuración del sistema sigue una jerarquía estricta de tres niveles orientada a garantizar la precedencia de los ajustes administrativos en base de datos sin comprometer la resiliencia del sistema ante fallos transitorios:

```
[Nivel 1: Primario - Base de Datos]
Parameter (Tabla `parameters`, id=1)
  │ (Si el valor existe y está poblado en BD)
  ▼
[Nivel 2: Secundario - Fallback de Preset / Config]
config('dkript.*') / config('app.*') / config('mail.*')
  │ (Si no hay registro en BD o el campo está vacío)
  ▼
[Nivel 3: Neutro - Fallback Estático del Core]
Valores estáticos neutros codificados en los Servicios del Core
```

### Principio Fundamental: `.env` vs `Parameter`
- **El archivo `.env`** es exclusivo para arranque del framework y variables de infraestructura técnica (conexión a DB, secretos de API, llaves de cifrado, entorno `local`/`production`).
- **El modelo `Parameter` (tabla `parameters`)** es la fuente oficial y primaria de configuración funcional y administrativa del negocio. Es modificable en caliente desde la interfaz web (`/parameters`) por usuarios con permiso administrativo.
- **Queda estrictamente prohibido** utilizar llamadas directas a `env()` en controladores, vistas o servicios. Todo valor debe consumirse a través de `Parameter::getSystemSettings()` o mediante servicios centralizados (`BrandingService`, `MailConfigService`, `BackupService`).

---

## 2. Categorías de Parámetros del Core

La tabla `parameters` consolida las siguientes áreas funcionales:

### A. Identidad Corporativa y Marca
- **`system_name`**: Nombre visible del sistema en el panel, barra superior y correos.
- **`system_logo`**: Ruta relativa del archivo de logotipo institucional en almacenamiento.
- **`show_brand_text`**: Booleano (`1`/`0`) que indica si el texto del sistema acompaña al logo.
- **`contact_email`**: Correo institucional para soporte y contacto visible a los usuarios.

### B. Sesión y Navegación
- **`session_timeout_minutes`**: Tiempo máximo de inactividad permitido (en minutos) antes del cierre automático de sesión por el middleware `InactivityTimeout` (default: `15`).
- **`records_per_page`**: Cantidad de filas por página por defecto en tablas administrativas (default: `10`).
- **`modal_style`**: Estilo visual de modales y diálogos corporativos (`corporate`).

### C. Modo de Mantenimiento Funcional
- **`maintenance_mode`**: Bandera funcional (`0` = Inactivo, `1` = Activo).
  - Cuando está activo (`1`), el middleware `CheckSystemMaintenanceMode` intercepta las peticiones y responde HTTP 503 a usuarios regulares e invitados.
  - El rol **Super Administrador** conserva acceso total para labores de mantenimiento y recuperación.
  - *Distinción crítica:* Este parámetro es independiente del mantenimiento de despliegue de Laravel (`php artisan down`).

### D. Servidor de Correo (SMTP Dinámico)
- **`mail_mailer`**: Protocolo de transporte (`smtp`, `log`, `sendmail`).
- **`mail_host`**: Servidor SMTP (ej. `smtp.mailgun.org`, `smtp.office365.com`).
- **`mail_port`**: Puerto SMTP (`587`, `465`, `25`).
- **`mail_username`**: Usuario o cuenta de correo de autenticación.
- **`mail_password`**: Contraseña o App Password de correo (cifrada en base de datos).
- **`mail_encryption`**: Algoritmo de cifrado (`tls`, `ssl` o `null`).
- **`mail_from_address`**: Dirección de correo remitente corporativa.
- **`mail_from_name`**: Nombre del remitente que visualiza el destinatario.
- *Servicio Consumidor:* `MailConfigService::apply()` aplica estos valores en tiempo de ejecución a la configuración de Laravel antes de cada envío.

### E. Mensajería y Verificación Telefónica (SMS / WhatsApp)
- **`sms_provider`**: Proveedor activo de mensajería para autenticación OTP (`log`, `twilio`, `whatsapp`).
- **Credenciales Twilio:** `twilio_sid`, `twilio_token` (cifrado), `twilio_from`.
- **Credenciales WhatsApp Cloud API:** `whatsapp_phone_number_id`, `whatsapp_access_token` (cifrado), `whatsapp_business_account_id`.
- *Servicio Consumidor:* `MessagingService` implementa el patrón *Strategy* resolviendo el driver adecuado.

### F. Escenas y Páginas de Error
- **`error_display_mode`**: Modo de renderizado para errores HTTP 404, 403, 500, 419, 503 (`scene` = Escena Focal con Drypt Alchemist, `panoramic` = Fondo Inmersivo).

---

## 3. Manejo Seguro de Secretos

Para cumplir con la gobernanza de seguridad de Dkript Core:
1. Las contraseñas de correo, tokens de Twilio y tokens de WhatsApp se almacenan utilizando el cast `encrypted` de Eloquent en el modelo `Parameter`.
2. El servicio centralizado `AuditService` redacta automáticamente cualquier campo sensible reemplazándolo por `[REDACTED]` en las bitácoras y registros de auditoría (`audit_logs`).
3. En las vistas de parámetros, las contraseñas se renderizan en campos tipo `password` con atributos de autocompletado desactivados (`autocomplete="new-password"`).