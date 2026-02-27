@extends('layout')

@section('title', 'Admin - Servicio y Stock')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Gestion de servicio y stock</h3>
        <a href="{{ route('admin.videos.manage') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    <div class="card mb-4">
        <div class="card-header">Producto</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.videos.update', $video) }}">
                @csrf
                @method('PUT')
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Titulo</label>
                        <input class="form-control" name="title" value="{{ $video->title }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio USD</label>
                        <input class="form-control" type="number" step="0.01" min="0" name="price" value="{{ $video->price }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="product_type">
                            <option value="video" @selected(!$video->isServiceProduct())>Video</option>
                            <option value="service_access" @selected($video->isServiceProduct())>Servicio (lista/membresia)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Duracion (dias)</label>
                        <input class="form-control" type="number" min="1" max="365" name="duration_days" value="{{ $video->duration_days ?? 30 }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="category_id" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((int)$video->category_id === (int)$category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Descripcion</label>
                        <input class="form-control" name="description" value="{{ $video->description }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripcion larga</label>
                        <textarea class="form-control" rows="2" name="long_description">{{ $video->long_description }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mensaje exclusivo post-compra</label>
                        <textarea class="form-control" rows="2" name="fan_message">{{ $video->fan_message }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Instrucciones de acceso</label>
                        <textarea class="form-control" rows="2" name="access_instructions">{{ $video->access_instructions }}</textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Guardar producto</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Cargar lineas (formato: nombre|url_m3u|usuario|contrasena|notas)</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.videos.service-lines.store', $video) }}">
                @csrf
                <textarea class="form-control" rows="5" name="bulk_lines" placeholder="Linea 1|https://...m3u|user1|pass1|nota"></textarea>
                <button class="btn btn-success mt-2">Cargar lineas</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <span>Lineas del producto</span>
            <span class="badge text-bg-info">Total: {{ $lines->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Nombre</th><th>Usuario</th><th>Estado</th><th>Asignada a compra</th><th></th></tr></thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td>{{ $line->line_name }}</td>
                            <td>{{ $line->line_username ?: '-' }}</td>
                            <td>@if($line->is_assigned)<span class="badge text-bg-secondary">Asignada</span>@else<span class="badge text-bg-success">Libre</span>@endif</td>
                            <td>{{ $line->assigned_purchase_id ?: '-' }}</td>
                            <td>
                                @if(!$line->is_assigned)
                                    <form method="POST" action="{{ route('admin.videos.service-lines.delete', ['video' => $video, 'line' => $line]) }}" onsubmit="return confirm('Eliminar linea?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Sin lineas cargadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $lines->links() }}</div>
    </div>
</div>
@endsection
