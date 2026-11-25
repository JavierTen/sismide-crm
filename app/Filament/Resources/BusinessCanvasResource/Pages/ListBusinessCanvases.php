<?php

namespace App\Filament\Resources\BusinessCanvasResource\Pages;

use App\Filament\Resources\BusinessCanvasResource;
use App\Filament\Resources\BusinessPlanResource;
use App\Models\BusinessPlan;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListBusinessCanvases extends ListRecords
{
    protected static string $resource = BusinessCanvasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createBusinessCanvas')),
        ];
    }

    public function getTabs(): array
    {
        // Mismo orden que en Planes de negocio, para que las pestañas no cambien de lugar.
        return [
            'plan' => Tab::make('Plan de Negocio')
                ->icon('heroicon-o-document-chart-bar')
                ->badge(fn () => BusinessPlan::query()
                    ->when(
                        ! auth()->user()->hasRole(['Admin', 'Viewer']),
                        fn ($q) => $q->where('manager_id', auth()->id())
                    )
                    ->count()
                ),

            'canvas' => Tab::make('Canvas (Ruta 1)')
                ->icon('heroicon-o-squares-2x2')
                ->badge(fn () => \App\Models\BusinessCanvas::query()
                    ->when(
                        ! auth()->user()->hasRole(['Admin', 'Viewer']),
                        fn ($q) => $q->where('manager_id', auth()->id())
                    )
                    ->count()
                ),
        ];
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'plan') {
            $this->redirect(BusinessPlanResource::getUrl('index'));
            return;
        }
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'canvas';
    }
}
