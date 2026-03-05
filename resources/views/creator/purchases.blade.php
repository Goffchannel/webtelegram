@extends('layout')

@section('title', 'Compras de mi tienda')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
    --cr-bg: #0e1117;
    --cr-surface: #161b25;
    --cr-border: #252d3d;
    --cr-accent: #4f8ef7;
    --cr-accent-dim: rgba(79,142,247,.12);
    --cr-success: #22c55e;
    --cr-warning: #f59e0b;
    --cr-danger: #ef4444;
    --cr-text: #e2e8f0;
    --cr-muted: #64748b;
    --cr-font: 'Outfit', sans-serif;
}

.cr-shell { font-family: var(--cr-font); }
.cr-shell *:not(i):not([class*="fa"]):not([class*="fab"]) { font-family: var(--cr-font); }

.cr-topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
.cr-topbar h1 {
    font-size: 1.35rem; font-weight: 700; color: var(--cr-text);
    margin: 0; letter-spacing: -.02em;
    display: flex; align-items: center; gap: 8px;
}
.cr-topbar h1 i { color: var(--cr-accent); font-size: 1.1rem; }

/* ── Back button ── */
.cr-btn-back {
    background: rgba(255,255,255,.06);
    border: 1px solid var(--cr-border);
    color: var(--cr-muted);
    border-radius: 8px; padding: 7px 14px;
    font-size: .82rem; font-weight: 500;
    text-decoration: none; display: inline-flex;
    align-items: center; gap: 6px;
    transition: all .2s;
}
.cr-btn-back:hover { color: var(--cr-text); background: rgba(255,255,255,.1); }

/* ── Card ── */
.cr-card {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: 14px; overflow: hidden;
    margin-bottom: 16px;
}

/* ── Table ── */
.cr-table { width: 100%; border-collapse: collapse; }
.cr-table thead th {
    background: rgba(255,255,255,.03);
    color: var(--cr-muted); font-size: .7rem;
    font-weight: 600; text-transform: uppercase;
    letter-spacing: .08em; padding: 10px 16px;
    border-bottom: 1px solid var(--cr-border);
    white-space: nowrap;
}
.cr-table tbody tr { border-bottom: 1px solid rgba(37,45,61,.7); transition: background .15s; }
.cr-table tbody tr:last-child { border-bottom: none; }
.cr-table tbody tr:hover { background: rgba(79,142,247,.04); }
.cr-table td { padding: 12px 16px; color: var(--cr-text); font-size: .85rem; vertical-align: middle; }

