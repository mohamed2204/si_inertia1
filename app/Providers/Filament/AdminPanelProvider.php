<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\RoadmapWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;


class AdminPanelProvider extends PanelProvider
{
    /**
     * @throws \Exception
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('SI')
            ->default()
            ->authGuard('web') // Vérifiez que c'est bien 'web'
            ->id('admin')
            ->path('si')
            ->login()
            ->sidebarCollapsibleOnDesktop() // permet la réduction
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
//            ->resources([
//                \App\Filament\Resources\DepartementResource::class,
//                \App\Filament\Resources\SousDepartementResource::class,
//            ])
            ->pages([
//                \App\Filament\Pages\ImportData::class, // Ajoutez cette ligne explicitement
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
//                Widgets\AccountWidget::class,
//                Widgets\FilamentInfoWidget::class,
//                RoadmapWidget::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    // ->scheduler(false) // Mettez à true si vous avez une licence FullCalendar Premium
                    ->selectable(true)
                    ->editable(true),
                FilamentShieldPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::head.done',
                fn(): string => Blade::render('
                <style>
                    .fi-no-notification-container {
                        position: fixed !important;
                        inset: 0 !important; /* Prend tout l\'écran */
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        pointer-events: none; /* Laisse cliquer derrière sauf sur la notification */
                        z-index: 9999;
                    }
                    .fi-no-notification {
                        pointer-events: auto; /* Rend la notification cliquable */
                        width: 400px !important;
                        max-width: 90vw;
                        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
                        border: 1px solid rgba(0,0,0,0.1) !important;
                        padding: 1.5rem !important;
                    }
                </style>
            '),
            );
    }

    public function boot(): void
    {
        FilamentAsset::register([
            Css::make(
                'custom-stylesheet',
                asset('css/filament-custom.css')
            )
        ]);
        FilamentView::registerRenderHook(
            'panels::notifications.before',
            fn(): string => '<div id="notification-overlay" style="position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:9998; display:none;"></div>',
        );
    }
}
