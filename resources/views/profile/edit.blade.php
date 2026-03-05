@extends('layout')

@section('title', 'Configuración de cuenta')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --pf-font: 'Outfit', sans-serif;
    --pf-accent: #4f8ef7;
    --pf-success: #22c55e;
    --pf-danger: #ef4444;
    --pf-muted: #64748b;
}
.pf-shell { font-family: var(--pf-font); max-width: 760px; margin: 0 auto; }

.pf-page-title {
    font-size: 1.4rem; font-weight: 700; letter-spacing: -.03em;
    margin-bottom: 24px;
    display: flex; align-items: center; gap: 10px;
    color: var(--bs-body-color, #212529);
}
.pf-page-title i { color: var(--pf-accent); }

.pf-card {
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 14px; overflow: hidden;
    margin-bottom: 20px;
}
.pf-card-header {
    padding: 14px 22px;
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
    font-weight: 700; font-size: .88rem;
    color: var(--bs-body-color, #212529);
    display: flex; align-items: center; gap: 9px;
}
.pf-card-header i { color: var(--pf-accent); font-size: .95rem; }
.pf-card-body { padding: 22px; }

.pf-label {
    font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em;
    color: var(--pf-muted); display: block; margin-bottom: 6px;
}
.pf-input {
    background: var(--bs-secondary-bg, #f8f9fa) !important;
    border: 1px solid var(--bs-border-color, #dee2e6) !important;
    border-radius: 9px !important; padding: 10px 13px !important;
    font-size: .9rem !important; width: 100%;
    color: var(--bs-body-color, #212529) !important;
    font-family: var(--pf-font) !important;
    transition: border-color .2s, box-shadow .2s !important;
}
.pf-input:focus {
    outline: none !important;
    border-color: var(--pf-accent) !important;
    box-shadow: 0 0 0 3px rgba(79,142,247,.12) !important;
}
.pf-input.is-invalid {
    border-color: var(--pf-danger) !important;
    box-shadow: 0 0 0 3px rgba(239,68,68,.1) !important;
}
.pf-error { font-size: .78rem; color: var(--pf-danger); margin-top: 5px; }

.pf-btn {
    padding: 10px 22px; border-radius: 9px;
    background: var(--pf-accent); color: #fff;
    border: none; font-weight: 600; font-size: .88rem;
    cursor: pointer; transition: background .2s;
    display: inline-flex; align-items: center; gap: 7px;
    font-family: var(--pf-font);
}
.pf-btn:hover { background: #3a7ae0; }
.pf-btn-danger {
    background: transparent;
    border: 1px solid var(--pf-danger);
    color: var(--pf-danger);
}
.pf-btn-danger:hover { background: var(--pf-danger); color: #fff; }

.pf-saved-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .8rem; color: var(--pf-success);
    background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.2);
    border-radius: 20px; padding: 4px 12px; font-weight: 600;
}

.pf-divider {
    border: none; border-top: 1px solid var(--bs-border-color, #dee2e6);
    margin: 20px 0;
}

.pf-danger-zone {
    background: rgba(239,68,68,.04);
    border: 1px solid rgba(239,68,68,.2);
    border-radius: 14px; padding: 20px 22px;
    margin-bottom: 20px;
}
.pf-danger-zone-title {
    font-weight: 700; font-size: .88rem; color: var(--pf-danger);
    display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
}
.pf-danger-zone p { font-size: .84rem; color: var(--pf-muted); margin: 0 0 14px; }
</style>
@endsection

@section('content')
<div class="pf-shell">

    <div class="pf-page-title">
        <i class="fas fa-user-cog"></i> Configuración de cuenta
    </div>

    @if(session('status') === 'profile-updated')
        <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:10px 16px;font-size:.85rem;color:#16a34a;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-check-circle"></i> Perfil actualizado correctamente.
        </div>
    @endif
    @if(session('status') === 'password-updated')
        <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:10px 16px;font-size:.85rem;color:#16a34a;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-check-circle"></i> Contraseña actualizada correctamente.
        </div>
    @endif

    {{-- ── Información personal ── --}}
    <div class="pf-card">
        <div class="pf-card-header">
            <i class="fas fa-user"></i> Información personal
        </div>
        <div class="pf-card-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PATCH')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="pf-label">Nombre</label>
                        <input type="text" class="pf-input @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="pf-label">Email</label>
                        <input type="email" class="pf-input @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="pf-error">{{ $message }}</div>@enderror
                        @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                            <div style="margin-top:8px;font-size:.78rem;color:var(--pf-muted);">
                                Email no verificado.
                                <button form="send-verification" class="btn btn-link p-0 text-decoration-underline" style="font-size:.78rem;">
                                    Reenviar verificación
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                <hr class="pf-divider">
                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="pf-btn">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Cambiar contraseña ── --}}
    <div class="pf-card">
        <div class="pf-card-header">
            <i class="fas fa-lock"></i> Cambiar contraseña
        </div>
        <div class="pf-card-body">
            <p style="font-size:.84rem;color:var(--pf-muted);margin-bottom:18px;">
                Usa una contraseña larga y aleatoria para mantener tu cuenta segura.
            </p>
            <form method="POST" action="{{ route('password.update') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="pf-label">Contraseña actual</label>
                        <input type="password" class="pf-input @error('current_password', 'updatePassword') is-invalid @enderror"
                               name="current_password" autocomplete="current-password">
                        @error('current_password', 'updatePassword')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="pf-label">Nueva contraseña</label>
                        <input type="password" class="pf-input @error('password', 'updatePassword') is-invalid @enderror"
                               name="password" autocomplete="new-password">
                        @error('password', 'updatePassword')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="pf-label">Confirmar contraseña</label>
                        <input type="password" class="pf-input @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                               name="password_confirmation" autocomplete="new-password">
                        @error('password_confirmation', 'updatePassword')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <hr class="pf-divider">
                <button type="submit" class="pf-btn">
                    <i class="fas fa-key"></i> Actualizar contraseña
                </button>
            </form>
        </div>
    </div>

    {{-- ── Eliminar cuenta ── --}}
    <div class="pf-danger-zone">
        <div class="pf-danger-zone-title">
            <i class="fas fa-exclamation-triangle"></i> Zona de peligro
        </div>
        <p>Eliminar tu cuenta es una acción permanente e irreversible. Todos tus datos serán borrados.</p>
        <button type="button" class="pf-btn pf-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
            <i class="fas fa-trash-alt"></i> Eliminar mi cuenta
        </button>
    </div>

</div>

{{-- Modal confirmar eliminación --}}
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px;border:1px solid rgba(239,68,68,.3);">
            <div class="modal-header" style="border-color:rgba(239,68,68,.2);">
                <h5 class="modal-title" style="font-family:'Outfit',sans-serif;font-weight:700;color:#ef4444;">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-family:'Outfit',sans-serif;">
                <p style="color:var(--bs-body-color);margin-bottom:16px;">
                    Esta acción no se puede deshacer. Introduce tu contraseña para confirmar.
                </p>
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf @method('DELETE')
                    <div class="mb-3">
                        <label class="pf-label">Contraseña</label>
                        <input type="password" class="pf-input @error('password', 'userDeletion') is-invalid @enderror"
                               name="password" placeholder="Tu contraseña actual">
                        @error('password', 'userDeletion')<div class="pf-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="pf-btn pf-btn-danger">
                            <i class="fas fa-trash-alt"></i> Sí, eliminar cuenta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" style="display:none;">
        @csrf
    </form>
@endif
@endsection
