<?php

namespace App\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
}
