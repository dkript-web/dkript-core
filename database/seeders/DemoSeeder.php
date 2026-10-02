<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Profile;

class DemoSeeder extends Seeder
{
    /**
     * Seed demonstration users and sample profiles.
     * Intended exclusively for local development and test environments.
     * NEVER executed by dkript:install in production.
     */
    public function run(): void
    {
        // 1. Super Administrador de Demostración
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@dkript.com'],
            [
                'name' => 'Admin Dkript',
                'password' => Hash::make('admin123'),
                'status' => 1,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $adminUser->id],
            [
                'role_id' => 1,
                'first_name' => 'Administrador',
                'last_name' => 'Dkript',
                'phone' => '555-0100',
                'avatar' => 'assets/images/branding/logo-dkript-profile.png',
            ]
        );

        // 2. Usuario Operador / Regular de Demostración
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@dkript.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password123'),
                'status' => 1,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $demoUser->id],
            [
                'role_id' => 2,
                'first_name' => 'Demo',
                'last_name' => 'Operador',
                'phone' => '555-0101',
                'avatar' => 'assets/images/branding/icon-drypt-front.png',
            ]
        );
    }
}
