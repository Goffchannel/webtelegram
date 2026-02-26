@extends('layout')

@section('title', 'Browse Categories')

@section('content')
    <div class="text-center mb-5">
        <h1><i class="fas fa-layer-group text-primary"></i> Browse Categories</h1>
        <p class="lead text-muted">Select a category to view videos</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
            @guest
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Iniciar sesion</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Crear cuenta</a>
                <a href="{{ route('creator.subscription.show') }}" class="btn btn-warning">Quiero ser creador</a>
            @else
                @if (Auth::user()->is_creator && Auth::user()->subscribed('creator'))
                    <a href="{{ route('creator.dashboard') }}" class="btn btn-warning">Ir a mi panel de creador</a>
                @else
                    <a href="{{ route('creator.subscription.show') }}" class="btn btn-warning">Activar modo creador</a>
                @endif
            @endguest
        </div>
    </div>

    @if ($categories->count() > 0)
        <div class="row">
            @foreach ($categories as $category)
                <div class="col-md-6 col-lg-4 mb-4">
                    <a href="{{ route('categories.show', $category) }}" class="text-decoration-none text-dark">
                        <div class="card h-100 shadow-sm category-card">
                            @if ($category->hasImage())
                                <div class="position-relative" style="height: 300px;">
                                    <img src="{{ $category->getImageUrl() }}" class="card-img-top" alt="{{ $category->name }} thumbnail "
                                        style="height: 300px; object-fit: cover; object-position: top; ">
                                </div>
                            @else
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                    style="height: 200px;">
                                    <i class="fas fa-photo-video fa-3x text-muted"></i>
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
        <div class="text-center py-5">
            <i class="fas fa-layer-group fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No categories available yet</h4>
            <p class="text-muted">Check back soon!</p>
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
