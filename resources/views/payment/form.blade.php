@extends('layout')

@section('title', 'Purchase ' . $video->title)

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-shopping-cart"></i> Comprar</h4>
                </div>
                <div class="card-body">
                    <!-- Video Details with Thumbnail -->
                    <div class="card mb-4">
                        @if ($video->hasThumbnail())
                        <div class="position-relative" style="height: 500px;">
                            <img src="{{ $video->getThumbnailUrl() }}" class="card-img-top" alt="Video thumbnail"
                                style="height: 500px; object-fit: cover; object-position: top; {{ $video->shouldShowBlurred() ? $video->getBlurredThumbnailStyle() : '' }}{{ $video->allow_preview ? ' cursor: pointer;' : '' }}"
                                @if ($video->allow_preview) onclick="toggleThumbnailBlur(this, {{ $video->blur_intensity }})"
                                    title="Click to preview" @endif>
                            @if ($video->shouldShowBlurred())
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <div class="text-center text-white bg-dark bg-opacity-75 px-4 py-3 rounded">
                                        <i class="fas fa-lock fa-3x mb-3"></i>
                                        <div class="h6">Video Preview</div>
                                        <div class="small">
                                            @if ($video->allow_preview)
                                                Click to preview •
                                            @endif
                                            Purchase to see full video
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $video->title }}</h5>
                            <p class="card-text">{{ $video->description }}</p>
                            @if($video->long_description)
                                <div class="alert alert-secondary" style="white-space: pre-wrap;">{{ $video->long_description }}</div>
                            @endif
                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Price:</strong>
                                    <span class="h5 text-success">${{ number_format($video->price, 2) }}</span>
                                </div>
                                <div class="col-sm-6">
                                    @if($video->isServiceProduct())
                                        <strong>Servicio:</strong> {{ $video->duration_days ?? 30 }} dias
                                    @else
                                        <strong>Duration:</strong> {{ $video->duration ?? 'N/A' }}
                                    @endif
                                </div>
                            </div>
                            @if($video->isServiceProduct())
                                <div class="mt-2">
                                    <span class="badge text-bg-info">Producto de acceso</span>
                                    <span class="badge text-bg-success">Stock: {{ $video->availableServiceLines()->count() }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="{{ route('payment.process', $video) }}" method="POST">
                        @csrf
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="telegram_username" class="form-label">
                                        <i class="fab fa-telegram"></i> Telegram Username <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">@</span>
                                        <input type="text"
                                               class="form-control @error('telegram_username') is-invalid @enderror"
                                               id="telegram_username"
                                               name="telegram_username"
                                               value="{{ old('telegram_username') }}"
                                               placeholder="your_username"
                                               required>
                                    </div>
                                    @error('telegram_username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Enter your Telegram username (without @). The video will be sent to this account.
                                    </small>
                                </div>
                            </div>
                        </div>

                        @if($bot['is_configured'])
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>How it works:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Complete payment using Stripe (secure)</li>
                                    <li>Start a chat with our bot: <a href="{{ $bot['url'] }}" target="_blank">{{ $bot['username'] }}</a></li>
                                    <li>Your video will be delivered automatically!</li>
                                </ol>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Setup Required:</strong>
                                <p class="mb-2 mt-2">The Telegram bot is not configured yet. Please contact the administrator to complete the setup before making purchases.</p>
                                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-cog"></i> Admin Setup
                                </a>
                            </div>
                        @endif

                        <div class="d-grid gap-2">
                            @if($bot['is_configured'])
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card"></i>
                                    Pay ${{ number_format($video->price, 2) }} with Stripe
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-lg" disabled>
                                    <i class="fas fa-cog"></i> Payment Disabled - Bot Setup Required
                                </button>
                            @endif
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <a href="{{ route('videos.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Videos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const button = document.getElementById('paymentButton');
        const originalText = button.innerHTML;
        const username = document.getElementById('telegram_username').value.trim();

        if (!username) {
            showAlert('error', 'Please enter your Telegram username');
            return;
        }

        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        button.disabled = true;

        // Clear any existing error messages
        clearExistingAlerts();

        fetch('/api/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                video_id: {{ $video->id }},
                telegram_username: username
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                // Handle duplicate purchase or other errors
                if (data.existing_purchase) {
                    showDuplicatePurchaseError(data.error, data.existing_purchase);
                } else {
                    showAlert('error', data.error);
                }

                button.innerHTML = originalText;
                button.disabled = false;
            } else if (data.session_url) {
                // Redirect to Stripe checkout
                window.location.href = data.session_url;
            } else {
                showAlert('error', 'Unexpected response from server');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Payment error:', error);
            showAlert('error', 'Payment setup failed. Please try again.');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insert after the form
        const form = document.getElementById('paymentForm');
        form.insertAdjacentHTML('afterend', alertHtml);
    }

    function showDuplicatePurchaseError(message, purchaseInfo) {
        const alertHtml = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Duplicate Purchase Detected</h6>
                <p class="mb-2">${message}</p>
                <hr>
                <small class="text-muted">
                    <strong>Purchase Date:</strong> ${purchaseInfo.purchase_date}<br>
                    <strong>Status:</strong> ${purchaseInfo.verification_status} / ${purchaseInfo.delivery_status}
                </small>
                ${purchaseInfo.purchase_uuid ? `
                    <div class="mt-2">
                        <a href="/purchase/${purchaseInfo.purchase_uuid}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>View Purchase Details
                        </a>
                    </div>
                ` : ''}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insert after the form
        const form = document.getElementById('paymentForm');
        form.insertAdjacentHTML('afterend', alertHtml);
    }

    function clearExistingAlerts() {
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
    }
</script>
@endsection
