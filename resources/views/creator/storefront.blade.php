@extends('layout')

@section('title', ($creator->creator_store_name ?? $creator->name) . ' - Categorías')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.sf-shell { font-family: 'Outfit', sans-serif; }

/* ── Store header ── */
.sf-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
}
.sf-header-info { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.sf-header-avatar {
    width: 56px; height: 56px;
    border-radius: 50%;
    object-fit: contain;
    background: var(--bs-secondary-bg, #e9ecef);
    border: 2px solid var(--bs-border-color, #dee2e6);
    flex-shrink: 0;
}
.sf-header-avatar-placeholder {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: var(--bs-secondary-bg, #e9ecef);
    border: 2px solid var(--bs-border-color, #dee2e6);
    display: flex; align-items: center; justify-content: center;
    color: var(--bs-secondary-color, #6c757d);
    font-size: 22px; flex-shrink: 0;
}
.sf-header h1 {
    font-size: 1.45rem; font-weight: 700;
    letter-spacing: -.025em; margin: 0;
    display: flex; align-items: center; gap: 8px;
}
.sf-header h1 i { color: #4f8ef7; font-size: 1.1rem; }
.sf-header-bio {
    font-size: .83rem;
    color: var(--bs-secondary-color, #6c757d);
    margin: 3px 0 0;
}

/* ── Cart button ── */
.sf-cart-btn {
    background: #4f8ef7;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 9px 18px;
    font-size: .88rem;
    font-weight: 600;
    display: inline-flex; align-items: center; gap: 7px;
    text-decoration: none;
    position: relative;
    transition: background .2s;
    font-family: 'Outfit', sans-serif;
}
.sf-cart-btn:hover { background: #3b7de8; color: #fff; }
.sf-cart-badge {
    position: absolute; top: -6px; right: -6px;
    background: #ef4444; color: #fff;
    border-radius: 50%; width: 18px; height: 18px;
    font-size: .62rem; font-weight: 700;
    display: none; align-items: center; justify-content: center;
}

/* ── Category cards ── */
.cat-card {
    display: block;
    text-decoration: none;
    color: inherit;
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 14px;
    overflow: hidden;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    height: 100%;
}
.cat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0,0,0,.13);
    border-color: #4f8ef7;
    color: inherit;
    text-decoration: none;
}

/* ── Image container — fixed height, dark bg, contain ── */
.cat-card-img {
    height: 220px;
    background: #0e1117;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
.cat-card-img img {
    max-width: 100%;
    max-height: 100%;
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
}
.cat-card-img-placeholder {
    color: rgba(255,255,255,.2);
    font-size: 2.5rem;
}

.cat-card-body {
    padding: 16px 18px 18px;
    text-align: center;
}
.cat-card-name {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 4px;
    letter-spacing: -.01em;
    color: var(--bs-body-color, #212529);
}
.cat-card-count {
    font-size: .78rem;
    color: #4f8ef7;
    font-weight: 500;
    margin: 0;
}

/* ── Empty state ── */
.sf-empty {
    text-align: center;
    padding: 60px 0;
    color: var(--bs-secondary-color, #6c757d);
}
.sf-empty i { font-size: 2.5rem; opacity: .35; display: block; margin-bottom: 14px; }
</style>
@endsection

@section('content')
<div class="sf-shell">

{{-- Store header --}}
<div class="sf-header">
    <div class="sf-header-info">
        @php
            $avatarSrc = $creator->creator_avatar
                ? (Str::startsWith($creator->creator_avatar, 'http')
                    ? $creator->creator_avatar
                    : asset('storage/' . $creator->creator_avatar))
                : null;
        @endphp
        @if($avatarSrc)
            <img src="{{ $avatarSrc }}" alt="{{ $creator->creator_store_name ?? $creator->name }}" class="sf-header-avatar">
        @else
            <div class="sf-header-avatar-placeholder"><i class="fas fa-store"></i></div>
        @endif
        <div>
            <h1><i class="fas fa-store"></i> {{ $creator->creator_store_name ?? $creator->name }}</h1>
            @if($creator->creator_bio)
                <p class="sf-header-bio">{{ $creator->creator_bio }}</p>
            @endif
        </div>
    </div>

    @if(!$creator->is_admin)
    <a href="{{ route('creator.cart.show', $creator->creator_slug) }}" class="sf-cart-btn" id="cartHeaderBtn">
        <i class="fas fa-shopping-cart"></i> Carrito
        <span id="cartBadgeHeader" class="sf-cart-badge">0</span>
    </a>
    @endif
</div>

@if(!$creator->is_admin)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const count = JSON.parse(localStorage.getItem('cart_{{ $creator->creator_slug }}') || '[]').length;
    const badge = document.getElementById('cartBadgeHeader');
    if (badge && count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    }
});
</script>
@endif

{{-- Categories grid --}}
@if($categories->count() > 0)
    <div class="row g-4">
        @foreach($categories as $category)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('creator.storefront.category', ['creator' => $creator->creator_slug, 'category' => $category->id]) }}"
                   class="cat-card">
                    <div class="cat-card-img">
                        @if($category->hasImage())
                            <img src="{{ $category->getImageUrl() }}" alt="{{ $category->name }}">
                        @else
                            <span class="cat-card-img-placeholder">
                                <i class="fas fa-layer-group"></i>
                            </span>
                        @endif
                    </div>
                    <div class="cat-card-body">
                        <p class="cat-card-name">{{ $category->name }}</p>
                        <p class="cat-card-count">
                            <i class="fas fa-video" style="font-size:.7rem;"></i>
                            {{ $category->videos_count }} {{ Str::plural('video', $category->videos_count) }}
                        </p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="sf-empty">
        <i class="fas fa-layer-group"></i>
        <h5>Sin categorías todavía</h5>
        <p>Este creador aún no tiene categorías creadas.</p>
    </div>
@endif

</div>{{-- /sf-shell --}}
@endsection
