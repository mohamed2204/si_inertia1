<?php

namespace App\Providers;

use Filament\PluginServiceProvider;
use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;;

class FilamentServiceProvider extends ServiceProvider
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
        // Register global JS
        // Filament::registerScripts([
        //     asset('js/arabic-keyboard.js'),
        // ]);
    }
}
