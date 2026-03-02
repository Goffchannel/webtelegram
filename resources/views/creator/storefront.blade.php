@extends('layout')

@section('title', ($creator->creator_store_name ?? $creator->name) . ' - Categorias')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1 class="mb-1"><i class="fas fa-store text-primary"></i> {{ $creator->creator_store_name ?? $creator->name }}</h1>
        <p class="text-muted mb-0">{{ $creator->creator_bio ?: 'Contenido premium del creador' }}</p>
    </div>
    @if(!$creator->is_admin)
    <a href="{{ route('creator.cart.show', $creator->creator_slug) }}"
       class="btn btn-primary position-relative" id="cartHeaderBtn">
        <i class="fas fa-shopping-cart"></i> Carrito
        <span id="cartBadgeHeader" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">0</span>
    </a>
    @endif
</div>

@if(!$creator->is_admin)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const count = JSON.parse(localStorage.getItem('cart_{{ $creator->creator_slug }}') || '[]').length;
    const badge = document.getElementById('cartBadgeHeader');
    if (badge && count > 0) { badge.textContent = count; badge.style.display = ''; }
});
</script>
@endif

@if($categories->count() > 0)
    <div class="row">
        @foreach($categories as $category)
            <div class="col-md-6 col-lg-4 mb-4">
                <a href="{{ route('creator.storefront.category', ['creator' => $creator->creator_slug, 'category' => $category->id]) }}" class="text-decoration-none text-dark">
                    <div class="card h-100 shadow-sm category-card">
                        @if ($category->hasImage())
                            <div class="position-relative" style="height: 300px;">
                                <img src="{{ $category->getImageUrl() }}" class="card-img-top"
                                    alt="{{ $category->name }} thumbnail"
                                    style="height: 300px; object-fit: cover; object-position: top;">
                            </div>
                        @else
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                <i class="fas fa-layer-group fa-3x text-muted"></i>
                            </div>
                        @endif
                        <div class="card-body d-flex flex-column text-center">
                            <h5 class="card-title">{{ $category->name }}</h5>
                            <p class="card-text text-muted flex-grow-1">
                                {{ $category->videos_count }} {{ Str::plural('video', $category->videos_count) }}
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="alert alert-secondary">Este creador todavia no tiene categorias creadas.</div>
@endif
@endsection

@section('styles')
<style>
    .category-card {
        transition: transform .2s ease-in-out, box-shadow .2s ease-in-out;
    }
    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
</style>
@endsection
