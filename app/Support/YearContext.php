<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class YearContext
{
    public const SESSION_KEY = 'selected_year';

    public const ALL_YEARS = 'all';

    /**
     * Tables scoped by year, all keyed off their own created_at.
     */
    protected const YEAR_COLUMNS = [
        'entrepreneurs' => 'created_at',
        'businesses' => 'created_at',
        'visits' => 'created_at',
        'characterizations' => 'created_at',
        'business_diagnoses' => 'created_at',
        'business_plans' => 'created_at',
        'business_plan_evaluations' => 'created_at',
        'trainings' => 'created_at',
        'training_participations' => 'created_at',
        'training_supports' => 'created_at',
        'fairs' => 'created_at',
        'fair_evaluations' => 'created_at',
        'pqrfs' => 'created_at',
        'actors' => 'created_at',
    ];

    /**
     * The year that should be applied to queries for the current user, or
     * null if no year filter should be applied (i.e. show all years).
     */
    public static function effectiveYear(): ?int
    {
        $user = auth()->user();

        if (! $user || ! $user->can('viewAllYears')) {
            return now()->year;
        }

        $selected = session(self::SESSION_KEY, now()->year);

        return $selected === self::ALL_YEARS ? null : (int) $selected;
    }

    /**
     * Years that actually have data, derived from the min/max of the
     * relevant date column across every year-scoped table.
     *
     * @return array<int, int>
     */
    public static function availableYears(): array
    {
        return Cache::remember('year-context:available-years', now()->addHour(), function () {
            $min = null;
            $max = null;

            foreach (self::YEAR_COLUMNS as $table => $column) {
                $row = DB::table($table)
                    ->whereNotNull($column)
                    ->selectRaw("MIN(YEAR({$column})) as min_year, MAX(YEAR({$column})) as max_year")
                    ->first();

                if ($row?->min_year === null) {
                    continue;
                }

                $min = $min === null ? (int) $row->min_year : min($min, (int) $row->min_year);
                $max = $max === null ? (int) $row->max_year : max($max, (int) $row->max_year);
            }

            $min ??= now()->year;
            $max ??= now()->year;

            // El año en curso siempre debe poder elegirse, aunque todavía no tenga datos.
            $min = min($min, now()->year);
            $max = max($max, now()->year);

            return range($min, $max);
        });
    }
}
