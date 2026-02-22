<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTransaction extends Model
{
    public const OPERATION_LOAN = 'loan';
    public const OPERATION_LOAN_REPAYMENT = 'loan_repayment';
    public const OPERATION_OTHER_INCOME = 'other_income';
    public const OPERATION_PROJECT_EXPENSE = 'project_expense';

    protected $fillable = [
        'client_id',
        'product_id',
        'project_id',
        'project_expense_item_id',
        'type',
        'operation_type',
        'loan_days',
        'loan_due_at',
        'amount',
        'balance_after',
        'comment',
        'user_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectExpenseItem(): BelongsTo
    {
        return $this->belongsTo(ProjectExpenseItem::class, 'project_expense_item_id');
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'loan_days' => 'integer',
        'loan_due_at' => 'date',
    ];

    public static function operationTypeLabels(): array
    {
        return [
            self::OPERATION_LOAN => 'Займ',
            self::OPERATION_LOAN_REPAYMENT => 'Возврат займа',
            self::OPERATION_OTHER_INCOME => 'Прочие поступления',
            self::OPERATION_PROJECT_EXPENSE => 'Расход на проект',
        ];
    }

    public function getOperationTypeLabelAttribute(): ?string
    {
        if (!$this->operation_type) {
            return $this->type === 'deposit' ? 'Пополнение' : 'Списание';
        }
        return self::operationTypeLabels()[$this->operation_type] ?? $this->operation_type;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdraw');
    }
}
