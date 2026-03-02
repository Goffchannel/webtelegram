@extends('layout')

@section('title', 'Explorar Creadores')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.explore-hero {
    text-align: center;
    padding: 40px 0 32px;
}
.explore-hero h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: -.03em;
    margin-bottom: 6px;
}
.explore-hero p {
    color: var(--bs-secondary-color, #6c757d);
    font-size: .95rem;
}

/* ── Creator card ── */
.creator-card {
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 16px;
    overflow: hidden;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    text-decoration: none;
    color: inherit;
    display: block;
}
.creator-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 32px rgba(0,0,0,.12);
    border-color: #4f8ef7;
    color: inherit;
    text-decoration: none;
}

/* ── Avatar area ── */
.creator-card-banner {
    height: 90px;
    background: linear-gradient(135deg, #1a2744 0%, #0f1c3a 100%);
    position: relative;
    flex-shrink: 0;
}
.creator-card-avatar-wrap {
    position: absolute;
    bottom: -36px;
    left: 50%;
    transform: translateX(-50%);
    width: 76px;
    height: 76px;
}
.creator-card-avatar {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    object-fit: contain;
    background: var(--bs-body-bg, #fff);
    border: 3px solid var(--bs-body-bg, #fff);
    box-shadow: 0 2px 12px rgba(0,0,0,.18);
}
.creator-card-avatar-placeholder {
    width: 76px;
    height: 76px;
    border-radius: 50%;
    background: var(--bs-secondary-bg, #e9ecef);
    border: 3px solid var(--bs-body-bg, #fff);
    box-shadow: 0 2px 12px rgba(0,0,0,.12);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-secondary-color, #6c757d);
    font-size: 28px;
}

/* ── Card body ── */
.creator-card-body {
    padding: 48px 20px 22px;
    text-align: center;
    font-family: 'Outfit', sans-serif;
}
.creator-card-name {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -.02em;
    margin: 0 0 4px;
    color: var(--bs-body-color, #212529);
}
.creator-card-count {
    font-size: .78rem;
    color: #4f8ef7;
    font-weight: 600;
    margin-bottom: 8px;
    letter-spacing: .02em;
}
.creator-card-bio {
    font-size: .8rem;
    color: var(--bs-secondary-color, #6c757d);
    line-height: 1.45;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ── Empty state ── */
.explore-empty {
    text-align: center;
    padding: 80px 0;
    color: var(--bs-secondary-color, #6c757d);
}
.explore-empty i {
    font-size: 3rem;
    margin-bottom: 16px;
    display: block;
    opacity: .4;
}
</style>
@endsection

@section('content')

<div class="explore-hero">
    <h1><i class="fas fa-store" style="color:#4f8ef7;"></i> Explorar Creadores</h1>
    <p>Selecciona un creador para ver su tienda</p>
</div>

@if ($creators->count() > 0)
    <div class="row g-4">
        @foreach ($creators as $creator)
            @php
                $avatarUrl = null;
                if ($creator->creator_avatar) {
                    $avatarUrl = Str::startsWith($creator->creator_avatar, 'http')
                        ? $creator->creator_avatar
                        : asset('storage/' . $creator->creator_avatar);
                }
            @endphp
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="creator-card">
                    <div class="creator-card-banner">
                        <div class="creator-card-avatar-wrap">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}"
                                     alt="{{ $creator->creator_store_name ?? $creator->name }}"
                                     class="creator-card-avatar">
                            @else
                                <div class="creator-card-avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="creator-card-body">
                        <p class="creator-card-name">{{ $creator->creator_store_name ?? $creator->name }}</p>
                        <p class="creator-card-count">
                            <i class="fas fa-video" style="font-size:.7rem;"></i>
                            {{ $creator->videos_count }} {{ Str::plural('video', $creator->videos_count) }}
                        </p>
                        @if($creator->creator_bio)
                            <p class="creator-card-bio">{{ $creator->creator_bio }}</p>
                        @endif
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="explore-empty">
        <i class="fas fa-store"></i>
        <h4>No hay creadores disponibles</h4>
        <p>Vuelve pronto.</p>
    </div>
@endif

@endsection
