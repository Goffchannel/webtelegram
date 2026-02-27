@extends('layout')

@section('title', ($creator->creator_store_name ?? $creator->name) . ' - ' . $category->name)

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1 class="mb-1"><i class="fas fa-store text-primary"></i> {{ $creator->creator_store_name ?? $creator->name }}</h1>
        <p class="text-muted mb-0">Categoria: <strong>{{ $category->name }}</strong></p>
    </div>
    <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="btn btn-outline-secondary">Ver categorias</a>
</div>

<div class="row align-items-start">
@forelse($videos as $video)
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm">
            @if ($video->hasThumbnail())
                <div class="video-thumbnail-container position-relative" style="max-height: 300px; overflow: hidden;">
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
                <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-video fa-3x text-muted"></i>
                </div>
            @endif

            <div class="card-body d-flex flex-column">
                <h5 class="card-title">{{ $video->title }}</h5>
                <p class="card-text text-muted flex-grow-1">{{ $video->description ?: 'High-quality video content' }}</p>
                @if($video->isServiceProduct())
                    <span class="badge text-bg-info mb-2">Servicio {{ $video->duration_days ?? 30 }} dias</span>
                @endif

                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        @if ($video->isFree())
                            <span class="h4 text-success mb-0">FREE</span>
                        @else
                            <span class="h4 text-primary mb-0">${{ number_format($video->price, 2) }}</span>
                        @endif
                        @if ($video->telegram_file_id)
                            <span class="badge text-bg-success"><i class="fas fa-check"></i> Ready</span>
                        @else
                            <span class="badge text-bg-warning"><i class="fas fa-clock"></i> Preparing</span>
                        @endif
                    </div>

                    @if ($video->isServiceProduct() && ($video->available_service_lines_count ?? 0) < 1)
                        <button class="btn btn-outline-danger w-100" disabled>
                            <i class="fas fa-ban"></i> SIN STOCK
                        </button>
                    @elseif ($video->telegram_file_id || $video->isServiceProduct())
                        @if ($video->isFree())
                            <a href="{{ route('video.show', $video) }}" class="btn btn-success w-100">
                                <i class="fas fa-download"></i> Get Free Video
                            </a>
                        @else
                            @if($creator->is_admin)
                                <a href="{{ route('payment.form', $video) }}" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart"></i> Comprar con Stripe
                                </a>
                            @else
                                <a href="{{ route('creator.checkout.form', ['creator' => $creator->creator_slug, 'video' => $video->id]) }}" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart"></i> Comprar video
                                </a>
                            @endif
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
@empty
    <div class="col-12"><div class="alert alert-secondary">No hay videos en esta categoria.</div></div>
@endforelse
</div>

<div class="mt-4">{{ $videos->links() }}</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const thumbnailContainers = document.querySelectorAll('.video-thumbnail-container');

        thumbnailContainers.forEach(container => {
            const img = container.querySelector('.video-thumbnail');
            const allowPreview = img?.dataset.allowPreview === 'true';
            const blurIntensity = img?.dataset.blurIntensity;
            const originalFilter = img?.style.filter || '';
            const previewLockOverlay = container.querySelector('.preview-lock-overlay');

            container.style.transition = 'max-height 1s ease';
            if (img) {
                img.style.transition = 'object-fit 0.3s ease-in-out, filter 0.3s ease-in-out';
            }
            if (previewLockOverlay) {
                previewLockOverlay.style.transition = 'opacity 0.3s ease-in-out';
            }

            container.addEventListener('mouseenter', () => {
                container.style.maxHeight = '1000px';
                if (img) {
                    img.style.objectFit = 'contain';
                    if (allowPreview) img.style.filter = 'none';
                }
                if (previewLockOverlay) previewLockOverlay.style.opacity = '0';
            });

            container.addEventListener('mouseleave', () => {
                container.style.maxHeight = '300px';
                if (img) {
                    img.style.objectFit = 'cover';
                    if (allowPreview && blurIntensity) {
                        img.style.filter = `blur(${blurIntensity}px)`;
                    } else {
                        img.style.filter = originalFilter;
                    }
                }
                if (previewLockOverlay) previewLockOverlay.style.opacity = '1';
            });
        });
    });
</script>
@endsection
