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
        $query = Characterization::with([
            'entrepreneur',
            'entrepreneur.business',
            'entrepreneur.city',
            'entrepreneur.businessDiagnosis',
            'manager'
        ])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude');

        // Filtrar por gestor si no es Admin
        if (!auth()->user()->hasRole(['Admin', 'Viewer'])) {
            $query->where('manager_id', auth()->id());
        }

        return $query->get()
            ->map(function ($characterization) {
                // ✅ Obtener el diagnóstico desde el emprendedor
                $diagnosis = $characterization->entrepreneur?->businessDiagnosis;
                $maturityLevel = $diagnosis?->maturity_level;

                // Determinar la ruta según el nivel
                $route = $this->getRouteByMaturityLevel($maturityLevel);

                return [
                    'latitude' => (float) $characterization->latitude,
                    'longitude' => (float) $characterization->longitude,
                    'entrepreneur_name' => $characterization->entrepreneur?->full_name ?? 'N/A',
                    'business_name' => $characterization->entrepreneur?->business?->business_name ?? 'N/A',
                    'phone' => $characterization->entrepreneur?->phone ?? 'N/A',
                    'email' => $characterization->entrepreneur?->email ?? 'N/A',
                    'manager' => $characterization->manager?->name ?? 'N/A',
                    'city' => $characterization->entrepreneur?->city?->name ?? 'N/A',
                    'maturity_level' => $maturityLevel ?? 'Sin diagnóstico',
                    'route' => $route['name'],
                    'route_color' => $route['color'],
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Determinar la ruta según el nivel de madurez
     * El maturity_level viene como string: "Nivel 0: Pre-emprendimiento..."
     */
    private function getRouteByMaturityLevel($maturityLevel)
    {
        if (empty($maturityLevel)) {
            return [
                'name' => 'Sin diagnóstico',
                'color' => '#6B7280' // Gris
            ];
        }

        // Extraer el número del nivel (0-5) del string "Nivel X: ..."
        if (preg_match('/Nivel\s+(\d+):/', $maturityLevel, $matches)) {
            $level = (int) $matches[1];
        } else {
            return [
                'name' => 'Sin clasificar',
                'color' => '#6B7280' // Gris
            ];
        }

        // Ruta 1: Pre-emprendimiento y validación temprana (Nivel 0, 1, 2)
        if (in_array($level, [0, 1, 2])) {
            return [
                'name' => 'Ruta 1: Pre-emprendimiento',
                'color' => '#F97316'
            ];
        }

        // Ruta 2: Consolidación (Nivel 3, 4)
        if (in_array($level, [3, 4])) {
            return [
                'name' => 'Ruta 2: Consolidación',
                'color' => '#3B82F6'
            ];
        }

        // Ruta 3: Escalamiento (Nivel 5)
        if ($level === 5) {
            return [
                'name' => 'Ruta 3: Escalamiento',
                'color' => '#10B981'
            ];
        }

        // Por defecto
        return [
            'name' => 'Sin clasificar',
            'color' => '#6B7280'
        ];
    }
}
