<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — {{ config('app.name', 'CRM') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh; display: flex;
            background: #f1f5f9;
        }

        /* ── Left panel ── */
        .panel-left {
            flex: 0 0 480px;
            background: linear-gradient(145deg, #0f172a 0%, #1e3a5f 60%, #2563eb 100%);
            display: flex; flex-direction: column;
            justify-content: center; align-items: flex-start;
            padding: 3rem 3.5rem;
            position: relative; overflow: hidden;
        }
        .panel-left::before {
            content: '';
            position: absolute; top: -80px; right: -80px;
            width: 320px; height: 320px; border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .panel-left::after {
            content: '';
            position: absolute; bottom: -120px; left: -60px;
            width: 420px; height: 420px; border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 3rem; position: relative; z-index: 1; }
        .brand-icon {
            width: 44px; height: 44px; background: #2563eb; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 1.1rem;
            border: 2px solid rgba(255,255,255,0.15);
        }
        .brand-name { color: #fff; font-weight: 700; font-size: 1.2rem; }
        .hero-text { position: relative; z-index: 1; }
        .hero-text h1 { color: #fff; font-size: 2rem; font-weight: 700; line-height: 1.25; margin-bottom: 1rem; }
        .hero-text p { color: #94a3b8; font-size: 0.95rem; line-height: 1.65; max-width: 340px; }
        .features { margin-top: 2.5rem; display: flex; flex-direction: column; gap: 0.75rem; position: relative; z-index: 1; }
        .feature-item { display: flex; align-items: center; gap: 10px; color: #cbd5e1; font-size: 0.875rem; }
        .feature-dot { width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; flex-shrink: 0; }

        /* ── Right panel ── */
        .panel-right {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 2rem;
        }
        .login-card {
            background: #fff; border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 60px -20px rgba(0,0,0,0.12);
            padding: 2.5rem; width: 100%; max-width: 400px;
        }
        .login-card h2 { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin-bottom: 0.4rem; }
        .login-card .subtitle { color: #64748b; font-size: 0.9rem; margin-bottom: 2rem; }

        .form-group { display: flex; flex-direction: column; gap: 0.35rem; margin-bottom: 1.1rem; }
        .form-label { font-size: 0.875rem; font-weight: 600; color: #374151; }
        .form-input {
            width: 100%; padding: 0.7rem 0.9rem;
            border: 1.5px solid #d1d5db; border-radius: 8px;
            font-size: 0.95rem; outline: none; color: #0f172a;
            font-family: inherit;
            transition: border-color 140ms, box-shadow 140ms;
        }
        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .form-input.is-error { border-color: #ef4444; }

        .error-box {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
            padding: 0.75rem 1rem; margin-bottom: 1.25rem;
            color: #b91c1c; font-size: 0.875rem; display: flex; align-items: center; gap: 8px;
        }
        .error-box svg { flex-shrink: 0; width: 16px; height: 16px; }

        .submit-btn {
            width: 100%; padding: 0.75rem; border: none; border-radius: 8px;
            background: #2563eb; color: #fff; font-weight: 700;
            font-size: 0.95rem; cursor: pointer; margin-top: 0.5rem;
            font-family: inherit;
            transition: background 140ms, transform 100ms;
        }
        .submit-btn:hover { background: #1d4ed8; }
        .submit-btn:active { transform: translateY(1px); }

        .login-footer { text-align: center; margin-top: 1.25rem; }
        .login-footer a { color: #2563eb; font-size: 0.875rem; text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .panel-left { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body>

    <!-- Left branding panel -->
    <div class="panel-left">
        <div class="brand">
            <div class="brand-icon">CR</div>
            <div class="brand-name">{{ config('app.name', 'CRM') }}</div>
        </div>
        <div class="hero-text">
            <h1>Manage your Events, smarter.</h1>
            <p>A complete CRM platform to manage events, speakers, and deals — all in one place.</p>
        </div>
        <div class="features">

            <div class="feature-item"><span class="feature-dot"></span> Events, Speakers</div>
            <div class="feature-item"><span class="feature-dot"></span> Real-time Analytics</div>
        </div>
    </div>

    <!-- Right login panel -->
    <div class="panel-right">
        <div class="login-card">
            <h2>Welcome back</h2>
            <p class="subtitle">Sign in to your admin account</p>

            @if($errors->any())
                <div class="error-box">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        id="email" type="email" name="email"
                        value="{{ old('email') }}"
                        class="form-input {{ $errors->has('email') ? 'is-error' : '' }}"
                        placeholder="admin@example.com"
                        required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input
                        id="password" type="password" name="password"
                        class="form-input {{ $errors->has('password') ? 'is-error' : '' }}"
                        placeholder="••••••••"
                        required>
                </div>

                <button type="submit" class="submit-btn">Sign in to Dashboard</button>
            </form>

            <div class="login-footer">
                <a href="/">← Back to site</a>
            </div>
        </div>
    </div>

</body>
</html>
