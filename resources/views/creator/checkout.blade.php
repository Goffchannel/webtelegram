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

                {{-- Price display (updates live when discount applied) --}}
                <div class="mb-4">
                    <h5 id="priceDisplay">Precio: ${{ number_format($video->price, 2) }}</h5>
                    <div id="discountSummary" class="d-none alert alert-success py-2 px-3 small"></div>
                </div>

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

                {{-- Discount code --}}
                <div class="card bg-light border-0 mb-4">
                    <div class="card-body py-3">
                        <label class="form-label fw-semibold small mb-2">
                            <i class="fas fa-tag me-1 text-success"></i>¿Tienes un código de descuento?
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="discountCodeInput" class="form-control font-monospace text-uppercase"
                                   placeholder="CÓDIGO" maxlength="50"
                                   oninput="this.value=this.value.toUpperCase(); resetDiscount()">
                            <button class="btn btn-outline-success" type="button" onclick="applyDiscount()">
                                Aplicar
                            </button>
                        </div>
                        <div id="discountMsg" class="form-text mt-1"></div>
                    </div>
                </div>

                <form method="POST" action="{{ route('creator.checkout.submit', ['creator' => $creator->creator_slug, 'video' => $video->id]) }}">
                    @csrf
                    <input type="hidden" name="discount_code" id="discountCodeHidden">
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

@section('scripts')
<script>
const basePrice = {{ (float) $video->price }};

function applyDiscount() {
    const code = document.getElementById('discountCodeInput').value.trim();
    const msg  = document.getElementById('discountMsg');

    if (!code) {
        msg.className = 'form-text text-warning mt-1';
        msg.textContent = 'Introduce un código.';
        return;
    }

    fetch('{{ route('discount-codes.validate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ code, amount: basePrice }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            document.getElementById('discountCodeHidden').value = code;
            document.getElementById('priceDisplay').innerHTML =
                `Precio: <s class="text-muted">$${basePrice.toFixed(2)}</s> <strong class="text-success">$${data.final_amount.toFixed(2)}</strong>`;
            const summary = document.getElementById('discountSummary');
            summary.className = 'alert alert-success py-2 px-3 small';
            summary.innerHTML = `<i class="fas fa-check-circle me-1"></i><strong>${code}</strong> — ${data.description || 'Descuento aplicado'}: <strong>${data.formatted}</strong>`;
            msg.className = 'form-text text-success mt-1';
            msg.textContent = '✓ Código aplicado correctamente.';
        } else {
            resetDiscount();
            msg.className = 'form-text text-danger mt-1';
            msg.textContent = '✗ ' + (data.message || 'Código inválido.');
        }
    })
    .catch(() => {
        msg.className = 'form-text text-danger mt-1';
        msg.textContent = 'Error al verificar el código.';
    });
}

function resetDiscount() {
    document.getElementById('discountCodeHidden').value = '';
    document.getElementById('priceDisplay').innerHTML = `Precio: $${basePrice.toFixed(2)}`;
    document.getElementById('discountSummary').className = 'd-none alert alert-success py-2 px-3 small';
    document.getElementById('discountMsg').textContent = '';
}

// Allow pressing Enter in the code input
document.getElementById('discountCodeInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); applyDiscount(); }
});
</script>
@endsection
