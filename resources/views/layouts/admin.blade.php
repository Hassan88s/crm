<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />
        <style>
            :root { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif; color: #111827; }
            body { margin: 0; min-height: 100vh; background: #f3f4f6; }
            .container { max-width: 1024px; margin: 0 auto; padding: 1.5rem; }
            .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 10px 25px -12px rgba(0,0,0,0.1); }
            .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.65rem 1rem; border-radius: 0.5rem; border: none; font-weight: 600; cursor: pointer; transition: transform 120ms ease, box-shadow 120ms ease; background: #2563eb; color: #fff; }
            .btn:active { transform: translateY(1px); box-shadow: inset 0 1px 0 rgba(0,0,0,0.05); }
            a { color: #2563eb; text-decoration: none; }
        </style>
    @endif
</head>
<body class="bg-gray-100 text-gray-900">
    <header class="bg-white border-b border-gray-200">
        <div class="container" style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
            <div style="display:flex; align-items:center; gap:0.5rem; font-weight:700; letter-spacing:-0.01em;">
                <span style="display:inline-flex; width:34px; height:34px; border-radius:0.65rem; background:#2563eb; color:#fff; align-items:center; justify-content:center; font-size:0.95rem;">EM</span>
                <span>{{ config('app.name', 'ExpenseManage') }}</span>
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem; font-size:0.95rem;">
                <a href="/" style="color:#4b5563;">Home</a>
                @auth
                    <form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn" style="background:#ef4444;">Logout</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main class="container" style="padding-top:2.5rem; padding-bottom:2.5rem;">
        @yield('content')
    </main>
</body>
</html>
