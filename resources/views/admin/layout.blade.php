<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Panel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    :root {
        --ad-bg:      #0e1117;
        --ad-sidebar: #090c12;
        --ad-surface: #161b25;
        --ad-border:  #252d3d;
        --ad-accent:  #4f8ef7;
        --ad-text:    #e2e8f0;
        --ad-muted:   #64748b;
        --ad-success: #22c55e;
        --ad-warning: #f59e0b;
        --ad-danger:  #ef4444;
        --ad-font:    'Outfit', sans-serif;
        --ad-sidebar-w: 220px;
        --ad-topbar-h: 52px;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
        margin: 0;
        background: var(--ad-bg);
        color: var(--ad-text);
        font-family: var(--ad-font);
        min-height: 100vh;
    }

    /* ── Sidebar ─────────────────────────────────────────── */
    .ad-sidebar {
        position: fixed;
        top: 0; left: 0;
        width: var(--ad-sidebar-w);
        height: 100vh;
        background: var(--ad-sidebar);
        border-right: 1px solid var(--ad-border);
        display: flex;
        flex-direction: column;
        z-index: 1040;
        overflow-y: auto;
        transition: transform .25s ease;
    }

    .ad-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 20px 18px 16px;
        border-bottom: 1px solid var(--ad-border);
        color: var(--ad-text);
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: .02em;
        text-decoration: none;
        flex-shrink: 0;
    }
    .ad-brand i {
        font-size: 1.1rem;
        color: var(--ad-accent);
    }
    .ad-brand:hover { color: var(--ad-text); }

    .ad-nav {
        flex: 1;
        padding: 10px 8px;
        list-style: none;
        margin: 0;
    }

    .ad-nav-section {
        font-size: .65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .12em;
        color: var(--ad-muted);
        padding: 14px 10px 6px;
    }

    .ad-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 8px;
        color: var(--ad-muted);
        text-decoration: none;
        font-size: .875rem;
        font-weight: 500;
        transition: background .15s, color .15s;
        margin-bottom: 2px;
    }
    .ad-nav a i {
        width: 16px;
        text-align: center;
        font-size: .85rem;
        flex-shrink: 0;
    }
    .ad-nav a:hover {
        background: rgba(79,142,247,.1);
        color: var(--ad-text);
    }
    .ad-nav a.active {
        background: rgba(79,142,247,.15);
        color: var(--ad-accent);
        font-weight: 600;
    }
    .ad-nav a.active i { color: var(--ad-accent); }

    .ad-sidebar-footer {
        padding: 12px 8px;
        border-top: 1px solid var(--ad-border);
        flex-shrink: 0;
    }
    .ad-sidebar-footer a,
    .ad-sidebar-footer button {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 8px 10px;
        border-radius: 8px;
        color: var(--ad-muted);
        text-decoration: none;
        font-size: .85rem;
        font-weight: 500;
        background: none;
        border: none;
        cursor: pointer;
        transition: background .15s, color .15s;
        margin-bottom: 2px;
        text-align: left;
        font-family: var(--ad-font);
    }
    .ad-sidebar-footer a:hover,
    .ad-sidebar-footer button:hover {
        background: rgba(255,255,255,.06);
        color: var(--ad-text);
    }
    .ad-sidebar-footer a i,
    .ad-sidebar-footer button i {
        width: 16px;
        text-align: center;
        font-size: .85rem;
    }

    /* ── Mobile topbar ───────────────────────────────────── */
    .ad-topbar {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0;
        height: var(--ad-topbar-h);
        background: var(--ad-sidebar);
        border-bottom: 1px solid var(--ad-border);
        z-index: 1035;
        align-items: center;
        padding: 0 14px;
        gap: 12px;
    }
    .ad-topbar-toggle {
        background: none;
        border: 1px solid var(--ad-border);
        border-radius: 7px;
        color: var(--ad-muted);
        width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    .ad-topbar-title {
        font-size: .9rem;
        font-weight: 600;
        color: var(--ad-text);
        flex: 1;
    }

    /* ── Overlay (mobile) ────────────────────────────────── */
    .ad-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.55);
        z-index: 1039;
    }

    /* ── Main content ────────────────────────────────────── */
    .ad-main {
        margin-left: var(--ad-sidebar-w);
        min-height: 100vh;
        padding: 28px 28px 40px;
    }

    /* ── Bootstrap dark overrides ────────────────────────── */
    .card {
        background: var(--ad-surface);
        border-color: var(--ad-border);
        color: var(--ad-text);
    }
    .card-header {
        background: rgba(255,255,255,.04);
        border-color: var(--ad-border);
        color: var(--ad-text);
    }
    .table {
        --bs-table-bg: transparent;
        --bs-table-color: var(--ad-text);
        --bs-table-border-color: var(--ad-border);
        --bs-table-striped-bg: rgba(255,255,255,.025);
        --bs-table-hover-bg: rgba(79,142,247,.07);
    }
    .table thead th {
        color: var(--ad-muted);
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 600;
        border-bottom-color: var(--ad-border);
    }
    .table-light {
        --bs-table-bg: transparent;
        --bs-table-color: var(--ad-text);
        --bs-table-border-color: var(--ad-border);
    }
    .modal-content {
        background: var(--ad-surface);
        border-color: var(--ad-border);
        color: var(--ad-text);
    }
    .modal-header {
        border-color: var(--ad-border);
    }
    .modal-footer {
        border-color: var(--ad-border);
    }
    .form-control, .form-select {
        background: #0d1117;
        border-color: var(--ad-border);
        color: var(--ad-text);
    }
    .form-control:focus, .form-select:focus {
        background: #0a0e16;
        border-color: var(--ad-accent);
        color: var(--ad-text);
        box-shadow: 0 0 0 3px rgba(79,142,247,.15);
    }
    .form-control::placeholder { color: var(--ad-muted); }
    .input-group-text {
        background: #0d1117;
        border-color: var(--ad-border);
        color: var(--ad-muted);
    }
    .btn-outline-secondary {
        --bs-btn-color: var(--ad-muted);
        --bs-btn-border-color: var(--ad-border);
        --bs-btn-hover-bg: rgba(255,255,255,.08);
        --bs-btn-hover-color: var(--ad-text);
        --bs-btn-hover-border-color: var(--ad-border);
        --bs-btn-active-bg: rgba(255,255,255,.12);
    }
    .dropdown-menu {
        background: var(--ad-surface);
        border-color: var(--ad-border);
    }
    .dropdown-item {
        color: var(--ad-text);
    }
    .dropdown-item:hover {
        background: rgba(79,142,247,.1);
        color: var(--ad-text);
    }
    .dropdown-divider { border-color: var(--ad-border); }
    .pagination .page-link {
        background: var(--ad-surface);
        border-color: var(--ad-border);
        color: var(--ad-muted);
    }
    .pagination .page-link:hover {
        background: rgba(79,142,247,.1);
        color: var(--ad-text);
        border-color: var(--ad-border);
    }
    .pagination .page-item.active .page-link {
        background: var(--ad-accent);
        border-color: var(--ad-accent);
        color: #fff;
    }
    .pagination .page-item.disabled .page-link {
        background: var(--ad-surface);
        color: var(--ad-muted);
    }
    .badge.text-bg-dark, .badge.bg-secondary {
        background: rgba(255,255,255,.1) !important;
        color: var(--ad-text) !important;
    }
    .text-muted { color: var(--ad-muted) !important; }
    h1, h2, h3, h4, h5, h6 { color: var(--ad-text); font-family: var(--ad-font); }
    hr { border-color: var(--ad-border); }
    .border, .border-top, .border-bottom { border-color: var(--ad-border) !important; }
    .bg-dark { background: var(--ad-surface) !important; }

    /* ── Mobile responsive ───────────────────────────────── */
    @media (max-width: 767.98px) {
        .ad-topbar { display: flex; }
        .ad-sidebar {
            transform: translateX(-100%);
            top: 0;
        }
        .ad-sidebar.open {
            transform: translateX(0);
        }
        .ad-overlay.open { display: block; }
        .ad-main {
            margin-left: 0;
            padding: 16px 14px 32px;
            padding-top: calc(var(--ad-topbar-h) + 16px);
        }
    }
    </style>

    @yield('styles')
