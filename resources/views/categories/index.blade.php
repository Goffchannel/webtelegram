@extends('layout')

@section('title', 'Explorar Creadores')

@section('content')
    <div class="text-center mb-5">
        <h1><i class="fas fa-store text-primary"></i> Explorar Creadores</h1>
        <p class="lead text-muted">Selecciona un creador para ver su tienda</p>
    </div>

    @if ($creators->count() > 0)
        <div class="row">
            @foreach ($creators as $creator)
                <div class="col-md-6 col-lg-4 mb-4">
                    <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="text-decoration-none text-dark">
                        <div class="card h-100 shadow-sm category-card">
                            @php
                                $avatarUrl = null;
                                if ($creator->creator_avatar) {
                                    $avatarUrl = Str::startsWith($creator->creator_avatar, 'http')
                                        ? $creator->creator_avatar
                                        : asset('storage/' . $creator->creator_avatar);
                                }
                            @endphp
                            @if ($avatarUrl)
                                <div style="height: 220px; overflow: hidden;">
                                    <img src="{{ $avatarUrl }}" class="card-img-top" alt="{{ $creator->creator_store_name ?? $creator->name }}"
                                        style="height: 220px; width: 100%; object-fit: cover; object-position: top;">
                                </div>
                            @else
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                    style="height: 220px;">
                                    <i class="fas fa-user fa-4x text-muted"></i>
                                </div>
                            @endif

                            <div class="card-body d-flex flex-column text-center">
                                <h5 class="card-title">{{ $creator->creator_store_name ?? $creator->name }}</h5>
                                <p class="card-text text-muted flex-grow-1">
                                    {{ $creator->videos_count }} {{ Str::plural('video', $creator->videos_count) }}
                                </p>
                                @if($creator->creator_bio)
                                    <small class="text-muted">{{ Str::limit($creator->creator_bio, 85) }}</small>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-store fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No hay creadores disponibles</h4>
            <p class="text-muted">Vuelve pronto.</p>
        </div>
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
