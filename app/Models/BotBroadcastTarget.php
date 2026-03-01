<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotBroadcastTarget extends Model
{
    protected $fillable = [
        'bot_broadcast_id',
        'bot_group_id',
        'status',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(BotBroadcast::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(BotGroup::class, 'bot_group_id');
    }
}
