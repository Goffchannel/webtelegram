@extends('layouts.app')

@section('title', 'Telegram Bot Settings')

@section('content')
<div class="container">
    <h1>Telegram Bot Settings</h1>

    <form action="{{ route('settings.telegram-bot.update') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="telegram_bot_token" class="form-label">Telegram Bot Token</label>
            <input type="text" class="form-control" id="telegram_bot_token" name="telegram_bot_token" value="{{ old('telegram_bot_token', $settings['telegram_bot_token']) }}">
            @error('telegram_bot_token')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="telegram_bot_username" class="form-label">Telegram Bot Username</label>
            <input type="text" class="form-control" id="telegram_bot_username" name="telegram_bot_username" value="{{ old('telegram_bot_username', $settings['telegram_bot_username']) }}">
            @error('telegram_bot_username')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="telegram_sync_user_id" class="form-label">Telegram Sync User ID</label>
            <input type="number" class="form-control" id="telegram_sync_user_id" name="telegram_sync_user_id" value="{{ old('telegram_sync_user_id', $settings['telegram_sync_user_id']) }}">
            @error('telegram_sync_user_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="vercel_blob_asset_domain" class="form-label">Vercel Blob Asset Domain</label>
            <input type="text" class="form-control" id="vercel_blob_asset_domain" name="vercel_blob_asset_domain" value="{{ old('vercel_blob_asset_domain', $settings['vercel_blob_asset_domain']) }}">
            @error('vercel_blob_asset_domain')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="vercel_blob_read_write_token" class="form-label">Vercel Blob Read Write Token</label>
            <input type="text" class="form-control" id="vercel_blob_read_write_token" name="vercel_blob_read_write_token" value="{{ old('vercel_blob_read_write_token', $settings['vercel_blob_read_write_token']) }}">
            @error('vercel_blob_read_write_token')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="vercel_blob_api_url" class="form-label">Vercel Blob API URL</label>
            <input type="text" class="form-control" id="vercel_blob_api_url" name="vercel_blob_api_url" value="{{ old('vercel_blob_api_url', $settings['vercel_blob_api_url']) }}">
            @error('vercel_blob_api_url')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
@endsection
