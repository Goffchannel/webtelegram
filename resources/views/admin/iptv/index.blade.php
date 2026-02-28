@extends('layout')

@section('title', 'IPTV Admin')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tv me-2"></i>Gestión IPTV</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('iptv.channels') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-external-link-alt"></i> Ver JSON canales
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

            {{-- --- Token card --- --}}
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-key me-2"></i>Token CDN (x-tcdn-token)</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Token actual</label>
                        <input type="text" class="form-control font-monospace" readonly
                            value="{{ $settings['current_token'] ?: '(sin token)' }}">
                    </div>
                    <form method="POST" action="{{ route('admin.iptv.refresh-token') }}">
                        @csrf
                        <button class="btn btn-warning" type="submit">
                            <i class="fas fa-sync-alt me-1"></i>Refresh Token
                        </button>
                        <span class="text-muted ms-2 small">Obtiene el token actual del servidor externo e inyecta en todos los canales.</span>
                    </form>
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
                        El token CDN se inyectará en claro en <code>headers.x-tcdn-token</code>.
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
                                    <th>CDN Token</th>
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
                                        <code class="small text-truncate d-inline-block" style="max-width:140px;">
                                            {{ $station['headers']['x-tcdn-token'] ?? '—' }}
                                        </code>
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
</script>
@endsection
