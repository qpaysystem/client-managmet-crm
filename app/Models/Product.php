<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'photo_path',
        'kind',
        'type',
        'estimated_cost',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
    ];

    public function isPledge(): bool
    {
        return BalanceTransaction::where('product_id', $this->id)
            ->where('operation_type', BalanceTransaction::OPERATION_LOAN)
            ->exists();
    }
}
