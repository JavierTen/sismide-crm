<?php

namespace App\Support;

use Throwable;

/**
 * Deja constancia de cada descarga de datos.
 *
 * Exportar es la acción más sensible del sistema: saca de la plataforma datos
 * personales de estudiantes y emprendedores. No pasa por los eventos de los
 * modelos, así que cada punto de descarga debe llamar aquí explícitamente.
 *
 * Un fallo al registrar nunca debe impedir la descarga.
 */
class ExportLogger
{
    /**
     * @param  string  $what  Qué se exportó, en lenguaje de usuario: "Estudiantes".
     * @param  array<string, mixed>  $details  Archivo, registros, filtros, etc.
     */
    public static function log(string $what, array $details = []): void
    {
        try {
            $records = $details['records'] ?? null;

            $description = 'Exportó '.$what
                .(is_int($records) ? ' ('.number_format($records).' '.($records === 1 ? 'registro' : 'registros').')' : '');

            activity('export')
                ->event('exported')
                ->withProperties(array_filter(
                    ['what' => $what, ...$details],
                    fn ($value) => $value !== null && $value !== [] && $value !== '',
                ))
                ->log($description);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Deja solo los filtros con valor, para que el registro muestre con qué
     * criterio se hizo la exportación y no el estado vacío de cada filtro.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array<string, mixed>
     */
    public static function activeFilters(?array $filters): array
    {
        $clean = [];

        foreach ($filters ?? [] as $key => $value) {
            if (is_array($value)) {
                $value = static::activeFilters($value);
            }

            if ($value === null || $value === '' || $value === [] || $value === false) {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
