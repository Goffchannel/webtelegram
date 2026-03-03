<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\CreatorCheckoutController;
use App\Http\Controllers\CreatorSubscriptionController;
use App\Http\Controllers\ServiceAccessController;
use App\Http\Controllers\IptvController;
use Illuminate\Support\Facades\Auth;

// Customer-facing routes
Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
Route::redirect('/videos', '/')->name('videos.index');
Route::get('/videos/{video}', [VideoController::class, 'show'])->name('video.show');
// Payment routes
Route::get('/payment/{video}/form', [PaymentController::class, 'form'])->name('payment.form');
Route::post('/payment/{video}/process', [PaymentController::class, 'process'])->name('payment.process');
Route::get('/payment/{video}/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/{video}/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
Route::get('/purchase/{uuid}', [PaymentController::class, 'viewPurchase'])->name('purchase.view');
Route::post('/purchase/{uuid}/update-username', [PaymentController::class, 'updateTelegramUsername'])->name('purchase.update-username');
Route::post('/purchase/{uuid}/report', [PaymentController::class, 'reportCreator'])->name('purchase.report-creator');
Route::get('/access/{token}', [ServiceAccessController::class, 'show'])->name('service.access.show');

// IPTV subscriber endpoints (no auth — accessed by Plooplayer app)
// Note: /iptv/channels must be declared BEFORE /iptv/{token} to avoid route conflict.
Route::get('/iptv/channels', [IptvController::class, 'channels'])->name('iptv.channels');
Route::get('/iptv/{token}', [IptvController::class, 'playlist'])->name('iptv.playlist');

// Authentication routes (profile management)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user->is_admin) {
            return redirect()->route('admin.videos.manage');
        }

        if ($user->is_creator && $user->subscribed('creator')) {
            return redirect()->route('creator.dashboard');
        }

        return redirect()->route('categories.index');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User purchases route
    Route::get('/purchases', [PaymentController::class, 'index'])->name('purchases.index');

    // Creator subscription onboarding
    Route::get('/creator/subscription', [CreatorSubscriptionController::class, 'show'])->name('creator.subscription.show');
    Route::post('/creator/subscription/checkout', [CreatorSubscriptionController::class, 'checkout'])->name('creator.subscription.checkout');
    Route::get('/creator/subscription/success', [CreatorSubscriptionController::class, 'success'])->name('creator.subscription.success');
    Route::get('/creator/subscription/portal', [CreatorSubscriptionController::class, 'billingPortal'])->name('creator.subscription.portal');
});

Route::middleware(['auth', 'verified', 'creator'])->group(function () {
    Route::get('/creator/dashboard', [CreatorController::class, 'dashboard'])->name('creator.dashboard');
    Route::post('/creator/profile', [CreatorController::class, 'updateProfile'])->name('creator.profile.update');
    Route::get('/creator/videos', [CreatorController::class, 'videos'])->name('creator.videos');
    Route::post('/creator/videos', [CreatorController::class, 'storeVideo'])->name('creator.videos.store');
    Route::put('/creator/videos/{video}', [CreatorController::class, 'updateVideo'])->name('creator.videos.update');
    Route::delete('/creator/videos/{video}', [CreatorController::class, 'deleteVideo'])->name('creator.videos.delete');
    Route::post('/creator/videos/{video}/service-lines', [CreatorController::class, 'storeServiceLines'])->name('creator.videos.service-lines.store');
    Route::delete('/creator/videos/{video}/service-lines/{line}', [CreatorController::class, 'deleteServiceLine'])->name('creator.videos.service-lines.delete');
    Route::post('/creator/categories', [CreatorController::class, 'storeCategory'])->name('creator.categories.store');
    Route::post('/creator/categories/{category}', [CreatorController::class, 'updateCategory'])->name('creator.categories.update');
    Route::delete('/creator/categories/{category}', [CreatorController::class, 'deleteCategory'])->name('creator.categories.delete');
    Route::get('/creator/purchases', [CreatorController::class, 'purchases'])->name('creator.purchases');
    Route::post('/creator/purchases/{purchase}/approve', [CreatorController::class, 'approvePurchase'])->name('creator.purchases.approve');
    Route::post('/creator/purchases/{purchase}/reject', [CreatorController::class, 'rejectPurchase'])->name('creator.purchases.reject');
    Route::post('/creator/purchases/{purchase}/messages', [CreatorController::class, 'sendMessage'])->name('creator.purchases.messages.send');
    Route::get('/creator/purchases/{purchase}/messages', [CreatorController::class, 'getMessages'])->name('creator.purchases.messages.get');
});

