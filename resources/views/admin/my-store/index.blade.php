@extends('layout')

@section('title', 'Mi Tienda')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
    --ms-bg:      #0e1117;
    --ms-surface: #161b25;
    --ms-border:  #252d3d;
    --ms-accent:  #4f8ef7;
    --ms-success: #22c55e;
    --ms-warning: #f59e0b;
    --ms-danger:  #ef4444;
    --ms-text:    #e2e8f0;
    --ms-muted:   #64748b;
    --ms-font:    'Outfit', sans-serif;
}

body { background: var(--ms-bg); color: var(--ms-text); font-family: var(--ms-font); }
.ms-shell *:not(i):not([class*="fa"]):not([class*="fab"]) { font-family: var(--ms-font); }

/* ── Header ──────────────────────────────────────────── */
.ms-header {
    background: linear-gradient(135deg, #0e1117 0%, #131c2e 60%, #0e1a2f 100%);
    border: 1px solid var(--ms-border);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.ms-header::before {
    content: '';
    position: absolute; top: -80px; right: -80px;
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(79,142,247,.15) 0%, transparent 70%);
    pointer-events: none;
}
.ms-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    object-fit: contain; background: #0a0e16;
    border: 3px solid var(--ms-accent);
    box-shadow: 0 0 0 4px rgba(79,142,247,.2);
    flex-shrink: 0;
}
.ms-avatar-placeholder {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--ms-surface); border: 3px solid var(--ms-border);
    display: flex; align-items: center; justify-content: center;
    color: var(--ms-muted); font-size: 28px; flex-shrink: 0;
}
.ms-store-name { font-size: 1.4rem; font-weight: 700; color: var(--ms-text); margin: 0; }
.ms-slug { color: var(--ms-accent); font-size: .82rem; font-family: 'DM Mono', monospace; }
.ms-stat {
    background: rgba(255,255,255,.04); border: 1px solid var(--ms-border);
    border-radius: 10px; padding: 14px 20px; text-align: center; min-width: 90px;
}
.ms-stat-num { font-size: 1.5rem; font-weight: 700; color: var(--ms-text); line-height: 1; }
.ms-stat-label { font-size: .7rem; color: var(--ms-muted); margin-top: 3px; text-transform: uppercase; letter-spacing: .08em; }
.ms-stat.accent .ms-stat-num { color: var(--ms-accent); }
.ms-stat.success .ms-stat-num { color: var(--ms-success); }
.ms-stat.warning .ms-stat-num { color: var(--ms-warning); }

/* ── Admin shortcuts ──────────────────────────────────── */
.ms-shortcuts {
    display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px;
}
.ms-shortcut {
    display: flex; align-items: center; gap: 8px;
    background: var(--ms-surface); border: 1px solid var(--ms-border);
    border-radius: 10px; padding: 10px 18px;
    color: var(--ms-muted); font-size: .85rem; font-weight: 500;
    text-decoration: none; transition: all .2s;
}
.ms-shortcut:hover { border-color: var(--ms-accent); color: var(--ms-accent); background: rgba(79,142,247,.08); }
.ms-shortcut i { font-size: .82rem; }

