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
                ->label('Registrar Canvas')
                ->icon('heroicon-o-plus')
                ->visible(fn () => auth()->user()->can('createBusinessCanvas')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'canvas' => Tab::make('Canvas (Ruta 1)')
                ->icon('heroicon-o-squares-2x2'),

            'plan' => Tab::make('Plan de Negocio')
                ->icon('heroicon-o-document-chart-bar')
                ->badge(fn () => BusinessPlan::query()
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
