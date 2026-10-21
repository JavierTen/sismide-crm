<?php

namespace App\Support\ClauseExport;

use App\Models\City;

/**
 * Cláusulas de Ruta D Magdalena y los municipios que agrupa cada una.
 */
class Clauses
{
    public const LA_LOMA   = 'la_loma';
    public const EL_COROZO = 'el_corozo';
    public const BOTH      = 'both';

    /** @var array<string, array{name: string, cities: array<int>}> */
    public const DEFINITIONS = [
        self::LA_LOMA   => ['name' => 'La Loma',   'cities' => [219, 1096]],     // Ciénaga, Zona Bananera
        self::EL_COROZO => ['name' => 'El Corozo', 'cities' => [21, 352, 49]],   // Algarrobo, Fundación, Aracataca
    ];

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::LA_LOMA   => 'Cláusula La Loma',
            self::EL_COROZO => 'Cláusula El Corozo',
            self::BOTH      => 'Ambas cláusulas',
        ];
    }

    /** @return array<int> */
    public static function cityIds(string $clause): array
    {
        if ($clause === self::BOTH) {
            return array_merge(...array_column(self::DEFINITIONS, 'cities'));
        }

        return self::DEFINITIONS[$clause]['cities'] ?? [];
    }

    /** Nombre de la cláusula a la que pertenece un municipio. */
    public static function nameForCity(?int $cityId): string
    {
        foreach (self::DEFINITIONS as $definition) {
            if (in_array($cityId, $definition['cities'], true)) {
                return $definition['name'];
            }
        }

        return '—';
    }

    /** @return array<int, string> id => nombre, en el orden de la cláusula */
    public static function cities(string $clause): array
    {
        $ids   = static::cityIds($clause);
        $names = City::whereIn('id', $ids)->pluck('name', 'id');

        return collect($ids)->mapWithKeys(fn (int $id) => [$id => $names[$id] ?? "#{$id}"])->all();
    }
}
