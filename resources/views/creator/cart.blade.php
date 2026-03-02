@extends('layout')

@section('title', 'Carrito — ' . ($creator->creator_store_name ?? $creator->name))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Empty cart state (hidden by default, shown by JS) --}}
        <div id="emptyCart" style="display:none;" class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3 d-block opacity-50"></i>
            <h5 class="text-muted">Tu carrito está vacío</h5>
            <a href="{{ route('creator.storefront', $creator->creator_slug) }}" class="btn btn-primary mt-3">
                <i class="fas fa-store me-1"></i>Volver a la tienda
            </a>
        </div>

        {{-- Cart content (hidden initially, shown by JS) --}}
        <div id="cartContent" style="display:none;">

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0"><i class="fas fa-shopping-cart me-2 text-primary"></i>Tu carrito</h3>
                <a href="{{ route('creator.storefront', $creator->creator_slug) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Seguir comprando
                </a>
            </div>

            {{-- Items --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body p-0">
                    <div id="cartItemsList" class="px-3 py-2"></div>
                </div>
            </div>

            {{-- Discount code --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-tag me-1 text-success"></i>Código de descuento</h6>
                    <div class="input-group">
                        <input type="text" id="discountInput" class="form-control font-monospace text-uppercase"
                               placeholder="CÓDIGO" maxlength="50"
                               oninput="this.value=this.value.toUpperCase()">
                        <button class="btn btn-outline-success" type="button" onclick="applyDiscount()">
                            <i class="fas fa-check me-1"></i>Aplicar
                        </button>
                    </div>
                    <div id="discountMsg" class="form-text mt-1"></div>
                </div>
            </div>

            {{-- Order total --}}
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div id="orderSummary"></div>
                </div>
            </div>

            {{-- Checkout form --}}
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="fas fa-credit-card me-1"></i>Datos de pago
                </div>
                <div class="card-body">
                    @if(!empty($methods['paypal_url']))
                        <div class="alert alert-info small">
                            <strong>PayPal:</strong>
                            <a href="{{ $methods['paypal_url'] }}" target="_blank">{{ $methods['paypal_url'] }}</a>
                        </div>
                    @endif
                    @if(!empty($methods['payment_button_html']))
                        <div class="alert alert-light border small">
                            <strong>Botón de pago del creador:</strong>
                            <div class="mt-2">{!! $methods['payment_button_html'] !!}</div>
                        </div>
                    @endif
                    @if(!empty($methods['other_payment_notes']))
                        <div class="alert alert-secondary small" style="white-space:pre-wrap;">{{ $methods['other_payment_notes'] }}</div>
                    @endif

                    <form method="POST" action="{{ route('creator.cart.checkout', $creator->creator_slug) }}" id="cartForm">
                        @csrf
                        <div id="videoIdInputs">{{-- JS populated --}}</div>
                        <input type="hidden" name="discount_code" id="discountCodeHidden">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tu usuario de Telegram</label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input class="form-control" name="telegram_username" required placeholder="usuario">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de pago utilizado</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="paypal">PayPal</option>
                                    <option value="boton_personalizado">Botón personalizado</option>
                                    <option value="otro">Otro método</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referencia de pago <small class="text-muted">(opcional)</small></label>
                                <input class="form-control" name="payment_reference" placeholder="ID de operación">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">URL de comprobante <small class="text-muted">(opcional)</small></label>
                                <input class="form-control" name="proof_url" placeholder="https://...">
                            </div>
                        </div>

                        <button class="btn btn-success w-100 mt-4" type="submit">
                            <i class="fas fa-paper-plane me-1"></i>Ya he pagado — enviar para revisión
                        </button>
                    </form>

                    <small class="text-muted d-block mt-3">
                        El creador verificará tu pago y activará cada producto individualmente.
                    </small>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
const CART_KEY = 'cart_{{ $creator->creator_slug }}';
let discountData = null;

function getCart() {
    return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
}

function renderPage() {
    const cart = getCart();

    if (cart.length === 0) {
        document.getElementById('emptyCart').style.display = '';
        document.getElementById('cartContent').style.display = 'none';
        return;
    }

    document.getElementById('emptyCart').style.display = 'none';
    document.getElementById('cartContent').style.display = '';

    // Items list
    let html = '';
    cart.forEach((item, idx) => {
        html += `
        <div class="d-flex justify-content-between align-items-center py-3 ${idx < cart.length - 1 ? 'border-bottom' : ''}">
            <div>
                <div class="fw-semibold">${escHtml(item.title)}</div>
                ${item.type === 'service' ? '<span class="badge text-bg-info small">Servicio</span>' : ''}
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-primary fw-bold fs-5">$${item.price.toFixed(2)}</span>
                <button class="btn btn-link text-danger p-0" onclick="removeItem(${item.id})" title="Quitar">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>`;
    });
    document.getElementById('cartItemsList').innerHTML = html;

    // Hidden video_ids inputs
    const container = document.getElementById('videoIdInputs');
    container.innerHTML = '';
    cart.forEach(item => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'video_ids[]';
        inp.value = item.id;
        container.appendChild(inp);
    });

    // Order summary
    const rawTotal  = cart.reduce((s, i) => s + i.price, 0);
    const count     = cart.length;
    let summaryHtml = '';

    if (discountData) {
        const finalTotal = Math.max(0, rawTotal - discountData.discount);
        document.getElementById('discountCodeHidden').value = discountData.code;
        summaryHtml = `
            <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Subtotal (${count} producto${count > 1 ? 's' : ''})</span>
                <span>$${rawTotal.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between text-success small mb-2">
                <span><i class="fas fa-tag me-1"></i>${escHtml(discountData.code)}${discountData.description ? ' — ' + escHtml(discountData.description) : ''}</span>
                <span>-${discountData.formatted}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                <span>Total</span>
                <span class="text-success">$${finalTotal.toFixed(2)}</span>
            </div>`;
    } else {
        document.getElementById('discountCodeHidden').value = '';
        summaryHtml = `
            <div class="d-flex justify-content-between fw-bold fs-5">
                <span>Total (${count} producto${count > 1 ? 's' : ''})</span>
                <span>$${rawTotal.toFixed(2)}</span>
            </div>`;
    }

    document.getElementById('orderSummary').innerHTML = summaryHtml;
}

function removeItem(id) {
    let cart = getCart().filter(i => i.id !== id);
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    discountData = null;
    document.getElementById('discountInput').value = '';
    document.getElementById('discountMsg').textContent = '';
    renderPage();
}

function applyDiscount() {
    const code  = document.getElementById('discountInput').value.trim().toUpperCase();
    const msg   = document.getElementById('discountMsg');
    const total = getCart().reduce((s, i) => s + i.price, 0);

    if (!code) {
        msg.className   = 'form-text text-warning mt-1';
        msg.textContent = 'Introduce un código de descuento.';
        return;
    }

    fetch('{{ route('discount-codes.validate') }}', {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ code, amount: total }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            discountData        = { code, discount: data.discount, formatted: data.formatted, description: data.description };
            msg.className       = 'form-text text-success mt-1';
            msg.textContent     = '✓ Código aplicado correctamente.';
        } else {
            discountData    = null;
            msg.className   = 'form-text text-danger mt-1';
            msg.textContent = '✗ ' + (data.message || 'Código inválido.');
        }
        renderPage();
    })
    .catch(() => {
        msg.className   = 'form-text text-danger mt-1';
        msg.textContent = 'Error al verificar el código.';
    });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('discountInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); applyDiscount(); }
});

document.addEventListener('DOMContentLoaded', renderPage);
</script>
@endsection
