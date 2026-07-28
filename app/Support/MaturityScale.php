<?php

namespace App\Support;

class MaturityScale
{
    /**
     * Escala de niveles de madurez según el año del diagnóstico.
     * A partir de 2026 se aplica la nueva escala; años anteriores mantienen la escala original.
     */
    public static function getScale(int $year): array
    {
        if ($year >= 2026) {
            return [
                ['level' => 0, 'name' => 'Potencial emprendedor',     'label' => 'Nivel 0: Potencial emprendedor',     'min' => 0,  'max' => 10,  'color' => '#6B7280'],
                ['level' => 1, 'name' => 'Ideación',                  'label' => 'Nivel 1: Ideación',                  'min' => 11, 'max' => 20,  'color' => '#EC4899'],
                ['level' => 2, 'name' => 'Validación',                'label' => 'Nivel 2: Validación',                'min' => 21, 'max' => 30,  'color' => '#F59E0B'],
                ['level' => 3, 'name' => 'Puesta en Marcha',          'label' => 'Nivel 3: Puesta en Marcha',          'min' => 31, 'max' => 45,  'color' => '#3B82F6'],
                ['level' => 4, 'name' => 'Consolidación',             'label' => 'Nivel 4: Consolidación',             'min' => 46, 'max' => 60,  'color' => '#F97316'],
                ['level' => 5, 'name' => 'Escalamiento e Innovación', 'label' => 'Nivel 5: Escalamiento e Innovación', 'min' => 61, 'max' => 100, 'color' => '#8B5CF6'],
            ];
        }

        return [
            ['level' => 0, 'name' => 'Pre-emprendimiento y validación temprana', 'label' => 'Nivel 0: Pre-emprendimiento y validación temprana', 'min' => 0,  'max' => 15,  'color' => '#6B7280'],
            ['level' => 1, 'name' => 'Pre-emprendimiento y validación temprana', 'label' => 'Nivel 1: Pre-emprendimiento y validación temprana', 'min' => 16, 'max' => 30,  'color' => '#EC4899'],
            ['level' => 2, 'name' => 'Pre-emprendimiento y validación temprana', 'label' => 'Nivel 2: Pre-emprendimiento y validación temprana', 'min' => 31, 'max' => 50,  'color' => '#F59E0B'],
            ['level' => 3, 'name' => 'Consolidación',                            'label' => 'Nivel 3: Consolidación',                            'min' => 51, 'max' => 70,  'color' => '#3B82F6'],
            ['level' => 4, 'name' => 'Consolidación',                            'label' => 'Nivel 4: Consolidación',                            'min' => 71, 'max' => 85,  'color' => '#F97316'],
            ['level' => 5, 'name' => 'Escalamiento e Innovación',                'label' => 'Nivel 5: Escalamiento e Innovación',                'min' => 86, 'max' => 100, 'color' => '#8B5CF6'],
        ];
    }

    public static function getLevelForScore(int $score, int $year): array
    {
        foreach (self::getScale($year) as $level) {
            if ($score >= $level['min'] && $score <= $level['max']) {
                return $level;
            }
        }

        return self::getScale($year)[0];
    }

    /**
     * Rangos de fases (agrupaciones de niveles) según año.
     * Fase 1 = Niveles 0,1,2 — Fase 2 = Niveles 3,4 — Fase 3 = Nivel 5
     */
    public static function getPhaseRanges(int $year): array
    {
        if ($year >= 2026) {
            return [
                1 => ['label' => 'Fase 1: Nivel 0,1,2 - Pre-emprendimiento', 'min' => 0,  'max' => 30],
                2 => ['label' => 'Fase 2: Nivel 3,4 - Consolidación',         'min' => 31, 'max' => 60],
                3 => ['label' => 'Fase 3: Nivel 5 - Escalamiento',            'min' => 61, 'max' => 100],
            ];
        }

        return [
            1 => ['label' => 'Fase 1: Nivel 0,1,2 - Pre-emprendimiento', 'min' => 0,  'max' => 50],
            2 => ['label' => 'Fase 2: Nivel 3,4 - Consolidación',         'min' => 51, 'max' => 85],
            3 => ['label' => 'Fase 3: Nivel 5 - Escalamiento',            'min' => 86, 'max' => 100],
        ];
    }
}
