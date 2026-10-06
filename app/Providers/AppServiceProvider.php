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
    }
}
