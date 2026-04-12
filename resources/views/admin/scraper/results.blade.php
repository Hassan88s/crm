@extends('layouts.admin-sidebar')
@section('title', 'Scraper Results')
@section('page-title', 'Web Scraper')

@section('extra-styles')
<style>
    /* Result table */
    .results-table { width:100%; border-collapse:collapse; }
    .results-table th {
        text-align:left; font-size:0.72rem; font-weight:700; color:#64748b;
        text-transform:uppercase; letter-spacing:0.06em;
        padding:0.65rem 1rem; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    }
    .results-table th:first-child { padding-left:1.25rem; }
    .results-table td { padding:0.8rem 1rem; font-size:0.875rem; color:#374151; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    .results-table td:first-child { padding-left:1.25rem; }
    .results-table tbody tr:hover td { background:#fafbff; }
    .results-table tbody tr.exists td { opacity:0.5; }
    .results-table tbody tr:last-child td { border-bottom:none; }

    /* Avatar */
    .sp-avatar {
        width:34px; height:34px; border-radius:50%; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:0.78rem; color:#fff;
    }

    /* Seniority badge */
    .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:600; background:#f1f5f9; color:#475569; }
    .badge-csuite   { background:#dbeafe; color:#1d4ed8; }
    .badge-vp       { background:#f3e8ff; color:#7e22ce; }
    .badge-director { background:#fef9c3; color:#854d0e; }
    .badge-head     { background:#dcfce7; color:#16a34a; }
    .badge-manager  { background:#fce7f3; color:#be185d; }
    .badge-founder  { background:#ffedd5; color:#c2410c; }

    /* Toolbar */
    .toolbar { display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem; flex-wrap:wrap; }
    .search-box {
        display:flex; align-items:center; gap:8px;
        background:#fff; border:1.5px solid #e2e8f0; border-radius:8px;
        padding:0.5rem 0.85rem; min-width:220px;
    }
    .search-box svg { width:15px; height:15px; color:#94a3b8; flex-shrink:0; }
    .search-box input { border:none; outline:none; font-size:0.875rem; color:#0f172a; background:transparent; width:100%; font-family:inherit; }

    /* Stat chips */
    .stat-chips { display:flex; gap:0.75rem; margin-bottom:1.25rem; flex-wrap:wrap; }
    .stat-chip { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:0.55rem 0.9rem; }
    .stat-chip-val { font-size:1.1rem; font-weight:700; color:#0f172a; }
    .stat-chip-lbl { font-size:0.72rem; color:#64748b; }

    .exists-badge { display:inline-block; padding:1px 6px; border-radius:4px; font-size:0.68rem; font-weight:700; background:#fef9c3; color:#854d0e; }

    .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; border-radius:8px; padding:0.75rem 1rem; font-size:0.875rem; margin-bottom:1rem; display:flex; align-items:center; gap:8px; }
</style>
@endsection

@section('content')

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
    <div>
        <h1 style="font-size:1.3rem; font-weight:700; color:#0f172a;">Scrape Results</h1>
        <p style="color:#64748b; font-size:0.875rem; margin-top:3px;">
            Found <strong>{{ count($people) }}</strong> people from
            <a href="{{ $url }}" target="_blank" style="color:#2563eb; text-decoration:none; word-break:break-all;">{{ $url }}</a>
        </p>
    </div>
    <a href="{{ route('admin.scraper.index') }}" class="btn btn-outline" style="font-size:0.85rem;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Scrape Another
    </a>
</div>

@php
    $newCount      = collect($people)->where('exists', false)->count();
    $existingCount = collect($people)->where('exists', true)->count();
    $withEmail     = collect($people)->filter(fn($p) => !empty($p['email']))->count();
    $colors = ['#2563eb','#16a34a','#9333ea','#ef4444','#ca8a04','#0891b2','#d946ef','#f97316'];
@endphp

@if(count($people) === 0)
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:4rem 2rem; text-align:center;">
        <svg width="52" height="52" fill="none" viewBox="0 0 24 24" stroke="#cbd5e1" stroke-width="1" style="margin:0 auto 1rem; display:block;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 style="font-size:1rem; font-weight:700; color:#64748b; margin-bottom:0.4rem;">No speakers found</h3>
        <p style="font-size:0.875rem; color:#94a3b8; margin-bottom:1.5rem; max-width:400px; margin-left:auto; margin-right:auto;">
            The scraper couldn't extract speakers from this page. Try a different page type or use the speakers/team page directly.
        </p>
        <a href="{{ route('admin.scraper.index') }}" class="btn">Try Another URL</a>
    </div>
@else

    {{-- Stats --}}
    <div class="stat-chips">
        <div class="stat-chip">
            <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div><div class="stat-chip-val">{{ count($people) }}</div><div class="stat-chip-lbl">Found</div></div>
        </div>
        <div class="stat-chip">
            <div style="width:32px;height:32px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div><div class="stat-chip-val">{{ $newCount }}</div><div class="stat-chip-lbl">New</div></div>
        </div>
        <div class="stat-chip">
            <div style="width:32px;height:32px;border-radius:8px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#ca8a04" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div><div class="stat-chip-val">{{ $existingCount }}</div><div class="stat-chip-lbl">Already in DB</div></div>
        </div>
        <div class="stat-chip">
            <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
            </div>
            <div><div class="stat-chip-val">{{ $withEmail }}</div><div class="stat-chip-lbl">With Email</div></div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="toolbar">
        <div class="search-box">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="search-input" placeholder="Filter by name, title, company…" oninput="filterResults(this.value)">
        </div>
        <button type="button" class="btn btn-outline" style="font-size:0.82rem;" onclick="selectAll(true)">Select All New</button>
        <button type="button" class="btn btn-outline" style="font-size:0.82rem;" onclick="selectAll(false)">Deselect All</button>
        <span id="sel-count" style="font-size:0.82rem; color:#64748b;">0 selected</span>
    </div>

    <form action="{{ route('admin.scraper.import') }}" method="POST" id="import-form">
        @csrf
        <input type="hidden" name="people" value="{{ json_encode($people) }}">
        <input type="hidden" name="event_id" value="{{ $event_id }}">

        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:1.25rem;">
            <table class="results-table" id="results-table">
                <thead>
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" id="check-all" onchange="toggleAll(this)" style="accent-color:#2563eb; width:15px; height:15px;">
                        </th>
                        <th>Speaker</th>
                        <th>Title / Role</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Seniority</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($people as $i => $person)
                    @php
                        $fullName = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
                        $initial  = strtoupper(substr($person['first_name'] ?? 'U', 0, 1));
                        $color    = $colors[$i % count($colors)];
                        $exists   = $person['exists'] ?? false;
                        $sen      = strtolower($person['seniority'] ?? '');
                        $senClass = match(true) {
                            str_contains($sen,'c-suite') || str_contains($sen,'c suite') => 'badge-csuite',
                            str_contains($sen,'vp') => 'badge-vp',
                            str_contains($sen,'director') => 'badge-director',
                            str_contains($sen,'head') => 'badge-head',
                            str_contains($sen,'manager') => 'badge-manager',
                            str_contains($sen,'founder') => 'badge-founder',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $exists ? 'exists' : '' }}"
                        data-search="{{ strtolower($fullName . ' ' . ($person['title'] ?? '') . ' ' . ($person['company'] ?? '')) }}">
                        <td>
                            <input type="checkbox" name="selected[]" value="{{ $i }}"
                                   {{ !$exists ? '' : 'disabled' }}
                                   onchange="updateCount()"
                                   style="accent-color:#2563eb; width:15px; height:15px;">
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div class="sp-avatar" style="background:{{ $color }};">{{ $initial }}</div>
                                <div>
                                    <div style="font-weight:600; color:#0f172a;">{{ $fullName ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="color:#64748b; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            {{ $person['title'] ?: '—' }}
                        </td>
                        <td style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:500;">
                            {{ $person['company'] ?: '—' }}
                        </td>
                        <td style="color:#64748b; font-size:0.8rem;">
                            {{ $person['email'] ?: '—' }}
                        </td>
                        <td>
                            @if($person['seniority'])
                                <span class="badge {{ $senClass }}">{{ $person['seniority'] }}</span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($exists)
                                <span class="exists-badge">In DB</span>
                            @else
                                <span style="font-size:0.72rem; color:#16a34a; font-weight:600;">New</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Import footer --}}
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
            <div>
                @if($event_id)
                    @php $ev = $events->find($event_id); @endphp
                    <div style="font-size:0.82rem; color:#64748b;">
                        Will be assigned to:
                        <span style="font-weight:600; color:#2563eb;">{{ $ev->name ?? '' }}</span>
                    </div>
                @else
                    <div style="font-size:0.82rem; color:#94a3b8;">No event assigned — you can assign later</div>
                @endif
            </div>
            <div style="display:flex; gap:0.6rem; align-items:center;">
                <a href="{{ route('admin.scraper.index') }}" class="btn btn-outline" style="font-size:0.85rem;">Scrape Another</a>
                <button type="submit" class="btn" onclick="return confirmImport()">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import Selected
                </button>
            </div>
        </div>
    </form>

@endif

<script>
function updateCount() {
    const n = document.querySelectorAll('#results-table input[type=checkbox][name="selected[]"]:checked').length;
    document.getElementById('sel-count').textContent = n + ' selected';
}

function toggleAll(master) {
    document.querySelectorAll('#results-table input[type=checkbox][name="selected[]"]:not(:disabled)').forEach(cb => {
        const row = cb.closest('tr');
        if (row.style.display !== 'none') cb.checked = master.checked;
    });
    updateCount();
}

function selectAll(onlyNew) {
    document.querySelectorAll('#results-table input[type=checkbox][name="selected[]"]:not(:disabled)').forEach(cb => {
        const row = cb.closest('tr');
        if (row.style.display !== 'none') cb.checked = onlyNew;
    });
    updateCount();
}

function filterResults(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('#results-table tbody tr').forEach(row => {
        const match = !q || (row.getAttribute('data-search') || '').includes(q);
        row.style.display = match ? '' : 'none';
    });
}

function confirmImport() {
    const n = document.querySelectorAll('#results-table input[type=checkbox][name="selected[]"]:checked').length;
    if (n === 0) { alert('Please select at least one speaker to import.'); return false; }
    return confirm(`Import ${n} speaker${n !== 1 ? 's' : ''} into the CRM?`);
}

// Auto-select all new on load
document.addEventListener('DOMContentLoaded', function() {
    selectAll(true);
});
</script>
@endsection
