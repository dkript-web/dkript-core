# 🛡️ Control de Acceso (RBAC) y Navegación Dinámica — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  

---

## 1. Fundamentos del Modelo RBAC de Dkript Core

El control de acceso basado en roles (Role-Based Access Control) de Dkript Core está diseñado para proporcionar una granularidad atómica y determinista. No utiliza strings dispersos o permisos arbitrarios, sino una estructura relacional estricta compuesta por cuatro modelos principales:

```
[Role] (roles)
  │
  ├── BelongsToMany (tabla pivote `role_option`) ──▶ [MenuOption] (menu_options)
  │                                                        │
  └── BelongsToMany (vía permisos asignados)               └── HasMany ──▶ [Permission] (permissions)
```

### Modelos y Tablas
1. **`MenuOption` (tabla `menu_options`):** Representa un módulo funcional del sistema.
   - Campos clave: `id`, `name`, `icon` (Bootstrap Icons), `route_name` (ruta principal), `order` (orden numérico en menú), `status` (activo/inactivo).
2. **`Permission` (tabla `permissions`):** Representa una acción autorizable sobre una opción de menú específica.
   - Campos clave: `id`, `menu_option_id`, `position` (entero del 1 al 5), `name`, `status`.
3. **`Role` (tabla `roles`):** Representa un perfil de usuario dentro de la organización.
   - Campos clave: `id`, `name`, `description`, `status`.
4. **`role_option` (tabla pivote):** Vincula las opciones de menú autorizadas para cada rol.

---

## 2. La Regla Inviolable de 5 Posiciones

Cada módulo registrado en Dkript Core posee exactamente **5 posiciones fijas de permisos**, las cuales se representan de manera idéntica tanto en la base de datos como en los formularios de configuración de roles:

| Posición | Acción RBAC | Identificador Visual | Alcance Funcional |
|:---:|---|---|---|
| **1** | **Crear** | `bi-plus-circle-fill` (Esmeralda) | Formularios de creación (`create`), almacenamiento (`store`) y clonación de registros. |
| **2** | **Editar** | `bi-pencil-square` (Ámbar) | Formularios de edición (`edit`) y actualización de registros (`update`). |
| **3** | **Eliminar** | `bi-trash3-fill` (Rosa/Rojo) | Destrucción o borrado físico/lógico de registros (`destroy`). |
| **4** | **Ver / PDF** | `bi-eye-fill` (Azul Cielo) | Consulta de fichas y detalles (`show`) y generación de reportes oficiales en **PDF** (`exportPdf`). |
| **5** | **Especial / Excel** | `bi-shield-check` (Púrpura) | Acciones administrativas avanzadas, cambios masivos y exportación de datos en **Excel** (`exportExcel`). |

---

## 3. Comportamiento del Super Administrador (`id: 1`)

El rol con ID `1` (`Super Administrador`) es el rol raíz del sistema:
- **Bypass Automático:** El método `$user->isSuperAdmin()` otorga acceso irrestricto a todos los módulos y acciones del sistema.
- **Acceso en Modo Mantenimiento:** Cuando el sistema se encuentra en modo mantenimiento funcional (`Parameter.maintenance_mode = 1`), el Super Administrador es el único usuario con privilegios para navegar e interactuar con la plataforma para labores de recuperación.
- **Asignación en Generador:** Al crear un módulo nuevo mediante `dkript:make-module`, sus 5 permisos se asocian de forma automática al Super Administrador.

---

## 4. Middlewares de Autorización

Dkript Core valida el acceso mediante dos middlewares complementarios:

1. **`VerifyOption` (`verify.option`):**
   - Intercepta la petición y verifica si el usuario autenticado tiene asignada la opción de menú asociada a la ruta en la sesión activa (`session('myoptions')`).
   - Si no está autorizado, aborta con código HTTP 403.
2. **`VerifyPermissionPosition` (`verify.position:N`):**
   - Valida si el usuario posee la posición específica (`1`, `2`, `3`, `4` o `5`) para el módulo en cuestión.
   - Ejemplo de uso en rutas modulares:
     ```php
     Route::post('/customers', [CustomerController::class, 'store'])
         ->middleware('verify.position:1')
         ->name('customers.store');
     ```

---

## 5. Construcción de la Navegación Dinámica

El menú lateral (sidebar) del panel de administración (`resources/views/layouts/admin.blade.php`) se renderiza dinámicamente en tiempo de ejecución:
1. Durante el inicio de sesión (`AuthController@login`), el sistema consulta los módulos activos asignados al rol del usuario y los almacena en la sesión.
2. El layout itera sobre estas opciones ordenadas por el campo `order`, renderizando su icono oficial y generando la URL con el helper `route($option->route_name)`.
3. Al retirar o agregar módulos, el sidebar se actualiza automáticamente sin necesidad de editar plantillas Blade troncales.