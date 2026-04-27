@extends('layouts.admin-sidebar')

@section('title', 'Campaigns')
@section('page-title', 'Campaigns')

@section('extra-styles')
<style>
    .stat-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:0.75rem; margin-bottom:1.25rem; }
    .stat { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem; }
    .stat .label { font-size:0.72rem; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }
    .stat .value { font-size:1.4rem; font-weight:800; color:#0f172a; line-height:1; margin-top:4px; }

    .camps-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .camps-table { width:100%; border-collapse:collapse; }
    .camps-table th { background:#f8fafc; padding:0.75rem 1rem; font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; text-align:left; border-bottom:1px solid #e2e8f0; }
    .camps-table td { padding:0.85rem 1rem; font-size:0.875rem; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .camps-table tbody tr:hover { background:#f8fafc; }

    .pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
    .pill-draft     { background:#f1f5f9; color:#64748b; }
    .pill-running   { background:#dcfce7; color:#16a34a; }
    .pill-paused    { background:#fef9c3; color:#ca8a04; }
    .pill-completed { background:#dbeafe; color:#1d4ed8; }
    .pill-failed    { background:#fef2f2; color:#dc2626; }

    .progress { height:6px; background:#e2e8f0; border-radius:999px; overflow:hidden; min-width:120px; }
    .progress > div { height:100%; background:#16a34a; transition:width 200ms; }

    .empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
    .empty-state h3 { font-size:1rem; font-weight:600; color:#64748b; margin-bottom:0.3rem; }
</style>
@endsection

@section('content')

<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 style="font-size:1.4rem; font-weight:700; color:#0f172a;">Campaigns</h1>
        <p style="font-size:0.85rem; color:#64748b; margin-top:3px;">
            AI-personalised speaker invitations sent through your rotated SMTP accounts.
        </p>
    </div>
    <a href="{{ route('admin.campaigns.create') }}" class="btn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Campaign
    </a>
</div>

@if(session('success'))
<div class="alert-success" style="margin-bottom:1rem;">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert-success" style="margin-bottom:1rem; background:#fef2f2; border-color:#fecaca; color:#dc2626;">
    {{ session('error') }}
</div>
@endif

<div class="stat-row">
    <div class="stat"><div class="label">Total Campaigns</div><div class="value">{{ $campaigns->count() }}</div></div>
    <div class="stat"><div class="label">Emails Sent</div><div class="value" style="color:#16a34a;">{{ number_format($sentTotal) }}</div></div>
    <div class="stat"><div class="label">Failed</div><div class="value" style="color:#dc2626;">{{ number_format($failedTotal) }}</div></div>
    <div class="stat"><div class="label">Running Now</div><div class="value" style="color:#2563eb;">{{ $campaigns->where('status','running')->count() }}</div></div>
</div>

<div class="camps-card">
    @if($campaigns->isEmpty())
        <div class="empty-state">
            <h3>No campaigns yet</h3>
            <p>Click <strong>New Campaign</strong> to get started.</p>
        </div>
    @else
        <table class="camps-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Recipients</th>
                    <th>Progress</th>
                    <th>Throttle</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                <tr>
                    <td>
                        <a href="{{ route('admin.campaigns.show', $c) }}" style="color:#0f172a; font-weight:600; text-decoration:none;">
                            {{ $c->name }}
                        </a>
                        @if($c->agenda_filename)
                            <div style="font-size:0.72rem; color:#94a3b8; margin-top:2px;">📎 {{ $c->agenda_filename }}</div>
                        @endif
                    </td>
                    <td><span class="pill pill-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
                    <td style="font-weight:700;">{{ number_format($c->total_count) }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div class="progress"><div style="width: {{ $c->progressPercent() }}%;"></div></div>
                            <span style="font-size:0.75rem; color:#64748b; font-weight:600;">{{ $c->sent_count }}/{{ $c->total_count }}</span>
                        </div>
                    </td>
                    <td style="font-size:0.78rem; color:#64748b;">{{ $c->throttle_seconds }}s</td>
                    <td style="font-size:0.78rem; color:#94a3b8;">{{ $c->created_at->diffForHumans() }}</td>
                    <td>
                        <div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <a href="{{ route('admin.campaigns.show', $c) }}" class="action-btn">View</a>
                            <form action="{{ route('admin.campaigns.destroy', $c) }}" method="POST"
                                  onsubmit="return confirm('Delete this campaign and all its recipients?')" style="margin:0;">
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
