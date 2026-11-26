<?php

namespace App\Support;

use App\Models\BusinessCanvas;
use App\Models\BusinessPlan;
use App\Models\Characterization;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * "Descargar Evidencias" de Caracterización, Planes de negocio y Canvas: un
 * ZIP con carpetas Municipio/Emprendimiento. Se arma y se entrega por rutas
 * normales (EvidenceZipController), no por Livewire, para que el archivo no
 * tenga que caber completo en memoria; la pantalla muestra el avance.
 */
class EvidenceZips
{
    public const CHARACTERIZATIONS = 'characterizations';

    public const PLANS = 'plans';

    public const CANVASES = 'canvases';

    /**
     * Descargas que se muestran. Planes y Canvas quedan ocultos por ahora
     * (decisión del usuario, 2026-11-25): para mostrarlos, agregarlos aquí.
     */
    private const ENABLED = [self::CHARACTERIZATIONS];

    /**
     * Qué lleva cada carpeta: archivos (nombre en el ZIP => campo) y enlaces
     * de video (etiqueta => campo). "yearly": el registro depende del año del panel.
     */
    private const CONTENTS = [
        self::CHARACTERIZATIONS => [
            'model'    => Characterization::class,
            'label'    => 'caracterizaciones',
            'article'  => 'las',
            'yearly'   => true,
            'files'    => ['Evidencia_Comercio' => 'commerce_evidence_path', 'Evidencia_Poblacion' => 'population_evidence_path', 'Foto_Georeferenciacion' => 'photo_evidence_path'],
            'videos'   => [],
            'contents' => 'evidencia del comercio, evidencia de población vulnerable (fotos o PDF) y foto de georreferenciación',
        ],
        self::PLANS => [
            'model'    => BusinessPlan::class,
            'label'    => 'planes de negocio',
            'article'  => 'los',
            'yearly'   => true,
            'files'    => ['Matriz_Adquisicion' => 'acquisition_matrix_path', 'Modelo_Negocio' => 'business_model_path', 'Logo' => 'logo_path'],
            'videos'   => ['Video Fire Pitch' => 'fire_pitch_video_url', 'Video ciclo productivo' => 'production_cycle_video_url'],
            'contents' => 'matriz de adquisición, modelo de negocio, logo y un archivo con los enlaces de los videos',
        ],
        self::CANVASES => [
            'model'    => BusinessCanvas::class,
            'label'    => 'Canvas',
            'article'  => 'los',
            'yearly'   => false,
            'files'    => ['Documento_Canvas' => 'canvas_file_path'],
            'videos'   => ['Video Fire Pitch' => 'fire_pitch_video_url'],
            'contents' => 'documento Canvas y un archivo con el enlace del video Fire Pitch',
        ],
    ];

    public static function canDownload(): bool
    {
        return auth()->user()?->hasRole(['Admin', 'Viewer']) ?? false;
    }

    /** La descarga existe y está habilitada (ver ENABLED). */
    public static function exists(?string $type): bool
    {
        return isset(self::CONTENTS[$type]) && in_array($type, self::ENABLED, true);
    }

    public static function logLabel(string $type): string
    {
        return 'Evidencias de ' . self::CONTENTS[$type]['label'] . ' (ZIP)';
    }

    /** Clave en caché de una descarga: "request" (registros escogidos), "progress" (avance) o "ready" (ZIP armado). */
    public static function cacheKey(string $what, string $token): string
    {
        return "evidence_zip_{$what}_{$token}";
    }

    /**
     * Botón del encabezado de la tabla. Baja todo; si la tabla tiene filtros o
     * búsqueda que cambian lo que se ve, pregunta si todo o solo lo filtrado.
     */
    public static function action(string $type): Tables\Actions\Action
    {
        $c = self::CONTENTS[$type];

        return Tables\Actions\Action::make('download_evidences')
            ->label('Descargar Evidencias')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('primary')
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-archive-box-arrow-down')
            ->modalHeading('Descargar evidencias de ' . $c['label'])
            ->modalDescription(fn (HasTable $livewire) => static::description($type, $livewire))
            ->form(fn (HasTable $livewire) => static::scopeQuestion($type, $livewire))
            ->modalSubmitActionLabel('Descargar ZIP')
            ->action(function (HasTable $livewire, array $data) use ($type, $c) {
                $scope = $data['scope'] ?? 'all';
                $ids   = static::records($type, $livewire, $scope)->pluck((new $c['model'])->getQualifiedKeyName())->all();

                if ($ids === []) {
                    Notification::make()->warning()->title('Sin registros')
                        ->body('No hay ' . $c['label'] . ' ' . ($scope === 'all' ? static::yearText($type) : 'en la tabla con los filtros actuales') . '.')
                        ->send();

                    return;
                }

                // El ZIP se arma en otra petición mientras la pantalla muestra el avance
                // (ver evidence-zip-toast.blade.php); los registros esperan en caché.
                $token = Str::random(32);
                Cache::put(static::cacheKey('request', $token), ['user' => auth()->id(), 'type' => $type, 'ids' => $ids], now()->addMinutes(30));

                $livewire->js('window.evidenceZip.start(' . json_encode(['token' => $token, 'label' => 'Evidencias de ' . $c['label']]) . ')');
            })
            ->visible(fn () => static::exists($type) && static::canDownload());
    }

