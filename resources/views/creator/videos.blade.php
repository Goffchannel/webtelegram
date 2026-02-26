@extends('layout')

@section('title', 'Mis videos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Mis videos</h2>
    <a href="{{ route('creator.dashboard') }}" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr><th>ID</th><th>Titulo</th><th>Precio USD</th><th>Categoria</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            @forelse($videos as $video)
                <tr>
                    <td>{{ $video->id }}</td>
                    <td>{{ $video->title }}</td>
                    <td>${{ number_format($video->price, 2) }}</td>
                    <td>{{ $video->category->name ?? 'N/A' }}</td>
                    <td>
                        <form method="POST" action="{{ route('creator.videos.update', $video) }}" class="d-inline-flex gap-2">
                            @csrf
                            @method('PUT')
                            <input class="form-control form-control-sm" style="max-width:220px" name="title" value="{{ $video->title }}" required>
                            <input class="form-control form-control-sm" style="max-width:120px" name="price" type="number" min="0" step="0.01" value="{{ $video->price }}" required>
                            <input type="hidden" name="description" value="{{ $video->description }}">
                            <input type="hidden" name="category_id" value="{{ $video->category_id ?? $defaultCategoryId }}">
                            <button class="btn btn-sm btn-primary" @disabled(empty($video->category_id) && empty($defaultCategoryId))>Guardar</button>
                        </form>
                        <form method="POST" action="{{ route('creator.videos.delete', $video) }}" class="d-inline" onsubmit="return confirm('Eliminar video?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Aun no tienes videos. Envia videos al bot desde tu Telegram configurado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div>{{ $videos->links() }}</div>
@endsection
