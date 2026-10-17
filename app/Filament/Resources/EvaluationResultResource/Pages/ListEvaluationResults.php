<?php

namespace App\Filament\Resources\EvaluationResultResource\Pages;

use App\Filament\Resources\EvaluationResultResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEvaluationResults extends ListRecords
{
    protected static string $resource = EvaluationResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones
        ];
    }

    /**
     * El filtro "Puntaje" ordena el listado. Filament agrupa las consultas de
     * los filtros dentro de un WHERE, donde el orden se perdería, así que se
     * aplica aquí. Quien aún no tiene nota va siempre al final. Si se ordena
     * haciendo clic en el encabezado de una columna, ese orden tiene prioridad.
     */
    protected function applySortingToTableQuery(Builder $query): Builder
    {
        $direction = $this->tableFilters['score_order']['value'] ?? null;

        if (! $this->tableSortColumn && in_array($direction, ['asc', 'desc'], true)) {
            return $query
                ->orderByRaw('final_score_value IS NULL')
                ->orderBy('final_score_value', $direction)
                ->orderBy('business_plans.id');
        }

        return parent::applySortingToTableQuery($query);
    }
}
