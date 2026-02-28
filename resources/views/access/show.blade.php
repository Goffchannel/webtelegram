@extends('layout')

@section('title', 'Acceso de servicio')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <strong>Acceso activo</strong>
                    <span class="badge text-bg-success">Vence: {{ $access->expires_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="card-body">
                    <h4>{{ $access->video->title }}</h4>
                    @if($access->video->fan_message)
                        <div class="alert alert-info" style="white-space: pre-wrap;">{{ $access->video->fan_message }}</div>
                    @endif

                    @if($access->line && $access->line->is_shared)
                        {{-- ============================================================ --}}
                        {{-- IPTV shared line: give the subscriber their unique proxy URL --}}
                        {{-- ============================================================ --}}
                        <div class="alert alert-success">
                            <i class="fas fa-tv me-2"></i>
                            <strong>Tu lista IPTV está activa.</strong>
                            Copia el enlace de abajo y pégalo en la app <strong>Plooplayer</strong>.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Enlace de acceso (Plooplayer)</label>
                            <div class="input-group">
                                <input id="iptv-link" class="form-control font-monospace"
                                    value="{{ route('iptv.playlist', $access->access_token) }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyIptvLink()">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                            </div>
                            <div class="form-text text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                No compartas este enlace. Es único para tu suscripción y caduca el
                                <strong>{{ $access->expires_at->format('d/m/Y H:i') }}</strong>.
                            </div>
                        </div>

                        <div class="mt-3">
                            <h6>¿Cómo usarlo?</h6>
                            <ol class="mb-0">
                                <li>Abre la app <strong>Plooplayer</strong> en tu dispositivo.</li>
                                <li>Ve a <em>Añadir lista</em> o <em>Add playlist</em>.</li>
                                <li>Pega la URL copiada y guarda.</li>
                                <li>Los canales se cargarán automáticamente.</li>
                            </ol>
                        </div>

                    @else
                        {{-- ============================================================ --}}
                        {{-- Classic inventory line: show credentials as before           --}}
                        {{-- ============================================================ --}}
                        @if($access->line)
                        <div class="mb-3">
                            <label class="form-label">Nombre de linea</label>
                            <input class="form-control" value="{{ $access->line->line_name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL M3U</label>
                            <input class="form-control" value="{{ $access->line->m3u_url }}" readonly>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Usuario</label>
                                <input class="form-control" value="{{ $access->line->line_username }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contraseña</label>
                                <input class="form-control" value="{{ $access->line->line_password }}" readonly>
                            </div>
                        </div>

                        @if($access->line->notes)
                            <div class="mt-3">
                                <label class="form-label">Notas</label>
                                <textarea class="form-control" rows="3" readonly>{{ $access->line->notes }}</textarea>
                            </div>
                        @endif
                        @endif
                    @endif

                    @if($access->video->access_instructions)
                        <div class="mt-3 alert alert-secondary" style="white-space: pre-wrap;">
                            <strong>Instrucciones:</strong><br>
                            {{ $access->video->access_instructions }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@section('scripts')
<script>
function copyIptvLink() {
    const input = document.getElementById('iptv-link');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
        btn.classList.replace('btn-outline-secondary', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy"></i> Copiar';
            btn.classList.replace('btn-success', 'btn-outline-secondary');
        }, 2000);
    });
}
</script>
@endsection

@endsection
