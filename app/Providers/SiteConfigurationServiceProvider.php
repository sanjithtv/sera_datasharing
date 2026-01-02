<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SiteConfiguration;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class SiteConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            $config = SiteConfiguration::first();

            View::share('siteConfig', $config);

            if ($config?->app_title) {
                config(['app.name' => $config->app_title]);
            }
        } catch (\Exception $e) {
            // Prevent crash during migrations
        }
    }
}
