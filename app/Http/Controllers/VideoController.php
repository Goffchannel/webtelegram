<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Setting;
use App\Models\TelegramBot;
use App\Models\Category;
use App\Models\BotBroadcast;
use App\Models\BotGroup;
use App\Models\BotGroupBan;
use App\Models\BotGroupWarning;
use App\Models\Purchase;
use Illuminate\Support\Facades\Cache;
use App\Models\PurchaseMessage;
use App\Models\User;
use App\Models\ServiceAccessLine;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use VercelBlobPhp\Client as BlobClient;
use VercelBlobPhp\CommonCreateBlobOptions;

class VideoController extends Controller
{
    /**
     * Display a listing of videos for customers.
     */
    public function index()
    {
        $videos = Video::orderBy('created_at', 'desc')->paginate(12);
        return view('videos.index', compact('videos'));
    }

    /**
     * Show the video detail page.
     */
    public function show(Video $video)
    {
        return view('videos.show', compact('video'));
    }

    private function resolveDefaultCategoryId(?User $creatorUser): ?int
    {
        if (!$creatorUser) {
            return null;
        }

        $category = Category::firstOrCreate(
            [
                'creator_id' => $creatorUser->id,
                'name' => 'General',
            ],
            [
                'creator_id' => $creatorUser->id,
                'name' => 'General',
            ]
        );

        return $category->id;
    }

    private function generateUniqueCreatorSlug(string $name, int $userId): string
    {
        $base = Str::slug($name) ?: 'adminuser';
        $slug = $base;
        $i = 1;

        while (User::where('creator_slug', $slug)->where('id', '!=', $userId)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Admin: Display captured videos for management.
     */
    public function manage()
    {
        try {
            $videos = Video::with(['category', 'creator'])->orderBy('created_at', 'desc')->paginate(15);
            $videos->loadCount('availableServiceLines');
            $categories = Category::with('creator')->orderBy('name')->get();

            // Initialize $bot array
            $bot = [
                'is_configured' => false,
                'url' => '#',
            ];

            // Get webhook status
            $isWebhookActive = false;
            $webhookUrl = '';

            // Get tokens from settings
            $telegramToken = Setting::get('telegram_bot_token');
            $telegramBotUsername = Setting::get('telegram_bot_username');
            $stripeKey = Setting::get('stripe_key');
            $stripeSecret = Setting::get('stripe_secret');
            $stripeWebhookSecret = Setting::get('stripe_webhook_secret');
            $creatorMonthlyPriceUsd = Setting::get('creator_monthly_price_usd', 5.00);
            $vercelBlobToken = Setting::get('vercel_blob_token');

            // Get new Vercel Blob settings (simple, just like the other settings)
            $vercelBlobStoreId = Setting::get('vercel_blob_store_id');
            $vercelBlobBaseUrl = Setting::get('vercel_blob_base_url');

            try {
                $botToken = $telegramToken ?: config('telegram.bots.mybot.token');
                if ($botToken && $botToken !== 'YOUR-BOT-TOKEN') {
                    $response = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
                    if ($response->successful()) {
                        $webhookInfo = $response->json();
                        $isWebhookActive = !empty($webhookInfo['result']['url']);
                        $webhookUrl = $webhookInfo['result']['url'] ?? '';
                    }

                    // Update $bot array with configuration status and URL
                    if ($telegramBotUsername) {
                        $bot['is_configured'] = true;
                        $bot['url'] = "https://t.me/{$telegramBotUsername}";
                    }
                }
            } catch (Exception $e) {
                Log::warning('Failed to get webhook status: ' . $e->getMessage());
            }

            return view('admin.videos.manage', compact(
                'videos',
                'categories',
                'isWebhookActive',
                'webhookUrl',
                'telegramToken',
                'stripeKey',
                'stripeSecret',
                'stripeWebhookSecret',
                'creatorMonthlyPriceUsd',
                'vercelBlobToken',
                'vercelBlobStoreId',
                'vercelBlobBaseUrl',
                'bot' // Pass the $bot variable to the view
            ));
        } catch (Exception $e) {
            Log::error('Error loading admin videos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load videos: ' . $e->getMessage());
        }
    }

    /**
     * Admin: Update video details.
     */
    public function update(Request $request, Video $video)
    {
        try {
            // ✅ FIX: Use correct Laravel 12 content type detection methods
            $isJsonRequest = $request->isJson() ||
                            $request->expectsJson() ||
                            $request->header('Content-Type') === 'application/json' ||
                            str_contains($request->header('Content-Type', ''), 'application/json');

            // Handle JSON input by merging it with the request
            if ($isJsonRequest) {
                $jsonData = json_decode($request->getContent(), true);
                if ($jsonData && is_array($jsonData)) {
                    $request->merge($jsonData);
                }
            }

            // Log incoming request for debugging (but limit size to prevent memory issues)
            $requestData = $request->all();
            if (count($requestData) > 20) {
                $requestData = array_slice($requestData, 0, 20, true);
                $requestData['_truncated'] = true;
            }

            Log::info('Video update request', [
                'video_id' => $video->id,
                'request_data' => $requestData,
                'content_type' => $request->header('Content-Type'),
                'is_json' => $isJsonRequest,
                'request_size' => strlen($request->getContent())
            ]);

            // Enhanced validation with better error handling
            try {
                $validationRules = [
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string|max:2000',
                    'long_description' => 'nullable|string|max:5000',
                    'fan_message' => 'nullable|string|max:5000',
                    'access_instructions' => 'nullable|string|max:5000',
                    'price' => 'required|numeric|min:0|max:9999.99',
                    'product_type' => 'nullable|in:video,service_access',
                    'duration_days' => 'nullable|integer|min:1|max:365',
                    'category_id' => 'required|exists:categories,id',
                    'thumbnail_url' => 'nullable|url|max:500',
                    'thumbnail_blob_url' => 'nullable|url|max:500',
                    'blur_intensity' => 'nullable|integer|min:1|max:20',
                    'show_blurred' => 'nullable|boolean',
                    'allow_preview' => 'nullable|boolean',
                ];

                // Only validate file if it's actually present and not empty
                if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
                    $validationRules['thumbnail'] = 'file|image|mimes:jpeg,png,jpg,gif|max:2048';
                }

                $validated = $request->validate($validationRules);

            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Validation failed', ['errors' => $e->errors(), 'video_id' => $video->id]);

                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors()
                    ], 422);
                } else {
                    // Traditional form submission - redirect back with errors
                    return redirect()->route('admin.videos.manage')
                        ->withErrors($e->errors())
                        ->withInput()
                        ->with('error', 'Validation failed. Please check your input.');
                }
            }

            // Prepare update data
            $updateData = [];

            // Basic fields
            if ($request->has('title')) $updateData['title'] = $request->input('title');
            if ($request->has('description')) $updateData['description'] = $request->input('description');
            if ($request->has('long_description')) $updateData['long_description'] = $request->input('long_description');
            if ($request->has('fan_message')) $updateData['fan_message'] = $request->input('fan_message');
            if ($request->has('access_instructions')) $updateData['access_instructions'] = $request->input('access_instructions');
            if ($request->has('price')) $updateData['price'] = (float) $request->input('price');
            if ($request->has('product_type')) $updateData['product_type'] = $request->input('product_type');
            if ($request->has('duration_days')) $updateData['duration_days'] = (int) $request->input('duration_days');
            if ($request->has('category_id')) $updateData['category_id'] = (int) $request->input('category_id');
            if ($request->has('blur_intensity')) $updateData['blur_intensity'] = (int) $request->input('blur_intensity');

            $hasExternalThumbnailUrl = $request->has('thumbnail_url') && !empty($request->input('thumbnail_url'));

            // External URL should override blob/local thumbnail values
            if ($hasExternalThumbnailUrl) {
                $updateData['thumbnail_url'] = $request->input('thumbnail_url');
                $updateData['thumbnail_path'] = null;
                $updateData['thumbnail_blob_url'] = null;
            }

            // Boolean fields with proper conversion
            if ($request->has('show_blurred')) {
                $showBlurred = $request->input('show_blurred');
                $updateData['show_blurred_thumbnail'] = ($showBlurred === true || $showBlurred === 1 || $showBlurred === '1') ? 1 : 0;
            }

            if ($request->has('allow_preview')) {
                $allowPreview = $request->input('allow_preview');
                $updateData['allow_preview'] = ($allowPreview === true || $allowPreview === 1 || $allowPreview === '1') ? 1 : 0;
            }

