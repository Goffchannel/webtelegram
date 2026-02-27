<?php

namespace App\Console\Commands;

use App\Models\PurchaseServiceAccess;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendServiceExpiryReminders extends Command
{
    protected $signature = 'service-access:send-expiry-reminders';
    protected $description = 'Send Telegram reminders 3 days before service access expiration and expire past accesses';

    public function handle(): int
    {
        $expired = PurchaseServiceAccess::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $targetDate = now()->addDays(3)->toDateString();

        $toRemind = PurchaseServiceAccess::with(['purchase', 'video'])
            ->where('status', 'active')
            ->whereDate('expires_at', $targetDate)
            ->whereNull('reminder_sent_at')
            ->get();

        $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
        if (!$botToken || $botToken === 'YOUR-BOT-TOKEN') {
            $this->warn('Telegram bot token not configured.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($toRemind as $access) {
            $chatId = $access->purchase?->telegram_user_id ?: ('@' . ltrim((string) $access->purchase?->telegram_username, '@'));
            if (!$chatId || $chatId === '@') {
                continue;
            }

            $accessUrl = route('service.access.show', $access->access_token);
            $message = "Recordatorio: tu acceso a {$access->video->title} vence en 3 dias ({$access->expires_at->format('Y-m-d')}).\n";
            $message .= "Acceso actual: {$accessUrl}\n";
            $message .= "Para seguir usando el servicio, compra de nuevo antes de que expire.";

            try {
                $response = Http::timeout(20)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);

                if ($response->successful()) {
                    $access->update(['reminder_sent_at' => now()]);
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send service reminder', [
                    'access_id' => $access->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Expired updated: {$expired}. Reminders sent: {$sent}");
        return self::SUCCESS;
    }
}
