<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Video Store') - TeleBot</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Sticky footer using flexbox */
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; font-family: 'Outfit', sans-serif; }
        main { flex: 1; }
        footer { margin-top: auto; }

        /* ── Navbar ─────────────────────────────────────────────────── */
        .site-nav {
            background: #0d1117;
            border-bottom: 1px solid rgba(255,255,255,.07);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .site-nav .container {
            height: 56px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Brand */
        .site-nav .nav-brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            color: #fff !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -.02em;
            margin-right: 8px;
            flex-shrink: 0;
        }
        .site-nav .nav-brand i {
            color: #4f8ef7;
            font-size: .95rem;
        }

        /* Nav links */
        .site-nav .nav-link-item {
            color: rgba(255,255,255,.6) !important;
            font-size: .85rem;
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 7px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color .15s, background .15s;
            white-space: nowrap;
            cursor: pointer;
            background: none;
            border: none;
            font-family: 'Outfit', sans-serif;
        }
        .site-nav .nav-link-item:hover,
        .site-nav .nav-link-item.show {
            color: #fff !important;
            background: rgba(255,255,255,.07);
        }
        .site-nav .nav-link-item i { font-size: .8rem; }

        /* Dropdown */
        .site-nav .nav-dropdown {
            position: relative;
        }
        .site-nav .nav-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 200px;
            background: #161b25;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 12px;
            padding: 6px;
            box-shadow: 0 16px 48px rgba(0,0,0,.4);
            display: none;
            z-index: 9999;
        }
        .site-nav .nav-dropdown-menu.open { display: block; }
        .site-nav .nav-dropdown-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,.75) !important;
            text-decoration: none;
            font-size: .84rem;
            font-weight: 500;
            transition: background .15s, color .15s;
            font-family: 'Outfit', sans-serif;
        }
        .site-nav .nav-dropdown-item:hover {
            background: rgba(79,142,247,.12);
            color: #fff !important;
        }
        .site-nav .nav-dropdown-item i {
            width: 16px;
            text-align: center;
            color: #4f8ef7;
            font-size: .78rem;
            flex-shrink: 0;
        }
        .site-nav .nav-divider {
            height: 1px;
            background: rgba(255,255,255,.07);
            margin: 4px 6px;
        }
        .site-nav .nav-dropdown-item.danger { color: rgba(239,68,68,.85) !important; }
        .site-nav .nav-dropdown-item.danger:hover { background: rgba(239,68,68,.1); color: #ef4444 !important; }
        .site-nav .nav-dropdown-item.danger i { color: #ef4444; }

        /* Right side */
        .site-nav .nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Theme toggle */
        .site-nav .nav-theme-btn {
            width: 34px; height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            color: rgba(255,255,255,.6);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all .15s;
            font-size: .82rem;
        }
        .site-nav .nav-theme-btn:hover {
            background: rgba(255,255,255,.12);
            color: #fff;
        }

        /* Guest buttons */
        .site-nav .nav-btn-ghost {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            color: #fff !important;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: .82rem; font-weight: 600;
            text-decoration: none;
            transition: all .15s;
            font-family: 'Outfit', sans-serif;
        }
        .site-nav .nav-btn-ghost:hover {
            background: rgba(255,255,255,.15);
        }
        .site-nav .nav-btn-accent {
            background: #4f8ef7;
            border: 1px solid transparent;
            color: #fff !important;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: .82rem; font-weight: 600;
            text-decoration: none;
            transition: background .15s;
            font-family: 'Outfit', sans-serif;
        }
        .site-nav .nav-btn-accent:hover { background: #3b7de8; }

        /* Mobile toggle */
        .site-nav .nav-toggler {
            display: none;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px;
            padding: 6px 10px;
            color: rgba(255,255,255,.8);
            cursor: pointer;
            margin-left: auto;
        }
        .site-nav .nav-mobile-menu {
            display: none;
            background: #0d1117;
            border-top: 1px solid rgba(255,255,255,.07);
            padding: 12px 16px;
        }
        .site-nav .nav-mobile-menu.open { display: block; }
        .site-nav .nav-mobile-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 8px;
            color: rgba(255,255,255,.75) !important;
            text-decoration: none; font-size: .88rem; font-weight: 500;
            transition: background .15s;
            font-family: 'Outfit', sans-serif;
        }
        .site-nav .nav-mobile-item:hover { background: rgba(255,255,255,.07); color: #fff !important; }
        .site-nav .nav-mobile-item i { width: 18px; text-align: center; color: #4f8ef7; }
        .site-nav .nav-mobile-divider { height:1px; background:rgba(255,255,255,.07); margin: 6px 0; }

        @media (max-width: 991px) {
            .site-nav .nav-desktop { display: none !important; }
            .site-nav .nav-toggler { display: flex; align-items: center; gap: 6px; }
            .site-nav .container { flex-wrap: wrap; height: auto; padding-top: 10px; padding-bottom: 10px; }
        }
        @media (min-width: 992px) {
            .site-nav .nav-toggler { display: none !important; }
            .site-nav .nav-mobile-menu { display: none !important; }
        }

        /* ── Toasts ─────────────────────────────────────────────────── */
        #toastContainer {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            pointer-events: none;
            max-width: 380px;
            width: calc(100vw - 2rem);
        }
        .tl-toast {
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0,0,0,.18);
            overflow: hidden;
            pointer-events: all;
            animation: toastSlideIn .3s cubic-bezier(.21,1.02,.73,1) forwards;
        }
        @keyframes toastSlideIn {
            from { opacity:0; transform: translateX(80px); }
            to   { opacity:1; transform: translateX(0); }
        }
        .tl-toast.dismissing {
            animation: toastSlideOut .3s ease forwards;
        }
        @keyframes toastSlideOut {
            to { opacity:0; transform: translateX(80px); }
        }
        .tl-toast-body {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            padding: .8rem 1rem;
        }
        .tl-toast-icon { font-size: 1.1rem; flex-shrink:0; margin-top:1px; }
        .tl-toast-msg  { flex: 1; font-size: .9rem; line-height: 1.4; }
        .tl-toast-close {
            background: none; border: none; padding: 0;
            cursor: pointer; opacity: .45; flex-shrink:0;
            color: var(--bs-body-color); font-size:.9rem;
            transition: opacity .15s;
        }
        .tl-toast-close:hover { opacity:1; }
        .tl-toast-progress {
            height: 3px;
            background: rgba(128,128,128,.15);
        }
        .tl-toast-bar {
            height: 100%;
            width: 100%;
            border-radius: 0 0 10px 10px;
            transition: width linear;
        }
    </style>

    @yield('styles')
</head>

<body>
    <!-- Navigation -->
    <nav class="site-nav">
        <div class="container">
            {{-- Brand --}}
            <a class="nav-brand" href="{{ route('categories.index') }}">
                <i class="fas fa-play-circle"></i> Video Store
            </a>

            {{-- Desktop links --}}
            <div class="nav-desktop" style="display:flex;align-items:center;gap:2px;">

                {{-- Explore dropdown --}}
                <div class="nav-dropdown" id="exploreDropdown">
                    <button class="nav-link-item" onclick="toggleDropdown('exploreMenu')">
                        <i class="fas fa-compass"></i> Explore <i class="fas fa-chevron-down" style="font-size:.6rem;opacity:.6;"></i>
                    </button>
                    <div class="nav-dropdown-menu" id="exploreMenu">
                        <a class="nav-dropdown-item" href="{{ route('categories.index') }}">
                            <i class="fas fa-store"></i> Ver creadores
                        </a>
                    </div>
                </div>

                {{-- Bot link --}}
                @if($bot['is_configured'])
                    <a class="nav-link-item" href="{{ $bot['url'] }}" target="_blank">
                        <i class="fab fa-telegram"></i> Start Bot Chat
                    </a>
                @else
                    <a class="nav-link-item" href="{{ route('login') }}">
                        <i class="fas fa-cog" style="color:#f59e0b;"></i> Setup Required
                    </a>
                @endif
            </div>

            {{-- Right side --}}
            <div class="nav-right nav-desktop">
                @auth
                    {{-- User dropdown --}}
                    <div class="nav-dropdown" id="userDropdown">
                        <button class="nav-link-item" onclick="toggleDropdown('userMenu')">
                            <i class="fas fa-user-circle" style="color:#4f8ef7;font-size:.95rem;"></i>
                            {{ Auth::user()->name }}
                            <i class="fas fa-chevron-down" style="font-size:.6rem;opacity:.6;"></i>
                        </button>
                        <div class="nav-dropdown-menu" id="userMenu" style="right:0;left:auto;">
                            <a class="nav-dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="fas fa-user-pen"></i> Perfil
                            </a>

                            @if(Auth::user()->is_admin)
                                <a class="nav-dropdown-item" href="{{ route('admin.my-store.index') }}">
                                    <i class="fas fa-store"></i> Mi Tienda
                                </a>
                                <div class="nav-divider"></div>
                                <a class="nav-dropdown-item" href="{{ route('admin.videos.manage') }}">
                                    <i class="fas fa-video"></i> Videos
                                </a>
                                <a class="nav-dropdown-item" href="{{ route('admin.purchases.index') }}">
                                    <i class="fas fa-money-bill-wave"></i> Compras
                                </a>
                                <a class="nav-dropdown-item" href="{{ route('admin.categories.manage') }}">
                                    <i class="fas fa-layer-group"></i> Categorías
                                </a>
                                <a class="nav-dropdown-item" href="{{ route('admin.discount-codes.index') }}">
                                    <i class="fas fa-tag"></i> Descuentos
                                </a>
                                <a class="nav-dropdown-item" href="{{ route('admin.iptv.index') }}">
                                    <i class="fas fa-tv"></i> IPTV
                                </a>
                                <a class="nav-dropdown-item" href="{{ route('admin.bot-manager.index') }}">
                                    <i class="fas fa-robot"></i> Bot Manager
                                </a>
                            @elseif(Auth::user()->is_creator && Auth::user()->subscribed('creator'))
                                <a class="nav-dropdown-item" href="{{ route('creator.dashboard') }}">
                                    <i class="fas fa-store"></i> Panel Creador
                                </a>
                            @else
                                <a class="nav-dropdown-item" href="{{ route('creator.subscription.show') }}">
                                    <i class="fas fa-user-plus"></i> Hazte Creador
                                </a>
                            @endif

                            <div class="nav-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-dropdown-item danger" style="width:100%;text-align:left;">
                                    <i class="fas fa-right-from-bracket"></i> Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('logincreator') }}" class="nav-btn-ghost">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="nav-btn-accent">Ser creador</a>
                @endauth

                {{-- Theme toggle --}}
                <button type="button" class="nav-theme-btn" id="theme-toggle-bootstrap" title="Cambiar tema">
                    <i class="fas fa-moon" id="theme-icon-moon-bootstrap"></i>
                    <i class="fas fa-sun d-none" id="theme-icon-sun-bootstrap"></i>
                </button>
            </div>

            {{-- Mobile toggler --}}
            <button class="nav-toggler" onclick="toggleMobileMenu()">
                <i class="fas fa-bars" id="mobileMenuIcon"></i>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div class="nav-mobile-menu" id="mobileMenu">
            <a class="nav-mobile-item" href="{{ route('categories.index') }}">
                <i class="fas fa-store"></i> Ver creadores
            </a>
            @if($bot['is_configured'])
                <a class="nav-mobile-item" href="{{ $bot['url'] }}" target="_blank">
                    <i class="fab fa-telegram"></i> Start Bot Chat
                </a>
            @endif
            @auth
                <div class="nav-mobile-divider"></div>
                <a class="nav-mobile-item" href="{{ route('profile.edit') }}">
                    <i class="fas fa-user-pen"></i> Perfil
                </a>
                @if(Auth::user()->is_admin)
                    <a class="nav-mobile-item" href="{{ route('admin.my-store.index') }}">
                        <i class="fas fa-store"></i> Mi Tienda
                    </a>
                    <a class="nav-mobile-item" href="{{ route('admin.videos.manage') }}">
                        <i class="fas fa-video"></i> Videos
                    </a>
                    <a class="nav-mobile-item" href="{{ route('admin.purchases.index') }}">
                        <i class="fas fa-money-bill-wave"></i> Compras
                    </a>
                    <a class="nav-mobile-item" href="{{ route('admin.bot-manager.index') }}">
                        <i class="fas fa-robot"></i> Bot Manager
                    </a>
                @elseif(Auth::user()->is_creator && Auth::user()->subscribed('creator'))
                    <a class="nav-mobile-item" href="{{ route('creator.dashboard') }}">
                        <i class="fas fa-store"></i> Panel Creador
                    </a>
                @endif
                <div class="nav-mobile-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-mobile-item" style="width:100%;background:none;border:none;text-align:left;color:rgba(239,68,68,.85);">
                        <i class="fas fa-right-from-bracket" style="color:#ef4444;"></i> Cerrar sesión
                    </button>
                </form>
            @else
                <div class="nav-mobile-divider"></div>
                <a class="nav-mobile-item" href="{{ route('logincreator') }}">
                    <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                </a>
                <a class="nav-mobile-item" href="{{ route('register') }}">
                    <i class="fas fa-user-plus"></i> Ser creador
                </a>
            @endauth
            <div class="nav-mobile-divider"></div>
            <button type="button" class="nav-mobile-item" id="theme-toggle-bootstrap" style="background:none;border:none;width:100%;text-align:left;">
                <i class="fas fa-moon" id="theme-icon-moon-bootstrap"></i>
                <i class="fas fa-sun d-none" id="theme-icon-sun-bootstrap"></i>
                <span id="themeLabel">Modo oscuro</span>
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mt-4">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="py-4">
        <div class="container text-center">
            <p class="mb-0 text-muted">
                <i class="fas fa-play-circle"></i> Video Store - Instant Telegram Delivery
                &copy; {{ date('Y') }}
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        (() => {
            'use strict'

            const getStoredTheme = () => localStorage.getItem('theme')
            const setStoredTheme = theme => localStorage.setItem('theme', theme)

            const getPreferredTheme = () => {
                const storedTheme = getStoredTheme()
                if (storedTheme) {
                    return storedTheme
                }

                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            }

            const setTheme = theme => {
                if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-bs-theme', 'dark')
                } else {
                    document.documentElement.setAttribute('data-bs-theme', theme)
                }
            }

            setTheme(getPreferredTheme())

            const showActiveTheme = (theme) => {
                const themeToggle = document.querySelector('#theme-toggle-bootstrap')
                if (!themeToggle) {
                    return
                }
                const moonIcon = document.querySelector('#theme-icon-moon-bootstrap')
                const sunIcon = document.querySelector('#theme-icon-sun-bootstrap')

                if (theme === 'dark') {
                    moonIcon.classList.add('d-none');
                    sunIcon.classList.remove('d-none');
                } else {
                    moonIcon.classList.remove('d-none');
                    sunIcon.classList.add('d-none');
                }
            }

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                const storedTheme = getStoredTheme()
                if (!storedTheme || storedTheme === 'auto') {
                    setTheme(getPreferredTheme())
                }
            })

            window.addEventListener('DOMContentLoaded', () => {
                showActiveTheme(getPreferredTheme())

                const themeToggle = document.querySelector('#theme-toggle-bootstrap');
                if (themeToggle) {
                    themeToggle.addEventListener('click', () => {
                        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || getPreferredTheme();
                        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                        setStoredTheme(newTheme);
                        setTheme(newTheme);
                        showActiveTheme(newTheme);
                    });
                }
            })
        })()
    </script>

    <!-- Toast container -->
    <div id="toastContainer"></div>

    <script>
    function showToast(message, type, duration) {
        duration = duration || 10000;
        var colors = {
            success: { icon: 'fa-check-circle',       color: '#198754' },
            error:   { icon: 'fa-exclamation-circle', color: '#dc3545' },
            warning: { icon: 'fa-exclamation-triangle',color: '#fd7e14' },
            info:    { icon: 'fa-info-circle',         color: '#0dcaf0' },
        };
        var c = colors[type] || colors.success;
        var toast = document.createElement('div');
        toast.className = 'tl-toast';
        toast.innerHTML =
            '<div class="tl-toast-body">' +
                '<i class="fas ' + c.icon + ' tl-toast-icon" style="color:' + c.color + '"></i>' +
                '<span class="tl-toast-msg">' + message + '</span>' +
                '<button class="tl-toast-close" onclick="dismissToast(this.closest(\'.tl-toast\'))">' +
                    '<i class="fas fa-times"></i>' +
                '</button>' +
            '</div>' +
            '<div class="tl-toast-progress">' +
                '<div class="tl-toast-bar" style="background:' + c.color + '"></div>' +
            '</div>';
        document.getElementById('toastContainer').appendChild(toast);
        // Animate progress bar
        var bar = toast.querySelector('.tl-toast-bar');
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                bar.style.transition = 'width ' + duration + 'ms linear';
                bar.style.width = '0%';
            });
        });
        toast._timer = setTimeout(function() { dismissToast(toast); }, duration);
    }

    function dismissToast(toast) {
        if (!toast || toast._dismissing) return;
        toast._dismissing = true;
        clearTimeout(toast._timer);
        toast.classList.add('dismissing');
        setTimeout(function() { toast.remove(); }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
            showToast(@json(session('success')), 'success');
        @endif
        @if(session('error'))
            showToast(@json(session('error')), 'error');
        @endif
        @if($errors->any())
            showToast(@json($errors->first()), 'error');
        @endif
    });
    </script>

    @yield('scripts')

    <script>
    // ── Custom navbar dropdowns ──────────────────────────────────────
    function toggleDropdown(id) {
        const menu = document.getElementById(id);
        const isOpen = menu.classList.contains('open');
        // Close all
        document.querySelectorAll('.nav-dropdown-menu').forEach(m => m.classList.remove('open'));
        document.querySelectorAll('.nav-link-item').forEach(b => b.classList.remove('show'));
        if (!isOpen) {
            menu.classList.add('open');
            menu.previousElementSibling?.classList.add('show');
        }
    }
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const icon = document.getElementById('mobileMenuIcon');
        menu.classList.toggle('open');
        icon.className = menu.classList.contains('open') ? 'fas fa-times' : 'fas fa-bars';
    }
    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown') && !e.target.closest('#theme-toggle-bootstrap')) {
            document.querySelectorAll('.nav-dropdown-menu').forEach(m => m.classList.remove('open'));
            document.querySelectorAll('.nav-link-item').forEach(b => b.classList.remove('show'));
        }
    });
    </script>
</body>

</html>