/* ── Tabs ─────────────────────────────────────────────── */
.ms-tabs {
    display: flex; gap: 4px;
    background: var(--ms-surface); border: 1px solid var(--ms-border);
    border-radius: 12px; padding: 5px; margin-bottom: 20px;
}
.ms-tab-btn {
    flex: 1; border: none; background: transparent;
    color: var(--ms-muted); padding: 9px 14px;
    border-radius: 8px; font-size: .85rem; font-weight: 500;
    cursor: pointer; transition: all .2s; white-space: nowrap;
    font-family: var(--ms-font);
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.ms-tab-btn:hover { color: var(--ms-text); background: rgba(255,255,255,.05); }
.ms-tab-btn.active { background: var(--ms-accent); color: #fff; box-shadow: 0 2px 12px rgba(79,142,247,.35); }
.ms-tab-pane { display: none; }
.ms-tab-pane.active { display: block; }

/* ── Cards ────────────────────────────────────────────── */
.ms-card {
    background: var(--ms-surface); border: 1px solid var(--ms-border);
    border-radius: 14px; padding: 24px; margin-bottom: 16px;
}
.ms-card-title {
    font-size: .75rem; font-weight: 600; color: var(--ms-muted);
    text-transform: uppercase; letter-spacing: .1em; margin-bottom: 18px;
}

/* ── Form elements ────────────────────────────────────── */
.ms-label { font-size: .8rem; font-weight: 500; color: var(--ms-muted); margin-bottom: 5px; display: block; }
.ms-input {
    width: 100%; background: #0d1117; border: 1px solid var(--ms-border);
    border-radius: 8px; color: var(--ms-text); padding: 9px 12px;
    font-size: .88rem; transition: border-color .2s; font-family: var(--ms-font);
}
.ms-input:focus { outline: none; border-color: var(--ms-accent); background: #0a0e16; }
.ms-input::placeholder { color: var(--ms-muted); }
textarea.ms-input { resize: vertical; min-height: 80px; }
.ms-avatar-edit { display: flex; align-items: center; gap: 16px; }

/* ── Buttons ──────────────────────────────────────────── */
.ms-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 8px; font-size: .85rem; font-weight: 600;
    cursor: pointer; border: none; text-decoration: none; transition: all .2s;
    font-family: var(--ms-font);
}
.ms-btn-primary { background: var(--ms-accent); color: #fff; }
.ms-btn-primary:hover { background: #3a7ef5; color: #fff; }
.ms-btn-ghost { background: transparent; border: 1px solid var(--ms-border); color: var(--ms-muted); }
.ms-btn-ghost:hover { border-color: var(--ms-accent); color: var(--ms-accent); }

/* ── Purchases table ──────────────────────────────────── */
.ms-table-wrap { overflow-x: auto; border-radius: 10px; }
.ms-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.ms-table th {
    background: rgba(255,255,255,.03); color: var(--ms-muted);
    font-size: .7rem; text-transform: uppercase; letter-spacing: .08em;
    padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--ms-border);
}
.ms-table td { padding: 12px 14px; border-bottom: 1px solid rgba(37,45,61,.6); vertical-align: middle; }
.ms-table tr:last-child td { border-bottom: none; }
.ms-table tr:hover td { background: rgba(255,255,255,.02); }
.ms-mono { font-family: 'DM Mono', monospace; font-size: .82rem; }
.ms-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px; font-size: .72rem; font-weight: 600;
}
.ms-badge-pending  { background: rgba(245,158,11,.15); color: var(--ms-warning); }
.ms-badge-verified { background: rgba(34,197,94,.15);  color: var(--ms-success); }
.ms-badge-rejected { background: rgba(239,68,68,.15);  color: var(--ms-danger); }

/* ── Categories ───────────────────────────────────────── */
.ms-cat-list { display: flex; flex-direction: column; gap: 8px; }
.ms-cat-item {
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(255,255,255,.03); border: 1px solid var(--ms-border);
    border-radius: 9px; padding: 11px 16px;
}
.ms-cat-name { font-weight: 500; font-size: .88rem; }
.ms-cat-count { font-size: .78rem; color: var(--ms-muted); }

/* ── Alert ────────────────────────────────────────────── */
.ms-alert-success {
    background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3);
    color: var(--ms-success); border-radius: 9px; padding: 12px 16px;
    margin-bottom: 16px; font-size: .88rem;
}
</style>
@endsection

@section('content')
<div class="ms-shell" style="max-width:1000px; margin:0 auto; padding:24px 16px;">

    {{-- Flash --}}
    @if(session('success'))
        <div class="ms-alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="ms-header">
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            @if($avatarSrc)
                <img src="{{ $avatarSrc }}" class="ms-avatar" alt="Avatar">
            @else
                <div class="ms-avatar-placeholder"><i class="fas fa-store"></i></div>
            @endif
            <div style="flex:1; min-width:0;">
                <p class="ms-store-name">{{ $creator->creator_store_name ?? $creator->name }}</p>
                <p class="ms-slug" style="margin:2px 0 8px;">xshop.brukyon.com/store/{{ $creator->creator_slug }}</p>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <div class="ms-stat accent">
                        <div class="ms-stat-num">{{ $stats['videos'] }}</div>
                        <div class="ms-stat-label">Videos</div>
                    </div>
                    <div class="ms-stat warning">
                        <div class="ms-stat-num">{{ $stats['pending'] }}</div>
                        <div class="ms-stat-label">Pendientes</div>
                    </div>
                    <div class="ms-stat success">
                        <div class="ms-stat-num">{{ $stats['approved'] }}</div>
                        <div class="ms-stat-label">Aprobadas</div>
                    </div>
                    <div class="ms-stat">
                        <div class="ms-stat-num">{{ $stats['total'] }}</div>
                        <div class="ms-stat-label">Total ventas</div>
                    </div>
                </div>
            </div>
            <a href="{{ url('/store/' . $creator->creator_slug) }}" target="_blank" class="ms-btn ms-btn-ghost">
                <i class="fas fa-external-link-alt"></i> Ver tienda
            </a>
        </div>
    </div>

    {{-- Admin shortcuts --}}
    <div class="ms-shortcuts">
        <a href="{{ route('admin.videos.manage') }}" class="ms-shortcut">
            <i class="fas fa-film"></i> Todos los videos
        </a>
        <a href="{{ route('admin.purchases.index') }}" class="ms-shortcut">
            <i class="fas fa-money-bill-wave"></i> Todas las compras
        </a>
        <a href="{{ route('admin.categories.creator', $creator) }}" class="ms-shortcut">
            <i class="fas fa-folder"></i> Mis categorías
        </a>
        <a href="{{ route('admin.categories.manage') }}" class="ms-shortcut">
            <i class="fas fa-layer-group"></i> Todas las categorías
        </a>
        <a href="{{ route('admin.discount-codes.index') }}" class="ms-shortcut">
            <i class="fas fa-tag"></i> Códigos descuento
        </a>
        <a href="{{ route('settings.telegram-bot') }}" class="ms-shortcut">
            <i class="fab fa-telegram"></i> Config Telegram
        </a>
    </div>

    {{-- Tabs --}}
    <div class="ms-tabs">
        <button class="ms-tab-btn active" onclick="switchTab('ventas')">
            <i class="fas fa-receipt"></i> Mis Ventas
        </button>
        <button class="ms-tab-btn" onclick="switchTab('perfil')">
            <i class="fas fa-store"></i> Perfil de Tienda
        </button>
        <button class="ms-tab-btn" onclick="switchTab('categorias')">
            <i class="fas fa-folder"></i> Mis Categorías
        </button>
    </div>

    {{-- TAB: Ventas --}}
    <div id="tab-ventas" class="ms-tab-pane active">
        <div class="ms-card">
            <div class="ms-card-title"><i class="fas fa-receipt"></i> Ventas recientes</div>
            @if($recentPurchases->count())
                <div class="ms-table-wrap">
                    <table class="ms-table">
                        <thead>
                            <tr>
                                <th>Comprador</th>
                                <th>Video</th>
                                <th>Importe</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPurchases as $purchase)
                            <tr>
                                <td>
                                    <div style="font-weight:500;">{{ $purchase->telegram_username ?? $purchase->customer_email }}</div>
                                    @if($purchase->telegram_username && $purchase->customer_email)
                                        <div style="font-size:.75rem; color:var(--ms-muted);">{{ $purchase->customer_email }}</div>
                                    @endif
                                </td>
                                <td class="ms-mono">{{ Str::limit($purchase->video->title ?? '—', 28) }}</td>
                                <td class="ms-mono" style="color:var(--ms-success);">
                                    ${{ number_format($purchase->amount, 2) }}
                                </td>
                                <td>
                                    @php $vs = $purchase->verification_status; @endphp
                                    <span class="ms-badge ms-badge-{{ $vs }}">
                                        {{ $vs === 'verified' ? 'Aprobada' : ($vs === 'pending' ? 'Pendiente' : 'Rechazada') }}
                                    </span>
                                </td>
                                <td style="color:var(--ms-muted); font-size:.8rem;">
                                    {{ $purchase->created_at->format('d/m/y H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.purchases.show', $purchase) }}" class="ms-btn ms-btn-ghost" style="padding:5px 10px; font-size:.78rem;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:16px;">
                    {{ $recentPurchases->links() }}
                </div>
            @else
                <div style="text-align:center; padding:40px; color:var(--ms-muted);">
                    <i class="fas fa-receipt" style="font-size:2rem; margin-bottom:12px; display:block;"></i>
                    Aún no hay ventas registradas.
                </div>
            @endif
        </div>
    </div>

    {{-- TAB: Perfil --}}
    <div id="tab-perfil" class="ms-tab-pane">
        <form method="POST" action="{{ route('admin.my-store.profile.update') }}" enctype="multipart/form-data">
            @csrf

            {{-- Avatar --}}
            <div class="ms-card">
                <div class="ms-card-title"><i class="fas fa-image"></i> Foto de perfil</div>
                <div class="ms-avatar-edit">
                    @if($avatarSrc)
                        <img src="{{ $avatarSrc }}" id="avatarPreview" alt="Avatar"
                             style="width:72px;height:72px;border-radius:50%;object-fit:contain;background:#0a0e16;border:2px solid var(--ms-accent);">
                    @else
                        <div id="avatarPreviewPlaceholder" style="width:72px;height:72px;border-radius:50%;background:var(--ms-bg);border:2px dashed var(--ms-border);display:flex;align-items:center;justify-content:center;color:var(--ms-muted);font-size:24px;flex-shrink:0;">
                            <i class="fas fa-user"></i>
                        </div>
                        <img src="" id="avatarPreview" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:contain;background:#0a0e16;border:2px solid var(--ms-accent);display:none;">
                    @endif
                    <div style="flex:1;">
                        <label class="ms-label">Subir archivo</label>
                        <input type="file" class="ms-input" name="creator_avatar" accept="image/*" id="avatarFile" style="margin-bottom:8px;">
                        <label class="ms-label">O URL de imagen</label>
                        <input type="url" class="ms-input" name="creator_avatar_url" placeholder="https://..." value="{{ old('creator_avatar_url') }}">
                    </div>
                </div>
            </div>

            {{-- Info básica --}}
            <div class="ms-card">
                <div class="ms-card-title"><i class="fas fa-store"></i> Información de tienda</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ms-label">Nombre de tienda</label>
                        <input class="ms-input" name="creator_store_name" required maxlength="120"
                               value="{{ old('creator_store_name', $creator->creator_store_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="ms-label">Slug (URL)</label>
                        <div style="display:flex; align-items:center; gap:0;">
                            <span style="background:#0d1117;border:1px solid var(--ms-border);border-right:none;border-radius:8px 0 0 8px;padding:9px 10px;color:var(--ms-muted);font-size:.82rem;white-space:nowrap;">/store/</span>
                            <input class="ms-input" name="creator_slug" required style="border-radius:0 8px 8px 0;"
                                   value="{{ old('creator_slug', $creator->creator_slug) }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="ms-label">Bio / Descripción</label>
                        <textarea class="ms-input" name="creator_bio" maxlength="1200">{{ old('creator_bio', $creator->creator_bio) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="ms-label">Telegram User ID</label>
                        <input class="ms-input" name="telegram_user_id" type="number"
                               placeholder="123456789"
                               value="{{ old('telegram_user_id', $creator->telegram_user_id) }}">
                    </div>
                </div>
            </div>

            {{-- Métodos de pago --}}
            <div class="ms-card">
                <div class="ms-card-title"><i class="fas fa-credit-card"></i> Métodos de pago</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="ms-label">URL de PayPal.me</label>
                        <input class="ms-input" name="paypal_url" type="url"
                               placeholder="https://paypal.me/tu-usuario"
                               value="{{ old('paypal_url', $paymentMethods['paypal_url'] ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="ms-label">HTML de botón de pago personalizado</label>
                        <textarea class="ms-input" name="payment_button_html" style="min-height:80px;" placeholder="<a href='...'>Pagar</a>">{{ old('payment_button_html', $paymentMethods['payment_button_html'] ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="ms-label">Notas adicionales de pago</label>
                        <textarea class="ms-input" name="other_payment_notes" placeholder="Instrucciones de pago...">{{ old('other_payment_notes', $paymentMethods['other_payment_notes'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="ms-btn ms-btn-primary">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
        </form>
    </div>

    {{-- TAB: Categorías --}}
    <div id="tab-categorias" class="ms-tab-pane">
        <div class="ms-card">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
                <div class="ms-card-title" style="margin:0;"><i class="fas fa-folder"></i> Mis categorías</div>
                <a href="{{ route('admin.categories.creator', $creator) }}" class="ms-btn ms-btn-primary" style="font-size:.82rem; padding:7px 14px;">
                    <i class="fas fa-cog"></i> Gestionar
                </a>
            </div>
            @if($categories->count())
                <div class="ms-cat-list">
                    @foreach($categories as $cat)
                    <div class="ms-cat-item">
                        <div>
                            <span class="ms-cat-name">{{ $cat->name }}</span>
                            @if($cat->is_hidden)
                                <span class="ms-badge ms-badge-rejected" style="margin-left:8px;">Oculta</span>
                            @endif
                        </div>
                        <span class="ms-cat-count">{{ $cat->videos_count }} video{{ $cat->videos_count !== 1 ? 's' : '' }}</span>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center; padding:30px; color:var(--ms-muted);">
                    <i class="fas fa-folder-open" style="font-size:1.8rem; margin-bottom:10px; display:block;"></i>
                    No tienes categorías. <a href="{{ route('admin.categories.creator', $creator) }}" style="color:var(--ms-accent);">Crear una</a>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
function switchTab(name) {
    document.querySelectorAll('.ms-tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ms-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Avatar preview
document.getElementById('avatarFile')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('avatarPreview');
        const ph = document.getElementById('avatarPreviewPlaceholder');
        img.src = e.target.result;
        img.style.display = 'block';
        if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>
@endsection
