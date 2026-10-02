<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Escudo de Micro-Ingeniería Eloquent: Detección estricta de N+1 (Anti-Lazy Loading)
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(! $this->app->isProduction());

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $parameter = null;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('parameters')) {
                    $parameter = \App\Models\Parameter::getSystemSettings();
                }
            } catch (\Throwable $e) {
                // Ignore DB connection errors during console/migrations
            }

            if (!$parameter) {
                $parameter = new \App\Models\Parameter([
                    'system_name' => config('app.name', 'Dkript Core'),
                    'contact_email' => config('dkript.branding.default_contact_email', null),
                    'records_per_page' => 10,
                    'maintenance_mode' => false,
                    'modal_style' => 'corporate',
                    'error_display_mode' => 'scene',
                    'system_logo' => config('dkript.branding.default_logo', null),
                    'show_brand_text' => config('dkript.branding.show_brand_text', true),
                ]);
            }

            $view->with('globalSystemParameter', $parameter);
        });
    }
}
