<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EmprendedorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('emprendedor')
            ->path('emprendedor')
            ->login()
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('100%')
            ->favicon(asset('favicon.png'))
            ->authGuard('entrepreneur')
            ->brandName('Portal Emprendedor')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                'panels::body.start',
                fn (): string => '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>',
            )
            ->discoverResources(in: app_path('Filament/Emprendedor/Resources'), for: 'App\\Filament\\Emprendedor\\Resources')
            ->discoverPages(in: app_path('Filament/Emprendedor/Pages'), for: 'App\\Filament\\Emprendedor\\Pages')
            ->pages([
                Pages\Dashboard2::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Emprendedor/Widgets'), for: 'App\\Filament\\Emprendedor\\Widgets')
            ->widgets([])
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
            ]);
    }
}
