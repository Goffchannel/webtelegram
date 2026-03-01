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

                        {{-- Step 1: Copy link --}}
                        <div class="card border-success mb-3">
                            <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                                <span class="badge bg-white text-success fw-bold" style="font-size:.9rem;">1</span>
                                <strong>Copia tu enlace personal</strong>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-2">Este enlace es único para ti. No lo compartas.</p>
                                <div class="input-group input-group-lg">
                                    <input id="iptv-link" class="form-control font-monospace bg-dark text-success border-success"
                                        style="font-size:.8rem; letter-spacing:.03em;"
                                        value="{{ route('iptv.playlist', $access->access_token) }}" readonly>
                                    <button class="btn btn-success fw-bold" type="button" onclick="copyIptvLink()" id="copy-btn">
                                        <i class="fas fa-copy me-1"></i> Copiar
                                    </button>
                                </div>
                                <div class="mt-2 d-flex gap-3" style="font-size:.8rem;">
                                    <span class="text-muted"><i class="fas fa-calendar-alt me-1"></i>Caduca: <strong>{{ $access->expires_at->format('d/m/Y H:i') }}</strong></span>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Open Plooplayer --}}
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
                                <span class="badge bg-white text-primary fw-bold" style="font-size:.9rem;">2</span>
                                <strong>Abre Plooplayer y añade la lista</strong>
                            </div>
                            <div class="card-body">
                                <ol class="mb-0 ps-3" style="line-height:2;">
                                    <li>Abre la app <strong>Plooplayer</strong> en tu dispositivo.</li>
                                    <li>Ve a <strong>Añadir lista</strong> / <em>Add playlist</em>.</li>
                                    <li>Pega la URL que copiaste y guarda.</li>
                                    <li>Los canales se cargarán automáticamente. ✅</li>
                                </ol>
                            </div>
                        </div>

                        <div class="alert alert-warning py-2 mb-0" style="font-size:.82rem;">
                            <i class="fas fa-lock me-1"></i>
                            <strong>Enlace privado</strong> — No lo compartas. Si detectamos acceso desde varios dispositivos, el acceso quedará bloqueado.
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
    const btn   = document.getElementById('copy-btn');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        btn.innerHTML = '<i class="fas fa-check me-1"></i> ¡Copiado!';
        btn.classList.replace('btn-success', 'btn-outline-success');
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy me-1"></i> Copiar';
            btn.classList.replace('btn-outline-success', 'btn-success');
        }, 2500);
    });
}
</script>
@endsection

@endsection
