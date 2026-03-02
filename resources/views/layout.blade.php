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

    <style>
        /* Sticky footer using flexbox */
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; }
        main { flex: 1; }
        footer { margin-top: auto; }
        #theme-toggle { color: white; }
        #theme-toggle:hover { color: #f8f9fa; }

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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('categories.index') }}">
                <i class="fas fa-play-circle"></i> Video Store
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    {{-- Videos and Purchases Dropdown --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="exploreDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-compass"></i> Explore
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="exploreDropdown">
                            <li><a class="dropdown-item" href="{{ route('categories.index') }}">
                                <i class="fas fa-store"></i> Ver creadores
                            </a></li>
                            @auth
                            {{-- <li><a class="dropdown-item" href="{{ route('purchases.index') }}">
                                <i class="fas fa-shopping-cart"></i> My Purchases
                            </a></li> --}}
                            @endauth
                        </ul>
                    </li>
                    {{-- End Videos and Purchases Dropdown --}}

                    @if($bot['is_configured'])
                    <li class="nav-item">
                            <a class="nav-link" href="{{ $bot['url'] }}" target="_blank">
                            <i class="fab fa-telegram"></i> Start Bot Chat
                        </a>
                    </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}" title="Configure bot in admin panel">
                                <i class="fas fa-cog text-warning"></i> Setup Required
                            </a>
                        </li>
                    @endif
                </ul>

                {{-- Move Theme Toggle Button to ms-auto (right side) --}}
                @auth
                <div class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> {{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu">
                                <li></li>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                        <i class="fas fa-user-edit"></i> Profile
                                    </a>
                                </li>
                                @if (Auth::user()->is_admin)
                                <li>
                                    <a class="dropdown-item" href="{{ route('creator.dashboard') }}">
                                        <i class="fas fa-store"></i> Panel Creador
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.videos.manage') }}">
                                        <i class="fas fa-video"></i> Admin Videos
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.categories.manage') }}">
                                        <i class="fas fa-layer-group"></i> Admin Categorías
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.purchases.index') }}">
                                        <i class="fas fa-money-bill-wave"></i> Admin Compras
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.iptv.index') }}">
                                        <i class="fas fa-tv"></i> Gestión IPTV
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.bot-manager.index') }}">
                                        <i class="fas fa-robot"></i> Bot Manager
                                    </a>
                                </li>
                                @elseif (Auth::user()->is_creator && Auth::user()->subscribed('creator'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('creator.dashboard') }}">
                                        <i class="fas fa-store"></i> Panel Creador
                                    </a>
                                </li>
                                @else
                                <li>
                                    <a class="dropdown-item" href="{{ route('creator.subscription.show') }}">
                                        <i class="fas fa-user-plus"></i> Hazte Creador ($5/mes)
                                    </a>
                                </li>
                                @endif
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                        {{-- Theme Toggle Button as last item --}}
                        <button type="button" class="btn nav-link" id="theme-toggle-bootstrap">
                            <i class="fas fa-moon" id="theme-icon-moon-bootstrap"></i>
                            <i class="fas fa-sun d-none" id="theme-icon-sun-bootstrap"></i>
                        </button>
                    </ul>
                    @else {{-- Guest actions --}}
                        <div class="d-flex align-items-center ms-auto gap-2">
                            <a href="{{ route('logincreator') }}" class="btn btn-outline-light btn-sm">Login</a>
                            <a href="{{ route('register') }}" class="btn btn-warning btn-sm">Quiero ser un creador</a>
                            <button type="button" class="btn nav-link" id="theme-toggle-bootstrap">
                                <i class="fas fa-moon" id="theme-icon-moon-bootstrap"></i>
                                <i class="fas fa-sun d-none" id="theme-icon-sun-bootstrap"></i>
                            </button>
                        </div>
                    @endauth
            </div>
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
</body>

</html>
