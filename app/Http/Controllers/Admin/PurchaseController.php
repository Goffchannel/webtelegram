<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorReport;
use App\Models\Purchase;
use App\Models\PurchaseMessage;
use App\Models\PurchaseServiceAccess;
use App\Models\Setting;
use App\Services\ServiceAccessManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    /**
     * Display a listing of all purchases.
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['video', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('purchase_status')) {
            $query->where('purchase_status', $request->purchase_status);
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        // Search by username, email, uuid, id, or video id
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('telegram_username', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('purchase_uuid', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('video', function ($videoQuery) use ($search) {
                        $videoQuery->where('id', 'like', "%{$search}%");
                    });
            });
        }

        $purchases = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => Purchase::count(),
            'completed' => Purchase::where('purchase_status', 'completed')->count(),
            'pending_verification' => Purchase::where('verification_status', 'pending')->count(),
            'pending_delivery' => Purchase::where('delivery_status', 'pending')->count(),
            'delivered' => Purchase::where('delivery_status', 'delivered')->count(),
            'failed_delivery' => Purchase::where('delivery_status', 'failed')->count(),
            'total_revenue' => Purchase::where('purchase_status', 'completed')->sum('amount'),
            'open_creator_reports' => CreatorReport::whereIn('status', ['open', 'reviewing'])->count(),
        ];

        $creatorReports = CreatorReport::with(['purchase.video', 'creator'])
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.purchases.index', compact('purchases', 'stats', 'creatorReports'));
    }

    /**
     * Show the details of a specific purchase.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['video', 'user', 'serviceAccess', 'messages']);

        // If it's an AJAX request, return partial view for modal
        if (request()->ajax()) {
            return view('admin.purchases.show-modal', compact('purchase'));
        }

        // Otherwise return full page view
        return view('admin.purchases.show', compact('purchase'));
    }

    /**
     * Manually verify a purchase. For service products (IPTV), provisions access automatically.
     * For regular video purchases, links the purchase to a Telegram user ID.
     */
    public function verify(Request $request, Purchase $purchase)
    {
        $isService = $purchase->video?->isServiceProduct();

        $request->validate([
            'telegram_user_id' => $isService ? 'nullable|string' : 'required|string',
        ]);

        try {
            if ($isService) {
                // For IPTV/service products: provision the service access and auto-verify
                app(ServiceAccessManager::class)->provisionForPurchase($purchase);
                $purchase->refresh();
                if ($purchase->verification_status === 'pending') {
                    $purchase->update(['verification_status' => 'verified']);
                }

                Log::info('IPTV purchase manually provisioned by admin', [
                    'purchase_id' => $purchase->id,
                    'admin_user'  => Auth::user()->id ?? 'unknown',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Acceso IPTV aprovisionado y verificado correctamente.',
                ]);
            }

            // Regular video purchase: link to Telegram user
            $purchase->verifyTelegramUser($request->telegram_user_id);

            Log::info('Purchase manually verified by admin', [
                'purchase_id'      => $purchase->id,
                'purchase_uuid'    => $purchase->purchase_uuid,
                'telegram_user_id' => $request->telegram_user_id,
                'admin_user'       => Auth::user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compra verificada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to verify purchase', [
                'purchase_id' => $purchase->id,
                'error'       => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar la compra: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Manually mark delivery as completed.
     */
    public function markDelivered(Request $request, Purchase $purchase)
    {
        $request->validate([
            'delivery_notes' => 'nullable|string|max:500',
        ]);

        try {
            $purchase->markAsDelivered([
                'manual_delivery' => true,
                'admin_user' => Auth::user()->id ?? 'unknown',
                'notes' => $request->delivery_notes,
            ]);

            Log::info('Purchase manually marked as delivered by admin', [
                'purchase_id' => $purchase->id,
                'purchase_uuid' => $purchase->purchase_uuid,
                'admin_user' => Auth::user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Purchase marked as delivered successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark purchase as delivered', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as delivered: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Retry failed delivery.
     */
    public function retryDelivery(Purchase $purchase)
    {
        if (!$purchase->canRetryDelivery()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot retry delivery for this purchase.',
            ]);
        }

        try {
            $purchase->update([
                'delivery_status' => 'pending',
                'delivery_notes' => 'Retry requested by admin at ' . now(),
            ]);

            Log::info('Delivery retry initiated by admin', [
                'purchase_id' => $purchase->id,
                'purchase_uuid' => $purchase->purchase_uuid,
                'admin_user' => Auth::user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery retry initiated successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retry delivery', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry delivery: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update purchase notes.
     */
    public function updateNotes(Request $request, Purchase $purchase)
    {
        $request->validate([
            'delivery_notes' => 'required|string|max:1000',
        ]);

        try {
            $purchase->update([
                'delivery_notes' => $request->delivery_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notes updated successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notes: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the telegram username for a purchase (admin access)
     */
    public function updateTelegramUsername(Request $request, Purchase $purchase)
    {
        $request->validate([
            'telegram_username' => 'required|string|max:255|regex:/^[a-zA-Z0-9_]+$/',
        ]);

        // Clean the username (remove @ if present)
        $username = ltrim($request->telegram_username, '@');
        $oldUsername = $purchase->telegram_username;

        try {
            $purchase->update([
                'telegram_username' => $username,
            ]);

            Log::info('Admin updated purchase telegram username', [
                'purchase_id' => $purchase->id,
                'purchase_uuid' => $purchase->purchase_uuid,
                'old_username' => $oldUsername,
                'new_username' => $username,
                'admin_user' => Auth::user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'username' => $username,
                'message' => 'Telegram username updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update telegram username via admin', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update username: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get purchase statistics for dashboard.
     */
    public function stats()
    {
        $today = now()->startOfDay();
        $week = now()->subWeek();
        $month = now()->subMonth();

        $stats = [
            'today' => [
                'purchases' => Purchase::where('created_at', '>=', $today)->count(),
                'revenue' => Purchase::where('created_at', '>=', $today)
                    ->where('purchase_status', 'completed')->sum('amount'),
            ],
            'week' => [
                'purchases' => Purchase::where('created_at', '>=', $week)->count(),
                'revenue' => Purchase::where('created_at', '>=', $week)
                    ->where('purchase_status', 'completed')->sum('amount'),
            ],
            'month' => [
                'purchases' => Purchase::where('created_at', '>=', $month)->count(),
                'revenue' => Purchase::where('created_at', '>=', $month)
                    ->where('purchase_status', 'completed')->sum('amount'),
            ],
            'pending_actions' => [
                'verification' => Purchase::where('verification_status', 'pending')->count(),
                'delivery' => Purchase::where('delivery_status', 'failed')->count(),
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Fix stuck deliveries (admin tool)
     */
    public function fixStuckDeliveries(Request $request)
    {
        try {
            // Find purchases that are verified but still showing as pending delivery
            $stuckPurchases = Purchase::where('verification_status', 'verified')
                ->where('delivery_status', 'pending')
                ->whereNotNull('telegram_user_id')
                ->where('purchase_status', 'completed')
                ->get();

            $fixed = 0;
            $errors = [];

            foreach ($stuckPurchases as $purchase) {
                try {
                    // If purchase is verified and has telegram_user_id, mark as delivered
                    if ($purchase->telegram_user_id && $purchase->verification_status === 'verified') {
                        $purchase->markAsDelivered([
                            'admin_fix' => true,
                            'fixed_by_admin' => Auth::user()->id,
                            'fix_timestamp' => now()->toISOString(),
                            'fix_reason' => 'Auto-fixed stuck delivery status',
                        ]);
                        $fixed++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Purchase {$purchase->id}: " . $e->getMessage();
                }
            }

            Log::info('Admin fixed stuck deliveries', [
                'admin_user' => Auth::user()->id,
                'fixed_count' => $fixed,
                'errors' => $errors,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Fixed {$fixed} stuck deliveries.",
                'fixed_count' => $fixed,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fix stuck deliveries', [
                'admin_user' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fix deliveries: ' . $e->getMessage(),
            ]);
        }
    }

    public function updateReportStatus(Request $request, CreatorReport $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,reviewing,resolved',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $report->status = $validated['status'];
        $report->admin_notes = $validated['admin_notes'] ?? $report->admin_notes;

        if ($validated['status'] === 'resolved') {
            $report->resolved_by = Auth::id();
            $report->resolved_at = now();
        } else {
            $report->resolved_by = null;
            $report->resolved_at = null;
        }

        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado del reporte actualizado.',
        ]);
    }

    public function banCreatorFromReport(CreatorReport $report)
    {
        $creator = $report->creator;

        if (!$creator) {
            return response()->json([
                'success' => false,
                'message' => 'El creador ya no existe.',
            ], 404);
        }

        $creator->update([
            'is_creator' => false,
            'creator_subscription_status' => 'banned',
        ]);

        $report->update([
            'status' => 'resolved',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
            'admin_notes' => trim(($report->admin_notes ? $report->admin_notes . "\n" : '') . 'Creador baneado por administrador.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Creador baneado correctamente.',
        ]);
    }

    public function deleteCreatorFromReport(CreatorReport $report)
    {
        $creator = $report->creator;

        if (!$creator) {
            return response()->json([
                'success' => false,
                'message' => 'El creador ya no existe.',
            ], 404);
        }

        if ($creator->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta admin.',
            ], 422);
        }

        $creatorName = $creator->name;
        $creator->delete();

        $report->update([
            'status' => 'resolved',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
            'admin_notes' => trim(($report->admin_notes ? $report->admin_notes . "\n" : '') . "Cuenta de creador eliminada: {$creatorName}."),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta del creador eliminada correctamente.',
        ]);
    }

    // =========================================================================
    // Service access: renew / revoke (IPTV subscriptions)
    // =========================================================================

    /**
     * Extend the service access expiry by 30 days from today (or from current expiry
     * if it hasn't expired yet), and set status back to 'active'.
     */
    public function renewServiceAccess(Request $request, Purchase $purchase)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:366',
        ]);

        $access = PurchaseServiceAccess::where('purchase_id', $purchase->id)->first();

        if (!$access) {
            return back()->with('error', 'Esta compra no tiene acceso de servicio asignado.');
        }

        $days    = (int) ($request->input('days', 30));
        $baseDate = ($access->expires_at && $access->expires_at->isFuture())
            ? $access->expires_at
            : now();

        $access->update([
            'expires_at' => $baseDate->addDays($days),
            'status'     => 'active',
        ]);

        Log::info('Service access renewed by admin', [
            'purchase_id' => $purchase->id,
            'admin_id'    => Auth::id(),
            'days'        => $days,
            'new_expiry'  => $access->expires_at,
        ]);

        return back()->with('success', "Acceso renovado por {$days} días. Vence: " . $access->fresh()->expires_at->format('d/m/Y H:i'));
    }

    /**
     * Immediately revoke a subscriber's service access.
     */
    public function revokeServiceAccess(Purchase $purchase)
    {
        $access = PurchaseServiceAccess::where('purchase_id', $purchase->id)->first();

        if (!$access) {
            return back()->with('error', 'Esta compra no tiene acceso de servicio asignado.');
        }

        $access->update([
            'status'     => 'revoked',
            'expires_at' => now(),
        ]);

        Log::info('Service access revoked by admin', [
            'purchase_id' => $purchase->id,
            'admin_id'    => Auth::id(),
        ]);

        return back()->with('success', 'Acceso revocado inmediatamente.');
    }

    /**
     * Reset the bound IPs for a subscriber's IPTV token,
     * allowing them to access from a new device/IP.
     */
    public function resetBoundIps(Purchase $purchase)
    {
        $access = PurchaseServiceAccess::where('purchase_id', $purchase->id)->first();

        if (!$access) {
            return back()->with('error', 'Esta compra no tiene acceso de servicio asignado.');
        }

        $access->update(['bound_ips' => []]);

        Log::info('Service access bound IPs reset by admin', [
            'purchase_id' => $purchase->id,
            'admin_id'    => Auth::id(),
        ]);

        return back()->with('success', 'IPs vinculadas reseteadas. El suscriptor puede acceder desde un nuevo dispositivo.');
    }

    /**
     * Send a Telegram message to the purchase's customer and store it.
     */
    public function sendMessage(Request $request, Purchase $purchase)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        if (!$purchase->telegram_user_id) {
            return response()->json(['success' => false, 'error' => 'El comprador no ha vinculado su Telegram todavía (debe escribir /start al bot).'], 422);
        }

        $text = $request->input('message');
        $senderName = Auth::user()->name . ' (admin)';

        // Send via Telegram Bot API
        $telegramMsgId = null;
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            $response = Http::timeout(15)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id'    => $purchase->telegram_user_id,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]);
            if ($response->successful()) {
                $telegramMsgId = $response->json('result.message_id');
            } else {
                Log::error('Admin message send failed', ['purchase_id' => $purchase->id, 'response' => $response->body()]);
                return response()->json(['success' => false, 'error' => 'Error al enviar el mensaje por Telegram.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Admin message send exception', ['purchase_id' => $purchase->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Error de conexión con Telegram.'], 500);
        }

        $msg = PurchaseMessage::create([
            'purchase_id'        => $purchase->id,
            'sender_type'        => 'admin',
            'sender_name'        => $senderName,
            'message'            => $text,
            'telegram_message_id'=> $telegramMsgId,
        ]);

        Log::info('Admin sent message to customer', ['purchase_id' => $purchase->id, 'admin_id' => Auth::id()]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'          => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_name' => $msg->sender_name,
                'message'     => $msg->message,
                'time'        => $msg->created_at->format('H:i'),
                'created_at'  => $msg->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Return new messages for a purchase (for polling).
     */
    public function getMessages(Request $request, Purchase $purchase)
    {
        $query = $purchase->messages();

        if ($afterId = $request->query('after_id')) {
            $query->where('id', '>', (int) $afterId);
        }

        $messages = $query->get();

        // Mark incoming user messages as read
        $purchase->messages()
            ->where('sender_type', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn($m) => [
                'id'          => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'message'     => $m->message,
                'time'        => $m->created_at->format('H:i'),
                'created_at'  => $m->created_at->toIso8601String(),
            ]),
        ]);
    }
}
