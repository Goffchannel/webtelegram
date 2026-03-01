<?php

namespace App\Console\Commands;

use App\Models\BotGroup;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToggleNightMode extends Command
{
    protected $signature = 'bot:toggle-night-mode';
    protected $description = 'Enable/disable chat permissions based on night mode schedule';

    public function handle(): int
    {
        $groups = BotGroup::where('is_active', true)->get()
            ->filter(fn($g) => $g->getSetting('night_mode_enabled'));

        foreach ($groups as $group) {
            $start    = $group->getSetting('night_mode_start', '23:00');
            $end      = $group->getSetting('night_mode_end', '08:00');
            $isNight  = $this->isNightTime($start, $end);
            $wasNight = (bool) $group->getSetting('night_mode_active', false);

            if ($isNight && !$wasNight) {
                // ── Activate night mode ──────────────────────────────────────
                $result = $this->setChatPermissions($group->chat_id, false);
                if ($result['ok'] ?? false) {
                    $this->updateNightModeState($group, true);
                    $this->sendMessage(
                        $group->chat_id,
                        "🌙 *Modo noche activado*\nLos mensajes están deshabilitados hasta las *{$end}*. Hasta mañana!"
                    );
                    $this->info("Night mode ON → {$group->chat_title}");
                } else {
                    $this->warn("Failed to enable night mode for {$group->chat_title}: " . ($result['description'] ?? '?'));
                }
            } elseif (!$isNight && $wasNight) {
                // ── Deactivate night mode ────────────────────────────────────
                $result = $this->setChatPermissions($group->chat_id, true);
                if ($result['ok'] ?? false) {
                    $this->updateNightModeState($group, false);
                    $this->sendMessage(
                        $group->chat_id,
                        "☀️ *Buenos días!*\nEl modo noche ha finalizado. Ya podéis escribir."
                    );
                    $this->info("Night mode OFF → {$group->chat_title}");
                } else {
                    $this->warn("Failed to disable night mode for {$group->chat_title}: " . ($result['description'] ?? '?'));
                }
            }
        }

        return self::SUCCESS;
    }

    private function isNightTime(string $start, string $end): bool
    {
        $now     = now();
        $current = $now->hour * 60 + $now->minute;

        [$sh, $sm] = explode(':', $start);
        [$eh, $em] = explode(':', $end);
        $startMin = (int)$sh * 60 + (int)$sm;
        $endMin   = (int)$eh * 60 + (int)$em;

        // Overnight window (e.g. 23:00 → 08:00) vs same-day window
        return $startMin > $endMin
            ? ($current >= $startMin || $current < $endMin)
            : ($current >= $startMin && $current < $endMin);
    }

    private function setChatPermissions(int $chatId, bool $allowed): array
    {
        $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');

        $permissions = [
            'can_send_messages'         => $allowed,
            'can_send_audios'           => $allowed,
            'can_send_documents'        => $allowed,
            'can_send_photos'           => $allowed,
            'can_send_videos'           => $allowed,
            'can_send_video_notes'      => $allowed,
            'can_send_voice_notes'      => $allowed,
            'can_send_polls'            => $allowed,
            'can_send_other_messages'   => $allowed,
            'can_add_web_page_previews' => $allowed,
        ];

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$botToken}/setChatPermissions",
                [
                    'chat_id'     => $chatId,
                    'permissions' => json_encode($permissions),
                ]
            );
            return $response->json() ?? ['ok' => false];
        } catch (\Exception $e) {
            Log::error("ToggleNightMode setChatPermissions error: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    private function sendMessage(int $chatId, string $text): void
    {
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            Http::timeout(10)->post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown']
            );
        } catch (\Exception $e) {
            Log::error("ToggleNightMode sendMessage error: " . $e->getMessage());
        }
    }

    private function updateNightModeState(BotGroup $group, bool $active): void
    {
        $settings                     = $group->settings ?? [];
        $settings['night_mode_active'] = $active;
        $group->update(['settings' => $settings]);
    }
}
