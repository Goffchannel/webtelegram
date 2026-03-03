@extends('admin.layout')

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

    @php $sharedLine = $lines->first(fn($l) => $l->is_shared); @endphp

    {{-- IPTV Plooplayer: shared access --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="fas fa-tv text-info"></i>
            <strong>Acceso IPTV Plooplayer (compartido)</strong>
        </div>
        <div class="card-body">
            @if($sharedLine)
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Acceso compartido activo.</strong>
                    Cada comprador recibe su propia URL:
                    <code>{{ url('/iptv/') }}/<em>token-unico</em></code>
                    que apunta a tus canales en <code>{{ url('/iptv/channels') }}</code>.
                </div>
                <form method="POST" action="{{ route('admin.videos.service-lines.delete', ['video' => $video, 'line' => $sharedLine]) }}" onsubmit="return confirm('Desactivar acceso IPTV compartido?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times me-1"></i>Desactivar</button>
                </form>
            @else
                <p class="text-muted mb-3">
                    Activa el acceso compartido para que cada comprador reciba su URL personal de Plooplayer
                    (<code>/iptv/token</code>) apuntando a tus canales.
                    Asegúrate de tener los canales cargados en
                    <a href="{{ route('admin.iptv.index') }}">Gestión IPTV</a>.
                </p>
                <form method="POST" action="{{ route('admin.videos.service-lines.store', $video) }}">
                    @csrf
                    <input type="hidden" name="bulk_lines" value="iptv-plooplayer|shared|shared|shared">
                    <input type="hidden" name="is_shared" value="1">
                    <button class="btn btn-success"><i class="fas fa-tv me-2"></i>Activar acceso IPTV compartido</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Individual lines (reselling) --}}
    <div class="card mb-4">
        <div class="card-header">
            Lineas individuales (reventa de cuentas) — formato: <code>nombre|url_m3u|usuario|contraseña|notas</code>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.videos.service-lines.store', $video) }}">
                @csrf
                <textarea class="form-control" rows="4" name="bulk_lines" placeholder="Cliente1|http://proveedor.com:8080/get.php?username=u1&password=p1&type=m3u|u1|p1|VIP"></textarea>
                <button class="btn btn-outline-success mt-2">Cargar lineas</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <span>Lineas cargadas</span>
            <span class="badge text-bg-info">Total: {{ $lines->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Nombre</th><th>Tipo</th><th>Usuario</th><th>Estado</th><th>Compra</th><th></th></tr></thead>
                <tbody>
                    @forelse($lines as $line)
                        <tr>
                            <td>{{ $line->line_name }}</td>
                            <td>@if($line->is_shared)<span class="badge text-bg-info">Compartida</span>@else<span class="badge text-bg-secondary">Individual</span>@endif</td>
                            <td>{{ $line->line_username ?: '-' }}</td>
                            <td>@if($line->is_assigned)<span class="badge text-bg-warning">Asignada</span>@else<span class="badge text-bg-success">Libre</span>@endif</td>
                            <td>{{ $line->assigned_purchase_id ?: '-' }}</td>
                            <td>
                                @if(!$line->is_assigned || $line->is_shared)
                                    <form method="POST" action="{{ route('admin.videos.service-lines.delete', ['video' => $video, 'line' => $line]) }}" onsubmit="return confirm('Eliminar linea?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Sin lineas cargadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $lines->links() }}</div>
    </div>
</div>
@endsection
