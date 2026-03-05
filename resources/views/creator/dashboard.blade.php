@extends('layout')

@section('title', 'Panel de Creador')

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

.creator-shell { font-family: var(--cr-font); }
.creator-shell *:not(i):not([class*="fa"]):not([class*="fab"]) { font-family: var(--cr-font); }

/* ── Profile Header ───────────────────────────────── */
.cr-header {
    background: linear-gradient(135deg, #0e1117 0%, #131c2e 60%, #0e1a2f 100%);
    border: 1px solid var(--cr-border);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 8px;
    position: relative;
    overflow: hidden;
}
.cr-header::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(79,142,247,.18) 0%, transparent 70%);
    pointer-events: none;
}
.cr-avatar {
    width: 76px; height: 76px;
    border-radius: 50%;
    object-fit: contain;
    background: #0a0e16;
    border: 3px solid var(--cr-accent);
    box-shadow: 0 0 0 4px rgba(79,142,247,.2);
}
.cr-avatar-placeholder {
    width: 76px; height: 76px;
    border-radius: 50%;
    background: var(--cr-surface);
    border: 3px solid var(--cr-border);
    display: flex; align-items: center; justify-content: center;
    color: var(--cr-muted); font-size: 28px;
}
.cr-store-name {
    font-size: 1.5rem; font-weight: 700;
    color: var(--cr-text); margin: 0;
    letter-spacing: -.02em;
}
.cr-slug { color: var(--cr-accent); font-size: .85rem; font-family: 'DM Mono', monospace; }
.cr-stat {
    background: rgba(255,255,255,.04);
    border: 1px solid var(--cr-border);
    border-radius: 10px;
    padding: 14px 20px;
    min-width: 100px;
    text-align: center;
}
.cr-stat-num { font-size: 1.6rem; font-weight: 700; color: var(--cr-text); line-height: 1; }
.cr-stat-label { font-size: .72rem; color: var(--cr-muted); margin-top: 3px; text-transform: uppercase; letter-spacing: .08em; }
.cr-stat.accent .cr-stat-num { color: var(--cr-accent); }
.cr-stat.success .cr-stat-num { color: var(--cr-success); }
.cr-stat.warning .cr-stat-num { color: var(--cr-warning); }

/* ── Tab Nav ──────────────────────────────────────── */
.cr-tabs {
    display: flex; gap: 4px;
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: 12px;
    padding: 5px;
    margin-bottom: 20px;
}
.cr-tab-btn {
    flex: 1; border: none; background: transparent;
    color: var(--cr-muted); padding: 9px 14px;
    border-radius: 8px; font-size: .85rem; font-weight: 500;
    cursor: pointer; transition: all .2s; white-space: nowrap;
    font-family: var(--cr-font);
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.cr-tab-btn:hover { color: var(--cr-text); background: rgba(255,255,255,.05); }
.cr-tab-btn.active {
    background: var(--cr-accent);
    color: #fff;
    box-shadow: 0 2px 12px rgba(79,142,247,.35);
}
.cr-tab-btn i { font-size: .8rem; }

/* ── Panels ───────────────────────────────────────── */
.cr-panel { display: none; animation: fadeIn .2s ease; }
.cr-panel.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

/* ── Cards ────────────────────────────────────────── */
.cr-card {
    background: var(--cr-surface);
    border: 1px solid var(--cr-border);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 16px;
}
.cr-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--cr-border);
    font-weight: 600; font-size: .9rem;
    color: var(--cr-text);
    display: flex; align-items: center; gap: 8px;
}
.cr-card-header i { color: var(--cr-accent); }
.cr-card-body { padding: 20px; }

