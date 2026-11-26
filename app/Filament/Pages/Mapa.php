<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EntrepreneurPathResource;
use App\Models\Actor;
use App\Models\Characterization;
use App\Support\EntrepreneurPath;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentColor;

/**
 * Mapa de emprendedores (por su caracterización) y entidades con coordenadas.
 * La vista dibuja, filtra y agrupa en el navegador; aquí solo se arman los datos.
 */
class Mapa extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static string $view = 'filament.pages.mapa';

    protected static ?string $navigationLabel = 'Mapa';

    protected static ?int $navigationSort = 2;

    /** Rutas: nombre, niveles que agrupa y color (los de la ficha: Ruta 1 ámbar, 2 azul, 3 verde). */
    private const ROUTES = [
        1 => ['name' => 'Pre-emprendimiento', 'levels' => 'Niveles 0 a 2', 'color' => 'warning'],
        2 => ['name' => 'Consolidación', 'levels' => 'Niveles 3 y 4', 'color' => 'info'],
        3 => ['name' => 'Escalamiento', 'levels' => 'Nivel 5', 'color' => 'success'],
    ];

    public static function canAccess(): bool
    {
        return auth()->user()->can('viewMap');
    }

    /** Los gestores solo ven lo suyo. */
    private static function ownOnly(): bool
    {
        return ! auth()->user()->hasRole(['Admin', 'Viewer']);
    }

    /** Todo lo que necesita la vista, en un solo arreglo. */
    public function mapData(): array
    {
        $entrepreneurs = $this->entrepreneurs();

        return [
            'token'         => config('services.mapbox.token'),
            'routes'        => collect(self::ROUTES)->map(fn ($r, $n) => $r + ['number' => $n, 'hex' => static::hex($r['color'])])->values()->all(),
            'noRouteHex'    => static::hex('gray', 400),
            'entrepreneurs' => $entrepreneurs,
            'actors'        => $this->actors(),
            'cities'        => collect($entrepreneurs)->pluck('city')->filter()->unique()->sort()->values()->all(),
            'managers'      => static::ownOnly() ? [] : collect($entrepreneurs)->pluck('manager')->filter()->unique()->sort()->values()->all(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function entrepreneurs(): array
    {
        return Characterization::query()
            ->with([
                'entrepreneur' => fn ($q) => $q->with(['business', 'city', 'manager', 'businessDiagnoses']),
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when(static::ownOnly(), fn ($q) => $q->where('manager_id', auth()->id()))
            ->get()
            ->filter(fn (Characterization $c) => $c->entrepreneur)
            ->unique('entrepreneur_id')
            ->map(function (Characterization $c) {
                $e         = $c->entrepreneur;
                $placement = EntrepreneurPath::placement($e);

                return [
                    'id'       => $e->id,
                    'lat'      => (float) $c->latitude,
                    'lng'      => (float) $c->longitude,
                    'name'     => $e->full_name,
                    'business' => $e->business?->business_name,
                    'city'     => $e->city?->name,
                    'manager'  => $e->manager?->name,
                    'phone'    => $e->phone,
                    'email'    => $e->email,
                    'route'    => $placement['route'],
                    'level'    => $placement['level']['label'] ?? null,
                    'url'      => EntrepreneurPathResource::getUrl('index', ['tableAction' => 'view', 'tableActionRecord' => $e->id]),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function actors(): array
    {
        return Actor::query()
            ->with(['city', 'manager'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when(static::ownOnly(), fn ($q) => $q->where('manager_id', auth()->id()))
            ->get()
            ->map(fn (Actor $a) => [
                'id'      => $a->id,
                'lat'     => (float) $a->latitude,
                'lng'     => (float) $a->longitude,
                'name'    => $a->name,
                'type'    => $a->type_name,
                'contact' => $a->contact_name,
                'phone'   => $a->contact_phone,
                'email'   => $a->contact_email,
                'city'    => $a->city?->name,
                'manager' => $a->manager?->name,
            ])
            ->values()
            ->all();
    }

    /** Color de Filament en hexadecimal, para pintar en el mapa (Mapbox no lee variables CSS). */
    private static function hex(string $color, int $shade = 500): string
    {
        $rgb = FilamentColor::getColors()[$color][$shade] ?? '107, 114, 128';

        return '#' . collect(explode(',', $rgb))->map(fn ($c) => str_pad(dechex((int) trim($c)), 2, '0', STR_PAD_LEFT))->implode('');
    }
}
