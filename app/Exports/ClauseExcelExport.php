<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Excel de la exportación por cláusulas, con el mismo formato que los demás
 * listados del sistema: encabezado fijo, filtro y columnas ajustadas. Los
 * ceros se escriben como 0 (sin WithStrictNullComparison quedarían vacíos).
 */
class ClauseExcelExport implements FromArray, WithEvents, WithHeadings, WithStrictNullComparison, WithTitle
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $rows,
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return 'Exportación';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet        = $event->sheet->getDelegate();
                $lastCol      = Coordinate::stringFromColumnIndex(max(count($this->headings), 1));
                $lastRow      = count($this->rows) + 1;

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                if ($lastRow > 1) {
                    $sheet->getStyle("A2:{$lastCol}{$lastRow}")->getAlignment()
                        ->setWrapText(true)
                        ->setVertical(Alignment::VERTICAL_TOP);
                }

                // Los enlaces de los adjuntos se abren con un clic. Una celda con
                // varios archivos (uno por línea) no admite hipervínculo y queda como texto.
                foreach ($this->rows as $r => $row) {
                    foreach (array_values($row) as $c => $value) {
                        if (is_string($value) && preg_match('#^https?://\S+$#i', $value)) {
                            $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($c + 1).($r + 2));
                            $cell->getHyperlink()->setUrl($value);
                            $cell->getStyle()->getFont()->setUnderline(true)->getColor()->setARGB('FF1D4ED8');
                        }
                    }
                }

                for ($i = 1; $i <= count($this->headings); $i++) {
                    $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}1");
            },
        ];
    }
}