/* ── Badges ── */
.cr-badge {
    display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 20px;
    font-size: .72rem; font-weight: 600; white-space: nowrap;
}
.cr-badge-success { background: rgba(34,197,94,.15); color: #22c55e; border: 1px solid rgba(34,197,94,.25); }
.cr-badge-danger  { background: rgba(239,68,68,.12);  color: #ef4444; border: 1px solid rgba(239,68,68,.25); }
.cr-badge-warning { background: rgba(245,158,11,.12); color: #f59e0b; border: 1px solid rgba(245,158,11,.25); }
.cr-badge-info    { background: rgba(79,142,247,.12); color: var(--cr-accent); border: 1px solid rgba(79,142,247,.2); }

/* ── Buttons ── */
.cr-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 14px; border-radius: 8px;
    font-size: .82rem; font-weight: 500;
    cursor: pointer; border: none; transition: all .2s;
    font-family: var(--cr-font);
}
.cr-btn-sm { padding: 5px 10px; font-size: .78rem; }
.cr-btn-success { background: var(--cr-success); color: #fff; }
.cr-btn-success:hover { background: #16a34a; }
.cr-btn-danger  { background: var(--cr-danger); color: #fff; }
.cr-btn-danger:hover  { background: #dc2626; }
.cr-btn-outline { background: transparent; border: 1px solid var(--cr-border); color: var(--cr-muted); }
.cr-btn-outline:hover { color: var(--cr-text); border-color: var(--cr-accent); }
.cr-btn-accent  { background: var(--cr-accent); color: #fff; box-shadow: 0 2px 10px rgba(79,142,247,.3); }
.cr-btn-accent:hover { background: #3b7de8; }

/* ── Messaging button ── */
.cr-msg-btn {
    position: relative;
    background: rgba(79,142,247,.12);
    border: 1px solid rgba(79,142,247,.25);
    color: var(--cr-accent);
    border-radius: 8px; padding: 5px 10px;
    font-size: .82rem; cursor: pointer;
    transition: all .2s; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 4px;
}
.cr-msg-btn:hover { background: rgba(79,142,247,.22); }
.cr-unread-badge {
    position: absolute; top: -5px; right: -5px;
    background: var(--cr-danger); color: #fff;
    border-radius: 50%; width: 16px; height: 16px;
    font-size: .62rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    line-height: 1;
}

/* ── Proof link ── */
.cr-proof-link {
    color: var(--cr-accent); font-size: .8rem;
    text-decoration: none; display: inline-flex;
    align-items: center; gap: 4px;
}
.cr-proof-link:hover { text-decoration: underline; }

/* ── Pagination ── */
.cr-pagination { padding: 14px 20px; border-top: 1px solid var(--cr-border); }
.cr-pagination nav { display: flex; justify-content: flex-end; }
.cr-pagination .pagination { margin: 0; }
.cr-pagination .page-link {
    background: var(--cr-surface); border-color: var(--cr-border);
    color: var(--cr-muted); font-size: .82rem;
}
.cr-pagination .page-link:hover { background: var(--cr-accent-dim); color: var(--cr-accent); }
.cr-pagination .page-item.active .page-link { background: var(--cr-accent); border-color: var(--cr-accent); color: #fff; }

/* ── Modal ── */
.cr-modal .modal-content {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: 14px;
    color: var(--cr-text);
}
.cr-modal .modal-header { border-color: var(--cr-border); padding: 16px 20px; }
.cr-modal .modal-title { font-weight: 600; color: var(--cr-text); font-size: .95rem; }
.cr-modal .modal-footer { border-color: var(--cr-border); }
.cr-modal .btn-close { filter: invert(1) opacity(.6); }
.cr-thread {
    min-height: 200px; max-height: 360px; overflow-y: auto;
    display: flex; flex-direction: column; gap: 8px;
    padding: 14px; background: var(--cr-bg);
    border-radius: 10px; border: 1px solid var(--cr-border);
}
.cr-msg-input {
    background: var(--cr-bg) !important;
    border: 1px solid var(--cr-border) !important;
    color: var(--cr-text) !important;
    border-radius: 8px !important;
    font-family: var(--cr-font) !important;
    font-size: .85rem !important;
    resize: none;
}
.cr-msg-input:focus {
    outline: none !important;
    border-color: var(--cr-accent) !important;
    box-shadow: 0 0 0 3px rgba(79,142,247,.15) !important;
}

/* ── Scrollbar ── */
.cr-shell ::-webkit-scrollbar { width: 6px; }
.cr-shell ::-webkit-scrollbar-track { background: transparent; }
.cr-shell ::-webkit-scrollbar-thumb { background: var(--cr-border); border-radius: 3px; }
</style>
@endsection

@section('content')
<div class="cr-shell">

{{-- Top bar --}}
<div class="cr-topbar">
    <h1><i class="fas fa-receipt"></i> Compras de mi tienda</h1>
    <a href="{{ route('creator.dashboard') }}" class="cr-btn-back">
        <i class="fas fa-arrow-left"></i> Volver al panel
    </a>
</div>

@if(session('success'))
    <div style="background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#22c55e;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:.85rem;">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

<div class="cr-card">
    <div style="overflow-x:auto;">
        <table class="cr-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Cliente</th>
                    <th>Método</th>
                    <th>Referencia</th>
                    <th>Comprobante</th>
                    <th>Estado</th>
                    <th>Chat</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    @php
                        $unread = $purchase->messages->where('sender_type', 'user')->whereNull('read_at')->count();
                    @endphp
                    <tr>
                        <td style="font-family:'DM Mono',monospace;font-size:.78rem;color:var(--cr-muted);white-space:nowrap;">
                            {{ $purchase->created_at->format('d/m/y H:i') }}
                        </td>
                        <td><strong>{{ $purchase->video->title ?? 'N/A' }}</strong></td>
                        <td style="font-family:'DM Mono',monospace;font-size:.82rem;">
                            {{ $purchase->telegram_username ? '@'.$purchase->telegram_username : '—' }}
                        </td>
                        <td style="color:var(--cr-muted);font-size:.82rem;">
                            @php
                                $isPaypal = str_starts_with($purchase->stripe_session_id ?? '', 'paypal_')
                                         || str_starts_with($purchase->stripe_payment_intent_id ?? '', 'paypal_capture_');
                                $method = $isPaypal ? 'paypal' : ($purchase->payment_method ?? '—');
                            @endphp
                            @if($method === 'paypal')
                                <span style="color:#0070ba;font-weight:600;font-size:.8rem;"><i class="fab fa-paypal me-1"></i>PayPal</span>
                            @elseif($method === 'stripe')
                                <span style="color:#635bff;font-weight:600;font-size:.8rem;"><i class="fab fa-stripe-s me-1"></i>Stripe</span>
                            @else
                                {{ $method }}
                            @endif
                        </td>
                        <td style="font-family:'DM Mono',monospace;font-size:.78rem;color:var(--cr-muted);">
                            {{ $purchase->payment_reference ?? '—' }}
                        </td>
                        <td>
                            @if($purchase->proof_url)
                                <a href="{{ $purchase->proof_url }}" target="_blank" class="cr-proof-link">
                                    <i class="fas fa-external-link-alt"></i> Ver
                                </a>
                            @else
                                <span style="color:var(--cr-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($purchase->verification_status === 'verified')
                                <span class="cr-badge cr-badge-success">Aprobado</span>
                            @elseif($purchase->verification_status === 'invalid')
                                <span class="cr-badge cr-badge-danger">Rechazado</span>
                            @else
                                <span class="cr-badge cr-badge-warning">Pendiente</span>
                            @endif
                        </td>
                        <td>
                            <button class="cr-msg-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#creatorMsgModal"
                                    data-purchase-id="{{ $purchase->id }}"
                                    data-username="{{ $purchase->telegram_username ? '@'.$purchase->telegram_username : 'Cliente' }}"
                                    data-has-tg="{{ $purchase->telegram_user_id ? '1' : '0' }}"
                                    onclick="openCreatorMsgModal(this)">
                                <i class="fas fa-comment-dots"></i> Chat
                                @if($unread > 0)
                                    <span class="cr-unread-badge">{{ $unread }}</span>
                                @endif
                            </button>
                        </td>
                        <td>
                            @if($purchase->verification_status === 'pending')
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <form method="POST" action="{{ route('creator.purchases.approve', $purchase) }}" style="display:inline;">
                                        @csrf
                                        <button class="cr-btn cr-btn-sm cr-btn-success" type="submit">
                                            <i class="fas fa-check"></i> Aprobar
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('creator.purchases.reject', $purchase) }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="delivery_notes" value="Pago rechazado por el creador">
                                        <button class="cr-btn cr-btn-sm cr-btn-danger" type="submit">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span style="color:var(--cr-muted);font-size:.8rem;">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;color:var(--cr-muted);padding:48px;">
                            <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
                            No hay compras todavía.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="cr-pagination">
        {{ $purchases->links() }}
    </div>
</div>

</div>{{-- /cr-shell --}}

{{-- Modal de mensajería --}}
<div class="modal fade cr-modal" id="creatorMsgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-comment-dots me-2" style="color:var(--cr-accent);"></i>
                    Mensajes con <span id="creatorMsgUsername" style="color:var(--cr-accent);"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="creatorMsgNoTg" class="d-none" style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:#f59e0b;border-radius:8px;padding:10px 14px;font-size:.83rem;margin-bottom:12px;">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    El comprador aún no ha vinculado su Telegram. Debe escribir <strong>/start</strong> al bot primero.
                </div>
                <div id="creatorMsgThread" class="cr-thread">
                    <p style="color:var(--cr-muted);text-align:center;font-size:.82rem;margin:auto 0;" id="creatorMsgEmpty">Sin mensajes todavía.</p>
                </div>
            </div>
            <div class="modal-footer flex-column align-items-stretch gap-2">
                <textarea id="creatorMsgInput"
                          class="cr-msg-input form-control"
                          rows="2"
                          placeholder="Escribe un mensaje… (Enter envía, Shift+Enter nueva línea)"></textarea>
                <button id="creatorMsgSend" class="cr-btn cr-btn-accent w-100" onclick="sendCreatorMessage()">
                    <i class="fas fa-paper-plane"></i> Enviar mensaje
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let creatorActivePurchaseId = null;
let creatorPollingInterval = null;
let creatorLastMsgId = 0;

function openCreatorMsgModal(btn) {
    creatorActivePurchaseId = btn.dataset.purchaseId;
    const hasTg = btn.dataset.hasTg === '1';
    const username = btn.dataset.username;

    document.getElementById('creatorMsgUsername').textContent = username;

    const noTgAlert = document.getElementById('creatorMsgNoTg');
    const input = document.getElementById('creatorMsgInput');
    const sendBtn = document.getElementById('creatorMsgSend');

    if (hasTg) {
        noTgAlert.classList.add('d-none');
        input.disabled = false;
        sendBtn.disabled = false;
    } else {
        noTgAlert.classList.remove('d-none');
        input.disabled = true;
        sendBtn.disabled = true;
    }

    const thread = document.getElementById('creatorMsgThread');
    thread.innerHTML = '<p style="color:var(--cr-muted);text-align:center;font-size:.82rem;margin:auto 0;" id="creatorMsgEmpty">Cargando...</p>';
    creatorLastMsgId = 0;

    loadCreatorMessages(true);
}

function loadCreatorMessages(initial = false) {
    if (!creatorActivePurchaseId) return;

    let url = `/creator/purchases/${creatorActivePurchaseId}/messages`;
    if (!initial && creatorLastMsgId > 0) {
        url += `?after_id=${creatorLastMsgId}`;
    }

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(data => {
        const msgs = data.messages || [];
        if (msgs.length > 0) {
            const emptyEl = document.getElementById('creatorMsgEmpty');
            if (emptyEl) emptyEl.remove();
            msgs.forEach(msg => appendCreatorMessage(msg));
        } else if (initial) {
            const thread = document.getElementById('creatorMsgThread');
            thread.innerHTML = '<p style="color:var(--cr-muted);text-align:center;font-size:.82rem;margin:auto 0;" id="creatorMsgEmpty">Sin mensajes todavía.</p>';
        }
    })
    .catch(() => {});
}

function appendCreatorMessage(msg) {
    const thread = document.getElementById('creatorMsgThread');
    const isAdmin = msg.sender_type === 'admin';

    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'display:flex;justify-content:' + (isAdmin ? 'flex-end' : 'flex-start') + ';';

    const bubble = document.createElement('div');
    bubble.style.cssText = isAdmin
        ? 'max-width:75%;padding:8px 13px;border-radius:16px 16px 4px 16px;font-size:.855rem;background:#1d6ae5;color:#fff;'
        : 'max-width:75%;padding:8px 13px;border-radius:16px 16px 16px 4px;font-size:.855rem;background:#252d3d;color:#e2e8f0;';

    const meta = document.createElement('div');
    meta.style.cssText = 'font-size:.68rem;opacity:.75;margin-bottom:2px;';
    meta.textContent = `${msg.sender_name} · ${msg.time}`;

    const text = document.createElement('div');
    text.textContent = msg.message;

    bubble.appendChild(meta);
    bubble.appendChild(text);
    wrapper.appendChild(bubble);
    thread.appendChild(wrapper);
    thread.scrollTop = thread.scrollHeight;
    if (msg.id) creatorLastMsgId = msg.id;
}

function sendCreatorMessage() {
    const input = document.getElementById('creatorMsgInput');
    const message = input.value.trim();
    if (!message || !creatorActivePurchaseId) return;

    const sendBtn = document.getElementById('creatorMsgSend');
    sendBtn.disabled = true;

    fetch(`/creator/purchases/${creatorActivePurchaseId}/messages`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ message })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            const emptyEl = document.getElementById('creatorMsgEmpty');
            if (emptyEl) emptyEl.remove();
            appendCreatorMessage(data.message);
        } else {
            alert(data.error || 'Error al enviar el mensaje.');
        }
    })
    .catch(() => alert('Error de red.'))
    .finally(() => { sendBtn.disabled = false; });
}

// Polling
const creatorMsgModal = document.getElementById('creatorMsgModal');
creatorMsgModal.addEventListener('shown.bs.modal', () => {
    creatorPollingInterval = setInterval(() => loadCreatorMessages(false), 5000);
});
creatorMsgModal.addEventListener('hidden.bs.modal', () => {
    clearInterval(creatorPollingInterval);
    creatorPollingInterval = null;
    creatorActivePurchaseId = null;
});

document.getElementById('creatorMsgInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendCreatorMessage();
    }
});
</script>
@endsection
