<?php

namespace App\Exports;

use App\Models\Actor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ActorContactsExport implements FromCollection, WithHeadings, WithEvents
{
    public function __construct(private ?array $ids = null) {}

    public function collection(): Collection
    {
        $actors = Actor::with(['department', 'city', 'manager', 'contacts'])
            ->when($this->ids, fn ($q) => $q->whereIn('id', $this->ids))
            ->orderBy('name')
            ->get();

        $rows = collect();

        foreach ($actors as $actor) {
            $entityData = [
                $actor->name,
                $actor->nit ?? '',
                Actor::TYPE_OPTIONS[$actor->type] ?? $actor->type ?? '',
                Actor::NATURE_OPTIONS[$actor->nature] ?? $actor->nature ?? '',
                $actor->economic_sector ?? '',
                $actor->institutional_phone ?? '',
                $actor->institutional_email ?? '',
                $actor->website ?? '',
                Actor::LINKAGE_STATUS_OPTIONS[$actor->linkage_status] ?? $actor->linkage_status ?? '',
                Actor::ACTION_SCOPE_OPTIONS[$actor->action_scope] ?? $actor->action_scope ?? '',
                $actor->has_physical_office ? 'Sí' : 'No',
                $actor->office_address ?? '',
                $actor->department?->name ?? '',
                $actor->city?->name ?? '',
                $actor->manager?->name ?? '',
                $actor->created_at?->format('d/m/Y H:i') ?? '',
            ];

            if ($actor->contacts->isEmpty()) {
                $rows->push(array_merge($entityData, ['', '', '', '', '', '', '', '']));
            } else {
                foreach ($actor->contacts as $contact) {
                    $contactTypeOptions = [
                        'directive'       => 'Directivo',
                        'institutional'   => 'Institucional',
                        'commercial'      => 'Comercial',
                        'financial'       => 'Financiero',
                        'technical'       => 'Técnico',
                        'formative'       => 'Formativo',
                        'logistic'        => 'Logístico',
                        'other'           => 'Otro',
                    ];
                    $decisionOptions = [
                        'decision_maker' => 'Toma decisiones',
                        'influencer'     => 'Influye en la decisión',
                        'operational'    => 'Operativo',
                    ];
                    $statusOptions = [
                        'active'           => 'Activo',
                        'pending_contact'  => 'Pendiente contacto',
                        'no_response'      => 'Sin respuesta',
                        'withdrawn'        => 'Retirado',
                        'inactive'         => 'Inactivo',
                    ];

                    $rows->push(array_merge($entityData, [
                        $contact->name ?? '',
                        $contact->role ?? '',
                        $contact->email ?? '',
                        $contact->phone ?? '',
                        $contact->area ?? '',
                        $contactTypeOptions[$contact->contact_type] ?? $contact->contact_type ?? '',
                        $contact->is_primary_contact ? 'Sí' : 'No',
                        $statusOptions[$contact->status] ?? $contact->status ?? '',
                    ]));
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            // Entidad
            'Nombre de la Entidad',
            'NIT',
            'Tipo de Entidad',
            'Naturaleza',
            'Sector Económico',
            'Teléfono Institucional',
            'Correo Institucional',
            'Sitio Web',
            'Estado de Vinculación',
            'Ámbito de Acción',
            'Tiene Oficina Física',
            'Dirección',
            'Departamento',
            'Municipio',
            'Registrado por',
            'Fecha de Registro',
            // Contacto
            'Nombre Contacto',
            'Cargo',
            'Correo Contacto',
            'Teléfono Contacto',
            'Área',
            'Tipo de Contacto',
            'Es Principal',
            'Estado Contacto',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle('1:1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E40AF'],
                    ],
                    'alignment' => [
                        'wrapText'   => true,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:' . $lastCol . '1');

                $lastColIdx = Coordinate::columnIndexFromString($lastCol);
                for ($i = 1; $i <= $lastColIdx; $i++) {
                    $col = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
