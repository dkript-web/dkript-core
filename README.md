<p align="center">
  <img src="public/assets/images/branding/logo-dkript.png" alt="Dkript Logo" width="180">
</p>

<h1 align="center">Dkript Core</h1>

<p align="center">
  <strong>Reusable Laravel application core and starter kit by Dkript.</strong>
</p>

<p align="center">
  <a href="#installation">Installation</a> ·
  <a href="#features">Features</a> ·
  <a href="#documentation">Documentation</a>
</p>

<p align="center">
  <a href="https://github.com/dkript-web/dkript-core/releases"><img src="https://img.shields.io/github/v/release/dkript-web/dkript-core" alt="Release"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/laravel-13.x-red.svg" alt="Laravel"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%5E8.3-indigo.svg" alt="PHP"></a>
  <a href="tests"><img src="https://img.shields.io/badge/tests-300%20passed-brightgreen.svg" alt="Tests"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License"></a>
</p>

---

## 📌 Overview

**Dkript Core** is a clean, robust, and modular base platform engineered for the enterprise application ecosystem of **Dkript Inc.**

> [!IMPORTANT]
> **Dkript Core is NOT an ERP, NOT a CRM, and NOT a finished end-user product.**  
> It is an advanced *Application Core & Starter Kit* designed to serve as the architectural foundation upon which to build SaaS platforms, corporate management portals, and custom web applications with strict governance, decoupled architecture, and high developer productivity.

---

## 📊 Project Status

- **Current Version:** `1.0.0`
- **Release Stage:** Official Stable Release (v1.0.0).
- **Quality Gate:** 300 automated tests, 1,496 assertions, 0 failures, 0 errors, 0 skipped tests.
- **Routing & Assets:** Route caching fully verified (`route:cache: OK`), production assets built cleanly (`npm run build: OK`).

---

## 🛠️ Technology Stack

| Component | Technology / Version | Description & Purpose |
| :--- | :--- | :--- |
| **Backend Language** | **PHP ^8.3** (Verified on PHP 8.4+) | Strict typing, native attributes, high performance. |
| **Backend Framework** | **Laravel Framework ^13.17** | MVC architecture, Service Container, Eloquent ORM. |
| **Styles & UI** | **Tailwind CSS v4** | Modern atomic utility-first styling framework. |
| **Frontend Bundler** | **Vite v8** | High-speed asset compilation for scripts and stylesheets. |
| **Data Visualization** | **D3.js v7 (RosenCharts)** | Native SVG analytical charts (Donut, Area, Line). |
| **Iconography** | **Bootstrap Icons v1.11** | Unified vector iconography for actions and navigation. |
| **PDF Engine** | **barryvdh/laravel-dompdf ^3.1** | Server-side vectorial PDF report generation. |
| **Database Support** | **MySQL 8.0+ / MariaDB / SQLite** | Relational database engines for production, development, and testing. |

---

## 🚀 Features

Dkript Core provides 21 foundational, battle-tested capabilities out of the box:

1. **Authentication:** Full authentication workflows, IP/credential-based rate limiting, "Remember Me", secure password resets, and account lockout protection.
2. **Users & Profiles:** Comprehensive user management (CRUD), avatars, active/inactive statuses, profile customization, and secure password updates.
3. **5-Position RBAC Matrix:** Granular permission system governed by the immutable standard:
   - `1 = Create` (Insert and duplicate)
   - `2 = Edit` (Update and modify)
   - `3 = Delete` (Soft or hard delete)
   - `4 = View / PDF` (Details view and PDF export)
   - `5 = Especial / Excel` (Privileged operations and Excel export)
4. **SuperAdmin Bypass:** Reserved administrative role (`id = 1`) with complete system access and emergency recovery privileges.
5. **Dynamic Navigation:** Left sidebar rendered dynamically based on active menu options (`menu_options`) and user permissions.
6. **Two-Factor Authentication (2FA):** Multi-factor security supporting TOTP apps, Email/SMS OTPs, and single-use emergency recovery codes.
7. **Session & Device Management:** Active session auditing, user-agent and IP fingerprinting, and remote termination of concurrent sessions.
8. **Inactivity Timeout (Idle Session Expiration):** Configurable client-side timer and server-side middleware ensuring clean session termination and CSRF token regeneration upon inactivity.
9. **Centralized Audit Logging (`AuditService`):** System-wide activity auditing (login, logout, model mutations, password changes) with automatic redaction of sensitive credentials.
10. **Notification Center:** Database-driven notification system (`notifications`) with interactive bell dropdown, unread counter, mark-as-read, and batch dismissal.
11. **Mail Service (`MailConfigService`):** Dynamic transactional email configuration loaded from the `parameters` table with graceful fallback to `config/mail.php`.
12. **Messaging Service (`MessagingService`):** Multi-channel notification dispatcher supporting `LogSimulator` (testing/local), `Twilio`, and `WhatsApp Cloud API`.
13. **Automated Backups (`BackupService`):** Manual and scheduled database dumps (`.sql` and compressed `.sql.gz`) for MySQL and SQLite with automated retention rotation.
14. **Safe Database Restoration (`BackupService`):** Database restore engine with mandatory pre-restore *Safety Backup* and concurrent lock protection (`storage/framework/dkript_restore.lock`).
15. **Centralized Parameters (`Parameter`):** Dynamic application configuration stored in the database as the single source of truth, accessible via strongly-typed getters.
16. **Branding Service (`BrandingService`):** Dynamic customization of application title, logos (light/dark), and favicon, keeping the core neutral while supporting company branding presets.
17. **Decoupled Error Media:** Polished HTTP error scenes (401, 403, 404, 419, 429, 500, 503) with graceful fallback: Custom Video → Custom Image → Brand Preset → Core Neutral.
18. **Application Maintenance Mode:** Granular access restriction via `Parameter.maintenance_mode` featuring custom downtime messaging and SuperAdmin bypass (independent of `php artisan down`).
19. **Native Export Engines:** Lightweight client-side Excel generation and server-side corporate PDF rendering.
20. **Module Generator (`dkript:make-module`):** Powerful CLI scaffolding tool that creates migrations, models, FormRequests, controllers, Blade views, policies, factories, tests, and modular routes with automatic RBAC and menu registration.
21. **Isolated Modular Routing:** Automatic loading pipeline in `bootstrap/app.php` that discovers `routes/modules/*.php` deterministically, keeping `routes/web.php` immutable and cache-friendly.

