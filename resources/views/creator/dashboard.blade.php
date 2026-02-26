@extends('layout')

@section('title', 'Panel de Creador')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Panel de Creador</h2>
        <small class="text-muted">Gestiona tu tienda, pagos y contenido</small>
    </div>
    <a class="btn btn-outline-primary" href="{{ route('creator.storefront', $creator->creator_slug) }}" target="_blank">Ver tienda publica</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><h6>Videos</h6><div class="display-6">{{ $stats['videos'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h6>Pagos pendientes</h6><div class="display-6 text-warning">{{ $stats['pending'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h6>Pagos aprobados</h6><div class="display-6 text-success">{{ $stats['approved'] }}</div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header">Configuracion de tienda</div>
    <div class="card-body">
        <form method="POST" action="{{ route('creator.profile.update') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre de tienda</label>
                    <input class="form-control" name="creator_store_name" value="{{ old('creator_store_name', $creator->creator_store_name ?? $creator->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug publico</label>
                    <input class="form-control" name="creator_slug" value="{{ old('creator_slug', $creator->creator_slug) }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Bio</label>
                    <textarea class="form-control" name="creator_bio" rows="3">{{ old('creator_bio', $creator->creator_bio) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telegram User ID (para subir videos al bot)</label>
                    <input class="form-control" name="telegram_user_id" value="{{ old('telegram_user_id', $creator->telegram_user_id) }}" placeholder="Ejemplo: 123456789">
                </div>
                <div class="col-md-6">
                    <label class="form-label">PayPal URL</label>
                    <input class="form-control" name="paypal_url" value="{{ old('paypal_url', data_get($creator->creator_payment_methods, 'paypal_url')) }}" placeholder="https://paypal.me/...">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Boton personalizado (HTML opcional)</label>
                    <textarea class="form-control" name="payment_button_html" rows="3" placeholder="Pega aqui el boton HTML de pago">{{ old('payment_button_html', data_get($creator->creator_payment_methods, 'payment_button_html')) }}</textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Otros metodos de pago / instrucciones</label>
                    <textarea class="form-control" name="other_payment_notes" rows="3" placeholder="Binance, transferencia, etc">{{ old('other_payment_notes', data_get($creator->creator_payment_methods, 'other_payment_notes')) }}</textarea>
                </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit">Guardar configuracion</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-video"></i> Videos ({{ $videos->total() }})
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Descripcion</th>
                        <th>Precio</th>
                        <th>Categoria</th>
                        <th>Thumbnail</th>
                        <th>File ID</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($videos as $video)
                        <tr>
                            <td>
                                <strong>{{ $video->title }}</strong><br>
                                <small class="text-muted">Creado: {{ $video->created_at->format('M d, Y H:i') }}</small>
                            </td>
                            <td>
                                <div style="max-width: 220px; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $video->description ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge text-bg-success">${{ number_format($video->price, 2) }}</span>
                            </td>
                            <td>
                                <span class="badge text-bg-info">{{ $video->category->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                @if($video->hasThumbnail())
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $video->getThumbnailUrl() }}" alt="Thumbnail" style="width: 40px; height: 30px; object-fit: cover;" class="rounded me-2">
                                        @if($video->show_blurred_thumbnail)
                                            <span class="badge text-bg-warning">Blurred</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">No thumbnail</span>
                                @endif
                            </td>
                            <td>
                                <code style="font-size: 10px;">{{ $video->telegram_file_id }}</code>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-video-{{ $video->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('creator.videos.delete', $video) }}" onsubmit="return confirm('Eliminar video?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="collapse" id="edit-video-{{ $video->id }}">
                            <td colspan="7">
                                <form method="POST" action="{{ route('creator.videos.update', $video) }}" class="border rounded p-3 bg-body-tertiary">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label mb-1">Titulo</label>
                                            <input name="title" class="form-control form-control-sm" value="{{ $video->title }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Precio</label>
                                            <input name="price" type="number" min="0" step="0.01" class="form-control form-control-sm" value="{{ $video->price }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1">Categoria</label>
                                            <select name="category_id" class="form-select form-select-sm" required>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" @selected($video->category_id == $category->id)>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label mb-1">Blur</label>
                                            <input name="blur_intensity" type="number" min="1" max="20" class="form-control form-control-sm" value="{{ $video->blur_intensity ?? 10 }}">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label mb-1">Descripcion</label>
                                            <textarea name="description" class="form-control form-control-sm" rows="2">{{ $video->description }}</textarea>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label mb-1">Thumbnail URL externa</label>
                                            <input name="thumbnail_url" class="form-control form-control-sm" value="{{ $video->thumbnail_url ?: (filter_var($video->thumbnail_path, FILTER_VALIDATE_URL) ? $video->thumbnail_path : '') }}" placeholder="https://...">
                                        </div>
                                        <div class="col-md-12 d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="show_blurred" value="1" id="blurred-{{ $video->id }}" @checked($video->show_blurred_thumbnail)>
                                                <label class="form-check-label" for="blurred-{{ $video->id }}">Mostrar blurred</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_preview" value="1" id="preview-{{ $video->id }}" @checked($video->allow_preview)>
                                                <label class="form-check-label" for="preview-{{ $video->id }}">Permitir preview</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-sm btn-primary" type="submit">Guardar cambios</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aun no tienes videos. Envialos al bot con tu Telegram User ID.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $videos->links() }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Compras recientes</span>
        <a href="{{ route('creator.purchases') }}">Ver todas</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Fecha</th><th>Video</th><th>Usuario TG</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                @forelse($recentPurchases as $purchase)
                    <tr>
                        <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $purchase->video->title ?? 'N/A' }}</td>
                        <td>{{ '@' . $purchase->telegram_username }}</td>
                        <td>
                            @if($purchase->verification_status === 'verified')
                                <span class="badge text-bg-success">Aprobado</span>
                            @elseif($purchase->verification_status === 'invalid')
                                <span class="badge text-bg-danger">Rechazado</span>
                            @else
                                <span class="badge text-bg-warning">Pendiente</span>
                            @endif
                        </td>
                        <td>
                            @if($purchase->verification_status === 'pending')
                                <form method="POST" action="{{ route('creator.purchases.approve', $purchase) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success" type="submit">Aprobar</button>
                                </form>
                                <form method="POST" action="{{ route('creator.purchases.reject', $purchase) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="delivery_notes" value="Pago rechazado por el creador">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Declinar</button>
                                </form>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No hay compras todavia.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
