<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\BotManagerController;
use App\Models\BotBroadcast;
use Illuminate\Console\Command;

class SendScheduledBroadcasts extends Command
{
    protected $signature = 'bot:send-scheduled-broadcasts';
    protected $description = 'Send scheduled media broadcasts to Telegram groups/channels';

    public function handle(): int
    {
        $due = BotBroadcast::where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->with('targets.group')
            ->get();

        if ($due->isEmpty()) {
            $this->line('No scheduled broadcasts due.');
            return self::SUCCESS;
        }

        $controller = app(BotManagerController::class);

        foreach ($due as $broadcast) {
            $this->info("Dispatching broadcast #{$broadcast->id} (caption: {$broadcast->caption})");
            $controller->dispatchBroadcast($broadcast);
            $this->info("  → Done. Status: {$broadcast->fresh()->status}");
        }

        return self::SUCCESS;
    }
}
