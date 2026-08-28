<?php

namespace App\Models;

use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BusinessCanvas extends Model
{
    use HasFactory, SoftDeletes, TracksUpdatedBy;

    protected $fillable = [
        'entrepreneur_id',
        'manager_id',
        'problem_identification',
        'business_idea',
        'differentiator',
        'achievements',
        'business_model_description',
        'next_steps',
        'fire_pitch_video_url',
        'canvas_file_path',
        'is_potential',
        'is_prioritized',
        'updated_by_id',
    ];

    protected $casts = [
        'is_prioritized' => 'boolean',
        // is_potential es boolean nullable — NO castear para preservar null
    ];

    protected static function booted(): void
    {
        static::updating(function (self $canvas) {
            $original = $canvas->getOriginal();
            if ($original['canvas_file_path'] && $original['canvas_file_path'] !== $canvas->canvas_file_path) {
                Storage::disk('public')->delete($original['canvas_file_path']);
            }
        });

        static::deleting(function (self $canvas) {
            if ($canvas->canvas_file_path) {
                Storage::disk('public')->delete($canvas->canvas_file_path);
            }
        });
    }

    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class)->withTrashed();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
