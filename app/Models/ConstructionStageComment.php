<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstructionStageComment extends Model
{
    protected $fillable = [
        'construction_stage_id',
        'client_id',
        'body',
    ];

    public function constructionStage(): BelongsTo
    {
        return $this->belongsTo(ConstructionStage::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
