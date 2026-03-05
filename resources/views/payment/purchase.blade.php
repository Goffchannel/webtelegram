@extends('layout')

@section('title', 'Compra completada')

@section('styles')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
    --pu-bg:      #0e1117;
    --pu-surface: #161b25;
    --pu-surface2:#1e2535;
    --pu-border:  #252d3d;
    --pu-accent:  #4f8ef7;
    --pu-success: #22c55e;
    --pu-warning: #f59e0b;
    --pu-danger:  #ef4444;
    --pu-text:    #e2e8f0;
    --pu-muted:   #64748b;
    --pu-font:    'Outfit', sans-serif;
    --pu-mono:    'DM Mono', monospace;
}
body { background: var(--pu-bg) !important; font-family: var(--pu-font); color: var(--pu-text); }
main.container { max-width: 100% !important; padding: 24px 16px !important; margin-top: 0 !important; }

.pu-wrap { max-width: 720px; margin: 0 auto; }

/* ── Header ── */
.pu-header {
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 24px;
}
.pu-header-icon {
    width: 52px; height: 52px; border-radius: 50%;
    background: rgba(34,197,94,.15); border: 1.5px solid rgba(34,197,94,.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: var(--pu-success); flex-shrink: 0;
}
.pu-header-title { font-size: 1.45rem; font-weight: 700; letter-spacing: -.03em; margin: 0; }
.pu-header-sub   { font-size: .82rem; color: var(--pu-muted); margin: 0; }

/* ── Banner ── */
.pu-banner {
    border-radius: 12px; padding: 14px 18px;
    margin-bottom: 20px; font-size: .88rem;
    display: flex; align-items: flex-start; gap: 12px;
}
.pu-banner.success { background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.25); color: #86efac; }
.pu-banner.warning { background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.25); color: #fcd34d; }
.pu-banner.info    { background: rgba(79,142,247,.08); border: 1px solid rgba(79,142,247,.25); color: #93c5fd; }
.pu-banner.danger  { background: rgba(239,68,68,.08);  border: 1px solid rgba(239,68,68,.25);  color: #fca5a5; }
.pu-banner-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
.pu-banner-title { font-weight: 700; margin-bottom: 2px; font-size: .92rem; }

/* ── Card ── */
.pu-card {
    background: var(--pu-surface);
    border: 1px solid var(--pu-border);
    border-radius: 14px; overflow: hidden;
    margin-bottom: 16px;
}
.pu-card-header {
    padding: 13px 20px;
    border-bottom: 1px solid var(--pu-border);
    font-weight: 600; font-size: .84rem;
    color: var(--pu-muted); text-transform: uppercase; letter-spacing: .06em;
    display: flex; align-items: center; gap: 8px;
}
.pu-card-header i { color: var(--pu-accent); }
.pu-card-body { padding: 20px; }

/* ── Meta grid ── */
.pu-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
@media(max-width:560px) { .pu-meta { grid-template-columns: 1fr; } }
.pu-meta-block { background: var(--pu-surface2); border: 1px solid var(--pu-border); border-radius: 10px; padding: 14px 16px; }
.pu-meta-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--pu-muted); margin-bottom: 4px; }
.pu-meta-value { font-size: .9rem; color: var(--pu-text); font-weight: 500; }
.pu-meta-price { font-size: 1.4rem; font-weight: 700; color: var(--pu-success); font-family: var(--pu-mono); }
.pu-meta-uuid  { font-size: .7rem; color: var(--pu-muted); font-family: var(--pu-mono); word-break: break-all; }
.pu-badge-done { display: inline-block; background: rgba(34,197,94,.15); color: var(--pu-success); border: 1px solid rgba(34,197,94,.3); border-radius: 20px; padding: 2px 10px; font-size: .75rem; font-weight: 600; }

/* ── Delivery card states ── */
.pu-delivery-icon { font-size: 2.6rem; line-height:1; margin-bottom: 6px; }
.pu-delivery-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 4px; }
.pu-delivery-sub   { font-size: .82rem; color: var(--pu-muted); }

