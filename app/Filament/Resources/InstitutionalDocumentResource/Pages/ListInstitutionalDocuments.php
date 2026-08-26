<?php

namespace App\Filament\Resources\InstitutionalDocumentResource\Pages;

use App\Filament\Resources\InstitutionalDocumentResource;
use App\Models\Actor;
use App\Models\EntityContact;
use App\Models\InstitutionalDocument;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ListInstitutionalDocuments extends ListRecords
{
    protected static string $resource = InstitutionalDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar documento')
                ->icon('heroicon-o-plus'),

            Actions\ActionGroup::make([

                Actions\Action::make('export_seguimiento')
                    ->label('Exportar Seguimiento')
                    ->icon('heroicon-o-table-cells')
                    ->visible(fn() => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->action(function (ListInstitutionalDocuments $livewire) {
                        $documents = $livewire->getFilteredSortedTableQuery()
                            ->with(['entity.city'])
                            ->get();

                        $rows = $documents->map(fn($doc) => [
                            $doc->entity?->city?->name ?? '—',
                            $doc->entity?->name        ?? '—',
                            InstitutionalDocument::DOCUMENT_TYPE_OPTIONS[$doc->document_type] ?? $doc->document_type,
                            $doc->subject,
                            InstitutionalDocument::STATUS_OPTIONS[$doc->status] ?? $doc->status,
                            $doc->main_file_path ? 'Sí' : 'No',
                            $doc->main_file_path ? strtoupper(pathinfo($doc->main_file_path, PATHINFO_EXTENSION)) : '—',
                            InstitutionalDocument::PHYSICAL_SUPPORT_OPTIONS[$doc->has_physical_support] ?? '—',
                            $doc->created_at?->format('d/m/Y') ?? '—',
                            $doc->updated_at?->format('d/m/Y') ?? '—',
                            match (true) {
                                !$doc->requires_follow_up               => 'Completo',
                                $doc->follow_up_date?->isPast()         => 'Vencido',
                                $doc->status === 'pending_signature'    => 'Pendiente de firma',
                                (bool) $doc->requires_follow_up         => 'Requiere seguimiento',
                                default                                  => '—',
                            },
                            $doc->observation ?? '',
                        ])->toArray();

                        $token = Str::random(32);
                        session(["excel_{$token}" => [
                            'rows'     => $rows,
                            'headings' => ['Municipio', 'Entidad', 'Tipo de documento', 'Nombre del documento', 'Estado', 'Archivo cargado', 'Formato', 'Soporte físico', 'Fecha de carga', 'Última actualización', 'Seguimiento', 'Observaciones'],
                            'filename' => 'seguimiento-documental-' . now()->format('Y-m-d') . '.xlsx',
                        ]]);

                        $url = route('institutional-documents.download-excel', ['token' => $token]);
                        $livewire->js('window.location.href = ' . json_encode($url));
                    }),

                Actions\Action::make('export_directorio')
                    ->label('Exportar Directorio')
                    ->icon('heroicon-o-user-group')
                    ->visible(fn() => auth()->user()->hasRole(['Admin', 'Viewer']))
                    ->action(function (ListInstitutionalDocuments $livewire) {
                        $entityIds = $livewire->getFilteredSortedTableQuery()
                            ->pluck('entity_id')
                            ->unique()
                            ->toArray();

                        $contacts = EntityContact::with(['entity.city'])
                            ->whereIn('entity_id', $entityIds)
                            ->orderBy('entity_id')
                            ->get();

                        $rows = $contacts->map(fn($c) => [
                            $c->entity?->city?->name ?? '—',
                            $c->entity?->name        ?? '—',
                            Actor::TYPE_OPTIONS[$c->entity?->type] ?? '—',
                            $c->name,
                            $c->role,
                            $c->phone,
                            $c->email,
                            $c->is_primary_contact ? 'Sí' : 'No',
                            match ($c->status) {
                                'active'          => 'Activo',
                                'pending_contact' => 'Pendiente contacto',
                                'no_response'     => 'Sin respuesta',
                                'withdrawn'       => 'Retirado',
                                'inactive'        => 'Inactivo',
                                default           => $c->status ?? '—',
                            },
                            $c->notes ?? '',
                        ])->toArray();

                        $token = Str::random(32);
                        session(["excel_{$token}" => [
                            'rows'     => $rows,
                            'headings' => ['Municipio', 'Entidad', 'Tipo de entidad', 'Nombre contacto', 'Cargo', 'Teléfono', 'Correo', 'Principal', 'Estado', 'Observaciones'],
                            'filename' => 'directorio-contactos-' . now()->format('Y-m-d') . '.xlsx',
                        ]]);

                        $url = route('institutional-documents.download-excel', ['token' => $token]);
                        $livewire->js('window.location.href = ' . json_encode($url));
                    }),

                Actions\Action::make('download_evidences')
                    ->label('Descargar evidencias')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->action(function (ListInstitutionalDocuments $livewire) {
                        $ids = $livewire->getFilteredSortedTableQuery()
                            ->whereNotNull('main_file_path')
                            ->pluck('id')
                            ->toArray();

                        if (empty($ids)) {
                            Notification::make()
                                ->warning()
                                ->title('Sin archivos')
                                ->body('No hay archivos digitales en los registros actuales.')
                                ->send();
                            return;
                        }

                        $token = Str::random(32);
                        session(["evidences_{$token}" => $ids]);

                        $url = route('institutional-documents.download-evidences', ['token' => $token]);
                        $livewire->js('window.location.href = ' . json_encode($url));
                    }),

            ])
            ->label('Exportar')
            ->icon('heroicon-o-arrow-down-tray')
            ->button()
            ->color('gray'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-document-text'),

            'pendientes' => Tab::make('Pendientes')
                ->icon('heroicon-o-clock')
                ->badge(fn() => InstitutionalDocument::query()
                    ->when(!auth()->user()->hasRole(['Admin', 'Viewer']), fn($q) => $q->where('manager_id', auth()->id()))
                    ->whereIn('status', ['pending', 'in_management'])
                    ->count()
                )
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['pending', 'in_management'])),

            'pendientes_firma' => Tab::make('Pendientes de firma')
                ->icon('heroicon-o-pencil-square')
                ->badge(fn() => InstitutionalDocument::query()
                    ->when(!auth()->user()->hasRole(['Admin', 'Viewer']), fn($q) => $q->where('manager_id', auth()->id()))
                    ->where('status', 'pending_signature')
                    ->count()
                )
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending_signature')),

            'seguimiento_vencido' => Tab::make('Seguimiento vencido')
                ->icon('heroicon-o-bell-alert')
                ->badge(fn() => InstitutionalDocument::query()
                    ->when(!auth()->user()->hasRole(['Admin', 'Viewer']), fn($q) => $q->where('manager_id', auth()->id()))
                    ->where('requires_follow_up', true)
                    ->where('follow_up_date', '<', now())
                    ->whereNotIn('status', ['signed', 'finalized'])
                    ->count()
                )
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->where('requires_follow_up', true)
                    ->where('follow_up_date', '<', now())
                    ->whereNotIn('status', ['signed', 'finalized'])
                ),
        ];
    }
}
