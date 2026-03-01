<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotBroadcast;
use App\Models\BotBroadcastTarget;
use App\Models\BotGroup;
use App\Models\BotGroupBan;
use App\Models\BotGroupCommand;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotManagerController extends Controller
{
    // ── Listing ──────────────────────────────────────────────────────────

    public function index()
    {
        $groups = BotGroup::withCount(['commands', 'activeBans'])
            ->orderByDesc('registered_at')
            ->get();

        return view('admin.bot-manager.index', compact('groups'));
    }

    // ── Add group manually ────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|integer',
        ]);

        $chatId = (int) $request->chat_id;

        // Fetch group info from Telegram API
        $info = $this->callTelegramApi('getChat', ['chat_id' => $chatId]);

        if (!($info['ok'] ?? false)) {
            return back()->withErrors(['chat_id' => 'No se pudo obtener información del grupo. Verifica el chat_id y que el bot sea administrador.']);
        }

        $chat = $info['result'];

        BotGroup::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'chat_title'    => $chat['title'] ?? "Grupo {$chatId}",
                'chat_type'     => $chat['type'] ?? 'group',
                'username'      => $chat['username'] ?? null,
                'member_count'  => $chat['member_count'] ?? null,
                'is_active'     => true,
                'settings'      => BotGroup::defaultSettings(),
                'registered_at' => now(),
            ]
        );

        return redirect()->route('admin.bot-manager.index')
            ->with('success', 'Grupo añadido correctamente.');
    }

    // ── Group detail ──────────────────────────────────────────────────────

    public function show(BotGroup $group)
    {
        $group->load(['commands', 'activeBans.bannedBy']);
        $broadcasts = BotBroadcast::with(['targets' => fn($q) => $q->where('bot_group_id', $group->id)])
            ->orderByDesc('created_at')
            ->get();
        return view('admin.bot-manager.show', compact('group', 'broadcasts'));
    }

    // ── Update settings ───────────────────────────────────────────────────

    public function update(Request $request, BotGroup $group)
    {
        $request->validate([
            'auto_delete_links'  => 'boolean',
            'delete_link_action' => 'in:delete_only,delete_and_warn,delete_and_ban',
            'welcome_enabled'    => 'boolean',
            'welcome_message'    => 'nullable|string|max:500',
            'is_active'          => 'boolean',
            'night_mode_enabled'  => 'boolean',
            'night_mode_start'    => 'nullable|date_format:H:i',
            'night_mode_end'      => 'nullable|date_format:H:i',
            'night_mode_timezone' => 'nullable|timezone',
        ]);

        $group->update([
            'is_active' => $request->boolean('is_active', $group->is_active),
            'settings'  => [
                'auto_delete_links'   => $request->boolean('auto_delete_links'),
                'delete_link_action'  => $request->input('delete_link_action', 'delete_only'),
                'welcome_enabled'     => $request->boolean('welcome_enabled'),
                'welcome_message'     => $request->input('welcome_message', BotGroup::defaultSettings()['welcome_message']),
                'night_mode_enabled'  => $request->boolean('night_mode_enabled'),
                'night_mode_start'    => $request->input('night_mode_start', '23:00'),
                'night_mode_end'      => $request->input('night_mode_end', '08:00'),
                'night_mode_timezone' => $request->input('night_mode_timezone', 'Europe/Madrid'),
                'night_mode_active'   => $group->getSetting('night_mode_active', false), // preserve runtime state
            ],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Configuración guardada.');
    }

    // ── Delete group ──────────────────────────────────────────────────────

    public function destroy(BotGroup $group)
    {
        $group->delete();
        return redirect()->route('admin.bot-manager.index')
            ->with('success', 'Grupo eliminado del panel.');
    }

    // ── Commands CRUD ─────────────────────────────────────────────────────

    public function storeCommand(Request $request, BotGroup $group)
    {
        $request->validate([
            'trigger'  => 'required|string|max:100',
            'response' => 'required|string|max:2000',
        ]);

        $group->commands()->create([
            'trigger'   => trim($request->trigger),
            'response'  => $request->response,
            'is_active' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Comando añadido.');
    }

    public function updateCommand(Request $request, BotGroup $group, BotGroupCommand $command)
    {
        $request->validate([
            'trigger'   => 'required|string|max:100',
            'response'  => 'required|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $command->update([
            'trigger'   => trim($request->trigger),
            'response'  => $request->response,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Comando actualizado.');
    }

    public function destroyCommand(BotGroup $group, BotGroupCommand $command)
    {
        $command->delete();
        return back()->with('success', 'Comando eliminado.');
    }

    // ── Bans ──────────────────────────────────────────────────────────────

    public function banUser(Request $request, BotGroup $group)
    {
        $request->validate([
            'telegram_user_id' => 'required|string',
            'reason'           => 'nullable|string|max:255',
        ]);

        $userId = $request->telegram_user_id;

        // Call Telegram API to ban
        $result = $this->callTelegramApi('banChatMember', [
            'chat_id' => $group->chat_id,
            'user_id' => (int) $userId,
        ]);

        if (!($result['ok'] ?? false)) {
            $error = $result['description'] ?? 'Error al banear en Telegram.';
            return back()->withErrors(['telegram_user_id' => $error]);
        }

        // Store ban record
        $group->bans()->create([
            'telegram_user_id'  => $userId,
            'telegram_username' => $request->input('telegram_username'),
            'reason'            => $request->reason,
            'banned_by'         => Auth::id(),
            'banned_at'         => now(),
        ]);

        return back()->with('success', 'Usuario baneado correctamente.');
    }

    public function unbanUser(BotGroup $group, BotGroupBan $ban)
    {
        // Call Telegram API to unban
        $this->callTelegramApi('unbanChatMember', [
            'chat_id'        => $group->chat_id,
            'user_id'        => (int) $ban->telegram_user_id,
            'only_if_banned' => true,
        ]);

        $ban->update(['unbanned_at' => now()]);

        return back()->with('success', 'Usuario desbaneado.');
    }

    // ── Per-group broadcast message ────────────────────────────────────────

    public function sendMessage(Request $request, BotGroup $group)
    {
        $request->validate([
            'message' => 'required|string|max:4096',
        ]);

        $result = $this->callTelegramApi('sendMessage', [
            'chat_id'    => $group->chat_id,
            'text'       => $request->message,
            'parse_mode' => 'Markdown',
        ]);

        if (!($result['ok'] ?? false)) {
            $error = $result['description'] ?? 'Error al enviar el mensaje.';
            return back()->withErrors(['message' => $error]);
        }

        return back()->with('success', 'Mensaje enviado al grupo.');
    }

    // ── Media Broadcasts ──────────────────────────────────────────────────

    public function broadcasts()
    {
        $broadcasts = BotBroadcast::withCount('targets')
            ->orderByDesc('created_at')
            ->get();

        $groups = BotGroup::where('is_active', true)->orderBy('chat_title')->get();

        return view('admin.bot-manager.broadcasts', compact('broadcasts', 'groups'));
    }

    public function sendBroadcast(Request $request, BotBroadcast $broadcast)
    {
        $request->validate([
            'group_ids'   => 'required|array|min:1',
            'group_ids.*' => 'exists:bot_groups,id',
        ]);

        // Remove previous pending targets and recreate with selected groups
        $broadcast->targets()->delete();
        foreach ($request->group_ids as $groupId) {
            BotBroadcastTarget::create([
                'bot_broadcast_id' => $broadcast->id,
                'bot_group_id'     => $groupId,
                'status'           => 'pending',
            ]);
        }

        $this->dispatchBroadcast($broadcast->fresh(['targets.group']));

        return redirect()->route('admin.bot-manager.broadcasts')
            ->with('success', 'Broadcast enviado correctamente.');
    }

    public function scheduleBroadcast(Request $request, BotBroadcast $broadcast)
    {
        $request->validate([
            'group_ids'    => 'required|array|min:1',
            'group_ids.*'  => 'exists:bot_groups,id',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $broadcast->targets()->delete();
        foreach ($request->group_ids as $groupId) {
            BotBroadcastTarget::create([
                'bot_broadcast_id' => $broadcast->id,
                'bot_group_id'     => $groupId,
                'status'           => 'pending',
            ]);
        }

        $broadcast->update([
            'status'       => 'pending',
            'scheduled_at' => $request->scheduled_at,
        ]);

        return redirect()->route('admin.bot-manager.broadcasts')
            ->with('success', 'Broadcast programado para ' . \Carbon\Carbon::parse($request->scheduled_at)->format('d/m/Y H:i') . '.');
    }

    public function destroyBroadcast(BotBroadcast $broadcast)
    {
        $broadcast->delete();
        return back()->with('success', 'Broadcast eliminado.');
    }

    public function sendBroadcastToGroup(BotGroup $group, BotBroadcast $broadcast)
    {
        BotBroadcastTarget::updateOrCreate(
            ['bot_broadcast_id' => $broadcast->id, 'bot_group_id' => $group->id],
            ['status' => 'pending', 'scheduled_at' => null, 'sent_at' => null, 'error' => null]
        );

        $this->dispatchBroadcast($broadcast->fresh(['targets.group']));

        return back()->with('success', 'Broadcast enviado a ' . $group->chat_title . '.');
    }

    public function scheduleToGroup(Request $request, BotGroup $group, BotBroadcast $broadcast)
    {
        $request->validate(['scheduled_at' => 'required|date']);

        // Compensate for browser timezone offset (in minutes, positive = behind UTC)
        $tzOffset   = (int) $request->input('tz_offset', 0); // browser offset in minutes
        $appOffset  = now()->getOffset() / 60;               // server offset in minutes (+60 = UTC+1)
        $diffMinutes = $tzOffset + $appOffset;               // net difference to apply

        $scheduledAt = \Carbon\Carbon::parse($request->scheduled_at)
            ->addMinutes($diffMinutes);

        if ($scheduledAt->isPast()) {
            return back()->withErrors(['scheduled_at' => 'La fecha debe ser futura.']);
        }

        BotBroadcastTarget::updateOrCreate(
            ['bot_broadcast_id' => $broadcast->id, 'bot_group_id' => $group->id],
            ['status' => 'pending', 'scheduled_at' => $scheduledAt, 'sent_at' => null, 'error' => null]
        );

        return back()->with('success', 'Programado para ' . $scheduledAt->format('d/m/Y H:i') . '.');
    }

    public function saveBroadcastTrigger(Request $request, BotGroup $group, BotBroadcast $broadcast)
    {
        $request->validate(['trigger' => 'nullable|string|max:50']);

        $trigger = trim($request->trigger) ?: null;
        $broadcast->update(['trigger' => $trigger]);

        return back()->with('success', $trigger ? "Trigger «{$trigger}» guardado." : 'Trigger eliminado.');
    }

    // ── Internal: dispatch a broadcast now ────────────────────────────────

    public function dispatchBroadcast(BotBroadcast $broadcast): void
    {
        $broadcast->update(['status' => 'sending']);
        $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');

        $allOk = true;
        foreach ($broadcast->targets()->where('status', 'pending')->with('group')->get() as $target) {
            $result = $this->sendMediaToChat($botToken, $target->group->chat_id, $broadcast);

            if ($result['ok'] ?? false) {
                $target->update(['status' => 'sent', 'sent_at' => now()]);
            } else {
                $error = $result['description'] ?? 'Error desconocido';
                $target->update(['status' => 'failed', 'error' => $error]);
                $allOk = false;
                Log::warning("BotManager broadcast target failed", [
                    'broadcast_id' => $broadcast->id,
                    'group_id'     => $target->bot_group_id,
                    'error'        => $error,
                ]);
            }
        }

        $broadcast->update([
            'status'  => $allOk ? 'done' : 'failed',
            'sent_at' => now(),
        ]);
    }

    private function sendMediaToChat(string $botToken, $chatId, BotBroadcast $broadcast): array
    {
        try {
            $response = Http::timeout(30)->post(
                "https://api.telegram.org/bot{$botToken}/{$broadcast->sendMethod()}",
                array_filter([
                    'chat_id'    => $chatId,
                    $broadcast->fileKey() => $broadcast->telegram_file_id,
                    'caption'    => $broadcast->caption,
                    'parse_mode' => 'Markdown',
                ])
            );
            return $response->json() ?? ['ok' => false, 'description' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error("BotManager sendMediaToChat error: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    // ── Telegram API helper ───────────────────────────────────────────────

    private function callTelegramApi(string $method, array $data): array
    {
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            $response = Http::timeout(15)->post(
                "https://api.telegram.org/bot{$botToken}/{$method}",
                $data
            );
            return $response->json() ?? ['ok' => false, 'description' => 'Empty response'];
        } catch (\Exception $e) {
            Log::error("BotManager Telegram API error [{$method}]: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
