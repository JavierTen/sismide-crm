<?php

namespace App\Support;

use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Throwable;

/**
 * Registra los cambios de los campos que guardan en tablas pivote: checklists y
 * selects múltiples con `->relationship()`. Filament los guarda con `sync()`,
 * que no dispara eventos de modelo, así que el historial no los vería.
 *
 * Se aplica a nivel de campo con `->auditRelationship('estudiantes')`, de modo
 * que funciona igual desde la página de edición que desde el modal de la tabla.
 * No reemplaza el guardado de Filament: lo envuelve, comparando los vínculos
 * antes y después.
 */
class RelationAudit
{
    public static function registerMacro(): void
    {
        Field::macro('auditRelationship', function (string $label): static {
            /** @var Field $this */
            $original = $this->saveRelationshipsUsing;

            if (! $original) {
                return $this;
            }

            $this->saveRelationshipsUsing(function () use ($original, $label): void {
                $relationship = $this->getRelationship();
                $tracked      = $relationship instanceof BelongsToMany;
                $before       = $tracked ? RelationAudit::relatedIds($relationship) : [];

                // El guardado original de Filament, sin tocar.
                $this->evaluate($original);

                if ($tracked) {
                    RelationAudit::log(
                        $this->getRecord(),
                        $label,
                        $relationship->getRelated(),
                        $before,
                        RelationAudit::relatedIds($this->getRelationship()),
                    );
                }
            });

            return $this;
        });
    }

    /**
     * @return array<int, int>
     */
    public static function relatedIds(BelongsToMany $relationship): array
    {
        return $relationship->allRelatedIds()->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<int, int>  $before
     * @param  array<int, int>  $after
     */
    public static function log(?Model $record, string $label, Model $related, array $before, array $after): void
    {
        $added   = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if (! $record || ($added === [] && $removed === [])) {
            return;
        }

        try {
            $names = $related->newQuery()
                ->withoutGlobalScopes()
                ->whereKey([...$added, ...$removed])
                ->get()
                ->mapWithKeys(fn (Model $model) => [(int) $model->getKey() => static::displayName($model)]);

            activity('relation')
                ->performedOn($record)
                ->event('relation_updated')
                ->withProperties([
                    'relation' => $label,
                    'total'    => count($after),
                    'added'    => array_map(fn ($id) => $names[$id] ?? "#{$id}", $added),
                    'removed'  => array_map(fn ($id) => $names[$id] ?? "#{$id}", $removed),
                ])
                ->log(sprintf('Actualizó %s: +%d / -%d (quedan %d)', $label, count($added), count($removed), count($after)));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public static function displayName(Model $model): string
    {
        if (method_exists($model, 'getFilamentName')) {
            return (string) $model->getFilamentName();
        }

        foreach (['display_name', 'full_name', 'name'] as $attribute) {
            if (filled($model->{$attribute} ?? null)) {
                return (string) $model->{$attribute};
            }
        }

        return '#'.$model->getKey();
    }
}
