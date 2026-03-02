@extends('layout')

@section('title', ($creator->creator_store_name ?? $creator->name) . ' - ' . $category->name)

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1 class="mb-1"><i class="fas fa-store text-primary"></i> {{ $creator->creator_store_name ?? $creator->name }}</h1>
        <p class="text-muted mb-0">Categoria: <strong>{{ $category->name }}</strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="btn btn-outline-secondary">Ver categorias</a>
        @if(!$creator->is_admin)
        <a href="{{ route('creator.cart.show', $creator->creator_slug) }}"
           class="btn btn-primary position-relative" id="cartHeaderBtn">
            <i class="fas fa-shopping-cart"></i> Carrito
            <span id="cartBadgeHeader" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
        </a>
        @endif
    </div>
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
                                <button class="btn btn-primary w-100"
                                        data-video-id="{{ $video->id }}"
                                        data-video-title="{{ addslashes($video->title) }}"
                                        data-video-price="{{ $video->price }}"
                                        data-video-type="{{ $video->isServiceProduct() ? 'service' : 'video' }}"
                                        onclick="addToCart(this)">
                                    <i class="fas fa-cart-plus me-1"></i> Añadir al carrito
                                </button>
                                <a href="{{ route('creator.checkout.form', ['creator' => $creator->creator_slug, 'video' => $video->id]) }}"
                                   class="btn btn-link btn-sm w-100 text-muted mt-1 py-0">
                                    <i class="fas fa-bolt me-1"></i>Comprar ahora (sin carrito)
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

@if(!$creator->is_admin)
{{-- Cart Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" style="width:360px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title"><i class="fas fa-shopping-cart me-2 text-primary"></i>Mi carrito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div id="offcanvasItems" class="flex-grow-1 overflow-auto px-3 py-2"></div>
        <div class="border-top p-3">
            <div id="offcanvasTotal" class="d-flex justify-content-between fw-bold mb-3">
                <span>Total</span><span>$0.00</span>
            </div>
            <a href="{{ route('creator.cart.show', $creator->creator_slug) }}" class="btn btn-success w-100">
                <i class="fas fa-arrow-right me-1"></i>Ir al checkout
            </a>
        </div>
    </div>
</div>

{{-- Floating cart button (shown only when cart has items) --}}
<button class="btn btn-primary rounded-circle shadow position-fixed d-flex align-items-center justify-content-center"
        id="floatingCartBtn"
        style="bottom:1.5rem; right:1.5rem; width:3.5rem; height:3.5rem; z-index:1030; display:none!important;"
        data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas"
        title="Ver carrito">
    <i class="fas fa-shopping-cart"></i>
    <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.7rem; display:none;">0</span>
</button>

<script>
const CREATOR_SLUG = '{{ $creator->creator_slug }}';
const CART_KEY     = `cart_${CREATOR_SLUG}`;

function getCart() { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }
function saveCart(cart) { localStorage.setItem(CART_KEY, JSON.stringify(cart)); }

function addToCart(btn) {
    const id    = parseInt(btn.dataset.videoId);
    const title = btn.dataset.videoTitle;
    const price = parseFloat(btn.dataset.videoPrice);
    const type  = btn.dataset.videoType;

    let cart = getCart();
    if (cart.find(i => i.id === id)) {
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cartOffcanvas')).show();
        return;
    }
    cart.push({ id, title, price, type });
    saveCart(cart);
    renderOffcanvas();
    updateBadges();

    btn.innerHTML = '<i class="fas fa-check me-1"></i> Añadido';
    btn.classList.replace('btn-primary', 'btn-success');
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-cart-plus me-1"></i> Añadir al carrito';
        btn.classList.replace('btn-success', 'btn-primary');
    }, 1500);

    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cartOffcanvas')).show();
}

function removeFromCart(id) {
    saveCart(getCart().filter(i => i.id !== id));
    renderOffcanvas();
    updateBadges();
}

function renderOffcanvas() {
    const cart      = getCart();
    const container = document.getElementById('offcanvasItems');
    const totalEl   = document.getElementById('offcanvasTotal').querySelector('span:last-child');

    if (cart.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4 small"><i class="fas fa-shopping-cart fa-2x mb-2 d-block opacity-25"></i>Tu carrito está vacío.</p>';
        totalEl.textContent = '$0.00';
        return;
    }

    let html = '';
    cart.forEach((item, idx) => {
        html += `
        <div class="d-flex justify-content-between align-items-center py-2 ${idx < cart.length - 1 ? 'border-bottom' : ''}">
            <div class="me-2" style="min-width:0">
                <div class="fw-semibold small text-truncate">${escHtml(item.title)}</div>
                ${item.type === 'service' ? '<span class="badge text-bg-info" style="font-size:.65rem">Servicio</span>' : ''}
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="text-primary fw-bold small">$${item.price.toFixed(2)}</span>
                <button class="btn btn-link text-danger p-0" style="line-height:1;" onclick="removeFromCart(${item.id})" title="Quitar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    });
    container.innerHTML = html;
    totalEl.textContent = `$${cart.reduce((s, i) => s + i.price, 0).toFixed(2)}`;
}

function updateBadges() {
    const count = getCart().length;
    const floatBtn     = document.getElementById('floatingCartBtn');
    const badge        = document.getElementById('cartBadge');
    const headerBadge  = document.getElementById('cartBadgeHeader');

    if (count > 0) {
        floatBtn.style.cssText = 'bottom:1.5rem;right:1.5rem;width:3.5rem;height:3.5rem;z-index:1030;display:flex!important;';
        badge.textContent = count;
        badge.style.display = '';
        if (headerBadge) { headerBadge.textContent = count; headerBadge.style.display = ''; }
    } else {
        floatBtn.style.cssText = 'bottom:1.5rem;right:1.5rem;width:3.5rem;height:3.5rem;z-index:1030;display:none!important;';
        badge.style.display = 'none';
        if (headerBadge) headerBadge.style.display = 'none';
    }
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', function () {
    renderOffcanvas();
    updateBadges();

    const thumbnailContainers = document.querySelectorAll('.video-thumbnail-container');
    thumbnailContainers.forEach(container => {
        const img = container.querySelector('.video-thumbnail');
        const allowPreview   = img?.dataset.allowPreview === 'true';
        const blurIntensity  = img?.dataset.blurIntensity;
        const originalFilter = img?.style.filter || '';
        const overlay        = container.querySelector('.preview-lock-overlay');

        container.style.transition = 'max-height 1s ease';
        if (img) img.style.transition = 'object-fit 0.3s ease-in-out, filter 0.3s ease-in-out';
        if (overlay) overlay.style.transition = 'opacity 0.3s ease-in-out';

        container.addEventListener('mouseenter', () => {
            container.style.maxHeight = '1000px';
            if (img) { img.style.objectFit = 'contain'; if (allowPreview) img.style.filter = 'none'; }
            if (overlay) overlay.style.opacity = '0';
        });
        container.addEventListener('mouseleave', () => {
            container.style.maxHeight = '300px';
            if (img) {
                img.style.objectFit = 'cover';
                img.style.filter = (allowPreview && blurIntensity) ? `blur(${blurIntensity}px)` : originalFilter;
            }
            if (overlay) overlay.style.opacity = '1';
        });
    });
});
</script>
@endif
@endsection
