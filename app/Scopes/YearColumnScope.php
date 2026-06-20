<?php

namespace App\Scopes;

use App\Support\YearContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class YearColumnScope implements Scope
{
    public function __construct(protected string $column)
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $year = YearContext::effectiveYear();

        if ($year === null) {
            return;
        }

        $builder->whereYear($model->getTable() . '.' . $this->column, $year);
    }
}
