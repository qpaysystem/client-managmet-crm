<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCustomValue extends Model
{
    protected $table = 'client_custom_values';

    protected $fillable = ['client_id', 'custom_field_id', 'value'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
