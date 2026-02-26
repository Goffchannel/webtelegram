@extends('layout')

@section('title', ($creator->creator_store_name ?? $creator->name))

@section('content')
<div class="mb-4">
    <h1 class="mb-1">{{ $creator->creator_store_name ?? $creator->name }}</h1>
    <p class="text-muted mb-0">{{ $creator->creator_bio }}</p>
</div>

<div class="row g-4">
@forelse($videos as $video)
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            @if($video->hasThumbnail())
                <img src="{{ $video->getThumbnailUrl() }}" class="card-img-top" style="height:220px;object-fit:cover" alt="thumbnail">
            @endif
            <div class="card-body d-flex flex-column">
                <h5>{{ $video->title }}</h5>
                <p class="text-muted small">{{ \Illuminate\Support\Str::limit($video->description, 100) }}</p>
                <div class="mt-auto d-flex justify-content-between align-items-center">
                    <strong>${{ number_format($video->price, 2) }}</strong>
                    <a class="btn btn-sm btn-primary" href="{{ route('video.show', $video) }}">Ver</a>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="alert alert-secondary">Este creador todavia no tiene videos publicados.</div></div>
@endforelse
</div>

<div class="mt-4">{{ $videos->links() }}</div>
@endsection
