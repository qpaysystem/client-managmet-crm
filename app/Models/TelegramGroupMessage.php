<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramGroupMessage extends Model
{
    protected $fillable = [
        'chat_id',
        'message_id',
        'from_user_id',
        'from_username',
        'from_first_name',
        'text',
        'message_date',
    ];

    protected $casts = [
        'message_date' => 'datetime',
    ];
}
