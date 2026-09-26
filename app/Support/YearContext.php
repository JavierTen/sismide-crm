<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class YearContext
{
    public const SESSION_KEY = 'selected_year';

    public const ALL_YEARS = 'all';

    protected const DASHBOARD_YEAR_COLUMNS = [
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

    protected const EJE_YEAR_COLUMNS = [
        'student_canvases' => 'created_at',
        'student_characterizations' => 'created_at',
        'institution_evaluations' => 'created_at',
        'student_fairs' => 'created_at',
        'student_fair_participations' => 'created_at',
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
     * Years that actually have data for the given panel ('dashboard' or 'eje').
     * Each panel only considers its own tables, so EJE never muestra años
     * con datos exclusivos del Dashboard y viceversa.
     *
     * @return array<int, int>
     */
    public static function availableYears(string $panel = 'dashboard'): array
    {
        $tables = $panel === 'eje' ? self::EJE_YEAR_COLUMNS : self::DASHBOARD_YEAR_COLUMNS;

        return Cache::remember("year-context:available-years:{$panel}", now()->addHour(), function () use ($tables) {
            $min = null;
            $max = null;

            foreach ($tables as $table => $column) {
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

            $min = min($min, now()->year);
            $max = max($max, now()->year);

            return range($min, $max);
        });
    }
}
