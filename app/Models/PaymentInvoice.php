<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInvoice extends Model
{
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';

    public const PRIORITY_URGENT = 'urgent';
    public const PRIORITY_PLANNED = 'planned';
    public const PRIORITY_IMMEDIATE = 'immediate';

    protected $fillable = [
        'expense_article',
        'amount',
        'project_id',
        'project_expense_item_id',
        'responsible_user_id',
        'received_date',
        'due_date',
        'comments',
        'priority',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectExpenseItem(): BelongsTo
    {
        return $this->belongsTo(ProjectExpenseItem::class, 'project_expense_item_id');
    }

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

    public static function statusLabels(): array
    {
        return [
            self::STATUS_UNPAID => 'Не оплачен',
            self::STATUS_PAID => 'Оплачен',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}

