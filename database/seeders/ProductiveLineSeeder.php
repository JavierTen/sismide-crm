<?php

namespace Database\Seeders;

use App\Models\ProductiveLine;
use Illuminate\Database\Seeder;

class ProductiveLineSeeder extends Seeder
{
    public function run(): void
    {
        $lines = [
            'PENDIENTE DEFINIR',
            'AGRICOLA',
            'AGROINDUSTRIAL',
            'AGROPECUARIO',
            'ARTESANIAS',
            'AVICOLA',
            'COMERCIAL',
            'COMIDAS RAPIDAS',
            'CONFECCIONES',
            'EBANISTERIA',
            'EVENTOS',
            'GASTRONOMIA',
            'MANUFACTURA',
            'PANADERIA',
            'RECICLAJE',
            'REPOSTERIA',
            'RESTAURANTE',
            'SALA DE BELLEZA',
            'SERVICIOS',
            'TURISMO',
        ];

        foreach ($lines as $name) {
            ProductiveLine::firstOrCreate(
                ['name' => $name],
                ['status' => true]
            );
        }
    }
}
