@extends('admin.layout')

@section('title', 'IPTV Admin')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tv me-2"></i>Gestión IPTV</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('iptv.channels') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-external-link-alt"></i> Ver JSON canales (slot 1)
            </a>
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-shopping-cart"></i> Compras
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- ================================================================ --}}
        {{-- COLUMN LEFT                                                       --}}
        {{-- ================================================================ --}}
        <div class="col-lg-6">

            {{-- --- Settings card --- --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-cog me-2"></i>Configuración general</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.iptv.settings') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre de la lista</label>
                                <input type="text" name="list_name" class="form-control"
                                    value="{{ old('list_name', $settings['list_name']) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre del grupo</label>
                                <input type="text" name="group_name" class="form-control"
                                    value="{{ old('group_name', $settings['group_name']) }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Campo <code>pl</code> (versión app)</label>
                                <input type="text" name="list_pl" class="form-control font-monospace"
                                    value="{{ old('list_pl', $settings['list_pl']) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Máx IPs/día</label>
                                <input type="number" name="max_ips_per_day" class="form-control"
                                    value="{{ old('max_ips_per_day', $settings['max_ips_per_day']) }}"
                                    min="1" max="1000" required>
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" type="submit">
                            <i class="fas fa-save me-1"></i>Guardar configuración
                        </button>
                    </form>
                </div>
            </div>

            {{-- --- CDN Slots card --- --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-server me-2"></i>Tokens CDN por slot</span>
                    <button id="btn-generate-tokens" class="btn btn-sm btn-success">
                        <i class="fas fa-bolt me-1"></i>Generar tokens (slots 2+)
                    </button>
                </div>

                {{-- Resultado de generación --}}
                <div id="generate-result" class="d-none border-bottom">
                    <div id="generate-output" class="p-3" style="font-family:monospace;font-size:.85rem;white-space:pre-wrap;"></div>
                </div>

                <div class="card-body p-0">

                    {{-- Slot 1 (siempre existe) --}}
                    @php
                        $slot1Cfg      = collect($cdnSlots)->firstWhere('slot', 1);
                        $slot1Max      = $slot1Cfg ? (int)($slot1Cfg['max_users'] ?? 10) : 10;
                        $slot1Url      = $slot1Cfg['token_url'] ?? null;
                        $slot1Blocked  = !empty($slot1Cfg['blocked']);
                    @endphp
                    <div class="p-3 border-bottom {{ $slot1Blocked ? 'bg-danger bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>Slot 1</strong>
                                @if($slot1Blocked)
                                    <span class="badge bg-danger ms-1">BLOQUEADO</span>
                                @endif
                                <span class="badge bg-secondary ms-2" data-slot-count="1" data-slot-max="{{ $slot1Max }}">{{ $slotCounts[1] ?? 0 }}/{{ $slot1Max }} activos</span>
                                <a href="{{ route('iptv.channels') }}" target="_blank" class="ms-2 small text-muted">
                                    <i class="fas fa-external-link-alt"></i> /iptv/channels
                                </a>
                            </div>
                            <div class="d-flex gap-1">
                                <form method="POST" action="{{ route('admin.iptv.refresh-token') }}">
                                    @csrf
                                    <button class="btn btn-sm btn-warning" type="submit">
                                        <i class="fas fa-sync-alt me-1"></i>Refresh
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.iptv.slot-toggle-block') }}"
                                      onsubmit="return confirm('{{ $slot1Blocked ? '¿Desbloquear slot 1?' : '¿Bloquear slot 1? Los suscriptores de este slot no podrán acceder.' }}')">
                                    @csrf
                                    <input type="hidden" name="slot" value="1">
                                    <button class="btn btn-sm {{ $slot1Blocked ? 'btn-success' : 'btn-danger' }}" type="submit">
                                        <i class="fas fa-{{ $slot1Blocked ? 'lock-open' : 'lock' }} me-1"></i>{{ $slot1Blocked ? 'Desbloquear' : 'Bloquear' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Token actual</label>
                            <input type="text" class="form-control form-control-sm font-monospace" readonly
                                value="{{ $settings['current_token'] ?: '(sin token)' }}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small mb-1">URL del token externo
                                @if($slot1Url)
                                    <code class="ms-1">{{ $slot1Url }}</code>
                                @else
                                    <span class="text-danger ms-1">⚠ No configurada — el Refresh fallará</span>
                                @endif
                            </label>
                            <p class="text-muted small mb-0">Usa el formulario «Añadir / editar slot» con N.º slot = <strong>1</strong> para configurar esta URL.</p>
                        </div>
                    </div>

                    {{-- Slots adicionales --}}
                    @foreach($cdnSlots as $slot)
                    @php
                        $slotNum     = (int)($slot['slot'] ?? 0);
                        $slotBlocked = !empty($slot['blocked']);
                    @endphp
                    @if($slotNum >= 2)
                    <div class="p-3 border-bottom {{ $slotBlocked ? 'bg-danger bg-opacity-10' : '' }}" data-slot-num="{{ $slotNum }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>Slot {{ $slotNum }}</strong>
                                @if($slotBlocked)
                                    <span class="badge bg-danger ms-1">BLOQUEADO</span>
                                @endif
                                <span class="badge bg-secondary ms-2" data-slot-count="{{ $slotNum }}" data-slot-max="{{ $slot['max_users'] ?? 10 }}">{{ $slotCounts[$slotNum] ?? 0 }}/{{ $slot['max_users'] ?? 10 }} activos</span>
                                <a href="{{ route('iptv.channels.slot', ['slot' => $slotNum]) }}" target="_blank" class="ms-2 small text-muted">
                                    <i class="fas fa-external-link-alt"></i> /iptv/channels/{{ $slotNum }}
                                </a>
                            </div>
                            <div class="d-flex gap-1">
                                <form method="POST" action="{{ route('admin.iptv.slot-refresh-token') }}">
                                    @csrf
                                    <input type="hidden" name="slot" value="{{ $slotNum }}">
                                    <button class="btn btn-sm btn-warning" type="submit">
                                        <i class="fas fa-sync-alt me-1"></i>Refresh
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.iptv.slot-toggle-block') }}"
                                      onsubmit="return confirm('{{ $slotBlocked ? '¿Desbloquear slot '.$slotNum.'?' : '¿Bloquear slot '.$slotNum.'? Los suscriptores de este slot no podrán acceder.' }}')">
                                    @csrf
                                    <input type="hidden" name="slot" value="{{ $slotNum }}">
                                    <button class="btn btn-sm {{ $slotBlocked ? 'btn-outline-success' : 'btn-outline-danger' }}" type="submit">
                                        <i class="fas fa-{{ $slotBlocked ? 'lock-open' : 'lock' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.iptv.slot-remove') }}" onsubmit="return confirm('¿Eliminar slot {{ $slotNum }}?')">
                                    @csrf
                                    <input type="hidden" name="slot" value="{{ $slotNum }}">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label small mb-1">Token actual</label>
                            <input type="text" class="form-control form-control-sm font-monospace" readonly
                                value="{{ $slot['current_token'] ?: '(sin token — haz Refresh)' }}">
                        </div>
                        <small class="text-muted">URL: <code>{{ $slot['token_url'] ?? '—' }}</code></small>
                    </div>
                    @endif
                    @endforeach

                    {{-- Añadir nuevo slot --}}
                    <div class="p-3">
                        <p class="fw-semibold small mb-2"><i class="fas fa-plus me-1"></i>Añadir / editar slot</p>
                        <form method="POST" action="{{ route('admin.iptv.slot-save') }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <label class="form-label small">N.º slot</label>
                                    <input type="number" name="slot" class="form-control form-control-sm" min="1" max="20" placeholder="2" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">URL del token externo</label>
                                    <input type="url" name="token_url" class="form-control form-control-sm" placeholder="https://example.com/token.json" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Máx usuarios</label>
                                    <input type="number" name="max_users" class="form-control form-control-sm" min="1" max="1000" value="10" required>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-primary btn-sm w-100" type="submit">
                                        <i class="fas fa-save me-1"></i>Guardar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            {{-- --- Buscar suscriptor por slot --- --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-search me-2"></i>Diagnóstico: buscar suscriptor</div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Introduce el <strong>@username</strong> de Telegram o el <strong>UUID</strong> de la compra para ver en qué slot está asignado.</p>
                    <div class="input-group mb-3">
                        <input type="text" id="lookup-input" class="form-control" placeholder="@username o UUID de compra">
                        <button id="btn-lookup" class="btn btn-outline-primary">
                            <i class="fas fa-search me-1"></i>Buscar
                        </button>
                    </div>
                    <div id="lookup-result" class="d-none">
                        {{-- filled by JS --}}
                    </div>
                </div>
            </div>

            {{-- --- IP ban management --- --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-ban me-2"></i>IPs baneadas</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.iptv.ban-ip') }}" class="d-flex gap-2 mb-3">
                        @csrf
                        <input type="text" name="ip" class="form-control" placeholder="192.168.1.1" required>
                        <button class="btn btn-danger" type="submit"><i class="fas fa-ban"></i> Banear</button>
                    </form>
                    @if(count($bannedIps) > 0)
                        <ul class="list-group">
                            @foreach($bannedIps as $bannedIp)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <code>{{ $bannedIp }}</code>
                                <form method="POST" action="{{ route('admin.iptv.unban-ip') }}">
                                    @csrf
                                    <input type="hidden" name="ip" value="{{ $bannedIp }}">
                                    <button class="btn btn-sm btn-outline-success" type="submit">
                                        <i class="fas fa-check"></i> Desbanear
                                    </button>
                                </form>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No hay IPs baneadas.</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- ================================================================ --}}
        {{-- COLUMN RIGHT                                                      --}}
        {{-- ================================================================ --}}
        <div class="col-lg-6">

            {{-- --- M3U upload / encrypt --- --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-upload me-2"></i>Subir M3U y cifrar canales</div>
                <div class="card-body">
                    <p class="text-muted small">
                        Pega aquí el M3U completo. Solo se importarán canales con URLs <code>.mpd</code>.
                        Los campos URL, keys, referer, origin y user-agent se cifrarán con AES+ChaCha20.
                        El token CDN se inyecta dinámicamente en cada slot — no se guarda en el JSON base.
                    </p>

                    <textarea id="m3u-input" class="form-control font-monospace mb-3"
                        rows="12" placeholder="#EXTM3U&#10;#EXTINF:-1 tvg-logo=&quot;...&quot;,Canal 1&#10;#KODIPROP:...&#10;https://stream.com/video.mpd"></textarea>

                    <div class="d-flex gap-2 mb-3">
                        <button id="btn-parse" class="btn btn-outline-secondary">
                            <i class="fas fa-search me-1"></i>Previsualizar
                        </button>
                        <button id="btn-save" class="btn btn-success" disabled>
                            <i class="fas fa-cloud-upload-alt me-1"></i>Cifrar y guardar
                        </button>
                    </div>

                    {{-- Preview result --}}
                    <div id="parse-result" class="d-none">
                        <div class="alert alert-info" id="parse-summary"></div>
                        <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Keys</th>
                                        <th>Headers</th>
                                    </tr>
                                </thead>
                                <tbody id="parse-table-body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Hidden form for actual save --}}
                    <form id="save-form" method="POST" action="{{ route('admin.iptv.save-channels') }}" class="d-none">
                        @csrf
                        <input type="hidden" name="m3u" id="save-m3u">
                    </form>
                </div>
            </div>

            {{-- --- Current channel list info --- --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-list me-2"></i>Lista actual — {{ count($channels) }} canales</span>
                    @if(count($channels) > 0)
                        <small class="text-muted">Última actualización: {{ \App\Models\Setting::get('iptv_channels_updated_at', 'nunca') }}</small>
                    @endif
                </div>
                @if(count($channels) > 0)
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Keys</th>
                                    <th class="text-muted small">Token (inyectado en runtime)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($channels as $i => $station)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td>{{ $station['name'] }}</td>
                                    <td>
                                        @if(!empty($station['qqs']['qs']))
                                            <span class="badge bg-info">{{ count($station['qqs']['qs']) }} key(s)</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted small"><i class="fas fa-exchange-alt me-1"></i>por slot</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="card-body text-muted">No hay canales guardados todavía. Sube un M3U arriba.</div>
                @endif
            </div>

            {{-- --- Access log --- --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history me-2"></i>Log de accesos (últimas {{ count($accessLog) }} entradas)</span>
                    <form method="POST" action="{{ route('admin.iptv.clear-log') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" type="submit">
                            <i class="fas fa-trash"></i> Limpiar
                        </button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:260px;overflow-y:auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>IP</th>
                                    <th>Timestamp</th>
                                    <th>User-Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accessLog as $entry)
                                <tr>
                                    <td>
                                        <code>{{ $entry['ip'] }}</code>
                                        @if(in_array($entry['ip'], $bannedIps))
                                            <span class="badge bg-danger ms-1">BANEADA</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $entry['ts'] }}</td>
                                    <td class="text-truncate" style="max-width:200px;">
                                        <small>{{ $entry['ua'] ?? '—' }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">Sin registros.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const btnParse  = document.getElementById('btn-parse');
const btnSave   = document.getElementById('btn-save');
const m3uInput  = document.getElementById('m3u-input');
const parseResult  = document.getElementById('parse-result');
const parseSummary = document.getElementById('parse-summary');
const parseBody    = document.getElementById('parse-table-body');
const saveForm     = document.getElementById('save-form');
const saveM3uInput = document.getElementById('save-m3u');

const M3U_STORAGE_KEY = 'iptv_admin_m3u';

// Restore from localStorage on page load
const savedM3u = localStorage.getItem(M3U_STORAGE_KEY);
if (savedM3u) m3uInput.value = savedM3u;

// Save to localStorage whenever content changes
m3uInput.addEventListener('input', () => {
    localStorage.setItem(M3U_STORAGE_KEY, m3uInput.value);
});

btnParse.addEventListener('click', async () => {
    const m3u = m3uInput.value.trim();
    if (!m3u) { alert('Pega el M3U primero.'); return; }

    btnParse.disabled = true;
    btnParse.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando…';

    try {
        const resp = await fetch('{{ route('admin.iptv.parse') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ m3u }),
        });
        const data = await resp.json();

        if (!resp.ok) {
            alert('Error al parsear: ' + (data.error ?? data.message ?? resp.status));
            return;
        }

        parseSummary.textContent = `Se encontraron ${data.count} canales MPD.`;
        parseBody.innerHTML = '';

        data.channels.forEach((ch, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td>${ch.name}</td>
                <td>${ch.keys.length > 0 ? ch.keys.length + ' key(s)' : '—'}</td>
                <td>${(ch.referer || ch.origin) ? '✓' : '—'}</td>
            `;
            parseBody.appendChild(tr);
        });

        parseResult.classList.remove('d-none');
        btnSave.disabled = data.count === 0;
    } catch (e) {
        alert('Error al parsear: ' + e.message);
    } finally {
        btnParse.disabled = false;
        btnParse.innerHTML = '<i class="fas fa-search me-1"></i>Previsualizar';
    }
});

btnSave.addEventListener('click', () => {
    if (!confirm(`¿Cifrar y guardar la lista? Esto reemplazará la lista actual.`)) return;
    saveM3uInput.value = m3uInput.value;
    saveForm.classList.remove('d-none');
    saveForm.submit();
});

// --- Helper: actualiza el badge de conteo de un slot ---
function updateSlotBadge(slotNum, delta) {
    const badge = document.querySelector(`[data-slot-count="${slotNum}"]`);
    if (!badge) return;

    const current = parseInt(badge.textContent) || 0;
    const newCount = Math.max(0, current + delta);

    if (slotNum === 1) {
        badge.textContent = `${newCount} activos`;
    } else {
        const max = badge.dataset.slotMax || '10';
        badge.textContent = `${newCount}/${max} activos`;
    }
}

// --- Diagnóstico: buscar suscriptor ---
const btnLookup    = document.getElementById('btn-lookup');
const lookupInput  = document.getElementById('lookup-input');
const lookupResult = document.getElementById('lookup-result');

async function doLookup() {
    const q = lookupInput.value.trim();
    if (!q) return;

    btnLookup.disabled = true;
    btnLookup.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    lookupResult.classList.remove('d-none');
    lookupResult.innerHTML = '<span class="text-muted small">Buscando…</span>';

    try {
        const url = new URL('{{ route('admin.iptv.lookup') }}');
        url.searchParams.set('query', q);
        const resp = await fetch(url);
        const data = await resp.json();

        if (!resp.ok) {
            lookupResult.innerHTML = `<div class="alert alert-warning py-2 mb-0"><i class="fas fa-exclamation-triangle me-1"></i>${data.error}</div>`;
        } else {
            const statusBadge = data.status === 'active'
                ? `<span class="badge bg-success">${data.status}</span>`
                : `<span class="badge bg-secondary">${data.status}</span>`;

            // Build slot options from the slots visible on the page
            const slotNumbers = [1, ...Array.from(document.querySelectorAll('[data-slot-num]')).map(el => parseInt(el.dataset.slotNum))];
            const slotOptions = slotNumbers.map(n =>
                `<option value="${n}" ${n === data.cdn_slot ? 'selected' : ''}>Slot ${n}</option>`
            ).join('');

            lookupResult.innerHTML = `
                <div class="alert alert-info py-2 mb-0" id="lookup-info-box">
                    <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                        <span class="badge bg-primary fs-6" id="current-slot-badge">Slot ${data.cdn_slot}</span>
                        ${statusBadge}
                        <strong>${data.telegram_username}</strong>
                        <span class="text-muted small">UUID: ${data.purchase_uuid}</span>
                    </div>
                    <div class="small mb-2">
                        <span class="me-3">⏳ Expira: <strong>${data.expires_at}</strong></span>
                        <span>👁 Último acceso: <strong>${data.last_viewed_at}</strong></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <label class="small mb-0">Mover a:</label>
                        <select id="move-slot-select" class="form-select form-select-sm" style="width:auto;">
                            ${slotOptions}
                        </select>
                        <button id="btn-move-slot" class="btn btn-sm btn-warning" data-uuid="${data.purchase_uuid}">
                            <i class="fas fa-exchange-alt me-1"></i>Mover
                        </button>
                        <span id="move-slot-msg" class="small"></span>
                    </div>
                </div>`;

            document.getElementById('btn-move-slot').addEventListener('click', async () => {
                const btn      = document.getElementById('btn-move-slot');
                const newSlot  = parseInt(document.getElementById('move-slot-select').value);
                const uuid     = btn.dataset.uuid;
                const msgEl    = document.getElementById('move-slot-msg');
                // Read the current slot from the badge before moving
                const oldSlot  = parseInt(document.getElementById('current-slot-badge').textContent.replace('Slot ', ''));

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                msgEl.textContent = '';

                try {
                    const r = await fetch('{{ route('admin.iptv.move-subscriber') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ purchase_uuid: uuid, cdn_slot: newSlot }),
                    });
                    const d = await r.json();

                    if (d.ok) {
                        document.getElementById('current-slot-badge').textContent = `Slot ${newSlot}`;
                        msgEl.className = 'small text-success';
                        msgEl.textContent = '✓ ' + d.message;
                        // Update slot count badges live
                        if (oldSlot !== newSlot) {
                            updateSlotBadge(oldSlot, -1);
                            updateSlotBadge(newSlot, +1);
                        }
                    } else {
                        msgEl.className = 'small text-danger';
                        msgEl.textContent = d.error ?? 'Error desconocido';
                    }
                } catch (e) {
                    msgEl.className = 'small text-danger';
                    msgEl.textContent = 'Error: ' + e.message;
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Mover';
                }
            });
        }
    } catch (e) {
        lookupResult.innerHTML = `<div class="alert alert-danger py-2 mb-0">Error: ${e.message}</div>`;
    } finally {
        btnLookup.disabled = false;
        btnLookup.innerHTML = '<i class="fas fa-search me-1"></i>Buscar';
    }
}

btnLookup.addEventListener('click', doLookup);
lookupInput.addEventListener('keydown', e => { if (e.key === 'Enter') doLookup(); });

// --- Generar tokens (slots 2+) ---
const btnGenerate    = document.getElementById('btn-generate-tokens');
const generateResult = document.getElementById('generate-result');
const generateOutput = document.getElementById('generate-output');

btnGenerate.addEventListener('click', async () => {
    btnGenerate.disabled = true;
    btnGenerate.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generando…';
    generateResult.classList.remove('d-none');
    generateOutput.textContent = 'Contactando servidor…';
    generateOutput.className = 'p-3 text-muted';

    try {
        const resp = await fetch('{{ route('admin.iptv.generate-tokens') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await resp.json();

        generateOutput.textContent = data.output || '(sin respuesta)';

        if (data.ok) {
            generateOutput.className = 'p-3 text-success';
        } else {
            generateOutput.className = 'p-3 text-danger';
        }
    } catch (e) {
        generateOutput.textContent = 'Error de red: ' + e.message;
        generateOutput.className = 'p-3 text-danger';
    } finally {
        btnGenerate.disabled = false;
        btnGenerate.innerHTML = '<i class="fas fa-bolt me-1"></i>Generar tokens (slots 2+)';
    }
});
</script>
@endsection