/* ── Form ─────────────────────────────────────────── */
.cr-label {
    font-size: .78rem; font-weight: 600;
    color: var(--cr-muted); text-transform: uppercase;
    letter-spacing: .06em; margin-bottom: 6px;
    display: block;
}
.cr-input {
    background: rgba(255,255,255,.04) !important;
    border: 1px solid var(--cr-border) !important;
    border-radius: 8px !important;
    color: var(--cr-text) !important;
    font-family: var(--cr-font) !important;
    font-size: .88rem !important;
    padding: 9px 12px !important;
    transition: border-color .2s !important;
    width: 100%;
}
.cr-input:focus {
    outline: none !important;
    border-color: var(--cr-accent) !important;
    box-shadow: 0 0 0 3px rgba(79,142,247,.15) !important;
    background: rgba(79,142,247,.04) !important;
}
.cr-input::placeholder { color: #3a4458 !important; }
.cr-input option { background: #1c2333; color: var(--cr-text); }
.cr-input[disabled] { opacity: .45; }

/* ── Buttons ──────────────────────────────────────── */
.cr-btn {
    background: var(--cr-accent); color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 20px; font-size: .85rem; font-weight: 600;
    cursor: pointer; transition: all .2s;
    font-family: var(--cr-font);
    display: inline-flex; align-items: center; gap: 6px;
}
.cr-btn:hover { background: #3b7ae4; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(79,142,247,.35); }
.cr-btn:active { transform: none; }
.cr-btn-sm { padding: 6px 14px; font-size: .8rem; }
.cr-btn-outline {
    background: transparent;
    border: 1px solid var(--cr-border);
    color: var(--cr-text);
}
.cr-btn-outline:hover { background: rgba(255,255,255,.06); border-color: var(--cr-text); transform: none; box-shadow: none; }
.cr-btn-danger { background: var(--cr-danger); }
.cr-btn-danger:hover { background: #dc2626; box-shadow: 0 4px 14px rgba(239,68,68,.3); }
.cr-btn-success { background: var(--cr-success); }
.cr-btn-success:hover { background: #16a34a; box-shadow: 0 4px 14px rgba(34,197,94,.3); }

/* ── Table ────────────────────────────────────────── */
.cr-table { width: 100%; border-collapse: collapse; }
.cr-table th {
    padding: 10px 14px; font-size: .72rem;
    color: var(--cr-muted); text-transform: uppercase;
    letter-spacing: .07em; font-weight: 600;
    border-bottom: 1px solid var(--cr-border);
    text-align: left;
}
.cr-table td {
    padding: 12px 14px; font-size: .85rem;
    color: var(--cr-text);
    border-bottom: 1px solid rgba(37,45,61,.6);
    vertical-align: middle;
}
.cr-table tr:last-child td { border-bottom: none; }
.cr-table tbody tr:hover td { background: rgba(255,255,255,.02); }

/* ── Badge ────────────────────────────────────────── */
.cr-badge {
    display: inline-block; padding: 3px 9px;
    border-radius: 20px; font-size: .72rem; font-weight: 600;
    letter-spacing: .04em;
}
.cr-badge-success { background: rgba(34,197,94,.15); color: var(--cr-success); }
.cr-badge-warning { background: rgba(245,158,11,.15); color: var(--cr-warning); }
.cr-badge-danger { background: rgba(239,68,68,.15); color: var(--cr-danger); }
.cr-badge-info { background: rgba(79,142,247,.15); color: var(--cr-accent); }
.cr-badge-secondary { background: rgba(100,116,139,.15); color: var(--cr-muted); }

/* ── Avatar upload preview ────────────────────────── */
.cr-avatar-edit {
    display: flex; align-items: center; gap: 20px;
    padding: 16px; background: rgba(255,255,255,.03);
    border: 1px dashed var(--cr-border); border-radius: 10px;
}

/* ── Expand rows ──────────────────────────────────── */
.cr-expand-row td { padding: 0 !important; }
.cr-expand-inner {
    padding: 16px;
    background: rgba(79,142,247,.04);
    border-top: 1px solid var(--cr-border);
}

/* ── Scrollbar ────────────────────────────────────── */
.creator-shell ::-webkit-scrollbar { width: 6px; height: 6px; }
.creator-shell ::-webkit-scrollbar-track { background: transparent; }
.creator-shell ::-webkit-scrollbar-thumb { background: var(--cr-border); border-radius: 3px; }

/* ── View store btn ───────────────────────────────── */
.cr-view-store {
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.12);
    color: var(--cr-text) !important;
    border-radius: 8px; padding: 8px 16px;
    font-size: .82rem; font-weight: 500;
    text-decoration: none !important;
    transition: all .2s;
    display: inline-flex; align-items: center; gap: 6px;
}
.cr-view-store:hover { background: rgba(255,255,255,.12); }

/* ── Responsive ───────────────────────────────────── */
@media(max-width: 576px) {
    .cr-tabs { flex-wrap: wrap; }
    .cr-tab-btn { flex: none; min-width: 45%; }
    .cr-header { padding: 20px; }
    .cr-stat { min-width: 80px; padding: 10px 14px; }
}
</style>
@endsection

@section('content')
<div class="creator-shell">

{{-- ── Profile Header ──────────────────────────────── --}}
<div class="cr-header mb-3">
    <div class="d-flex align-items-center gap-4 flex-wrap">
        @php
            $avatarSrc = $creator->creator_avatar
                ? (Str::startsWith($creator->creator_avatar, 'http') ? $creator->creator_avatar : asset('storage/' . $creator->creator_avatar))
                : null;
        @endphp
        @if($avatarSrc)
            <img src="{{ $avatarSrc }}" alt="Avatar" class="cr-avatar">
        @else
            <div class="cr-avatar-placeholder"><i class="fas fa-user"></i></div>
        @endif

        <div class="flex-grow-1">
            <p class="cr-store-name">{{ $creator->creator_store_name ?? $creator->name }}</p>
            @if($creator->creator_slug)
                <span class="cr-slug">/store/{{ $creator->creator_slug }}</span>
            @endif
            @if($creator->creator_bio)
                <p style="color:var(--cr-muted);font-size:.82rem;margin-top:6px;margin-bottom:0;">{{ Str::limit($creator->creator_bio, 100) }}</p>
            @endif
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-center">
            <div class="cr-stat accent">
                <div class="cr-stat-num">{{ $stats['videos'] }}</div>
                <div class="cr-stat-label">Productos</div>
            </div>
            <div class="cr-stat warning">
                <div class="cr-stat-num">{{ $stats['pending'] }}</div>
                <div class="cr-stat-label">Pendientes</div>
            </div>
            <div class="cr-stat success">
                <div class="cr-stat-num">{{ $stats['approved'] }}</div>
                <div class="cr-stat-label">Aprobados</div>
            </div>
            @if($creator->creator_slug)
            <a href="/store/{{ $creator->creator_slug }}/categories" target="_blank" class="cr-view-store">
                <i class="fas fa-external-link-alt" style="font-size:.75rem;"></i> Ver tienda
            </a>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div style="background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#22c55e;border-radius:10px;padding:10px 16px;margin-bottom:12px;font-size:.85rem;">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444;border-radius:10px;padding:10px 16px;margin-bottom:12px;font-size:.85rem;">
        <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
    </div>
@endif

{{-- ── Tab Nav ─────────────────────────────────────── --}}
<div class="cr-tabs" id="crTabs">
    <button class="cr-tab-btn active" data-tab="resumen"><i class="fas fa-chart-bar"></i> Resumen</button>
    <button class="cr-tab-btn" data-tab="perfil"><i class="fas fa-user-cog"></i> Perfil</button>
    <button class="cr-tab-btn" data-tab="categorias"><i class="fas fa-layer-group"></i> Categorías <span style="background:rgba(79,142,247,.2);color:var(--cr-accent);border-radius:20px;padding:1px 8px;font-size:.72rem;margin-left:2px;">{{ $categories->count() }}</span></button>
    <button class="cr-tab-btn" data-tab="productos"><i class="fas fa-video"></i> Productos <span style="background:rgba(79,142,247,.2);color:var(--cr-accent);border-radius:20px;padding:1px 8px;font-size:.72rem;margin-left:2px;">{{ $videos->total() }}</span></button>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: RESUMEN
════════════════════════════════════════════════════ --}}
<div class="cr-panel active" id="tab-resumen">
    <div class="cr-card">
        <div class="cr-card-header"><i class="fas fa-clock"></i> Compras recientes</div>
        <div style="overflow-x:auto;">
            <table class="cr-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPurchases as $purchase)
                        <tr>
                            <td style="font-family:'DM Mono',monospace;font-size:.78rem;color:var(--cr-muted);">
                                {{ $purchase->created_at->format('d/m/y H:i') }}
                            </td>
                            <td><strong>{{ $purchase->video->title ?? 'N/A' }}</strong></td>
                            <td style="font-family:'DM Mono',monospace;font-size:.82rem;">{{ $purchase->telegram_username ?? '—' }}</td>
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
                                @if($purchase->verification_status === 'pending')
                                    <div style="display:flex;gap:6px;">
                                        <form method="POST" action="{{ route('creator.purchases.approve', $purchase) }}" style="display:inline;">
                                            @csrf
                                            <button class="cr-btn cr-btn-sm cr-btn-success" type="submit"><i class="fas fa-check"></i> Aprobar</button>
                                        </form>
                                        <form method="POST" action="{{ route('creator.purchases.reject', $purchase) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="delivery_notes" value="Pago rechazado por el creador">
                                            <button class="cr-btn cr-btn-sm cr-btn-danger" type="submit"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                @else
                                    <span style="color:var(--cr-muted);font-size:.8rem;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;color:var(--cr-muted);padding:32px;">No hay compras todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--cr-border);">
            <a href="{{ route('creator.purchases') }}" style="color:var(--cr-accent);font-size:.82rem;text-decoration:none;">Ver todas las compras →</a>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: PERFIL
════════════════════════════════════════════════════ --}}
<div class="cr-panel" id="tab-perfil">
<form method="POST" action="{{ route('creator.profile.update') }}" enctype="multipart/form-data">
@csrf

    {{-- ── Sección: Identidad de tienda ── --}}
    <div class="cr-card" style="margin-bottom:16px;">
        <div class="cr-card-header">
            <i class="fas fa-store"></i> Identidad de tienda
        </div>
        <div class="cr-card-body">

            {{-- Avatar row --}}
            <div style="display:flex;align-items:center;gap:20px;margin-bottom:22px;padding-bottom:20px;border-bottom:1px solid var(--cr-border);">
                {{-- Avatar preview --}}
                <div style="position:relative;flex-shrink:0;">
                    @if($avatarSrc)
                        <img src="{{ $avatarSrc }}" id="avatarPreview" alt="Avatar"
                             style="width:80px;height:80px;border-radius:50%;object-fit:cover;background:#0a0e16;border:2px solid var(--cr-accent);">
                    @else
                        <div id="avatarPreviewPlaceholder" style="width:80px;height:80px;border-radius:50%;background:var(--cr-bg);border:2px dashed var(--cr-border);display:flex;align-items:center;justify-content:center;color:var(--cr-muted);font-size:28px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <img src="" id="avatarPreview" alt="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;background:#0a0e16;border:2px solid var(--cr-accent);display:none;">
                    @endif
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.8rem;font-weight:600;color:var(--cr-text);margin-bottom:8px;">Foto de perfil</div>
                    <input type="file" class="cr-input" name="creator_avatar" accept="image/*" id="avatarFile"
                           style="margin-bottom:8px;font-size:.82rem;">
                    <input type="url" class="cr-input" name="creator_avatar_url"
                           placeholder="O pega una URL de imagen directa"
                           value="{{ old('creator_avatar_url') }}"
                           style="font-size:.82rem;">
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="cr-label">Nombre de tienda</label>
                    <input class="cr-input" name="creator_store_name"
                           value="{{ old('creator_store_name', $creator->creator_store_name ?? $creator->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="cr-label">
                        Slug público
                        <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.7rem;color:var(--cr-muted);margin-left:4px;">/store/<strong>{{ $creator->creator_slug }}</strong></span>
                    </label>
                    <input class="cr-input" name="creator_slug"
                           value="{{ old('creator_slug', $creator->creator_slug) }}" required>
                </div>
                <div class="col-12">
                    <label class="cr-label">Bio</label>
                    <textarea class="cr-input" name="creator_bio" rows="3"
                              placeholder="Describe tu tienda o contenido...">{{ old('creator_bio', $creator->creator_bio) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sección: Telegram ── --}}
    <div class="cr-card" style="margin-bottom:16px;">
        <div class="cr-card-header">
            <i class="fab fa-telegram" style="color:#229ED9;"></i> Configuración Telegram
        </div>
        <div class="cr-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="cr-label">Tu Telegram User ID</label>
                    <input class="cr-input" name="telegram_user_id"
                           value="{{ old('telegram_user_id', $creator->telegram_user_id) }}"
                           placeholder="Ej: 123456789">
                    @if($creator->telegram_user_id)
                        <div style="margin-top:6px;font-size:.78rem;color:var(--cr-success);">
                            <i class="fas fa-check-circle me-1"></i> ID vinculado — el bot te reconocerá al subir videos.
                        </div>
                    @else
                        <div style="margin-top:6px;font-size:.78rem;color:var(--cr-warning);">
                            <i class="fas fa-exclamation-triangle me-1"></i> Sin vincular. Envía <code style="background:rgba(255,255,255,.07);padding:1px 5px;border-radius:4px;">/start</code> al bot para obtener tu ID.
                        </div>
                    @endif
                </div>
                <div class="col-md-6" style="display:flex;align-items:flex-start;">
                    <div style="background:rgba(34,157,217,.08);border:1px solid rgba(34,157,217,.2);border-radius:10px;padding:12px 14px;font-size:.8rem;color:var(--cr-muted);line-height:1.5;width:100%;margin-top:22px;">
                        <i class="fab fa-telegram" style="color:#229ED9;margin-right:6px;"></i>
                        Necesitas tu <strong style="color:var(--cr-text);">Telegram User ID</strong> para que el bot pueda enviarte los videos que subes y entregártelos a los compradores.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sección: Métodos de pago ── --}}
    <div class="cr-card" style="margin-bottom:16px;">
        <div class="cr-card-header">
            <i class="fas fa-wallet"></i> Métodos de pago
        </div>
        <div class="cr-card-body">

            {{-- PayPal API --}}
            <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cr-muted);margin-bottom:10px;">
                <i class="fab fa-paypal" style="color:#0070ba;margin-right:4px;"></i> PayPal API — cobro automático
            </div>
            <div class="row g-3" style="margin-bottom:20px;">
                <div class="col-12">
                    <label class="cr-label">Email PayPal</label>
                    <input class="cr-input" type="email" name="paypal_email"
                           value="{{ old('paypal_email', $creator->paypal_email) }}"
                           placeholder="tu@email.com">
                    <small style="color:var(--cr-muted);font-size:.75rem;margin-top:4px;display:block;">
                        El comprador paga automáticamente y recibe el video al instante. Requiere cuenta PayPal Business o verificada.
                    </small>
                </div>
            </div>

            <div style="border-top:1px solid var(--cr-border);margin-bottom:20px;"></div>

            {{-- Métodos manuales --}}
            <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cr-muted);margin-bottom:10px;">
                Métodos manuales
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="cr-label">PayPal.me URL</label>
                    <input class="cr-input" name="paypal_url"
                           value="{{ old('paypal_url', data_get($creator->creator_payment_methods, 'paypal_url')) }}"
                           placeholder="https://paypal.me/tunombre">
                    <small style="color:var(--cr-muted);font-size:.75rem;margin-top:4px;display:block;">El comprador abre el link, paga y te notifica. Tú apruebas manualmente.</small>
                </div>
                <div class="col-12">
                    <label class="cr-label">Botón de pago personalizado (HTML)</label>
                    <textarea class="cr-input" name="payment_button_html" rows="3"
                              placeholder="Pega aquí el HTML de tu botón de pago (Ko-fi, Stripe, etc.)">{{ old('payment_button_html', data_get($creator->creator_payment_methods, 'payment_button_html')) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="cr-label">Otros métodos / instrucciones</label>
                    <textarea class="cr-input" name="other_payment_notes" rows="2"
                              placeholder="Ej: Binance ID: xxxx, transferencia IBAN: xxxx, etc.">{{ old('other_payment_notes', data_get($creator->creator_payment_methods, 'other_payment_notes')) }}</textarea>
                </div>
            </div>

        </div>
    </div>

    {{-- Save --}}
    <div style="display:flex;justify-content:flex-end;">
        <button class="cr-btn" type="submit" style="padding:11px 28px;">
            <i class="fas fa-save"></i> Guardar cambios
        </button>
    </div>

</form>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: CATEGORÍAS
════════════════════════════════════════════════════ --}}
<div class="cr-panel" id="tab-categorias">
    {{-- Crear categoría --}}
    <div class="cr-card">
        <div class="cr-card-header"><i class="fas fa-plus-circle"></i> Nueva categoría</div>
        <div class="cr-card-body">
            <form action="{{ route('creator.categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="cr-label">Nombre</label>
                        <input type="text" class="cr-input" name="name" required>
                    </div>
                    <div class="col-md-4">
                        <label class="cr-label">Subir imagen</label>
                        <input type="file" class="cr-input" name="image" accept="image/*">
                    </div>
                    <div class="col-md-4">
                        <label class="cr-label">O URL de imagen</label>
                        <input type="url" class="cr-input" name="image_url" placeholder="https://...">
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <button type="submit" class="cr-btn"><i class="fas fa-plus"></i> Crear categoría</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Lista categorías --}}
    <div class="cr-card">
        <div class="cr-card-header"><i class="fas fa-layer-group"></i> Categorías ({{ $categories->count() }})</div>
        <div style="overflow-x:auto;">
            <table class="cr-table">
                <thead>
                    <tr><th>Imagen</th><th>Nombre</th><th>Videos</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                @if($category->hasImage())
                                    <img src="{{ $category->getImageUrl() }}" style="width:48px;height:36px;object-fit:cover;border-radius:6px;">
                                @else
                                    <div style="width:48px;height:36px;background:var(--cr-bg);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--cr-muted);font-size:14px;"><i class="fas fa-image"></i></div>
                                @endif
                            </td>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td><span class="cr-badge cr-badge-info">{{ $category->videos_count }}</span></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <button class="cr-btn cr-btn-sm cr-btn-outline" type="button"
                                            onclick="toggleRow('cat-{{ $category->id }}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('creator.categories.delete', $category) }}"
                                          onsubmit="return confirm('¿Eliminar categoría? Los videos quedarán sin categoría.')">
                                        @csrf @method('DELETE')
                                        <button class="cr-btn cr-btn-sm cr-btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="cat-{{ $category->id }}" style="display:none;">
                            <td colspan="4" style="padding:0 !important;">
                                <div class="cr-expand-inner">
                                    <form method="POST" action="{{ route('creator.categories.update', $category) }}" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="cr-label">Nombre</label>
                                                <input class="cr-input" name="name" value="{{ $category->name }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="cr-label">Nueva imagen</label>
                                                <input type="file" class="cr-input" name="image" accept="image/*">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="cr-label">URL de imagen</label>
                                                <input class="cr-input" name="image_url" value="{{ $category->image_url }}" placeholder="https://...">
                                            </div>
                                            <div class="col-12" style="margin-top:8px;">
                                                <button class="cr-btn cr-btn-sm" type="submit"><i class="fas fa-save"></i> Guardar</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--cr-muted);padding:32px;">Aún no tienes categorías.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: PRODUCTOS