---

## ⚡ CLI Commands (`dkript:*`)

Dkript Core includes 3 purpose-built Artisan commands for system governance and development:

| Command | Purpose | Production Safe | Writes to DB | Modifies Files | Notes |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `php artisan dkript:install` | Assisted system setup: validates DB, runs migrations, seeds core catalogs, and creates initial SuperAdmin. | ⚠️ Initial only | Yes (Schema/Seeds) | Yes (`.env`) | Supports interactive and non-interactive execution (`--no-interaction`). Guarded by `installed_at`. |
| `php artisan dkript:make-module <Name>` | Scaffolds complete business modules (CRUD, RBAC, Views, FormRequests, Routes, Tests). | ⚠️ Dev/Staging | Yes (RBAC/Menu) | Yes (`app/`, `resources/`, `routes/modules/`, `database/`, `tests/`) | Registers menu options on the active database connection. Use `--dry-run` to preview. |
| `php artisan dkript:auto-backup` | Runs scheduled database backups according to frequency and retention policies configured in `parameters`. | Yes (Cron/Scheduler) | No | Yes (`storage/app/backups/`) | Designed to run automatically via Laravel Scheduler (`routes/console.php`). |

---

## Installation

### Create a new project

```bash
git clone https://github.com/dkript-web/dkript-core.git dkript-erp
cd dkript-erp

composer install
npm ci

# Windows:
copy .env.example .env

# Linux / macOS:
cp .env.example .env

php artisan dkript:install
npm run build
```

> Run these commands from the root directory of the new project.

---

## 📚 Documentation

Detailed architectural and operational documentation is available in the [`docs/`](docs/) directory:

- [**01. Installation & Requirements**](docs/01-INSTALLATION.md) — System prerequisites, interactive and non-interactive `dkript:install` workflows.
- [**02. Configuration & Parameters**](docs/02-CONFIGURATION.md) — Precedence hierarchy (`Parameter` vs `.env`), typed keys, and administrative categories.
- [**03. System Architecture**](docs/03-ARCHITECTURE.md) — Core vs business decoupling, lifecycle of modules, and service layers.
- [**04. Module Generator (`dkript:make-module`)**](docs/04-MODULE-GENERATOR.md) — Syntax guide, field types, array/JSON handling, `--dry-run`, and collision protection.
- [**05. RBAC & Dynamic Navigation**](docs/05-RBAC-AND-NAVIGATION.md) — 5-position permission matrix, relational schema, SuperAdmin bypass, and menu rendering.
- [**06. Automated Testing & Quality**](docs/06-TESTING.md) — In-memory SQLite isolation, running test suites, and environment protection rules.
- [**07. Backups & Restoration**](docs/07-BACKUPS-AND-RESTORE.md) — Backup engine, automated retention, mandatory Safety Backup, and concurrency lock.
- [**08. Security & Sessions**](docs/08-SECURITY.md) — Encryption, 2FA, idle session timeouts, audit log redaction, and rate limiting.
- [**09. Production Deployment**](docs/09-DEPLOYMENT.md) — Web server configuration, cache optimizations, SSL/HTTPS, and background workers.
- [**10. Troubleshooting Guide**](docs/10-TROUBLESHOOTING.md) — Known operational issues, root cause analyses, and safe resolution procedures.
- [**11. Creating a New Project**](docs/11-CREATE-A-NEW-DKRIPT-PROJECT.md) — Step-by-step workflow for bootstrapping a new derivative product using Dkript Core.

---

## 🧪 Automated Testing

Dkript Core features a comprehensive automated test suite verifying authentication, RBAC, core services, generator output, exports, and security hardening:

```bash
# Run the entire test suite
php artisan test

# Run specific feature tests
php artisan test --filter=MakeDkriptModuleTest
php artisan test --filter=RestoreBackupTest
```

---

## 🛡️ Security

- All user inputs mutating state are validated through strictly-typed `FormRequest` classes.
- Critical credentials and secrets are encrypted at rest using Laravel's `Crypt` service.
- Audit logs automatically mask sensitive parameters (`password`, `token`, `secret`, `api_key`).
- For security guidelines and vulnerability disclosures, refer to [`docs/08-SECURITY.md`](docs/08-SECURITY.md).

---

## Author

Developed and maintained by:

Joel Jonathan Perez Delgado  
Founder / Software Developer — Dkript Inc.

Dkript  
https://dkript.com

Professional Portfolio  
https://inteligenciadigital.mx

Contact  
dkript@outlook.com

---

## License

MIT License  
Copyright (c) 2026 Dkript Inc.