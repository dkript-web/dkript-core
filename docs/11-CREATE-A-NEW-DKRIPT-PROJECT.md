# 🏗️ Guía Maestra: Creación de un Nuevo Proyecto Derivado — Dkript Core

**Versión del Core:** `0.1.0-dev`  
**Gobernanza:** Dkript Inc.  
**Propósito:** Manual operativo oficial para desarrolladores e ingenieros que van a iniciar un nuevo producto, plataforma SaaS o sistema empresarial utilizando Dkript Core como base arquitectónica.

---

## 1. Principios del Modelo de Proyecto Derivado

1. **Independencia de Repositorio:** El nuevo proyecto debe nacer con su propio repositorio y su propio historial de control de versiones. **Queda estrictamente prohibido copiar la carpeta `.git` de Dkript Core** en el repositorio del nuevo proyecto.
2. **Separación de Responsabilidades (Core vs Demo):** Dkript Core es el motor y starter kit reutilizable. Los datos de prueba de la demo oficial (`DemoSeeder`) no forman parte del núcleo productivo y nunca deben sembrarse en entornos reales.
3. **Identidad de Marca:** Todo nuevo proyecto puede operar bajo la identidad neutra del Core, bajo el preset oficial de Dkript Inc. o bajo la identidad gráfica personalizada del cliente o producto.

---

## 2. Flujo Operativo Paso a Paso

### Paso 1: Obtener el Código Base del Core
Dkript Core puede obtenerse mediante dos fuentes aprobadas:

```bash
# Fuente A: Repositorio oficial publicado (cuando se libere públicamente)
git clone <URL-OFICIAL-DKRIPT-CORE> mi-nuevo-proyecto
cd mi-nuevo-proyecto

# Fuente B: Copia limpia local pre-publicación (Utilizada para validación y Paso 1.11)
# Copiar la carpeta completa de dkript-core a un nuevo directorio mi-nuevo-proyecto.
# IMPORTANTE (Gobernanza H-02): El directorio public/build/ debe excluirse DURANTE la copia,
# no copiarse y eliminarse a posteriori como mecanismo principal. La verificación inicial del
# laboratorio o proyecto derivado debe constatar su ausencia previa a la compilación frontend.
# Elementos y directorios a excluir estrictamente durante la copia:
# - .git/
# - .env
# - vendor/
# - node_modules/
# - public/build/ (excluir en origen durante la copia)
# - storage/app/backups/*
# - storage/logs/*
# - storage/framework/cache/*
# - storage/framework/sessions/*
# - storage/framework/views/*
# (conservando los subdirectorios y estructura base de storage/)
```

---

### Paso 2: Desvincular el Historial de Git del Core e Iniciar Nuevo Repositorio
Para garantizar la total independencia del producto derivado, elimine el directorio `.git` heredado (si provino de un clone) e inicialice el control de versiones propio de su proyecto:

```bash
# 1. Eliminar historial anterior (si existiera .git heredado)
# En Windows PowerShell:
Remove-Item -Force -Recurse .git

# En Linux / macOS:
rm -rf .git

# 2. Inicializar repositorio limpio para el nuevo producto derivado
git init
git branch -M main
```

> [!WARNING]
> **Aviso de Gobernanza:** Esta instrucción aplica exclusivamente a la creación de un **nuevo producto derivado**. En el repositorio central de desarrollo de Dkript Core (`dkript-core`) no se debe ejecutar `git init`, `git commit`, `git push` ni alterar remotos durante las fases de estabilización pre-1.0.

---

### Paso 3: Configurar el Archivo de Entorno (`.env`)
Copie la plantilla de variables de entorno y ajuste los parámetros de su nuevo sistema:

```bash
cp .env.example .env
```

Edite `.env` con los datos reales de su proyecto:
```ini
APP_NAME="Acme ERP Enterprise"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=acme_erp_db
DB_USERNAME=root
DB_PASSWORD=mi_password_local
```

---

### Paso 4: Crear la Base de Datos en MySQL
Asegúrese de crear la base de datos limpia en su servidor MySQL con codificación UTF-8 multibyte:

