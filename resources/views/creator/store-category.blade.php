@extends('layout')

@section('title', ($creator->creator_store_name ?? $creator->name) . ' - ' . $category->name)

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
    --sf-bg: #0e1117;
    --sf-surface: #161b25;
    --sf-border: #252d3d;
    --sf-accent: #4f8ef7;
    --sf-success: #22c55e;
    --sf-warning: #f59e0b;
    --sf-danger: #ef4444;
    --sf-text: #e2e8f0;
    --sf-muted: #64748b;
    --sf-font: 'Outfit', sans-serif;
}

.sc-shell { font-family: var(--sf-font); }
.sc-shell *:not(i):not([class*="fa"]):not([class*="fab"]) { font-family: var(--sf-font); }

/* ── Header ── */
.sc-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
}
.sc-header-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.sc-store-name {
    font-size: 1rem; font-weight: 500;
    color: var(--bs-secondary-color, #6c757d);
    display: flex; align-items: center; gap: 6px;
    text-decoration: none;
}
.sc-store-name:hover { color: var(--sf-accent); }
.sc-divider { color: var(--bs-secondary-color, #6c757d); opacity: .4; }
.sc-cat-name {
    font-size: 1.15rem; font-weight: 700;
    color: var(--bs-body-color, #212529);
    letter-spacing: -.02em;
}

/* ── Header buttons ── */
.sc-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px;
    font-size: .84rem; font-weight: 600;
    cursor: pointer; border: none; transition: all .2s;
    text-decoration: none; font-family: var(--sf-font);
}
.sc-btn-outline {
    background: transparent;
    border: 1px solid var(--bs-border-color, #dee2e6);
    color: var(--bs-secondary-color, #6c757d);
}
.sc-btn-outline:hover { border-color: var(--sf-accent); color: var(--sf-accent); }
.sc-btn-cart {
    background: var(--sf-accent); color: #fff;
    box-shadow: 0 2px 10px rgba(79,142,247,.3);
    position: relative;
}
.sc-btn-cart:hover { background: #3b7de8; color: #fff; }
.sc-cart-badge-hdr {
    position: absolute; top: -6px; right: -6px;
    background: var(--sf-danger); color: #fff;
    border-radius: 50%; width: 18px; height: 18px;
    font-size: .62rem; font-weight: 700;
    display: none; align-items: center; justify-content: center;
}

/* ── Video card ── */
.vc-card {
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 14px;
    overflow: hidden;
    transition: box-shadow .22s ease, border-color .22s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.vc-card:hover {
    box-shadow: 0 8px 28px rgba(0,0,0,.14);
    border-color: var(--sf-accent);
}

/* ── Thumbnail — hover expands ── */
.vc-thumb {
    max-height: 260px;
    overflow: hidden;
    position: relative;
    background: #0e1117;
    transition: max-height .7s ease;
    flex-shrink: 0;
}
.vc-thumb.expanded { max-height: 1000px; }
.vc-thumb img {
    width: 100%;
    height: auto;
    object-fit: cover;
    object-position: top;
    display: block;
    transition: object-fit .25s ease, filter .25s ease;
}
.vc-thumb-placeholder {
    height: 200px;
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.2);
    font-size: 2.5rem;
    background: #0e1117;
}
.vc-lock-overlay {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,.72);
    color: #fff; border-radius: 10px;
    padding: 10px 16px; text-align: center;
    pointer-events: none;
    transition: opacity .25s ease;
}
.vc-lock-overlay i { font-size: 1.4rem; display: block; margin-bottom: 4px; }
.vc-lock-overlay span { font-size: .72rem; }

/* ── Card body ── */
.vc-body {
    padding: 16px;
    display: flex; flex-direction: column;
    flex: 1;
}
.vc-title {
    font-size: .97rem; font-weight: 700;
    color: var(--bs-body-color, #212529);
    margin: 0 0 5px;
    letter-spacing: -.01em;
}
.vc-desc {
    font-size: .8rem;
    color: var(--bs-secondary-color, #6c757d);
    margin: 0 0 10px;
    flex-grow: 1;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.45;
}
.vc-service-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(79,142,247,.12);
    color: var(--sf-accent);
    border: 1px solid rgba(79,142,247,.22);
    border-radius: 20px; padding: 2px 9px;
    font-size: .7rem; font-weight: 600;
    margin-bottom: 10px;
    width: fit-content;
}
.vc-footer {
    display: flex; align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.vc-price {
    font-size: 1.3rem; font-weight: 700;
    color: var(--sf-accent);
    font-family: 'DM Mono', monospace;
    letter-spacing: -.02em;
}
.vc-price.free { color: var(--sf-success); }
.vc-status-ready {
    font-size: .7rem; font-weight: 600;
    color: var(--sf-success);
    display: flex; align-items: center; gap: 4px;
}
.vc-status-soon {
    font-size: .7rem; font-weight: 600;
    color: var(--sf-warning);
    display: flex; align-items: center; gap: 4px;
}

/* ── CTA Buttons ── */
.vc-btn {
    width: 100%; padding: 9px 16px;
    border-radius: 9px; border: none;
    font-size: .88rem; font-weight: 600;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center;
    gap: 7px; text-decoration: none;
    font-family: var(--sf-font);
}
.vc-btn-primary { background: var(--sf-accent); color: #fff; }
.vc-btn-primary:hover { background: #3b7de8; color: #fff; }
.vc-btn-success { background: var(--sf-success); color: #fff; }
.vc-btn-success:hover { background: #16a34a; color: #fff; }
.vc-btn-disabled { background: var(--bs-secondary-bg, #e9ecef); color: var(--bs-secondary-color, #6c757d); cursor: not-allowed; }
.vc-btn-link {
    background: transparent; border: none;
    color: var(--bs-secondary-color, #6c757d);
    font-size: .78rem; padding: 4px 0;
    margin-top: 4px;
    text-decoration: none;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.vc-btn-link:hover { color: var(--sf-accent); }

/* ── Empty ── */
.sc-empty {
    grid-column: 1/-1;
    text-align: center; padding: 60px 0;
    color: var(--bs-secondary-color, #6c757d);
}
.sc-empty i { font-size: 2.5rem; opacity: .3; display: block; margin-bottom: 14px; }

/* ── Offcanvas dark ── */
#cartOffcanvas {
    background: #161b25;
    border-left: 1px solid #252d3d;
    color: #e2e8f0;
}
#cartOffcanvas .offcanvas-header { border-color: #252d3d !important; }
#cartOffcanvas .offcanvas-title { color: #e2e8f0; font-family: var(--sf-font); font-weight: 600; }
#cartOffcanvas .btn-close { filter: invert(1) opacity(.6); }
#cartOffcanvas .border-top { border-color: #252d3d !important; }
</style>
@endsection

@section('content')
<div class="sc-shell">

{{-- Header --}}
<div class="sc-header">
    <div class="sc-header-left">
        <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="sc-store-name">
            <i class="fas fa-store"></i> {{ $creator->creator_store_name ?? $creator->name }}
        </a>
        <span class="sc-divider">/</span>
        <span class="sc-cat-name">{{ $category->name }}</span>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="sc-btn sc-btn-outline">
            <i class="fas fa-arrow-left"></i> Categorías
        </a>
        @if(!$creator->is_admin)
        <a href="{{ route('creator.cart.show', $creator->creator_slug) }}" class="sc-btn sc-btn-cart" id="cartHeaderBtn">
            <i class="fas fa-shopping-cart"></i> Carrito
            <span id="cartBadgeHeader" class="sc-cart-badge-hdr">0</span>
        </a>
        @endif
    </div>
</div>

{{-- Grid --}}
<div class="row g-4">
@forelse($videos as $video)
    <div class="col-sm-6 col-lg-4">
        <div class="vc-card">

            {{-- Thumbnail --}}
            @if($video->hasThumbnail())
                <div class="vc-thumb video-thumbnail-container">
                    <img src="{{ $video->getThumbnailUrl() }}"
                         class="video-thumbnail"
                         alt="{{ $video->title }}"
                         style="{{ $video->shouldShowBlurred() ? $video->getBlurredThumbnailStyle() : '' }}"
                         @if($video->allow_preview)
                             data-allow-preview="true"
                             data-blur-intensity="{{ $video->blur_intensity }}"
                         @endif>
                    @if($video->shouldShowBlurred())
                        <div class="vc-lock-overlay preview-lock-overlay">
                            <i class="fas fa-lock"></i>
                            <span>Preview after purchase</span>
                        </div>
                    @endif
                </div>
            @else
                <div class="vc-thumb-placeholder">
                    <i class="fas fa-video"></i>
                </div>
            @endif

            {{-- Body --}}
            <div class="vc-body">
                <h5 class="vc-title">{{ $video->title }}</h5>
                <p class="vc-desc">{{ $video->description ?: 'Contenido premium del creador' }}</p>

                @if($video->isServiceProduct())
                    <span class="vc-service-badge">
                        <i class="fas fa-key" style="font-size:.65rem;"></i>
                        Servicio · {{ $video->duration_days ?? 30 }} días
                    </span>
                @endif

                <div class="vc-footer">
                    @if($video->isFree())
                        <span class="vc-price free">FREE</span>
                    @else
                        <span class="vc-price">${{ number_format($video->price, 2) }}</span>
                    @endif
                    @if($video->telegram_file_id || $video->isServiceProduct())
                        <span class="vc-status-ready"><i class="fas fa-circle" style="font-size:.45rem;"></i> Disponible</span>
                    @else
                        <span class="vc-status-soon"><i class="fas fa-clock" style="font-size:.7rem;"></i> Próximamente</span>
                    @endif
                </div>

                {{-- CTA --}}
                @if($video->isServiceProduct() && ($video->available_service_lines_count ?? 0) < 1)
                    <button class="vc-btn vc-btn-disabled" disabled>
                        <i class="fas fa-ban"></i> Sin stock
                    </button>
                @elseif($video->telegram_file_id || $video->isServiceProduct())
                    @if($video->isFree())
                        <a href="{{ route('video.show', $video) }}" class="vc-btn vc-btn-success">
                            <i class="fas fa-download"></i> Obtener gratis
                        </a>
                    @else
                        @if($creator->is_admin)
                            <a href="{{ route('payment.form', $video) }}" class="vc-btn vc-btn-primary">
                                <i class="fas fa-shopping-cart"></i> Comprar con Stripe
                            </a>
                        @else
                            <button class="vc-btn vc-btn-primary"
                                    data-video-id="{{ $video->id }}"
                                    data-video-title="{{ addslashes($video->title) }}"
                                    data-video-price="{{ $video->price }}"
                                    data-video-type="{{ $video->isServiceProduct() ? 'service' : 'video' }}"
                                    onclick="addToCart(this)">
                                <i class="fas fa-cart-plus"></i> Añadir al carrito
                            </button>
                            <a href="{{ route('creator.checkout.form', ['creator' => $creator->creator_slug, 'video' => $video->id]) }}"
                               class="vc-btn-link">
                                <i class="fas fa-bolt"></i> Comprar ahora
                            </a>
                        @endif
                    @endif
                @else
                    <button class="vc-btn vc-btn-disabled" disabled>
                        <i class="fas fa-hourglass-half"></i> Próximamente
                    </button>
                @endif
            </div>

        </div>
    </div>
@empty
    <div class="col-12 sc-empty">
        <i class="fas fa-video"></i>
        <h5>Sin productos en esta categoría</h5>
        <p>Vuelve pronto para ver nuevo contenido.</p>
    </div>
@endforelse
</div>

@if($videos->hasPages())
    <div style="margin-top:24px;">{{ $videos->links() }}</div>
@endif

</div>{{-- /sc-shell --}}

@if(!$creator->is_admin)
{{-- Offcanvas cart --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" style="width:360px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title"><i class="fas fa-shopping-cart me-2" style="color:var(--sf-accent);"></i>Mi carrito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <div id="offcanvasItems" class="flex-grow-1 overflow-auto px-3 py-2"></div>
        <div class="border-top p-3">
            <div id="offcanvasTotal" class="d-flex justify-content-between fw-bold mb-3" style="color:#e2e8f0;">
                <span>Total</span><span>$0.00</span>
            </div>
            <a href="{{ route('creator.cart.show', $creator->creator_slug) }}"
               class="vc-btn vc-btn-success" style="text-decoration:none;">
                <i class="fas fa-arrow-right"></i> Ir al checkout
            </a>
        </div>
    </div>
</div>

{{-- Floating cart button --}}
<button class="btn btn-primary rounded-circle shadow position-fixed d-flex align-items-center justify-content-center"
        id="floatingCartBtn"
        style="bottom:1.5rem;right:1.5rem;width:3.5rem;height:3.5rem;z-index:1030;display:none!important;"
        data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
    <i class="fas fa-shopping-cart"></i>
    <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.7rem;display:none;">0</span>
</button>
@endif

@endsection

@section('scripts')
<script>
@if(!$creator->is_admin)
const CREATOR_SLUG = '{{ $creator->creator_slug }}';
const CART_KEY     = `cart_${CREATOR_SLUG}`;

function getCart()       { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }
function saveCart(cart)  { localStorage.setItem(CART_KEY, JSON.stringify(cart)); }

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
    btn.innerHTML = '<i class="fas fa-check"></i> Añadido';
    btn.style.background = 'var(--sf-success)';
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-cart-plus"></i> Añadir al carrito';
        btn.style.background = '';
    }, 1500);
    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cartOffcanvas')).show();
}

function removeFromCart(id) {
    saveCart(getCart().filter(i => i.id !== id));
    renderOffcanvas();
    updateBadges();
}

function renderOffcanvas() {
    const cart     = getCart();
    const container = document.getElementById('offcanvasItems');
    const totalEl  = document.getElementById('offcanvasTotal').querySelector('span:last-child');
    if (cart.length === 0) {
        container.innerHTML = '<p style="color:#64748b;text-align:center;padding:32px 0;font-size:.85rem;"><i class="fas fa-shopping-cart" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.25;"></i>Tu carrito está vacío.</p>';
        totalEl.textContent = '$0.00';
        return;
    }
    let html = '';
    cart.forEach((item, idx) => {
        html += `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;${idx < cart.length-1 ? 'border-bottom:1px solid #252d3d;' : ''}">
            <div style="min-width:0;margin-right:10px;">
                <div style="font-weight:600;font-size:.85rem;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(item.title)}</div>
                ${item.type==='service' ? '<span style="font-size:.65rem;background:rgba(79,142,247,.15);color:#4f8ef7;padding:1px 7px;border-radius:20px;">Servicio</span>' : ''}
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span style="color:#4f8ef7;font-weight:700;font-size:.88rem;font-family:\'DM Mono\',monospace;">$${item.price.toFixed(2)}</span>
                <button style="background:none;border:none;color:#ef4444;cursor:pointer;padding:0;line-height:1;" onclick="removeFromCart(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
    });
    container.innerHTML = html;
    totalEl.textContent = `$${cart.reduce((s,i)=>s+i.price,0).toFixed(2)}`;
}

function updateBadges() {
    const count = getCart().length;
    const floatBtn    = document.getElementById('floatingCartBtn');
    const badge       = document.getElementById('cartBadge');
    const headerBadge = document.getElementById('cartBadgeHeader');
    if (count > 0) {
        floatBtn.style.cssText = 'bottom:1.5rem;right:1.5rem;width:3.5rem;height:3.5rem;z-index:1030;display:flex!important;';
        badge.textContent = count; badge.style.display = '';
        if (headerBadge) { headerBadge.textContent = count; headerBadge.style.display = 'flex'; }
    } else {
        floatBtn.style.cssText = 'bottom:1.5rem;right:1.5rem;width:3.5rem;height:3.5rem;z-index:1030;display:none!important;';
        badge.style.display = 'none';
        if (headerBadge) headerBadge.style.display = 'none';
    }
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
@endif

document.addEventListener('DOMContentLoaded', function () {
    @if(!$creator->is_admin)
    renderOffcanvas();
    updateBadges();
    @endif

    // Hover expand thumbnails
    document.querySelectorAll('.video-thumbnail-container').forEach(container => {
        const img           = container.querySelector('.video-thumbnail');
        const allowPreview  = img?.dataset.allowPreview === 'true';
        const blurIntensity = img?.dataset.blurIntensity;
        const origFilter    = img?.style.filter || '';
        const overlay       = container.querySelector('.preview-lock-overlay');

        if (img) img.style.transition = 'filter .25s ease';

        container.addEventListener('mouseenter', () => {
            container.classList.add('expanded');
            if (img) {
                img.style.objectFit = 'contain';
                if (allowPreview) img.style.filter = 'none';
            }
            if (overlay) overlay.style.opacity = '0';
        });
        container.addEventListener('mouseleave', () => {
            container.classList.remove('expanded');
            if (img) {
                img.style.objectFit = 'cover';
                img.style.filter = (allowPreview && blurIntensity) ? `blur(${blurIntensity}px)` : origFilter;
            }
            if (overlay) overlay.style.opacity = '1';
        });
    });
});
</script>
@endsection