════════════════════════════════════════════════════ --}}
<div class="cr-panel" id="tab-productos">
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
        <button class="cr-btn" data-bs-toggle="modal" data-bs-target="#modal-create-product">
            <i class="fas fa-plus"></i> Nuevo producto
        </button>
    </div>

    <div class="cr-card">
        <div style="overflow-x:auto;">
            <table class="cr-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Thumb</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($videos as $video)
                        <tr>
                            <td>
                                <strong>{{ $video->title }}</strong>
                                <div style="font-size:.75rem;color:var(--cr-muted);font-family:'DM Mono',monospace;margin-top:2px;">
                                    {{ $video->created_at->format('d/m/y') }}
                                    @if($video->duration)
                                        · <i class="fas fa-clock" style="font-size:.65rem;"></i> {{ $video->duration >= 3600 ? gmdate('H:i:s', $video->duration) : gmdate('i:s', $video->duration) }}
                                    @endif
                                    @if($video->file_size)
                                        · {{ round($video->file_size / 1048576, 1) }} MB
                                    @endif
                                </div>
                            </td>
                            <td><span class="cr-badge cr-badge-success">${{ number_format($video->price, 2) }}</span></td>
                            <td>
                                @if($video->isServiceProduct())
                                    <span class="cr-badge cr-badge-info">Servicio {{ $video->duration_days ?? 30 }}d</span>
                                    <div style="font-size:.72rem;color:var(--cr-muted);margin-top:2px;">Stock: {{ $video->available_service_lines_count ?? 0 }}</div>
                                @else
                                    <span class="cr-badge cr-badge-secondary">Video</span>
                                @endif
                            </td>
                            <td><span class="cr-badge cr-badge-info">{{ $video->category->name ?? '—' }}</span></td>
                            <td>
                                @if($video->hasThumbnail())
                                    <img src="{{ $video->getThumbnailUrl() }}" style="width:44px;height:32px;object-fit:cover;border-radius:5px;">
                                @else
                                    <span style="color:var(--cr-muted);font-size:.78rem;">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <button class="cr-btn cr-btn-sm cr-btn-outline"
                                            onclick="toggleRow('vid-{{ $video->id }}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('creator.videos.delete', $video) }}"
                                          onsubmit="return confirm('¿Eliminar producto?')">
                                        @csrf @method('DELETE')
                                        <button class="cr-btn cr-btn-sm cr-btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="vid-{{ $video->id }}" style="display:none;">
                            <td colspan="6" style="padding:0 !important;background:var(--cr-bg);">
                                <div style="padding:20px 24px;">
                                    <form method="POST" action="{{ route('creator.videos.update', $video) }}">
                                        @csrf @method('PUT')

                                        {{-- Estado Telegram --}}
                                        @if($video->telegram_file_id)
                                            <div style="display:flex;align-items:center;gap:10px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:10px 16px;margin-bottom:18px;font-size:.84rem;color:var(--cr-success);">
                                                <i class="fab fa-telegram" style="font-size:1.1rem;"></i>
                                                <span><strong>Video vinculado</strong> — entrega automática activa.</span>
                                            </div>
                                        @else
                                            <div style="display:flex;align-items:flex-start;gap:10px;background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.25);border-radius:10px;padding:10px 16px;margin-bottom:18px;font-size:.84rem;color:var(--cr-warning);">
                                                <i class="fab fa-telegram" style="font-size:1.1rem;margin-top:1px;"></i>
                                                <span><strong>Sin vincular.</strong> Envía el video al bot{{ $bot['is_configured'] ? ' <strong>@'.$bot['username'].'</strong>' : '' }} con el título como caption. Asegúrate de tener tu <strong>Telegram User ID</strong> en el tab Perfil.</span>
                                            </div>
                                        @endif

                                        {{-- Sección: Información básica --}}
                                        <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cr-muted);margin-bottom:10px;">
                                            Información básica
                                        </div>
                                        <div class="row g-3" style="margin-bottom:18px;">
                                            <div class="col-12">
                                                <label class="cr-label">Título</label>
                                                <input name="title" class="cr-input" value="{{ $video->title }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="cr-label">Precio (USD)</label>
                                                <input name="price" type="number" min="0" step="0.01" class="cr-input" value="{{ $video->price }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="cr-label">Tipo</label>
                                                <select name="product_type" class="cr-input" required>
                                                    <option value="video" @selected(!$video->isServiceProduct())>Video</option>
                                                    <option value="service_access" @selected($video->isServiceProduct())>Servicio / Membresía</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="cr-label">Categoría</label>
                                                @if($categories->count() > 0)
                                                    <select name="category_id" class="cr-input" required>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}" @selected($video->category_id == $cat->id)>{{ $cat->name }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input class="cr-input" value="Crea una categoría primero" disabled>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                <label class="cr-label">Días de acceso</label>
                                                <input name="duration_days" type="number" min="1" max="365" class="cr-input"
                                                       value="{{ $video->duration_days ?? 30 }}"
                                                       placeholder="30">
                                            </div>
                                            <div class="col-12">
                                                <label class="cr-label">Descripción breve</label>
                                                <textarea name="description" class="cr-input" rows="2" placeholder="Descripción visible en la tienda...">{{ $video->description }}</textarea>
                                            </div>
                                        </div>

                                        {{-- Divider --}}
                                        <div style="border-top:1px solid var(--cr-border);margin-bottom:18px;"></div>

                                        {{-- Sección: Imagen y detalles --}}
                                        <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cr-muted);margin-bottom:10px;">
                                            Imagen y detalles
                                        </div>
                                        <div class="row g-3" style="margin-bottom:18px;">
                                            <div class="col-md-8">
                                                <label class="cr-label">Thumbnail URL</label>
                                                <input name="thumbnail_url" class="cr-input"
                                                       value="{{ $video->thumbnail_url ?: (filter_var($video->thumbnail_path, FILTER_VALIDATE_URL) ? $video->thumbnail_path : '') }}"
                                                       placeholder="https://...">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="cr-label">Intensidad blur</label>
                                                <input name="blur_intensity" type="number" min="1" max="20" class="cr-input"
                                                       value="{{ $video->blur_intensity ?? 10 }}"
                                                       placeholder="10">
                                            </div>
                                            <div class="col-12">
                                                <label class="cr-label">Descripción larga</label>
                                                <textarea name="long_description" class="cr-input" rows="3" placeholder="Descripción detallada del producto...">{{ $video->long_description }}</textarea>
                                            </div>
                                        </div>

                                        {{-- Divider --}}
                                        <div style="border-top:1px solid var(--cr-border);margin-bottom:18px;"></div>

                                        {{-- Sección: Post-compra --}}
                                        <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cr-muted);margin-bottom:10px;">
                                            Mensajes post-compra
                                        </div>
                                        <div class="row g-3" style="margin-bottom:18px;">
                                            <div class="col-md-6">
                                                <label class="cr-label">Mensaje al comprador</label>
                                                <textarea name="fan_message" class="cr-input" rows="2" placeholder="Gracias por tu compra...">{{ $video->fan_message }}</textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="cr-label">Instrucciones de acceso</label>
                                                <textarea name="access_instructions" class="cr-input" rows="2" placeholder="Cómo acceder al contenido...">{{ $video->access_instructions }}</textarea>
                                            </div>
                                        </div>

                                        {{-- Opciones de visualización + Guardar --}}
                                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                                            <div style="display:flex;gap:24px;align-items:center;">
                                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--cr-text);font-size:.85rem;margin:0;">
                                                    <input type="checkbox" name="show_blurred" value="1" @checked($video->show_blurred_thumbnail)
                                                           style="accent-color:var(--cr-accent);width:15px;height:15px;">
                                                    Mostrar blurred
                                                </label>
                                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--cr-text);font-size:.85rem;margin:0;">
                                                    <input type="checkbox" name="allow_preview" value="1" @checked($video->allow_preview)
                                                           style="accent-color:var(--cr-accent);width:15px;height:15px;">
                                                    Permitir preview
                                                </label>
                                            </div>
                                            <div style="display:flex;gap:8px;align-items:center;">
                                                @if($video->isServiceProduct() && auth()->user()->is_admin)
                                                    <a href="{{ route('admin.videos.service-lines.show', $video) }}"
                                                       class="cr-btn cr-btn-sm cr-btn-outline">
                                                        <i class="fas fa-key"></i> Líneas IPTV
                                                    </a>
                                                @endif
                                                <button class="cr-btn cr-btn-sm" type="submit" @disabled($categories->count() === 0)>
                                                    <i class="fas fa-save"></i> Guardar cambios
                                                </button>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;color:var(--cr-muted);padding:40px;">
                            Aún no tienes productos. Envía videos al bot con tu Telegram User ID.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:14px 20px;border-top:1px solid var(--cr-border);">
            {{ $videos->links() }}
        </div>
    </div>
