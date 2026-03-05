@extends('layout')

@section('title', 'Comprar: ' . $video->title)

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

/* ── Product card ── */
.ch-product-card {
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 14px; overflow: hidden;
    margin-bottom: 16px;
}
.ch-product-thumb {
    height: 200px; background: #0e1117;
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.ch-product-thumb img { width: 100%; height: 100%; object-fit: contain; }
.ch-product-thumb-placeholder { color: rgba(255,255,255,.2); font-size: 3rem; }
.ch-product-info { padding: 16px 20px; }
.ch-product-store {
    font-size: .78rem; color: var(--ch-muted);
    display: flex; align-items: center; gap: 6px; margin-bottom: 6px;
}
.ch-product-title {
    font-size: 1.1rem; font-weight: 700;
    color: var(--bs-body-color, #212529);
    letter-spacing: -.02em; margin: 0 0 6px;
}
.ch-product-desc { font-size: .82rem; color: var(--ch-muted); margin: 0 0 12px; line-height: 1.45; }

/* ── Price box ── */
.ch-price-box {
    display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap;
    padding: 12px 16px;
    background: rgba(79,142,247,.07);
    border: 1px solid rgba(79,142,247,.18);
    border-radius: 10px;
}
.ch-price-current {
    font-size: 1.6rem; font-weight: 700;
    font-family: 'DM Mono', monospace;
    color: var(--ch-accent); letter-spacing: -.03em;
}
.ch-price-current.discounted { color: var(--ch-success); }
.ch-price-original {
    font-size: .95rem; font-family: 'DM Mono', monospace;
    color: var(--ch-muted); text-decoration: line-through;
}
.ch-discount-applied {
    display: none; font-size: .78rem; color: var(--ch-success);
    background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.2);
    border-radius: 20px; padding: 2px 10px; font-weight: 600;
}

/* ── Service info ── */
.ch-service-info {
    background: rgba(79,142,247,.07); border: 1px solid rgba(79,142,247,.18);
    border-radius: 10px; padding: 12px 16px; font-size: .84rem; margin-top: 12px;
    color: var(--bs-body-color, #212529); display: flex; gap: 16px; flex-wrap: wrap;
}
.ch-service-info span { display: flex; align-items: center; gap: 5px; }
.ch-service-info i { color: var(--ch-accent); }

/* ── Card ── */
.ch-card {
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 14px; overflow: hidden; margin-bottom: 16px;
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

/* ── PayPal.me button ── */
.ch-paypalme-btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 13px 20px;
    background: #0070ba; color: #fff;
    border: none; border-radius: 10px;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    text-decoration: none; transition: background .2s;
    font-family: var(--ch-font);
}
.ch-paypalme-btn:hover { background: #005ea6; color: #fff; }

/* ── Method section divider ── */
.ch-method-divider {
    display: flex; align-items: center; gap: 10px;
    font-size: .75rem; color: var(--ch-muted);
    text-transform: uppercase; letter-spacing: .06em;
    margin: 16px 0;
}
.ch-method-divider::before,
.ch-method-divider::after {
    content: ''; flex: 1;
    border-top: 1px solid var(--bs-border-color, #dee2e6);
}

/* ── Payment notes ── */
.ch-payment-notes {
    background: var(--bs-secondary-bg, #f8f9fa);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 10px; padding: 14px 16px;
    font-size: .84rem; white-space: pre-wrap;
    color: var(--bs-body-color, #212529);
}

/* ── Discount ── */
.ch-discount-row { display: flex; gap: 8px; }
.ch-discount-input {
    flex: 1; background: var(--bs-secondary-bg, #f8f9fa) !important;
    border: 1px solid var(--bs-border-color, #dee2e6) !important;
    border-radius: 8px !important; padding: 9px 12px !important;
    font-family: 'DM Mono', monospace !important; font-size: .88rem !important;
    text-transform: uppercase; color: var(--bs-body-color, #212529) !important;
    transition: border-color .2s !important;
}
.ch-discount-input:focus {
    outline: none !important; border-color: var(--ch-success) !important;
    box-shadow: 0 0 0 3px rgba(34,197,94,.12) !important;
}
.ch-discount-btn {
    padding: 9px 16px; border-radius: 8px;
    background: rgba(34,197,94,.12); color: var(--ch-success);
    border: 1px solid rgba(34,197,94,.25); font-weight: 600;
    font-size: .85rem; cursor: pointer; transition: all .2s; white-space: nowrap;
}
.ch-discount-btn:hover { background: var(--ch-success); color: #fff; }
.ch-discount-msg { font-size: .8rem; margin-top: 6px; min-height: 1.2em; }

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
    outline: none !important; border-color: var(--ch-accent) !important;
    box-shadow: 0 0 0 3px rgba(79,142,247,.12) !important;
}
.ch-input-prefix { display: flex; align-items: center; }
.ch-prefix {
    background: var(--bs-border-color, #dee2e6);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-right: none; border-radius: 8px 0 0 8px;
    padding: 9px 12px; font-size: .88rem; color: var(--ch-muted);
}
.ch-input-prefix .ch-input { border-radius: 0 8px 8px 0 !important; }

/* ── Submit ── */
.ch-submit {
    width: 100%; padding: 13px;
    background: var(--ch-success); color: #fff;
    border: none; border-radius: 10px;
    font-size: .95rem; font-weight: 700;
    cursor: pointer; transition: background .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    font-family: var(--ch-font); margin-top: 20px;
}
.ch-submit:hover { background: #16a34a; }

/* ── Back link ── */
.ch-back {
    background: transparent; border: 1px solid var(--bs-border-color, #dee2e6);
    color: var(--ch-muted); border-radius: 8px; padding: 7px 14px;
    font-size: .82rem; font-weight: 500; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px; transition: all .2s; margin-bottom: 20px;
}
.ch-back:hover { color: var(--bs-body-color, #212529); border-color: var(--ch-accent); }

.ch-notice {
    font-size: .78rem; color: var(--ch-muted); margin-top: 14px;
    text-align: center; display: flex; align-items: flex-start;
    gap: 6px; justify-content: center;
}
</style>
@endsection

@section('content')
@php
    $hasPaypalMe     = !empty($methods['paypal_url']);
    $hasButtonHtml   = !empty($methods['payment_button_html']);
    $hasOtherNotes   = !empty($methods['other_payment_notes']);
    $hasManualMethod = $hasPaypalMe || $hasButtonHtml || $hasOtherNotes;
@endphp
<div class="ch-shell">
<div class="row justify-content-center">
<div class="col-lg-6">

    <a href="{{ url()->previous() }}" class="ch-back">
        <i class="fas fa-arrow-left"></i> Volver
    </a>

    {{-- Product preview --}}
    <div class="ch-product-card">
        @if($video->hasThumbnail())
            <div class="ch-product-thumb">
                <img src="{{ $video->getThumbnailUrl() }}" alt="{{ $video->title }}">
            </div>
        @endif
        <div class="ch-product-info">
            <p class="ch-product-store">
                <i class="fas fa-store"></i> {{ $creator->creator_store_name ?? $creator->name }}
            </p>
            <h2 class="ch-product-title">{{ $video->title }}</h2>
            @if($video->description)
                <p class="ch-product-desc">{{ $video->description }}</p>
            @endif
            <div class="ch-price-box">
                <span class="ch-price-original" id="priceOriginal" style="display:none;">${{ number_format($video->price, 2) }}</span>
                <span class="ch-price-current" id="priceCurrent">${{ number_format($video->price, 2) }}</span>
                <span class="ch-discount-applied" id="discountAppliedBadge"></span>
            </div>
            @if($video->isServiceProduct())
                <div class="ch-service-info">
                    <span><i class="fas fa-calendar-alt"></i> {{ $video->duration_days ?? 30 }} días de acceso</span>
                    <span><i class="fas fa-box"></i> Stock: {{ $video->availableServiceLines()->count() }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- ── PayPal automático (API) ── --}}
    @if(!empty($paypalConfigured))
    <div class="ch-card">
        <div class="ch-card-header">
            <i class="fab fa-paypal"></i> Pagar con PayPal
            <span style="margin-left:auto;font-size:.72rem;font-weight:400;color:var(--ch-success);background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:20px;padding:2px 10px;">
                <i class="fas fa-bolt" style="font-size:.65rem;"></i> Entrega automática
            </span>
        </div>
        <div class="ch-card-body">
            <div class="mb-3">
                <label class="ch-label">Tu usuario de Telegram <span style="color:var(--ch-danger)">*</span></label>
                <div class="ch-input-prefix">
                    <span class="ch-prefix">@</span>
                    <input class="ch-input" id="paypalTelegramInput" placeholder="usuario" maxlength="100">
                </div>
                <div id="paypalTelegramError" style="font-size:.78rem;color:var(--ch-danger);margin-top:4px;display:none;">
                    Introduce tu usuario de Telegram antes de pagar.
                </div>
            </div>
            <div id="paypal-button-container"></div>
        </div>
    </div>
    @if($hasManualMethod)
        <div style="text-align:center;color:var(--ch-muted);font-size:.82rem;margin:-6px 0 10px;letter-spacing:.04em;">— o usa otro método —</div>
    @endif
    @endif

    {{-- ── Descuento ── --}}
    <div class="ch-card">
        <div class="ch-card-header">
            <i class="fas fa-tag"></i> Código de descuento
        </div>
        <div class="ch-card-body">
            <div class="ch-discount-row">
                <input type="text" id="discountCodeInput" class="ch-discount-input"
                       placeholder="CÓDIGO" maxlength="50"
                       oninput="this.value=this.value.toUpperCase(); resetDiscount()">
                <button class="ch-discount-btn" type="button" onclick="applyDiscount()">
                    <i class="fas fa-check me-1"></i> Aplicar
                </button>
            </div>
            <div id="discountMsg" class="ch-discount-msg"></div>
        </div>
    </div>

    {{-- ── Métodos manuales ── --}}
    @if($hasManualMethod)
    <div class="ch-card">
        <div class="ch-card-header">
            <i class="fas fa-wallet"></i> Otros métodos de pago
        </div>
        <div class="ch-card-body">

            {{-- PayPal.me --}}
            @if($hasPaypalMe)
                <p style="font-size:.83rem;color:var(--ch-muted);margin-bottom:10px;">
                    Haz clic en el botón, paga en PayPal y vuelve aquí para notificarnos.
                </p>
                <a href="{{ $methods['paypal_url'] }}" target="_blank" class="ch-paypalme-btn">
                    <i class="fab fa-paypal"></i> Pagar con PayPal.me
                    <i class="fas fa-external-link-alt" style="font-size:.75rem;opacity:.7;"></i>
                </a>
            @endif

            {{-- Botón HTML personalizado --}}
            @if($hasButtonHtml)
                @if($hasPaypalMe)
                    <div class="ch-method-divider">otro método</div>
                @endif
                <div>{!! $methods['payment_button_html'] !!}</div>
            @endif

            {{-- Otros (Binance, transferencia, etc.) --}}
            @if($hasOtherNotes)
                @if($hasPaypalMe || $hasButtonHtml)
                    <div class="ch-method-divider">instrucciones</div>
                @endif
                <div class="ch-payment-notes">{{ $methods['other_payment_notes'] }}</div>
            @endif

        </div>
    </div>

    {{-- ── Notificar al creador ── --}}
    <div class="ch-card">
        <div class="ch-card-header">
            <i class="fas fa-paper-plane"></i> Notificar al creador
        </div>
        <div class="ch-card-body">
            <p style="font-size:.83rem;color:var(--ch-muted);margin-bottom:16px;">
                Una vez hayas pagado por el método de arriba, deja tu usuario de Telegram para que el creador pueda enviarte el acceso.
            </p>
            <form method="POST" action="{{ route('creator.checkout.submit', ['creator' => $creator->creator_slug, 'video' => $video->id]) }}">
                @csrf
                <input type="hidden" name="discount_code" id="discountCodeHidden">
                <input type="hidden" name="payment_method" value="{{ $hasPaypalMe ? 'paypal' : ($hasButtonHtml ? 'boton_personalizado' : 'otro') }}">

                <div class="mb-3">
                    <label class="ch-label">Tu usuario de Telegram <span style="color:var(--ch-danger)">*</span></label>
                    <div class="ch-input-prefix">
                        <span class="ch-prefix">@</span>
                        <input class="ch-input" name="telegram_username" required placeholder="usuario" maxlength="100">
                    </div>
                </div>
                <div>
                    <label class="ch-label">Nota <span style="font-weight:400;text-transform:none;letter-spacing:0;">(opcional)</span></label>
                    <textarea class="ch-input" name="payment_reference" rows="2" style="resize:vertical;"
                              placeholder="Ej: ya pagué, ID de transacción, etc."></textarea>
                </div>

                <button class="ch-submit" type="submit">
                    <i class="fas fa-paper-plane"></i> Ya pagué — notificar al creador
                </button>
            </form>

            <p class="ch-notice">
                <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:2px;"></i>
                El creador revisará tu pago y activará el acceso manualmente.
            </p>
        </div>
    </div>
    @endif

    @if(!$hasManualMethod && !$paypalConfigured)
    <div style="text-align:center;padding:40px 0;color:var(--ch-muted);">
        <i class="fas fa-exclamation-circle" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
        Este creador aún no ha configurado métodos de pago.
    </div>
    @endif

</div>
</div>
</div>{{-- /ch-shell --}}
@endsection

@section('scripts')
<script>
const basePrice = {{ (float) $video->price }};

function applyDiscount() {
    const code = document.getElementById('discountCodeInput').value.trim();
    const msg  = document.getElementById('discountMsg');
    if (!code) {
        msg.style.color = 'var(--ch-warning)';
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
            const currentEl  = document.getElementById('priceCurrent');
            const originalEl = document.getElementById('priceOriginal');
            const badgeEl    = document.getElementById('discountAppliedBadge');
            currentEl.textContent = `$${data.final_amount.toFixed(2)}`;
            currentEl.classList.add('discounted');
            originalEl.style.display = '';
            badgeEl.textContent = `−${data.formatted}`;
            badgeEl.style.display = 'inline-flex';
            msg.style.color = 'var(--ch-success)';
            msg.textContent = '✓ Código aplicado.';
        } else {
            resetDiscount();
            msg.style.color = 'var(--ch-danger)';
            msg.textContent = '✗ ' + (data.message || 'Código inválido.');
        }
    })
    .catch(() => {
        msg.style.color = 'var(--ch-danger)';
        msg.textContent = 'Error al verificar el código.';
    });
}

function resetDiscount() {
    document.getElementById('discountCodeHidden').value = '';
    document.getElementById('priceCurrent').textContent = `$${basePrice.toFixed(2)}`;
    document.getElementById('priceCurrent').classList.remove('discounted');
    document.getElementById('priceOriginal').style.display = 'none';
    document.getElementById('discountAppliedBadge').style.display = 'none';
    document.getElementById('discountMsg').textContent = '';
}

document.getElementById('discountCodeInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); applyDiscount(); }
});
</script>

@if(!empty($paypalConfigured))
<script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency=USD" data-namespace="paypal_sdk"></script>
<script>
paypal_sdk.Buttons({
    createOrder: function() {
        const tg = document.getElementById('paypalTelegramInput').value.trim().replace(/^@/, '');
        if (!tg) {
            document.getElementById('paypalTelegramError').style.display = '';
            return Promise.reject(new Error('Falta usuario de Telegram'));
        }
        document.getElementById('paypalTelegramError').style.display = 'none';
        return fetch('{{ route('api.paypal.create-order') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                video_id: {{ $video->id }},
                telegram_username: tg,
            }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.error) throw new Error(d.error);
            return d.order_id;
        });
    },
    onApprove: function(data) {
        return fetch('{{ route('api.paypal.capture-order') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ order_id: data.orderID }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.success && d.redirect_url) {
                window.location.href = d.redirect_url;
            } else {
                alert(d.error || 'Error al procesar el pago.');
            }
        });
    },
    onError: function(err) {
        alert('Error en PayPal: ' + err);
    }
}).render('#paypal-button-container');
</script>
@endif
@endsection
