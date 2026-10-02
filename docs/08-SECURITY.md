# 🔒 Seguridad y Blindaje del Sistema — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Estándares de Seguridad y Blindaje OWASP Top 10

Dkript Core incorpora mecanismos integrales de mitigación contra las principales vulnerabilidades web:

### A. Mitigación de Inyecciones (A03:2021 - Injection)
- **Eloquent ORM:** Todas las consultas a base de datos utilizan sentencias preparadas y enlaces fuertemente tipados.
- **Generador de Módulos:** `MakeDkriptModule` valida que los nombres de tablas y columnas cumplan con identificadores estrictos `[a-zA-Z0-9_]`, bloqueando la inyección de sentencias SQL en migraciones generadas.

### B. Prevención de Autenticación Rota (A07:2021 - Identification & Authentication Failures)
- **Rate Limiting:** Límites estrictos de intentos fallidos en el formulario de login y en el reenvío de códigos OTP para mitigar ataques de fuerza bruta.
- **Hashing Robusto:** Contraseñas hasheadas exclusivamente con `bcrypt` y factor de costo mínimo configurable (`BCRYPT_ROUNDS=12` en producción).

---

## 2. Autenticación de Dos Factores (2FA / MFA TOTP RFC 6238)

El servicio `TwoFactorAuthService` provee soporte nativo para 2FA compatible con Google Authenticator, Microsoft Authenticator y 1Password:
- **Secreto Cifrado:** El secreto TOTP se almacena cifrado en la base de datos (`users.two_factor_secret`).
- **Códigos de Recuperación:** Genera 8 códigos de emergencia de un solo uso con hashing SHA-256 en la base de datos (`users.two_factor_recovery_codes`). Al consumirse un código, queda invalidado permanentemente.

---

## 3. Recuperación Híbrida de Contraseñas

Dkript Core ofrece dos vías seguras de restablecimiento:
1. **Código OTP por Mensajería (SMS / WhatsApp):**
   - Código numérico de 6 dígitos con expiración de 10 minutos.
   - Máximo de intentos configurable y reenvío con tiempo de espera forzado.
2. **Enlace Seguro por Correo Corporativo:**
   - Token firmado criptográficamente de un solo uso.
   - Registro en bitácora de auditoría mediante `AuditService` (evento `PASSWORD_RESET` / `AUTH`), excluyendo contraseñas, tokens y OTPs del payload (`method=EMAIL` / `method=OTP`).

---

## 4. Gestión de Sesiones Activas y Expiración por Inactividad

### Principio Inviolable: Un Redirect a Login NO es Logout
Un redireccionamiento hacia la pantalla de inicio de sesión no invalida una sesión en el servidor. Toda expiración o cierre de sesión en Dkript Core cumple el flujo oficial de desautenticación:
1. Destrucción de credenciales activas: `Auth::logout()`.
2. Invalidación del almacenamiento de sesión: `session()->invalidate()`.
3. Regeneración obligatoria del token CSRF: `session()->regenerateToken()`.

### Detección Dual de Inactividad
- **En Frontend:** Un temporizador en JavaScript en `custom.js` monitoriza eventos de interacción (teclado, mouse, táctil). Al acercarse el límite configurado (`session_timeout_minutes`), muestra un modal con cuenta regresiva. Si el usuario no responde, invoca la ruta oficial `POST /logout`.
- **En Backend (`InactivityTimeout`):** Si un cliente supera el tiempo máximo sin interactuar, el middleware invalida la sesión en el servidor, audita el cierre por inactividad y redirige a `/login` (o responde HTTP 401 si la petición es AJAX/JSON).

---

## 5. Auditoría con Redacción Automática de Secretos (`AuditService`)

El servicio `AuditService` intercepta operaciones críticas del sistema y registra en la tabla `audit_logs`:
- Usuario, dirección IP, User-Agent, ruta y método HTTP.
- Valores anteriores (`old_values`) y nuevos (`new_values`) en formato JSON.
- **Redacción Automática:** Toda clave coincidente con contraseñas, tokens, OTPs o llaves de API (ej. `password`, `twilio_token`, `whatsapp_access_token`, `two_factor_secret`, `mail_password`) es sustituida automáticamente por la cadena `[REDACTED]` antes de persistir.

---

## 6. Cabeceras de Seguridad HTTP (`SecurityHeaders`)

El middleware global `SecurityHeaders` adjunta a cada respuesta HTTP:
- `X-Frame-Options: SAMEORIGIN` (prevención de Clickjacking).
- `X-Content-Type-Options: nosniff` (prevención de MIME-sniffing).
- `X-XSS-Protection: 1; mode=block`.
- `Referrer-Policy: strict-origin-when-cross-origin`.
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`.