<?php

namespace App\Support;

/**
 * Comprobación de que un par de coordenadas cae dentro del territorio
 * colombiano, usando cajas delimitadoras (bounding boxes).
 *
 * Se usan varias cajas en vez de una sola porque una caja única que cubriera
 * el continente y las islas incluiría medio mar Caribe y buena parte del
 * Pacífico. Separándolas se reducen mucho los falsos positivos.
 *
 * Alcance: descarta coordenadas claramente fuera del país (errores de signo,
 * latitud y longitud intercambiadas, valores de otro continente). No distingue
 * los territorios vecinos que quedan dentro de la caja continental
 * (Venezuela, Brasil, Perú, Ecuador y Panamá comparten frontera). Para eso
 * haría falta el polígono real de la frontera; ver nota en el README del
 * módulo.
 */
class ColombiaBounds
{
    /**
     * Cada caja es [latMin, latMax, lngMin, lngMax].
     *
     * Los extremos continentales son: Punta Gallinas al norte (12.46°N),
     * la quebrada San Antonio al sur (4.23°S), la isla San José sobre el
     * río Negro al este (66.85°O) y Cabo Manglares al oeste (79.04°O).
     * Se añade un margen de 0.1° para no rechazar puntos legítimos de
     * frontera por redondeo.
     *
     * @var array<string, array{float, float, float, float}>
     */
    protected const BOXES = [
        // Territorio continental.
        'continental' => [-4.33, 12.57, -79.15, -66.75],
        // Archipiélago de San Andrés, Providencia y Santa Catalina.
        'san_andres'  => [12.40, 13.45, -81.80, -81.30],
        // Isla de Malpelo.
        'malpelo'     => [3.90, 4.10, -81.70, -81.50],
        // Isla Gorgona queda dentro de la caja continental.
    ];

    public static function contains(float $latitude, float $longitude): bool
    {
        foreach (self::BOXES as [$latMin, $latMax, $lngMin, $lngMax]) {
            if ($latitude >= $latMin && $latitude <= $latMax
                && $longitude >= $lngMin && $longitude <= $lngMax) {
                return true;
            }
        }

        return false;
    }

    /** Rango global de latitud, para acotar el input en el navegador. */
    public static function latitudeRange(): array
    {
        return [-4.33, 13.45];
    }

    /** Rango global de longitud, para acotar el input en el navegador. */
    public static function longitudeRange(): array
    {
        return [-81.80, -66.75];
    }
}
