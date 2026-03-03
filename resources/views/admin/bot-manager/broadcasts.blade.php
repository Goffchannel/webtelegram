@extends('admin.layout')

@section('title', 'Bot Manager — Broadcasts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fas fa-broadcast-tower me-2 text-primary"></i>Broadcasts</h2>
        <small class="text-muted">Envía vídeos/fotos guardados a grupos y canales</small>
    </div>
    <a href="{{ route('admin.bot-manager.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

{{-- Instructions --}}
<div class="alert alert-info d-flex gap-2 mb-4">
    <i class="fas fa-info-circle mt-1"></i>
    <div>
        Para guardar un vídeo como broadcast, envíaselo al bot con la caption que empiece por
        <code>#broadcast</code> o <code>!broadcast</code>.<br>
        Ejemplo: <code>#broadcast ¡Oferta especial de hoy!</code>
    </div>
</div>

@if($broadcasts->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-broadcast-tower fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay broadcasts guardados</h5>
            <p class="text-muted small">Envía un vídeo o foto al bot con el caption <code>#broadcast Tu mensaje</code> y aparecerá aquí.</p>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($broadcasts as $broadcast)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span>
                        <i class="fas {{ $broadcast->fileTypeIcon() }} me-1 text-primary"></i>
                        {{ ucfirst($broadcast->file_type) }}
                    </span>
                    @php
                        $badgeClass = match($broadcast->status) {
                            'done'    => 'text-bg-success',
                            'sending' => 'text-bg-warning',
                            'failed'  => 'text-bg-danger',
                            default   => 'text-bg-secondary',
                        };
                        $badgeLabel = match($broadcast->status) {
                            'done'    => 'Enviado',
                            'sending' => 'Enviando…',
                            'failed'  => 'Error',
                            default   => 'Pendiente',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
                <div class="card-body">
                    @if($broadcast->caption)
                        <p class="mb-2"><strong>Caption:</strong><br>{{ $broadcast->caption }}</p>
                    @else
                        <p class="text-muted small mb-2"><em>Sin caption</em></p>
                    @endif

                    <div class="small text-muted">
                        <i class="fas fa-clock me-1"></i>Guardado: {{ $broadcast->created_at->format('d/m/Y H:i') }}
                        @if($broadcast->scheduled_at)
                            <br><i class="fas fa-calendar me-1"></i>Programado: {{ $broadcast->scheduled_at->format('d/m/Y H:i') }}
                        @endif
                        @if($broadcast->sent_at)
                            <br><i class="fas fa-paper-plane me-1"></i>Enviado: {{ $broadcast->sent_at->format('d/m/Y H:i') }}
                        @endif
                        @if($broadcast->targets_count > 0)
                            <br><i class="fas fa-users me-1"></i>Grupos: {{ $broadcast->targets_count }}
                        @endif
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    @if($groups->isNotEmpty())
                        <button class="btn btn-sm btn-primary flex-fill"
                                onclick="openSendModal({{ $broadcast->id }}, '{{ addslashes($broadcast->caption ?? '') }}')">
                            <i class="fas fa-paper-plane me-1"></i>Enviar ahora
                        </button>
                        <button class="btn btn-sm btn-outline-primary flex-fill"
                                onclick="openScheduleModal({{ $broadcast->id }}, '{{ addslashes($broadcast->caption ?? '') }}')">
                            <i class="fas fa-clock me-1"></i>Programar
                        </button>
                    @else
                        <span class="text-muted small">No hay grupos activos</span>
                    @endif
                    <form method="POST"
                          action="{{ route('admin.bot-manager.broadcasts.destroy', $broadcast) }}"
                          onsubmit="return confirm('¿Eliminar este broadcast?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- Modal: Send now --}}
<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Enviar ahora</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="sendForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="sendCaption"></p>
                    <label class="form-label fw-semibold">Selecciona los grupos destino</label>
                    <div class="border rounded p-2" style="max-height:220px;overflow-y:auto;">
                        @foreach($groups as $group)
                        <div class="form-check">
                            <input class="form-check-input send-group-check" type="checkbox"
                                   name="group_ids[]" value="{{ $group->id }}"
                                   id="sg{{ $group->id }}">
                            <label class="form-check-label" for="sg{{ $group->id }}">
                                {{ $group->chat_title }}
                                @if($group->chat_type === 'channel')
                                    <span class="badge text-bg-info ms-1" style="font-size:.7rem">Canal</span>
                                @endif
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="selectAllSend">
                        <label class="form-check-label text-muted small" for="selectAllSend">Seleccionar todos</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Schedule --}}
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clock me-2"></i>Programar envío</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="schedCaption"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha y hora de envío</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" required
                               min="{{ now()->addMinutes(2)->format('Y-m-d\TH:i') }}">
                    </div>
                    <label class="form-label fw-semibold">Selecciona los grupos destino</label>
                    <div class="border rounded p-2" style="max-height:200px;overflow-y:auto;">
                        @foreach($groups as $group)
                        <div class="form-check">
                            <input class="form-check-input sched-group-check" type="checkbox"
                                   name="group_ids[]" value="{{ $group->id }}"
                                   id="schg{{ $group->id }}">
                            <label class="form-check-label" for="schg{{ $group->id }}">
                                {{ $group->chat_title }}
                                @if($group->chat_type === 'channel')
                                    <span class="badge text-bg-info ms-1" style="font-size:.7rem">Canal</span>
                                @endif
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="selectAllSched">
                        <label class="form-check-label text-muted small" for="selectAllSched">Seleccionar todos</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-clock me-1"></i>Programar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openSendModal(broadcastId, caption) {
    const form = document.getElementById('sendForm');
    form.action = `/admin/bot-manager/broadcasts/${broadcastId}/send`;
    document.getElementById('sendCaption').textContent = caption ? `Caption: "${caption}"` : 'Sin caption';
    document.querySelectorAll('.send-group-check').forEach(c => c.checked = false);
    new bootstrap.Modal(document.getElementById('sendModal')).show();
}

function openScheduleModal(broadcastId, caption) {
    const form = document.getElementById('scheduleForm');
    form.action = `/admin/bot-manager/broadcasts/${broadcastId}/schedule`;
    document.getElementById('schedCaption').textContent = caption ? `Caption: "${caption}"` : 'Sin caption';
    document.querySelectorAll('.sched-group-check').forEach(c => c.checked = false);
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}

document.getElementById('selectAllSend').addEventListener('change', function () {
    document.querySelectorAll('.send-group-check').forEach(c => c.checked = this.checked);
});
document.getElementById('selectAllSched').addEventListener('change', function () {
    document.querySelectorAll('.sched-group-check').forEach(c => c.checked = this.checked);
});
</script>
@endsection
