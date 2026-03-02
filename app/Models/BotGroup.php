<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotGroup extends Model
{
    protected $fillable = [
        'chat_id',
        'chat_title',
        'chat_type',
        'username',
        'is_active',
        'member_count',
        'settings',
        'registered_at',
    ];

    protected $casts = [
        'chat_id'       => 'integer',
        'is_active'     => 'boolean',
        'member_count'  => 'integer',
        'settings'      => 'json',
        'registered_at' => 'datetime',
    ];

    public function commands(): HasMany
    {
        return $this->hasMany(BotGroupCommand::class);
    }

    public function bans(): HasMany
    {
        return $this->hasMany(BotGroupBan::class)->orderByDesc('banned_at');
    }

    public function activeBans(): HasMany
    {
        return $this->hasMany(BotGroupBan::class)->whereNull('unbanned_at');
    }

    public function isUserBanned(string $telegramUserId): bool
    {
        return $this->activeBans()->where('telegram_user_id', $telegramUserId)->exists();
    }

    /**
     * Find a matching active command for the given text (case-insensitive).
     */
    public function matchCommand(string $text): ?BotGroupCommand
    {
        $text = trim($text);
        return $this->commands()
            ->where('is_active', true)
            ->get()
            ->first(fn(BotGroupCommand $cmd) => $cmd->matches($text));
    }

    /**
     * Default settings applied to new groups.
     */
    public static function defaultSettings(): array
    {
        return [
            'auto_delete_links'  => false,
            'delete_link_action' => 'delete_only',
            'welcome_enabled'    => false,
            'welcome_message'    => '¡Bienvenido/a {nombre} al grupo {grupo}! 👋',
            'night_mode_enabled'  => false,
            'night_mode_start'    => '23:00',
            'night_mode_end'      => '08:00',
            'night_mode_timezone' => 'Europe/Madrid',
            'night_mode_active'   => false,
            // Blacklist
            'blacklist_enabled'   => false,
            'blacklist_words'     => [],
            'blacklist_action'    => 'delete_only',
            // Anti-flood
            'antiflood_enabled'      => false,
            'antiflood_max_messages' => 5,
            'antiflood_seconds'      => 10,
            'antiflood_action'       => 'mute',
            // Warnings
            'warn_before_ban'    => false,
            'max_warnings'       => 3,
        ];
    }

    /**
     * Get a setting value with fallback to default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = array_merge(self::defaultSettings(), $this->settings ?? []);
        return $settings[$key] ?? $default;
    }
}
