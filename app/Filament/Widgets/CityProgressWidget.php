<?php

namespace App\Filament\Widgets;

use App\Models\BusinessDiagnosis;
use App\Models\Characterization;
use App\Models\City;
use App\Models\Entrepreneur;
use App\Models\Project;
use Filament\Widgets\Widget;

class CityProgressWidget extends Widget
{
    protected static string $view = 'filament.widgets.city-progress';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $goal = Project::where('status', true)->value('participant_goal') ?? 300;

        $entQuery = Entrepreneur::query();
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $entQuery->where('manager_id', auth()->id());
        }
        $entrepreneurs = $entQuery->with('city')->get();

        $cityIds = $entrepreneurs->pluck('city_id')->unique()->filter()->values();
        $cities  = City::whereIn('id', $cityIds)->get()->keyBy('id');
        $cityCount = max($cities->count(), 1);
        $goalPerCity = (int) round($goal / $cityCount);

        $grouped = $entrepreneurs->groupBy('city_id');

        $rows = [];
        foreach ($grouped as $cityId => $group) {
            $cityName  = $cities[$cityId]?->name ?? 'Sin municipio';
            $entIds    = $group->pluck('id')->toArray();
            $registered = $group->count();

            $characterized = Characterization::whereIn('entrepreneur_id', $entIds)
                ->distinct('entrepreneur_id')->count('entrepreneur_id');

            $diagnosed = BusinessDiagnosis::whereIn('entrepreneur_id', $entIds)
                ->distinct('entrepreneur_id')->count('entrepreneur_id');

            $pending  = $registered - $characterized;
            $avance   = $goalPerCity > 0 ? round(($characterized / $goalPerCity) * 100, 1) : 0;

            $rows[] = [
                'city'          => $cityName,
                'meta'          => $goalPerCity,
                'registered'    => $registered,
                'characterized' => $characterized,
                'diagnosed'     => $diagnosed,
                'pending'       => $pending,
                'avance'        => $avance,
            ];
        }

        usort($rows, fn($a, $b) => $b['avance'] <=> $a['avance']);

        $totalCharacterized = array_sum(array_column($rows, 'characterized'));
        $totals = [
            'meta'          => $goal,
            'registered'    => array_sum(array_column($rows, 'registered')),
            'characterized' => $totalCharacterized,
            'diagnosed'     => array_sum(array_column($rows, 'diagnosed')),
            'pending'       => array_sum(array_column($rows, 'pending')),
            'avance'        => $goal > 0 ? round(($totalCharacterized / $goal) * 100, 1) : 0,
        ];

        return compact('rows', 'totals');
    }
}
