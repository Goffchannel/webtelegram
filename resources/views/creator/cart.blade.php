@extends('layout')

@section('title', 'Carrito — ' . ($creator->creator_store_name ?? $creator->name))

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
    --ch-bg: #0e1117;
    --ch-surface: #161b25;
    --ch-border: #252d3d;
    --ch-accent: #4f8ef7;
    --ch-success: #22c55e;
    --ch-warning: #f59e0b;
    --ch-danger: #ef4444;
    --ch-text: #e2e8f0;
    --ch-muted: #64748b;
    --ch-font: 'Outfit', sans-serif;
}
.ch-shell { font-family: var(--ch-font); }
.ch-shell *:not(i):not([class*="fa"]):not([class*="fab"]) { font-family: var(--ch-font); }

/* ── Header ── */
.ch-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
    margin-bottom: 24px;
}
.ch-header h1 {
    font-size: 1.5rem; font-weight: 700; margin: 0;
    letter-spacing: -.03em;
    display: flex; align-items: center; gap: 10px;
    color: var(--bs-body-color, #212529);
}
.ch-header h1 i { color: var(--ch-accent); }

/* ── Card ── */
.ch-card {
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 14px; overflow: hidden;
    margin-bottom: 16px;
}
.ch-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
    font-weight: 600; font-size: .88rem;
    color: var(--bs-body-color, #212529);
    display: flex; align-items: center; gap: 8px;
}
.ch-card-header i { color: var(--ch-accent); }
.ch-card-body { padding: 20px; }

/* ── Cart item ── */
.ch-item {
    display: flex; align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 20px;
}
.ch-item + .ch-item { border-top: 1px solid var(--bs-border-color, #dee2e6); }
.ch-item-title {
    font-weight: 600; font-size: .92rem;
    color: var(--bs-body-color, #212529);
    margin: 0 0 3px;
}
.ch-item-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(79,142,247,.12); color: var(--ch-accent);
    border: 1px solid rgba(79,142,247,.2);
    border-radius: 20px; padding: 1px 8px;
    font-size: .67rem; font-weight: 600;
}
.ch-item-price {
    font-size: 1.05rem; font-weight: 700;
    color: var(--ch-accent);
    font-family: 'DM Mono', monospace;
    white-space: nowrap;
}
.ch-remove-btn {
    background: none; border: none; cursor: pointer;
    color: var(--ch-muted); padding: 4px 8px;
    border-radius: 6px; transition: all .15s;
    line-height: 1;
}
.ch-remove-btn:hover { color: var(--ch-danger); background: rgba(239,68,68,.08); }

/* ── Discount ── */
.ch-discount-row {
    display: flex; gap: 8px;
}
.ch-discount-input {
    flex: 1; background: var(--bs-secondary-bg, #f8f9fa) !important;
    border: 1px solid var(--bs-border-color, #dee2e6) !important;
    border-radius: 8px !important; padding: 9px 12px !important;
    font-family: 'DM Mono', monospace !important;
    font-size: .88rem !important; text-transform: uppercase;
    color: var(--bs-body-color, #212529) !important;
    transition: border-color .2s !important;
}
.ch-discount-input:focus {
    outline: none !important;
    border-color: var(--ch-success) !important;
    box-shadow: 0 0 0 3px rgba(34,197,94,.12) !important;
}
.ch-discount-btn {
    padding: 9px 16px; border-radius: 8px;
    background: rgba(34,197,94,.12); color: var(--ch-success);
    border: 1px solid rgba(34,197,94,.25); font-weight: 600;
    font-size: .85rem; cursor: pointer; transition: all .2s;
    white-space: nowrap;
}
.ch-discount-btn:hover { background: var(--ch-success); color: #fff; }
.ch-discount-msg { font-size: .8rem; margin-top: 6px; min-height: 1.2em; }

/* ── Summary ── */
.ch-summary-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: .88rem; color: var(--ch-muted);
    padding: 4px 0;
}
.ch-summary-row.total {
    font-size: 1.15rem; font-weight: 700;
    color: var(--bs-body-color, #212529);
    border-top: 1px solid var(--bs-border-color, #dee2e6);
    margin-top: 8px; padding-top: 12px;
}
.ch-summary-row.discount { color: var(--ch-success); }
.ch-summary-price { font-family: 'DM Mono', monospace; font-weight: 600; }

/* ── Payment methods info ── */
.ch-payment-info {
    background: rgba(79,142,247,.06);
    border: 1px solid rgba(79,142,247,.18);
    border-radius: 10px; padding: 14px 16px;
    margin-bottom: 14px; font-size: .84rem;
    color: var(--bs-body-color, #212529);
}
.ch-payment-info strong { color: var(--ch-accent); }
.ch-payment-info a { color: var(--ch-accent); }
.ch-payment-notes {
    background: var(--bs-secondary-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 10px; padding: 14px 16px;
    margin-bottom: 14px; font-size: .84rem;
    white-space: pre-wrap;
    color: var(--bs-body-color, #212529);
}

/* ── Form ── */
.ch-label {
    font-size: .75rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .06em;
    color: var(--ch-muted); display: block; margin-bottom: 6px;
}
.ch-input {
    background: var(--bs-secondary-bg, #f8f9fa) !important;
    border: 1px solid var(--bs-border-color, #dee2e6) !important;
    border-radius: 8px !important; padding: 9px 12px !important;
    font-size: .88rem !important; width: 100%;
    color: var(--bs-body-color, #212529) !important;
    transition: border-color .2s !important;
}
.ch-input:focus {
    outline: none !important;
    border-color: var(--ch-accent) !important;
    box-shadow: 0 0 0 3px rgba(79,142,247,.12) !important;
}
.ch-input-prefix {
    display: flex; align-items: center;
}
.ch-prefix {
    background: var(--bs-border-color, #dee2e6);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-right: none; border-radius: 8px 0 0 8px;
    padding: 9px 12px; font-size: .88rem;
    color: var(--ch-muted);
}
.ch-input-prefix .ch-input { border-radius: 0 8px 8px 0 !important; }

/* ── Submit button ── */
.ch-submit {
    width: 100%; padding: 13px;
    background: var(--ch-success); color: #fff;
    border: none; border-radius: 10px;
    font-size: .95rem; font-weight: 700;
    cursor: pointer; transition: background .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    font-family: var(--ch-font);
    margin-top: 20px;
}
.ch-submit:hover { background: #16a34a; }

/* ── Back link ── */
.ch-back {
    background: transparent;
    border: 1px solid var(--bs-border-color, #dee2e6);
    color: var(--ch-muted); border-radius: 8px;
    padding: 7px 14px; font-size: .82rem; font-weight: 500;
    text-decoration: none; display: inline-flex;
    align-items: center; gap: 6px; transition: all .2s;
}
.ch-back:hover { color: var(--bs-body-color, #212529); border-color: var(--ch-accent); }

/* ── Empty state ── */
.ch-empty {
    text-align: center; padding: 64px 0;
    color: var(--ch-muted);
}
.ch-empty i { font-size: 2.8rem; opacity: .3; display: block; margin-bottom: 14px; }
.ch-empty h5 { color: var(--bs-body-color, #212529); margin-bottom: 6px; }

/* ── Notice ── */
.ch-notice {
    font-size: .78rem; color: var(--ch-muted);
    margin-top: 14px; text-align: center;
    display: flex; align-items: flex-start; gap: 6px; justify-content: center;
}
</style>
@endsection

@section('content')
<div class="ch-shell">
<div class="row justify-content-center">
<div class="col-lg-7">

    {{-- Empty state --}}
    <div id="emptyCart" style="display:none;">
        <div class="ch-empty">
            <i class="fas fa-shopping-cart"></i>
            <h5>Tu carrito está vacío</h5>
            <p>Añade productos de la tienda para continuar.</p>
            <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="ch-back" style="margin:auto;display:inline-flex;">
                <i class="fas fa-store"></i> Volver a la tienda
            </a>
        </div>
    </div>

    {{-- Cart content --}}
    <div id="cartContent" style="display:none;">

        {{-- Header --}}
        <div class="ch-header">
            <h1><i class="fas fa-shopping-cart"></i> Tu carrito</h1>
            <a href="{{ route('creator.storefront.categories', $creator->creator_slug) }}" class="ch-back">
                <i class="fas fa-arrow-left"></i> Seguir comprando
            </a>
        </div>

        {{-- Items --}}
        <div class="ch-card">
            <div class="ch-card-header">
                <i class="fas fa-box-open"></i> Productos
            </div>
            <div id="cartItemsList"></div>
        </div>

        {{-- Discount code --}}
        <div class="ch-card">
            <div class="ch-card-header">
                <i class="fas fa-tag"></i> Código de descuento
            </div>
            <div class="ch-card-body">
                <div class="ch-discount-row">
                    <input type="text" id="discountInput" class="ch-discount-input"
                           placeholder="CÓDIGO" maxlength="50"
                           oninput="this.value=this.value.toUpperCase()">
                    <button class="ch-discount-btn" type="button" onclick="applyDiscount()">
                        <i class="fas fa-check me-1"></i> Aplicar
                    </button>
                </div>
                <div id="discountMsg" class="ch-discount-msg"></div>
            </div>
        </div>

        {{-- Order summary --}}
        <div class="ch-card">
            <div class="ch-card-header">
                <i class="fas fa-receipt"></i> Resumen del pedido
            </div>
            <div class="ch-card-body">
                <div id="orderSummary"></div>
            </div>
        </div>

        {{-- Payment info + form --}}
        <div class="ch-card">
            <div class="ch-card-header">
                <i class="fas fa-credit-card"></i> Datos de pago
            </div>
            <div class="ch-card-body">

                @if(!empty($methods['paypal_url']))
                    <div class="ch-payment-info">
                        <strong><i class="fab fa-paypal me-1"></i>PayPal:</strong>
                        <a href="{{ $methods['paypal_url'] }}" target="_blank" style="word-break:break-all;">{{ $methods['paypal_url'] }}</a>
                    </div>
                @endif

                @if(!empty($methods['payment_button_html']))
                    <div class="ch-payment-info">
                        <strong><i class="fas fa-hand-pointer me-1"></i>Botón de pago:</strong>
                        <div class="mt-2">{!! $methods['payment_button_html'] !!}</div>
                    </div>
                @endif

                @if(!empty($methods['other_payment_notes']))
                    <div class="ch-payment-notes">{{ $methods['other_payment_notes'] }}</div>
                @endif

                <form method="POST" action="{{ route('creator.cart.checkout', $creator->creator_slug) }}" id="cartForm">
                    @csrf
                    <div id="videoIdInputs"></div>
                    <input type="hidden" name="discount_code" id="discountCodeHidden">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ch-label">Usuario de Telegram</label>
                            <div class="ch-input-prefix">
                                <span class="ch-prefix">@</span>
                                <input class="ch-input" name="telegram_username" required placeholder="usuario">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="ch-label">Método de pago</label>
                            <select class="ch-input" name="payment_method" required>
                                <option value="paypal">PayPal</option>
                                <option value="boton_personalizado">Botón personalizado</option>
                                <option value="otro">Otro método</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ch-label">Referencia de pago <span style="font-weight:400;text-transform:none;letter-spacing:0;">(opcional)</span></label>
                            <input class="ch-input" name="payment_reference" placeholder="ID de operación">
                        </div>
                        <div class="col-md-6">
                            <label class="ch-label">URL del comprobante <span style="font-weight:400;text-transform:none;letter-spacing:0;">(opcional)</span></label>
                            <input class="ch-input" name="proof_url" placeholder="https://...">
                        </div>
                    </div>

                    <button class="ch-submit" type="submit">
                        <i class="fas fa-paper-plane"></i> Ya pagué — enviar para revisión
                    </button>
                </form>

                <p class="ch-notice">
                    <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:2px;"></i>
                    El creador verificará tu pago y activará cada producto individualmente.
                </p>
            </div>
        </div>

    </div>{{-- /cartContent --}}
</div>
</div>
</div>{{-- /ch-shell --}}
@endsection

@section('scripts')
<script>
const CART_KEY = 'cart_{{ $creator->creator_slug }}';
let discountData = null;

function getCart() { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); }

function renderPage() {
    const cart = getCart();

    if (cart.length === 0) {
        document.getElementById('emptyCart').style.display = '';
        document.getElementById('cartContent').style.display = 'none';
        return;
    }
    document.getElementById('emptyCart').style.display = 'none';
    document.getElementById('cartContent').style.display = '';

    // Items
    let html = '';
    cart.forEach(item => {
        html += `
        <div class="ch-item">
            <div style="flex:1;min-width:0;">
                <p class="ch-item-title">${escHtml(item.title)}</p>
                ${item.type === 'service' ? '<span class="ch-item-badge"><i class="fas fa-key" style="font-size:.6rem;"></i>Servicio</span>' : ''}
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                <span class="ch-item-price">$${item.price.toFixed(2)}</span>
                <button class="ch-remove-btn" onclick="removeItem(${item.id})" title="Quitar">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>`;
    });
    document.getElementById('cartItemsList').innerHTML = html;

    // Hidden inputs
    const container = document.getElementById('videoIdInputs');
    container.innerHTML = '';
    cart.forEach(item => {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'video_ids[]'; inp.value = item.id;
        container.appendChild(inp);
    });

    // Summary
    const rawTotal = cart.reduce((s, i) => s + i.price, 0);
    const count    = cart.length;
    let summaryHtml = '';

    if (discountData) {
        const finalTotal = Math.max(0, rawTotal - discountData.discount);
        document.getElementById('discountCodeHidden').value = discountData.code;
        summaryHtml = `
            <div class="ch-summary-row">
                <span>Subtotal (${count} producto${count > 1 ? 's' : ''})</span>
                <span class="ch-summary-price">$${rawTotal.toFixed(2)}</span>
            </div>
            <div class="ch-summary-row discount">
                <span><i class="fas fa-tag me-1"></i>${escHtml(discountData.code)}${discountData.description ? ' — ' + escHtml(discountData.description) : ''}</span>
                <span class="ch-summary-price">−${discountData.formatted}</span>
            </div>
            <div class="ch-summary-row total">
                <span>Total</span>
                <span class="ch-summary-price" style="color:var(--ch-success);">$${finalTotal.toFixed(2)}</span>
            </div>`;
    } else {
        document.getElementById('discountCodeHidden').value = '';
        summaryHtml = `
            <div class="ch-summary-row total" style="margin-top:0;padding-top:0;border-top:none;">
                <span>Total (${count} producto${count > 1 ? 's' : ''})</span>
                <span class="ch-summary-price">$${rawTotal.toFixed(2)}</span>
            </div>`;
    }
    document.getElementById('orderSummary').innerHTML = summaryHtml;
}

function removeItem(id) {
    localStorage.setItem(CART_KEY, JSON.stringify(getCart().filter(i => i.id !== id)));
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
        msg.style.color = 'var(--ch-warning)';
        msg.textContent = 'Introduce un código de descuento.';
        return;
    }

    fetch('{{ route('discount-codes.validate') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ code, amount: total }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.valid) {
            discountData    = { code, discount: data.discount, formatted: data.formatted, description: data.description };
            msg.style.color = 'var(--ch-success)';
            msg.textContent = '✓ Código aplicado correctamente.';
        } else {
            discountData    = null;
            msg.style.color = 'var(--ch-danger)';
            msg.textContent = '✗ ' + (data.message || 'Código inválido.');
        }
        renderPage();
    })
    .catch(() => {
        msg.style.color = 'var(--ch-danger)';
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
