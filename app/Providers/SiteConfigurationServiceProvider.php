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
                // Fetch configs as key => value array
            $configs = SiteConfiguration::query()
                ->pluck('value', 'key')
                ->toArray();

            // Convert to object for blade friendliness
            $siteConfig = (object) $configs;

            // Share with all views
            View::share('siteConfig', $siteConfig);

            // Apply default language
            if (!empty($siteConfig->default_language)) {
                app()->setLocale($siteConfig->default_language);
                // Persist to session if not already set
                if (!session()->has('lang')) {
                    session(['lang' => $siteConfig->default_language]);
                }
            }
        } catch (\Exception $e) {
            // Prevent crash during migrations
        }
    }
}
