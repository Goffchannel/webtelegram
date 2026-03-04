@extends('layout')

@section('title', 'Comprar: ' . $video->title)

@section('styles')
<style>
/* Make the full page dark — override the public layout's white body + container mt-4 */
body { background: #0e1117 !important; }
main.container { max-width: 100% !important; padding: 0 !important; margin-top: 0 !important; }

:root {
    --pf-bg:      #0e1117;
    --pf-surface: #161b25;
    --pf-border:  #252d3d;
    --pf-accent:  #4f8ef7;
    --pf-success: #22c55e;
    --pf-warning: #f59e0b;
    --pf-danger:  #ef4444;
    --pf-text:    #e2e8f0;
    --pf-muted:   #64748b;
}

.pf-wrap {
    min-height: calc(100vh - 80px);
    background: var(--pf-bg);
    padding: 32px 16px 60px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.pf-card {
    width: 100%;
    max-width: 540px;
    background: var(--pf-surface);
    border: 1px solid var(--pf-border);
    border-radius: 16px;
    overflow: hidden;
}

/* ── Thumbnail ── */
.pf-thumb {
    height: 220px;
    background: #090c12;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}
.pf-thumb img {
    width: 100%; height: 100%;
    object-fit: cover;
    object-position: top;
}
.pf-thumb-placeholder {
    color: rgba(255,255,255,.15);
    font-size: 4rem;
}
.pf-thumb-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    flex-direction: column; gap: 8px;
    cursor: pointer;
    transition: background .2s;
}
.pf-thumb-overlay:hover { background: rgba(0,0,0,.35); }
.pf-thumb-overlay i { font-size: 2.5rem; color: rgba(255,255,255,.85); }
.pf-thumb-overlay span { font-size: .78rem; color: rgba(255,255,255,.6); }

/* ── Body ── */
.pf-body { padding: 24px; }

.pf-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--pf-text);
    letter-spacing: -.02em;
    margin: 0 0 4px;
}
.pf-desc {
    font-size: .85rem;
    color: var(--pf-muted);
    margin: 0 0 16px;
    line-height: 1.5;
}

/* ── Service badge row ── */
.pf-meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.pf-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 600;
}
.pf-badge-blue  { background: rgba(79,142,247,.15); color: var(--pf-accent); border: 1px solid rgba(79,142,247,.25); }
.pf-badge-green { background: rgba(34,197,94,.12);  color: var(--pf-success); border: 1px solid rgba(34,197,94,.2); }
.pf-badge-amber { background: rgba(245,158,11,.12); color: var(--pf-warning); border: 1px solid rgba(245,158,11,.2); }

/* ── Price box ── */
.pf-price-box {
    display: flex;
    align-items: baseline;
    gap: 8px;
    padding: 12px 16px;
    background: rgba(79,142,247,.07);
    border: 1px solid rgba(79,142,247,.18);
    border-radius: 10px;
    margin-bottom: 20px;
}
.pf-price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--pf-accent);
    font-family: 'DM Mono', monospace;
    letter-spacing: -.04em;
}
.pf-price-label {
    font-size: .8rem;
    color: var(--pf-muted);
}

/* ── Steps ── */
.pf-steps {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px 16px;
    background: rgba(255,255,255,.03);
    border: 1px solid var(--pf-border);
    border-radius: 10px;
    margin-bottom: 20px;
}
.pf-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: .83rem;
    color: var(--pf-muted);
}
.pf-step-num {
    width: 20px; height: 20px;
    border-radius: 50%;
    background: rgba(79,142,247,.15);
    color: var(--pf-accent);
    font-size: .7rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

/* ── Form ── */
.pf-label {
    font-size: .82rem;
    font-weight: 600;
    color: var(--pf-text);
    margin-bottom: 6px;
    display: block;
}
.pf-label span { color: var(--pf-danger); }
.pf-input-group {
    display: flex;
    border: 1px solid var(--pf-border);
    border-radius: 8px;
    overflow: hidden;
    background: #0d1117;
    margin-bottom: 4px;
    transition: border-color .2s;
}
.pf-input-group:focus-within {
    border-color: var(--pf-accent);
    box-shadow: 0 0 0 3px rgba(79,142,247,.12);
}
.pf-input-prefix {
    display: flex; align-items: center;
    padding: 0 12px;
    color: var(--pf-muted);
    font-size: .9rem;
    background: rgba(255,255,255,.04);
    border-right: 1px solid var(--pf-border);
    user-select: none;
}
.pf-input {
    flex: 1;
    padding: 10px 12px;
    background: transparent;
    border: none;
    outline: none;
    color: var(--pf-text);
    font-size: .9rem;
}
.pf-input::placeholder { color: var(--pf-muted); }
.pf-hint {
    font-size: .75rem;
    color: var(--pf-muted);
    margin-bottom: 20px;
}

/* ── Bot warning ── */
.pf-warning {
    display: flex;
    gap: 10px;
    padding: 12px 14px;
    background: rgba(245,158,11,.08);
    border: 1px solid rgba(245,158,11,.2);
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: .82rem;
    color: var(--pf-warning);
}

/* ── Submit button ── */
.pf-btn {
    width: 100%;
    padding: 13px;
    border-radius: 10px;
    border: none;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: filter .15s, transform .1s;
    margin-bottom: 12px;
}
.pf-btn:active { transform: scale(.985); }
.pf-btn-pay {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
}
.pf-btn-pay:hover { filter: brightness(1.08); }
.pf-btn-pay:disabled {
    background: var(--pf-border);
    color: var(--pf-muted);
    cursor: not-allowed;
    filter: none;
}

.pf-back {
    display: block;
    text-align: center;
    font-size: .8rem;
    color: var(--pf-muted);
    text-decoration: none;
    margin-top: 6px;
    transition: color .15s;
}
.pf-back:hover { color: var(--pf-text); }

/* ── Alert messages ── */
.pf-alert {
    display: flex;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    margin-bottom: 14px;
    font-size: .83rem;
}
.pf-alert-error {
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.25);
    color: #fca5a5;
}
.pf-alert-success {
    background: rgba(34,197,94,.1);
    border: 1px solid rgba(34,197,94,.25);
    color: #86efac;
}
</style>
@endsection

