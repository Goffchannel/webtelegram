@extends('admin.layout')

@section('title', 'Manage Videos')

@section('content')
    <div class="container-fluid">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4">
                            <h2 class="fw-bold mb-0"><i class="fas fa-video me-2 text-primary"></i>Videos</h2>
                        </div>

                        <!-- Token Management Section -->
                        <div class="mb-6 border-b pb-6 dark:border-gray-700">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-blue-600 dark:text-blue-400">📋 API Configuration</h3>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#tokenModal">
                                    <i class="fas fa-cog"></i> Configure API Keys
                                </button>
                            </div>

                            <!-- Token Status Summary -->
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="card h-100 {{ $telegramToken ? 'border-success' : 'border-danger' }}">
                                        <div class="card-body text-center py-3">
                                            <i class="fab fa-telegram-plane fa-lg {{ $telegramToken ? 'text-success' : 'text-danger' }} mb-1"></i>
                                            <h6 class="card-title mb-1 small">Telegram Bot</h6>
                                            <p class="card-text text-muted small mb-0">
                                                {{ $telegramToken ? 'Configured' : 'Not Configured' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card h-100 {{ $stripeKey && $stripeSecret ? 'border-success' : 'border-danger' }}">
                                        <div class="card-body text-center py-3">
                                            <i class="fab fa-stripe fa-lg {{ $stripeKey && $stripeSecret ? 'text-success' : 'text-danger' }} mb-1"></i>
                                            <h6 class="card-title mb-1 small">Stripe Payments</h6>
                                            <p class="card-text text-muted small mb-0">
                                                {{ $stripeKey && $stripeSecret ? 'Configured' : 'Not Configured' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card h-100 {{ $stripeWebhookSecret ? 'border-success' : 'border-warning' }}">
                                        <div class="card-body text-center py-3">
                                            <i class="fas fa-shield-alt fa-lg {{ $stripeWebhookSecret ? 'text-success' : 'text-warning' }} mb-1"></i>
                                            <h6 class="card-title mb-1 small">Webhook Security</h6>
                                            <p class="card-text text-muted small mb-0">
                                                {{ $stripeWebhookSecret ? 'Secured' : 'Basic Mode' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card h-100 {{ $vercelBlobToken ? 'border-success' : 'border-danger' }}">
                                        <div class="card-body text-center py-3">
                                            <i class="fas fa-cloud-upload-alt fa-lg {{ $vercelBlobToken ? 'text-success' : 'text-danger' }} mb-1"></i>
                                            <h6 class="card-title mb-1 small">Vercel Blob Storage</h6>
                                            <p class="card-text text-muted small mb-0">
                                                {{ $vercelBlobToken ? 'Configured' : 'Not Configured' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        {{-- Success/Error Messages --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        {{-- Sync User Configuration --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-cog text-warning"></i> Sync User Configuration
                                </h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $syncUserTelegramId = \App\Models\Setting::get('sync_user_telegram_id');
                                    $syncUserName = \App\Models\Setting::get('sync_user_name');
                                @endphp

                                @if ($syncUserTelegramId)
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> <strong>Sync User Configured:</strong><br>
                                        <strong>Name:</strong> {{ $syncUserName }}<br>
                                        <strong>Telegram ID:</strong> {{ $syncUserTelegramId }}<br>
                                        <small class="text-muted">Only videos from this user will be auto-captured. The bot
                                            will interact
                                            normally with all other users but ignore their videos.</small>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSyncUser()">
                                        <i class="fas fa-trash"></i> Remove Sync User
                                    </button>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>No sync user
                                            configured.</strong><br>
                                        You need to set a sync user to control which Telegram user's videos can be imported.
                                    </div>
                                    <form onsubmit="setSyncUser(event)" class="row g-3">
                                        <div class="col-md-4">
                                            <label for="sync-telegram-id" class="form-label">Telegram User ID</label>
                                            <input type="text" class="form-control" id="sync-telegram-id"
                                                placeholder="e.g., 123456789" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="sync-name" class="form-label">Display Name</label>
                                            <input type="text" class="form-control" id="sync-name"
                                                placeholder="e.g., John Doe" required>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i> Set Sync User
                                            </button>
                                        </div>
                </form>
                                @endif
            </div>
        </div>

                        {{-- Webhook Management --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-wifi text-primary"></i> Webhook Management
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                <div class="col-md-8">
                                        <div class="d-flex align-items-center">
                                            <span><strong>Webhook Status:</strong></span>
                                            <span id="webhook-status" class="ms-2 badge text-bg-secondary">Checking...</span>
                    </div>
                                        <small class="text-muted">
                                            <strong>Active:</strong> Auto-capture videos when sent to bot<br>
                                            <strong>Disabled:</strong> Videos are not automatically captured (use manual
                                            import)
                                        </small>
                </div>
                <div class="col-md-4 text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-warning btn-sm"
                                                onclick="toggleWebhook('deactivate')" id="deactivate-webhook-btn">
                                                <i class="fas fa-stop"></i> Disable Webhook
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm"
                                                onclick="toggleWebhook('reactivate')" id="reactivate-webhook-btn">
                                                <i class="fas fa-play"></i> Enable Webhook
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Bot Testing --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-stethoscope text-success"></i> Bot Testing & Diagnostics
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Test the bot connection and view recent message history for debugging purposes.
                                </p>
                                <button type="button" class="btn btn-success" onclick="testTelegramConnection()">
                                    <i class="fas fa-search"></i> Test Bot Connection & View Messages
                                </button>

                                {{-- Test Results Display --}}
                                <div id="test-results" class="mt-4" style="display: none;">
                                    <h6>Bot Connection Test Results:</h6>
                                    <div id="test-content" class="border p-3"
                                        style="max-height: 400px; overflow-y: auto;">
                                        <!-- Test results will be populated here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- HIDDEN FOR NOW: Manual Video Import - Only show when webhook is disabled --}}
                        {{--
                        <div class="card mb-4" id="manual-import-section" style="display: none;">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-tools text-info"></i> Manual Video Import
                                </h5>
                            </div>
                            <div class="card-body">
                                @if (!$syncUserTelegramId)
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Please configure a sync user first
                                        before using manual
                                        import.
                                    </div>
                                @else
                                    <p class="text-muted">
                                        <i class="fas fa-info-circle"></i> Manual import is only available when webhook is
                                        disabled.
                                        Use "Test Bot Connection" above to find video file IDs, then import them here.
                                    </p>

                                    <div class="row">
                                        <div class="col-md-8">
                                            <label for="manual-file-id" class="form-label">Video File ID</label>
                                            <input type="text" id="manual-file-id" class="form-control"
                                                placeholder="Paste file ID from bot test results">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="manual-price" class="form-label">Price ($)</label>
                                            <input type="number" id="manual-price" class="form-control"
                                                placeholder="Price" value="4.99" step="0.01">
                </div>
            </div>

                                    <div class="row mt-3">
                                        <div class="col-md-8">
                                            <label for="manual-title" class="form-label">Video Title</label>
                                            <input type="text" id="manual-title" class="form-control"
                                                placeholder="Video title" value="Imported Video">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="button" class="btn btn-success w-100"
                                                onclick="quickImport('${fileId}', '${(msg.caption || 'Imported Video').replace(/'/g, "\\'")}')">
                                                <i class="fas fa-upload"></i> Import Video
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        --}}

            {{-- Videos Table --}}
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-video"></i> Videos ({{ count($videos) }})
                                </h5>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modal-admin-create-iptv">
                                    <i class="fas fa-plus me-1"></i> Crear producto IPTV
                                </button>
                            </div>
                            <div class="card-body">
                                @if (count($videos) > 0)

                                {{-- Bulk action bar --}}
                                <div id="bulk-bar" style="display:none; position:sticky; top:64px; z-index:100; background:#1e2d45; border:1px solid #3a5080; border-radius:10px; padding:10px 16px; margin-bottom:12px; display:none; align-items:center; gap:12px; flex-wrap:wrap;">
                                    <span id="bulk-count" style="color:#a0b4d0; font-size:.88rem; font-weight:600;"></span>
                                    <select id="bulk-category-select" class="form-select form-select-sm" style="max-width:260px; background:#0d1117; color:#e2e8f0; border-color:#3a5080;">
                                        <option value="">— Seleccionar categoría —</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }} -- {{ $category->creator->creator_store_name ?? $category->creator->name ?? 'Sin creador' }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-primary" onclick="bulkChangeCategory()" style="white-space:nowrap;">
                                        <i class="fas fa-layer-group me-1"></i> Cambiar categoría
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                                        <i class="fas fa-times me-1"></i> Deseleccionar
                                    </button>
                                </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width:36px;">
                                <input type="checkbox" id="check-all" class="form-check-input" title="Seleccionar todos" onchange="toggleAll(this)">
                            </th>
                                                    <th>Title</th>
                                                    <th>Creador</th>
                                                    <th>Description</th>
                            <th>Price</th>
                                                    <th>Category</th>
                                                    <th>Thumbnail</th>
                                                    <th>File ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                                                @foreach ($videos as $video)
                            <tr data-video-id="{{ $video->id }}">
                                <td style="vertical-align:middle;">
                                    <input type="checkbox" class="form-check-input video-check" value="{{ $video->id }}" onchange="updateBulkBar()">
                                </td>
                                <td>
                                                            <strong>{{ $video->title }}</strong><br>
                                                            <small class="text-muted">Created:
                                                                {{ $video->created_at->format('M j, Y H:i') }}</small>
                                </td>
                                <td>
                                    @if($video->creator)
                                        <div>
                                            <span class="fw-semibold small">{{ $video->creator->name }}</span><br>
                                            @if($video->creator->creator_slug)
                                                <a href="{{ route('creator.storefront', $video->creator) }}"
                                                   target="_blank"
                                                   class="small text-muted text-decoration-none">
                                                    <i class="fas fa-store me-1"></i>{{ $video->creator->creator_slug }}
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Admin</span>
                                    @endif
                                </td>
                                <td>
                                                            <div
                                                                style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                                                {{ $video->description ?? 'No description' }}
                                    </div>
                                </td>
                                <td>
                                    @if ($video->price > 0)
                                                                <span
                                                                    class="badge text-bg-success">${{ number_format($video->price, 2) }}</span>
                                                            @else
                                                                <span class="badge text-bg-warning">Free</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge text-bg-info">{{ $video->category->name ?? 'N/A' }}</span>
                                                        </td>
                                                        <td>
                                                            @if ($video->hasThumbnail())
                                                                <div class="d-flex align-items-center">
                                                                    <img src="{{ $video->getThumbnailUrl() }}"
                                                                        alt="Thumbnail"
                                                                        style="width: 40px; height: 30px; object-fit: cover;"
                                                                        class="rounded me-2">
                                                                    @if ($video->shouldShowBlurred())
                                                                        <span class="badge text-bg-warning">Blurred</span>
                                                                    @else
                                                                        <span class="badge text-bg-success">Clear</span>
                                                                    @endif
                                                                </div>
                                    @else
                                                                <span class="text-muted">No thumbnail</span>
                                    @endif
                                </td>
                                                        <td>
                                                            <code
                                                                style="font-size: 10px;">{{ $video->telegram_file_id }}</code>
                                                        </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                                                <!-- Edit Video Details Button -->
                                                                <button type="button" class="btn btn-outline-primary" title="Edit Video Details"
                                                                    onclick="editVideoDetails({{ $video->id }}, '{{ addslashes($video->title) }}', '{{ addslashes($video->description) }}', {{ $video->price }}, {{ $video->show_blurred_thumbnail ? 'true' : 'false' }}, {{ $video->blur_intensity }}, {{ $video->allow_preview ? 'true' : 'false' }}, {{ $video->category_id ?? 1 }})">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <!-- Edit Thumbnail Button -->
                                                                <button type="button" class="btn btn-outline-info" title="Edit Thumbnail"
                                                                    onclick="editVideoThumbnail({{ $video->id }}, '{{ $video->getThumbnailUrl() }}', '{{ $video->thumbnail_url }}', '{{ $video->thumbnail_blob_url }}', '{{ addslashes($video->title) }}', '{{ addslashes($video->description) }}', {{ $video->price }}, {{ $video->blur_intensity }}, {{ $video->show_blurred_thumbnail ? 'true' : 'false' }}, {{ $video->allow_preview ? 'true' : 'false' }}, {{ $video->category_id ?? 1 }})">
                                                                    <i class="fas fa-image"></i>
                                                                </button>
                                                                @if($video->creator?->creator_slug)
                                                                <a class="btn btn-outline-success"
                                                                   title="Ver página del producto"
                                                                   href="{{ route('creator.checkout.form', ['creator' => $video->creator, 'video' => $video]) }}"
                                                                   target="_blank">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                                @endif
                                                                @if($video->isServiceProduct())
                                                                <a class="btn btn-outline-secondary" title="Manage service lines" href="{{ route('admin.videos.service-lines.show', $video) }}">
                                                                    <i class="fas fa-key"></i>
                                                                </a>
                                                                @endif
                                                                @if ($syncUserTelegramId)
                                                                    <button type="button" class="btn btn-outline-success"
                                                                        onclick="testVideo({{ $video->id }})">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                        @endif
                                                                <button type="button" class="btn btn-outline-danger"
                                                                    onclick="deleteVideo({{ $video->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                    </div>
                                </td>
                            </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    @if ($videos->hasPages())
                                        <div class="d-flex justify-content-center mt-4">
                                            {{ $videos->links() }}
                                        </div>
                                    @endif
                                @else
                                    <div class="text-center py-5">
                                        <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No videos found</h5>
                                        <p class="text-muted">Configure sync user and enable webhook to auto-capture
                                            videos, or use manual
                                            import.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Token Management Modal --}}
    <div class="modal fade " id="tokenModal" tabindex="-1" style="margin-bottom: 8px;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">API Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="tokenForm">
                        <!-- Help Guide -->
                        <div class="alert alert-info mb-4">
                            <h6 class="fw-bold mb-2">🔑 Where to get your API keys:</h6>
                            <ul class="mb-0 small">
                                <li><strong>Telegram Bot Token:</strong> Create a bot with @BotFather on Telegram, it will
                                    give you a token like "123456789:ABCdefGHIjklMNOpqrsTUVwxyz"</li>
                                <li><strong>Stripe Keys:</strong> Get from <a href="https://dashboard.stripe.com/apikeys"
                                        target="_blank" class="text-decoration-none">Stripe Dashboard → API Keys</a> (use
                                    test keys for testing, live keys for production)</li>
                                <li><strong>Stripe Webhook Secret:</strong> Get from <a
                                        href="https://dashboard.stripe.com/webhooks" target="_blank"
                                        class="text-decoration-none">Stripe Dashboard → Webhooks</a> (optional but
                                    recommended for security)</li>
                                <li><strong>Vercel Blob Token:</strong> Get from <a href="https://vercel.com/dashboard"
                                        target="_blank" class="text-decoration-none">Vercel Dashboard → Storage → Blob</a>
                                    (required for thumbnail uploads on serverless deployments)</li>
                            </ul>
                        </div>

                        <!-- Telegram Bot Token -->
                        <div class="mb-3">
                            <label for="modal_telegram_token" class="form-label">
                                <i class="fab fa-telegram-plane text-primary"></i> Telegram Bot Token
                            </label>
                            <input type="password" class="form-control" id="modal_telegram_token"
                                placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                                value="{{ $telegramToken ? str_repeat('*', 30) . substr($telegramToken, -10) : '' }}">
                            <div class="form-text">Get this from @BotFather on Telegram</div>
                        </div>

                        <!-- Stripe Publishable Key -->
                        <div class="mb-3">
                            <label for="modal_stripe_key" class="form-label">
                                <i class="fab fa-stripe text-success"></i> Stripe Publishable Key
                            </label>
                            <input type="text" class="form-control" id="modal_stripe_key"
                                placeholder="pk_test_... or pk_live_..."
                                value="{{ $stripeKey ? substr($stripeKey, 0, 15) . str_repeat('*', 20) : '' }}">
                            <div class="form-text">Starts with pk_test_ (testing) or pk_live_ (production)</div>
                        </div>

                        <!-- Stripe Secret Key -->
                        <div class="mb-3">
                            <label for="modal_stripe_secret" class="form-label">
                                <i class="fab fa-stripe text-success"></i> Stripe Secret Key
                            </label>
                            <input type="password" class="form-control" id="modal_stripe_secret"
                                placeholder="sk_test_... or sk_live_..."
                                value="{{ $stripeSecret ? substr($stripeSecret, 0, 10) . str_repeat('*', 30) : '' }}">
                            <div class="form-text">Starts with sk_test_ (testing) or sk_live_ (production)</div>
                        </div>

                        <!-- Stripe Webhook Secret -->
                        <div class="mb-3">
                            <label for="modal_stripe_webhook_secret" class="form-label">
                                <i class="fas fa-shield-alt text-warning"></i> Stripe Webhook Secret <span
                                    class="text-muted">(Optional)</span>
                            </label>
                            <input type="password" class="form-control" id="modal_stripe_webhook_secret"
                                placeholder="whsec_..."
                                value="{{ $stripeWebhookSecret ? str_repeat('*', 30) . substr($stripeWebhookSecret, -10) : '' }}">
                            <div class="form-text">For secure webhook processing (recommended for production)</div>
                        </div>

                        <!-- Creator Monthly Price -->
                        <div class="mb-3">
                            <label for="modal_creator_monthly_price_usd" class="form-label">
                                <i class="fas fa-user-tie text-primary"></i> Creator Monthly Price (USD)
                            </label>
                            <input type="number" class="form-control" id="modal_creator_monthly_price_usd"
                                min="1" max="999" step="0.01"
                                value="{{ number_format((float) ($creatorMonthlyPriceUsd ?? 5), 2, '.', '') }}">
                            <div class="form-text">Price creators pay monthly. Stripe price will be generated automatically.</div>
                        </div>

                        <!-- Vercel Blob Storage Token -->
                        <div class="mb-3">
                            <label for="modal_vercel_blob_token" class="form-label">
                                <i class="fas fa-cloud-upload-alt text-info"></i> Vercel Blob Storage Token
                            </label>
                            <input type="password" class="form-control" id="modal_vercel_blob_token"
                                placeholder="vercel_blob_rw_..."
                                value="{{ $vercelBlobToken ? str_repeat('*', 40) . substr($vercelBlobToken, -10) : '' }}">
                            <div class="form-text">Required for thumbnail uploads on Vercel (serverless deployment)</div>
                        </div>

                        <!-- Vercel Blob Store ID -->
                        <div class="mb-3">
                            <label for="modal_vercel_blob_store_id" class="form-label">
                                <i class="fas fa-database text-info"></i> Vercel Blob Store ID
                            </label>
                            <input type="text" class="form-control" id="modal_vercel_blob_store_id"
                                placeholder="store_lplRsSrAbxTyf1Og"
                                value="{{ $vercelBlobStoreId ?? '' }}">
                            <div class="form-text">Your unique store ID from Vercel Dashboard → Storage → Blob</div>
                        </div>

                        <!-- Vercel Blob Base URL -->
                        <div class="mb-3">
                            <label for="modal_vercel_blob_base_url" class="form-label">
                                <i class="fas fa-link text-info"></i> Vercel Blob Base URL
                            </label>
                            <input type="url" class="form-control" id="modal_vercel_blob_base_url"
                                placeholder="https://lplrssrabxtyf1og.public.blob.vercel-storage.com"
                                value="{{ $vercelBlobBaseUrl ?? '' }}">
                            <div class="form-text">Base URL for your blob store (without trailing slash)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAllTokens()">
                        <i class="fas fa-save"></i> Save All Keys
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Video Details Modal --}}
    <div class="modal fade" id="editVideoDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Video Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editVideoDetailsForm" onsubmit="updateVideoDetails(event)" action="javascript:void(0)">
                    <div class="modal-body">
                        <!-- Basic Video Details -->
                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary"></i> Video Information</h6>
                        <div class="mb-3">
                            <label for="edit-details-title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit-details-title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-details-description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit-details-description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit-details-price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="edit-details-price" name="price"
                                step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-details-category" class="form-label">Category</label>
                            <select class="form-select" id="edit-details-category" name="category_id" required>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }} -- {{ $category->creator->creator_store_name ?? $category->creator->name ?? 'Sin creador' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr>

                        <!-- Display Settings -->
                        <h6 class="fw-bold mb-3"><i class="fas fa-eye text-warning"></i> Customer Display Settings</h6>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit-details-show-blurred"
                                    name="show_blurred" value="1" onchange="toggleDetailsDisplaySettings()">
                                <label class="form-check-label" for="edit-details-show-blurred">
                                    Show Blurred Thumbnail to Customers
                                </label>
                            </div>
                            <div class="form-text">When enabled, customers see a blurred version until purchase</div>
                        </div>

                        <div class="mb-3" id="details-blur-intensity-container">
                            <label for="edit-details-blur-intensity" class="form-label">Blur Intensity</label>
                            <input type="range" class="form-range" id="edit-details-blur-intensity"
                                name="blur_intensity" min="1" max="20" value="10">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Light</small>
                                <small class="text-muted">Heavy</small>
                            </div>
                            <div class="form-text">Intensity: <span id="details-blur-intensity-display">10</span>px</div>
                        </div>

                        <div class="mb-3" id="details-allow-preview-container">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit-details-allow-preview"
                                    name="allow_preview" value="1">
                                <label class="form-check-label" for="edit-details-allow-preview">
                                    Allow Unblurred Preview
                                </label>
                            </div>
                            <div class="form-text">Allow customers to see unblurred version on hover/click</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Video Thumbnail Modal --}}
    <div class="modal fade" id="editVideoThumbnailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Video Thumbnail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editVideoThumbnailForm" method="POST" action="" enctype="multipart/form-data" onsubmit="prepareTraditionalThumbnailSubmission(event)">
                    @csrf
                    @method('PUT')

                    <!-- Hidden fields to satisfy validation requirements -->
                    <input type="hidden" id="hidden-title" name="title" value="">
                    <input type="hidden" id="hidden-description" name="description" value="">
                    <input type="hidden" id="hidden-price" name="price" value="">
                    <input type="hidden" id="hidden-category" name="category_id" value="">
                    <input type="hidden" id="hidden-blur-intensity" name="blur_intensity" value="">
                    <input type="hidden" id="hidden-show-blurred" name="show_blurred" value="">
                    <input type="hidden" id="hidden-allow-preview" name="allow_preview" value="">
                    <input type="hidden" id="hidden-blob-url" name="thumbnail_blob_url" value="">

                    <div class="modal-body">
                        <h6 class="fw-bold mb-3"><i class="fas fa-image text-success"></i> Thumbnail Management</h6>

                        <!-- Current Thumbnail Preview -->
                        <div id="current-thumbnail-preview" class="mb-3" style="display: none;">
                            <label class="form-label">Current Thumbnail</label>
                            <div class="border rounded p-2">
                                <img id="current-thumbnail-img" src="" alt="Current thumbnail"
                                    class="img-fluid rounded" style="max-height: 200px;">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                                    onclick="removeThumbnail()">
                                    <i class="fas fa-trash"></i> Remove Current
                                </button>
                            </div>
                        </div>

                        <!-- Upload New Thumbnail -->
                        <div class="mb-3">
                            <label for="edit-thumbnail" class="form-label">Upload New Thumbnail</label>
                            <input type="file" class="form-control" id="edit-thumbnail" name="thumbnail"
                                accept="image/*" onchange="previewThumbnail(this)">
                            <div class="form-text">Upload JPG, PNG, or GIF image (max 2MB)</div>
                        </div>

                        <!-- External Thumbnail URL -->
                        <div class="mb-3">
                            <label for="edit-thumbnail-url" class="form-label">Or Use External URL</label>
                            <input type="url" class="form-control" id="edit-thumbnail-url"
                                name="thumbnail_url" placeholder="https://example.com/image.jpg">
                            <div class="form-text">Provide a direct link to an image</div>
                        </div>

                        <!-- Thumbnail Preview (New Upload) -->
                        <div id="new-thumbnail-preview" class="mb-3" style="display: none;">
                            <label class="form-label">New Thumbnail Preview</label>
                            <div class="border rounded p-2">
                                <img id="new-thumbnail-img" src="" alt="New thumbnail"
                                    class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-image"></i> Save Thumbnail
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
{{-- Modal: Crear producto IPTV (Admin) --}}
<div class="modal fade" id="modal-admin-create-iptv" tabindex="-1" aria-labelledby="modal-admin-create-iptv-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('creator.videos.store') }}">
                @csrf
                <input type="hidden" name="product_type" value="service_access">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-admin-create-iptv-label">
                        <i class="fas fa-tv me-2 text-success"></i>Crear producto IPTV
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($categories->isEmpty())
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            No hay categorías. Ve al <a href="{{ route('creator.dashboard') }}">Dashboard de creador</a> y crea una primero.
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Título del producto <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Ej: IPTV Premium 30 días" required maxlength="200">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Precio (USD) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="9.99" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duración (días)</label>
                            <input type="number" name="duration_days" class="form-control" min="1" max="365" value="30">
                            <div class="form-text">Por defecto 30 días</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required @disabled($categories->isEmpty())>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="1000" placeholder="Descripción del servicio IPTV..."></textarea>
                    </div>
                    <div class="alert alert-info mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Tras crear el producto, aparecerá en la lista con el botón <i class="fas fa-key"></i> para añadir la línea IPTV.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" @disabled($categories->isEmpty())>
                        <i class="fas fa-plus me-1"></i>Crear producto IPTV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        let isWebhookActive = false;

        // Page load initialization
        document.addEventListener('DOMContentLoaded', function() {
            checkWebhookStatus();
        });

        // ── Bulk category change ────────────────────────────────────
        function getSelectedIds() {
            return [...document.querySelectorAll('.video-check:checked')].map(c => c.value);
        }

        function updateBulkBar() {
            const ids = getSelectedIds();
            const bar = document.getElementById('bulk-bar');
            const countEl = document.getElementById('bulk-count');
            if (ids.length > 0) {
                bar.style.display = 'flex';
                countEl.textContent = ids.length + ' video' + (ids.length !== 1 ? 's' : '') + ' seleccionado' + (ids.length !== 1 ? 's' : '');
            } else {
                bar.style.display = 'none';
            }
            document.getElementById('check-all').indeterminate =
                ids.length > 0 && ids.length < document.querySelectorAll('.video-check').length;
            document.getElementById('check-all').checked =
                ids.length > 0 && ids.length === document.querySelectorAll('.video-check').length;
        }

        function toggleAll(checkbox) {
            document.querySelectorAll('.video-check').forEach(c => c.checked = checkbox.checked);
            updateBulkBar();
        }

        function clearSelection() {
            document.querySelectorAll('.video-check').forEach(c => c.checked = false);
            document.getElementById('check-all').checked = false;
            document.getElementById('check-all').indeterminate = false;
            document.getElementById('bulk-bar').style.display = 'none';
        }

        function bulkChangeCategory() {
            const ids = getSelectedIds();
            const categoryId = document.getElementById('bulk-category-select').value;
            if (!ids.length) return showAlert('warning', 'No hay videos seleccionados.');
            if (!categoryId) return showAlert('warning', 'Selecciona una categoría primero.');

            if (!confirm(`¿Cambiar la categoría de ${ids.length} video(s)?`)) return;

            fetch('{{ route('admin.videos.bulk-category') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ video_ids: ids, category_id: categoryId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    clearSelection();
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showAlert('danger', data.error || 'Error al cambiar categorías.');
                }
            })
            .catch(() => showAlert('danger', 'Error de red.'));
        }

        // Check webhook status and update UI accordingly
        function checkWebhookStatus() {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            fetch('{{ route('admin.videos.webhook-status') }}', { signal: controller.signal })
                .then(response => response.json())
                .then(data => {
                    clearTimeout(timeoutId);
                    const statusBadge = document.getElementById('webhook-status');
                    if (data.success) {
                        isWebhookActive = data.webhook_info.url && data.webhook_info.url.length > 0;
                        statusBadge.textContent = isWebhookActive ? 'Active' : 'Disabled';
                        statusBadge.className = isWebhookActive ? 'ms-2 badge text-bg-success' : 'ms-2 badge text-bg-warning';

                        // Update manual import section visibility
                        updateManualImportVisibility();
                    } else {
                        statusBadge.textContent = 'Error';
                        statusBadge.className = 'ms-2 badge text-bg-danger';
                    }
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    const statusBadge = document.getElementById('webhook-status');
                    statusBadge.textContent = error.name === 'AbortError' ? 'Timeout' : 'Error';
                    statusBadge.className = 'ms-2 badge text-bg-danger';
                    console.error('Failed to check webhook status:', error);
                });
        }

        // Update manual import section based on webhook status
        function updateManualImportVisibility() {
            const manualImportSection = document.getElementById('manual-import-section');

            if (manualImportSection) {
                if (isWebhookActive) {
                    manualImportSection.style.display = 'none';
                } else {
                    manualImportSection.style.display = 'block';
                }
            }
        }

        // Toggle webhook
        function toggleWebhook(action) {
            const url = action === 'reactivate' ?
                '{{ route('admin.videos.reactivate-webhook') }}' :
                '{{ route('admin.videos.deactivate-webhook') }}';

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        setTimeout(checkWebhookStatus, 1000);
                    } else {
                        showAlert('danger', data.error || 'Operation failed');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Network error occurred');
                    console.error('Webhook toggle failed:', error);
                });
        }

        // Set sync user
        function setSyncUser(event) {
            event.preventDefault();
            const telegramId = document.getElementById('sync-telegram-id').value.trim();
            const name = document.getElementById('sync-name').value.trim();

            if (!telegramId || !name) {
                showAlert('warning', 'Please fill in both fields');
                return;
            }

            fetch('{{ route('admin.videos.set-sync-user') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        telegram_id: telegramId,
                        name: name
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Sync user configured successfully!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', data.error || 'Failed to set sync user');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Network error occurred');
                    console.error('Set sync user failed:', error);
                });
        }

        // Remove sync user
        function removeSyncUser() {
            if (!confirm('Are you sure you want to remove the sync user configuration?')) {
                return;
            }

            fetch('{{ route('admin.videos.remove-sync-user') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Sync user removed successfully!');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', data.error || 'Failed to remove sync user');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Network error occurred');
                    console.error('Remove sync user failed:', error);
                });
        }

        // Test Telegram connection
        function testTelegramConnection() {
            const testResults = document.getElementById('test-results');
            const testContent = document.getElementById('test-content');

            testContent.innerHTML =
                '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Testing bot connection...</div>';
            testResults.style.display = 'block';

            fetch('{{ route('admin.videos.test-connection') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '<div class="mb-3">';
                        html += '<h6>Bot Information:</h6>';
                        html += `<p><strong>Name:</strong> ${data.data.bot_info.first_name || 'Unknown'}<br>`;
                        html += `<strong>Username:</strong> @${data.data.bot_info.username || 'Unknown'}<br>`;
                        html += `<strong>ID:</strong> ${data.data.bot_info.id || 'Unknown'}</p>`;

                        html += '<h6>Webhook Status:</h6>';
                        html += `<p><strong>Active:</strong> ${data.data.webhook_active ? 'Yes' : 'No'}<br>`;
                        html +=
                            `<strong>Can use getUpdates:</strong> ${data.data.can_use_getupdates ? 'Yes' : 'No'}</p>`;

                        if (data.data.message_analysis && data.data.message_analysis.length > 0) {
                            html += '<h6>Recent Messages with Video File IDs:</h6>';
                            html += '<div class="table-responsive">';
                            html += '<table class="table table-sm table-bordered">';
                            html +=
                                '<thead><tr><th>From</th><th>Date</th><th>Video File ID</th><th>Caption</th><th>Action</th></tr></thead><tbody>';

                            data.data.message_analysis.forEach(msg => {
                                if (msg.has_video || msg.video_file_id || msg.document_file_id) {
                                    const fileId = msg.video_file_id || msg.document_file_id;
                                    html += '<tr class="table-success">';
                                    html +=
                                        `<td>${msg.from_first_name || 'Unknown'} (@${msg.from_username || 'no username'})<br><small>ID: ${msg.from_id}</small></td>`;
                                    html += `<td><small>${msg.date}</small></td>`;
                                    html += `<td><code style="font-size: 10px;">${fileId}</code></td>`;
                                    html += `<td>${msg.caption || msg.text || '-'}</td>`;
                                    if (!isWebhookActive) {
                                        html +=
                                            `<td><button class="btn btn-success btn-xs" onclick="quickImport('${fileId}', '${(msg.caption || 'Imported Video').replace(/'/g, "\\'")}')">Import</button></td>`;
                                    } else {
                                        html += '<td><span class="text-muted">Webhook active</span></td>';
                                    }
                                    html += '</tr>';
                                }
                            });

                            html += '</tbody></table></div>';
                            html +=
                                `<p><strong>Summary:</strong> Found ${data.data.video_messages_found} video messages out of ${data.data.total_messages_found} total messages.</p>`;
                        } else if (data.data.message) {
                            html += `<div class="alert alert-warning">${data.data.message}</div>`;
                        } else {
                            html +=
                                '<div class="alert alert-info">No recent video messages found in conversation history.</div>';
                        }

                        html += '</div>';
                        testContent.innerHTML = html;
                    } else {
                        testContent.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
                    }
                })
                .catch(error => {
                    testContent.innerHTML = '<div class="alert alert-danger">Network error occurred during test</div>';
                    console.error('Test connection failed:', error);
                });
        }

        // Quick import from test results
        function quickImport(fileId, title) {
            if (isWebhookActive) {
                showAlert('warning', 'Cannot import manually while webhook is active. Disable webhook first.');
                return;
            }

            document.getElementById('manual-file-id').value = fileId;
            document.getElementById('manual-title').value = title;
            // manualImportVideo(); // Commented out - manual import feature hidden
        }

        // HIDDEN FOR NOW: Manual import video function
        /*
        function manualImportVideo() {
            if (isWebhookActive) {
                showAlert('warning', 'Manual import is disabled while webhook is active.');
                return;
            }

            const fileId = document.getElementById('manual-file-id').value.trim();
            const title = document.getElementById('manual-title').value.trim();
            const price = document.getElementById('manual-price').value;

            if (!fileId) {
                showAlert('warning', 'Please enter a file ID');
                return;
            }

            fetch('{{ route('admin.videos.manual-import') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        file_id: fileId,
                        title: title || 'Imported Video',
                        price: parseFloat(price) || 4.99
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        // Clear form
                        document.getElementById('manual-file-id').value = '';
                        document.getElementById('manual-title').value = 'Imported Video';
                        // Reload page to show new video
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', data.error || 'Import failed');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Network error occurred');
                    console.error('Manual import failed:', error);
                });
        }
        */

        // Test video
        function testVideo(id) {
            fetch(`/admin/videos/${id}/test`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Test video sent successfully!');
                    } else {
                        showAlert('danger', data.error || 'Failed to send test video');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Network error occurred');
                    console.error('Test video failed:', error);
                });
        }

        // Delete video
        function deleteVideo(id) {
            if (!confirm('Are you sure you want to delete this video?')) {
                return;
            }

            fetch(`/admin/videos/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Video deleted successfully!');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showAlert('danger', data.error || 'Failed to delete video');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Network error occurred');
                    console.error('Delete video failed:', error);
                });
        }

        // Token Management Functions
        function saveAllTokens() {
            const telegramToken = document.getElementById('modal_telegram_token').value.trim();
            const stripeKey = document.getElementById('modal_stripe_key').value.trim();
            const stripeSecret = document.getElementById('modal_stripe_secret').value.trim();
            const stripeWebhookSecret = document.getElementById('modal_stripe_webhook_secret').value.trim();
            const creatorMonthlyPriceUsd = document.getElementById('modal_creator_monthly_price_usd').value.trim();
            const vercelBlobToken = document.getElementById('modal_vercel_blob_token').value.trim();
            const vercelBlobStoreId = document.getElementById('modal_vercel_blob_store_id').value.trim();
            const vercelBlobBaseUrl = document.getElementById('modal_vercel_blob_base_url').value.trim();

            // Validate required fields
            if (!telegramToken && !stripeKey && !stripeSecret && !stripeWebhookSecret && !creatorMonthlyPriceUsd && !vercelBlobToken && !vercelBlobStoreId && !vercelBlobBaseUrl) {
                showAlert('warning', 'Please enter at least one token to save');
                return;
            }

            const tokens = {};
            if (telegramToken && !telegramToken.includes('*')) tokens.telegram_token = telegramToken;
            if (stripeKey && !stripeKey.includes('*')) tokens.stripe_key = stripeKey;
            if (stripeSecret && !stripeSecret.includes('*')) tokens.stripe_secret = stripeSecret;
            if (stripeWebhookSecret && !stripeWebhookSecret.includes('*')) tokens.stripe_webhook_secret = stripeWebhookSecret;
            if (creatorMonthlyPriceUsd) tokens.creator_monthly_price_usd = creatorMonthlyPriceUsd;
            if (vercelBlobToken && !vercelBlobToken.includes('*')) tokens.vercel_blob_token = vercelBlobToken;
            if (vercelBlobStoreId) tokens.vercel_blob_store_id = vercelBlobStoreId;
            if (vercelBlobBaseUrl) tokens.vercel_blob_base_url = vercelBlobBaseUrl;

            fetch('{{ route('admin.tokens.save-all') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(tokens)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'API tokens saved successfully!');
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('tokenModal')).hide();
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', data.error || 'Failed to save tokens');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Network error occurred');
                    console.error('Save tokens failed:', error);
                });
        }

        // Edit Video Details Functions
        function editVideoDetails(id, title, description, price, showBlurred, blurIntensity, allowPreview, categoryId) {
            // Set basic video fields
            document.getElementById('edit-details-title').value = title;
            document.getElementById('edit-details-description').value = description;
            document.getElementById('edit-details-price').value = price;
            document.getElementById('edit-details-category').value = categoryId;

            // Set blur settings - convert string 'true'/'false' to boolean
            const isBlurredEnabled = showBlurred === true || showBlurred === 'true';
            const isPreviewEnabled = allowPreview === true || allowPreview === 'true';

            document.getElementById('edit-details-show-blurred').checked = isBlurredEnabled;
            document.getElementById('edit-details-blur-intensity').value = blurIntensity || 10;
            document.getElementById('details-blur-intensity-display').textContent = blurIntensity || 10;
            document.getElementById('edit-details-allow-preview').checked = isPreviewEnabled;

            // Toggle display settings based on blur checkbox
            toggleDetailsDisplaySettings();

            // Store the video ID for submission
            document.getElementById('editVideoDetailsForm').setAttribute('data-video-id', id);

            const modal = new bootstrap.Modal(document.getElementById('editVideoDetailsModal'));
            modal.show();
        }

        // Edit Video Thumbnail Functions
        function editVideoThumbnail(id, thumbnailPath, thumbnailUrl, thumbnailBlobUrl, title, description, price, blurIntensity, showBlurred, allowPreview, categoryId) {
            // Set thumbnail fields
            document.getElementById('edit-thumbnail-url').value = thumbnailUrl || '';

            // Show current thumbnail if exists
            const currentThumbnailPreview = document.getElementById('current-thumbnail-preview');
            const currentThumbnailImg = document.getElementById('current-thumbnail-img');

            if (thumbnailPath) {
                currentThumbnailImg.src = thumbnailPath;
                currentThumbnailPreview.style.display = 'block';
            } else {
                currentThumbnailPreview.style.display = 'none';
            }

            // Reset upload preview
            document.getElementById('new-thumbnail-preview').style.display = 'none';
            document.getElementById('edit-thumbnail').value = '';

            // Set form action for traditional submission
            const form = document.getElementById('editVideoThumbnailForm');
            form.action = `/admin/videos/${id}`;

            // Populate hidden fields with current video data for validation
            document.getElementById('hidden-title').value = title || '';
            document.getElementById('hidden-description').value = description || '';
            document.getElementById('hidden-price').value = price || '0';
            document.getElementById('hidden-category').value = categoryId || '1';
            document.getElementById('hidden-blur-intensity').value = blurIntensity || '10';
            document.getElementById('hidden-show-blurred').value = showBlurred === true || showBlurred === 'true' ? '1' : '0';
            document.getElementById('hidden-allow-preview').value = allowPreview === true || allowPreview === 'true' ? '1' : '0';
            document.getElementById('hidden-blob-url').value = thumbnailBlobUrl || '';

            const modal = new bootstrap.Modal(document.getElementById('editVideoThumbnailModal'));
            modal.show();
        }



        function updateVideoDetails(event) {
            // CRITICAL: Prevent any form submission to avoid FormData processing
            event.preventDefault();
            event.stopPropagation();

            const form = event.target;
            const videoId = form.getAttribute('data-video-id');

            // Show loading state
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitButton.disabled = true;

            async function performUpdate() {
                try {
                    // Collect form data manually (NO FormData to avoid serverless issues)
                    const titleEl = form.querySelector('[name="title"]');
                    const descriptionEl = form.querySelector('[name="description"]');
                    const priceEl = form.querySelector('[name="price"]');
                    const blurIntensityEl = form.querySelector('[name="blur_intensity"]');
                    const showBlurredEl = form.querySelector('[name="show_blurred"]');
                    const allowPreviewEl = form.querySelector('[name="allow_preview"]');
                    const categoryEl = form.querySelector('[name="category_id"]');

                    if (!titleEl || !priceEl) {
                        throw new Error('Required form fields not found');
                    }

                    const formData = {
                        title: titleEl.value,
                        description: descriptionEl ? descriptionEl.value : '',
                        price: priceEl.value,
                        category_id: categoryEl ? categoryEl.value : 1,
                        blur_intensity: blurIntensityEl ? blurIntensityEl.value : 10,
                        show_blurred: showBlurredEl ? (showBlurredEl.checked ? 1 : 0) : 0,
                        allow_preview: allowPreviewEl ? (allowPreviewEl.checked ? 1 : 0) : 0,
                        _method: 'PUT',
                        _token: '{{ csrf_token() }}'
                    };

                    console.log('Submitting video details update:', formData);

                    // Submit as JSON instead of FormData to avoid serverless middleware issues
                    const response = await fetch(`/admin/videos/${videoId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(formData)
                    });

                    const responseText = await response.text();
                    console.log('Raw response:', responseText);

                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError);
                        if (responseText.includes('500') || responseText.includes('Internal Server Error')) {
                            throw new Error('Server error occurred. Please try again or contact support.');
                        }
                        throw new Error('Invalid server response. Please try again.');
                    }

                    if (data.success) {
                        showAlert('success', 'Video details updated successfully!');
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('editVideoDetailsModal')).hide();
                            window.location.reload();
                        }, 1500);
                    } else {
                        const errorMsg = data.message || data.error || 'Failed to update video details';
                        showAlert('danger', errorMsg);
                    }
                } catch (error) {
                    showAlert('danger', 'Update failed: ' + error.message);
                    console.error('Update video details failed:', error);
                } finally {
                    // Restore button state
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }
            }

            performUpdate();
        }

        function prepareTraditionalThumbnailSubmission(event) {
            // This function prepares the thumbnail form for traditional submission
            event.preventDefault();

            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            const thumbnailFileEl = form.querySelector('[name="thumbnail"]');
            const thumbnailFile = thumbnailFileEl && thumbnailFileEl.files ? thumbnailFileEl.files[0] : null;

            // Show loading state
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitButton.disabled = true;

            async function handleSubmission() {
                try {
                    // If there's a file to upload, upload to Vercel Blob first
                    if (thumbnailFile && thumbnailFile.size > 0) {
                        console.log('Uploading thumbnail to Vercel Blob...');

                        const uploadResponse = await fetch('/admin/videos/direct-upload', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Filename': thumbnailFile.name,
                                'X-Content-Type': thumbnailFile.type || 'image/jpeg'
                            },
                            body: thumbnailFile
                        });

                        const uploadResult = await uploadResponse.json();

                        if (!uploadResult.success) {
                            throw new Error('Thumbnail upload failed: ' + uploadResult.error);
                        }

                        console.log('Thumbnail uploaded successfully:', uploadResult.blob_url);

                        // Update the hidden blob URL field
                        document.getElementById('hidden-blob-url').value = uploadResult.blob_url;
                    }

                    // Now submit the form traditionally (this will cause a page reload)
                    form.submit();

                } catch (error) {
                    showAlert('danger', 'Upload failed: ' + error.message);
                    console.error('Thumbnail upload failed:', error);

                    // Restore button state on error
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                }
            }

            handleSubmission();
        }

        // Thumbnail management functions
        function previewThumbnail(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('new-thumbnail-img').src = e.target.result;
                    document.getElementById('new-thumbnail-preview').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeThumbnail() {
            document.getElementById('current-thumbnail-preview').style.display = 'none';
            // You could add an AJAX call here to actually remove the thumbnail from the server
        }

        // Blur intensity slider update for details modal
        document.addEventListener('DOMContentLoaded', function() {
            const detailsBlurSlider = document.getElementById('edit-details-blur-intensity');
            const detailsBlurDisplay = document.getElementById('details-blur-intensity-display');

            if (detailsBlurSlider && detailsBlurDisplay) {
                detailsBlurSlider.addEventListener('input', function() {
                    detailsBlurDisplay.textContent = this.value;
                });
            }
        });

        // Show alert
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

            document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid')
                .firstChild);

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Toggle customer display settings for details modal
        function toggleDetailsDisplaySettings() {
            const showBlurred = document.getElementById('edit-details-show-blurred').checked;
            const blurIntensityContainer = document.getElementById('details-blur-intensity-container');
            const allowPreviewContainer = document.getElementById('details-allow-preview-container');
            const blurIntensitySlider = document.getElementById('edit-details-blur-intensity');
            const allowPreviewCheckbox = document.getElementById('edit-details-allow-preview');

            if (showBlurred) {
                // Enable blur settings
                blurIntensityContainer.style.display = 'block';
                allowPreviewContainer.style.display = 'block';
                blurIntensityContainer.style.opacity = '1';
                allowPreviewContainer.style.opacity = '1';
                blurIntensitySlider.disabled = false;
                allowPreviewCheckbox.disabled = false;
            } else {
                // Disable but show blur settings with reduced opacity
                blurIntensityContainer.style.display = 'block';
                allowPreviewContainer.style.display = 'block';
                blurIntensityContainer.style.opacity = '0.5';
                allowPreviewContainer.style.opacity = '0.5';
                blurIntensitySlider.disabled = true;
                allowPreviewCheckbox.disabled = true;
                // Also uncheck the allow preview when blur is disabled
                allowPreviewCheckbox.checked = false;
            }
        }
    </script>
@endsection
