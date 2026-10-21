<?php

namespace App\Support\ClauseExport;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Form;
use Throwable;

/**
 * Etiquetas de las opciones de un campo, leídas del formulario Filament del
 * recurso que lo gestiona. Así el Excel muestra "Menos de 6 meses" y no
 * "lt_6_months", con exactamente las mismas palabras que ve el usuario en
 * pantalla, sin mantener una copia aparte de cada lista.
 */
class FormOptions
{
    /** @var array<class-string, array<string, array<string, string>>> */
    private static array $cache = [];

    /**
     * @param  class-string  $resource
     * @return array<string, string> valor => etiqueta
     */
    public static function for(string $resource, string $field): array
    {
        return static::all($resource)[$field] ?? [];
    }

    /**
     * @param  class-string  $resource
     * @return array<string, array<string, string>>
     */
    public static function all(string $resource): array
    {
        if (isset(static::$cache[$resource])) {
            return static::$cache[$resource];
        }

        $options = [];

        try {
            $page = $resource::getPages()['create'] ?? $resource::getPages()['index'] ?? null;
            $form = $resource::form(Form::make(new ($page->getPage())()));

            static::collect($form->getComponents(), $options);
        } catch (Throwable $exception) {
            report($exception);
        }

        return static::$cache[$resource] = $options;
    }

    /**
     * @param  array<Component>  $components
     * @param  array<string, array<string, string>>  $options
     */
    private static function collect(array $components, array &$options): void
    {
        foreach ($components as $component) {
            if ($component instanceof Field && method_exists($component, 'getOptions')) {
                try {
                    $list = $component->getOptions();

                    if (is_array($list) && $list !== []) {
                        $options[$component->getName()] = array_map('strval', $list);
                    }
                } catch (Throwable) {
                    // Opciones que dependen del estado del formulario: no aplican aquí.
                }
            }

            if (method_exists($component, 'getChildComponents')) {
                static::collect($component->getChildComponents(), $options);
            }
        }
    }

    /** Traduce un valor o una lista de valores a sus etiquetas. */
    public static function label(array $options, mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($item) => $options[(string) $item] ?? (string) $item)->implode(', ');
        }

        return $options[(string) $value] ?? (string) $value;
    }
}
