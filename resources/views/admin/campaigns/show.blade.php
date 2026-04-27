@extends('layouts.admin-sidebar')

@section('title', $campaign->name)
@section('page-title', 'Campaign · ' . $campaign->name)

@section('extra-styles')
<style>
    .pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; font-size:0.72rem; font-weight:700; text-transform:uppercase; }
    .pill-draft     { background:#f1f5f9; color:#64748b; }
    .pill-running   { background:#dcfce7; color:#16a34a; }
    .pill-paused    { background:#fef9c3; color:#ca8a04; }
    .pill-completed { background:#dbeafe; color:#1d4ed8; }
    .pill-failed    { background:#fef2f2; color:#dc2626; }

    .pill-r-pending    { background:#f1f5f9; color:#64748b; }
    .pill-r-processing { background:#dbeafe; color:#1d4ed8; }
    .pill-r-sent       { background:#dcfce7; color:#16a34a; }
    .pill-r-failed     { background:#fef2f2; color:#dc2626; }
    .pill-r-skipped    { background:#fefce8; color:#a16207; }

    .header-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; margin-bottom:1rem; }
    .progress-wrap { margin-top:0.75rem; }
    .progress { height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
    .progress > div { height:100%; background:#16a34a; transition:width 200ms; }

    .meta-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:0.75rem; margin-top:0.75rem; }
    .meta-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.6rem 0.85rem; }
    .meta-item .label { font-size:0.7rem; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }
    .meta-item .value { font-size:0.95rem; font-weight:700; color:#0f172a; margin-top:2px; }

    .recipients-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .rec-table { width:100%; border-collapse:collapse; }
    .rec-table th { background:#f8fafc; padding:0.65rem 1rem; font-size:0.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; text-align:left; border-bottom:1px solid #e2e8f0; }
    .rec-table td { padding:0.7rem 1rem; font-size:0.84rem; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
</style>
@if($campaign->status === 'running')
<meta http-equiv="refresh" content="60">
@endif
@endsection

@section('content')

<div style="margin-bottom:1rem;">
    <a href="{{ route('admin.campaigns.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.875rem;text-decoration:none;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Campaigns
    </a>
</div>

@if(session('success'))
<div class="alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert-success" style="margin-bottom:1rem; background:#fef2f2; border-color:#fecaca; color:#dc2626;">{{ session('error') }}</div>
@endif

<div class="header-card">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.3rem; font-weight:700; color:#0f172a;">{{ $campaign->name }}</h1>
            <div style="margin-top:6px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <span class="pill pill-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span>
                @if($campaign->event)
                    <span style="font-size:0.78rem; color:#64748b;">📅 {{ $campaign->event->name }}</span>
                @endif
                @if($campaign->agenda_filename)
                    <span style="font-size:0.78rem; color:#64748b;">📎 {{ $campaign->agenda_filename }}</span>
                @endif
            </div>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            @if(in_array($campaign->status, ['draft','paused']))
                <form action="{{ route('admin.campaigns.start', $campaign) }}" method="POST" style="margin:0;">
                    @csrf
                    <button class="btn">▶ Start</button>
                </form>
            @endif
            @if($campaign->status === 'running')
                <form action="{{ route('admin.campaigns.pause', $campaign) }}" method="POST" style="margin:0;">
                    @csrf
                    <button class="btn" style="background:#f59e0b;">⏸ Pause</button>
                </form>
            @endif
            @if($campaign->status === 'paused')
                <form action="{{ route('admin.campaigns.resume', $campaign) }}" method="POST" style="margin:0;">
                    @csrf
                    <button class="btn">▶ Resume</button>
                </form>
            @endif
            <form action="{{ route('admin.campaigns.destroy', $campaign) }}" method="POST"
                  onsubmit="return confirm('Delete this campaign and all its recipients?')" style="margin:0;">
                @csrf @method('DELETE')
                <button type="submit" class="btn" style="background:#ef4444;">Delete</button>
            </form>
        </div>
    </div>

    <div class="progress-wrap">
        <div class="progress"><div style="width: {{ $campaign->progressPercent() }}%;"></div></div>
        <div style="font-size:0.78rem; color:#64748b; margin-top:5px;">
            {{ $campaign->sent_count }} sent · {{ $campaign->failed_count }} failed · {{ $campaign->total_count - $campaign->sent_count - $campaign->failed_count }} pending
            of {{ $campaign->total_count }} total ({{ $campaign->progressPercent() }}%)
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-item"><div class="label">Throttle</div><div class="value">{{ $campaign->throttle_seconds }}s</div></div>
        <div class="meta-item"><div class="label">Started</div><div class="value">{{ $campaign->started_at?->diffForHumans() ?? '—' }}</div></div>
        <div class="meta-item"><div class="label">Completed</div><div class="value">{{ $campaign->completed_at?->diffForHumans() ?? '—' }}</div></div>
        <div class="meta-item"><div class="label">Attach PDF</div><div class="value">{{ $campaign->attach_agenda ? 'Yes' : 'No' }}</div></div>
    </div>
</div>

<div class="recipients-card">
    <div style="padding:0.85rem 1.1rem; border-bottom:1px solid #e2e8f0; font-size:0.9rem; font-weight:700; color:#0f172a;">
        Recipients
    </div>
    <table class="rec-table">
        <thead>
            <tr>
                <th>Speaker</th>
                <th>Status</th>
                <th>AI Topic</th>
                <th>Scheduled</th>
                <th>Sent</th>
                <th>SMTP</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            @foreach($campaign->recipients as $r)
            <tr>
                <td>
                    <strong>{{ $r->speaker?->full_name ?? '(deleted)' }}</strong>
                    <div style="font-size:0.74rem; color:#94a3b8;">{{ $r->speaker?->email }}</div>
                </td>
                <td><span class="pill pill-r-{{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
                <td style="max-width:280px; font-size:0.78rem; color:#475569;">{{ $r->ai_topic ?: '—' }}</td>
                <td style="font-size:0.78rem; color:#94a3b8;">{{ $r->scheduled_at?->diffForHumans() ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#16a34a;">{{ $r->sent_at?->diffForHumans() ?? '—' }}</td>
                <td style="font-size:0.78rem; color:#64748b;">{{ $r->smtpAccount?->name ?? ($r->smtp_account_id ? '#'.$r->smtp_account_id : '—') }}</td>
                <td style="font-size:0.74rem; color:#dc2626; max-width:220px; overflow:hidden; text-overflow:ellipsis;">{{ $r->error ?: '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
