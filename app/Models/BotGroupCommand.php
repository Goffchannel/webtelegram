<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotGroupCommand extends Model
{
    protected $fillable = [
        'bot_group_id',
        'trigger',
        'response',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(BotGroup::class, 'bot_group_id');
    }

    /**
     * Check if the given message text matches this command trigger (case-insensitive).
     */
    public function matches(string $text): bool
    {
        return strcasecmp(trim($text), trim($this->trigger)) === 0;
    }
}
