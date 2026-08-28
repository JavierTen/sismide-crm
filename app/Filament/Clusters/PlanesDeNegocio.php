<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class PlanesDeNegocio extends Cluster
{
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Planes de Negocio';
    protected static ?string $navigationGroup = 'Información general';
    protected static ?int    $navigationSort  = 5;
}
