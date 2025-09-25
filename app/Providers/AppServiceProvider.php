<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Blade;

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
        Filament::serving(function () {
            Filament::registerRenderHook(
                'panels::body.end',
                fn(): string => Blade::render('
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        // Buscar el campo de contraseña
                        const passwordInput = document.querySelector("input[name=\'password\']");
                        if (passwordInput) {
                            // Buscar el contenedor del campo
                            const fieldWrapper = passwordInput.closest(".fi-input-wrp") || passwordInput.parentElement;
                            if (fieldWrapper) {
                                // Crear el texto de ayuda
                                const helpText = document.createElement("div");
                                helpText.className = "text-sm text-gray-500 mt-1";
                                helpText.innerHTML = "¿Olvidaste tu contraseña? Comunícate con el Administrador de Sistemas";

                                // Agregarlo después del campo
                                fieldWrapper.parentElement.appendChild(helpText);
                            }
                        }
                    });
                </script>
            ')
            );
        });
    }
}