</head>
<body>

    {{-- Mobile topbar --}}
    <div class="ad-topbar">
        <button class="ad-topbar-toggle" id="adToggle" aria-label="Menú">
            <i class="fas fa-bars"></i>
        </button>
        <span class="ad-topbar-title">Admin Panel</span>
        @php $me = auth()->user(); @endphp
        @if($me && $me->creator_avatar)
            <img src="{{ str_starts_with($me->creator_avatar,'http') ? $me->creator_avatar : asset('storage/'.$me->creator_avatar) }}"
                 style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:2px solid var(--ad-border);">
        @else
            <div style="width:30px;height:30px;border-radius:50%;background:var(--ad-surface);border:2px solid var(--ad-border);display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-user" style="font-size:.7rem;color:var(--ad-muted);"></i>
            </div>
        @endif
    </div>

    {{-- Overlay --}}
    <div class="ad-overlay" id="adOverlay"></div>

    {{-- Sidebar --}}
    <nav class="ad-sidebar" id="adSidebar">
        <a href="{{ route('admin.videos.manage') }}" class="ad-brand">
            <i class="fas fa-shield-alt"></i>
            Admin Panel
        </a>

        <ul class="ad-nav">
            <li class="ad-nav-section">Contenido</li>
            <li>
                <a href="{{ route('admin.videos.manage') }}"
                   class="{{ request()->routeIs('admin.videos.*') ? 'active' : '' }}">
                    <i class="fas fa-video"></i> Videos
                </a>
            </li>
            <li>
                <a href="{{ route('admin.purchases.index') }}"
                   class="{{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i> Compras
                </a>
            </li>
            <li>
                <a href="{{ route('admin.categories.manage') }}"
                   class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-folder"></i> Categorías
                </a>
            </li>
            <li>
                <a href="{{ route('admin.discount-codes.index') }}"
                   class="{{ request()->routeIs('admin.discount-codes.*') ? 'active' : '' }}">
                    <i class="fas fa-tag"></i> Descuentos
                </a>
            </li>

            <li class="ad-nav-section">Bot & Servicios</li>
            <li>
                <a href="{{ route('admin.bot-manager.index') }}"
                   class="{{ request()->routeIs('admin.bot-manager.*') ? 'active' : '' }}">
                    <i class="fas fa-robot"></i> Bot Manager
                </a>
            </li>
            <li>
                <a href="{{ route('admin.iptv.index') }}"
                   class="{{ request()->routeIs('admin.iptv.*') ? 'active' : '' }}">
                    <i class="fas fa-tv"></i> IPTV
                </a>
            </li>

            <li class="ad-nav-section">Configuración</li>
            <li>
                <a href="{{ route('settings.telegram-bot') }}"
                   class="{{ request()->routeIs('settings.telegram-bot*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i> Config Bot
                </a>
            </li>
            <li>
                <a href="{{ route('admin.my-store.index') }}"
                   class="{{ request()->routeIs('admin.my-store.*') ? 'active' : '' }}">
                    <i class="fas fa-store"></i> Mi Tienda
                </a>
            </li>
        </ul>

        <div class="ad-sidebar-footer">
            @php $creator = auth()->user(); @endphp
            @if($creator && $creator->creator_slug)
            <a href="{{ url('/store/' . $creator->creator_slug) }}" target="_blank">
                <i class="fas fa-external-link-alt"></i> Ver tienda
            </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
            </form>
        </div>
    </nav>

    {{-- Main content --}}
    <main class="ad-main">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function() {
        const sidebar  = document.getElementById('adSidebar');
        const overlay  = document.getElementById('adOverlay');
        const toggle   = document.getElementById('adToggle');
        if (!toggle) return;
        function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('open');  }
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
        toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
        overlay.addEventListener('click', closeSidebar);
    })();
    </script>

    @yield('scripts')
</body>
</html>
