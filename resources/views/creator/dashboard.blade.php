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

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Compras recientes</span>
        <a href="{{ route('creator.purchases') }}">Ver todas</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Fecha</th><th>Video</th><th>Usuario TG</th><th>Estado</th></tr></thead>
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
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No hay compras todavia.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