    /** "all": todos los registros (del año, si aplica) con emprendedor; "filtered": lo que muestra la tabla. */
    private static function records(string $type, HasTable $livewire, string $scope): Builder
    {
        return $scope === 'all'
            ? self::CONTENTS[$type]['model']::query()->whereNotNull('entrepreneur_id')
            : $livewire->getFilteredSortedTableQuery();
    }

    /** Si los filtros o la búsqueda no cambian lo que se ve, no se pregunta. */
    private static function hasChoice(string $type, HasTable $livewire): bool
    {
        return static::records($type, $livewire, 'all')->count() !== static::records($type, $livewire, 'filtered')->count();
    }

    /** @return array<int, Forms\Components\Component> */
    private static function scopeQuestion(string $type, HasTable $livewire): array
    {
        if (! static::hasChoice($type, $livewire)) {
            return [];
        }

        $c = self::CONTENTS[$type];

        return [
            Forms\Components\Radio::make('scope')
                ->label('¿Qué quieres descargar?')
                ->options([
                    'all'      => trim("Todo: {$c['article']} " . static::records($type, $livewire, 'all')->count() . " {$c['label']} " . static::yearText($type)),
                    'filtered' => "Solo lo filtrado: {$c['article']} " . static::records($type, $livewire, 'filtered')->count() . ' que muestra la tabla',
                ])
                ->default('all')
                ->required(),
        ];
    }

    private static function yearText(string $type): string
    {
        if (! self::CONTENTS[$type]['yearly']) {
            return '';
        }

        $year = YearContext::effectiveYear();

        return $year ? "de {$year}" : 'de todos los años';
    }

    private static function description(string $type, HasTable $livewire): string
    {
        $c     = self::CONTENTS[$type];
        $which = static::hasChoice($type, $livewire)
            ? "de {$c['article']} {$c['label']} que escojas"
            : trim("de {$c['article']} " . static::records($type, $livewire, 'all')->count() . " {$c['label']} " . static::yearText($type));

        return "Se descargará un ZIP con las evidencias {$which}, en carpetas por municipio y emprendimiento: "
            . "{$c['contents']}. Puede tardar unos minutos si son muchos archivos.";
    }

    /**
     * Arma el ZIP. Devuelve null si ningún registro tiene archivos ni videos.
     *
     * @param  array<int>  $ids
     * @param  (\Closure(int): void)|null  $progress  recibe el avance de 0 a 100
     * @return array{path: string, name: string, records: int, files: int}|null
     */
    public static function zip(string $type, array $ids, ?\Closure $progress = null): ?array
    {
        $progress ??= fn (int $percent) => null;
        $progress(0);

        $contents = self::CONTENTS[$type];
        $records  = $contents['model']::query()
            ->withoutGlobalScopes()
            ->with([
                'entrepreneur'          => fn ($q) => $q->withoutGlobalScopes(),
                'entrepreneur.business' => fn ($q) => $q->withoutGlobalScopes(),
                'entrepreneur.city',
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

        // Emprendimientos con el mismo nombre en un municipio: sus carpetas llevan el nombre del emprendedor.
        $repeated = $records->countBy(fn ($record) => mb_strtoupper(static::cityFolder($record)))->filter(fn (int $n) => $n > 1);

        foreach ($records as $record) {
            $folder = static::folder($record, $repeated->has(mb_strtoupper(static::cityFolder($record))), $folders);

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

        // Los archivos se copian al ZIP al cerrarlo: ahí está casi todo el tiempo.
        // Revisar la lista es el primer 5 %; copiarlos, el resto.
        $progress(5);

        if (method_exists($zip, 'registerProgressCallback')) {
            $zip->registerProgressCallback(0.01, fn (float $rate) => $progress(5 + (int) floor($rate * 95)));
        }

        $zip->close();
        $progress(100);

        if ($files === 0) {
            @unlink($path);

            return null;
        }

        return ['path' => $path, 'name' => $name, 'records' => $records->count(), 'files' => $files];
    }

    /** "Municipio/Emprendimiento" (sin emprendimiento, el nombre del emprendedor). */
    private static function cityFolder($record): string
    {
        $entrepreneur = $record->entrepreneur;

        return static::segment($entrepreneur?->city?->name ?? 'Sin_Municipio') . '/'
            . static::segment($entrepreneur?->business?->business_name ?: ($entrepreneur?->full_name ?? 'Sin_Emprendedor'));
    }

    /**
     * "Municipio/Emprendimiento". Si el emprendimiento se repite en el
     * municipio, lleva también el nombre del emprendedor; si aun así se
     * repite, se numera.
     */
    private static function folder($record, bool $repeated, array &$used): string
    {
        $entrepreneur = $record->entrepreneur;
        $business     = $entrepreneur?->business?->business_name;

        $name = $repeated && filled($business)
            ? static::segment($entrepreneur?->city?->name ?? 'Sin_Municipio') . '/' . static::segment($business . '_' . ($entrepreneur?->full_name ?? 'Sin_Emprendedor'))
            : static::cityFolder($record);

        $used[$name] = ($used[$name] ?? 0) + 1;

        return $used[$name] > 1 ? "{$name}_{$used[$name]}" : $name;
    }

    /** Nombre de carpeta sin caracteres que Windows no acepta, sin tildes y sin espacios. */
    private static function segment(string $name): string
    {
        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $name);
        $name = preg_replace('/\s+/', '_', trim($name));

        return substr(Str::ascii($name), 0, 100);
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
