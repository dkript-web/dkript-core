<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with structural core data.
     * Demo data requires explicit execution: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call([
            CoreSeeder::class,
        ]);
    }
}
