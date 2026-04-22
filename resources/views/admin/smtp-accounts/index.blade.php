@extends('layouts.admin-sidebar')

@section('title', 'SMTP Accounts')
@section('page-title', 'SMTP Accounts')

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
    .action-btn svg { width:13px; height:13px; }

    .empty-state {
        text-align:center; padding:3rem 1rem; color:#94a3b8;
    }
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
        <h1 style="font-size:1.4rem; font-weight:700; color:#0f172a;">SMTP Accounts</h1>
        <p style="font-size:0.85rem; color:#64748b; margin-top:3px;">
            Manage multiple SMTP servers for round-robin email rotation.
        </p>
    </div>
    <a href="{{ route('admin.smtp-accounts.create') }}" class="btn">
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
        <strong>How it works:</strong> When sending bulk emails, each message uses the next enabled SMTP account in sequence (round-robin).
        This prevents any single account from being rate-limited. If no accounts are enabled, the system falls back to the default SMTP in Settings.
    </span>
</div>

<div class="accounts-card">
    @if($accounts->isEmpty())
        <div class="empty-state">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <h3>No SMTP accounts yet</h3>
            <p>Add your first SMTP account to start rotating across multiple senders.</p>
        </div>
    @else
        <table class="accounts-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Host</th>
                    <th>From</th>
                    <th>Status</th>
                    <th>Emails Sent</th>
                    <th>Last Used</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                <tr>
                    <td style="font-weight:600; color:#0f172a;">{{ $account->name }}</td>
                    <td style="color:#64748b;">
                        {{ $account->host }}:{{ $account->port }}
                        <span style="font-size:0.7rem; color:#94a3b8; text-transform:uppercase;">({{ $account->encryption }})</span>
                    </td>
                    <td style="color:#64748b;">
                        <div style="font-weight:500; color:#334155;">{{ $account->from_name }}</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">{{ $account->from_address }}</div>
                    </td>
                    <td>
                        <span class="status-pill {{ $account->is_active ? 'on' : 'off' }}">
                            {{ $account->is_active ? '● Active' : '○ Disabled' }}
                        </span>
                    </td>
                    <td style="font-weight:700; color:#0f172a;">{{ number_format($account->emails_sent) }}</td>
                    <td style="color:#94a3b8; font-size:0.78rem;">
                        {{ $account->last_used_at ? $account->last_used_at->diffForHumans() : 'Never' }}
                    </td>
                    <td>
                        <div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <form action="{{ route('admin.smtp-accounts.toggle', $account) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="action-btn {{ $account->is_active ? '' : 'success' }}">
                                    {{ $account->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <button type="button" class="action-btn" onclick="openTest({{ $account->id }}, '{{ addslashes($account->name) }}')">
                                Test
                            </button>
                            <a href="{{ route('admin.smtp-accounts.edit', $account) }}" class="action-btn">Edit</a>
                            <form action="{{ route('admin.smtp-accounts.destroy', $account) }}" method="POST"
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

{{-- Test email modal --}}
<div id="test-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:10000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:14px; padding:1.5rem; width:440px; max-width:90vw;">
        <h3 style="font-size:1rem; font-weight:700; color:#0f172a; margin-bottom:0.25rem;">
            Send Test Email
        </h3>
        <p style="font-size:0.82rem; color:#64748b; margin-bottom:1rem;" id="test-account-name"></p>
        <form id="test-form" method="POST">
            @csrf
            <input type="email" name="test_to" required placeholder="you@example.com"
                   style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid #e2e8f0; border-radius:8px; font-size:0.875rem; outline:none;">
            <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:1rem;">
                <button type="button" onclick="closeTest()" style="padding:0.5rem 1rem; border-radius:8px; font-size:0.82rem; font-weight:600; cursor:pointer; border:none; background:#f1f5f9; color:#64748b;">Cancel</button>
                <button type="submit" class="btn">Send Test</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTest(id, name) {
    document.getElementById('test-account-name').textContent = 'Sending from: ' + name;
    document.getElementById('test-form').action = '/admin/smtp-accounts/' + id + '/test';
    document.getElementById('test-modal').style.display = 'flex';
}
function closeTest() {
    document.getElementById('test-modal').style.display = 'none';
}
</script>

@endsection
