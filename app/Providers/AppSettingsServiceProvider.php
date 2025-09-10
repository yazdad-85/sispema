<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use App\Models\AppSetting;

class AppSettingsServiceProvider extends ServiceProvider
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
        // Share app settings to all views
        View::composer('*', function ($view) {
            $appSettings = AppSetting::getAll();
            $view->with('appSettings', $appSettings);
        });

        // Create Blade directives for app settings
        Blade::directive('appName', function () {
            return "<?php echo \App\Models\AppSetting::getValue('app_name', 'SISPEMA YASMU'); ?>";
        });

        Blade::directive('appDescription', function () {
            return "<?php echo \App\Models\AppSetting::getValue('app_description', 'Sistem Pembayaran Akademik Yayasan Mu\'allimin Mu\'allimat YASMU'); ?>";
        });

        Blade::directive('appCity', function () {
            return "<?php echo \App\Models\AppSetting::getValue('app_city', 'Kota'); ?>";
        });
    }
}