// Admin-only routes
Route::middleware(['auth', 'verified', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    // Admin video management routes
    Route::get('/admin/videos', [VideoController::class, 'manage'])->name('admin.videos.manage');
    Route::put('/admin/videos/{video}', [VideoController::class, 'update'])->name('admin.videos.update');
    Route::delete('/admin/videos/{video}', [VideoController::class, 'destroy'])->name('admin.videos.destroy');
    Route::post('/admin/videos/{video}/test', [VideoController::class, 'testVideo'])->name('admin.videos.test');
    Route::post('/admin/videos/{video}/service-lines', [VideoController::class, 'storeServiceLines'])->name('admin.videos.service-lines.store');
    Route::get('/admin/videos/{video}/service-lines', [VideoController::class, 'serviceLines'])->name('admin.videos.service-lines.show');
    Route::delete('/admin/videos/{video}/service-lines/{line}', [VideoController::class, 'deleteServiceLine'])->name('admin.videos.service-lines.delete');

    // Webhook management
    Route::post('/admin/videos/deactivate-webhook', [VideoController::class, 'deactivateWebhook'])->name('admin.videos.deactivate-webhook');
    Route::post('/admin/videos/reactivate-webhook', [VideoController::class, 'reactivateWebhook'])->name('admin.videos.reactivate-webhook');
    Route::get('/admin/videos/webhook-status', [VideoController::class, 'webhookStatus'])->name('admin.videos.webhook-status');

    // Sync user management
    Route::post('/admin/videos/set-sync-user', [VideoController::class, 'setSyncUser'])->name('admin.videos.set-sync-user');
    Route::post('/admin/videos/remove-sync-user', [VideoController::class, 'removeSyncUser'])->name('admin.videos.remove-sync-user');

    // Token management
    Route::post('/admin/tokens/save-all', [VideoController::class, 'saveAllTokens'])->name('admin.tokens.save-all');

    // Direct Vercel Blob upload endpoint
    Route::post('/admin/videos/direct-upload', [VideoController::class, 'directUpload'])->name('admin.videos.direct-upload');

    // Testing and manual import
    Route::get('/admin/videos/test-connection', [VideoController::class, 'testConnection'])->name('admin.videos.test-connection');
    Route::post('/admin/videos/manual-import', [VideoController::class, 'manualImport'])->name('admin.videos.manual-import');

    // Purchase management routes
    Route::get('/admin/purchases', [\App\Http\Controllers\Admin\PurchaseController::class, 'index'])->name('admin.purchases.index');
    Route::get('/admin/purchases/{purchase}', [\App\Http\Controllers\Admin\PurchaseController::class, 'show'])->name('admin.purchases.show');
    Route::post('/admin/purchases/{purchase}/verify', [\App\Http\Controllers\Admin\PurchaseController::class, 'verify'])->name('admin.purchases.verify');
    Route::post('/admin/purchases/{purchase}/mark-delivered', [\App\Http\Controllers\Admin\PurchaseController::class, 'markDelivered'])->name('admin.purchases.mark-delivered');
    Route::post('/admin/purchases/{purchase}/retry-delivery', [\App\Http\Controllers\Admin\PurchaseController::class, 'retryDelivery'])->name('admin.purchases.retry-delivery');
    Route::post('/admin/purchases/{purchase}/update-notes', [\App\Http\Controllers\Admin\PurchaseController::class, 'updateNotes'])->name('admin.purchases.update-notes');
    Route::post('/admin/purchases/{purchase}/update-username', [\App\Http\Controllers\Admin\PurchaseController::class, 'updateTelegramUsername'])->name('admin.purchases.update-username');
    Route::post('/admin/purchases/fix-stuck-deliveries', [\App\Http\Controllers\Admin\PurchaseController::class, 'fixStuckDeliveries'])->name('admin.purchases.fix-stuck-deliveries');
    Route::post('/admin/reports/{report}/status', [\App\Http\Controllers\Admin\PurchaseController::class, 'updateReportStatus'])->name('admin.reports.update-status');
    Route::post('/admin/reports/{report}/ban-creator', [\App\Http\Controllers\Admin\PurchaseController::class, 'banCreatorFromReport'])->name('admin.reports.ban-creator');
    Route::post('/admin/reports/{report}/delete-creator', [\App\Http\Controllers\Admin\PurchaseController::class, 'deleteCreatorFromReport'])->name('admin.reports.delete-creator');

    // Category management routes
    Route::get('/admin/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('admin.categories.manage');
    Route::get('/admin/categories/{creator}', [\App\Http\Controllers\Admin\CategoryController::class, 'showCreator'])->name('admin.categories.creator');
    Route::post('/admin/categories/{creator}', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('admin.categories.store');
    Route::post('/admin/categories/{creator}/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{creator}/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('admin.categories.destroy');
    Route::post('/admin/categories/{creator}/{category}/toggle-hide', [\App\Http\Controllers\Admin\CategoryController::class, 'toggleHide'])->name('admin.categories.toggle-hide');

    // Telegram Bot Settings routes
    Route::get('/admin/settings/telegram-bot', [\App\Http\Controllers\SettingController::class, 'telegramBot'])->name('settings.telegram-bot');
    Route::post('/admin/settings/telegram-bot', [\App\Http\Controllers\SettingController::class, 'updateTelegramBot'])->name('settings.telegram-bot.update');

    // IPTV admin management
    Route::get('/admin/iptv', [\App\Http\Controllers\Admin\IptvAdminController::class, 'index'])->name('admin.iptv.index');
    Route::post('/admin/iptv/settings', [\App\Http\Controllers\Admin\IptvAdminController::class, 'saveSettings'])->name('admin.iptv.settings');
    Route::post('/admin/iptv/parse', [\App\Http\Controllers\Admin\IptvAdminController::class, 'parseM3u'])->name('admin.iptv.parse');
    Route::post('/admin/iptv/save-channels', [\App\Http\Controllers\Admin\IptvAdminController::class, 'saveChannels'])->name('admin.iptv.save-channels');
    Route::post('/admin/iptv/refresh-token', [\App\Http\Controllers\Admin\IptvAdminController::class, 'refreshToken'])->name('admin.iptv.refresh-token');
    Route::post('/admin/iptv/ban-ip', [\App\Http\Controllers\Admin\IptvAdminController::class, 'banIp'])->name('admin.iptv.ban-ip');
    Route::post('/admin/iptv/unban-ip', [\App\Http\Controllers\Admin\IptvAdminController::class, 'unbanIp'])->name('admin.iptv.unban-ip');
    Route::post('/admin/iptv/clear-log', [\App\Http\Controllers\Admin\IptvAdminController::class, 'clearLog'])->name('admin.iptv.clear-log');

    // Bot Manager — group/channel moderation
    Route::prefix('admin/bot-manager')->name('admin.bot-manager.')->group(function () {
        // ── Static routes FIRST (before /{group} parameter) ──────────────────
        Route::get('/', [\App\Http\Controllers\Admin\BotManagerController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\BotManagerController::class, 'store'])->name('store');

        // Global media broadcasts (must be before /{group} to avoid conflict)
        Route::get('/broadcasts', [\App\Http\Controllers\Admin\BotManagerController::class, 'broadcasts'])->name('broadcasts');
        Route::post('/broadcasts/{broadcast}/send', [\App\Http\Controllers\Admin\BotManagerController::class, 'sendBroadcast'])->name('broadcasts.send');
        Route::post('/broadcasts/{broadcast}/schedule', [\App\Http\Controllers\Admin\BotManagerController::class, 'scheduleBroadcast'])->name('broadcasts.schedule');
        Route::delete('/broadcasts/{broadcast}', [\App\Http\Controllers\Admin\BotManagerController::class, 'destroyBroadcast'])->name('broadcasts.destroy');

        // ── Group parameter routes ────────────────────────────────────────────
        Route::get('/{group}', [\App\Http\Controllers\Admin\BotManagerController::class, 'show'])->name('show');
        Route::put('/{group}', [\App\Http\Controllers\Admin\BotManagerController::class, 'update'])->name('update');
        Route::delete('/{group}', [\App\Http\Controllers\Admin\BotManagerController::class, 'destroy'])->name('destroy');
        Route::post('/{group}/commands', [\App\Http\Controllers\Admin\BotManagerController::class, 'storeCommand'])->name('commands.store');
        Route::put('/{group}/commands/{command}', [\App\Http\Controllers\Admin\BotManagerController::class, 'updateCommand'])->name('commands.update');
        Route::delete('/{group}/commands/{command}', [\App\Http\Controllers\Admin\BotManagerController::class, 'destroyCommand'])->name('commands.destroy');
        Route::post('/{group}/ban', [\App\Http\Controllers\Admin\BotManagerController::class, 'banUser'])->name('ban');
        Route::delete('/{group}/bans/{ban}', [\App\Http\Controllers\Admin\BotManagerController::class, 'unbanUser'])->name('unban');
        Route::post('/{group}/message', [\App\Http\Controllers\Admin\BotManagerController::class, 'sendMessage'])->name('message');
        Route::post('/{group}/send-broadcast/{broadcast}', [\App\Http\Controllers\Admin\BotManagerController::class, 'sendBroadcastToGroup'])->name('broadcasts.send-to-group');
        Route::post('/{group}/schedule-broadcast/{broadcast}', [\App\Http\Controllers\Admin\BotManagerController::class, 'scheduleToGroup'])->name('broadcasts.schedule-to-group');
        Route::patch('/{group}/broadcast-trigger/{broadcast}', [\App\Http\Controllers\Admin\BotManagerController::class, 'saveBroadcastTrigger'])->name('broadcasts.trigger');
        Route::patch('/{group}/broadcast-recurrence/{broadcast}', [\App\Http\Controllers\Admin\BotManagerController::class, 'saveRecurrence'])->name('broadcasts.recurrence');
        Route::post('/{group}/retry-target/{target}', [\App\Http\Controllers\Admin\BotManagerController::class, 'retryTarget'])->name('broadcasts.retry-target');
        Route::delete('/{group}/warnings/{warning}', [\App\Http\Controllers\Admin\BotManagerController::class, 'resetWarning'])->name('warnings.reset');
    });

    // Mi Tienda (admin's own creator store)
    Route::get('/admin/my-store', [\App\Http\Controllers\Admin\MyStoreController::class, 'index'])->name('admin.my-store.index');
    Route::post('/admin/my-store/profile', [\App\Http\Controllers\Admin\MyStoreController::class, 'updateProfile'])->name('admin.my-store.profile.update');

    // Discount codes
    Route::prefix('admin/discount-codes')->name('admin.discount-codes.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'store'])->name('store');
        Route::patch('/{discountCode}/toggle', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'toggle'])->name('toggle');
        Route::delete('/{discountCode}', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'destroy'])->name('destroy');
        Route::post('/validate', [\App\Http\Controllers\Admin\DiscountCodeController::class, 'validate'])->name('validate');
    });

    // Service access: renew / revoke
    Route::post('/admin/purchases/{purchase}/service-access/renew', [\App\Http\Controllers\Admin\PurchaseController::class, 'renewServiceAccess'])->name('admin.purchases.service-access.renew');
    Route::post('/admin/purchases/{purchase}/service-access/revoke', [\App\Http\Controllers\Admin\PurchaseController::class, 'revokeServiceAccess'])->name('admin.purchases.service-access.revoke');
    Route::post('/admin/purchases/{purchase}/service-access/reset-ips', [\App\Http\Controllers\Admin\PurchaseController::class, 'resetBoundIps'])->name('admin.purchases.service-access.reset-ips');
    Route::post('/admin/purchases/{purchase}/messages', [\App\Http\Controllers\Admin\PurchaseController::class, 'sendMessage'])->name('admin.purchases.messages.send');
    Route::get('/admin/purchases/{purchase}/messages', [\App\Http\Controllers\Admin\PurchaseController::class, 'getMessages'])->name('admin.purchases.messages.get');
});

