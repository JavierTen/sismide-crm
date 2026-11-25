<?php

namespace App\Support;

use App\Models\BusinessCanvas;
use App\Models\BusinessPlan;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * "Descargar Evidencias" de Planes de negocio y de Canvas: un ZIP con una
 * carpeta por emprendedor, como el de Caracterización. Baja lo que muestra la
 * tabla (con sus filtros) y se entrega por una ruta normal, no por Livewire,
 * para que el archivo no tenga que caber completo en memoria.
 */
class PlanEvidences
{
    public const PLANS = 'plans';

    public const CANVASES = 'canvases';

    /** Qué lleva cada carpeta: archivos (nombre en el ZIP => campo) y enlaces de video (etiqueta => campo). */
    private const CONTENTS = [
        self::PLANS => [
            'model'  => BusinessPlan::class,
            'label'  => 'planes de negocio',
            'files'  => ['Matriz_Adquisicion' => 'acquisition_matrix_path', 'Modelo_Negocio' => 'business_model_path', 'Logo' => 'logo_path'],
            'videos' => ['Video Fire Pitch' => 'fire_pitch_video_url', 'Video ciclo productivo' => 'production_cycle_video_url'],
        ],
        self::CANVASES => [
            'model'  => BusinessCanvas::class,
            'label'  => 'Canvas',
            'files'  => ['Documento_Canvas' => 'canvas_file_path'],
            'videos' => ['Video Fire Pitch' => 'fire_pitch_video_url'],
        ],
    ];

    public static function canDownload(): bool
    {
        return auth()->user()?->hasRole(['Admin', 'Viewer']) ?? false;
    }

    /** Botón del encabezado de la tabla, igual en las dos pantallas. */
    public static function action(string $type): Tables\Actions\Action
    {
        $label = self::CONTENTS[$type]['label'];

        return Tables\Actions\Action::make('download_evidences')
            ->label('Descargar Evidencias')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('primary')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-archive-box-arrow-down')
            ->modalHeading('Descargar evidencias de ' . $label)
            ->modalDescription(fn (HasTable $livewire) => static::description($type, $livewire->getFilteredSortedTableQuery()->count()))
            ->modalSubmitActionLabel('Descargar ZIP')
            ->action(function (HasTable $livewire) use ($type, $label) {
                $model = self::CONTENTS[$type]['model'];
                $ids   = $livewire->getFilteredSortedTableQuery()->pluck((new $model)->getQualifiedKeyName())->all();

                if ($ids === []) {
                    Notification::make()->warning()->title('Sin registros')->body("No hay {$label} en la tabla con los filtros actuales.")->send();

                    return;
                }

                $token = Str::random(32);
                session(["plan_evidences_{$token}" => ['type' => $type, 'ids' => $ids]]);

                $livewire->js('window.location.href = ' . json_encode(route('business-plans.download-evidences', ['token' => $token])));
            })
            ->visible(fn () => static::canDownload());
    }

    private static function description(string $type, int $records): string
    {
        $contents = $type === self::PLANS
            ? 'matriz de adquisición, modelo de negocio, logo y un archivo con los enlaces de los videos'
            : 'documento Canvas y un archivo con el enlace del video Fire Pitch';

        return "Se descargará un ZIP con las evidencias de los {$records} " . self::CONTENTS[$type]['label']
            . " que muestra la tabla (con los filtros que tengas puestos), en una carpeta por emprendedor: {$contents}. "
            . 'Puede tardar unos minutos si son muchos archivos.';
    }

    /**
     * Arma el ZIP. Devuelve null si ningún registro tiene archivos ni videos.
     *
     * @param  array<int>  $ids
     * @return array{path: string, name: string, records: int, files: int}|null
     */
    public static function zip(string $type, array $ids): ?array
    {
        $contents = self::CONTENTS[$type];
        $records  = $contents['model']::query()
            ->withoutGlobalScopes()
            ->with([
                'entrepreneur'          => fn ($q) => $q->withoutGlobalScopes(),
                'entrepreneur.business' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->whereIn('id', $ids)
            ->get();

        $name = 'evidencias_' . Str::slug($contents['label'], '_') . '_' . now()->format('Y-m-d_His') . '.zip';
        $path = storage_path('app/temp/' . $name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $disk    = Storage::disk('public');
        $folders = [];
        $files   = 0;

        foreach ($records as $record) {
            $folder = static::folder($record, $folders);

            foreach ($contents['files'] as $fileName => $field) {
                $paths = static::paths($record->{$field});

                foreach ($paths as $i => $file) {
                    if (! $disk->exists($file)) {
                        continue;
                    }

                    $entry = $folder . '/' . $fileName . (count($paths) > 1 ? '_' . ($i + 1) : '') . '.' . pathinfo($file, PATHINFO_EXTENSION);

                    // Fotos, PDF y Excel ya vienen comprimidos: se guardan tal cual (más rápido).
                    if ($zip->addFile($disk->path($file), $entry)) {
                        $zip->setCompressionName($entry, ZipArchive::CM_STORE);
                        $files++;
                    }
                }
            }

            $links = collect($contents['videos'])
                ->map(fn (string $field, string $label) => filled($record->{$field}) ? "{$label}: {$record->{$field}}" : null)
                ->filter();

            if ($links->isNotEmpty()) {
                $zip->addFromString($folder . '/Videos.txt', $links->implode(PHP_EOL) . PHP_EOL);
                $files++;
            }
        }

        $zip->close();

        if ($files === 0) {
            @unlink($path);

            return null;
        }

        return ['path' => $path, 'name' => $name, 'records' => $records->count(), 'files' => $files];
    }

    /** "Nombre_Emprendimiento", como en Caracterización; si se repite, se numera. */
    private static function folder($record, array &$used): string
    {
        $entrepreneur = $record->entrepreneur;
        $name         = ($entrepreneur?->full_name ?? 'Sin_Emprendedor') . '_' . ($entrepreneur?->business?->business_name ?? 'Sin_Emprendimiento');

        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = substr((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name), 0, 100);

        $used[$name] = ($used[$name] ?? 0) + 1;

        return $used[$name] > 1 ? "{$name}_{$used[$name]}" : $name;
    }

    /**
     * Rutas de un adjunto: un archivo como texto o una lista (a veces en JSON).
     *
     * @return array<int, string>
     */
    private static function paths(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value   = is_array($decoded) ? $decoded : [$value];
        }

        return array_values(array_filter(array_map(fn ($p) => trim((string) $p), (array) $value), 'filled'));
    }
}
