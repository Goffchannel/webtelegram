<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\BotManagerController;
use App\Models\BotBroadcast;
use App\Models\BotBroadcastTarget;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendScheduledBroadcasts extends Command
{
    protected $signature = 'bot:send-scheduled-broadcasts';
    protected $description = 'Send scheduled media broadcasts to Telegram groups/channels';

    public function handle(): int
    {
        // ── 1. Broadcast-level scheduling (send to all pre-selected groups) ──
        $due = BotBroadcast::where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with('targets.group')
            ->get();

        if ($due->isNotEmpty()) {
            $controller = app(BotManagerController::class);
            foreach ($due as $broadcast) {
                $this->info("Dispatching broadcast #{$broadcast->id}");
                $controller->dispatchBroadcast($broadcast);
            }
        }

        // ── 2. Target-level scheduling (per-group scheduled sends) ───────────
        $dueTargets = BotBroadcastTarget::where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with(['broadcast', 'group'])
            ->get();

        foreach ($dueTargets as $target) {
            if (!$target->group || !$target->broadcast) {
                $target->update(['status' => 'failed', 'error' => 'Group or broadcast not found']);
                continue;
            }

            $broadcast = $target->broadcast;
            $botToken  = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');

            try {
                $response = Http::timeout(30)->post(
                    "https://api.telegram.org/bot{$botToken}/{$broadcast->sendMethod()}",
                    array_filter([
                        'chat_id'             => $target->group->chat_id,
                        $broadcast->fileKey() => $broadcast->telegram_file_id,
                        'caption'             => $broadcast->caption,
                        'parse_mode'          => 'Markdown',
                    ])
                );

                if ($response->json('ok')) {
                    $target->update(['status' => 'sent', 'sent_at' => now()]);
                    $this->info("Sent broadcast #{$broadcast->id} to group {$target->group->chat_title}");
                } else {
                    $error = $response->json('description') ?? 'Unknown error';
                    $target->update(['status' => 'failed', 'error' => $error]);
                    $this->warn("Failed broadcast #{$broadcast->id} → {$target->group->chat_title}: {$error}");
                }
            } catch (\Exception $e) {
                $target->update(['status' => 'failed', 'error' => $e->getMessage()]);
                Log::error("SendScheduledBroadcasts target #{$target->id} error: " . $e->getMessage());
            }
        }

        if ($due->isEmpty() && $dueTargets->isEmpty()) {
            $this->line('No scheduled broadcasts due.');
        }

        return self::SUCCESS;
    }
}
