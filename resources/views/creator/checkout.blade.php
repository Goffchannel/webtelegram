@extends('layout')

@section('title', 'Comprar: ' . $video->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">Compra directa al creador</div>
            <div class="card-body">
                <h4>{{ $video->title }}</h4>
                <p class="text-muted">Creador: {{ $creator->creator_store_name ?? $creator->name }}</p>
                <p>{{ $video->description }}</p>
                <h5 class="mb-4">Precio: ${{ number_format($video->price, 2) }}</h5>
                @if($video->isServiceProduct())
                    <div class="alert alert-info">
                        <strong>Servicio de acceso:</strong> {{ $video->duration_days ?? 30 }} dias<br>
                        <strong>Stock disponible:</strong> {{ $video->availableServiceLines()->count() }}
                    </div>
                @endif

                @if(!empty($methods['paypal_url']))
                    <div class="alert alert-info">
                        <strong>PayPal:</strong>
                        <a href="{{ $methods['paypal_url'] }}" target="_blank">{{ $methods['paypal_url'] }}</a>
                    </div>
                @endif

                @if(!empty($methods['payment_button_html']))
                    <div class="alert alert-light border">
                        <strong>Boton personalizado del creador:</strong>
                        <div class="mt-2">{!! $methods['payment_button_html'] !!}</div>
                    </div>
                @endif

                @if(!empty($methods['other_payment_notes']))
                    <div class="alert alert-secondary" style="white-space: pre-wrap;">{{ $methods['other_payment_notes'] }}</div>
                @endif

                <form method="POST" action="{{ route('creator.checkout.submit', ['creator' => $creator->creator_slug, 'video' => $video->id]) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tu usuario de Telegram</label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input class="form-control" name="telegram_username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metodo usado</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="paypal">PayPal</option>
                                <option value="boton_personalizado">Boton personalizado</option>
                                <option value="otro">Otro metodo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referencia de pago (opcional)</label>
                            <input class="form-control" name="payment_reference" placeholder="ID de operacion">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL de comprobante (opcional)</label>
                            <input class="form-control" name="proof_url" placeholder="https://...">
                        </div>
                    </div>
                    <button class="btn btn-success mt-3" type="submit">Ya he pagado - enviar para revision</button>
                </form>

                <hr>
                <small class="text-muted">El pago y reembolso lo gestiona directamente el creador. Tu acceso al video se activa cuando el creador apruebe el pago.</small>
            </div>
        </div>
    </div>
</div>
@endsection
