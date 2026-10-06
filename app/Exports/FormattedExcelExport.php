<?php

namespace App\Exports;

use App\Support\ExportLogger;
use Maatwebsite\Excel\Events\AfterSheet;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Throwable;

class FormattedExcelExport extends ExcelExport
{
    protected ?\Closure $afterSheetCallback = null;

    public function afterSheet(\Closure $callback): static
    {
        $this->afterSheetCallback = $callback;

        return $this;
    }

    public function registerEvents(): array
    {
        $events = parent::registerEvents();

        if ($this->afterSheetCallback) {
            $events[AfterSheet::class] = $this->afterSheetCallback;
        }

        return $events;
    }

    /**
     * Todas las exportaciones Excel de los listados pasan por aquí, así que es
     * el único punto donde hace falta registrarlas en el historial.
     */
    public function export()
    {
        $response = parent::export();

        ExportLogger::log($this->exportedLabel(), [
            'file'      => $this->getFilename(),
            'records'   => $this->exportedCount(),
            'selection' => $this->recordIds ? 'Registros seleccionados' : 'Listado completo',
            'filters'   => ExportLogger::activeFilters($this->getLivewire()?->tableFilters ?? null),
            'search'    => $this->getLivewire()?->tableSearch ?? null,
        ]);

        return $response;
    }

    private function exportedLabel(): string
    {
        try {
            if ($resource = $this->getResourceClass()) {
                return $resource::getPluralModelLabel();
            }

            return class_basename($this->getModelClass() ?? 'registros');
        } catch (Throwable) {
            return 'registros';
        }
    }

    private function exportedCount(): ?int
    {
        try {
            return $this->getQuery()->count();
        } catch (Throwable) {
            return null;
        }
    }
}