// Telegram webhook (must be accessible without auth)
Route::post('/telegram/webhook', [VideoController::class, 'webhook'])->name('telegram.webhook');

// Bot emulator for local testing
Route::get('/telegram/bot-emulator', [TelegramController::class, 'botEmulator']);
Route::post('/telegram/bot-emulator', [TelegramController::class, 'handleBotEmulator']);
Route::get('/bot-test', [TelegramController::class, 'botEmulator']); // Alias

// System status
Route::get('/system-status', [TelegramController::class, 'systemStatus']);

// One-time migration for categories
Route::get('/run-category-migration', function () {
    try {
        // Check if the categories table already exists to prevent re-running
        if (Schema::hasTable('categories')) {
            return response()->json([
                'status' => 'already_completed',
                'message' => 'Category migration appears to have been completed already.',
            ], 403);
        }

        Artisan::call('migrate', [
            '--path' => 'database/migrations/2025_07_05_100000_create_categories_table_and_add_category_id_to_videos.php',
            '--force' => true
        ]);
        $output = Artisan::output();

        return response()->json([
            'status' => 'success',
            'message' => 'Category migration completed successfully!',
            'output' => $output
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Category migration failed: ' . $e->getMessage(),
        ], 500);
    }
});


// One-time migration and setup route (REMOVE AFTER FIRST USE)
Route::get('/run-migrations-setup-once', function () {
    try {
        // Check if admin user already exists (better check than table existence)
        try {
            if (\App\Models\User::where('email', 'admin@telebot.local')->exists()) {
                return response()->json([
                    'status' => 'already_completed',
                    'message' => 'Database setup completed. Admin user exists. Remove this route for security.',
                    'login_url' => url('/login'),
                    'credentials' => [
                        'email' => 'admin@telebot.local',
                        'password' => 'admin123456'
                    ]
                ], 403);
            }
        } catch (\Exception $checkError) {
            // If checking fails, probably no tables exist yet - continue with setup
        }

        // Method 1: Try migrate:fresh (complete reset) - Works best with Supabase
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $migrationOutput = Artisan::output();
            $migrationMethod = 'migrate:fresh';
        } catch (\Exception $freshError) {
            // Method 2: If fresh fails, try regular migrate
            try {
                Artisan::call('migrate', ['--force' => true]);
                $migrationOutput = Artisan::output();
                $migrationMethod = 'migrate';
            } catch (\Exception $regularError) {
                // Method 3: For Supabase - simple schema reset
                try {
                    // Ensure all existing tables are dropped first
                    Schema::dropAllTables();

                    // Supabase-compatible schema reset (no Neon-specific commands)
                    // Use unprepared to avoid issues with transaction poolers like pgbouncer
                    DB::unprepared('DROP SCHEMA IF EXISTS public CASCADE;');
                    DB::unprepared('CREATE SCHEMA public;');

                    Artisan::call('migrate', ['--force' => true]);
                    $migrationOutput = Artisan::output();
                    $migrationMethod = 'supabase schema reset + migrate';
                } catch (\Exception $resetError) {
                    throw new \Exception("All migration methods failed. Last error: " . $resetError->getMessage());
                }
            }
        }

        // Create admin user
        $adminUser = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@telebot.local',
            'password' => Hash::make('admin123456'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Database setup completed successfully!',
            'method_used' => $migrationMethod,
            'admin_credentials' => [
                'email' => 'admin@telebot.local',
                'password' => 'admin123456',
                'login_url' => url('/login')
            ],
            'migration_output' => $migrationOutput,
            'next_steps' => [
                '1. Visit ' . url('/login') . ' and login with above credentials',
                '2. Change the admin password immediately in profile settings',
                '3. Remove this route from routes/web.php for security',
                '4. Push the updated code to GitHub'
            ],
            'security_note' => 'IMPORTANT: Remove this endpoint after successful setup!'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Setup failed: ' . $e->getMessage(),
            'solutions' => [
                '1. Go to your Supabase dashboard → SQL Editor → Run: DROP SCHEMA IF EXISTS public CASCADE; CREATE SCHEMA public;',
                '2. Or check your Vercel environment variables are using Supabase Transaction Pooler',
                '3. Then try this endpoint again'
            ],
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ],
            'contact' => 'If this persists, verify your Supabase connection details'
        ], 500);
    }
});

