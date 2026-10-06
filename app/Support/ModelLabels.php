<?php

namespace App\Support;

use Filament\Facades\Filament;

/**
 * Nombre en lenguaje de usuario de cada modelo, tomado de la etiqueta del
 * recurso Filament que lo gestiona en cualquiera de los paneles: así "Gender"
 * se muestra como "Género" sin mantener una traducción aparte.
 */
class ModelLabels
{
    /** @var array<class-string, string>|null */
    private static ?array $labels = null;

    public static function for(?string $modelClass): ?string
    {
        if (! $modelClass) {
            return null;
        }

        return static::all()[$modelClass] ?? null;
    }

    /**
     * @return array<class-string, string>
     */
    public static function all(): array
    {
        if (static::$labels !== null) {
            return static::$labels;
        }

        $labels = [];

        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getResources() as $resource) {
                $labels[$resource::getModel()] ??= mb_convert_case(mb_substr($resource::getModelLabel(), 0, 1), MB_CASE_UPPER)
                    .mb_substr($resource::getModelLabel(), 1);
            }
        }

        return static::$labels = $labels;
    }
}