        // Handle direct Vercel Blob upload (from JavaScript direct upload)
        if (!$hasExternalThumbnailUrl && $request->has('thumbnail_blob_url') && !empty($request->input('thumbnail_blob_url'))) {
            try {
                $blobUrl = $request->input('thumbnail_blob_url');

                // Validate that this is a valid Vercel Blob URL using configured base URL
                $configuredBaseUrl = Setting::get('vercel_blob_base_url');
                if ($configuredBaseUrl) {
                    $configuredHost = parse_url($configuredBaseUrl, PHP_URL_HOST);
                    if (!str_contains($blobUrl, $configuredHost)) {
                        throw new \Exception('Invalid blob URL - not from configured Vercel Blob storage');
                    }
                } else {
                    // Fallback to hardcoded validation for backward compatibility
                    if (!str_contains($blobUrl, '.public.blob.vercel-storage.com')) {
                        throw new \Exception('Invalid blob URL - not from Vercel Blob storage');
                    }
                }

                // Delete old thumbnail from Vercel Blob if exists
                if ($video->thumbnail_blob_url) {
                    try {
                        if (class_exists('VercelBlobPhp\Client')) {
                            $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
                            $blobClient = new BlobClient($blobToken);
                            $blobClient->del([$video->thumbnail_blob_url]);
                            Log::info('Old thumbnail deleted from Vercel Blob', ['url' => $video->thumbnail_blob_url]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old thumbnail from Vercel Blob', ['error' => $e->getMessage()]);
                    }
                }

                // Extract the path from the blob URL for storage
                $parsedUrl = parse_url($blobUrl);
                $thumbnailPath = ltrim($parsedUrl['path'], '/');

                $updateData['thumbnail_blob_url'] = $blobUrl;
                $updateData['thumbnail_path'] = $thumbnailPath;
                $updateData['thumbnail_url'] = null;

                Log::info('Direct blob upload processed', [
                    'blob_url' => $blobUrl,
                    'thumbnail_path' => $thumbnailPath
                ]);

            } catch (\Exception $e) {
                Log::error('Direct blob URL processing error', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing blob URL: ' . $e->getMessage()
                ]);
            }
        }
        // Handle traditional file upload ONLY if file is actually uploaded and valid
        elseif (!$hasExternalThumbnailUrl && $request->hasFile('thumbnail') && $request->file('thumbnail')->isValid() && $request->file('thumbnail')->getSize() > 0) {
            try {
                $thumbnailFile = $request->file('thumbnail');
                $extension = $thumbnailFile->getClientOriginalExtension();

                if (empty($extension)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not determine file extension.'
                    ]);
                }

                $thumbnailName = "thumbnails/" . time() . '_' . $video->id . '.' . $extension;

                Log::info('Processing thumbnail upload', [
                    'original_name' => $thumbnailFile->getClientOriginalName(),
                    'extension' => $extension,
                    'size' => $thumbnailFile->getSize(),
                    'mime_type' => $thumbnailFile->getMimeType(),
                    'new_name' => $thumbnailName
                ]);

                // Delete old thumbnail from Vercel Blob if exists
                if ($video->thumbnail_path && $video->thumbnail_blob_url) {
                    try {
                        // Check if Vercel Blob classes are available
                        if (!class_exists('VercelBlobPhp\Client')) {
                            throw new \Exception('Vercel Blob package not available');
                        }

                        $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
                        $blobClient = new BlobClient($blobToken);
                        // Use the full blob URL for deletion (del method expects URLs, not paths)
                        $blobClient->del([$video->thumbnail_blob_url]);
                        Log::info('Old thumbnail deleted from Vercel Blob', ['url' => $video->thumbnail_blob_url]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to delete old thumbnail from Vercel Blob', ['error' => $e->getMessage()]);
                    }
                }

                // Check if Vercel Blob token is available (settings first, then environment)
                $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
                if (empty($blobToken)) {
                    throw new \Exception('Vercel Blob token not configured in admin settings');
                }

                // Check if Vercel Blob classes are available before upload
                if (!class_exists('VercelBlobPhp\Client') || !class_exists('VercelBlobPhp\CommonCreateBlobOptions')) {
                    throw new \Exception('Vercel Blob package not available. Please run composer install.');
                }

                // Store new thumbnail to Vercel Blob directly (read from stream)
                $blobClient = new BlobClient($blobToken);

                // Read file content directly from upload stream to avoid filesystem issues
                $thumbnailContent = $thumbnailFile->get();

                if (empty($thumbnailContent)) {
                    throw new \Exception('Failed to read thumbnail file content');
                }

                $options = new CommonCreateBlobOptions(
                    access: 'public',
                    addRandomSuffix: false,
                    contentType: $thumbnailFile->getMimeType() ?: 'image/jpeg',
                );

                Log::info('Attempting to upload to Vercel Blob', [
                    'filename' => $thumbnailName,
                    'size' => strlen($thumbnailContent),
                    'content_type' => $thumbnailFile->getMimeType(),
                    'token_exists' => !empty($blobToken)
                ]);

                $result = $blobClient->put($thumbnailName, $thumbnailContent, $options);

                if (!$result || !isset($result->url)) {
                    throw new \Exception('Vercel Blob upload failed - no URL returned');
                }

                $publicUrl = $result->url;

                $updateData['thumbnail_path'] = $thumbnailName;
                $updateData['thumbnail_blob_url'] = $publicUrl;
                $updateData['thumbnail_url'] = null;

                Log::info('Thumbnail uploaded successfully to Vercel Blob', [
                    'stored_as' => $thumbnailName,
                    'url' => $publicUrl
                    ]);

            } catch (\Exception $e) {
                Log::error('Thumbnail upload error', ['error' => $e->getMessage()]);

                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error uploading thumbnail: ' . $e->getMessage()
                    ]);
                } else {
                    return redirect()->route('admin.videos.manage')
                        ->with('error', 'Error uploading thumbnail: ' . $e->getMessage());
                }
            }
        }

            // Update the video
            $video->update($updateData);

            Log::info('Video updated successfully', ['video_id' => $video->id, 'updates' => array_keys($updateData)]);

            // Return appropriate response based on request type
            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Video updated successfully!',
                    'video' => $video->fresh()
                ]);
            } else {
                // Traditional form submission - redirect with success message
                return redirect()->route('admin.videos.manage')
                    ->with('success', 'Video updated successfully!');
            }

        } catch (\Exception $e) {
            Log::error('Video update failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return appropriate error response based on request type
            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update video: ' . $e->getMessage()
                ], 500);
            } else {
                // Traditional form submission - redirect with error message
                return redirect()->route('admin.videos.manage')
                    ->with('error', 'Failed to update video: ' . $e->getMessage());
            }
        }
    }

    /**
     * Admin: Delete a video.
     */
    public function destroy(Video $video)
    {
        // Delete thumbnail from Vercel Blob if exists
        if ($video->thumbnail_blob_url) {
            try {
                // Check if Vercel Blob classes are available
                if (!class_exists('VercelBlobPhp\Client')) {
                    Log::warning('Vercel Blob package not available for deletion');
                } else {
                    $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
                    $blobClient = new BlobClient($blobToken);
                    // Use the full blob URL for deletion (del method expects URLs, not paths)
                    $blobClient->del([$video->thumbnail_blob_url]);
                    Log::info('Thumbnail deleted from Vercel Blob', ['video_id' => $video->id, 'url' => $video->thumbnail_blob_url]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete thumbnail from Vercel Blob', ['error' => $e->getMessage()]);
            }
        }

        // Delete local thumbnail if exists (fallback for old videos)
        if ($video->thumbnail_path && !$video->thumbnail_blob_url) {
            $thumbnailPath = storage_path('app/public/thumbnails/' . $video->thumbnail_path);
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
                Log::info('Local thumbnail deleted with video', ['video_id' => $video->id, 'path' => $thumbnailPath]);
            }
        }

        $video->delete();
        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully!'
        ]);
    }

    /**
     * Admin: Test video by sending to sync user.
     */
    public function testVideo(Video $video)
    {
        $syncUserTelegramId = Setting::get('sync_user_telegram_id');
        $syncUserName = Setting::get('sync_user_name');

        if (!$syncUserTelegramId) {
            return response()->json([
                'success' => false,
                'error' => 'No sync user configured.'
            ]);
        }

        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            if (!$botToken || $botToken === 'YOUR-BOT-TOKEN') {
                return response()->json([
                    'success' => false,
                    'error' => 'Bot token not configured'
                ]);
            }

            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendVideo", [
                'chat_id' => $syncUserTelegramId,
                'video' => $video->telegram_file_id,
                'caption' => "🧪 Test Video\n\n📹 {$video->title}\n💰 Precio: $" . number_format($video->price, 2) . "\n🆔 ID: {$video->id}"
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test video sent successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to send video'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Test video error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to send test video'
            ]);
        }
    }

    /**
     * Admin: Set sync user for testing.
     */
    public function setSyncUser(Request $request)
    {
        try {
            $telegramId = $request->input('telegram_id');
            $name = $request->input('name');

            if (empty($telegramId) || empty($name)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Both Telegram ID and name are required'
                ]);
            }

            Setting::set('sync_user_telegram_id', $telegramId);
            Setting::set('sync_user_name', $name);

            return response()->json([
                'success' => true,
                'message' => 'Sync user configured successfully!'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to set sync user', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Admin: Remove sync user.
     */
    public function removeSyncUser()
    {
        try {
            Setting::where('key', 'sync_user_telegram_id')->delete();
            Setting::where('key', 'sync_user_name')->delete();
            Setting::where('key', 'restrict_to_sync_user')->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sync user removed successfully!'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to remove sync user', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle bot restriction to sync user only
     */
    public function toggleBotRestriction(Request $request)
    {
        try {
            $restrict = $request->input('restrict_to_sync_user', false);

            Setting::set('restrict_to_sync_user', $restrict);

            $message = $restrict
                ? 'Bot is now restricted to sync user only'
                : 'Bot restriction removed - anyone can message the bot';

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle bot restriction failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update bot restriction: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save all API tokens at once
     */
    public function saveAllTokens(Request $request)
    {
        try {
            $tokens = $request->all();
            $savedTokens = [];
            $errors = [];

            // Validate and save Telegram token
            if (isset($tokens['telegram_token'])) {
                $token = trim($tokens['telegram_token']);
                if (!empty($token)) {
                    // Basic validation - Telegram bot tokens should follow pattern: digits:letters/digits
                    if (!preg_match('/^\d+:[A-Za-z0-9_-]+$/', $token)) {
                        $errors[] = 'Invalid Telegram token format. Should be: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz';
                    } else {
                        try {
                            // Create/update bot in database with fetched info from Telegram API
                            $botService = new TelegramBotService();
                            $bot = $botService->setupBotFromToken($token);

                            // Also save to settings for backward compatibility
                        Setting::set('telegram_bot_token', $token);
                            $savedTokens[] = "Telegram Bot Token (@{$bot->username})";
                        } catch (\Exception $e) {
                            $errors[] = 'Failed to setup bot: ' . $e->getMessage();
                        }
                    }
                }
            }

            // Validate and save Stripe publishable key
            if (isset($tokens['stripe_key'])) {
                $key = trim($tokens['stripe_key']);
                if (!empty($key)) {
                    if (!str_starts_with($key, 'pk_')) {
                        $errors[] = 'Invalid Stripe publishable key format. Should start with pk_';
                    } else {
                        Setting::set('stripe_key', $key);
                        $savedTokens[] = 'Stripe Publishable Key';
                    }
                }
            }

            // Validate and save Stripe secret key
            if (isset($tokens['stripe_secret'])) {
                $secret = trim($tokens['stripe_secret']);
                if (!empty($secret)) {
                    if (!str_starts_with($secret, 'sk_')) {
                        $errors[] = 'Invalid Stripe secret key format. Should start with sk_';
                    } else {
                        Setting::set('stripe_secret', $secret);
                        $savedTokens[] = 'Stripe Secret Key';
                    }
                }
            }

            // Validate and save Stripe webhook secret
            if (isset($tokens['stripe_webhook_secret'])) {
                $webhookSecret = trim($tokens['stripe_webhook_secret']);
                if (!empty($webhookSecret)) {
                    if (!str_starts_with($webhookSecret, 'whsec_')) {
                        $errors[] = 'Invalid Stripe webhook secret format. Should start with whsec_';
                    } else {
                        Setting::set('stripe_webhook_secret', $webhookSecret);
                        $savedTokens[] = 'Stripe Webhook Secret';
                    }
                }
            }

            // Validate and save creator monthly subscription price (USD)
            if (isset($tokens['creator_monthly_price_usd'])) {
                $price = (float) $tokens['creator_monthly_price_usd'];
                if ($price < 1 || $price > 999) {
                    $errors[] = 'Creator monthly price must be between 1 and 999 USD.';
                } else {
                    Setting::set('creator_monthly_price_usd', number_format($price, 2, '.', ''), 'float');
                    $savedTokens[] = 'Creator Monthly Price ($' . number_format($price, 2) . ')';
                }
            }

            // Validate and save Vercel Blob token
            if (isset($tokens['vercel_blob_token'])) {
                $blobToken = trim($tokens['vercel_blob_token']);
                if (!empty($blobToken)) {
                    if (!str_starts_with($blobToken, 'vercel_blob_rw_')) {
                        $errors[] = 'Invalid Vercel Blob token format. Should start with vercel_blob_rw_';
                    } else {
                        // Test the token by trying to list blobs (only if classes are available)
                        try {
                            if (!class_exists('VercelBlobPhp\Client')) {
                                $errors[] = 'Vercel Blob package not available. Please run composer install.';
                            } else {
                                $testClient = new BlobClient($blobToken);
                                $testClient->list(); // This will throw if token is invalid
                                Setting::set('vercel_blob_token', $blobToken);
                                $savedTokens[] = 'Vercel Blob Storage Token';
                            }
                        } catch (\Exception $e) {
                            $errors[] = 'Vercel Blob token is invalid or doesn\'t have proper permissions: ' . $e->getMessage();
                        }
                    }
                }
            }

            // Validate and save Vercel Blob Store ID (simple, just like the other settings)
            if (isset($tokens['vercel_blob_store_id'])) {
                $storeId = trim($tokens['vercel_blob_store_id']);
                if (!empty($storeId)) {
                    Setting::set('vercel_blob_store_id', $storeId);
                    $savedTokens[] = 'Vercel Blob Store ID';
                }
            }

            // Validate and save Vercel Blob Base URL (simple, just like the other settings)
            if (isset($tokens['vercel_blob_base_url'])) {
                $baseUrl = trim($tokens['vercel_blob_base_url']);
                if (!empty($baseUrl)) {
                    // Remove trailing slash for consistency
                    $baseUrl = rtrim($baseUrl, '/');
                    Setting::set('vercel_blob_base_url', $baseUrl);
                    $savedTokens[] = 'Vercel Blob Base URL';
                }
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'error' => implode('. ', $errors)
                ]);
            }

            if (empty($savedTokens)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No valid tokens provided to save'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully saved: ' . implode(', ', $savedTokens)
            ]);
        } catch (Exception $e) {
            Log::error('Failed to save tokens', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to save tokens: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Admin: Deactivate webhook to allow getUpdates.
     */
    // Deactivate webhook
    public function deactivateWebhook()
    {
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            if (!$botToken || $botToken === 'YOUR-BOT-TOKEN') {
                return response()->json([
                    'success' => false,
                    'error' => 'Bot token not configured'
                ]);
            }

            $response = Http::post("https://api.telegram.org/bot{$botToken}/deleteWebhook");

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Webhook deactivated successfully'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => $data['description'] ?? 'Failed to delete webhook'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to communicate with Telegram API'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deactivating webhook: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Network error occurred'
            ]);
        }
    }

    // Reactivate webhook
    public function reactivateWebhook()
    {
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            if (!$botToken || $botToken === 'YOUR-BOT-TOKEN') {
                return response()->json([
                    'success' => false,
                    'error' => 'Bot token not configured'
                ]);
            }

            // First, delete any existing webhook
            Http::post("https://api.telegram.org/bot{$botToken}/deleteWebhook");

            // Set new webhook
            $webhookUrl = url('/telegram/webhook');
            $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                'url' => $webhookUrl
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Webhook activated successfully'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => $data['description'] ?? 'Failed to set webhook'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to communicate with Telegram API'
            ]);
        } catch (\Exception $e) {
            Log::error('Error reactivating webhook: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Network error occurred'
            ]);
        }
    }

    /**
     * Get webhook status
     */
    public function webhookStatus()
    {
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";

            $response = Http::get($url);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'webhook_info' => $response->json()['result']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to get webhook info'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test Telegram connection
     */
    public function testConnection()
    {
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            $syncUserTelegramId = Setting::get('sync_user_telegram_id');

            if (!$syncUserTelegramId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No sync user configured. Please set a sync user first.'
                ]);
            }

            // Get bot info
            $botInfoUrl = "https://api.telegram.org/bot{$botToken}/getMe";
            $botInfoResponse = Http::get($botInfoUrl);

            if (!$botInfoResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to connect to bot: ' . $botInfoResponse->body()
                ]);
            }

            $botInfo = $botInfoResponse->json()['result'];

            // Check webhook status
            $webhookUrl = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
            $webhookResponse = Http::get($webhookUrl);
            $webhookInfo = $webhookResponse->json()['result'];
            $webhookActive = !empty($webhookInfo['url']);

            $responseData = [
                'success' => true,
                'data' => [
                    'bot_info' => $botInfo,
                    'webhook_active' => $webhookActive,
                    'can_use_getupdates' => !$webhookActive
                ]
            ];

            // Only try getUpdates if webhook is not active
            if (!$webhookActive) {
                $updatesUrl = "https://api.telegram.org/bot{$botToken}/getUpdates?limit=50";
                $updatesResponse = Http::get($updatesUrl);

                if ($updatesResponse->successful()) {
                    $updates = $updatesResponse->json()['result'];

                    // Filter and analyze messages from sync user
                    $syncUserMessages = array_filter($updates, function ($update) use ($syncUserTelegramId) {
                        return isset($update['message']['from']['id']) &&
                            $update['message']['from']['id'] == $syncUserTelegramId;
                    });

                    $messageAnalysis = [];
                    $videoMessagesFound = 0;

                    foreach ($syncUserMessages as $update) {
                        $message = $update['message'];
                        $hasVideo = isset($message['video']);
                        $hasVideoDocument = isset($message['document']) &&
                            isset($message['document']['mime_type']) &&
                            str_starts_with($message['document']['mime_type'], 'video/');

                        $videoFileId = null;
                        $documentFileId = null;

                        if ($hasVideo) {
                            $videoFileId = $message['video']['file_id'];
                            $videoMessagesFound++;
                        } elseif ($hasVideoDocument) {
                            $documentFileId = $message['document']['file_id'];
                            $videoMessagesFound++;
                        }

                        $messageAnalysis[] = [
                            'message_id' => $message['message_id'],
                            'from_id' => $message['from']['id'],
                            'from_first_name' => $message['from']['first_name'] ?? '',
                            'from_username' => $message['from']['username'] ?? '',
                            'date' => date('Y-m-d H:i:s', $message['date']),
                            'text' => $message['text'] ?? '',
                            'caption' => $message['caption'] ?? '',
                            'has_video' => $hasVideo || $hasVideoDocument,
                            'video_file_id' => $videoFileId,
                            'document_file_id' => $documentFileId
                        ];
                    }

                    $responseData['data']['message_analysis'] = $messageAnalysis;
                    $responseData['data']['total_messages_found'] = count($syncUserMessages);
                    $responseData['data']['video_messages_found'] = $videoMessagesFound;

                    if (count($syncUserMessages) === 0) {
                        $responseData['data']['message'] = 'No messages found from the configured sync user. Make sure the sync user has sent messages to the bot.';
                    }
                } else {
                    $responseData['data']['message'] = 'Could not retrieve conversation history: ' . $updatesResponse->body();
                }
            } else {
                $responseData['data']['message'] = 'Webhook is active - cannot retrieve conversation history using getUpdates method.';
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Test connection failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Manual import video
     */
    public function manualImport(Request $request)
    {
        try {
            $fileId = $request->input('file_id');
            $title = $request->input('title', 'Imported Video');
            $price = $request->input('price', 4.99);

            if (empty($fileId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File ID is required'
                ]);
            }

            // Check if sync user is configured
            $syncUserTelegramId = Setting::get('sync_user_telegram_id');
            if (!$syncUserTelegramId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No sync user configured. Please set a sync user first.'
                ]);
            }

            // Check if video already exists
            $existingVideo = Video::where('telegram_file_id', $fileId)->first();
            if ($existingVideo) {
                return response()->json([
                    'success' => false,
                    'error' => 'Video with this file ID already exists'
                ]);
            }

            // Create video record
            $video = Video::create([
                'title' => $title,
                'description' => 'Manually imported video',
                'price' => $price,
                'telegram_file_id' => $fileId,
                'filename' => 'imported_' . time() . '.mp4'
            ]);

            Log::info('Video manually imported', [
                'video_id' => $video->id,
                'file_id' => $fileId,
                'title' => $title
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video imported successfully!',
                'video_id' => $video->id
            ]);
        } catch (\Exception $e) {
            Log::error('Manual import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle incoming webhook from Telegram
     */
    public function webhook(Request $request)
    {
        try {
            $update = $request->all();
            Log::info('Webhook received:', $update);

            // ── Group/channel: bot added or removed ──────────────────────────
            if (isset($update['my_chat_member'])) {
                $this->handleChatMemberUpdate($update['my_chat_member']);
                return response()->json(['ok' => true]);
            }

            // ── Group/channel messages — handle before private flow ──────────
            if (isset($update['message'])) {
                $chatType = $update['message']['chat']['type'] ?? 'private';
                if (in_array($chatType, ['group', 'supergroup', 'channel'])) {
                    $this->handleGroupMessage($update['message']);
                    return response()->json(['ok' => true]);
                }
            }

            // Check if we have a message
            if (!isset($update['message'])) {
                Log::info('No message in update');
                return response()->json(['ok' => true]);
            }

            $message = $update['message'];
            $fromUser = $message['from'] ?? null;

            if (!$fromUser) {
                Log::info('No from user in message');
                return response()->json(['ok' => true]);
            }

            $fromUserId = $fromUser['id'];
            $username = $fromUser['username'] ?? null;
            $firstName = $fromUser['first_name'] ?? 'User';
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            Log::info('Processing webhook message', [
                'from_user_id' => $fromUserId,
                'username' => $username,
                'chat_id' => $chatId,
                'text' => $text,
                'first_name' => $firstName
            ]);

            $syncUserTelegramId = Setting::get('sync_user_telegram_id');
            $isSyncUser = ($fromUserId == $syncUserTelegramId);

            $creatorUser = User::where('telegram_user_id', (string) $fromUserId)
                ->where('is_creator', true)
                ->where('creator_subscription_status', 'active')
                ->first();

            $ownerCreator = User::where('telegram_user_id', (string) $fromUserId)
                ->where('is_admin', true)
                ->first();

            if (!$ownerCreator && $isSyncUser) {
                $ownerCreator = User::where('is_admin', true)->orderBy('id')->first();
            }

            if ($ownerCreator) {
                $ownerUpdates = [
                    'is_creator' => true,
                    'creator_subscription_status' => 'active',
                ];

                if (!$ownerCreator->creator_slug) {
                    $ownerUpdates['creator_slug'] = $this->generateUniqueCreatorSlug($ownerCreator->name ?: 'AdminUser', $ownerCreator->id);
                }
                if (!$ownerCreator->creator_store_name) {
                    $ownerUpdates['creator_store_name'] = $ownerCreator->name ?: 'AdminUser';
                }

                if (!empty($ownerUpdates)) {
                    $ownerCreator->update($ownerUpdates);
                }
            }

            $isActiveCreator = $creatorUser !== null;
            $uploaderCreator = $isActiveCreator ? $creatorUser : ($isSyncUser ? $ownerCreator : null);

            Log::info('Sync user check', [
                'sync_user_telegram_id' => $syncUserTelegramId,
                'is_sync_user' => $isSyncUser,
                'is_active_creator' => $isActiveCreator,
                'creator_id' => $uploaderCreator?->id,
            ]);

            // **UPLOAD FLOW** - Handle video uploads from admin sync user or active creators
            if ($isSyncUser || $isActiveCreator) {
                // Get video from message
                $video = null;
                $fileId = null;

                if (isset($message['video'])) {
                    $video = $message['video'];
                    $fileId = $video['file_id'];
                } elseif (
                    isset($message['document']) &&
                    isset($message['document']['mime_type']) &&
                    strpos($message['document']['mime_type'], 'video/') === 0
                ) {
                    $video = $message['document'];
                    $fileId = $video['file_id'];
                }

                if ($fileId) {
                    $rawCaption = $message['caption'] ?? '';

                    // ── Broadcast capture ─────────────────────────────────
                    if (preg_match('/^[#!]broadcast\s*/i', $rawCaption)) {
                        $broadcastCaption = trim(preg_replace('/^[#!]broadcast\s*/i', '', $rawCaption)) ?: null;
                        $fileType = isset($message['photo'])
                            ? 'photo'
                            : (isset($message['animation']) ? 'animation'
                            : (isset($message['document'])  ? 'document' : 'video'));

                        BotBroadcast::create([
                            'telegram_file_id' => $fileId,
                            'file_type'        => $fileType,
                            'caption'          => $broadcastCaption,
                            'status'           => 'pending',
                            'created_by'       => $ownerCreator?->id ?? $uploaderCreator?->id,
                        ]);

                        $this->sendTelegramMessage(
                            $fromUserId,
                            "📢 *Broadcast guardado!*\n\n" .
                            ($broadcastCaption ? "📝 Caption: {$broadcastCaption}\n\n" : "") .
                            "Ve al panel web → *Bot Manager → Broadcasts* para enviarlo a grupos o programarlo."
                        );

                        return response()->json(['ok' => true]);
                    }
                    // ─────────────────────────────────────────────────────

                    $caption = $rawCaption ?: 'Video capturado';
                    $defaultPrice = 4.99;

                    $videoRecord = Video::create([
                        'title' => $caption,
                        'description' => "Auto-captured from Telegram",
                        'telegram_file_id' => $fileId,
                        'price' => $defaultPrice,
                        'creator_id' => $uploaderCreator?->id,
                        'category_id' => $this->resolveDefaultCategoryId($uploaderCreator),
                    ]);

                    $this->sendTelegramMessage(
                        $fromUserId,
                        "✅ Video capturado correctamente!\n\n" .
                            "📹 Titulo: {$caption}\n" .
                            "💰 Precio: $" . number_format($defaultPrice, 2) . "\n" .
                            "🆔 ID de video: {$videoRecord->id}"
                    );

                    Log::info("Video auto-captured from uploader: {$fromUserId}", [
                        'video_id' => $videoRecord->id,
                        'file_id' => $fileId,
                        'creator_id' => $uploaderCreator?->id,
                    ]);

                    return response()->json(['ok' => true]);
                }

                // Sync user basic commands
                if (strtolower($text) === '/start') {
                    $this->sendTelegramMessage(
                        $fromUserId,
                        "👋 Hola Admin! Estoy listo para capturar videos.\n\n" .
                            "🎥 Enviame videos y los agregare a tu tienda!\n" .
                            "💡 Escribe /help para mas informacion."
                    );
                    return response()->json(['ok' => true]);
                } elseif (strtolower($text) === '/help') {
                    $this->handleCustomerHelpCommand($chatId);
                    return response()->json(['ok' => true]);
                }
            }

            // **CUSTOMER FLOW** - Handle purchase verification and delivery
            else {
                Log::info('Processing customer message', [
                    'from_user_id' => $fromUserId,
                    'username' => $username,
                    'text' => $text,
                    'is_command' => str_starts_with($text, '/')
                ]);

                // Handle customer commands
                if (str_starts_with($text, '/')) {
                    $this->handleCustomerCommand($text, $chatId, $fromUserId, $username, $firstName);
                } elseif (isset($message['video'])) {
                    // Non-sync user sent video
                    $this->sendTelegramMessage(
                        $fromUserId,
                        "Gracias por el video. Solo los videos del admin o creadores activos se capturan automaticamente.\n\nUsa /start, /mypurchases o /getvideo <id> para tus compras."
                    );
                } else {
                    // Free text from customer — try to store as a reply in the messaging system
                    $this->tryStorePurchaseMessage($update, (int) $fromUserId, $username, $text);
                }
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['ok' => true]);
        }
    }

    /**
     * Handle customer commands
     */
    private function handleCustomerCommand($text, $chatId, $telegramUserId, $username, $firstName)
    {
        $parts = explode(' ', trim($text));
        $command = $parts[0];
        $args = array_slice($parts, 1);

        Log::info('Customer command executed', [
            'command' => $command,
            'telegram_user_id' => $telegramUserId,
            'username' => $username,
            'chat_id' => $chatId,
        ]);

        switch ($command) {
            case '/start':
                // Check if /start has parameters (like getvideo_5)
                if (count($args) > 0) {
                    $startParam = $args[0];

                    // Handle getvideo_X parameter
                    if (strpos($startParam, 'getvideo_') === 0) {
                        $videoId = str_replace('getvideo_', '', $startParam);
                        if (is_numeric($videoId)) {
                            Log::info('Start command with getvideo parameter', [
                                'video_id' => $videoId,
                                'telegram_user_id' => $telegramUserId,
                                'username' => $username
                            ]);
                            $this->handleCustomerGetVideoCommand($chatId, $telegramUserId, $username, $videoId);
                            return;
                        }
                    }
                }

                // Regular /start command
                $this->handleCustomerStartCommand($chatId, $telegramUserId, $username, $firstName);
                break;

            case '/help':
                $this->handleCustomerHelpCommand($chatId);
                break;

            case '/mypurchases':
                $this->handleCustomerMyPurchasesCommand($chatId, $telegramUserId, $username);
                break;

            case '/getvideo':
                if (count($args) > 0) {
                    $this->handleCustomerGetVideoCommand($chatId, $telegramUserId, $username, $args[0]);
                } else {
                    $this->sendTelegramMessage($chatId, "❌ Debes indicar un ID de video. Uso: /getvideo <id>\n\nUsa /mypurchases para ver tus videos disponibles.");
                }
                break;

            default:
                $this->sendTelegramMessage($chatId, "❓ Comando desconocido. Escribe /help para ver los comandos disponibles.");
        }
    }

    /**
     * Handle customer /start command
     */
    private function handleCustomerStartCommand($chatId, $telegramUserId, $username, $firstName)
    {
        // Update/create user info
        $this->updateUserInfo($telegramUserId, $username, $firstName);

        Log::info('Customer /start command', [
            'telegram_user_id' => $telegramUserId,
            'username' => $username,
            'chat_id' => $chatId
        ]);

        // Find ALL purchases for this user (by username OR telegram_user_id) that need processing
        $userPurchases = collect();

        if ($username) {
            // Get purchases by username
            $purchasesByUsername = \App\Models\Purchase::where('telegram_username', $username)
                ->where('purchase_status', 'completed')
                ->where(function ($query) {
                    $query->whereNull('creator_id')
                        ->orWhere('verification_status', 'verified');
                })
                ->with('video')
                ->get();
            $userPurchases = $userPurchases->merge($purchasesByUsername);
        }

        // Get purchases by telegram_user_id (if they exist)
        $purchasesByTelegramId = \App\Models\Purchase::where('telegram_user_id', $telegramUserId)
            ->where('purchase_status', 'completed')
            ->where(function ($query) {
                $query->whereNull('creator_id')
                    ->orWhere('verification_status', 'verified');
            })
            ->with('video')
            ->get();
        $userPurchases = $userPurchases->merge($purchasesByTelegramId);

        // Remove duplicates by purchase ID
        $userPurchases = $userPurchases->unique('id');

        Log::info('Found user purchases', [
            'telegram_user_id' => $telegramUserId,
            'username' => $username,
            'total_purchases' => $userPurchases->count(),
            'purchase_ids' => $userPurchases->pluck('id')->toArray()
        ]);

        $pendingCreatorPurchases = \App\Models\Purchase::where('purchase_status', 'completed')
            ->where('verification_status', 'pending')
            ->whereNotNull('creator_id')
            ->where(function ($query) use ($telegramUserId, $username) {
                $query->where('telegram_user_id', $telegramUserId);
                if ($username) {
                    $query->orWhere('telegram_username', $username);
                }
            })
            ->count();

        if ($userPurchases->isEmpty() && $pendingCreatorPurchases > 0) {
            $this->sendTelegramMessage(
                $chatId,
                "Tu pago fue registrado, pero el creador aun no lo aprueba.\n\nCompras pendientes: {$pendingCreatorPurchases}\n\nCuando el creador apruebe, podras usar /getvideo <id>."
            );
            return;
        }

        if ($userPurchases->isEmpty()) {
            $message = "👋 *Bienvenido al bot de la tienda de videos!*\n\n";
            $message .= "❌ No se encontraron compras para tu cuenta.\n\n";
            $message .= "🛒 *Para comprar videos:*\n";
            $message .= "1. Visita nuestra web\n";
            if ($username) {
                $message .= "2. Compra con el usuario: @{$username}\n";
            }
            $message .= "3. Vuelve aqui y usa /start para acceder a tus videos\n\n";
            $message .= "💡 Escribe /help para mas comandos.";

            $this->sendTelegramMessage($chatId, $message);
            return;
        }

        // Process and deliver all user's videos
        $deliveredCount = 0;
        $alreadyDeliveredCount = 0;
        $deliveredVideos = collect();

        foreach ($userPurchases as $purchase) {
            try {
                // Link telegram_user_id if not already linked
                if (!$purchase->telegram_user_id) {
                    $verificationStatus = $purchase->creator_id ? $purchase->verification_status : 'verified';
                    $purchase->update([
                        'telegram_user_id' => $telegramUserId,
                        'verification_status' => $verificationStatus
                    ]);
                    Log::info('Linked telegram_user_id to purchase', [
                        'purchase_id' => $purchase->id,
                        'telegram_user_id' => $telegramUserId
                    ]);
                }

                // Deliver video if not already delivered
                if ($purchase->delivery_status !== 'delivered') {
                    $this->deliverVideoToCustomer($chatId, $purchase);
                    $deliveredCount++;
                    $deliveredVideos->push($purchase);
                } else {
                    $alreadyDeliveredCount++;
                    $deliveredVideos->push($purchase);
                }
            } catch (\Exception $e) {
                Log::error('Error processing purchase in /start', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send summary message
        if ($deliveredCount > 0) {
            $message = "🎉 *Bienvenido al bot de la tienda de videos!*\n\n";
            $message .= "✅ Encontrados y entregados {$deliveredCount} video(s)!\n\n";

            foreach ($deliveredVideos as $purchase) {
                $status = $purchase->delivery_status === 'delivered' ? '✅' : '🆕';
                $message .= "📹 *{$purchase->video->title}* {$status}\n";
                $message .= "🆔 ID de video: {$purchase->video_id} | Precio: {$purchase->formatted_amount}\n\n";
            }
        } else {
            $message = "👋 *Bienvenido de nuevo al bot de la tienda de videos!*\n\n";
            $message .= "📋 Tienes {$alreadyDeliveredCount} video(s) en tu biblioteca.\n\n";

            foreach ($deliveredVideos as $purchase) {
                $message .= "📹 *{$purchase->video->title}* ✅\n";
                $message .= "🆔 ID de video: {$purchase->video_id} | Precio: {$purchase->formatted_amount}\n\n";
            }
        }

        $message .= "🤖 *Comandos disponibles:*\n";
        $message .= "/mypurchases - Ver todos tus videos\n";
        $message .= "/getvideo <id> - Recibir cualquier video al instante\n";
        $message .= "/help - Ver ayuda\n\n";
        $message .= "💡 Guarda este chat: puedes descargar tus videos cuando quieras!";

        $this->sendTelegramMessage($chatId, $message);
    }

    /**
     * Handle customer /help command
     */
    private function handleCustomerHelpCommand($chatId)
    {
        $message = "🤖 *Ayuda del bot de la tienda de videos*\n\n";
        $message .= "*Comandos disponibles:*\n";
        $message .= "/start - Verify purchases & get videos\n";
        $message .= "/mypurchases - Show ALL purchased videos\n";
        $message .= "/getvideo <id> - Download specific video\n";
        $message .= "/help - Show this help\n\n";
        $message .= "*🎁 Free Videos:*\n";
        $message .= "Algunos videos son GRATIS. Usa `/getvideo <ID>` con el ID de un video gratis para descargar al instante, sin compra.\n\n";
        $message .= "Algunos videos son GRATIS. Usa `/getvideo <ID>` con el ID de un video gratis para descargar al instante, sin compra.\n\n";
        $message .= "1. Visita nuestra web\n";
        $message .= "2. Elige un video e introduce tu usuario de Telegram\n";
        $message .= "3. Completa el pago\n";
        $message .= "4. Vuelve aqui y escribe /start para verificar\n\n";
        $message .= "*Como descargar:*\n";
        $message .= "1. Usa /mypurchases para ver tus videos\n";
        $message .= "2. Use `/getvideo <ID>` to download\n";
        $message .= "3. Tienes acceso ilimitado\n\n";
        $message .= "*Necesitas soporte?*\n";
        $message .= "Contactanos si tienes problemas con compras o entregas.";

        $this->sendTelegramMessage($chatId, $message);
    }

    /**
     * Handle customer /mypurchases command
     */
    private function handleCustomerMyPurchasesCommand($chatId, $telegramUserId, $username)
    {
        if (!$username) {
            $this->sendTelegramMessage($chatId, "Necesitas un usuario de Telegram para usar este bot.");
            return;
        }

        $userPurchases = \App\Models\Purchase::where('purchase_status', 'completed')
            ->where(function ($query) use ($telegramUserId, $username) {
                $query->where('telegram_user_id', $telegramUserId);
                if ($username) {
                    $query->orWhere('telegram_username', $username);
                }
            })
            ->with(['video', 'serviceAccess.line'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($userPurchases->isEmpty()) {
            $this->sendTelegramMessage($chatId, "No se encontraron compras para @{$username}.");
            return;
        }

        $message = "Tus compras ({$userPurchases->count()}):\n\n";
        foreach ($userPurchases as $purchase) {
            $video = $purchase->video;
            if (!$video) {
                continue;
            }

            $message .= "{$video->title} - {$purchase->formatted_amount}\n";

            if ($video->isServiceProduct()) {
                if ($purchase->serviceAccess && !$purchase->serviceAccess->isExpired()) {
                    $token = $purchase->serviceAccess->access_token;
                    $isShared = $purchase->serviceAccess->line && $purchase->serviceAccess->line->is_shared;
                    $accessUrl = $isShared
                        ? url('/iptv/' . $token)
                        : route('service.access.show', $token);
                    $message .= "Acceso: {$accessUrl}\n";
                    $message .= "Expira: " . $purchase->serviceAccess->expires_at->format('Y-m-d H:i') . "\n\n";
                } else {
                    $message .= "Acceso expirado. Debes comprar de nuevo.\n\n";
                }
            } else {
                $message .= "Comando: /getvideo {$video->id}\n\n";
            }
        }

        $this->sendTelegramMessage($chatId, $message);
    }

    /**
     * Handle customer /getvideo command
     */
    private function handleCustomerGetVideoCommand($chatId, $telegramUserId, $username, $videoId)
    {
        if (!$username) {
            $this->sendTelegramMessage($chatId, "Necesitas un usuario de Telegram para usar este bot.");
            return;
        }

        $video = \App\Models\Video::find($videoId);
        if (!$video) {
            $this->sendTelegramMessage($chatId, "Video no encontrado.");
            return;
        }

        if ($video->isFree()) {
            $freeAccess = new \stdClass();
            $freeAccess->video = $video;
            $freeAccess->id = 'free-access-' . $videoId;
            $freeAccess->video_id = $videoId;
            $freeAccess->formatted_amount = 'FREE';
            $this->deliverFreeVideoToCustomer($chatId, $freeAccess);
            return;
        }

        $purchaseQuery = \App\Models\Purchase::where('purchase_status', 'completed')
            ->where('video_id', $videoId)
            ->where(function ($query) use ($telegramUserId, $username) {
                $query->where('telegram_user_id', $telegramUserId);
                if ($username) {
                    $query->orWhere('telegram_username', $username);
                }
            })
            ->with(['video', 'serviceAccess.line'])
            ->latest();

        // For regular videos require bot verification; IPTV access is provisioned automatically
        if (!$video->isServiceProduct()) {
            $purchaseQuery->where('verification_status', 'verified');
        }

        $purchase = $purchaseQuery->first();

        if (!$purchase) {
            $this->sendTelegramMessage($chatId, "No tienes acceso a este contenido.");
            return;
        }

        if ($video->isServiceProduct()) {
            if (!$purchase->serviceAccess || $purchase->serviceAccess->isExpired()) {
                $this->sendTelegramMessage($chatId, "Tu acceso esta expirado. Debes comprar de nuevo.");
                return;
            }

            $token = $purchase->serviceAccess->access_token;
            $isShared = $purchase->serviceAccess->line && $purchase->serviceAccess->line->is_shared;
            $accessUrl = $isShared
                ? url('/iptv/' . $token)
                : route('service.access.show', $token);

            $this->sendTelegramMessage($chatId, "Acceso activo: {$accessUrl}\nExpira: " . $purchase->serviceAccess->expires_at->format('Y-m-d H:i'));
            return;
        }

        if (!$purchase->telegram_user_id) {
            $purchase->update([
                'telegram_user_id' => $telegramUserId,
                'verification_status' => 'verified'
            ]);
        }

        $this->deliverVideoToCustomer($chatId, $purchase);
    }

    /**
     * Deliver free video to customer
     */
    private function deliverFreeVideoToCustomer($chatId, $freeAccess)
    {
        $video = $freeAccess->video;

        try {
            if ($video->telegram_file_id) {
                // Send video using file_id
                $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
                $url = "https://api.telegram.org/bot{$botToken}/sendVideo";

                $data = [
                    'chat_id' => $chatId,
                    'video' => $video->telegram_file_id,
                    'caption' => "🎬 *{$video->title}* (FREE)\n\n" .
                        "✅ Enjoy this free video!\n" .
                        "🔗 Visit our store for more amazing content!",
                    'parse_mode' => 'Markdown'
                ];

                $response = Http::timeout(30)->post($url, $data);

                if ($response->successful()) {
                    Log::info('Free video delivered successfully', [
                        'video_id' => $video->id,
                        'chat_id' => $chatId,
                        'video_title' => $video->title
                    ]);
                } else {
                    Log::error('Failed to deliver free video', [
                        'video_id' => $video->id,
                        'chat_id' => $chatId,
                        'response' => $response->body()
                    ]);

                    $this->sendTelegramMessage($chatId, "❌ *Error de entrega*\n\nHubo un problema al entregar el video gratis. Intentalo de nuevo o contacta soporte.");
                }
            } else {
                $this->sendTelegramMessage($chatId, "❌ *Video no disponible*\n\nEste video gratis aun no esta listo para entrega. Intentalo mas tarde.");
            }
        } catch (\Exception $e) {
            Log::error('Exception during free video delivery', [
                'video_id' => $video->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);

            $this->sendTelegramMessage($chatId, "❌ *Error de entrega*\n\nHubo un problema al entregar el video gratis. Intentalo de nuevo o contacta soporte.");
        }
    }

    /**
     * Deliver video to customer
     */
    private function deliverVideoToCustomer($chatId, $purchase)
    {
        $video = $purchase->video;

        try {
            if ($video->telegram_file_id) {
                // Send video using file_id
                $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
                $url = "https://api.telegram.org/bot{$botToken}/sendVideo";

                $response = Http::post($url, [
                    'chat_id' => $chatId,
                    'video' => $video->telegram_file_id,
                    'caption' => "🎬 *{$video->title}*\n\n" .
                        "📝 {$video->description}\n\n" .
                        "✅ Entregado correctamente!\n" .
                        "💡 Usa /getvideo {$video->id} cuando quieras para acceso ilimitado.",
                    'parse_mode' => 'Markdown'
                ]);

                if ($response->successful()) {
                    $purchase->markAsDelivered();

                    Log::info('Video delivered to customer', [
                        'purchase_id' => $purchase->id,
                        'video_id' => $video->id,
                        'telegram_user_id' => $purchase->telegram_user_id
                    ]);
                } else {
                    Log::error('Failed to send video to customer', [
                        'purchase_id' => $purchase->id,
                        'response' => $response->body()
                    ]);

                    $this->sendTelegramMessage($chatId, "❌ No se pudo entregar el video. El equipo fue notificado. Intentalo de nuevo en unos minutos.");
                }
            } else {
                Log::error('Video has no telegram_file_id', ['video_id' => $video->id]);
                $this->sendTelegramMessage($chatId, "❌ El archivo de video no esta disponible. El equipo fue notificado.");
            }
        } catch (\Exception $e) {
            Log::error('Video delivery error', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);

            $this->sendTelegramMessage($chatId, "❌ Ocurrio un error de entrega. Intentalo de nuevo o contacta soporte.");
        }
    }

    /**
     * Update user info for customers
     */
    private function updateUserInfo($telegramUserId, $username, $firstName)
    {
        if (!$telegramUserId) return;

        $user = \App\Models\User::where('telegram_user_id', $telegramUserId)->first();

        if ($user) {
            // Update existing user
            $updates = [];
            if ($username && $user->telegram_username !== $username) {
                $updates['telegram_username'] = $username;
            }
            if ($firstName && $user->name !== $firstName) {
                $updates['name'] = $firstName;
            }

            if (!empty($updates)) {
                $user->update($updates);
            }
        } else {
            // Create user only if they have purchases
            $hasPurchases = \App\Models\Purchase::where('telegram_username', $username)
                ->where('purchase_status', 'completed')
                ->exists();

            if ($hasPurchases) {
                \App\Models\User::create([
                    'name' => $firstName,
                    'telegram_user_id' => $telegramUserId,
                    'telegram_username' => $username,
                    'email' => $username . '@telegram.bot',
                    'password' => bcrypt('telegram_user_' . $telegramUserId),
                ]);
            }
        }
    }

    /**
     * Send a message to Telegram user
     */
    private function sendTelegramMessage($chatId, $text, $replyToMessageId = null)
    {
        try {
            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');

            if (!$botToken || $botToken === 'YOUR-BOT-TOKEN') {
                Log::error('Invalid or missing bot token');
                return false;
            }

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $data = [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ];

            if ($replyToMessageId) {
                $data['reply_parameters'] = ['message_id' => $replyToMessageId];
            }

            $response = Http::timeout(30)->post($url, $data);

            if ($response->successful()) {
                Log::info('Telegram message sent successfully', [
                    'chat_id' => $chatId,
                    'message_preview' => substr($text, 0, 100)
                ]);
                return $response->json();
            } else {
                Log::error('Failed to send Telegram message', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'message_preview' => substr($text, 0, 100)
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Telegram message sending failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'message_preview' => substr($text, 0, 100)
            ]);
            return false;
        }
    }

    /**
     * Try to store an incoming customer free-text message as a PurchaseMessage.
     * Matches by reply_to_message.message_id first, then by telegram_user_id.
     */
    // ═══════════════════════════════════════════════════════════════════════
    // GROUP / CHANNEL HANDLERS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Handle my_chat_member update: bot added or removed from a group/channel.
     */
    private function handleChatMemberUpdate(array $data): void
    {
        try {
            $chat      = $data['chat'] ?? [];
            $chatId    = $chat['id'] ?? null;
            $chatTitle = $chat['title'] ?? "Grupo {$chatId}";
            $chatType  = $chat['type'] ?? 'group';
            $username  = $chat['username'] ?? null;
            $newStatus = $data['new_chat_member']['status'] ?? null;

            if (!$chatId) return;

            if ($newStatus === 'administrator') {
                // Bot promoted to admin in a group — register it
                BotGroup::updateOrCreate(
                    ['chat_id' => $chatId],
                    [
                        'chat_title'    => $chatTitle,
                        'chat_type'     => in_array($chatType, ['group', 'supergroup', 'channel']) ? $chatType : 'group',
                        'username'      => $username,
                        'is_active'     => true,
                        'settings'      => BotGroup::defaultSettings(),
                        'registered_at' => now(),
                    ]
                );
                Log::info("BotManager: group registered [{$chatTitle}] id={$chatId}");
            } elseif (in_array($newStatus, ['kicked', 'left', 'member'])) {
                // Bot removed or demoted — deactivate
                BotGroup::where('chat_id', $chatId)->update(['is_active' => false]);
                Log::info("BotManager: group deactivated [{$chatTitle}] id={$chatId} status={$newStatus}");
            }
        } catch (\Exception $e) {
            Log::error('BotManager handleChatMemberUpdate error: ' . $e->getMessage());
        }
    }

    /**
     * Handle a message received in a group or channel.
     * Applies: commands, auto-delete links, welcome messages.
     */
    private function handleGroupMessage(array $message): void
    {
        try {
            $chatId    = $message['chat']['id'] ?? null;
            $text      = $message['text'] ?? ($message['caption'] ?? '');
            $fromUser  = $message['from'] ?? [];
            $userId    = (string) ($fromUser['id'] ?? '');
            $username  = $fromUser['username'] ?? null;
            $firstName = $fromUser['first_name'] ?? 'Usuario';
            $msgId     = $message['message_id'] ?? null;

            if (!$chatId) return;

            $group = BotGroup::where('chat_id', $chatId)->where('is_active', true)->first();
            if (!$group) return;

            $botToken = Setting::get('telegram_bot_token') ?: config('telegram.bots.mybot.token');
            $mention  = $username ? "@{$username}" : $firstName;

            // ── Welcome new members ─────────────────────────────────────────
            if (isset($message['new_chat_member']) && $group->getSetting('welcome_enabled')) {
                $welcomeText = str_replace(
                    ['{nombre}', '{grupo}'],
                    [$firstName, $group->chat_title],
                    $group->getSetting('welcome_message', '¡Bienvenido/a {nombre}!')
                );
                $this->sendTelegramMessage($chatId, $welcomeText);
                return;
            }

            if (!$text || !$userId) return;

            // ── Anti-flood ──────────────────────────────────────────────────
            if ($group->getSetting('antiflood_enabled') && $msgId) {
                $mutedKey = "muted:{$group->id}:{$userId}";

                // If user is already muted: silently delete and skip
                if (Cache::has($mutedKey)) {
                    Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/deleteMessage", [
                        'chat_id' => $chatId, 'message_id' => $msgId,
                    ]);
                    return;
                }

                $maxMsgs      = (int) $group->getSetting('antiflood_max_messages', 5);
                $seconds      = (int) $group->getSetting('antiflood_seconds', 10);
                $muteDuration = (int) $group->getSetting('antiflood_mute_duration', 5);
                $floodKey     = "flood:{$group->id}:{$userId}";
                $count        = (int) Cache::get($floodKey, 0) + 1;
                Cache::put($floodKey, $count, $seconds);

                if ($count > $maxMsgs) {
                    Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/deleteMessage", [
                        'chat_id' => $chatId, 'message_id' => $msgId,
                    ]);
                    $floodAction = $group->getSetting('antiflood_action', 'mute');
                    if ($floodAction === 'mute') {
                        Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/restrictChatMember", [
                            'chat_id'     => $chatId,
                            'user_id'     => (int) $userId,
                            'permissions' => json_encode(['can_send_messages' => false]),
                            'until_date'  => now()->addMinutes($muteDuration)->timestamp,
                        ]);
                        $durationText = $muteDuration >= 60
                            ? round($muteDuration / 60, 1) . ' h'
                            : $muteDuration . ' min';
                        $this->sendTelegramMessage($chatId, "🚫 {$mention}, silenciado {$durationText} por flood.");
                        // Mark as muted so subsequent messages are silently deleted
                        Cache::put($mutedKey, 1, $muteDuration * 60);
                    } elseif ($floodAction === 'ban') {
                        Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/banChatMember", [
                            'chat_id' => $chatId, 'user_id' => (int) $userId,
                        ]);
                        $group->bans()->create([
                            'telegram_user_id'  => $userId,
                            'telegram_username' => $username,
                            'reason'            => 'Auto-ban: flood de mensajes',
                            'banned_at'         => now(),
                        ]);
                    }
                    Cache::forget($floodKey);
                    return;
                }
            }

            // ── Custom commands ─────────────────────────────────────────────
            $command = $group->matchCommand($text);
            if ($command) {
                $this->sendTelegramMessage($chatId, $command->response, $msgId);
                return;
            }

            // ── Broadcast triggers ──────────────────────────────────────────
            $broadcast = BotBroadcast::where('trigger', $text)->first();
            if ($broadcast) {
                Http::timeout(30)->post(
                    "https://api.telegram.org/bot{$botToken}/{$broadcast->sendMethod()}",
                    array_filter([
                        'chat_id'             => $chatId,
                        $broadcast->fileKey() => $broadcast->telegram_file_id,
                        'caption'             => $broadcast->caption,
                        'parse_mode'          => 'Markdown',
                    ])
                );
                return;
            }

            // ── Blacklist ───────────────────────────────────────────────────
            if ($group->getSetting('blacklist_enabled') && $msgId) {
                $words   = $group->getSetting('blacklist_words', []);
                $textLow = strtolower($text);
                $matched = false;
                foreach ($words as $word) {
                    if ($word && str_contains($textLow, $word)) { $matched = true; break; }
                }
                if ($matched) {
                    Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/deleteMessage", [
                        'chat_id' => $chatId, 'message_id' => $msgId,
                    ]);
                    $this->applyModerationAction(
                        $group, $botToken, $chatId, $userId, $username, $firstName,
                        $group->getSetting('blacklist_action', 'delete_only'),
                        'Auto-ban: palabra prohibida'
                    );
                    return;
                }
            }

            // ── Auto-delete links ───────────────────────────────────────────
            if ($group->getSetting('auto_delete_links') && $msgId) {
                $hasLink = preg_match('/(https?:\/\/[^\s]+|t\.me\/[^\s]+)/i', $text);
                if ($hasLink) {
                    Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/deleteMessage", [
                        'chat_id' => $chatId, 'message_id' => $msgId,
                    ]);
                    $this->applyModerationAction(
                        $group, $botToken, $chatId, $userId, $username, $firstName,
                        $group->getSetting('delete_link_action', 'delete_only'),
                        'Auto-ban: publicó un enlace'
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('BotManager handleGroupMessage error: ' . $e->getMessage());
        }
    }

    /**
     * Apply a moderation action (warn/ban) with optional progressive warnings.
     */
    private function applyModerationAction(
        BotGroup $group, string $botToken, $chatId,
        string $userId, ?string $username, string $firstName,
        string $action, string $banReason
    ): void {
        $mention = $username ? "@{$username}" : $firstName;

        if ($action === 'delete_and_warn') {
            $this->sendTelegramMessage($chatId, "⚠️ {$mention}, este contenido no está permitido en el grupo.");
        } elseif ($action === 'delete_and_ban') {
            if ($group->getSetting('warn_before_ban')) {
                $maxWarnings = (int) $group->getSetting('max_warnings', 3);
                $warning = BotGroupWarning::firstOrNew(
                    ['bot_group_id' => $group->id, 'telegram_user_id' => $userId]
                );
                $warning->telegram_username = $username;
                $warning->count = ($warning->count ?? 0) + 1;
                $warning->reason = $banReason;
                $warning->last_warned_at = now();
                $warning->save();

                if ($warning->count >= $maxWarnings) {
                    Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/banChatMember", [
                        'chat_id' => $chatId, 'user_id' => (int) $userId,
                    ]);
                    $group->bans()->create([
                        'telegram_user_id'  => $userId,
                        'telegram_username' => $username,
                        'reason'            => $banReason . " (aviso {$warning->count}/{$maxWarnings})",
                        'banned_at'         => now(),
                    ]);
                    $warning->delete();
                    $this->sendTelegramMessage($chatId, "🚫 {$mention} ha sido baneado tras {$maxWarnings} avisos.");
                } else {
                    $remaining = $maxWarnings - $warning->count;
                    $this->sendTelegramMessage($chatId, "⚠️ {$mention}, aviso {$warning->count}/{$maxWarnings}. Te quedan {$remaining} avisos antes del ban.");
                }
            } else {
                Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/banChatMember", [
                    'chat_id' => $chatId, 'user_id' => (int) $userId,
                ]);
                $group->bans()->create([
                    'telegram_user_id'  => $userId,
                    'telegram_username' => $username,
                    'reason'            => $banReason,
                    'banned_at'         => now(),
                ]);
                Log::info("BotManager: auto-banned user {$userId} in group {$chatId}");
            }
        }
    }

    private function tryStorePurchaseMessage(array $update, int $telegramUserId, string $username, string $text): void
    {
        try {
            $message   = $update['message'] ?? [];
            $purchaseId = null;

            // 1. Try to match via reply_to_message
            $replyMsgId = $message['reply_to_message']['message_id'] ?? null;
            if ($replyMsgId) {
                $parent = PurchaseMessage::where('telegram_message_id', $replyMsgId)->first();
                if ($parent) {
                    $purchaseId = $parent->purchase_id;
                }
            }

            // 2. Fallback: most recent completed purchase for this Telegram user
            if (!$purchaseId) {
                $purchase = Purchase::where('telegram_user_id', (string) $telegramUserId)
                    ->where('purchase_status', 'completed')
                    ->latest()
                    ->first();
                if ($purchase) {
                    $purchaseId = $purchase->id;
                }
            }

            if (!$purchaseId) {
                return; // No purchase found — ignore silently
            }

            PurchaseMessage::create([
                'purchase_id'         => $purchaseId,
                'sender_type'         => 'user',
                'sender_name'         => '@' . ltrim($username, '@'),
                'message'             => $text,
                'telegram_message_id' => $message['message_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('tryStorePurchaseMessage failed', ['error' => $e->getMessage()]);
        }
    }

    public function storeServiceLines(Request $request, Video $video)
    {
        $validated = $request->validate([
            'bulk_lines' => 'required|string|max:50000',
        ]);

        if (!$video->isServiceProduct()) {
            return redirect()->route('admin.videos.manage')->with('error', 'Este producto no es de tipo servicio.');
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($validated['bulk_lines']));
        $created = 0;

        foreach ($lines as $rawLine) {
            $rawLine = trim($rawLine);
            if ($rawLine === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $rawLine));
            if (count($parts) < 2) {
                continue;
            }

            ServiceAccessLine::create([
                'video_id'   => $video->id,
                'creator_id' => $video->creator_id,
                'line_name'  => $parts[0],
                'm3u_url'    => $parts[1],
                'line_username' => $parts[2] ?? null,
                'line_password' => $parts[3] ?? null,
                'notes'      => $parts[4] ?? null,
                'is_shared'  => (bool) $request->input('is_shared', false),
            ]);
            $created++;
        }

        $backRoute = route('admin.videos.service-lines.show', $video);
        return redirect($backRoute)->with('success', "Lineas cargadas: {$created}");
    }

    public function serviceLines(Video $video)
    {
        $video->load('category');
        $lines = ServiceAccessLine::where('video_id', $video->id)->latest()->paginate(50);
        $categories = Category::orderBy('name')->get();

        return view('admin.videos.service-lines', compact('video', 'lines', 'categories'));
    }

    public function deleteServiceLine(Video $video, ServiceAccessLine $line)
    {
        if ($line->video_id !== $video->id) {
            abort(404);
        }
        if ($line->is_assigned) {
            return redirect()->route('admin.videos.manage')->with('error', 'No puedes borrar una linea ya asignada.');
        }

        $line->delete();
        return redirect()->route('admin.videos.manage')->with('success', 'Linea eliminada.');
    }

    // clearAllVideos method removed - database management section removed

    /**
     * Admin: Direct upload to Vercel Blob (bypasses Laravel file processing)
     */
    public function directUpload(Request $request)
    {
        try {
            $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
            if (empty($blobToken)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vercel Blob token not configured'
                ]);
            }

            // Check if Vercel Blob classes are available
            if (!class_exists('VercelBlobPhp\Client')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Vercel Blob package not available'
                ]);
            }

            // Get the raw file data from the request body
            $fileData = $request->getContent();
            $filename = $request->header('X-Filename', 'thumbnail-' . time() . '.jpg');
            $contentType = $request->header('X-Content-Type', 'image/jpeg');

            if (empty($fileData)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No file data received'
                ]);
            }

            // Generate unique filename in thumbnails folder
            $blobPath = 'thumbnails/' . uniqid() . '-' . $filename;

            $blobClient = new BlobClient($blobToken);

            // Upload directly to Vercel Blob
            $options = new CommonCreateBlobOptions(
                access: 'public',
                addRandomSuffix: false,
                contentType: $contentType,
            );

            // Use the put method with the file data
            $result = $blobClient->put($blobPath, $fileData, $options);

            Log::info('Direct upload to Vercel Blob successful', ['path' => $blobPath, 'url' => $result->url]);

            return response()->json([
                'success' => true,
                'blob_url' => $result->url,
                'blob_path' => $blobPath
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to upload directly to Vercel Blob', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Helper method to delete blob thumbnail
     */
    private function deleteBlobThumbnail(string $blobUrl): void
    {
        try {
            if (class_exists('VercelBlobPhp\Client')) {
                $blobToken = Setting::get('vercel_blob_token') ?: env('test_READ_WRITE_TOKEN');
                if ($blobToken) {
                    $blobClient = new BlobClient($blobToken);
                    $blobClient->del([$blobUrl]);
                    Log::info('Old thumbnail deleted from Vercel Blob', ['url' => $blobUrl]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete old thumbnail from Vercel Blob', ['error' => $e->getMessage()]);
        }
    }
}

