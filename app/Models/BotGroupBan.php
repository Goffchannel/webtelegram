<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotGroupBan extends Model
{
    protected $fillable = [
        'bot_group_id',
        'telegram_user_id',
        'telegram_username',
        'reason',
        'banned_by',
        'banned_at',
        'unbanned_at',
    ];

    protected $casts = [
        'banned_at'   => 'datetime',
        'unbanned_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(BotGroup::class, 'bot_group_id');
    }

    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function isActive(): bool
    {
        return $this->unbanned_at === null;
    }
}
