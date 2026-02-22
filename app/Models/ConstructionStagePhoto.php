<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ConstructionStagePhoto extends Model
{
    protected $fillable = [
        'construction_stage_id',
        'path',
        'caption',
        'sort_order',
    ];

    public function constructionStage(): BelongsTo
    {
        return $this->belongsTo(ConstructionStage::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