@section('content')
<div class="pf-wrap">
    <div class="pf-card">

        {{-- Thumbnail --}}
        @if($video->hasThumbnail())
            <div class="pf-thumb">
                <img id="pf-img" src="{{ $video->getThumbnailUrl() }}" alt="{{ $video->title }}"
                    style="{{ $video->shouldShowBlurred() ? $video->getBlurredThumbnailStyle() : '' }}{{ $video->allow_preview ? ' cursor:pointer;' : '' }}"
                    @if($video->allow_preview) onclick="toggleBlur(this, {{ $video->blur_intensity }})" @endif>
                @if($video->shouldShowBlurred())
                    <div class="pf-thumb-overlay" @if($video->allow_preview) onclick="toggleBlur(document.getElementById('pf-img'), {{ $video->blur_intensity }})" @endif>
                        <i class="fas fa-lock"></i>
                        @if($video->allow_preview)<span>Clic para previsualizar</span>@endif
                    </div>
                @endif
            </div>
        @else
            <div class="pf-thumb">
                <i class="fas fa-play-circle pf-thumb-placeholder"></i>
            </div>
        @endif

        <div class="pf-body">

            {{-- Title + meta --}}
            <h1 class="pf-title">{{ $video->title }}</h1>
            @if($video->description)
                <p class="pf-desc">{{ $video->description }}</p>
            @endif

            <div class="pf-meta">
                @if($video->isServiceProduct())
                    <span class="pf-badge pf-badge-blue">
                        <i class="fas fa-tv"></i> Acceso {{ $video->duration_days ?? 30 }} días
                    </span>
                    @php
                        $sharedLine = $video->serviceLines()->where('is_shared', true)->exists();
                        $stock = $sharedLine ? 1 : $video->availableServiceLines()->count();
                    @endphp
                    @if($sharedLine)
                        <span class="pf-badge pf-badge-green">
                            <i class="fas fa-infinity"></i> Disponible
                        </span>
                    @else
                        <span class="pf-badge {{ $stock > 0 ? 'pf-badge-green' : 'pf-badge-amber' }}">
                            <i class="fas fa-{{ $stock > 0 ? 'check' : 'exclamation-triangle' }}"></i>
                            {{ $stock > 0 ? "Stock: {$stock}" : 'Sin stock' }}
                        </span>
                    @endif
                @else
                    @if($video->duration)
                        <span class="pf-badge pf-badge-blue">
                            <i class="fas fa-clock"></i>
                            {{ $video->duration >= 3600 ? gmdate('H:i:s', $video->duration) : gmdate('i:s', $video->duration) }}
                        </span>
                    @endif
                @endif

                @if($video->long_description)
                    <span class="pf-badge pf-badge-blue"><i class="fas fa-info-circle"></i> Info extra</span>
                @endif
            </div>

            {{-- Long description --}}
            @if($video->long_description)
                <div style="font-size:.82rem; color:var(--pf-muted); background:rgba(255,255,255,.03); border:1px solid var(--pf-border); border-radius:8px; padding:12px 14px; margin-bottom:16px; white-space:pre-wrap; line-height:1.55;">{{ $video->long_description }}</div>
            @endif

            {{-- Price --}}
            <div class="pf-price-box">
                <span class="pf-price">${{ number_format($video->price, 2) }}</span>
                <span class="pf-price-label">
                    pago único
                    @if($video->isServiceProduct()) · {{ $video->duration_days ?? 30 }} días de acceso @endif
                </span>
            </div>

            {{-- Steps --}}
            @if($bot['is_configured'])
            <div class="pf-steps">
                <div class="pf-step">
                    <div class="pf-step-num">1</div>
                    <div>Introduce tu usuario de Telegram y completa el pago con Stripe (seguro)</div>
                </div>
                <div class="pf-step">
                    <div class="pf-step-num">2</div>
                    <div>
                        Abre el bot:
                        <a href="{{ $bot['url'] }}" target="_blank" style="color:var(--pf-accent);">{{ $bot['username'] }}</a>
                        y escribe <code style="color:var(--pf-accent); font-size:.8rem;">/start</code>
                    </div>
                </div>
                <div class="pf-step">
                    <div class="pf-step-num">3</div>
                    <div>
                        @if($video->isServiceProduct())
                            Recibirás tus credenciales de acceso al instante
                        @else
                            Recibirás el video directamente en Telegram
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Alert container --}}
            <div id="pf-alerts"></div>

            {{-- Bot not configured warning --}}
            @if(!$bot['is_configured'])
            <div class="pf-warning">
                <i class="fas fa-exclamation-triangle" style="margin-top:2px; flex-shrink:0;"></i>
                <div>
                    <strong>Bot no configurado.</strong><br>
                    El sistema de entrega aún no está listo. Contacta con el administrador.
                </div>
            </div>
            @endif

            {{-- Form --}}
            <form id="paymentForm" action="{{ route('payment.process', $video) }}" method="POST">
                @csrf

                <label class="pf-label" for="telegram_username">
                    <i class="fab fa-telegram me-1"></i> Usuario de Telegram <span>*</span>
                </label>
                <div class="pf-input-group">
                    <span class="pf-input-prefix">@</span>
                    <input class="pf-input" type="text" id="telegram_username" name="telegram_username"
                        value="{{ old('telegram_username') }}" placeholder="tu_usuario" required autocomplete="off">
                </div>
                @error('telegram_username')
                    <div style="font-size:.75rem; color:var(--pf-danger); margin-bottom:8px;">{{ $message }}</div>
                @enderror
                <p class="pf-hint">Sin @. El acceso/video se enviará a esta cuenta de Telegram.</p>

                @if($video->isServiceProduct() && !$video->serviceLines()->where('is_shared', true)->exists() && $video->availableServiceLines()->count() === 0)
                    <button type="button" class="pf-btn" disabled>
                        <i class="fas fa-exclamation-triangle"></i> Sin stock disponible
                    </button>
                @elseif($bot['is_configured'])
                    <button type="submit" class="pf-btn pf-btn-pay" id="paymentButton">
                        <i class="fas fa-lock"></i>
                        Pagar ${{ number_format($video->price, 2) }} con Stripe
                    </button>
                @else
                    <button type="button" class="pf-btn" disabled>
                        <i class="fas fa-cog"></i> Pagos desactivados
                    </button>
                @endif
            </form>

            <a href="{{ url()->previous() }}" class="pf-back">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleBlur(img, intensity) {
    const blurred = img.style.filter && img.style.filter.includes('blur');
    img.style.filter = blurred ? 'none' : `blur(${intensity}px)`;
    img.style.transition = 'filter .3s';
}

