<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        return view('admin.bot-manager.show', compact('group'));
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
        ]);

        $group->update([
            'is_active' => $request->boolean('is_active', $group->is_active),
            'settings'  => [
                'auto_delete_links'  => $request->boolean('auto_delete_links'),
                'delete_link_action' => $request->input('delete_link_action', 'delete_only'),
                'welcome_enabled'    => $request->boolean('welcome_enabled'),
                'welcome_message'    => $request->input('welcome_message', BotGroup::defaultSettings()['welcome_message']),
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
            'chat_id'       => $group->chat_id,
            'user_id'       => (int) $ban->telegram_user_id,
            'only_if_banned' => true,
        ]);

        $ban->update(['unbanned_at' => now()]);

        return back()->with('success', 'Usuario desbaneado.');
    }

    // ── Broadcast message ─────────────────────────────────────────────────

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
