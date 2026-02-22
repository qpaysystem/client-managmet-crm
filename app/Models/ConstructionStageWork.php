<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstructionStageWork extends Model
{
    protected $fillable = [
        'construction_stage_id',
        'work_start_date',
        'materials_name',
        'materials_cost',
        'works_name',
        'works_cost',
        'contractor',
        'sort_order',
    ];

    protected $casts = [
        'work_start_date' => 'date',
        'materials_cost' => 'decimal:2',
        'works_cost' => 'decimal:2',
    ];

    public function constructionStage(): BelongsTo
    {
        return $this->belongsTo(ConstructionStage::class);
    }
}