</div>

</div>{{-- /creator-shell --}}

{{-- ══════════════════════════════════════════════════
     MODAL: Crear producto
════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modal-create-product" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--cr-surface);border:1px solid var(--cr-border);border-radius:14px;">
            <form method="POST" action="{{ route('creator.videos.store') }}">
                @csrf
                <div class="modal-header" style="border-color:var(--cr-border);">
                    <h5 class="modal-title" style="color:var(--cr-text);font-family:var(--cr-font);font-weight:600;">
                        <i class="fas fa-plus-circle me-2" style="color:var(--cr-accent);"></i>Nuevo producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="display:flex;flex-direction:column;gap:14px;">
                    @if($categories->count() === 0)
                        <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:var(--cr-warning);border-radius:8px;padding:10px 14px;font-size:.83rem;">
                            <i class="fas fa-exclamation-triangle me-2"></i>Primero debes crear al menos una categoría.
                        </div>
                    @endif
                    <div>
                        <label class="cr-label">Título <span style="color:var(--cr-danger);">*</span></label>
                        <input type="text" name="title" class="cr-input" placeholder="Ej: IPTV Premium 30 días" required maxlength="200">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="cr-label">Tipo <span style="color:var(--cr-danger);">*</span></label>
                            <select name="product_type" class="cr-input" id="create-product-type" required>
                                <option value="service_access">Servicio / Membresía</option>
                                <option value="video">Video</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="cr-label">Precio (USD) <span style="color:var(--cr-danger);">*</span></label>
                            <input type="number" name="price" class="cr-input" min="0" step="0.01" placeholder="9.99" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="cr-label">Categoría <span style="color:var(--cr-danger);">*</span></label>
                            <select name="category_id" class="cr-input" required @disabled($categories->count() === 0)>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="create-duration-wrapper">
                            <label class="cr-label">Duración (días)</label>
                            <input type="number" name="duration_days" class="cr-input" min="1" max="365" value="30">
                        </div>
                    </div>
                    <div>
                        <label class="cr-label">Descripción breve</label>
                        <textarea name="description" class="cr-input" rows="2" maxlength="1000" placeholder="Descripción del producto..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:var(--cr-border);">
                    <button type="button" class="cr-btn cr-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="cr-btn cr-btn-success" @disabled($categories->count() === 0)>
                        <i class="fas fa-check"></i> Crear producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Tab switching
const tabBtns = document.querySelectorAll('.cr-tab-btn');
const panels  = document.querySelectorAll('.cr-panel');

function activateTab(tabName) {
    tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === tabName));
    panels.forEach(p => p.classList.toggle('active', p.id === 'tab-' + tabName));
    history.replaceState(null, '', '#' + tabName);
}

tabBtns.forEach(btn => btn.addEventListener('click', () => activateTab(btn.dataset.tab)));

// Restore tab from URL hash
const hash = location.hash.replace('#', '');
if (hash && document.getElementById('tab-' + hash)) activateTab(hash);

// Expand/collapse rows
function toggleRow(id) {
    const row = document.getElementById(id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

// Duration field toggle in modal
const createProductType    = document.getElementById('create-product-type');
const createDurationWrapper = document.getElementById('create-duration-wrapper');
function toggleDuration() {
    createDurationWrapper.style.display = createProductType.value === 'service_access' ? '' : 'none';
}
createProductType.addEventListener('change', toggleDuration);
toggleDuration();

// Avatar live preview
document.getElementById('avatarFile')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('avatarPreview');
        const placeholder = document.getElementById('avatarPreviewPlaceholder');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>
@endsection
