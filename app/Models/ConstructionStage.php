<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConstructionStage extends Model
{
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'project_id',
        'name',
        'client_id',
        'budget',
        'contractor',
        'sort_order',
        'status',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ConstructionStagePhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ConstructionStageComment::class)->with('client')->latest();
    }

    public function works(): HasMany
    {
        return $this->hasMany(ConstructionStageWork::class)->orderBy('sort_order')->orderBy('id');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_NOT_STARTED => 'Не начат',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_COMPLETED => 'Завершён',
        ];
    }

    public static function statusesForBoard(): array
    {
        return [
            self::STATUS_NOT_STARTED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /** Этап отстаёт: в работе, но плановая дата окончания уже прошла */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS || !$this->planned_end_date) {
            return false;
        }
        return $this->planned_end_date->isPast();
    }
}
