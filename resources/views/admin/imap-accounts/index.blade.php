@extends('layouts.admin-sidebar')

@section('title', 'IMAP Accounts')
@section('page-title', 'IMAP Accounts')

@section('extra-styles')
<style>
    .accounts-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .accounts-table { width:100%; border-collapse:collapse; }
    .accounts-table th {
        background:#f8fafc; padding:0.75rem 1rem; font-size:0.72rem; font-weight:700;
        color:#64748b; text-transform:uppercase; letter-spacing:0.06em; text-align:left;
        border-bottom:1px solid #e2e8f0;
    }
    .accounts-table td {
        padding:0.85rem 1rem; font-size:0.875rem; color:#1e293b;
        border-bottom:1px solid #f1f5f9; vertical-align:middle;
    }
    .accounts-table tbody tr:hover { background:#f8fafc; }
    .accounts-table tbody tr:last-child td { border-bottom:none; }

    .status-pill {
        display:inline-flex; align-items:center; gap:5px;
        padding:3px 10px; border-radius:999px;
        font-size:0.72rem; font-weight:700; text-transform:uppercase;
    }
    .status-pill.on { background:#dcfce7; color:#16a34a; }
    .status-pill.off { background:#f1f5f9; color:#64748b; }

    .color-dot { display:inline-block; width:12px; height:12px; border-radius:50%; vertical-align:middle; margin-right:6px; }

    .action-btn {
        display:inline-flex; align-items:center; gap:5px;
        padding:0.35rem 0.75rem; border-radius:7px; border:1.5px solid #e2e8f0;
        font-size:0.75rem; font-weight:600; cursor:pointer;
        background:#fff; color:#374151; transition:all 120ms; white-space:nowrap;
        text-decoration:none;
    }
    .action-btn:hover { background:#f1f5f9; }
    .action-btn.danger { color:#dc2626; border-color:#fecaca; }
    .action-btn.danger:hover { background:#fef2f2; }
    .action-btn.success { color:#16a34a; border-color:#bbf7d0; }
    .action-btn.success:hover { background:#f0fdf4; }

    .empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
    .empty-state svg { width:48px; height:48px; margin:0 auto 1rem; opacity:0.3; }
    .empty-state h3 { font-size:1rem; font-weight:600; color:#64748b; margin-bottom:0.3rem; }
    .empty-state p { font-size:0.85rem; }

    .info-banner {
        background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;
        padding:0.85rem 1.1rem; margin-bottom:1.25rem;
        display:flex; align-items:flex-start; gap:0.7rem;
        font-size:0.82rem; color:#1e40af;
    }
    .info-banner svg { width:18px; height:18px; flex-shrink:0; color:#2563eb; margin-top:1px; }
</style>
@endsection

@section('content')

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 style="font-size:1.4rem; font-weight:700; color:#0f172a;">IMAP Accounts</h1>
        <p style="font-size:0.85rem; color:#64748b; margin-top:3px;">
            Connect multiple inboxes — emails from all enabled accounts show in one unified Inbox feed.
        </p>
    </div>
    <a href="{{ route('admin.imap-accounts.create') }}" class="btn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Add Account
    </a>
</div>

@if(session('success'))
<div class="info-banner" style="background:#f0fdf4; border-color:#bbf7d0; color:#16a34a;">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="info-banner" style="background:#fef2f2; border-color:#fecaca; color:#dc2626;">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('error') }}
</div>
@endif

<div class="info-banner">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>
        <strong>How it works:</strong> Every enabled IMAP account is fetched when you open the Inbox.
        Emails from all accounts appear together, sorted by date, each with a small colored badge showing which account it came from.
        If no accounts are enabled, the system falls back to the IMAP credentials in your .env.
    </span>
</div>

<div class="accounts-card">
    @if($accounts->isEmpty())
        <div class="empty-state">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <h3>No IMAP accounts yet</h3>
            <p>Add your first IMAP account to connect an inbox.</p>
        </div>
    @else
        <table class="accounts-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Host</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Last Fetched</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                <tr>
                    <td style="font-weight:600; color:#0f172a;">
                        <span class="color-dot" style="background:{{ $account->color }};"></span>
                        {{ $account->name }}
                    </td>
                    <td style="color:#64748b;">
                        {{ $account->host }}:{{ $account->port }}
                        <span style="font-size:0.7rem; color:#94a3b8; text-transform:uppercase;">({{ $account->encryption }})</span>
                    </td>
                    <td style="color:#64748b; font-size:0.82rem;">{{ $account->username }}</td>
                    <td>
                        <span class="status-pill {{ $account->is_active ? 'on' : 'off' }}">
                            {{ $account->is_active ? '● Active' : '○ Disabled' }}
                        </span>
                    </td>
                    <td style="color:#94a3b8; font-size:0.78rem;">
                        {{ $account->last_fetched_at ? $account->last_fetched_at->diffForHumans() : 'Never' }}
                    </td>
                    <td>
                        <div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <form action="{{ route('admin.imap-accounts.toggle', $account) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="action-btn {{ $account->is_active ? '' : 'success' }}">
                                    {{ $account->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.imap-accounts.test', $account) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="action-btn">Test</button>
                            </form>
                            <a href="{{ route('admin.imap-accounts.edit', $account) }}" class="action-btn">Edit</a>
                            <form action="{{ route('admin.imap-accounts.destroy', $account) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ addslashes($account->name) }}?')" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
