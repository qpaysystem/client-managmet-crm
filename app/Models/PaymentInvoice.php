<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInvoice extends Model
{
    public const PRIORITY_URGENT = 'urgent';
    public const PRIORITY_PLANNED = 'planned';
    public const PRIORITY_IMMEDIATE = 'immediate';

    protected $fillable = [
        'expense_article',
        'amount',
        'responsible_user_id',
        'received_date',
        'due_date',
        'comments',
        'priority',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_date' => 'date',
        'due_date' => 'date',
    ];

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public static function priorityLabels(): array
    {
        return [
            self::PRIORITY_URGENT => 'Срочно',
            self::PRIORITY_PLANNED => 'Планово',
            self::PRIORITY_IMMEDIATE => 'Безотлагательно',
        ];
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::priorityLabels()[$this->priority] ?? $this->priority;
    }
}

