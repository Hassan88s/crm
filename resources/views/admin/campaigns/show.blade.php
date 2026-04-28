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
    .preview-btn { background:#fff; border:1.5px solid #e2e8f0; border-radius:6px; padding:4px 10px; font-size:0.74rem; font-weight:600; color:#2563eb; cursor:pointer; }
    .preview-btn:hover { background:#eff6ff; border-color:#bfdbfe; }
    .preview-btn:disabled { opacity:0.5; cursor:wait; }

    /* Modal */
    .pv-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:1rem; }
    .pv-overlay.show { display:flex; }
    .pv-modal { background:#fff; border-radius:12px; max-width:800px; width:100%; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.25); }
    .pv-head { padding:1rem 1.25rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; }
    .pv-head h3 { font-size:1rem; font-weight:700; color:#0f172a; flex:1; }
    .pv-close { background:none; border:none; cursor:pointer; font-size:1.4rem; color:#94a3b8; line-height:1; padding:4px 8px; }
    .pv-close:hover { color:#0f172a; }
    .pv-meta { padding:0.6rem 1.25rem; background:#f8fafc; border-bottom:1px solid #f1f5f9; font-size:0.78rem; color:#475569; display:flex; flex-direction:column; gap:4px; }
    .pv-meta strong { color:#0f172a; }
    .pv-body { padding:1.25rem; overflow-y:auto; line-height:1.55; color:#1e293b; }
    .pv-body p { margin:0 0 0.75rem; }
    .pv-loading { text-align:center; padding:2.5rem 1rem; color:#64748b; font-size:0.85rem; }
    .pv-spin { display:inline-block; width:18px; height:18px; border:3px solid #e2e8f0; border-top-color:#2563eb; border-radius:50%; animation:pvspin 0.8s linear infinite; vertical-align:middle; margin-right:8px; }
    @keyframes pvspin { to { transform:rotate(360deg); } }
    .pv-pill { display:inline-flex; align-items:center; gap:4px; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; padding:2px 8px; border-radius:999px; font-size:0.72rem; font-weight:600; }
    .pv-pill.fresh { background:#fefce8; color:#a16207; border-color:#fde68a; }
    .pv-error { color:#dc2626; padding:1rem; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; font-size:0.85rem; }
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
        <div class="meta-item">
            <div class="label">Attach PDF to email</div>
            <div style="display:flex; align-items:center; gap:8px; margin-top:2px;">
                <span class="value" style="color:{{ $campaign->attach_agenda ? '#16a34a' : '#dc2626' }};">
                    {{ $campaign->attach_agenda ? 'Yes' : 'No' }}
                </span>
                <form action="{{ route('admin.campaigns.toggleAttach', $campaign) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit"
                            style="background:#fff; border:1.5px solid #e2e8f0; border-radius:6px; padding:3px 9px; font-size:0.72rem; font-weight:600; cursor:pointer; color:#475569;"
                            title="Toggle whether the PDF is attached to outgoing emails">
                        Toggle
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="recipients-card">
    <div style="padding:0.85rem 1.1rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <span style="font-size:0.9rem; font-weight:700; color:#0f172a; margin-right:auto;">
            Recipients
            <span style="font-size:0.78rem; font-weight:500; color:#94a3b8; margin-left:6px;">
                ({{ $recipients->total() }} total)
            </span>
        </span>

        @php
            $tabs = [
                ''           => ['All',         null,        $campaign->total_count],
                'pending'    => ['Pending',     '#94a3b8',  (int)($statusCounts['pending']    ?? 0)],
                'processing' => ['Processing',  '#1d4ed8',  (int)($statusCounts['processing'] ?? 0)],
                'sent'       => ['Sent',        '#16a34a',  (int)($statusCounts['sent']       ?? 0)],
                'failed'     => ['Failed',      '#dc2626',  (int)($statusCounts['failed']     ?? 0)],
                'skipped'    => ['Skipped',     '#a16207',  (int)($statusCounts['skipped']    ?? 0)],
            ];
        @endphp
        @foreach($tabs as $key => [$label, $color, $count])
            @php $isActive = ($statusFilter ?? '') === $key; @endphp
            <a href="{{ route('admin.campaigns.show', $campaign) }}{{ $key ? '?status='.$key : '' }}"
               style="display:inline-flex; align-items:center; gap:5px;
                      padding:3px 10px; border-radius:999px;
                      font-size:0.72rem; font-weight:700; text-decoration:none;
                      border:1.5px solid {{ $isActive ? ($color ?? '#2563eb') : '#e2e8f0' }};
                      background:{{ $isActive ? ($color ?? '#2563eb').'14' : '#fff' }};
                      color:{{ $isActive ? ($color ?? '#2563eb') : '#64748b' }};">
                {{ $label }}
                <span style="background:{{ $isActive ? ($color ?? '#2563eb').'33' : '#f1f5f9' }}; padding:0 6px; border-radius:999px; font-size:0.68rem;">{{ $count }}</span>
            </a>
        @endforeach
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
                <th>Preview</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recipients as $r)
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
                <td>
                    <button class="preview-btn"
                            data-recipient-id="{{ $r->id }}"
                            data-name="{{ $r->speaker?->full_name }}"
                            data-status="{{ $r->status }}"
                            onclick="openPreview(this)">
                        @if($r->generated_body) View email @else Preview @endif
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="padding:2rem 1rem; text-align:center; color:#94a3b8; font-size:0.85rem;">
                    No recipients @if($statusFilter) with status "{{ $statusFilter }}" @endif.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($recipients->hasPages())
        <div style="display:flex; align-items:center; justify-content:space-between; padding:0.85rem 1.1rem; border-top:1px solid #e2e8f0; font-size:0.8rem; color:#64748b; flex-wrap:wrap; gap:0.75rem;">
            <div>
                Showing {{ $recipients->firstItem() }}–{{ $recipients->lastItem() }} of {{ $recipients->total() }}
            </div>
            <div style="display:flex; gap:4px; flex-wrap:wrap;">
                @if($recipients->onFirstPage())
                    <span style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #e2e8f0; color:#cbd5e1; font-size:0.8rem; font-weight:600;">‹</span>
                @else
                    <a href="{{ $recipients->previousPageUrl() }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #e2e8f0; color:#475569; font-size:0.8rem; font-weight:600; text-decoration:none;">‹</a>
                @endif

                @php
                    $current = $recipients->currentPage();
                    $last    = $recipients->lastPage();
                    $start   = max(1, $current - 2);
                    $end     = min($last, $current + 2);
                @endphp
                @if($start > 1)
                    <a href="{{ $recipients->url(1) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #e2e8f0; color:#475569; font-size:0.8rem; font-weight:600; text-decoration:none;">1</a>
                    @if($start > 2) <span style="padding:0 4px; color:#94a3b8;">…</span> @endif
                @endif
                @for($p = $start; $p <= $end; $p++)
                    @if($p === $current)
                        <span style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; background:#2563eb; color:#fff; font-size:0.8rem; font-weight:700;">{{ $p }}</span>
                    @else
                        <a href="{{ $recipients->url($p) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #e2e8f0; color:#475569; font-size:0.8rem; font-weight:600; text-decoration:none;">{{ $p }}</a>
                    @endif
                @endfor
                @if($end < $last)
                    @if($end < $last - 1) <span style="padding:0 4px; color:#94a3b8;">…</span> @endif
                    <a href="{{ $recipients->url($last) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #e2e8f0; color:#475569; font-size:0.8rem; font-weight:600; text-decoration:none;">{{ $last }}</a>
                @endif

                @if($recipients->hasMorePages())
                    <a href="{{ $recipients->nextPageUrl() }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #e2e8f0; color:#475569; font-size:0.8rem; font-weight:600; text-decoration:none;">›</a>
                @else
                    <span style="display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #e2e8f0; color:#cbd5e1; font-size:0.8rem; font-weight:600;">›</span>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- Preview modal --}}
<div class="pv-overlay" id="pv-overlay" onclick="if(event.target===this) closePreview()">
    <div class="pv-modal">
        <div class="pv-head">
            <h3 id="pv-title">Email Preview</h3>
            <span id="pv-source"></span>
            <button class="pv-close" onclick="closePreview()" title="Close">&times;</button>
        </div>
        <div class="pv-meta" id="pv-meta" style="display:none;">
            <div><strong>Subject:</strong> <span id="pv-subject"></span></div>
            <div><strong>AI Topic:</strong> <span id="pv-topic"></span></div>
            <div id="pv-research-row" style="display:none;"><strong>Research notes:</strong> <span id="pv-research"></span></div>
        </div>
        <div class="pv-body" id="pv-body">
            <div class="pv-loading"><span class="pv-spin"></span> Loading…</div>
        </div>
    </div>
</div>

<script>
const CAMPAIGN_ID = {{ $campaign->id }};

function openPreview(btn) {
    const id = btn.dataset.recipientId;
    const name = btn.dataset.name || 'Speaker';
    const status = btn.dataset.status;

    document.getElementById('pv-title').textContent = 'Email for ' + name;
    document.getElementById('pv-source').innerHTML = '';
    document.getElementById('pv-meta').style.display = 'none';
    document.getElementById('pv-body').innerHTML =
        '<div class="pv-loading"><span class="pv-spin"></span> ' +
        (status === 'sent' ? 'Loading the sent email…' : 'Generating preview (this may take 30–60s)…') +
        '</div>';
    document.getElementById('pv-overlay').classList.add('show');

    fetch(`/admin/emails/${CAMPAIGN_ID}/recipient/${id}/email`, {
        headers: { 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) throw new Error(d.error || 'Failed to load');
        document.getElementById('pv-subject').textContent = d.subject || '(no subject)';
        document.getElementById('pv-topic').textContent = d.topic || '—';
        const sourceEl = document.getElementById('pv-source');
        if (d.source === 'saved') {
            sourceEl.innerHTML = '<span class="pv-pill">✓ Sent email</span>';
        } else {
            sourceEl.innerHTML = '<span class="pv-pill fresh">⚡ Fresh preview</span>';
        }
        if (d.research_notes) {
            document.getElementById('pv-research-row').style.display = '';
            document.getElementById('pv-research').textContent = d.research_notes;
        } else {
            document.getElementById('pv-research-row').style.display = 'none';
        }
        document.getElementById('pv-meta').style.display = '';
        document.getElementById('pv-body').innerHTML = d.body_html || '(empty)';
    })
    .catch(e => {
        document.getElementById('pv-body').innerHTML =
            '<div class="pv-error">Could not load preview: ' + e.message + '</div>';
    });
}

function closePreview() {
    document.getElementById('pv-overlay').classList.remove('show');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closePreview();
});
</script>

@endsection
