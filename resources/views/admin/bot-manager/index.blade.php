@extends('layout')

@section('title', 'Bot Manager')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fas fa-robot me-2 text-primary"></i>Bot Manager</h2>
        <small class="text-muted">Gestión de grupos y canales de Telegram</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.bot-manager.broadcasts') }}" class="btn btn-outline-primary">
            <i class="fas fa-broadcast-tower me-1"></i>Broadcasts
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
            <i class="fas fa-plus me-1"></i>Añadir grupo
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@if($groups->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay grupos registrados</h5>
            <p class="text-muted small mb-3">
                Añade el bot como administrador en un grupo de Telegram y aparecerá aquí automáticamente,<br>
                o añádelo manualmente con el botón de arriba.
            </p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                <i class="fas fa-plus me-1"></i>Añadir grupo manualmente
            </button>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Grupo / Canal</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Comandos</th>
                            <th>Bans activos</th>
                            <th>Registrado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groups as $group)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $group->chat_title }}</div>
                                @if($group->username)
                                    <small class="text-muted">@{{ $group->username }}</small>
                                @endif
                                <div><small class="font-monospace text-muted">{{ $group->chat_id }}</small></div>
                            </td>
                            <td>
                                @if($group->chat_type === 'channel')
                                    <span class="badge text-bg-info">Canal</span>
                                @elseif($group->chat_type === 'supergroup')
                                    <span class="badge text-bg-secondary">Supergrupo</span>
                                @else
                                    <span class="badge text-bg-secondary">Grupo</span>
                                @endif
                            </td>
                            <td>
                                @if($group->is_active)
                                    <span class="badge text-bg-success"><i class="fas fa-circle me-1" style="font-size:.6rem;"></i>Activo</span>
                                @else
                                    <span class="badge text-bg-danger"><i class="fas fa-circle me-1" style="font-size:.6rem;"></i>Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-primary rounded-pill">{{ $group->commands_count }}</span>
                            </td>
                            <td>
                                @if($group->active_bans_count > 0)
                                    <span class="badge text-bg-danger rounded-pill">{{ $group->active_bans_count }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $group->registered_at?->format('d/m/Y') ?? '—' }}</small>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.bot-manager.show', $group) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-cog me-1"></i>Gestionar
                                </a>
                                <form method="POST" action="{{ route('admin.bot-manager.destroy', $group) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este grupo del panel? No afecta al grupo de Telegram.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- Instructions card --}}
<div class="card mt-4 border-info">
    <div class="card-body">
        <h6 class="text-info"><i class="fas fa-info-circle me-1"></i>¿Cómo añadir el bot a un grupo?</h6>
        <ol class="mb-0 small">
            <li>Abre el grupo en Telegram → <strong>Añadir miembro</strong> → busca tu bot</li>
            <li>Ve a <strong>Administradores</strong> → asigna al bot como admin</li>
            <li>Activa los permisos: <strong>Eliminar mensajes</strong> y <strong>Banear usuarios</strong></li>
            <li>El grupo aparecerá automáticamente en este panel en segundos</li>
        </ol>
    </div>
</div>

{{-- Modal: add group manually --}}
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Añadir grupo manualmente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.bot-manager.store') }}">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">
                        Si el bot ya es administrador del grupo pero no aparece automáticamente,
                        puedes añadirlo introduciendo el chat ID del grupo.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chat ID del grupo</label>
                        <input type="text" name="chat_id" class="form-control font-monospace"
                               placeholder="-1001234567890" required>
                        <div class="form-text">
                            Los IDs de grupos empiezan con <code>-100</code>.
                            Puedes obtenerlo reenviando un mensaje del grupo al bot
                            <a href="https://t.me/userinfobot" target="_blank">@userinfobot</a>.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Añadir</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
