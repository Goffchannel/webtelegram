@extends('layout')

@if (isset($category))
    @section('title', 'Videos in ' . $category->name)
@else
    @section('title', 'Video Store')
@endif

@section('content')
    <div class="text-center mb-5">
        @if (isset($category))
            <h1><i class="fas fa-layer-group text-primary"></i> {{ $category->name }}</h1>
            <p class="lead text-muted">Premium videos delivered instantly to your Telegram</p>
            <a href="{{ route('categories.index') }}">&larr; Back to all categories</a>
        @else
            <h1><i class="fas fa-play-circle text-primary"></i> Video Store</h1>
            <p class="lead text-muted">Premium videos delivered instantly to your Telegram</p>
        @endif
    </div>

    @if ($videos->count() > 0)
        <div class="row align-items-start">
            @foreach ($videos as $video)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        @if ($video->hasThumbnail())
                            <div class="video-thumbnail-container position-relative" data-video-id="{{ $video->id }}" style="max-height: 300px; overflow: hidden; position: relative;">
                                <img src="{{ $video->getThumbnailUrl() }}" class="card-img-top video-thumbnail"
                                    alt="Video thumbnail"
                                    style="width: 100%; height: auto; object-fit: cover; object-position: top; {{ $video->shouldShowBlurred() ? $video->getBlurredThumbnailStyle() : '' }}"
                                    @if ($video->allow_preview) data-allow-preview="true" data-blur-intensity="{{ $video->blur_intensity }}" @endif>
                                @if ($video->shouldShowBlurred())
                                    <div class="position-absolute top-50 start-50 translate-middle preview-lock-overlay" style="opacity: 1;">
                                        <div class="text-center text-white bg-dark bg-opacity-75 px-3 py-2 rounded">
                                            <i class="fas fa-lock fa-2x mb-2"></i>
                                            <div class="small">Preview after purchase</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="card-img-top d-flex align-items-center justify-content-center"
                                style="height: 200px;">
                                <i class="fas fa-video fa-3x text-muted"></i>
                            </div>
                        @endif

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $video->title }}</h5>
                            <p class="card-text text-muted flex-grow-1">
                                {{ $video->description ?: 'High-quality video content' }}
                            </p>

                            @if ($video->duration)
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> Duration: {{ gmdate('i:s', $video->duration) }}
                                    </small>
                                </div>
                            @endif

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    @if ($video->isFree())
                                        <span class="h4 text-success mb-0">FREE</span>
                                    @else
                                        <span class="h4 text-primary mb-0">${{ number_format($video->price, 2) }}</span>
                                    @endif

                                    @if ($video->telegram_file_id)
                                        <span class="badge text-bg-success">
                                            <i class="fas fa-check"></i> Ready
                                        </span>
                                    @else
                                        <span class="badge text-bg-warning">
                                            <i class="fas fa-clock"></i> Preparing
                                        </span>
                                    @endif
                                </div>

                                @if ($video->telegram_file_id)
                                    @if ($video->isFree())
                                        <a href="{{ route('video.show', $video) }}" class="btn btn-success w-100">
                                            <i class="fas fa-download"></i> Get Free Video
                                        </a>
                                    @else
                                        <a href="{{ route('payment.form', $video) }}" class="btn btn-primary w-100">
                                            <i class="fas fa-shopping-cart"></i> Purchase Video
                                        </a>
                                    @endif
                                @else
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="fas fa-hourglass-half"></i> Coming Soon
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if ($videos->hasPages())
            <div class="d-flex justify-content-center mt-5">
                {{ $videos->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-5">
            <i class="fas fa-video fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No videos available yet</h4>
            <p class="text-muted">Check back soon for new video content!</p>
        </div>
    @endif


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const thumbnailContainers = document.querySelectorAll('.video-thumbnail-container');

            thumbnailContainers.forEach(container => {
                const img = container.querySelector('.video-thumbnail');
                const allowPreview = img.dataset.allowPreview === 'true';
                const blurIntensity = img.dataset.blurIntensity;
                const originalFilter = img.style.filter; // Capture initial filter from inline style
                const previewLockOverlay = container.querySelector('.preview-lock-overlay');

                // Set initial transition for smooth animation for JS controlled styles
                container.style.transition = 'max-height 1s ease';
                img.style.transition = 'object-fit 0.3s ease-in-out, filter 0.3s ease-in-out';
                if (previewLockOverlay) {
                    previewLockOverlay.style.transition = 'opacity 0.3s ease-in-out';
                }

                container.addEventListener('mouseenter', () => {
                    container.style.maxHeight = '1000px'; // Expand
                    img.style.objectFit = 'contain';
                    if (allowPreview) {
                        img.style.filter = 'none';
                    }
                    if (previewLockOverlay) {
                        previewLockOverlay.style.opacity = '0';
                    }
                });

                container.addEventListener('mouseleave', () => {
                    container.style.maxHeight = '300px'; // Collapse
                    img.style.objectFit = 'cover';
                    if (allowPreview && blurIntensity) {
                        img.style.filter = `blur(${blurIntensity}px)`;
                    } else if (!allowPreview) {
                        img.style.filter = originalFilter; // Reapply original filter if no preview
                    }
                    if (previewLockOverlay) {
                        previewLockOverlay.style.opacity = '1';
                    }
                });
            });
        });
    </script>
@endsection
