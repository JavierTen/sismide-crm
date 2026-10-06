<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Traduce los campos que son referencias a otra tabla (`gender_id`) a algo
 * legible: el nombre del módulo como etiqueta ("Género") y el registro
 * referenciado como valor ("Femenino") en lugar del número.
 *
 * Las claves foráneas se descubren a partir de las relaciones BelongsTo del
 * propio modelo, así que no hay que mantener ningún mapa a mano. Se resuelve al
 * mostrar, de modo que también sirve para acciones registradas antes.
 */
class ActivityFieldResolver
{
    /** @var array<class-string, array<string, class-string>> clave foránea => modelo relacionado */
    private static array $foreignKeys = [];

    /** @var array<string, string> */
    private static array $displayCache = [];

    public function __construct(private readonly ?string $modelClass)
    {
    }

    /** Modelo al que apunta el campo, o null si no es una referencia. */
    public function relatedModelFor(string $field): ?string
    {
        if (! str_ends_with($field, '_id')) {
            return null;
        }

        return $this->foreignKeys()[$field] ?? null;
    }

    public function label(string $field): string
    {
        $related = $this->relatedModelFor($field);

        return $related ? (ModelLabels::for($related) ?? Str::headline(class_basename($related))) : $field;
    }

    /** Valor legible: el nombre del registro referenciado o el valor tal cual. */
    public function value(string $field, mixed $value): mixed
    {
        $related = $this->relatedModelFor($field);

        if (! $related || $value === null || $value === '') {
            return $value;
        }

        $key = $related.'#'.$value;

        return static::$displayCache[$key] ??= $this->displayName($related, $value);
    }

    private function displayName(string $related, mixed $id): string
    {
        try {
            /** @var Model|null $record */
            $record = $related::query()->withoutGlobalScopes()->find($id);
        } catch (Throwable) {
            $record = null;
        }

        if (! $record) {
            return "#{$id} (ya no existe)";
        }

        if (method_exists($record, 'getFilamentName')) {
            return (string) $record->getFilamentName();
        }

        foreach (['display_name', 'full_name', 'name', 'title', 'business_name', 'description', 'code'] as $attribute) {
            $value = $record->{$attribute} ?? null;

            if (filled($value) && is_scalar($value)) {
                return (string) $value;
            }
        }

        return "#{$id}";
    }

    /**
     * @return array<string, class-string>
     */
    private function foreignKeys(): array
    {
        $class = $this->modelClass;

        if (! $class || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return [];
        }

        if (isset(static::$foreignKeys[$class])) {
            return static::$foreignKeys[$class];
        }

        $model = new $class();
        $map   = [];

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (! $this->looksLikeBelongsTo($method)) {
                continue;
            }

            try {
                $relation = $model->{$method->getName()}();
            } catch (Throwable) {
                continue;
            }

            if ($relation instanceof BelongsTo && ! $relation instanceof MorphTo) {
                $map[$relation->getForeignKeyName()] ??= get_class($relation->getRelated());
            }
        }

        return static::$foreignKeys[$class] = $map;
    }

    /**
     * Solo se invocan métodos sin argumentos que son claramente una relación
     * BelongsTo: o lo declaran como tipo de retorno, o su código llama a
     * `belongsTo(`. Así nunca se ejecuta lógica arbitraria del modelo, y se
     * cubren también las relaciones escritas sin tipo de retorno.
     */
    private function looksLikeBelongsTo(ReflectionMethod $method): bool
    {
        if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        // Solo métodos del proyecto: los de Eloquent nunca son relaciones propias.
        $file = (string) $method->getFileName();

        if ($file === '' || str_contains(str_replace(DIRECTORY_SEPARATOR, '/', $file), '/vendor/')) {
            return false;
        }

        $type = $method->getReturnType();

        if ($type instanceof ReflectionNamedType) {
            return is_a($type->getName(), BelongsTo::class, true)
                && ! is_a($type->getName(), MorphTo::class, true);
        }

        if ($type !== null || ! $method->getFileName()) {
            return false;
        }

        $lines = @file($method->getFileName());

        if ($lines === false) {
            return false;
        }

        $body = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

        return str_contains($body, '->belongsTo(');
    }
}
