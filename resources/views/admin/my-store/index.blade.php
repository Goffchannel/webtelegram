@extends('admin.layout')

@section('title', 'Perfil de Tienda')

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
    --ms-text:    #e2e8f0;
    --ms-muted:   #64748b;
    --ms-font:    'Outfit', sans-serif;
}
body { background: var(--ms-bg); color: var(--ms-text); font-family: var(--ms-font); }
.ms-shell *:not(i):not([class*="fa"]):not([class*="fab"]) { font-family: var(--ms-font); }

.ms-card {
    background: var(--ms-surface); border: 1px solid var(--ms-border);
    border-radius: 14px; padding: 24px; margin-bottom: 16px;
}
.ms-card-title {
    font-size: .75rem; font-weight: 600; color: var(--ms-muted);
    text-transform: uppercase; letter-spacing: .1em; margin-bottom: 18px;
}
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
.ms-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    object-fit: contain; background: #0a0e16;
    border: 3px solid var(--ms-accent);
    box-shadow: 0 0 0 4px rgba(79,142,247,.2); flex-shrink: 0;
}
.ms-avatar-placeholder {
    width: 72px; height: 72px; border-radius: 50%;
    background: var(--ms-bg); border: 2px dashed var(--ms-border);
    display: flex; align-items: center; justify-content: center;
    color: var(--ms-muted); font-size: 24px; flex-shrink: 0;
}
.ms-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 20px; border-radius: 8px; font-size: .88rem; font-weight: 600;
    cursor: pointer; border: none; text-decoration: none; transition: all .2s;
    font-family: var(--ms-font);
}
.ms-btn-primary { background: var(--ms-accent); color: #fff; }
.ms-btn-primary:hover { background: #3a7ef5; color: #fff; }
.ms-alert-success {
    background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3);
    color: var(--ms-success); border-radius: 9px; padding: 12px 16px;
    margin-bottom: 16px; font-size: .88rem;
}
.ms-slug-wrap { display: flex; align-items: center; }
.ms-slug-prefix {
    background: #0d1117; border: 1px solid var(--ms-border); border-right: none;
    border-radius: 8px 0 0 8px; padding: 9px 10px;
    color: var(--ms-muted); font-size: .82rem; white-space: nowrap;
}
.ms-slug-wrap .ms-input { border-radius: 0 8px 8px 0; }
</style>
@endsection

@section('content')
<div class="ms-shell" style="max-width:780px; margin:0 auto; padding:24px 16px;">

    @if(session('success'))
        <div class="ms-alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    <div style="margin-bottom:20px;">
        <h4 style="color:var(--ms-text); font-weight:700; margin:0;">Perfil de Tienda</h4>
        <p style="color:var(--ms-muted); font-size:.85rem; margin:4px 0 0;">
            Así se verá tu tienda en
            <a href="{{ url('/store/' . $creator->creator_slug) }}" target="_blank" style="color:var(--ms-accent);">
                xshop.brukyon.com/store/{{ $creator->creator_slug }}
            </a>
        </p>
    </div>

    <form method="POST" action="{{ route('admin.my-store.profile.update') }}" enctype="multipart/form-data">
        @csrf

        {{-- Avatar --}}
        <div class="ms-card">
            <div class="ms-card-title"><i class="fas fa-image"></i> Foto de perfil</div>
            <div class="ms-avatar-edit">
                @if($avatarSrc)
                    <img src="{{ $avatarSrc }}" id="avatarPreview" alt="Avatar" class="ms-avatar">
                @else
                    <div id="avatarPreviewPlaceholder" class="ms-avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                    <img src="" id="avatarPreview" alt="" class="ms-avatar" style="display:none;">
                @endif
                <div style="flex:1;">
                    <label class="ms-label">Subir archivo</label>
                    <input type="file" class="ms-input" name="creator_avatar" accept="image/*" id="avatarFile" style="margin-bottom:10px;">
                    <label class="ms-label">O pegar URL de imagen</label>
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
                    <div class="ms-slug-wrap">
                        <span class="ms-slug-prefix">/store/</span>
                        <input class="ms-input" name="creator_slug" required
                               value="{{ old('creator_slug', $creator->creator_slug) }}">
                    </div>
                </div>
                <div class="col-12">
                    <label class="ms-label">Bio / Descripción</label>
                    <textarea class="ms-input" name="creator_bio" maxlength="1200">{{ old('creator_bio', $creator->creator_bio) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="ms-label">Telegram User ID</label>
                    <input class="ms-input" name="telegram_user_id" type="number" placeholder="123456789"
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
                    <label class="ms-label">Email PayPal (para recibir pagos directos vía API)</label>
                    <input class="ms-input" name="paypal_email" type="email"
                           placeholder="tu@email.com"
                           value="{{ old('paypal_email', $creator->paypal_email ?? '') }}">
                </div>
                <div class="col-12">
                    <label class="ms-label">HTML de botón de pago personalizado</label>
                    <textarea class="ms-input" name="payment_button_html" placeholder="<a href='...'>Pagar</a>">{{ old('payment_button_html', $paymentMethods['payment_button_html'] ?? '') }}</textarea>
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
@endsection

@section('scripts')
<script>
document.getElementById('avatarFile')?.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('avatarPreview');
        const ph  = document.getElementById('avatarPreviewPlaceholder');
        img.src = e.target.result;
        img.style.display = 'block';
        if (ph) ph.style.display = 'none';
    };
    reader.readAsDataURL(file);
});
</script>
@endsection
