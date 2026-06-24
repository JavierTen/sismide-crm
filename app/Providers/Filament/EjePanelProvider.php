<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EjePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('eje')
            ->path('eje')
            ->login()
            ->brandName('Proyecto Eje')
            ->brandLogo(asset('img/logo.png'))
            ->brandLogoHeight('100%')
            ->favicon(asset('favicon.png'))
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Eje/Resources'), for: 'App\\Filament\\Eje\\Resources')
            ->discoverPages(in: app_path('Filament/Eje/Pages'), for: 'App\\Filament\\Eje\\Pages')
            ->discoverWidgets(in: app_path('Filament/Eje/Widgets'), for: 'App\\Filament\\Eje\\Widgets')
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
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Comunidad Educativa')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Caracterizaciones')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Evaluaciones')
                    ->collapsible(),
            ]);
    }
}
