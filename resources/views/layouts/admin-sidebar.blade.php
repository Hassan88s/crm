<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — {{ config('app.name', 'CRM') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --sidebar-w: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --accent: #2563eb;
            --accent-light: #eff6ff;
            --text-muted: #94a3b8;
            font-family: 'Inter', system-ui, sans-serif;
        }
        body { display: flex; min-height: 100vh; background: #f1f5f9; color: #0f172a; }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            z-index: 50;
        }
        .sidebar-logo {
            display: flex; align-items: center; gap: 10px;
            padding: 1.5rem 1.25rem 1rem;
            border-bottom: 1px solid #1e293b;
        }
        .sidebar-logo .logo-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: var(--accent); display: flex; align-items: center;
            justify-content: center; color: #fff; font-weight: 700; font-size: 1rem;
            flex-shrink: 0;
        }
        .sidebar-logo .logo-text { color: #fff; font-weight: 700; font-size: 1.05rem; letter-spacing: -0.01em; }
        .sidebar-logo .logo-sub { color: var(--text-muted); font-size: 0.7rem; margin-top: 1px; }

        .sidebar-nav { flex: 1; padding: 1rem 0.75rem; overflow-y: auto; }
        .nav-section-label {
            color: #475569; font-size: 0.68rem; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase;
            padding: 0.5rem 0.5rem 0.35rem;
        }
        .nav-item {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.6rem 0.75rem; border-radius: 0.5rem;
            color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 500;
            transition: background 140ms, color 140ms;
            margin-bottom: 2px; cursor: pointer;
        }
        .nav-item:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .nav-item.active { background: var(--accent); color: #fff; }
        .nav-item.active svg { opacity: 1; }
        .nav-item svg { width: 18px; height: 18px; opacity: 0.6; flex-shrink: 0; }
        .nav-item.active svg { opacity: 1; }
        .nav-badge {
            margin-left: auto; background: #ef4444; color: #fff;
            font-size: 0.68rem; font-weight: 700; padding: 1px 6px;
            border-radius: 999px;
        }

        .sidebar-footer {
            padding: 0.75rem; border-top: 1px solid #1e293b;
        }
        .user-card {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.6rem 0.75rem; border-radius: 0.5rem;
            background: #1e293b;
        }
        .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem; flex-shrink: 0;
        }
        .user-name { color: #e2e8f0; font-size: 0.85rem; font-weight: 600; }
        .user-role { color: var(--text-muted); font-size: 0.72rem; }
        .logout-btn {
            margin-left: auto; background: none; border: none; cursor: pointer;
            color: #64748b; padding: 4px; border-radius: 6px; transition: color 140ms;
            display: flex; align-items: center;
        }
        .logout-btn:hover { color: #ef4444; }

        /* ── Main ── */
        .main-wrap {
            margin-left: var(--sidebar-w);
            flex: 1; display: flex; flex-direction: column; min-height: 100vh;
        }
        .topbar {
            position: sticky; top: 0; z-index: 40;
            background: #fff; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; gap: 1rem;
            padding: 0 1.75rem; height: 60px;
        }
        .topbar-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; flex: 1; }
        .topbar-actions { display: flex; align-items: center; gap: 0.5rem; }
        .icon-btn {
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e2e8f0;
            background: #fff; display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #64748b; transition: background 140ms;
        }
        .icon-btn:hover { background: #f8fafc; color: #0f172a; }
        .icon-btn svg { width: 18px; height: 18px; }

        .main-content { flex: 1; padding: 2rem 1.75rem; }

        /* ── Cards & Utilities ── */
        .card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 0.55rem 1.1rem; border-radius: 8px; border: none;
            font-weight: 600; font-size: 0.875rem; cursor: pointer;
            transition: filter 120ms, transform 100ms;
            background: var(--accent); color: #fff; text-decoration: none;
        }
        .btn:hover { filter: brightness(1.08); }
        .btn:active { transform: translateY(1px); }
        .btn-outline {
            background: #fff; color: #374151; border: 1px solid #d1d5db;
        }
        .btn-outline:hover { background: #f9fafb; filter: none; }
        .btn-danger { background: #ef4444; }

        a { text-decoration: none; }

        /* ── Mobile hamburger ── */
        .hamburger {
            display: none;
            width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e2e8f0;
            background: #fff; align-items: center; justify-content: center;
            cursor: pointer; color: #64748b; flex-shrink: 0;
        }
        .hamburger svg { width: 20px; height: 20px; }

        /* ── Mobile overlay ── */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 45;
        }
        .sidebar-overlay.active { display: block; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 250ms ease;
            }
            .sidebar.open { transform: translateX(0); }
            .main-wrap { margin-left: 0; }
            .hamburger { display: flex; }
            .topbar { padding: 0 1rem; }
            .main-content { padding: 1.25rem 1rem; }
        }
    </style>
    @yield('extra-styles')
</head>
<body>

<!-- ═══════════════════ SIDEBAR ═══════════════════ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">PC</div>
        <div>
            <div class="logo-text">{{ config('app.name') }}</div>
            <div class="logo-sub">Speaker Management</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <div class="nav-section-label" style="margin-top:1rem;">Events</div>

        <a href="{{ route('admin.events.index') }}"
           class="nav-item {{ request()->routeIs('admin.events*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Events
        </a>

        <a href="{{ route('admin.speakers.index') }}"
           class="nav-item {{ request()->routeIs('admin.speakers*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            Speakers
        </a>

        <div class="nav-section-label" style="margin-top:1rem;">Communication</div>

        <a href="{{ route('admin.emails.index') }}"
           class="nav-item {{ request()->routeIs('admin.emails*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Send Invites
        </a>

      <!--   <a href="{{ route('admin.smtp-accounts.index') }}"
           class="nav-item {{ request()->routeIs('admin.smtp-accounts*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7zm0 0l8 6 8-6"/>
            </svg>
            SMTP Accounts
        </a>

        <a href="{{ route('admin.imap-accounts.index') }}"
           class="nav-item {{ request()->routeIs('admin.imap-accounts*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
            IMAP Accounts
        </a> -->

       <a href="{{ route('admin.inbox.index') }}"
           class="nav-item {{ request()->routeIs('admin.inbox*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            Inbox
        </a>

        <a href="{{ route('admin.replies.index') }}"
           class="nav-item {{ request()->routeIs('admin.replies*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Classified Replies
        </a>

        <a href="{{ route('admin.scraper.index') }}"
           class="nav-item {{ request()->routeIs('admin.scraper*') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Web Scraper
        </a>

        <div class="nav-section-label" style="margin-top:1rem;">System</div>

        <a href="{{ route('admin.requirements') }}"
           class="nav-item {{ request()->routeIs('admin.requirements') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Prerequisites
        </a>

        <a href="{{ route('admin.settings') }}"
           class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="avatar">{{ strtoupper(substr(Auth::user()->name ?? Auth::user()->email ?? 'A', 0, 1)) }}</div>
            <div>
                <div class="user-name">{{ Auth::user()->name ?? 'Admin' }}</div>
                <div class="user-role">Administrator</div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="logout-btn" title="Logout">
                    <svg width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
        <div style="padding:0.6rem 1rem 0.75rem; border-top:1px solid #1e293b; text-align:center;">
            <span style="font-size:0.68rem; color:#475569; letter-spacing:0.03em;">
                PulseCore <span style="background:#1e293b; color:#94a3b8; padding:1px 6px; border-radius:4px; font-weight:600;">V1.1</span>
                &nbsp;·&nbsp; Developed by <span style="color:#64748b; font-weight:600;">SamiCodes</span>
            </span>
        </div>
    </div>
</aside>

<!-- ═══════════════════ OVERLAY ═══════════════════ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ═══════════════════ MAIN ═══════════════════ -->
<div class="main-wrap">
    <header class="topbar">
        <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()" title="Menu">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        <div class="topbar-actions">
            <button class="icon-btn" title="Notifications">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </button>
            <button class="icon-btn" title="Search">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
            <form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-danger" style="font-size:0.82rem; padding:0.45rem 0.9rem;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </header>

    <main class="main-content">
        @yield('content')
    </main>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
// Close sidebar when a nav link is clicked (mobile)
document.querySelectorAll('.nav-item').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            document.querySelector('.sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
    });
});
</script>
</body>
</html>