.pu-steps { list-style: none; padding: 0; margin: 14px 0; }
.pu-steps li {
    display: flex; gap: 10px; align-items: flex-start;
    font-size: .84rem; color: var(--pu-muted);
    padding: 7px 0; border-bottom: 1px solid var(--pu-border);
}
.pu-steps li:last-child { border-bottom: none; }
.pu-steps li .step-n {
    width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
    background: var(--pu-surface2); border: 1px solid var(--pu-border);
    display: flex; align-items: center; justify-content: center;
    font-size: .72rem; font-weight: 700; color: var(--pu-accent);
}

/* ── Bot button ── */
.pu-bot-btn {
    display: inline-flex; align-items: center; gap: 9px;
    background: var(--pu-success); color: #fff;
    border: none; border-radius: 10px;
    padding: 12px 24px; font-size: .95rem; font-weight: 700;
    text-decoration: none; transition: background .2s; cursor: pointer;
    font-family: var(--pu-font);
}
.pu-bot-btn:hover { background: #16a34a; color: #fff; }
.pu-bot-btn.secondary {
    background: transparent; color: var(--pu-accent);
    border: 1px solid rgba(79,142,247,.35);
}
.pu-bot-btn.secondary:hover { background: rgba(79,142,247,.1); color: var(--pu-accent); }

/* ── Code block ── */
code { background: var(--pu-surface2); color: #93c5fd; padding: 2px 7px; border-radius: 5px; font-family: var(--pu-mono); font-size: .82em; }

/* ── IPTV steps ── */
.pu-iptv-steps { background: var(--pu-surface2); border: 1px solid var(--pu-border); border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; }
.pu-iptv-steps ol { color: var(--pu-muted); font-size: .84rem; padding-left: 1.2rem; margin: 0; line-height: 2; }
.pu-iptv-steps ol strong { color: var(--pu-text); }

/* ── Bottom actions ── */
.pu-actions { text-align: center; margin-top: 24px; margin-bottom: 24px; }
.pu-back-btn { display: inline-flex; align-items: center; gap: 7px; background: var(--pu-accent); color: #fff; border-radius: 10px; padding: 11px 24px; font-weight: 700; font-size: .9rem; text-decoration: none; transition: background .2s; }
.pu-back-btn:hover { background: #3b7de9; color: #fff; }

.pu-support { text-align: center; font-size: .74rem; color: var(--pu-muted); margin-top: 8px; }
.pu-support code { font-size: .72rem; }

/* ── Modals ── */
.modal-content { background: var(--pu-surface); border-color: var(--pu-border); color: var(--pu-text); }
.modal-header  { border-color: var(--pu-border); }
.modal-footer  { border-color: var(--pu-border); }
.form-control, .form-select  { background: var(--pu-surface2); border-color: var(--pu-border); color: var(--pu-text); }
.form-control:focus, .form-select:focus { background: var(--pu-surface2); border-color: var(--pu-accent); color: var(--pu-text); box-shadow: 0 0 0 3px rgba(79,142,247,.2); }
.input-group-text { background: var(--pu-surface2); border-color: var(--pu-border); color: var(--pu-muted); }
.btn-close { filter: invert(1) opacity(.6); }
</style>
@endsection

@section('content')
@php
    $isManualCreatorFlow = $purchase->creator_id && $purchase->creator && !$purchase->creator->is_admin;
    $isServiceProduct    = $purchase->video && $purchase->video->isServiceProduct();
    $iptvReady           = $isServiceProduct && $purchase->serviceAccess && !$purchase->serviceAccess->isExpired();
    $botUrl              = $bot['url'] ?? '';
    $botConfigured       = $bot['is_configured'] ?? false;
    $deepLink            = $botUrl ? $botUrl . '?start=getvideo_' . $purchase->video_id : '#';
@endphp

<div class="pu-wrap">

    {{-- Header --}}
    <div class="pu-header">
        <div class="pu-header-icon"><i class="fas fa-check"></i></div>
        <div>
            <p class="pu-header-title">Compra completada</p>
            <p class="pu-header-sub">Tu pago se procesó correctamente</p>
        </div>
    </div>

    {{-- Pago confirmado banner --}}
    <div class="pu-banner success">
        <span class="pu-banner-icon"><i class="fas fa-shopping-cart"></i></span>
        <div>
            <div class="pu-banner-title">Pago confirmado</div>
            Tu pago se proceso correctamente. Debajo tienes los detalles de tu compra.
        </div>
    </div>

    {{-- Meta grid --}}
    <div class="pu-meta">
        <div class="pu-meta-block">
            <div class="pu-meta-label"><i class="fas {{ $isServiceProduct ? 'fa-tv' : 'fa-video' }} me-1"></i>{{ $isServiceProduct ? 'Servicio' : 'Video' }}</div>
            <div class="pu-meta-value" style="font-weight:700;font-size:1rem;margin-bottom:4px;">{{ $purchase->video->title }}</div>
            @if($purchase->video->description)
                <div class="pu-meta-value" style="font-size:.78rem;color:var(--pu-muted);">{{ Str::limit($purchase->video->description, 80) }}</div>
            @endif
            <div class="pu-meta-price mt-1">{{ $purchase->formatted_amount }}</div>
        </div>
        <div class="pu-meta-block">
            <div class="pu-meta-label"><i class="fas fa-receipt me-1"></i>Compra</div>
            <div class="pu-meta-uuid mb-2">{{ $purchase->purchase_uuid }}</div>
            <div class="pu-meta-value" style="font-size:.82rem;margin-bottom:6px;"><i class="fas fa-calendar-alt me-1" style="color:var(--pu-muted);"></i>{{ $purchase->created_at->format('d/m/Y H:i:s') }}</div>
            <span class="pu-badge-done">
                @php $statusLabels = ['completed'=>'Completado','pending'=>'Pendiente','failed'=>'Fallido','refunded'=>'Reembolsado']; @endphp
                {{ $statusLabels[$purchase->purchase_status] ?? ucfirst($purchase->purchase_status) }}
            </span>
        </div>
    </div>

    {{-- Delivery Status Card --}}
    <div class="pu-card">
        <div class="pu-card-header">
            <i class="fas fa-truck"></i> Estado de entrega
        </div>
        <div class="pu-card-body">

        @if ($iptvReady)
            {{-- ── IPTV activo ── --}}
            <div class="text-center mb-4">
                <div class="pu-delivery-icon">📺</div>
                <div class="pu-delivery-title">¡Tu suscripción IPTV está activa!</div>
                <div class="pu-delivery-sub"><i class="fas fa-calendar-alt me-1"></i>Expira: <strong>{{ $purchase->serviceAccess->expires_at->format('d/m/Y') }}</strong></div>
            </div>
            <div class="pu-iptv-steps">
                <div style="font-size:.8rem;font-weight:600;color:var(--pu-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;"><i class="fas fa-list-ol me-1" style="color:var(--pu-accent);"></i>Cómo ver en Plooplayer</div>
                <ol>
                    <li>Descarga <strong>Plooplayer</strong> en tu dispositivo (Android / iOS / Smart TV)</li>
                    <li>Pulsa el botón de abajo para obtener <strong>tu enlace personal</strong></li>
                    <li>Copia el enlace que aparece en la página siguiente</li>
                    <li>Abre Plooplayer → <em>Añadir lista</em> → pega el enlace → guarda</li>
                    <li>¡Listo! Ya puedes ver todos los canales</li>
                </ol>
            </div>
            <div class="text-center">
                <a class="pu-bot-btn" target="_blank" href="{{ route('service.access.show', $purchase->serviceAccess->access_token) }}">
                    <i class="fas fa-tv"></i> Obtener mi enlace IPTV
                </a>
                <p style="font-size:.75rem;color:var(--pu-muted);margin-top:10px;"><i class="fas fa-shield-alt me-1"></i>Enlace personal e intransferible — no lo compartas</p>
                @if($botConfigured)
                    <div class="mt-3">
                        <a href="{{ $deepLink }}" target="_blank" class="pu-bot-btn secondary" style="font-size:.82rem;padding:8px 16px;">
                            <i class="fab fa-telegram"></i> También recibir por Telegram
                        </a>
                    </div>
                @endif
            </div>

        @elseif ($purchase->verification_status === 'pending')
            {{-- ── Pendiente ── --}}
            @if($isManualCreatorFlow)
                <div class="pu-banner warning" style="margin-bottom:16px;">
                    <span class="pu-banner-icon"><i class="fas fa-clock"></i></span>
                    <div>
                        <div class="pu-banner-title">Pendiente de aprobacion del creador</div>
                        Tu solicitud fue enviada al creador. Cuando valide tu pago, el acceso quedara activo para tu usuario de Telegram.
                    </div>
                </div>
                <ul class="pu-steps">
                    <li>
                        <span class="step-n">1</span>
                        <span>Guarda este enlace de compra para volver luego:<br>
                            <a href="{{ route('purchase.view', $purchase->purchase_uuid) }}" style="color:var(--pu-accent);font-size:.78rem;word-break:break-all;">{{ route('purchase.view', $purchase->purchase_uuid) }}</a>
                        </span>
                    </li>
                    <li>
                        <span class="step-n">2</span>
                        <span>Cuando el creador apruebe, vuelve a este mismo enlace y sigue las instrucciones de entrega.</span>
                    </li>
                    @if ($purchase->telegram_username)
                    <li>
                        <span class="step-n">3</span>
                        <span>Asegurate de que tu usuario de Telegram es:
                            <strong style="color:var(--pu-text);">@<span id="telegram-username-display">{{ $purchase->telegram_username }}</span></strong>
                            <button type="button" style="background:transparent;border:1px solid var(--pu-border);color:var(--pu-muted);border-radius:6px;padding:2px 8px;font-size:.72rem;margin-left:6px;cursor:pointer;font-family:var(--pu-font);" onclick="editTelegramUsername()">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </span>
                    </li>
                    @endif
                </ul>
                <small style="color:var(--pu-muted);font-size:.75rem;">Si el creador rechaza el pago, deberas contactar directamente con ese creador para cualquier reembolso. Si tienes problemas, usa el boton "Reportar creador".</small>
            @else
                <div class="pu-banner info" style="margin-bottom:16px;">
                    <span class="pu-banner-icon"><i class="fas fa-clock"></i></span>
                    <div>
                        <div class="pu-banner-title">{{ $isServiceProduct ? 'Activa tu suscripcion en Telegram' : 'Esperando verificacion en Telegram' }}</div>
                        {{ $isServiceProduct ? 'Tu compra se proceso correctamente. Pulsa el botón para activar tu acceso.' : 'Para recibir tu video, pulsa el botón de abajo.' }}
                    </div>
                </div>
                @if ($purchase->telegram_username)
                <ul class="pu-steps">
                    <li>
                        <span class="step-n">✓</span>
                        <span>Usuario de Telegram: <strong style="color:var(--pu-text);">@<span id="telegram-username-display">{{ $purchase->telegram_username }}</span></strong>
                            <button type="button" style="background:transparent;border:1px solid var(--pu-border);color:var(--pu-muted);border-radius:6px;padding:2px 8px;font-size:.72rem;margin-left:6px;cursor:pointer;font-family:var(--pu-font);" onclick="editTelegramUsername()">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                        </span>
                    </li>
                </ul>
                @endif
                <div class="text-center mt-3">
                    @if($botConfigured)
                        <a href="{{ $deepLink }}" target="_blank" class="pu-bot-btn">
                            <i class="fab fa-telegram"></i>
                            {{ $isServiceProduct ? 'Activar suscripción IPTV' : 'Recibir video ahora' }}
                        </a>
                        <p style="font-size:.78rem;color:var(--pu-muted);margin-top:10px;"><i class="fas fa-info-circle me-1"></i>Al pulsar, el bot te enviará tu {{ $isServiceProduct ? 'enlace de acceso IPTV' : 'video' }} automáticamente.</p>
                    @else
                        <a href="{{ route('login') }}" class="pu-bot-btn" style="background:var(--pu-warning);">
                            <i class="fas fa-cog"></i> Falta configurar bot
                        </a>
                    @endif
                </div>
            @endif

        @elseif($purchase->verification_status === 'verified')
            @if ($purchase->delivery_status === 'delivered')
                @if($isServiceProduct && $purchase->serviceAccess)
                    {{-- ── IPTV entregado ── --}}
                    <div class="text-center mb-4">
                        <div class="pu-delivery-icon">📺</div>
                        <div class="pu-delivery-title">¡Tu suscripción IPTV está activa!</div>
                        <div class="pu-delivery-sub">
                            <i class="fas fa-calendar-alt me-1"></i>Expira: <strong>{{ $purchase->serviceAccess->expires_at->format('d/m/Y') }}</strong>
                            &nbsp;·&nbsp;
                            <i class="fas fa-clock me-1"></i>Activado: {{ $purchase->delivered_at->format('H:i') }}
                        </div>
                    </div>
                    <div class="pu-iptv-steps">
                        <div style="font-size:.8rem;font-weight:600;color:var(--pu-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;"><i class="fas fa-list-ol me-1" style="color:var(--pu-accent);"></i>Cómo ver en Plooplayer</div>
                        <ol>
                            <li>Descarga <strong>Plooplayer</strong> en tu dispositivo (Android / iOS / Smart TV)</li>
                            <li>Pulsa el botón de abajo para obtener <strong>tu enlace personal</strong></li>
                            <li>Copia el enlace que aparece en la página siguiente</li>
                            <li>Abre Plooplayer → <em>Añadir lista</em> → pega el enlace → guarda</li>
                            <li>¡Listo! Ya puedes ver todos los canales</li>
                        </ol>
                    </div>
                    <div class="text-center">
                        <a class="pu-bot-btn" target="_blank" href="{{ route('service.access.show', $purchase->serviceAccess->access_token) }}">
                            <i class="fas fa-tv"></i> Obtener mi enlace IPTV
                        </a>
                        <p style="font-size:.75rem;color:var(--pu-muted);margin-top:10px;"><i class="fas fa-shield-alt me-1"></i>Enlace personal e intransferible — no lo compartas</p>
                    </div>
                @else
                    {{-- ── Video entregado ── --}}
                    <div class="pu-banner success" style="margin-bottom:20px;">
                        <span class="pu-banner-icon"><i class="fas fa-check-circle"></i></span>
                        <div>
                            <div class="pu-banner-title">Video entregado</div>
                            Tu video se entrego correctamente en tu cuenta de Telegram.
                            @if($purchase->creator_id)
                                Si necesitas volver a recibirlo, usa <code>/getvideo {{ $purchase->video_id }}</code> en el bot.
                            @endif
                            <div style="font-size:.75rem;color:rgba(134,239,172,.6);margin-top:4px;">Entregado el: {{ $purchase->delivered_at->format('d/m/Y H:i:s') }}</div>
                        </div>
                    </div>
                    @if($botConfigured)
                    <div class="text-center">
                        <a href="{{ $deepLink }}" target="_blank" class="pu-bot-btn">
                            <i class="fab fa-telegram"></i> Abrir chat del bot
                        </a>
                        <p style="font-size:.78rem;color:var(--pu-muted);margin-top:10px;"><i class="fas fa-video me-1"></i>Usa <code>/getvideo {{ $purchase->video_id }}</code> cuando quieras para recibirlo otra vez.</p>
                    </div>
                    @endif
                @endif

            @elseif($purchase->delivery_status === 'pending')
                <div class="pu-banner info" style="margin-bottom:16px;">
                    <span class="pu-banner-icon"><i class="fas fa-spinner fa-spin"></i></span>
                    <div>
                        <div class="pu-banner-title">Preparando entrega</div>
                        @if($isManualCreatorFlow && !$isServiceProduct)
                            Pago aprobado por el creador. Sigue los pasos para recibir tu video.
                        @elseif(!$isServiceProduct)
                            Tu video se está preparando. Lo recibirás en breve en Telegram.
                        @else
                            Estamos activando tu acceso. Recarga en unos segundos.
                        @endif
                    </div>
                </div>
                @if($isManualCreatorFlow && !$isServiceProduct)
                <ul class="pu-steps">
                    <li><span class="step-n">1</span><span>Abre Telegram y entra al bot.</span></li>
                    <li><span class="step-n">2</span><span>Envía <code>/start</code> si es tu primera vez.</span></li>
                    <li><span class="step-n">3</span><span>Envía <code>/getvideo {{ $purchase->video_id }}</code> con el mismo usuario usado en la compra.</span></li>
                </ul>
                @if($botConfigured)
                <div class="text-center mt-3">
                    <a href="{{ $deepLink }}" target="_blank" class="pu-bot-btn">
                        <i class="fab fa-telegram"></i> Ir al bot a recoger el video
                    </a>
                </div>
                @endif
                @endif

            @elseif($purchase->delivery_status === 'failed')
                <div class="pu-banner danger">
                    <span class="pu-banner-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div>
                        <div class="pu-banner-title">Problema de entrega</div>
                        Hubo un problema al entregar tu video. El equipo ya fue notificado.
                        @if ($purchase->delivery_notes)
                            <div style="font-size:.78rem;margin-top:4px;">{{ $purchase->delivery_notes }}</div>
                        @endif
                    </div>
                </div>
            @endif
        @endif

        </div>
    </div>

    {{-- Fan message --}}
    @if($isServiceProduct && $purchase->video && $purchase->video->fan_message)
        <div class="pu-banner info">
            <span class="pu-banner-icon"><i class="fas fa-comment"></i></span>
            <div style="white-space:pre-wrap;">{{ $purchase->video->fan_message }}</div>
        </div>
    @endif

    {{-- Back button --}}
    <div class="pu-actions">
        <a href="{{ route('videos.index') }}" class="pu-back-btn">
            <i class="fas fa-arrow-left"></i> Ver mas videos
        </a>
    </div>

    <div class="pu-support">
        Necesitas ayuda? Contacta soporte con tu ID de compra: <code>{{ $purchase->purchase_uuid }}</code>
    </div>

</div>{{-- /pu-wrap --}}

{{-- Edit Telegram Username Modal --}}
<div class="modal fade" id="editUsernameModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar usuario de Telegram</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUsernameForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Usuario de Telegram</label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" class="form-control" id="new_telegram_username"
                                   name="telegram_username" value="{{ $purchase->telegram_username }}" required>
                        </div>
                        <div class="form-text">Escribe tu usuario correcto de Telegram (sin @)</div>
                    </div>
                    <div class="pu-banner info" style="margin-bottom:0;">
                        <span class="pu-banner-icon"><i class="fas fa-info-circle"></i></span>
                        <div><strong>Importante:</strong> Debe coincidir exactamente con tu usuario de Telegram.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
@if ($purchase->verification_status === 'pending' || ($purchase->verification_status === 'verified' && $purchase->delivery_status === 'pending'))
setInterval(function() {
    if (document.querySelector('.modal.show')) return;
    const a = document.activeElement;
    if (a && (a.tagName === 'INPUT' || a.tagName === 'TEXTAREA' || a.tagName === 'SELECT')) return;
    window.location.reload();
}, 30000);
@endif

function editTelegramUsername() {
    new bootstrap.Modal(document.getElementById('editUsernameModal')).show();
}

document.getElementById('editUsernameForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...';
    btn.disabled = true;
    fetch(`/purchase/{{ $purchase->purchase_uuid }}/update-username`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('telegram-username-display').textContent = data.username;
            showAlert('success', 'Usuario de Telegram actualizado correctamente.');
            bootstrap.Modal.getInstance(document.getElementById('editUsernameModal')).hide();
        } else {
            showAlert('error', data.message || 'No se pudo actualizar el usuario');
        }
    })
    .catch(() => showAlert('error', 'Ocurrio un error al actualizar el usuario'))
    .finally(() => { btn.innerHTML = orig; btn.disabled = false; });
});

function showAlert(type, message) {
    const cls = type === 'success' ? 'alert-success' : 'alert-danger';
    const el = document.createElement('div');
    el.className = `alert ${cls} alert-dismissible fade show position-fixed`;
    el.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
    el.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 5000);
}

</script>
@endsection
