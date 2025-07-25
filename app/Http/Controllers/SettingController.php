<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function telegramBot()
    {
        $settings = [
            'telegram_bot_token' => Setting::get('telegram_bot_token'),
            'telegram_bot_username' => Setting::get('telegram_bot_username'),
            'telegram_sync_user_id' => Setting::get('telegram_sync_user_id'),
            'vercel_blob_asset_domain' => Setting::get('vercel_blob_asset_domain'),
            'vercel_blob_read_write_token' => Setting::get('vercel_blob_read_write_token'),
            'vercel_blob_api_url' => Setting::get('vercel_blob_api_url'),
        ];

        return view('admin.settings.telegram-bot', compact('settings'));
    }

    public function updateTelegramBot(Request $request)
    {
        $validated = $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_bot_username' => 'nullable|string|max:255',
            'telegram_sync_user_id' => 'nullable|integer',
            'vercel_blob_asset_domain' => 'nullable|string|max:255',
            'vercel_blob_read_write_token' => 'nullable|string|max:255',
            'vercel_blob_api_url' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Telegram bot settings updated successfully!');
    }
}