```sql
CREATE DATABASE acme_erp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

### Paso 5: Instalar Dependencias del Proyecto
```bash
# 1. Dependencias PHP
composer install

# 2. Dependencias de Frontend (Instalación reproducible a partir de package-lock.json)
npm ci
```

---

### Paso 6: Ejecutar el Instalador Oficial (`dkript:install`)
Ejecute el asistente de instalación oficial:

```bash
php artisan dkript:install
```

El instalador ejecutará de forma automática y controlada:
1. Generación de `APP_KEY` segura.
2. Verificación de la conexión a `acme_erp_db`.
3. Ejecución de las migraciones base (`php artisan migrate --force`).
4. Siembra de datos estructurales obligatorios (`CoreSeeder`).
5. Registro interactivo de su cuenta inicial como **Super Administrador**.

> **Nota:** `CoreSeeder` solo crea los parámetros estructurales, los 2 roles base (`Super Administrador` y `Operador`) y las 6 opciones troncales del panel con sus permisos de 5 posiciones. No genera usuarios demo ni credenciales ficticias.

---

### Paso 7: Configuración de Branding e Identidad
Acceda al panel con sus credenciales de Super Administrador en [http://localhost:8000/login](http://localhost:8000/login) y diríjase al módulo **Parámetros del Sistema** (`/parameters`):

1. **Modo Neutro del Core:** Permite usar el sistema sin logotipos de Dkript ni referencias a la marca comercial.
2. **Preset Oficial Dkript:** Configura el logotipo de Dkript Inc. y la mascota asistente Drypt Alchemist para pantallas de error.
3. **Identidad Personalizada del Proyecto:** Suba el logotipo de su producto o cliente, configure el correo de soporte corporativo y personalice los mensajes del sistema.

---

### Paso 8: Generar su Primer Módulo de Negocio
Utilice el generador modular de Dkript Core para crear las entidades propias de su aplicación:

```bash
php artisan dkript:make-module Customer \
  --fields="name:string,tax_id:string(20):unique,email:string:nullable,phone:string(30):nullable,is_active:boolean:default(1),metadata:json:nullable" \
  --icon="bi-people-fill"
```

El generador creará automáticamente:
- Migración en `database/migrations/`.
- Modelo `App\Models\Customer` con casts y fillable.
- FormRequests `StoreCustomerRequest` y `UpdateCustomerRequest`.
- Controlador `CustomerController` con CRUD, exportación Excel y PDF.
- Policy de autorización `CustomerPolicy` con la regla de 5 posiciones.
- Vistas Blade y vista de impresión PDF.
- Archivo de rutas modulares en `routes/modules/customers.php`.
- Opción en `menu_options` con sus 5 permisos RBAC vinculados al Super Administrador.
- Feature Test automatizado en `tests/Feature/CustomerTest.php`.

---

### Paso 9: Aplicar la Migración del Módulo
Recuerde que el generador **no aplica la migración automáticamente** para permitir revisiones previas:

```bash
php artisan migrate
```

---

### Paso 10: Validación de Calidad y Suite de Pruebas
Valide que su nuevo módulo y la plataforma pasen exitosamente la suite de pruebas automatizadas:

```bash
# 1. Probar el módulo recién creado:
php artisan test tests/Feature/CustomerTest.php

# 2. Ejecutar la suite completa del proyecto:
php artisan test

# 3. Compilar los activos finales para producción:
npm run build
```

---

## 3. Resumen de Gobernanza para el Equipo de Desarrollo

- **No altere archivos troncales:** No agregue rutas de negocio a `routes/web.php`. Todo módulo debe residir en `routes/modules/`.
- **CSS y JavaScript:** Todo estilo personalizado debe agregarse a `public/assets/css/custom.css` y toda lógica modular a `public/assets/js/custom.js`.
- **Regla de 5 Posiciones:** Ninguna acción de negocio debe saltarse la matriz RBAC de 5 posiciones (Crear, Editar, Eliminar, Ver/PDF, Especial/Excel).
- **Quality Gate:** Ningún commit debe ingresar a la rama principal de su proyecto si la suite de pruebas reporta fallos.