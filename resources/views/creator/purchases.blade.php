@extends('layout')

@section('title', 'Compras de mi tienda')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Compras de mi tienda</h2>
    <a href="{{ route('creator.dashboard') }}" class="btn btn-outline-secondary">Volver al panel</a>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Fecha</th><th>Video</th><th>Usuario TG</th><th>Metodo</th><th>Referencia</th><th>Comprobante</th><th>Estado</th><th>Mensajes</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
                @php
                    $unread = $purchase->messages->where('sender_type', 'user')->whereNull('read_at')->count();
                @endphp
                <tr>
                    <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $purchase->video->title ?? 'N/A' }}</td>
                    <td>{{ '@' . $purchase->telegram_username }}</td>
                    <td>{{ $purchase->payment_method ?? 'N/A' }}</td>
                    <td>{{ $purchase->payment_reference ?? '-' }}</td>
                    <td>
                        @if($purchase->proof_url)
                            <a href="{{ $purchase->proof_url }}" target="_blank">Ver</a>
                        @else
                            -
                        @endif
                    </td>
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
                        <button class="btn btn-sm btn-outline-primary position-relative"
                                data-bs-toggle="modal"
                                data-bs-target="#creatorMsgModal"
                                data-purchase-id="{{ $purchase->id }}"
                                data-username="{{ '@' . $purchase->telegram_username }}"
                                data-has-tg="{{ $purchase->telegram_user_id ? '1' : '0' }}"
                                onclick="openCreatorMsgModal(this)">
                            💬
                            @if($unread > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $unread }}
                                </span>
                            @endif
                        </button>
                    </td>
                    <td>
                        @if($purchase->verification_status === 'pending')
                            <form method="POST" action="{{ route('creator.purchases.approve', $purchase) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success">Aprobar</button>
                            </form>
                            <form method="POST" action="{{ route('creator.purchases.reject', $purchase) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="delivery_notes" value="Pago rechazado por el creador">
                                <button class="btn btn-sm btn-outline-danger">Rechazar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No hay compras aun.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div>{{ $purchases->links() }}</div>

{{-- Modal de mensajería --}}
<div class="modal fade" id="creatorMsgModal" tabindex="-1" aria-labelledby="creatorMsgModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="creatorMsgModalLabel">💬 Mensajes con <span id="creatorMsgUsername"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Aviso sin Telegram --}}
                <div id="creatorMsgNoTg" class="alert alert-warning d-none">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    El comprador aún no ha vinculado su Telegram. Debe escribir <strong>/start</strong> al bot primero.
                </div>

                {{-- Hilo de mensajes --}}
                <div id="creatorMsgThread"
                     style="min-height:200px; max-height:360px; overflow-y:auto; display:flex; flex-direction:column; gap:8px; padding:10px; background:#1a1a2e; border-radius:10px;">
                    <p style="color:#6b6b8a; text-align:center; font-size:.82rem; margin:auto 0;" id="creatorMsgEmpty">Sin mensajes todavía.</p>
                </div>
            </div>
            <div class="modal-footer flex-column align-items-stretch gap-2">
                <textarea id="creatorMsgInput"
                          class="form-control"
                          rows="2"
                          style="resize:none; background:#1a1a2e; color:#e8e8f0; border-color:#3d3d5c;"
                          placeholder="Escribe un mensaje... (Enter envía, Shift+Enter nueva línea)"></textarea>
                <button id="creatorMsgSend" class="btn btn-primary w-100" onclick="sendCreatorMessage()">
                    <i class="fas fa-paper-plane me-1"></i>Enviar mensaje
                </button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
let creatorActivePurchaseId = null;
let creatorPollingInterval = null;
let creatorLastMsgTimestamp = null;

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

    // Clear thread
    const thread = document.getElementById('creatorMsgThread');
    thread.innerHTML = '<p class="text-muted text-center small mt-3" id="creatorMsgEmpty">Cargando...</p>';
    creatorLastMsgTimestamp = null;

    // Load messages
    loadCreatorMessages(true);
}

function loadCreatorMessages(initial = false) {
    if (!creatorActivePurchaseId) return;

    let url = `/creator/purchases/${creatorActivePurchaseId}/messages`;
    if (!initial && creatorLastMsgTimestamp) {
        url += `?after=${encodeURIComponent(creatorLastMsgTimestamp)}`;
    }

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const msgs = data.messages || [];
        if (msgs.length > 0) {
            const emptyEl = document.getElementById('creatorMsgEmpty');
            if (emptyEl) emptyEl.remove();

            msgs.forEach(msg => appendCreatorMessage(msg));
            creatorLastMsgTimestamp = msgs[msgs.length - 1].created_at;
        } else if (initial) {
            const thread = document.getElementById('creatorMsgThread');
            thread.innerHTML = '<p class="text-muted text-center small mt-3" id="creatorMsgEmpty">Sin mensajes todavía.</p>';
        }
    })
    .catch(() => {});
}

function appendCreatorMessage(msg) {
    const thread = document.getElementById('creatorMsgThread');
    const isAdmin = msg.sender_type === 'admin';

    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'display:flex; justify-content:' + (isAdmin ? 'flex-end' : 'flex-start') + ';';

    const bubble = document.createElement('div');
    bubble.style.cssText = isAdmin
        ? 'max-width:75%;padding:8px 13px;border-radius:16px 16px 4px 16px;font-size:.855rem;background:#1d6ae5;color:#fff;'
        : 'max-width:75%;padding:8px 13px;border-radius:16px 16px 16px 4px;font-size:.855rem;background:#2d2d42;color:#e8e8f0;';

    const meta = document.createElement('div');
    meta.style.cssText = isAdmin
        ? 'font-size:.68rem;opacity:.75;margin-bottom:2px;'
        : 'font-size:.68rem;color:#9d9db8;margin-bottom:2px;';
    meta.textContent = `${msg.sender_name} · ${msg.time}`;

    const text = document.createElement('div');
    text.textContent = msg.message;

    bubble.appendChild(meta);
    bubble.appendChild(text);
    wrapper.appendChild(bubble);
    thread.appendChild(wrapper);
    thread.scrollTop = thread.scrollHeight;
    if (msg.created_at) creatorLastMsgTimestamp = msg.created_at;
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
            creatorLastMsgTimestamp = data.message.created_at;
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

// Enter to send
document.getElementById('creatorMsgInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendCreatorMessage();
    }
});
</script>
@endsection
@endsection
