@extends('layouts.admin-sidebar')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('extra-styles')
<style>
    .settings-grid { display:grid; grid-template-columns:220px 1fr; gap:1.5rem; align-items:start; }

    /* Side nav */
    .settings-nav { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:0.6rem; position:sticky; top:80px; }
    .settings-nav-item {
        display:flex; align-items:center; gap:8px;
        padding:0.6rem 0.85rem; border-radius:8px;
        font-size:0.875rem; font-weight:500; color:#64748b;
        cursor:pointer; text-decoration:none; transition:all 140ms;
    }
    .settings-nav-item:hover { background:#f8fafc; color:#0f172a; }
    .settings-nav-item.active { background:#eff6ff; color:#2563eb; font-weight:600; }
    .settings-nav-item svg { width:16px; height:16px; }

    /* Sections */
    .settings-section { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:1.25rem; overflow:hidden; }
    .section-head { padding:1.1rem 1.5rem; border-bottom:1px solid #f1f5f9; }
    .section-head h3 { font-size:0.95rem; font-weight:700; color:#0f172a; margin-bottom:2px; }
    .section-head p  { font-size:0.8rem; color:#94a3b8; }
    .section-body { padding:1.5rem; }

    /* Form elements */
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
    .form-row.single { grid-template-columns:1fr; }
    .form-group { display:flex; flex-direction:column; gap:0.4rem; }
    .form-label { font-size:0.85rem; font-weight:600; color:#374151; }
    .form-hint  { font-size:0.75rem; color:#94a3b8; margin-top:1px; }
    .form-input, .form-select {
        padding:0.65rem 0.9rem; border:1.5px solid #d1d5db; border-radius:8px;
        font-size:0.875rem; outline:none; color:#0f172a; font-family:inherit; background:#fff;
        transition:border-color 140ms, box-shadow 140ms;
    }
    .form-input:focus, .form-select:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
    .form-input.is-error { border-color:#ef4444; }
    .error-msg { font-size:0.78rem; color:#dc2626; }

    /* Password toggle */
    .input-wrap { position:relative; }
    .input-wrap .form-input { padding-right:2.5rem; width:100%; }
    .eye-btn { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; padding:2px; }
    .eye-btn:hover { color:#374151; }
    .eye-btn svg { width:17px; height:17px; display:block; }

    /* Alerts */
    .alert { display:flex; align-items:center; gap:8px; padding:0.75rem 1rem; border-radius:8px; font-size:0.875rem; font-weight:500; margin-bottom:1rem; }
    .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
    .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }
    .alert svg { width:16px; height:16px; flex-shrink:0; }

    .form-footer { display:flex; align-items:center; gap:0.75rem; padding-top:1.25rem; border-top:1px solid #f1f5f9; margin-top:1.25rem; }

    /* Password strength */
    .strength-bar { display:flex; gap:3px; margin-top:6px; }
    .strength-seg { height:4px; border-radius:999px; flex:1; background:#e2e8f0; transition:background 200ms; }
    .strength-label { font-size:0.72rem; color:#94a3b8; margin-top:3px; }
</style>
@endsection

@section('content')

<div style="margin-bottom:1.5rem;">
    <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Settings</h1>
    <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">Manage your PulseCore configuration</p>
</div>

<div class="settings-grid">

    {{-- Side nav --}}
    <div class="settings-nav">
        <a href="#general" class="settings-nav-item active" onclick="scrollTo('#general',this)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            General
        </a>
        <a href="#smtp" class="settings-nav-item" onclick="scrollTo('#smtp',this)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
            </svg>
            SMTP / Email
        </a>
        <a href="#imap" class="settings-nav-item" onclick="scrollTo('#imap',this)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            IMAP Inbox
        </a>
        <a href="#ai" class="settings-nav-item" onclick="scrollTo('#ai',this)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            AI (OpenAI)
        </a>
        <a href="#email" class="settings-nav-item" onclick="scrollTo('#email',this)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Admin Email
        </a>
        <a href="#password" class="settings-nav-item" onclick="scrollTo('#password',this)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Password
        </a>
        <a href="#api-keys" class="settings-nav-item" onclick="scrollTo('#api-keys',this)">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a4 4 0 11-4 4m4-4a4 4 0 00-4 4m4-4V3m-4 8H3m8 0v10"/>
            </svg>
            API Keys
        </a>
    </div>

    {{-- Forms --}}
    <div>

        {{-- ── General ── --}}
        <div class="settings-section" id="general">
            <div class="section-head">
                <h3>General Settings</h3>
                <p>Update your platform name and timezone.</p>
            </div>
            <div class="section-body">
                @if(session('success_general'))
                    <div class="alert alert-success">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success_general') }}
                    </div>
                @endif

                <form action="{{ route('admin.settings.general') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Company / Platform Name</label>
                            <span class="form-hint">Displayed in the sidebar and browser tab</span>
                            <input type="text" name="app_name"
                                   value="{{ old('app_name', $envName) }}"
                                   class="form-input {{ $errors->has('app_name') ? 'is-error' : '' }}"
                                   placeholder="PulseCore">
                            @error('app_name') <span class="error-msg">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Timezone</label>
                            <span class="form-hint">Used for scheduling and display</span>
                            <select name="timezone" class="form-select">
                                @foreach([
                                    'UTC'                => 'UTC',
                                    'America/New_York'   => 'America / New York (EST)',
                                    'America/Chicago'    => 'America / Chicago (CST)',
                                    'America/Los_Angeles'=> 'America / Los Angeles (PST)',
                                    'Europe/London'      => 'Europe / London (GMT)',
                                    'Europe/Paris'       => 'Europe / Paris (CET)',
                                    'Europe/Prague'      => 'Europe / Prague (CET)',
                                    'Europe/Berlin'      => 'Europe / Berlin (CET)',
                                    'Asia/Dubai'         => 'Asia / Dubai (GST)',
                                    'Asia/Singapore'     => 'Asia / Singapore (SGT)',
                                    'Asia/Tokyo'         => 'Asia / Tokyo (JST)',
                                    'Australia/Sydney'   => 'Australia / Sydney (AEDT)',
                                ] as $tz => $label)
                                    <option value="{{ $tz }}" {{ old('timezone', $envTimezone) === $tz ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn">Save General Settings</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── SMTP / Email Server ── --}}
        <div class="settings-section" id="smtp">
            <div class="section-head">
                <h3>SMTP Email Settings</h3>
                <p>Configure the outgoing mail server used for sending emails to speakers.</p>
            </div>
            <div class="section-body">
                @if(session('success_smtp'))
                    <div class="alert alert-success">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success_smtp') }}
                    </div>
                @endif
                @if(session('error_smtp'))
                    <div class="alert alert-error">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ session('error_smtp') }}
                    </div>
                @endif

                <form action="{{ route('admin.settings.smtp') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">SMTP Host</label>
                            <span class="form-hint">e.g. smtp.gmail.com, smtp.sendgrid.net</span>
                            <input type="text" name="mail_host" value="{{ old('mail_host', $mailHost) }}"
                                   class="form-input" placeholder="smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">SMTP Port</label>
                            <span class="form-hint">Usually 587 (TLS) or 465 (SSL)</span>
                            <input type="number" name="mail_port" value="{{ old('mail_port', $mailPort) }}"
                                   class="form-input" placeholder="587">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username / Email</label>
                            <input type="text" name="mail_username" value="{{ old('mail_username', $mailUsername) }}"
                                   class="form-input" placeholder="you@gmail.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password / App Password</label>
                            <div class="input-wrap">
                                <input type="password" name="mail_password" id="smtp-pwd"
                                       value="{{ old('mail_password', $mailPassword) }}"
                                       class="form-input" placeholder="••••••••••••">
                                <button type="button" class="eye-btn" onclick="togglePwd('smtp-pwd','smtp-eye')">
                                    <svg id="smtp-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Encryption</label>
                            <select name="mail_encryption" class="form-select">
                                <option value="tls" {{ $mailEncryption === 'tls' ? 'selected' : '' }}>TLS (Recommended)</option>
                                <option value="ssl" {{ $mailEncryption === 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="none" {{ !$mailEncryption ? 'selected' : '' }}>None</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">From Email Address</label>
                            <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $mailFromAddress) }}"
                                   class="form-input" placeholder="noreply@yourcompany.com">
                        </div>
                    </div>
                    <div class="form-row single">
                        <div class="form-group">
                            <label class="form-label">From Name</label>
                            <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $mailFromName) }}"
                                   class="form-input" placeholder="PulseCore">
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn">Save SMTP Settings</button>
                    </div>
                </form>

                {{-- Test email --}}
                <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid #f1f5f9;">
                    <div style="font-weight:600; font-size:0.875rem; color:#374151; margin-bottom:0.5rem;">Test Connection</div>
                    <form action="{{ route('admin.settings.smtp.test') }}" method="POST" style="display:flex; gap:0.6rem; align-items:center;">
                        @csrf
                        <input type="email" name="test_email" placeholder="Send test to this email…"
                               class="form-input" style="max-width:280px;" required>
                        <button type="submit" class="btn btn-outline" style="white-space:nowrap;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Send Test
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ── IMAP Inbox ── --}}
        <div class="settings-section" id="imap">
            <div class="section-head">
                <h3>IMAP Inbox Settings</h3>
                <p>Connect your email inbox to receive and read emails inside PulseCore.</p>
            </div>
            <div class="section-body">
                @if(session('success_imap'))
                    <div class="alert alert-success" style="margin-bottom:1.25rem;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success_imap') }}
                    </div>
                @endif
                @if(session('error_imap'))
                    <div class="alert alert-error" style="margin-bottom:1.25rem;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ session('error_imap') }}
                    </div>
                @endif

                <form action="{{ route('admin.settings.imap') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">IMAP Host</label>
                            <span class="form-hint">e.g. imap.gmail.com, imap.outlook.com</span>
                            <input type="text" name="imap_host" value="{{ old('imap_host', $imapHost) }}"
                                   class="form-input" placeholder="imap.gmail.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">IMAP Port</label>
                            <select name="imap_port" class="form-select">
                                @foreach(['993' => '993 (SSL)', '143' => '143 (TLS/STARTTLS)', '585' => '585 (Legacy SSL)'] as $p => $label)
                                    <option value="{{ $p }}" {{ old('imap_port', $imapPort) == $p ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username / Email</label>
                            <input type="text" name="imap_username" value="{{ old('imap_username', $imapUsername) }}"
                                   class="form-input" placeholder="you@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrap">
                                <input type="password" name="imap_password" id="imap-pwd"
                                       value="{{ old('imap_password', $imapPassword) }}"
                                       class="form-input" placeholder="App password or account password">
                                <button type="button" class="eye-btn" onclick="togglePwd('imap-pwd','imap-eye')">
                                    <svg id="imap-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-row single">
                        <div class="form-group">
                            <label class="form-label">Encryption</label>
                            <select name="imap_encryption" class="form-select" style="max-width:240px;">
                                @foreach(['ssl' => 'SSL (recommended)', 'tls' => 'TLS', 'starttls' => 'STARTTLS', 'none' => 'None (not recommended)'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('imap_encryption', $imapEncryption) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn">Save IMAP Settings</button>
                        <a href="{{ route('admin.inbox.index') }}" class="btn btn-outline">Open Inbox</a>
                    </div>
                </form>

                {{-- Test connection --}}
                <div style="margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid #f1f5f9;">
                    <div style="font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.75rem;">Test Connection</div>
                    <form action="{{ route('admin.settings.imap.test') }}" method="POST" style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
                        @csrf
                        <button type="submit" class="btn btn-outline" style="font-size:0.85rem;">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Test IMAP Connection
                        </button>
                        <span style="font-size:0.78rem; color:#94a3b8;">Saves settings first, then tries to connect</span>
                    </form>
                </div>

                {{-- Quick setup guide --}}
                {{-- <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:1rem; margin-top:1.25rem;">
                    <div style="font-weight:700; font-size:0.82rem; color:#92400e; margin-bottom:0.5rem;">Quick Setup Guide</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem 2rem; font-size:0.78rem; color:#78350f; line-height:1.6;">
                        <div><strong>Gmail:</strong> imap.gmail.com · Port 993 · SSL<br><span style="color:#92400e;">Use App Password (not your regular password)</span></div>
                        <div><strong>Outlook:</strong> outlook.office365.com · Port 993 · SSL</div>
                        <div><strong>Yahoo:</strong> imap.mail.yahoo.com · Port 993 · SSL</div>
                        <div><strong>Custom:</strong> Check with your email provider for IMAP details</div>
                    </div>
                </div> --}}
            </div>
        </div>

        {{-- ── AI / OpenAI ── --}}
        <div class="settings-section" id="ai">
            <div class="section-head">
                <h3>AI Settings (OpenAI)</h3>
                <p>Connect your OpenAI account to enable the "Discover Events" feature in the Web Scraper.</p>
            </div>
            <div class="section-body">
                @if(session('success_openai'))
                    <div class="alert alert-success" style="margin-bottom:1.25rem;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success_openai') }}
                    </div>
                @endif

                <form action="{{ route('admin.settings.openai') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">OpenAI API Key</label>
                            <div style="position:relative;">
                                <input type="password" name="openai_api_key" id="openai-key-input"
                                       class="form-input" style="padding-right:2.5rem;"
                                       value="{{ $openaiApiKey }}"
                                       placeholder="sk-…">
                                <button type="button" onclick="togglePwd('openai-key-input', this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                            <span style="font-size:0.74rem; color:#94a3b8;">Get your key at <strong>platform.openai.com/api-keys</strong>. Uses GPT-5.4 with web search for profile verification.</span>
                        </div>
                    </div>
                    <div class="form-row" style="margin-top:1rem;">
                        <div class="form-group">
                            <label class="form-label">Apollo API Key <span style="font-weight:400;color:#94a3b8;">(optional — for LinkedIn lookup)</span></label>
                            <div style="position:relative;">
                                <input type="password" name="apollo_api_key" id="apollo-key-input"
                                       class="form-input" style="padding-right:2.5rem;"
                                       value="{{ $apolloApiKey ?? '' }}"
                                       placeholder="apollo-api-key…">
                                <button type="button" onclick="togglePwd('apollo-key-input', this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                            <span style="font-size:0.74rem; color:#94a3b8;">Get your key at <strong>app.apollo.io → Settings → Integrations → API</strong>. Used to find LinkedIn profiles fast. Falls back to AI if not set.</span>
                        </div>
                    </div>
                    <button type="submit" class="btn">Save API Keys</button>
                </form>

                {{-- Usage check --}}
                <div style="margin-top:1.25rem; border:1.5px solid #e2e8f0; border-radius:10px; overflow:hidden;" id="usage-panel">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:0.85rem 1.1rem; background:#f8fafc; border-bottom:1px solid #f1f5f9;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span style="font-size:0.85rem; font-weight:700; color:#0f172a;">API Usage & Limits</span>
                        </div>
                        <button type="button" onclick="checkUsage()" id="check-usage-btn"
                                style="display:inline-flex; align-items:center; gap:6px; padding:0.4rem 0.85rem; border-radius:7px; border:1.5px solid #c7d2fe; background:#fff; color:#6366f1; font-size:0.8rem; font-weight:700; cursor:pointer; transition:all 120ms;"
                                onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background='#fff'">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" id="usage-btn-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span id="usage-btn-text">Check Usage</span>
                        </button>
                    </div>
                    <div id="usage-content" style="padding:1rem 1.1rem; display:none;"></div>
                    <div id="usage-placeholder" style="padding:1rem 1.1rem; text-align:center;">
                        <span style="font-size:0.82rem; color:#94a3b8;">Click "Check Usage" to see your token limits and spending.</span>
                    </div>
                </div>

                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:1rem; margin-top:1rem; font-size:0.78rem; color:#166534; line-height:1.6;">
                    <strong style="display:block; margin-bottom:4px; color:#15803d;">What this enables</strong>
                    <ul style="padding-left:1rem; margin:0;">
                        <li>Web Scraper → <strong>Discover Events with AI</strong> tab</li>
                        <li>Search for fintech & banking conference websites by keyword</li>
                        <li>AI returns direct speaker page URLs ready to scrape</li>
                        <li>Send Invites → <strong>AI Draft Invite</strong> personalised emails</li>
                        <li>Classified Replies → <strong>AI Email Classification</strong></li>
                        <li>Speakers → <strong>AI Profile Verify</strong></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ── Admin Email ── --}}
        <div class="settings-section" id="email">
            <div class="section-head">
                <h3>Admin Email</h3>
                <p>Change the email address used to log in to this admin panel.</p>
            </div>
            <div class="section-body">
                @if(session('success_email'))
                    <div class="alert alert-success">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success_email') }}
                    </div>
                @endif
                @if($errors->has('current_password'))
                    <div class="alert alert-error">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $errors->first('current_password') }}
                    </div>
                @endif

                <form action="{{ route('admin.settings.email') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Admin Email</label>
                            <span class="form-hint">This will become your new login email</span>
                            <input type="email" name="admin_email"
                                   value="{{ old('admin_email', Auth::user()->email) }}"
                                   class="form-input {{ $errors->has('admin_email') ? 'is-error' : '' }}"
                                   placeholder="admin@example.com">
                            @error('admin_email') <span class="error-msg">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm with Current Password</label>
                            <span class="form-hint">Required to authorise email change</span>
                            <div class="input-wrap">
                                <input type="password" name="current_password" id="ep-pwd"
                                       class="form-input" placeholder="••••••••">
                                <button type="button" class="eye-btn" onclick="togglePwd('ep-pwd','ep-eye')">
                                    <svg id="ep-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn">Update Email</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Password ── --}}
        <div class="settings-section" id="password">
            <div class="section-head">
                <h3>Change Password</h3>
                <p>Use a strong password of at least 8 characters.</p>
            </div>
            <div class="section-body">
                @if(session('success_password'))
                    <div class="alert alert-success">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success_password') }}
                    </div>
                @endif
                @if($errors->has('current_password_pwd'))
                    <div class="alert alert-error">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $errors->first('current_password_pwd') }}
                    </div>
                @endif

                <form action="{{ route('admin.settings.password') }}" method="POST">
                    @csrf
                    <div class="form-row single">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <div class="input-wrap">
                                <input type="password" name="current_password" id="cp-pwd"
                                       class="form-input {{ $errors->has('current_password_pwd') ? 'is-error' : '' }}"
                                       placeholder="••••••••">
                                <button type="button" class="eye-btn" onclick="togglePwd('cp-pwd','cp-eye')">
                                    <svg id="cp-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <div class="input-wrap">
                                <input type="password" name="new_password" id="np-pwd"
                                       class="form-input" placeholder="Min 8 characters"
                                       oninput="checkStrength(this.value)">
                                <button type="button" class="eye-btn" onclick="togglePwd('np-pwd','np-eye')">
                                    <svg id="np-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="strength-bar">
                                <div class="strength-seg" id="s1"></div>
                                <div class="strength-seg" id="s2"></div>
                                <div class="strength-seg" id="s3"></div>
                                <div class="strength-seg" id="s4"></div>
                            </div>
                            <div class="strength-label" id="strength-label"></div>
                            @error('new_password') <span class="error-msg">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-wrap">
                                <input type="password" name="new_password_confirmation" id="cnp-pwd"
                                       class="form-input" placeholder="Repeat new password">
                                <button type="button" class="eye-btn" onclick="togglePwd('cnp-pwd','cnp-eye')">
                                    <svg id="cnp-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="btn">Update Password</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── API Keys ── --}}
        <div class="settings-section" id="api-keys">
            <div class="section-head">
                <h3>API Keys</h3>
                <p>Tokens for external agents to call <code>/api/v1/*</code>. Send as <code>Authorization: Bearer &lt;token&gt;</code>.</p>
            </div>
            <div class="section-body">
                @if(session('success_apikeys'))
                    <div class="alert alert-success">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ session('success_apikeys') }}
                    </div>
                @endif

                @if(!empty($newApiKey))
                    <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:0.85rem 1rem;margin-bottom:1rem;">
                        <div style="font-size:0.8rem;font-weight:600;color:#92400e;margin-bottom:6px;">⚠ Copy your new API token — this is the only time it will be shown:</div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input id="new-api-token" type="text" readonly value="{{ $newApiKey }}"
                                   class="form-input" style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:0.82rem;">
                            <button type="button" class="btn" onclick="navigator.clipboard.writeText(document.getElementById('new-api-token').value); this.textContent='Copied ✓';">Copy</button>
                        </div>
                    </div>
                @endif

                <form action="{{ route('admin.settings.apiKeys.store') }}" method="POST" style="margin-bottom:1.25rem;">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Key name</label>
                            <input type="text" name="name" class="form-input" placeholder="e.g. Zapier agent, LangChain bot" required>
                            <span class="form-hint">A label so you can identify this key later.</span>
                        </div>
                        <div class="form-group" style="justify-content:flex-end;">
                            <button type="submit" class="btn">＋ Generate new API key</button>
                        </div>
                    </div>
                </form>

                <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th style="text-align:left;padding:0.6rem 0.9rem;">Name</th>
                                <th style="text-align:left;padding:0.6rem 0.9rem;">Prefix</th>
                                <th style="text-align:left;padding:0.6rem 0.9rem;">Last used</th>
                                <th style="text-align:left;padding:0.6rem 0.9rem;">Status</th>
                                <th style="text-align:right;padding:0.6rem 0.9rem;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($apiKeys as $k)
                                <tr style="border-top:1px solid #f1f5f9;">
                                    <td style="padding:0.6rem 0.9rem;font-weight:600;color:#0f172a;">{{ $k->name }}</td>
                                    <td style="padding:0.6rem 0.9rem;font-family:ui-monospace,monospace;color:#64748b;">{{ $k->token_prefix }}…</td>
                                    <td style="padding:0.6rem 0.9rem;color:#64748b;">{{ $k->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td style="padding:0.6rem 0.9rem;">
                                        @if($k->revoked_at)
                                            <span style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:999px;font-size:0.72rem;font-weight:600;">Revoked</span>
                                        @else
                                            <span style="background:#dcfce7;color:#16a34a;padding:2px 8px;border-radius:999px;font-size:0.72rem;font-weight:600;">Active</span>
                                        @endif
                                    </td>
                                    <td style="padding:0.6rem 0.9rem;text-align:right;white-space:nowrap;">
                                        @if(!$k->revoked_at)
                                            <form action="{{ route('admin.settings.apiKeys.revoke', $k) }}" method="POST" style="display:inline;"
                                                  onsubmit="return confirm('Revoke this API key? Agents using it will stop working.');">
                                                @csrf
                                                <button type="submit" class="btn" style="background:#f59e0b;padding:4px 10px;font-size:0.75rem;">Revoke</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.settings.apiKeys.destroy', $k) }}" method="POST" style="display:inline;"
                                              onsubmit="return confirm('Delete this API key permanently?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn" style="background:#ef4444;padding:4px 10px;font-size:0.75rem;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:#94a3b8;">No API keys yet. Generate one above.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:1rem;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:0.85rem 1rem;font-size:0.8rem;color:#475569;">
                    <div style="font-weight:600;color:#0f172a;margin-bottom:4px;">Quick start</div>
                    <pre style="margin:0;font-family:ui-monospace,monospace;font-size:0.75rem;overflow-x:auto;">curl {{ url('/api/v1/stats') }} \
  -H "Authorization: Bearer YOUR_TOKEN"</pre>
                </div>
            </div>
        </div>

    </div>{{-- end forms --}}
</div>

<script>
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function scrollTo(hash, el) {
    event.preventDefault();
    document.querySelectorAll('.settings-nav-item').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
    const target = document.querySelector(hash);
    if (target) target.scrollIntoView({ behavior:'smooth', block:'start' });
}

function checkStrength(val) {
    let score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const colors = ['#ef4444','#f97316','#eab308','#16a34a'];
    const labels = ['Weak','Fair','Good','Strong'];
    for (let i = 1; i <= 4; i++) {
        const seg = document.getElementById('s' + i);
        seg.style.background = i <= score ? colors[score - 1] : '#e2e8f0';
    }
    const lbl = document.getElementById('strength-label');
    lbl.textContent = val.length ? labels[score - 1] || '' : '';
    lbl.style.color  = score ? colors[score - 1] : '#94a3b8';
}

// ── OpenAI Usage Check ──────────────────────────────────────
async function checkUsage() {
    const btn       = document.getElementById('check-usage-btn');
    const btnText   = document.getElementById('usage-btn-text');
    const btnIcon   = document.getElementById('usage-btn-icon');
    const content   = document.getElementById('usage-content');
    const placeholder = document.getElementById('usage-placeholder');

    btn.disabled = true;
    btnText.textContent = 'Checking…';
    btnIcon.outerHTML = '<span id="usage-btn-icon" style="display:inline-block;width:12px;height:12px;border:2px solid #c7d2fe;border-top-color:#6366f1;border-radius:50%;animation:uspin 0.7s linear infinite;"></span>';

    try {
        const resp = await fetch('{{ route("admin.settings.openai.usage") }}', {
            headers: { 'Accept': 'application/json' },
        });
        const data = await resp.json();

        if (!resp.ok || data.error) {
            content.innerHTML = `<div style="display:flex;align-items:center;gap:8px;color:#dc2626;font-size:0.84rem;font-weight:500;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                ${data.error || 'Failed to check usage.'}
            </div>`;
        } else {
            const rl = data.rate_limits || {};
            const crm = data.crm_usage || {};

            let html = '';

            // ── Key + Model row
            html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.85rem;">';
            html += buildCard('API Key', '✓ Valid', data.key_preview || '', '#16a34a');
            html += buildCard('Model', data.model || 'gpt-4o-mini', 'Active model for all AI features', '#6366f1');
            html += '</div>';

            // ── Rate limits section
            if (rl.tokens_limit > 0) {
                html += `<div style="font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:0.5rem;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Rate Limits (per minute)
                </div>`;

                // Tokens bar
                const tokUsed = rl.tokens_limit - rl.tokens_remaining;
                const tokPct = rl.tokens_limit > 0 ? Math.round((tokUsed / rl.tokens_limit) * 100) : 0;
                const tokColor = tokPct > 80 ? '#dc2626' : tokPct > 50 ? '#f59e0b' : '#16a34a';

                html += `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.75rem 0.85rem;margin-bottom:0.6rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-size:0.8rem;font-weight:700;color:#0f172a;">Tokens</span>
                        <span style="font-size:0.75rem;color:#64748b;">
                            <strong style="color:${tokColor};">${formatNum(rl.tokens_remaining)}</strong> remaining of ${formatNum(rl.tokens_limit)}
                        </span>
                    </div>
                    <div style="height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden;">
                        <div style="height:100%;width:${100 - tokPct}%;background:${tokColor};border-radius:999px;transition:width 300ms;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:0.7rem;color:#94a3b8;margin-top:4px;">
                        <span>${formatNum(tokUsed)} used</span>
                        <span>Resets: ${rl.tokens_reset || '~1min'}</span>
                    </div>
                </div>`;

                // Requests bar
                const reqUsed = rl.requests_limit - rl.requests_remaining;
                const reqPct = rl.requests_limit > 0 ? Math.round((reqUsed / rl.requests_limit) * 100) : 0;
                const reqColor = reqPct > 80 ? '#dc2626' : reqPct > 50 ? '#f59e0b' : '#16a34a';

                html += `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.75rem 0.85rem;margin-bottom:0.85rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-size:0.8rem;font-weight:700;color:#0f172a;">Requests</span>
                        <span style="font-size:0.75rem;color:#64748b;">
                            <strong style="color:${reqColor};">${formatNum(rl.requests_remaining)}</strong> remaining of ${formatNum(rl.requests_limit)}
                        </span>
                    </div>
                    <div style="height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden;">
                        <div style="height:100%;width:${100 - reqPct}%;background:${reqColor};border-radius:999px;transition:width 300ms;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:0.7rem;color:#94a3b8;margin-top:4px;">
                        <span>${reqUsed} used</span>
                        <span>Resets: ${rl.requests_reset || '~1min'}</span>
                    </div>
                </div>`;
            }

            // ── CRM Usage section
            html += `<div style="font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:0.5rem;display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                CRM Usage (This Month)
            </div>`;

            html += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.6rem;">';
            html += buildCard('AI Calls', String(crm.classifications_this_month || 0), 'This month', '#2563eb');
            html += buildCard('Est. Tokens', formatNum(crm.est_tokens_this_month || 0), 'Approximate', '#f59e0b');
            html += buildCard('Est. Cost', '$' + (crm.est_cost_this_month || 0).toFixed(4), 'gpt-4o-mini pricing', '#16a34a');
            html += '</div>';

            if (crm.classifications_total > 0) {
                html += `<div style="font-size:0.72rem;color:#94a3b8;margin-top:0.5rem;text-align:center;">
                    Total all-time AI calls: <strong>${crm.classifications_total}</strong>
                </div>`;
            }

            content.innerHTML = html;
        }

        content.style.display = 'block';
        placeholder.style.display = 'none';

    } catch (e) {
        content.innerHTML = `<div style="color:#dc2626;font-size:0.84rem;">Network error: ${e.message}</div>`;
        content.style.display = 'block';
        placeholder.style.display = 'none';
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Refresh';
        const spinner = document.getElementById('usage-btn-icon');
        if (spinner) spinner.outerHTML = '<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" id="usage-btn-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    }
}

function buildCard(label, value, sub, color) {
    return `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.7rem 0.85rem;">
        <div style="font-size:0.7rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:4px;">${label}</div>
        <div style="font-size:1rem;font-weight:700;color:${color};line-height:1.2;">${value}</div>
        ${sub ? `<div style="font-size:0.72rem;color:#94a3b8;margin-top:2px;">${sub}</div>` : ''}
    </div>`;
}

function formatNum(n) {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return String(n);
}
</script>
<style>
@keyframes uspin { to { transform:rotate(360deg); } }
</style>
@endsection
