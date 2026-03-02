@extends('layout')

@section('title', '¡Pedido recibido! — ' . ($creator->creator_store_name ?? $creator->name))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 text-center">
        <div class="mb-4">
            <i class="fas fa-check-circle fa-4x text-success mb-3 d-block"></i>
            <h2 class="fw-bold">¡Pedido recibido!</h2>
            <p class="text-muted lead">Tu solicitud ha sido enviada correctamente.<br>El creador verificará tu pago y activará cada producto.</p>
        </div>

        <div class="card shadow-sm text-start mb-4">
            <div class="card-header fw-semibold">
                <i class="fas fa-receipt me-1"></i>Resumen del pedido
            </div>
            <div class="list-group list-group-flush">
                @foreach($purchases as $p)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $p->video->title }}</div>
                            <small class="text-muted">
                                @if($p->discount_amount > 0)
                                    <s>${{ number_format($p->video->price, 2) }}</s>
                                    <span class="text-success ms-1 fw-semibold">${{ number_format($p->amount, 2) }}</span>
                                    <span class="badge text-bg-success ms-1">Descuento aplicado</span>
                                @else
                                    ${{ number_format($p->amount, 2) }}
                                @endif
                            </small>
                        </div>
                        <a href="{{ route('purchase.view', $p->purchase_uuid) }}" class="btn btn-outline-secondary btn-sm ms-3">
                            <i class="fas fa-eye me-1"></i>Ver estado
                        </a>
                    </div>
                    <div class="mt-1">
                        <span class="badge text-bg-warning">Pendiente de verificación</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <a href="{{ route('creator.storefront', $creator->creator_slug) }}" class="btn btn-primary">
            <i class="fas fa-store me-1"></i>Volver a la tienda
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Clear the cart for this creator after successful purchase
localStorage.removeItem('cart_{{ $creator->creator_slug }}');
</script>
@endsection
