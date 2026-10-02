<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\MenuOption;
use App\Models\Permission;
use App\Models\Parameter;
use App\Models\SystemImage;

class CoreSeeder extends Seeder
{
    /**
     * Seed structural data required for Dkript Core to operate.
     * Contains no demo users, fictive data or sensitive sample credentials.
     */
    public function run(): void
    {
        // 1. Parámetros Estructurales del Sistema
        Parameter::firstOrCreate(
            ['id' => 1],
            [
                'system_name' => config('app.name', 'Dkript Core'),
                'system_logo' => config('dkript.branding.default_logo', null),
                'show_brand_text' => config('dkript.branding.show_brand_text', true),
                'contact_email' => config('dkript.branding.default_contact_email', null),
                'records_per_page' => 10,
                'session_timeout_minutes' => 15,
                'maintenance_mode' => 0,
                'modal_style' => 'corporate',
                'error_display_mode' => 'scene',
                'sms_provider' => 'log',
                'mail_mailer' => 'log',
                'mail_host' => '127.0.0.1',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
                'mail_from_address' => config('mail.from.address', 'hello@example.com'),
                'mail_from_name' => config('app.name', 'Dkript Core'),
                'installed_at' => null,
            ]
        );

        // 2. Roles Estructurales Base
        $superAdminRole = Role::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Super Administrador',
                'description' => 'Acceso absoluto automático a todos los módulos y permisos del sistema.',
                'status' => 1,
            ]
        );

        $operatorRole = Role::firstOrCreate(
            ['id' => 2],
            [
                'name' => 'Operador / Usuario Regular',
                'description' => 'Acceso limitado de solo lectura y consulta para operaciones cotidianas.',
                'status' => 1,
            ]
        );

        // 3. Opciones de Menú Estructurales (Navegación RBAC)
        $menuData = [
            [
                'id' => 1,
                'name' => 'Dashboard',
                'icon' => 'bi-speedometer2',
                'route_name' => 'dashboard',
                'order' => 1,
                'status' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Usuarios',
                'icon' => 'bi-people-fill',
                'route_name' => 'users.index',
                'order' => 2,
                'status' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Roles',
                'icon' => 'bi-person-badge-fill',
                'route_name' => 'roles.index',
                'order' => 3,
                'status' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Permisos',
                'icon' => 'bi-key-fill',
                'route_name' => 'permissions.index',
                'order' => 4,
                'status' => 1,
            ],
            [
                'id' => 5,
                'name' => 'Parámetros del Sistema',
                'icon' => 'bi-sliders',
                'route_name' => 'parameters.index',
                'order' => 5,
                'status' => 1,
            ],
            [
                'id' => 6,
                'name' => 'Auditoría',
                'icon' => 'bi-journal-check',
                'route_name' => 'audit-logs.index',
                'order' => 6,
                'status' => 1,
            ],
        ];

        $allPermissionIds = [];
        $operatorPermissionIds = [];

        foreach ($menuData as $menu) {
            $option = MenuOption::firstOrCreate(['id' => $menu['id']], $menu);

            // Regla Inviolable RBAC: Cada módulo tiene 5 posiciones fijas de permisos
            $standardPermissions = [
                ['position' => 1, 'name' => 'Crear ' . $menu['name']],
                ['position' => 2, 'name' => 'Editar ' . $menu['name']],
                ['position' => 3, 'name' => 'Eliminar ' . $menu['name']],
                ['position' => 4, 'name' => 'Ver / PDF ' . $menu['name']],
                ['position' => 5, 'name' => 'Especial / Excel ' . $menu['name']],
            ];

            foreach ($standardPermissions as $p) {
                $perm = Permission::firstOrCreate(
                    [
                        'menu_option_id' => $option->id,
                        'position' => $p['position'],
                    ],
                    [
                        'name' => $p['name'],
                        'status' => 1,
                    ]
                );

                $allPermissionIds[] = $perm->id;

                // Para operador asignamos solo Ver (posición 4) en Dashboard y Usuarios
                if (in_array($option->id, [1, 2]) && $p['position'] == 4) {
                    $operatorPermissionIds[] = $perm->id;
                }
            }
        }

        // 4. Asignar módulos y permisos a Roles
        $allOptionIds = MenuOption::pluck('id')->toArray();
        $superAdminRole->menuOptions()->sync($allOptionIds);
        $superAdminRole->permissions()->sync($allPermissionIds);

        $operatorRole->menuOptions()->sync([1, 2]);
        $operatorRole->permissions()->sync($operatorPermissionIds);

        // 5. Poblar Catálogo Inicial de Imágenes Corporativas Presets
        if (class_exists(SystemImage::class) && method_exists(SystemImage::class, 'defaultPresets')) {
            foreach (SystemImage::defaultPresets() as $img) {
                SystemImage::firstOrCreate(
                    ['path' => $img['path']],
                    [
                        'name' => $img['name'],
                        'is_preset' => true,
                    ]
                );
            }
        }
    }
}
