<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksUpdatedBy
{
    public static function bootTracksUpdatedBy(): void
    {
        static::saving(function (self $model) {
            if (auth()->check()) {
                $model->updated_by_id = auth()->id();
            }
        });
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
