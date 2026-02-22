<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    public const STATUS_IN_DEVELOPMENT = 'in_development';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_EXECUTION = 'execution';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'title',
        'description',
        'status',
        'sort_order',
        'show_on_board',
        'client_id',
        'project_id',
        'budget',
        'due_date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected $casts = [
        'show_on_board' => 'boolean',
        'due_date' => 'date',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_IN_DEVELOPMENT => 'В разработке',
            self::STATUS_PROCESSING => 'Обработка',
            self::STATUS_EXECUTION => 'Исполнение',
            self::STATUS_COMPLETED => 'Завершено',
        ];
    }

    public static function statusesForBoard(): array
    {
        return [
            self::STATUS_IN_DEVELOPMENT,
            self::STATUS_PROCESSING,
            self::STATUS_EXECUTION,
            self::STATUS_COMPLETED,
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
