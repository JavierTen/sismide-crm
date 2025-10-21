<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Characterization;

class Mapa extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static string $view = 'filament.pages.mapa';

    protected static ?string $navigationLabel = 'Mapa';

    protected static ?int $navigationSort = 2;

    /**
     * Obtener emprendedores con coordenadas
     */
    public function getEntrepreneursWithCoordinates()
    {
        return Characterization::with([
            'entrepreneur',
            'entrepreneur.business',
            'manager'
        ])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get()
        ->map(function ($characterization) {
            return [
                'latitude' => (float) $characterization->latitude,
                'longitude' => (float) $characterization->longitude,
                'entrepreneur_name' => $characterization->entrepreneur?->full_name ?? 'N/A',
                'business_name' => $characterization->entrepreneur?->business?->business_name ?? 'N/A',
                'phone' => $characterization->entrepreneur?->phone ?? 'N/A',
                'email' => $characterization->entrepreneur?->email ?? 'N/A',
                'manager' => $characterization->manager?->name ?? 'N/A',
            ];
        })
        ->values()
        ->toArray();
    }
}
