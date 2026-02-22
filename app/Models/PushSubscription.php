<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'client_id',
        'endpoint',
        'public_key',
        'auth_token',
    ];

    protected $hidden = [
        'auth_token',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
