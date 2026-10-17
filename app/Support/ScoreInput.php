<?php

namespace App\Support;

/**
 * Casilla de calificación que no deja escribir un valor fuera de la escala:
 * si se escribe o pega un número mayor al máximo queda en el máximo, se limita
 * la cantidad de decimales y se bloquean "e", "+" y "-" (que el navegador sí
 * acepta en los campos numéricos). La validación del servidor se mantiene.
 */
class ScoreInput
{
    /** @return array<string, string> atributos para extraInputAttributes() */
    public static function attributes(int|float $max = 10, int $decimals = 1): array
    {
        return [
            'x-on:keydown' => "if (['e', 'E', '+', '-'].includes(\$event.key)) \$event.preventDefault()",
            'x-on:input'   => "
                let value = \$el.value;
                if (value === '') return;
                let fixed = value;
                if (parseFloat(value) > {$max}) fixed = '{$max}';
                else if ({$decimals} >= 0 && value.includes('.') && value.split('.')[1].length > {$decimals}) fixed = value.slice(0, value.indexOf('.') + " . ($decimals > 0 ? $decimals + 1 : 0) . ");
                if (fixed !== value) { \$el.value = fixed; \$el.dispatchEvent(new Event('input')); }
            ",
            'inputmode' => 'decimal',
        ];
    }
}