(function() {
    const form = document.getElementById('paymentForm');
    const btn  = document.getElementById('paymentButton');
    if (!form || !btn) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const username = document.getElementById('telegram_username').value.trim();
        if (!username) {
            showAlert('error', 'Introduce tu usuario de Telegram.');
            return;
        }

        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        btn.disabled = true;
        document.getElementById('pf-alerts').innerHTML = '';

        fetch('/api/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                video_id: {{ $video->id }},
                telegram_username: username
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.session_url) {
                window.location.href = data.session_url;
            } else if (data.error) {
                if (data.existing_purchase) {
                    let extra = data.existing_purchase.purchase_uuid
                        ? `<a href="/purchase/${data.existing_purchase.purchase_uuid}" style="color:var(--pf-accent);">Ver compra</a>`
                        : '';
                    showAlert('error', data.error + (extra ? ' ' + extra : ''));
                } else {
                    showAlert('error', data.error);
                }
                btn.innerHTML = orig;
                btn.disabled = false;
            } else {
                showAlert('error', 'Respuesta inesperada del servidor.');
                btn.innerHTML = orig;
                btn.disabled = false;
            }
        })
        .catch(() => {
            showAlert('error', 'Error de conexión. Inténtalo de nuevo.');
            btn.innerHTML = orig;
            btn.disabled = false;
        });
    });

    function showAlert(type, msg) {
        const cls = type === 'error' ? 'pf-alert-error' : 'pf-alert-success';
        const ico = type === 'error' ? 'exclamation-circle' : 'check-circle';
        document.getElementById('pf-alerts').innerHTML =
            `<div class="pf-alert ${cls}"><i class="fas fa-${ico}"></i><span>${msg}</span></div>`;
    }
})();
</script>
@endsection
