<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotGroupWarning extends Model
{
    protected $fillable = [
        'bot_group_id',
        'telegram_user_id',
        'telegram_username',
        'count',
        'reason',
        'last_warned_at',
    ];

    protected $casts = [
        'last_warned_at' => 'datetime',
        'count'          => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(BotGroup::class, 'bot_group_id');
    }
}
