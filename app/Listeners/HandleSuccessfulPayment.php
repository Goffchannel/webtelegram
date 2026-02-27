<?php

namespace App\Listeners;

use App\Models\Purchase;
use App\Models\Video;
use App\Models\User;
use App\Models\TelegramBot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription as CashierSubscription;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Services\ServiceAccessManager;

class HandleSuccessfulPayment
{
    public function __construct(private readonly ServiceAccessManager $serviceAccessManager)
    {
    }

    /**
     * Handle the webhook received event
     */
    public function handle(WebhookReceived $event): void
    {
        $this->syncCreatorMembershipStatus($event);

        if ($event->payload['type'] === 'checkout.session.completed') {
            $session = $event->payload['data']['object'];
            $metadata = $session['metadata'] ?? [];
            $purchaseType = $metadata['purchase_type'] ?? null;

            if ($purchaseType === 'creator_subscription') {
                $userId = $metadata['user_id'] ?? null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $updates = [
                            'is_creator' => true,
                            'creator_subscription_status' => 'active',
                        ];

                        if (!$user->creator_slug) {
                            $updates['creator_slug'] = \Illuminate\Support\Str::slug($user->name) . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5));
                        }

                        if (!$user->creator_store_name) {
                            $updates['creator_store_name'] = $user->name;
                        }

                        $user->update($updates);
                    }
                }
                return;
            }

            if ($purchaseType !== 'video') {
                return;
            }

            Log::info('Processing successful payment', [
                'session_id' => $session['id'],
                'amount' => $session['amount_total'],
                'metadata' => $metadata
            ]);

            // Extract metadata
            $videoId = $metadata['video_id'] ?? null;
            $telegramUsername = $metadata['telegram_username'] ?? null;

            if (!$videoId || !$telegramUsername) {
                Log::error('Missing required metadata in payment session', [
                    'session_id' => $session['id'],
                    'video_id' => $videoId,
                    'telegram_username' => $telegramUsername
                ]);
                return;
            }

            // Get video
            $video = Video::find($videoId);
            if (!$video) {
                Log::error('Video not found for payment', ['video_id' => $videoId]);
                return;
            }

            // Create or get user (username only for now)
            $user = $this->getOrCreateUser($telegramUsername);

            // Create purchase record
            $purchase = Purchase::firstOrCreate(
                ['stripe_session_id' => $session['id']],
                [
                    'user_id' => $user->id,
                    'video_id' => $video->id,
                    'creator_id' => $video->creator_id,
                    'amount' => ($session['amount_total'] ?? 0) / 100,
                    'currency' => $session['currency'] ?? 'usd',
                    'purchase_status' => 'completed',
                    'verification_status' => 'pending',
                    'delivery_status' => 'pending',
                    'telegram_username' => $telegramUsername,
                ]
            );

            $purchase->loadMissing('video');
            if ($purchase->video && $purchase->video->isServiceProduct()) {
                $this->serviceAccessManager->provisionForPurchase($purchase);
            }

            Log::info('Purchase record created', [
                'purchase_id' => $purchase->id,
                'video_id' => $video->id,
                'telegram_username' => $telegramUsername
            ]);

            // Send activation message instead of delivering video
            $this->sendActivationMessage($telegramUsername, $video);
        }
    }

    private function syncCreatorMembershipStatus(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;

        // Keep creator status fully in sync with Stripe lifecycle events.
        $supportedEvents = [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.payment_failed',
            'invoice.payment_succeeded',
        ];

        if (!in_array($type, $supportedEvents, true)) {
            return;
        }

        try {
            $object = $event->payload['data']['object'] ?? [];
            $subscriptionId = null;
            $subscriptionStatus = null;
            $periodEnd = null;

            if (str_starts_with((string) $type, 'customer.subscription.')) {
                $subscriptionId = $object['id'] ?? null;
                $subscriptionStatus = $object['status'] ?? null;
                $periodEnd = $object['current_period_end'] ?? null;
            } elseif (str_starts_with((string) $type, 'invoice.payment_')) {
                $subscriptionId = $object['subscription'] ?? null;
                $subscriptionStatus = $type === 'invoice.payment_succeeded' ? 'active' : 'past_due';
                $periodEnd = $object['period_end'] ?? null;
            }

            if (!$subscriptionId) {
                return;
            }

            $subscription = CashierSubscription::query()
                ->where('stripe_id', $subscriptionId)
                ->first();

            if (!$subscription || $subscription->name !== 'creator') {
                return;
            }

            $user = User::find($subscription->user_id);
            if (!$user || $user->is_admin) {
                return;
            }

            $isActive = in_array((string) $subscriptionStatus, ['active', 'trialing'], true);

            $updates = [
                'is_creator' => $isActive,
                'creator_subscription_status' => $isActive ? 'active' : 'inactive',
                'creator_subscription_ends_at' => $periodEnd
                    ? Carbon::createFromTimestamp((int) $periodEnd)
                    : null,
            ];

            $user->update($updates);
        } catch (\Throwable $e) {
            Log::warning('Failed to sync creator membership status from Stripe webhook', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getOrCreateUser($telegramUsername)
    {
        // First try to find by username
        $user = User::where('telegram_username', $telegramUsername)->first();

        if ($user) {
            return $user;
        }

        // Create new user with minimal info (Telegram User ID will be linked later)
        // NOTE: Regular users cannot login to admin panel - they only exist for purchase tracking
        return User::create([
            'name' => $telegramUsername,
            'email' => $telegramUsername . '@telegram.placeholder',
            'telegram_username' => $telegramUsername,
            'password' => Hash::make(Str::random(32)), // Random secure password (not admin pattern)
            'is_admin' => false, // Explicitly set as non-admin
            // telegram_user_id will be set when user interacts with bot
        ]);
    }

    private function sendActivationMessage($telegramUsername, $video)
    {
        try {
            // Try to send message to username (this might not work if user hasn't started bot)
            $message = "🎉 *Payment Successful!*\n\n";
            $message .= "✅ Your purchase of *{$video->title}* has been confirmed!\n\n";
            $message .= "🤖 *Next Steps:*\n";
            $bot = TelegramBot::getActiveBot();
            $botUsername = $bot ? $bot->getUsernameWithAt() : '@videotestpowerbot';
            $message .= "1. Start a chat with me: " . $botUsername . "\n";
            $message .= "2. Type /start to activate your purchase\n";
            $message .= "3. I'll deliver your video and set up unlimited access!\n\n";
            $message .= "💡 After activation, use /getvideo {$video->id} anytime to get your video.";

            // Note: This might fail if user hasn't started the bot yet, which is expected
            // The real activation happens when user types /start in the bot
            Telegram::sendMessage([
                'chat_id' => '@' . $telegramUsername,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            Log::info('Activation message sent', [
                'telegram_username' => $telegramUsername,
                'video_id' => $video->id
            ]);
        } catch (\Exception $e) {
            // This is expected - most users won't have started the bot yet
            Log::info('Could not send activation message (expected)', [
                'telegram_username' => $telegramUsername,
                'error' => $e->getMessage(),
                'note' => 'User needs to start bot first'
            ]);
        }
    }

    /**
     * Deliver video to customer via Telegram
     */
    public function deliverVideoToTelegram(Purchase $purchase): void
    {
        try {
            $purchase->markAsRetrying();

            $video = $purchase->video;
            $telegramUsername = $purchase->telegram_username;

            Log::info('Sending purchase confirmation (no auto-delivery)', [
                'purchase_id' => $purchase->id,
                'video_id' => $video->id,
                'telegram_username' => $telegramUsername,
                'attempt' => $purchase->delivery_attempts + 1
            ]);

            // Clean username (remove @ if present)
            $username = ltrim($telegramUsername, '@');

            // Determine chat_id to use
            $chatId = '@' . $username; // Default to username

            // Special case for known users (you can expand this as needed)
            if (strtolower($username) === 'salesmanp2p') {
                $chatId = '5928450281'; // Your specific chat ID
            }

            // Send purchase confirmation with bot instructions (NO VIDEO DELIVERY)
            $confirmationMessage = "✅ *Payment Confirmed!*\n\n";
            $confirmationMessage .= "🎥 **Video:** {$video->title}\n";
            if ($video->description) {
                $confirmationMessage .= "📝 **Description:** {$video->description}\n";
            }
            $confirmationMessage .= "💰 **Amount:** $" . number_format($purchase->amount, 2) . "\n";
            $confirmationMessage .= "🆔 **Video ID:** {$video->id}\n\n";

            $confirmationMessage .= "🤖 **How to access your video:**\n";
            $bot = TelegramBot::getActiveBot();
            $botUsername = $bot ? $bot->getUsernameWithAt() : '@videotestpowerbot';
            $confirmationMessage .= "1. Start a chat with our bot: " . $botUsername . "\n";
            $confirmationMessage .= "2. Use command: `/getvideo {$video->id}`\n";
            $confirmationMessage .= "3. Enjoy unlimited access to your video!\n\n";

            $confirmationMessage .= "📋 Use `/mypurchases` to see all your videos\n";
            $confirmationMessage .= "❓ Use `/help` for assistance";

            $response = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $confirmationMessage,
                'parse_mode' => 'Markdown'
            ]);

            // Mark as delivered (notification sent)
            $purchase->markAsDelivered();

            Log::info('Purchase confirmation sent successfully', [
                'purchase_id' => $purchase->id,
                'video_id' => $video->id,
                'telegram_username' => $telegramUsername,
                'message_id' => $response['message_id'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send purchase confirmation', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $purchase->markAsDeliveryFailed($e->getMessage());
        }
    }

    /**
     * Send video using Telegram file_id (fallback method)
     */
    private function sendVideoByFileId(Video $video, string $chatId, string $caption)
    {
        $videoType = $video->getVideoType();

        switch ($videoType) {
            case 'video':
                return Telegram::sendVideo([
                    'chat_id' => $chatId,
                    'video' => $video->telegram_file_id,
                    'caption' => $caption . "Here's your video! Thank you for your purchase! 🎬",
                    'parse_mode' => 'Markdown'
                ]);

            case 'document':
                return Telegram::sendDocument([
                    'chat_id' => $chatId,
                    'document' => $video->telegram_file_id,
                    'caption' => $caption . "Here's your video file! Thank you for your purchase! 🎬",
                    'parse_mode' => 'Markdown'
                ]);

            case 'animation':
                return Telegram::sendAnimation([
                    'chat_id' => $chatId,
                    'animation' => $video->telegram_file_id,
                    'caption' => $caption . "Here's your video! Thank you for your purchase! 🎬",
                    'parse_mode' => 'Markdown'
                ]);

            default:
                // Fallback to video
                return Telegram::sendVideo([
                    'chat_id' => $chatId,
                    'video' => $video->telegram_file_id,
                    'caption' => $caption . "Here's your video! Thank you for your purchase! 🎬",
                    'parse_mode' => 'Markdown'
                ]);
        }
    }
}
