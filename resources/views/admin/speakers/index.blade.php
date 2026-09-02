@extends('layouts.admin-sidebar')
@section('title', 'Speakers')
@section('page-title', 'Speakers')

@section('extra-styles')
<style>
    .alert-success {
        background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px;
        padding:0.85rem 1.1rem; margin-bottom:1.5rem;
        color:#16a34a; font-size:0.875rem; font-weight:500;
        display:flex; align-items:center; gap:8px;
    }

    /* Toolbar */
    .toolbar { display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem; flex-wrap:wrap; }
    .toolbar-left { display:flex; align-items:center; gap:0.5rem; flex:1; }
    .search-box {
        display:flex; align-items:center; gap:8px;
        background:#fff; border:1.5px solid #e2e8f0; border-radius:8px;
        padding:0.5rem 0.85rem; min-width:240px;
    }
    .search-box svg { width:16px; height:16px; color:#94a3b8; flex-shrink:0; }
    .search-box input { border:none; outline:none; font-size:0.875rem; color:#0f172a; background:transparent; width:100%; font-family:inherit; }
    .search-box input::placeholder { color:#94a3b8; }

    /* Table */
    .speakers-table { width:100%; border-collapse:collapse; }
    .speakers-table th {
        text-align:left; font-size:0.72rem; font-weight:700; color:#64748b;
        text-transform:uppercase; letter-spacing:0.06em;
        padding:0.7rem 1.25rem; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    }
    .speakers-table td { padding:0.9rem 1.25rem; font-size:0.875rem; color:#374151; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .speakers-table tbody tr:hover td { background:#f8fafc; }
    .speakers-table tbody tr:last-child td { border-bottom:none; }

    /* Avatar */
    .avatar { width:38px; height:38px; border-radius:50%; object-fit:cover; flex-shrink:0; }
    .avatar-placeholder {
        width:38px; height:38px; border-radius:50%; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:0.82rem; color:#fff;
    }

    /* Seniority badge */
    .seniority-badge {
        display:inline-block; padding:2px 9px; border-radius:999px;
        font-size:0.72rem; font-weight:600; background:#f1f5f9; color:#475569;
    }
    .seniority-c { background:#dbeafe; color:#1d4ed8; }
    .seniority-vp { background:#f3e8ff; color:#7e22ce; }
    .seniority-director { background:#fef9c3; color:#854d0e; }
    .seniority-manager { background:#dcfce7; color:#16a34a; }

    /* Action buttons */
    .action-btn {
        display:inline-flex; align-items:center; gap:4px;
        padding:0.35rem 0.7rem; border-radius:6px; border:1px solid #e2e8f0;
        font-size:0.78rem; font-weight:600; cursor:pointer; text-decoration:none;
        background:#fff; color:#374151; transition:all 120ms; white-space:nowrap;
    }
    .action-btn:hover { background:#f1f5f9; }
    .action-btn.danger { color:#dc2626; border-color:#fecaca; }
    .action-btn.danger:hover { background:#fef2f2; }
    .action-btn.verify { color:#6366f1; border-color:#c7d2fe; }
    .action-btn.verify:hover { background:#eef2ff; }
    .action-btn.verify:disabled { opacity:0.5; cursor:not-allowed; }
    .action-btn.linkedin { color:#0077b5; border-color:#b8d4e8; }
    .action-btn.linkedin:hover { background:#f0f7fb; }
    .action-btn.linkedin:disabled { opacity:0.5; cursor:not-allowed; }
    .action-btn.hermes { color:#7c3aed; border-color:#ddd6fe; }
    .action-btn.hermes:hover { background:#f5f3ff; }
    .action-btn.hermes:disabled { opacity:0.5; cursor:not-allowed; }

    .action-btn svg { width:13px; height:13px; }
    .verify-spin { display:inline-block; width:11px; height:11px; border:2px solid #c7d2fe; border-top-color:#6366f1; border-radius:50%; animation:vspin 0.7s linear infinite; vertical-align:middle; }
    @keyframes vspin { to { transform:rotate(360deg); } }

    /* Verify result toast */
    .verify-toast {
        position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
        background:#fff; border:1px solid #e2e8f0; border-radius:12px;
        box-shadow:0 8px 30px rgba(0,0,0,0.12); padding:1rem 1.25rem;
        max-width:420px; min-width:280px;
        transform:translateY(120%); opacity:0; transition:all 300ms ease;
    }
    .verify-toast.show { transform:translateY(0); opacity:1; }
    .verify-toast-head { display:flex; align-items:center; gap:8px; margin-bottom:0.6rem; }
    .verify-toast-title { font-size:0.88rem; font-weight:700; color:#0f172a; }
    .verify-toast-close { margin-left:auto; background:none; border:none; cursor:pointer; color:#94a3b8; font-size:1rem; padding:2px; }
    .verify-toast-close:hover { color:#0f172a; }
    .verify-confidence { font-size:0.7rem; font-weight:700; padding:2px 7px; border-radius:999px; text-transform:uppercase; }
    .conf-high   { background:#f0fdf4; color:#16a34a; }
    .conf-medium { background:#fefce8; color:#ca8a04; }
    .conf-low    { background:#fef2f2; color:#dc2626; }
    .verify-change { display:flex; align-items:flex-start; gap:6px; font-size:0.8rem; padding:0.3rem 0; border-bottom:1px solid #f1f5f9; }
    .verify-change:last-child { border-bottom:none; }
    .verify-field { font-weight:700; color:#475569; min-width:60px; text-transform:capitalize; }
    .verify-old { color:#ef4444; text-decoration:line-through; }
    .verify-new { color:#16a34a; font-weight:600; }
    .verify-arrow { color:#94a3b8; flex-shrink:0; }
    .verify-summary { font-size:0.78rem; color:#64748b; margin-top:0.5rem; font-style:italic; }

    /* Empty state */
    .empty-state { text-align:center; padding:4rem 2rem; }
    .empty-state svg { width:52px; height:52px; margin:0 auto 1rem; display:block; color:#cbd5e1; }
    .empty-state h3 { font-size:1rem; font-weight:700; color:#64748b; margin-bottom:0.4rem; }
    .empty-state p { font-size:0.875rem; color:#94a3b8; margin-bottom:1.5rem; }

    .table-footer { padding:0.75rem 1.25rem; border-top:1px solid #f1f5f9; font-size:0.8rem; color:#94a3b8; }
    .page-btn {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:32px; height:32px; padding:0 8px;
        border-radius:6px; border:1px solid #e2e8f0;
        font-size:0.8rem; font-weight:600; color:#374151;
        text-decoration:none; background:#fff; transition:all 120ms;
    }
    .page-btn:hover:not(.disabled):not(.active) { background:#f1f5f9; }
    .page-btn.active { background:#2563eb; color:#fff; border-color:#2563eb; }
    .page-btn.disabled { color:#d1d5db; cursor:default; }
</style>
@endsection

@section('content')

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.3rem; font-weight:700; color:#0f172a;">Speakers</h1>
        <p style="color:#64748b; font-size:0.875rem; margin-top:3px;">
            {{ $speakers->total() }} speaker{{ $speakers->total() !== 1 ? 's' : '' }}
            @if($eventId && ($activeEvent = $events->find($eventId)))
                <span style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:5px;padding:1px 7px;font-size:0.75rem;font-weight:600;margin-left:6px;">
                    {{ $activeEvent->name }}
                </span>
            @endif
        </p>
    </div>
    <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        @php $eventForDelete = $eventId ? $events->find($eventId) : null; @endphp
        @if($eventForDelete)
        <form action="{{ route('admin.speakers.destroyByEvent', $eventForDelete) }}" method="POST"
              onsubmit="return confirm('Delete all speakers in event \&quot;{{ addslashes($eventForDelete->name) }}\&quot;? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn" style="font-size:0.85rem; background:#f59e0b;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete from Event
            </button>
        </form>
        @endif
        @if($speakers->total() > 0)
        <form action="{{ route('admin.speakers.destroyAll') }}" method="POST"
              onsubmit="return confirm('Delete ALL {{ $speakers->total() }} speakers? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn" style="font-size:0.85rem; background:#ef4444;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete All
            </button>
        </form>
        @endif
        @php
            // Carry every active filter into the export URL so the download
            // always matches what the user is currently looking at.
            $exportParams = array_filter([
                'event_id'       => $eventId,
                'search'         => $search,
                'missing'        => $missing,
                'reply_category' => $replyCategory,
                'bounced'        => !empty($bouncedOnly) ? 1 : null,
            ], fn($v) => !is_null($v) && $v !== '');
            $hasFilters = !empty($exportParams);
        @endphp
        <a href="{{ route('admin.speakers.export', $exportParams) }}" class="btn btn-outline"
           style="font-size:0.85rem;"
           title="{{ $hasFilters ? 'Download the currently filtered speakers as a CSV' : 'Download all speakers as a CSV' }}">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4 4l-4-4m0 0l-4 4m4-4v-12"/>
            </svg>
            Export CSV{{ $hasFilters ? ' (filtered)' : '' }}
        </a>

        <a href="{{ route('admin.speakers.import') }}" class="btn btn-outline" style="font-size:0.85rem;">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Import CSV
        </a>
        <a href="{{ route('admin.speakers.create') }}" class="btn" style="font-size:0.85rem;">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Add Speaker
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

{{-- Search + Event filter toolbar --}}
<div class="toolbar">
    <div class="toolbar-left">
        <form method="GET" action="{{ route('admin.speakers.index') }}" id="search-form" style="display:flex;align-items:center;gap:8px;">
        <div class="search-box">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" id="search-input" value="{{ $search ?? '' }}"
                   placeholder="Search by name, company, c…"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('search-form').submit();}">
        </div>

        @if($events->isNotEmpty())
            <select name="event_id" onchange="document.getElementById('search-form').submit()"
                    style="padding:0.5rem 0.75rem; border:1.5px solid #e2e8f0; border-radius:8px; font-size:0.875rem; outline:none; color:#0f172a; background:#fff; font-family:inherit; cursor:pointer;">
                <option value="">All Events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" {{ $eventId == $event->id ? 'selected' : '' }}>
                        {{ $event->name }}
                    </option>
                @endforeach
            </select>
        @endif

        {{-- Missing-field filter --}}
        <select name="missing" onchange="document.getElementById('search-form').submit()"
                title="Filter speakers missing a specific field"
                style="padding:0.5rem 0.75rem; border:1.5px solid #e2e8f0; border-radius:8px; font-size:0.875rem; outline:none; color:#0f172a; background:#fff; font-family:inherit; cursor:pointer;">
            <option value="">All speakers</option>
            <option value="title"        {{ ($missing ?? '') === 'title'        ? 'selected' : '' }}>Missing: Title</option>
            <option value="company"      {{ ($missing ?? '') === 'company'      ? 'selected' : '' }}>Missing: Company</option>
            <option value="email"        {{ ($missing ?? '') === 'email'        ? 'selected' : '' }}>Missing: Email</option>
            <option value="country"      {{ ($missing ?? '') === 'country'      ? 'selected' : '' }}>Missing: Country</option>
            <option value="seniority"    {{ ($missing ?? '') === 'seniority'    ? 'selected' : '' }}>Missing: Seniority</option>
            <option value="linkedin_url" {{ ($missing ?? '') === 'linkedin_url' ? 'selected' : '' }}>Missing: LinkedIn URL</option>
        </select>

        {{-- Reply category filter (Confirmed / Interested / Bounced / No Reply / etc.) --}}
        @php
            $catColors = [
                'Confirmed'      => '#0891b2',
                'Interested'     => '#16a34a',
                'Not Interested' => '#64748b',
                'Info Request'   => '#2563eb',
                'Out of Office'  => '#ca8a04',
                'Spam'           => '#dc2626',
                'Negative'       => '#9f1239',
                'Manual Review'  => '#94a3b8',
                'No Reply'       => '#f97316',
                'Bounced'        => '#7c3aed',
            ];
            $catIcons = [
                'Confirmed'      => '✅',
                'Interested'     => '🟢',
                'Not Interested' => '⚫',
                'Info Request'   => '🔵',
                'Out of Office'  => '🟡',
                'Spam'           => '🔴',
                'Negative'       => '🚫',
                'Manual Review'  => '⚪',
                'No Reply'       => '🟠',
                'Bounced'        => '↩️',
            ];
            $activeColor = $replyCategory ? ($catColors[$replyCategory] ?? '#2563eb') : null;
        @endphp
        <select name="reply_category" onchange="document.getElementById('search-form').submit()"
                title="Filter speakers by their classified reply category"
                style="padding:0.45rem 0.75rem;
                       border:1.5px solid {{ $replyCategory ? $activeColor.'55' : '#e2e8f0' }};
                       background:{{ $replyCategory ? $activeColor.'14' : '#fff' }};
                       color:{{ $replyCategory ? $activeColor : '#0f172a' }};
                       border-radius:8px; font-size:0.875rem;
                       font-weight:{{ $replyCategory ? 700 : 500 }};
                       outline:none; cursor:pointer; font-family:inherit;">
            <option value="">Reply category — All</option>
            @foreach($allowedReplyCats as $c)
                @php
                    $count = $c === 'Bounced'
                        ? count($bouncedEmails ?? [])
                        : (int)($replyCategoryCounts[$c] ?? 0);
                @endphp
                <option value="{{ $c }}" {{ $replyCategory === $c ? 'selected' : '' }}>
                    {{ ($catIcons[$c] ?? '') }} {{ $c }} ({{ $count }})
                </option>
            @endforeach
        </select>

        @if($eventId || $search || !empty($missing) || !empty($replyCategory))
            <a href="{{ route('admin.speakers.index') }}"
               style="font-size:0.78rem; color:#64748b; text-decoration:none; white-space:nowrap; padding:0.4rem 0.5rem;">
               ✕ Clear
            </a>
        @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
    @if($speakers->total() === 0)
        <div class="empty-state">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h3>No speakers yet</h3>
            <p>Add speakers manually or import them from a CSV file.</p>
            <div style="display:flex; gap:0.6rem; justify-content:center;">
                <a href="{{ route('admin.speakers.create') }}" class="btn">+ Add Speaker</a>
                <a href="{{ route('admin.speakers.import') }}" class="btn btn-outline">Import CSV</a>
            </div>
        </div>
    @else
        <table class="speakers-table" id="speakers-table">
            <thead>
                <tr>
                    <th>Speaker</th>
                    <th>Title</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Seniority</th>
                    <th>Country</th>
                    <th>Event</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $colors = ['#2563eb','#16a34a','#9333ea','#ef4444','#ca8a04','#0891b2','#d946ef','#f97316']; @endphp
                @foreach($speakers as $i => $speaker)
                <tr data-name="{{ strtolower($speaker->full_name) }} {{ strtolower($speaker->company) }} {{ strtolower($speaker->country) }}">
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            @if($speaker->photo)
                                <img src="{{ Storage::url($speaker->photo) }}" alt="{{ $speaker->full_name }}" class="avatar">
                            @else
                                @php $c = $colors[$i % count($colors)]; @endphp
                                <div class="avatar-placeholder" style="background:{{ $c }};">
                                    {{ strtoupper(substr($speaker->first_name,0,1)) }}
                                </div>
                            @endif
                            <div>
                                <div style="font-weight:600; color:#0f172a; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    {{ $speaker->full_name }}
                                    @php
                                        $isBounced = !empty($bouncedEmails) && $speaker->email
                                                     && isset($bouncedEmails[strtolower($speaker->email)]);
                                        $latestReply = $latestReplyBySpeaker[$speaker->id] ?? null;

                                        // Per-category style maps (kept in PHP for SSR styling of pill / dropdown)
                                        $catColorMap = [
                                            'Confirmed'      => ['fg'=>'#0891b2', 'bg'=>'#ecfeff', 'icon'=>'✅'],
                                            'Interested'     => ['fg'=>'#16a34a', 'bg'=>'#f0fdf4', 'icon'=>'🟢'],
                                            'Not Interested' => ['fg'=>'#64748b', 'bg'=>'#f8fafc', 'icon'=>'⚫'],
                                            'Info Request'   => ['fg'=>'#2563eb', 'bg'=>'#eff6ff', 'icon'=>'🔵'],
                                            'Out of Office'  => ['fg'=>'#ca8a04', 'bg'=>'#fefce8', 'icon'=>'🟡'],
                                            'Spam'           => ['fg'=>'#dc2626', 'bg'=>'#fef2f2', 'icon'=>'🔴'],
                                            'Negative'       => ['fg'=>'#9f1239', 'bg'=>'#fff1f2', 'icon'=>'🚫'],
                                            'Manual Review'  => ['fg'=>'#94a3b8', 'bg'=>'#f1f5f9', 'icon'=>'⚪'],
                                            'No Reply'       => ['fg'=>'#f97316', 'bg'=>'#fff7ed', 'icon'=>'🟠'],
                                            'Bounced'        => ['fg'=>'#7c3aed', 'bg'=>'#f5f3ff', 'icon'=>'↩️'],
                                        ];
                                    @endphp

                                    @if($latestReply)
                                        @php $cs = $catColorMap[$latestReply->category] ?? $catColorMap['Manual Review']; @endphp
                                        <select class="speaker-cat speaker-cat-{{ $speaker->id }}"
                                                data-reply-id="{{ $latestReply->id }}"
                                                data-url="{{ route('admin.replies.changeCategory', $latestReply->id) }}"
                                                onchange="onSpeakerCategoryChange(this)"
                                                onclick="event.stopPropagation();"
                                                title="Change reply category"
                                                style="background:{{ $cs['bg'] }}; color:{{ $cs['fg'] }};
                                                       border:1px solid {{ $cs['fg'] }}33;
                                                       border-radius:999px; padding:1px 18px 1px 8px;
                                                       font-size:0.65rem; font-weight:700; letter-spacing:0.02em;
                                                       cursor:pointer; appearance:none; -webkit-appearance:none;
                                                       background-image:url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;8&quot; height=&quot;8&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;{{ str_replace('#', '%23', $cs['fg']) }}&quot; stroke-width=&quot;3&quot; stroke-linecap=&quot;round&quot;><path d=&quot;M6 9l6 6 6-6&quot;/></svg>');
                                                       background-repeat:no-repeat; background-position:right 5px center;">
                                            @foreach(['Confirmed','Interested','Not Interested','Info Request','Out of Office','Spam','Negative','Manual Review','No Reply','Bounced'] as $cOpt)
                                                <option value="{{ $cOpt }}" {{ $latestReply->category === $cOpt ? 'selected' : '' }}>
                                                    {{ ($catColorMap[$cOpt]['icon'] ?? '') }} {{ $cOpt }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @if($isBounced && (!$latestReply || $latestReply->category !== 'Bounced'))
                                        <span title="This speaker's email has bounced"
                                              style="display:inline-flex; align-items:center; gap:3px;
                                                     background:#f5f3ff; color:#7c3aed; border:1px solid #7c3aed33;
                                                     padding:1px 7px; border-radius:999px;
                                                     font-size:0.65rem; font-weight:700; letter-spacing:0.02em;">
                                            ↩️ Bounced
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="color:#64748b;">{{ $speaker->title ?: '—' }}</td>
                    <td style="font-weight:500;">{{ $speaker->company ?: '—' }}</td>
                    <td style="color:#64748b; font-size:0.82rem;">
                        {{ $speaker->email }}
                        @if($speaker->linkedin_url)
                            <a href="{{ $speaker->linkedin_url }}" target="_blank" rel="noopener"
                               style="display:inline-flex;align-items:center;margin-left:4px;color:#0077b5;"
                               title="LinkedIn Profile" id="linkedin-{{ $speaker->id }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        @else
                            <span id="linkedin-{{ $speaker->id }}"></span>
                        @endif
                    </td>
                    <td>
                        @if($speaker->seniority)
                            @php
                                $sl = strtolower($speaker->seniority);
                                $cls = str_contains($sl,'c-') || in_array($sl,['ceo','cto','coo','cmo','cfo'])
                                    ? 'seniority-c'
                                    : (str_contains($sl,'vp') ? 'seniority-vp'
                                    : (str_contains($sl,'director') ? 'seniority-director'
                                    : (str_contains($sl,'manager') ? 'seniority-manager' : '')));
                            @endphp
                            <span class="seniority-badge {{ $cls }}">{{ $speaker->seniority }}</span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="color:#64748b;">
                        @if($speaker->country)
                            <div style="display:flex; align-items:center; gap:5px;">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#94a3b8; flex-shrink:0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                {{ $speaker->country }}
                            </div>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($speaker->event)
                            <span style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:6px;padding:2px 8px;font-size:0.72rem;font-weight:600;white-space:nowrap;">
                                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $speaker->event->name }}
                            </span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <button class="action-btn verify" id="verify-btn-{{ $speaker->id }}"
                                    onclick="verifyProfile({{ $speaker->id }}, '{{ route('admin.speakers.verify', $speaker) }}', '{{ addslashes($speaker->full_name) }}')"
                                    title="AI deep search & verify profile">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Verify
                            </button>
                            <button class="action-btn linkedin" id="li-btn-{{ $speaker->id }}"
                                    onclick="findLinkedIn({{ $speaker->id }}, '{{ route('admin.speakers.findLinkedIn', $speaker) }}', '{{ addslashes($speaker->full_name) }}', '{{ addslashes($speaker->linkedin_url ?? '') }}')"
                                    title="AI search for LinkedIn profile">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                LinkedIn
                            </button>
                            <button class="action-btn hermes" id="hermes-btn-{{ $speaker->id }}"
                                    onclick="askHermes({{ $speaker->id }}, '{{ route('admin.speakers.askHermes', $speaker) }}', '{{ addslashes($speaker->full_name) }}')"
                                    title="Ask local Hermes agent to verify & enrich">
                                🤖 Hermes
                            </button>
                            <a href="{{ route('admin.speakers.edit', $speaker) }}" class="action-btn">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.speakers.destroy', $speaker) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ $speaker->full_name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn danger">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="table-footer" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
            <span id="table-count">
                Showing {{ $speakers->firstItem() }}–{{ $speakers->lastItem() }} of {{ $speakers->total() }} speaker{{ $speakers->total() !== 1 ? 's' : '' }}
            </span>
            @if($speakers->hasPages())
                <div style="display:flex; align-items:center; gap:4px;">
                    {{-- Previous --}}
                    @if($speakers->onFirstPage())
                        <span class="page-btn disabled">‹</span>
                    @else
                        <a href="{{ $speakers->previousPageUrl() }}" class="page-btn">‹</a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($speakers->getUrlRange(max(1, $speakers->currentPage()-2), min($speakers->lastPage(), $speakers->currentPage()+2)) as $page => $url)
                        @if($page == $speakers->currentPage())
                            <span class="page-btn active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($speakers->hasMorePages())
                        <a href="{{ $speakers->nextPageUrl() }}" class="page-btn">›</a>
                    @else
                        <span class="page-btn disabled">›</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Verify toast --}}
<div class="verify-toast" id="verify-toast">
    <div class="verify-toast-head">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span class="verify-toast-title" id="verify-toast-title">Verification Result</span>
        <span class="verify-confidence" id="verify-confidence"></span>
        <button class="verify-toast-close" onclick="closeVerifyToast()">&times;</button>
    </div>
    <div id="verify-toast-body"></div>
    <div class="verify-summary" id="verify-summary"></div>
</div>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';

function filterSpeakers(q) {
    q = q.toLowerCase().trim();
    let rows = document.querySelectorAll('#speakers-table tbody tr');
    let visible = 0;
    rows.forEach(row => {
        const text = row.getAttribute('data-name') || '';
        const show = !q || text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById('table-count');
    if (countEl) countEl.textContent = `Showing ${visible} speaker${visible !== 1 ? 's' : ''}${q ? ' matching "'+q+'"' : ''}`;
}

async function verifyProfile(id, url, name) {
    const btn = document.getElementById('verify-btn-' + id);
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="verify-spin"></span> Verifying…';

    try {
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const data = await resp.json();

        if (!resp.ok || data.error) {
            showVerifyToast(name, null, null, data.error || 'Verification failed.', null);
        } else {
            showVerifyToast(name, data.updated, data.confidence, null, data.summary);

            // Update table cells in-place if fields changed
            if (data.updated && Object.keys(data.updated).length > 0) {
                const row = btn.closest('tr');
                if (row) {
                    const cells = row.querySelectorAll('td');
                    // cells[1] = title, cells[2] = company, cells[5] = country
                    if (data.speaker) {
                        if (data.updated.title)   cells[1].innerHTML = `<span style="color:#64748b;">${data.speaker.title || '—'}</span>`;
                        if (data.updated.company)  cells[2].innerHTML = `<span style="font-weight:500;">${data.speaker.company || '—'}</span>`;
                        if (data.updated.country && cells[5]) {
                            cells[5].innerHTML = data.speaker.country
                                ? `<div style="display:flex;align-items:center;gap:5px;color:#64748b;"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#94a3b8;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>${data.speaker.country}</div>`
                                : '—';
                        }
                        if (data.updated.seniority && cells[4]) {
                            const s = (data.speaker.seniority || '').toLowerCase();
                            let cls = '';
                            if (s.includes('c-') || ['ceo','cto','coo','cmo','cfo'].includes(s)) cls = 'seniority-c';
                            else if (s.includes('vp')) cls = 'seniority-vp';
                            else if (s.includes('director')) cls = 'seniority-director';
                            else if (s.includes('manager')) cls = 'seniority-manager';
                            cells[4].innerHTML = data.speaker.seniority
                                ? `<span class="seniority-badge ${cls}">${data.speaker.seniority}</span>`
                                : '<span style="color:#cbd5e1;">—</span>';
                        }
                    }
                    // Update LinkedIn icon if found
                    if (data.updated.linkedin_url && data.speaker.linkedin_url) {
                        const liEl = document.getElementById('linkedin-' + id);
                        if (liEl) {
                            liEl.outerHTML = `<a href="${data.speaker.linkedin_url}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;margin-left:4px;color:#0077b5;" title="LinkedIn Profile" id="linkedin-${id}"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>`;
                        }
                    }

                    // Flash the row green briefly
                    row.style.transition = 'background 300ms';
                    row.style.background = '#f0fdf4';
                    setTimeout(() => { row.style.background = ''; }, 2000);
                }
            }
        }
    } catch (e) {
        showVerifyToast(name, null, null, 'Network error: ' + e.message, null);
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHTML;
    }
}

function showVerifyToast(name, updated, confidence, error, summary) {
    const toast = document.getElementById('verify-toast');
    const title = document.getElementById('verify-toast-title');
    const body  = document.getElementById('verify-toast-body');
    const conf  = document.getElementById('verify-confidence');
    const sumEl = document.getElementById('verify-summary');

    title.textContent = name;

    if (error) {
        body.innerHTML = `<div style="color:#dc2626;font-size:0.84rem;">${error}</div>`;
        conf.style.display = 'none';
        sumEl.textContent = '';
    } else {
        // Confidence badge
        if (confidence) {
            conf.style.display = 'inline-block';
            conf.textContent = confidence;
            conf.className = 'verify-confidence conf-' + confidence.toLowerCase();
        } else {
            conf.style.display = 'none';
        }

        if (updated && Object.keys(updated).length > 0) {
            let html = '';
            for (const [field, vals] of Object.entries(updated)) {
                html += `<div class="verify-change">
                    <span class="verify-field">${field}:</span>
                    <span class="verify-old">${vals.old}</span>
                    <span class="verify-arrow">&rarr;</span>
                    <span class="verify-new">${vals.new}</span>
                </div>`;
            }
            body.innerHTML = html;
        } else {
            body.innerHTML = '<div style="display:flex;align-items:center;gap:6px;font-size:0.84rem;color:#16a34a;font-weight:600;"><svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Profile is up to date — no changes needed.</div>';
        }

        sumEl.textContent = summary || '';
    }

    toast.classList.add('show');

    // Auto-hide after 8s
    clearTimeout(window._verifyTimer);
    window._verifyTimer = setTimeout(() => closeVerifyToast(), 8000);
}

function closeVerifyToast() {
    document.getElementById('verify-toast').classList.remove('show');
}

// ── Find LinkedIn ───────────────────────────────────────────────────
async function askHermes(id, url, name) {
    const btn = document.getElementById('hermes-btn-' + id);
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="verify-spin"></span> Hermes…';

    try {
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const data = await resp.json();

        if (!resp.ok || data.error) {
            showVerifyToast(name + ' — Hermes', null, null, data.error || 'Hermes call failed.', null);
        } else {
            showVerifyToast(name + ' — Hermes', data.updated, data.confidence, null, data.summary);
            // Update table cells in place (mirror the verify handler)
            if (data.updated && Object.keys(data.updated).length > 0) {
                const row = btn.closest('tr');
                if (row && data.speaker) {
                    const cells = row.querySelectorAll('td');
                    if (data.updated.title)   cells[1].innerHTML = `<span style="color:#64748b;">${data.speaker.title || '—'}</span>`;
                    if (data.updated.company) cells[2].innerHTML = `<span style="font-weight:500;">${data.speaker.company || '—'}</span>`;
                    if (data.updated.country && cells[5]) {
                        cells[5].innerHTML = data.speaker.country
                            ? `<div style="display:flex;align-items:center;gap:5px;color:#64748b;"><svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="color:#94a3b8;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>${data.speaker.country}</div>`
                            : '—';
                    }
                    if (data.updated.seniority && cells[4]) {
                        const s = (data.speaker.seniority || '').toLowerCase();
                        let cls = '';
                        if (s.includes('c-') || ['ceo','cto','coo','cmo','cfo','c suite'].includes(s)) cls = 'seniority-c';
                        else if (s.includes('vp')) cls = 'seniority-vp';
                        else if (s.includes('director')) cls = 'seniority-director';
                        else if (s.includes('manager')) cls = 'seniority-manager';
                        cells[4].innerHTML = data.speaker.seniority
                            ? `<span class="seniority-badge ${cls}">${data.speaker.seniority}</span>`
                            : '<span style="color:#cbd5e1;">—</span>';
                    }
                    if (data.updated.linkedin_url && data.speaker.linkedin_url) {
                        const liEl = document.getElementById('linkedin-' + id);
                        if (liEl) {
                            liEl.outerHTML = `<a href="${data.speaker.linkedin_url}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;margin-left:4px;color:#0077b5;" title="LinkedIn Profile" id="linkedin-${id}"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>`;
                        }
                    }
                    row.style.transition = 'background 300ms';
                    row.style.background = '#faf5ff';
                    setTimeout(() => { row.style.background = ''; }, 2000);
                }
            }
        }
    } catch (e) {
        showVerifyToast(name + ' — Hermes', null, null, 'Network error: ' + e.message, null);
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHTML;
    }
}

async function findLinkedIn(id, url, name, existingUrl) {
    // If already has LinkedIn URL, open it
    if (existingUrl && existingUrl.includes('linkedin.com')) {
        window.open(existingUrl, '_blank');
        return;
    }

    const btn = document.getElementById('li-btn-' + id);
    const origHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="verify-spin" style="border-color:#b8d4e8;border-top-color:#0077b5;"></span> Searching…';

    try {
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        });
        const data = await resp.json();

        if (!resp.ok || data.error) {
            showVerifyToast(name, null, null, data.error || 'LinkedIn search failed.', null);
        } else if (data.linkedin_url) {
            // Found — update the LinkedIn icon next to email
            const liEl = document.getElementById('linkedin-' + id);
            if (liEl) {
                liEl.outerHTML = `<a href="${data.linkedin_url}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;margin-left:4px;color:#0077b5;" title="LinkedIn Profile" id="linkedin-${id}"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>`;
            }

            // Update button to open URL next time
            btn.setAttribute('onclick', `findLinkedIn(${id}, '${url}', '${name.replace(/'/g,"\\'")}', '${data.linkedin_url.replace(/'/g,"\\'")}')`);

            // Flash row
            const row = btn.closest('tr');
            if (row) {
                row.style.transition = 'background 300ms';
                row.style.background = '#f0f7fb';
                setTimeout(() => { row.style.background = ''; }, 2000);
            }

            showVerifyToast(name, { linkedin_url: { old: '(empty)', new: data.linkedin_url } }, data.confidence, null, data.summary);
        } else {
            // Not found — show clear "not found" message
            const toast = document.getElementById('verify-toast');
            const title = document.getElementById('verify-toast-title');
            const body  = document.getElementById('verify-toast-body');
            const conf  = document.getElementById('verify-confidence');
            const sumEl = document.getElementById('verify-summary');

            title.textContent = name;
            conf.style.display = 'none';
            body.innerHTML = `<div style="display:flex;align-items:center;gap:6px;font-size:0.84rem;color:#f59e0b;font-weight:600;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                LinkedIn profile not found
            </div>`;
            sumEl.textContent = data.summary || 'Could not find a matching LinkedIn profile for this speaker.';
            toast.classList.add('show');
            clearTimeout(window._verifyTimer);
            window._verifyTimer = setTimeout(() => closeVerifyToast(), 8000);
        }
    } catch (e) {
        showVerifyToast(name, null, null, 'Network error: ' + e.message, null);
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHTML;
    }
}

// ── Inline speaker reply-category change ─────────────────────────────────────
function onSpeakerCategoryChange(sel) {
    const url = sel.dataset.url;
    const cat = sel.value;
    const colors = {
        'Confirmed':      { fg:'#0891b2', bg:'#ecfeff' },
        'Interested':     { fg:'#16a34a', bg:'#f0fdf4' },
        'Not Interested': { fg:'#64748b', bg:'#f8fafc' },
        'Info Request':   { fg:'#2563eb', bg:'#eff6ff' },
        'Out of Office':  { fg:'#ca8a04', bg:'#fefce8' },
        'Spam':           { fg:'#dc2626', bg:'#fef2f2' },
        'Negative':       { fg:'#9f1239', bg:'#fff1f2' },
        'Manual Review':  { fg:'#94a3b8', bg:'#f1f5f9' },
        'No Reply':       { fg:'#f97316', bg:'#fff7ed' },
        'Bounced':        { fg:'#7c3aed', bg:'#f5f3ff' },
    };
    sel.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ category: cat }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) throw new Error(data.message || data.error || 'Failed');
        const c = colors[cat] || colors['Manual Review'];
        sel.style.background = c.bg;
        sel.style.color = c.fg;
        sel.style.borderColor = c.fg + '33';
        sel.style.backgroundImage =
            'url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'8\' height=\'8\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'' +
            encodeURIComponent(c.fg) + '\' stroke-width=\'3\' stroke-linecap=\'round\'><path d=\'M6 9l6 6 6-6\'/></svg>")';
        sel.style.backgroundRepeat = 'no-repeat';
        sel.style.backgroundPosition = 'right 5px center';
        showVerifyToast(null, null, null, null, '✓ Reply set to ' + cat);
    })
    .catch(e => {
        showVerifyToast(null, null, null, 'Failed to update: ' + e.message, null);
    })
    .finally(() => { sel.disabled = false; });
}
</script>
@endsection