// API routes
Route::post('/api/create-payment-intent', [PaymentController::class, 'createPaymentIntent'])->name('api.create-payment-intent');

// Public creator storefront routes (use /store/* to avoid conflicts with /creator/subscription)
Route::get('/store/{creator:creator_slug}', [CreatorController::class, 'storefront'])->name('creator.storefront');
Route::get('/store/{creator:creator_slug}/categories', [CreatorController::class, 'storefront'])->name('creator.storefront.categories');
Route::get('/store/{creator:creator_slug}/categories/{category}', [CreatorController::class, 'storefrontCategory'])->name('creator.storefront.category');
Route::get('/store/{creator:creator_slug}/videos/{video}/checkout', [CreatorCheckoutController::class, 'form'])->name('creator.checkout.form');
Route::post('/store/{creator:creator_slug}/videos/{video}/checkout', [CreatorCheckoutController::class, 'submit'])->name('creator.checkout.submit');
Route::get('/store/{creator:creator_slug}/cart', [\App\Http\Controllers\CreatorCartController::class, 'show'])->name('creator.cart.show');
Route::post('/store/{creator:creator_slug}/cart', [\App\Http\Controllers\CreatorCartController::class, 'checkout'])->name('creator.cart.checkout');
Route::get('/store/{creator:creator_slug}/cart/success', [\App\Http\Controllers\CreatorCartController::class, 'success'])->name('creator.cart.success');
Route::post('/api/discount-codes/validate', [CreatorCheckoutController::class, 'validateDiscount'])->name('discount-codes.validate');

require __DIR__ . '/auth.php';
