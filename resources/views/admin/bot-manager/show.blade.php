@extends('admin.layout')

@section('title', 'Bot Manager — ' . $group->chat_title)

@section('content')
{{-- Header --}}
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <a href="{{ route('admin.bot-manager.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
        <h2 class="mb-0">
            @if($group->chat_type === 'channel')
                <i class="fas fa-broadcast-tower me-2 text-info"></i>
            @else
                <i class="fas fa-users me-2 text-primary"></i>
            @endif
            {{ $group->chat_title }}
        </h2>
        <div class="d-flex gap-2 mt-1 align-items-center">
            @if($group->chat_type === 'channel')
                <span class="badge text-bg-info">Canal</span>
            @elseif($group->chat_type === 'supergroup')
                <span class="badge text-bg-secondary">Supergrupo</span>
            @else
                <span class="badge text-bg-secondary">Grupo</span>
            @endif
            @if($group->is_active)
                <span class="badge text-bg-success">Activo</span>
            @else
                <span class="badge text-bg-danger">Inactivo</span>
            @endif
            <small class="text-muted font-monospace">{{ $group->chat_id }}</small>
            @if($group->username)
                <small class="text-muted">@{{ $group->username }}</small>
            @endif
        </div>
    </div>
</div>


{{-- Tabs --}}
<ul class="nav nav-tabs mb-4" id="groupTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-config">
            <i class="fas fa-sliders-h me-1"></i>Configuración
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-commands">
            <i class="fas fa-terminal me-1"></i>Comandos
            <span class="badge text-bg-primary ms-1">{{ $group->commands->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-bans">
            <i class="fas fa-ban me-1"></i>Bans
            @if($group->activeBans->count() > 0)
                <span class="badge text-bg-danger ms-1">{{ $group->activeBans->count() }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-broadcast">
            <i class="fas fa-bullhorn me-1"></i>Broadcast
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-warnings">
            <i class="fas fa-exclamation-triangle me-1"></i>Avisos
            @php $warnCount = \App\Models\BotGroupWarning::where('bot_group_id', $group->id)->count(); @endphp
            @if($warnCount > 0)
                <span class="badge text-bg-warning ms-1">{{ $warnCount }}</span>
            @endif
        </a>
    </li>
</ul>

<div class="tab-content">

    {{-- ─── Tab: Configuración ────────────────────────────────────────────── --}}
    <div class="tab-pane fade show active" id="tab-config">
        <form method="POST" action="{{ route('admin.bot-manager.update', $group) }}" id="configForm">
            @csrf @method('PUT')
            <div class="row g-4">

                {{-- Estado --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Estado del grupo</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="is_active" value="1" {{ $group->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Grupo activo — el bot responde comandos y modera mensajes
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Moderación de links --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-link me-1 text-warning"></i>Eliminar links automáticamente</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="auto_delete_links"
                                       id="auto_delete_links" value="1"
                                       {{ $group->getSetting('auto_delete_links') ? 'checked' : '' }}
                                       onchange="toggleLinkAction(this.checked)">
                                <label class="form-check-label" for="auto_delete_links">
                                    Eliminar mensajes con URLs
                                </label>
                            </div>
                            <div id="linkActionBlock" style="{{ $group->getSetting('auto_delete_links') ? '' : 'display:none' }}">
                                <label class="form-label small fw-semibold">Acción al detectar un link</label>
                                <select name="delete_link_action" class="form-select form-select-sm">
                                    <option value="delete_only" {{ $group->getSetting('delete_link_action') === 'delete_only' ? 'selected' : '' }}>
                                        Solo eliminar el mensaje
                                    </option>
                                    <option value="delete_and_warn" {{ $group->getSetting('delete_link_action') === 'delete_and_warn' ? 'selected' : '' }}>
                                        Eliminar + avisar al usuario
                                    </option>
                                    <option value="delete_and_ban" {{ $group->getSetting('delete_link_action') === 'delete_and_ban' ? 'selected' : '' }}>
                                        Eliminar + banear al usuario
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bienvenida --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-hand-wave me-1 text-success"></i>Mensaje de bienvenida</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="welcome_enabled"
                                       id="welcome_enabled" value="1"
                                       {{ $group->getSetting('welcome_enabled') ? 'checked' : '' }}
                                       onchange="toggleWelcome(this.checked)">
                                <label class="form-check-label" for="welcome_enabled">
                                    Enviar bienvenida a nuevos miembros
                                </label>
                            </div>
                            <div id="welcomeBlock" style="{{ $group->getSetting('welcome_enabled') ? '' : 'display:none' }}">
                                <label class="form-label small fw-semibold">Mensaje (Markdown soportado)</label>
                                <textarea name="welcome_message" class="form-control form-control-sm" rows="3"
                                          placeholder="¡Bienvenido/a {nombre} al grupo {grupo}! 👋">{{ $group->getSetting('welcome_message') }}</textarea>
                                <div class="form-text">Variables: <code>{nombre}</code> y <code>{grupo}</code></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modo noche --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-moon me-1 text-primary"></i>Modo noche</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="night_mode_enabled"
                                       id="night_mode_enabled" value="1"
                                       {{ $group->getSetting('night_mode_enabled') ? 'checked' : '' }}
                                       onchange="toggleNightMode(this.checked)">
                                <label class="form-check-label" for="night_mode_enabled">
                                    Deshabilitar mensajes en el horario indicado
                                </label>
                            </div>
                            <div id="nightModeBlock" style="{{ $group->getSetting('night_mode_enabled') ? '' : 'display:none' }}">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Silencio desde</label>
                                        <input type="time" name="night_mode_start" class="form-control form-control-sm"
                                               value="{{ $group->getSetting('night_mode_start', '23:00') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Reabrir a las</label>
                                        <input type="time" name="night_mode_end" class="form-control form-control-sm"
                                               value="{{ $group->getSetting('night_mode_end', '08:00') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Zona horaria</label>
                                        <select name="night_mode_timezone" class="form-select form-select-sm">
                                            @foreach([
                                                'Europe/Madrid'    => 'España (Europe/Madrid)',
                                                'Europe/London'    => 'Reino Unido (Europe/London)',
                                                'Europe/Paris'     => 'Francia / CET (Europe/Paris)',
                                                'Europe/Berlin'    => 'Alemania (Europe/Berlin)',
                                                'America/New_York' => 'Este EE.UU. (America/New_York)',
                                                'America/Chicago'  => 'Centro EE.UU. (America/Chicago)',
                                                'America/Denver'   => 'Montaña EE.UU. (America/Denver)',
                                                'America/Los_Angeles' => 'Pacífico EE.UU. (America/Los_Angeles)',
                                                'America/Mexico_City' => 'México (America/Mexico_City)',
                                                'America/Bogota'   => 'Colombia (America/Bogota)',
                                                'America/Argentina/Buenos_Aires' => 'Argentina (America/Argentina/Buenos_Aires)',
                                                'UTC'              => 'UTC',
                                            ] as $tz => $label)
                                                <option value="{{ $tz }}" {{ $group->getSetting('night_mode_timezone', 'Europe/Madrid') === $tz ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-text mt-2">
                                    @if($group->getSetting('night_mode_active'))
                                        <span class="badge text-bg-primary"><i class="fas fa-moon me-1"></i>Modo noche activo ahora</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Blacklist --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-ban me-1 text-danger"></i>Palabras prohibidas</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="blacklist_enabled"
                                       id="blacklist_enabled" value="1"
                                       {{ $group->getSetting('blacklist_enabled') ? 'checked' : '' }}
                                       onchange="toggleBlock('blacklistBlock', this.checked)">
                                <label class="form-check-label" for="blacklist_enabled">Eliminar mensajes con palabras prohibidas</label>
                            </div>
                            <div id="blacklistBlock" style="{{ $group->getSetting('blacklist_enabled') ? '' : 'display:none' }}">
                                <label class="form-label small fw-semibold">Palabras (una por línea)</label>
                                <textarea name="blacklist_words_raw" class="form-control form-control-sm font-monospace" rows="4"
                                          placeholder="spam&#10;casino&#10;oferta gratis">{{ implode("\n", $group->getSetting('blacklist_words', [])) }}</textarea>
                                <label class="form-label small fw-semibold mt-2">Acción</label>
                                <select name="blacklist_action" class="form-select form-select-sm">
                                    <option value="delete_only" {{ $group->getSetting('blacklist_action') === 'delete_only' ? 'selected' : '' }}>Solo eliminar</option>
                                    <option value="delete_and_warn" {{ $group->getSetting('blacklist_action') === 'delete_and_warn' ? 'selected' : '' }}>Eliminar + avisar</option>
                                    <option value="delete_and_ban" {{ $group->getSetting('blacklist_action') === 'delete_and_ban' ? 'selected' : '' }}>Eliminar + banear</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Anti-flood --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-tachometer-alt me-1 text-danger"></i>Anti-flood</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="antiflood_enabled"
                                       id="antiflood_enabled" value="1"
                                       {{ $group->getSetting('antiflood_enabled') ? 'checked' : '' }}
                                       onchange="toggleBlock('antifloodBlock', this.checked)">
                                <label class="form-check-label" for="antiflood_enabled">Limitar mensajes por usuario</label>
                            </div>
                            <div id="antifloodBlock" style="{{ $group->getSetting('antiflood_enabled') ? '' : 'display:none' }}">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Máx. mensajes</label>
                                        <input type="number" name="antiflood_max_messages" class="form-control form-control-sm"
                                               min="2" max="100" value="{{ $group->getSetting('antiflood_max_messages', 5) }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">En segundos</label>
                                        <input type="number" name="antiflood_seconds" class="form-control form-control-sm"
                                               min="5" max="300" value="{{ $group->getSetting('antiflood_seconds', 10) }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Acción</label>
                                        <select name="antiflood_action" class="form-select form-select-sm"
                                                onchange="toggleMuteDuration(this.value)">
                                            <option value="delete" {{ $group->getSetting('antiflood_action') === 'delete' ? 'selected' : '' }}>Solo eliminar mensaje</option>
                                            <option value="mute"   {{ $group->getSetting('antiflood_action', 'mute') === 'mute' ? 'selected' : '' }}>Silenciar</option>
                                            <option value="ban"    {{ $group->getSetting('antiflood_action') === 'ban' ? 'selected' : '' }}>Banear</option>
                                        </select>
                                    </div>
                                    <div class="col-12" id="muteDurationBlock"
                                         style="{{ $group->getSetting('antiflood_action', 'mute') === 'mute' ? '' : 'display:none' }}">
                                        <label class="form-label small fw-semibold">Duración del silencio</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="antiflood_mute_duration"
                                                   class="form-control form-control-sm"
                                                   min="1" max="10080"
                                                   value="{{ $group->getSetting('antiflood_mute_duration', 5) }}">
                                            <span class="input-group-text">min</span>
                                        </div>
                                        <div class="form-text">Ej: 5 min, 60 = 1 h, 1440 = 1 día</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Warnings before ban --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-exclamation-triangle me-1 text-warning"></i>Avisos antes de banear</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="warn_before_ban"
                                       id="warn_before_ban" value="1"
                                       {{ $group->getSetting('warn_before_ban') ? 'checked' : '' }}
                                       onchange="toggleBlock('warnBlock', this.checked)">
                                <label class="form-check-label" for="warn_before_ban">
                                    Acumular avisos antes de banear
                                </label>
                            </div>
                            <div id="warnBlock" style="{{ $group->getSetting('warn_before_ban') ? '' : 'display:none' }}">
                                <label class="form-label small fw-semibold">Número de avisos para banear</label>
                                <input type="number" name="max_warnings" class="form-control form-control-sm"
                                       min="1" max="10" value="{{ $group->getSetting('max_warnings', 3) }}">
                                <div class="form-text">Aplica cuando la acción es "Eliminar + banear" en links o blacklist.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Guardar configuración
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ─── Tab: Comandos ──────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="tab-commands">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Comandos personalizados</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCmdModal">
                <i class="fas fa-plus me-1"></i>Añadir comando
            </button>
        </div>
        <p class="text-muted small">
            Cuando un usuario escriba el trigger exacto en el grupo, el bot responderá con el texto configurado.
            Soporta prefijos <code>/cmd</code>, <code>!cmd</code> o texto plano. Case-insensitive.
        </p>

        @if($group->commands->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="fas fa-terminal fa-2x mb-2"></i><br>
                No hay comandos configurados. Añade uno para empezar.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr><th>Trigger</th><th>Respuesta</th><th>Estado</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($group->commands as $cmd)
                        <tr>
                            <td><code>{{ $cmd->trigger }}</code></td>
                            <td>
                                <div style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $cmd->response }}
                                </div>
                            </td>
                            <td>
                                @if($cmd->is_active)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-xs btn-outline-secondary"
                                        onclick="editCommand({{ $cmd->id }}, '{{ addslashes($cmd->trigger) }}', {{ json_encode($cmd->response) }}, {{ $cmd->is_active ? 'true' : 'false' }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('admin.bot-manager.commands.destroy', [$group, $cmd]) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este comando?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ─── Tab: Bans ──────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="tab-bans">
        <div class="row g-4">
            {{-- Ban form --}}
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-ban me-1 text-danger"></i>Banear usuario</h6>
                        <form method="POST" action="{{ route('admin.bot-manager.ban', $group) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">ID de usuario Telegram</label>
                                <input type="text" name="telegram_user_id" class="form-control form-control-sm"
                                       placeholder="123456789" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Username (opcional)</label>
                                <input type="text" name="telegram_username" class="form-control form-control-sm"
                                       placeholder="@usuario">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Motivo (opcional)</label>
                                <input type="text" name="reason" class="form-control form-control-sm"
                                       placeholder="Spam, publicidad...">
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-ban me-1"></i>Banear
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Active bans list --}}
            <div class="col-md-8">
                <h6>Bans activos ({{ $group->activeBans->count() }})</h6>
                @if($group->activeBans->isEmpty())
                    <p class="text-muted small">No hay usuarios baneados actualmente.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr><th>Usuario</th><th>Motivo</th><th>Baneado por</th><th>Fecha</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach($group->activeBans as $ban)
                                <tr>
                                    <td>
                                        <div class="font-monospace small">{{ $ban->telegram_user_id }}</div>
                                        @if($ban->telegram_username)
                                            <small class="text-muted">@{{ $ban->telegram_username }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ $ban->reason ?? '—' }}</small></td>
                                    <td><small>{{ $ban->bannedBy?->name ?? 'Sistema' }}</small></td>
                                    <td><small>{{ $ban->banned_at->format('d/m/Y H:i') }}</small></td>
                                    <td>
                                        <form method="POST"
                                              action="{{ route('admin.bot-manager.unban', [$group, $ban]) }}"
                                              onsubmit="return confirm('¿Desbanear a este usuario?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-outline-success">
                                                <i class="fas fa-user-check me-1"></i>Desbanear
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Tab: Broadcast ─────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="tab-broadcast">

        {{-- Text broadcast --}}
        <div class="row mb-4">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-bullhorn me-1 text-warning"></i>Enviar mensaje de texto</h6>
                        <p class="text-muted small">El bot enviará este mensaje directamente al grupo. Soporta Markdown.</p>
                        <form method="POST" action="{{ route('admin.bot-manager.message', $group) }}">
                            @csrf
                            <div class="mb-3">
                                <textarea name="message" class="form-control" rows="4"
                                          placeholder="Escribe el mensaje... (Markdown soportado)"
                                          required maxlength="4096"></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-paper-plane me-1"></i>Enviar al grupo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Media broadcasts --}}
        <h6 class="mb-3"><i class="fas fa-photo-video me-1 text-primary"></i>Vídeos / Fotos guardados</h6>

        <div class="alert alert-info d-flex gap-2 py-2 mb-3">
            <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
            <small>Envía un vídeo o foto al bot con caption <code>#broadcast Tu texto</code> para guardarlo aquí y poder enviarlo a este grupo con un clic.</small>
        </div>

        @if($broadcasts->isEmpty())
            <p class="text-muted small">No hay broadcasts guardados. Envía un vídeo al bot con <code>#broadcast</code> para que aparezca aquí.</p>
        @else
            <div class="row g-3">
                @foreach($broadcasts as $bc)
                @php
                    $groupTarget = $bc->targets->where('bot_group_id', $group->id)->first();
                    // Badge reflects THIS group's target status, not the global broadcast status
                    if ($groupTarget) {
                        $bc_badge = match($groupTarget->status) {
                            'sent'    => ['text-bg-success', 'Enviado a este grupo'],
                            'failed'  => ['text-bg-danger', 'Error'],
                            'pending' => $groupTarget->scheduled_at
                                ? ['text-bg-warning', 'Programado']
                                : ['text-bg-secondary', 'Pendiente'],
                            default   => ['text-bg-secondary', 'Pendiente'],
                        };
                    } else {
                        $bc_badge = ['text-bg-secondary', 'No enviado'];
                    }
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold">
                                <i class="fas {{ $bc->fileTypeIcon() }} me-1 text-primary"></i>
                                {{ ucfirst($bc->file_type) }}
                                @if($bc->caption)
                                    — <span class="text-muted fw-normal" style="max-width:120px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:bottom" title="{{ $bc->caption }}">{{ $bc->caption }}</span>
                                @endif
                            </span>
                            <span class="badge {{ $bc_badge[0] }}" style="font-size:.65rem">{{ $bc_badge[1] }}</span>
                        </div>
                        <div class="card-body py-2">
                            <div class="small text-muted mb-2">
                                Guardado: {{ $bc->created_at->format('d/m/Y H:i') }}
                                @if($groupTarget?->scheduled_at)
                                    <br><i class="fas fa-clock me-1 text-warning"></i>Programado: {{ $groupTarget->scheduled_at->format('d/m/Y H:i') }}
                                @endif
                                @if($groupTarget?->sent_at)
                                    <br><i class="fas fa-check me-1 text-success"></i>Enviado: {{ $groupTarget->sent_at->format('d/m/Y H:i') }}
                                @endif
                            </div>
                            @if($groupTarget?->status === 'failed' && $groupTarget->error)
                                <div class="alert alert-danger py-1 px-2 mb-2" style="font-size:.72rem">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $groupTarget->error }}
                                </div>
                            @endif

                            {{-- Trigger --}}
                            <form method="POST"
                                  action="{{ route('admin.bot-manager.broadcasts.trigger', [$group, $bc]) }}"
                                  class="d-flex gap-1 align-items-center">
                                @csrf @method('PATCH')
                                <input type="text" name="trigger"
                                       class="form-control form-control-sm font-monospace"
                                       placeholder="/video1 o !oferta"
                                       value="{{ $bc->trigger ?? '' }}"
                                       maxlength="50"
                                       style="font-size:.75rem">
                                <button type="submit" class="btn btn-sm btn-outline-secondary flex-shrink-0" title="Guardar trigger">
                                    <i class="fas fa-save"></i>
                                </button>
                            </form>
                            <div class="form-text" style="font-size:.7rem">Trigger: el usuario escribe esto en el grupo → bot envía el vídeo</div>
                        </div>
                        <div class="card-footer py-2 d-flex gap-1 flex-wrap">
                            @if($groupTarget?->status === 'failed')
                                {{-- Retry failed send --}}
                                <form method="POST"
                                      action="{{ route('admin.bot-manager.broadcasts.retry-target', [$group, $groupTarget]) }}"
                                      class="flex-fill"
                                      onsubmit="return confirm('¿Reintentar el envío fallido?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm w-100">
                                        <i class="fas fa-redo me-1"></i>Reintentar
                                    </button>
                                </form>
                            @else
                                <form method="POST"
                                      action="{{ route('admin.bot-manager.broadcasts.send-to-group', [$group, $bc]) }}"
                                      class="flex-fill"
                                      onsubmit="return confirm('¿Enviar este broadcast a {{ addslashes($group->chat_title) }}?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-paper-plane me-1"></i>Enviar ahora
                                    </button>
                                </form>
                            @endif
                            <button class="btn btn-sm btn-outline-primary flex-shrink-0"
                                    onclick="openScheduleGroupModal({{ $bc->id }})"
                                    title="Programar envío">
                                <i class="fas fa-clock"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                    onclick="openRecurrenceModal({{ $bc->id }}, '{{ $bc->recurrence ?? '' }}', '{{ $bc->recurrence_time ?? '10:00' }}', '{{ $bc->recurrence_day ?? '' }}', '{{ $bc->recurrence_timezone ?? 'Europe/Madrid' }}')"
                                    title="Repetición automática">
                                <i class="fas fa-sync-alt"></i>
                                @if($bc->recurrence && $bc->recurrence !== 'none')
                                    <span class="badge text-bg-info ms-1" style="font-size:.6rem">{{ ucfirst($bc->recurrence) }}</span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Modal: schedule to this group --}}
            <div class="modal fade" id="scheduleGroupModal" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title"><i class="fas fa-clock me-1"></i>Programar envío</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="scheduleGroupForm" method="POST">
                            @csrf
                            {{-- Hidden offset so server can adjust --}}
                            <input type="hidden" name="tz_offset" id="tzOffset">
                            <div class="modal-body">
                                <div class="alert alert-secondary py-2 px-3 mb-3 small">
                                    <i class="fas fa-clock me-1"></i>
                                    Tu hora local: <strong id="localTimeDisplay">—</strong>
                                </div>
                                <label class="form-label small fw-semibold">Fecha y hora de envío</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduleDateTime"
                                       class="form-control" required>
                                <div class="form-text">Introduce tu hora local. El sistema ajusta automáticamente.</div>
                            </div>
                            <div class="modal-footer py-2">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-clock me-1"></i>Programar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        {{-- Modal: recurrence --}}
        <div class="modal fade" id="recurrenceModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title"><i class="fas fa-sync-alt me-1"></i>Repetición automática</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="recurrenceForm" method="POST">
                        @csrf @method('PATCH')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Frecuencia</label>
                                <select name="recurrence" id="recurrenceType" class="form-select" onchange="toggleRecurrenceDay()">
                                    <option value="">Sin repetición</option>
                                    <option value="daily">Diaria</option>
                                    <option value="weekly">Semanal</option>
                                    <option value="monthly">Mensual</option>
                                </select>
                            </div>
                            <div id="recurrenceDetails" style="display:none">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Hora de envío</label>
                                        <input type="time" name="recurrence_time" id="recurrenceTime" class="form-control form-control-sm" value="10:00">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Zona horaria</label>
                                        <select name="recurrence_timezone" class="form-select form-select-sm">
                                            @foreach(['Europe/Madrid'=>'España','Europe/London'=>'UK','Europe/Paris'=>'CET','America/New_York'=>'EST','America/Mexico_City'=>'México','America/Bogota'=>'Colombia','America/Argentina/Buenos_Aires'=>'Argentina','UTC'=>'UTC'] as $tz => $label)
                                                <option value="{{ $tz }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div id="recurrenceDayBlock" style="display:none" class="mb-2">
                                    <label class="form-label small fw-semibold" id="recurrenceDayLabel">Día</label>
                                    <input type="number" name="recurrence_day" id="recurrenceDay" class="form-control form-control-sm" min="0" max="31">
                                    <div class="form-text" id="recurrenceDayHelp"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i>Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @endif

    </div>

    {{-- ─── Tab: Avisos ─────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="tab-warnings">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Avisos acumulados</h5>
                <p class="text-muted small mb-0">Usuarios con infracciones detectadas por el bot. Puedes resetear sus avisos manualmente.</p>
            </div>
        </div>

        @php
            $groupWarnings = \App\Models\BotGroupWarning::where('bot_group_id', $group->id)
                ->orderByDesc('last_warned_at')->get();
        @endphp

        @if($groupWarnings->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>
                No hay usuarios con avisos acumulados. Buen comportamiento en el grupo.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Avisos</th>
                            <th>Máx.</th>
                            <th>Último motivo</th>
                            <th>Último aviso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupWarnings as $warn)
                        @php $maxWarn = $group->getSetting('max_warnings', 3); @endphp
                        <tr>
                            <td>
                                <div class="font-monospace small">{{ $warn->telegram_user_id }}</div>
                                @if($warn->telegram_username)
                                    <small class="text-muted">@{{ $warn->telegram_username }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $warn->count >= $maxWarn ? 'text-bg-danger' : ($warn->count >= $maxWarn - 1 ? 'text-bg-warning' : 'text-bg-secondary') }}">
                                    {{ $warn->count }}
                                </span>
                            </td>
                            <td><small class="text-muted">{{ $maxWarn }}</small></td>
                            <td><small>{{ $warn->reason ?? '—' }}</small></td>
                            <td><small>{{ $warn->last_warned_at?->format('d/m/Y H:i') ?? '—' }}</small></td>
                            <td>
                                <form method="POST"
                                      action="{{ route('admin.bot-manager.warnings.reset', [$group, $warn]) }}"
                                      onsubmit="return confirm('¿Resetear avisos de este usuario?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-secondary" title="Resetear avisos">
                                        <i class="fas fa-undo me-1"></i>Resetear
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>{{-- end tab-content --}}

{{-- Modal: Add command --}}
<div class="modal fade" id="addCmdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Añadir comando</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.bot-manager.commands.store', $group) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trigger</label>
                        <input type="text" name="trigger" class="form-control font-monospace"
                               placeholder="!lista  o  /precios  o  info" required maxlength="100">
                        <div class="form-text">Texto exacto que debe escribir el usuario para activar el comando.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Respuesta</label>
                        <textarea name="response" class="form-control" rows="5"
                                  placeholder="*Lista de precios:*&#10;• Plan básico — 5€/mes&#10;• Plan pro — 10€/mes"
                                  required maxlength="2000"></textarea>
                        <div class="form-text">Soporta Markdown de Telegram (*negrita*, _cursiva_, `código`, etc.)</div>
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

{{-- Modal: Edit command --}}
<div class="modal fade" id="editCmdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar comando</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCmdForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trigger</label>
                        <input type="text" name="trigger" id="editTrigger" class="form-control font-monospace" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Respuesta</label>
                        <textarea name="response" id="editResponse" class="form-control" rows="5" required maxlength="2000"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
                        <label class="form-check-label" for="editIsActive">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
function toggleLinkAction(checked) {
    document.getElementById('linkActionBlock').style.display = checked ? '' : 'none';
}
function toggleWelcome(checked) {
    document.getElementById('welcomeBlock').style.display = checked ? '' : 'none';
}
function toggleNightMode(checked) {
    document.getElementById('nightModeBlock').style.display = checked ? '' : 'none';
}
function toggleBlock(id, checked) {
    document.getElementById(id).style.display = checked ? '' : 'none';
}
function toggleMuteDuration(action) {
    document.getElementById('muteDurationBlock').style.display = action === 'mute' ? '' : 'none';
}

function openRecurrenceModal(broadcastId, recurrence, time, day, timezone) {
    const baseUrl = "{{ url('admin/bot-manager/' . $group->id . '/broadcast-recurrence') }}";
    document.getElementById('recurrenceForm').action = baseUrl + '/' + broadcastId;

    const typeEl = document.getElementById('recurrenceType');
    typeEl.value = recurrence || '';
    document.getElementById('recurrenceTime').value = time || '10:00';
    document.getElementById('recurrenceDay').value  = day || '';

    // Set timezone select
    const tzSel = document.querySelector('[name="recurrence_timezone"]');
    if (tzSel) tzSel.value = timezone || 'Europe/Madrid';

    toggleRecurrenceDay();
    new bootstrap.Modal(document.getElementById('recurrenceModal')).show();
}

function toggleRecurrenceDay() {
    const type    = document.getElementById('recurrenceType').value;
    const details = document.getElementById('recurrenceDetails');
    const dayBlk  = document.getElementById('recurrenceDayBlock');
    const dayLbl  = document.getElementById('recurrenceDayLabel');
    const dayHelp = document.getElementById('recurrenceDayHelp');

    details.style.display = type ? '' : 'none';
    if (type === 'weekly') {
        dayBlk.style.display = '';
        dayLbl.textContent   = 'Día de la semana';
        dayHelp.textContent  = '0 = Lunes, 1 = Martes, ... 6 = Domingo';
        document.getElementById('recurrenceDay').max = 6;
    } else if (type === 'monthly') {
        dayBlk.style.display = '';
        dayLbl.textContent   = 'Día del mes';
        dayHelp.textContent  = '1–31 (si el mes tiene menos días se ajusta al último)';
        document.getElementById('recurrenceDay').max = 31;
    } else {
        dayBlk.style.display = 'none';
    }
}

function editCommand(id, trigger, response, isActive) {
    const baseUrl = "{{ route('admin.bot-manager.commands.update', [$group, '__ID__']) }}";
    document.getElementById('editCmdForm').action = baseUrl.replace('__ID__', id);
    document.getElementById('editTrigger').value   = trigger;
    document.getElementById('editResponse').value  = response;
    document.getElementById('editIsActive').checked = isActive;
    new bootstrap.Modal(document.getElementById('editCmdModal')).show();
}

function openScheduleGroupModal(broadcastId) {
    const baseUrl = "{{ url('admin/bot-manager/' . $group->id . '/schedule-broadcast') }}";
    document.getElementById('scheduleGroupForm').action = baseUrl + '/' + broadcastId;

    // Use browser LOCAL time for min (2 minutes from now)
    const localNow = new Date();
    const minDate  = new Date(localNow.getTime() + 2 * 60 * 1000);
    const pad = n => String(n).padStart(2, '0');
    const minStr = minDate.getFullYear() + '-' + pad(minDate.getMonth()+1) + '-' + pad(minDate.getDate())
                 + 'T' + pad(minDate.getHours()) + ':' + pad(minDate.getMinutes());
    document.getElementById('scheduleDateTime').min   = minStr;
    document.getElementById('scheduleDateTime').value = '';

    // Show local time in modal
    document.getElementById('localTimeDisplay').textContent =
        pad(localNow.getDate()) + '/' + pad(localNow.getMonth()+1) + '/' + localNow.getFullYear()
        + ' ' + pad(localNow.getHours()) + ':' + pad(localNow.getMinutes());

    // Send browser timezone offset (minutes) so server can compensate
    document.getElementById('tzOffset').value = new Date().getTimezoneOffset();

    new bootstrap.Modal(document.getElementById('scheduleGroupModal')).show();
}

// Restore active tab from URL hash
const hash = window.location.hash;
if (hash) {
    const tab = document.querySelector(`[href="${hash}"]`);
    if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
}
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', e => {
        history.replaceState(null, null, e.target.getAttribute('href'));
    });
});
</script>
@endsection
@endsection
