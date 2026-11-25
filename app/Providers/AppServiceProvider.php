<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Entrepreneur;
use App\Observers\EntrepreneurObserver;

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
    public function boot()
    {
        Entrepreneur::observe(EntrepreneurObserver::class);

        // Disco local cuyas URLs llevan la ruta cifrada (ver ProtectedPublicDisk).
        \Illuminate\Support\Facades\Storage::extend('protected-local', function ($app, array $config) {
            $local = $app['filesystem']->createLocalDriver($config);

            return new \App\Support\ProtectedPublicDisk($local->getDriver(), $local->getAdapter(), $config);
        });

        // El registro de actividad toma el autor del guard del panel en curso;
        // por defecto usaría siempre `web` y las acciones de los emprendedores
        // quedarían sin autor.
        // Registra los cambios de checklists y selects múltiples (tablas pivote).
        \App\Support\RelationAudit::registerMacro();

        \Spatie\Activitylog\Facades\CauserResolver::resolveUsing(
            fn ($subject = null) => $subject instanceof \Illuminate\Database\Eloquent\Model
                ? $subject
                : \App\Support\ActivityContext::causer()
        );

        // Botones de crear de todos los paneles: "+" y solo el nombre del
        // registro (por ejemplo, "+ Canvas"). Va como configuración importante
        // porque Filament pone su "Crear …" después de las configuraciones normales.
        foreach ([\Filament\Actions\CreateAction::class, \Filament\Tables\Actions\CreateAction::class] as $createAction) {
            $createAction::configureUsing(
                fn ($action) => $action
                    ->icon('heroicon-o-plus')
                    ->label(fn () => \Illuminate\Support\Str::ucfirst($action->getModelLabel())),
                isImportant: true,
            );
        }
    }
}
