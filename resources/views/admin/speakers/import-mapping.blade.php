@extends('layouts.admin-sidebar')

@section('title', 'Map CSV Columns')
@section('page-title', 'Map CSV Columns')

@section('extra-styles')
<style>
    .map-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; margin-bottom:1.25rem; }
    .map-card h2 { font-size:1.1rem; font-weight:700; color:#0f172a; margin-bottom:0.25rem; }
    .map-meta { font-size:0.82rem; color:#64748b; }
    .meta-row { display:flex; flex-wrap:wrap; align-items:center; gap:0.75rem 1.25rem; margin-top:0.75rem; }
    .meta-row .pill {
        display:inline-flex; align-items:center; gap:6px;
        background:#f1f5f9; border:1px solid #e2e8f0; padding:4px 10px;
        border-radius:999px; font-size:0.78rem; color:#334155; font-weight:600;
    }
    .meta-row .pill svg { width:13px; height:13px; }

    .mapping-table { width:100%; border-collapse:collapse; }
    .mapping-table th {
        background:#f8fafc; padding:0.75rem 1rem; font-size:0.7rem; font-weight:700;
        color:#64748b; text-transform:uppercase; letter-spacing:0.06em; text-align:left;
        border-bottom:1px solid #e2e8f0;
    }
    .mapping-table td {
        padding:0.85rem 1rem; font-size:0.875rem; color:#1e293b;
        border-bottom:1px solid #f1f5f9; vertical-align:middle;
    }
    .mapping-table tbody tr:last-child td { border-bottom:none; }
    .csv-header {
        font-weight:700; color:#0f172a; font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:0.85rem;
    }
    .sample-vals {
        font-size:0.78rem; color:#64748b; line-height:1.4;
        max-width:280px; overflow:hidden;
    }
    .sample-vals span {
        display:inline-block; background:#f1f5f9; border-radius:4px;
        padding:1px 6px; margin:1px 4px 1px 0; max-width:100%;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis; vertical-align:middle;
    }
    .map-select {
        padding:0.5rem 0.7rem; border:1.5px solid #e2e8f0; border-radius:7px;
        font-size:0.85rem; outline:none; background:#fff; min-width:200px;
        transition:border-color 120ms;
    }
    .map-select:focus { border-color:#2563eb; }
    .map-select.skip { color:#94a3b8; }
    .map-select.dup { border-color:#f59e0b; background:#fefce8; }

    .help-box {
        display:flex; align-items:flex-start; gap:0.7rem;
        background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;
        padding:0.85rem 1.1rem; font-size:0.82rem; color:#1e40af; margin-top:1rem;
    }
    .help-box svg { width:18px; height:18px; flex-shrink:0; color:#2563eb; margin-top:1px; }

    .form-footer { display:flex; align-items:center; gap:0.75rem; margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid #f1f5f9; }
</style>
@endsection

@section('content')

<div style="margin-bottom:1.25rem;">
    <a href="{{ route('admin.speakers.import') }}" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.875rem;text-decoration:none;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to upload
    </a>
</div>

@if(session('error'))
<div class="help-box" style="background:#fef2f2; border-color:#fecaca; color:#dc2626;">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('error') }}
</div>
@endif

<form action="{{ route('admin.speakers.import.post') }}" method="POST">
    @csrf
    <input type="hidden" name="cache_key" value="{{ $cacheKey }}">

    <div class="map-card">
        <h2>Map your CSV columns</h2>
        <p class="map-meta">Tell us which CSV column maps to which speaker field. Auto-suggestions are pre-filled where possible.</p>

        <div class="meta-row">
            <span class="pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                {{ $filename }}
            </span>
            <span class="pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ $totalRows }} row{{ $totalRows === 1 ? '' : 's' }} ready
            </span>
            <span class="pill">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                {{ count($header) }} column{{ count($header) === 1 ? '' : 's' }}
            </span>
        </div>

        <div style="margin-top:1rem;">
            <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">Assign all to event (optional)</label>
            <select name="event_id" class="map-select" style="min-width:280px;">
                <option value="">— No event —</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" {{ (string)$eventId === (string)$event->id ? 'selected' : '' }}>
                        {{ $event->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="map-card" style="padding:0; overflow:hidden;">
        <table class="mapping-table">
            <thead>
                <tr>
                    <th style="width:30%;">CSV Column</th>
                    <th>Sample Values</th>
                    <th style="width:280px;">Map to →</th>
                </tr>
            </thead>
            <tbody>
                @foreach($header as $i => $h)
                    @php
                        $samples = [];
                        foreach ($sampleRows as $row) {
                            $val = trim((string)($row[$i] ?? ''));
                            if ($val !== '' && count($samples) < 3) $samples[] = $val;
                        }
                        $suggested = $suggestions[$i] ?? '_skip';
                    @endphp
                    <tr>
                        <td><div class="csv-header">{{ $h ?: '(no header)' }}</div></td>
                        <td>
                            <div class="sample-vals">
                                @forelse($samples as $s)
                                    <span title="{{ $s }}">{{ \Illuminate\Support\Str::limit($s, 60) }}</span>
                                @empty
                                    <span style="background:transparent; color:#cbd5e1; padding:0;">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td>
                            <select name="mapping[{{ $i }}]" class="map-select map-target {{ $suggested === '_skip' ? 'skip' : '' }}" data-idx="{{ $i }}">
                                <option value="_skip" {{ $suggested === '_skip' ? 'selected' : '' }}>— Do not import —</option>
                                @foreach($fields as $key => $label)
                                    <option value="{{ $key }}" {{ $suggested === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="help-box">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <strong>Recommended:</strong> map at least <em>First Name + Last Name</em> (or <em>Full Name</em>) and <em>Email</em>.
            Rows with no name are skipped. Duplicate emails are skipped automatically. If the same speaker field is mapped to more than one CSV column, the mapping is highlighted; the last one wins.
        </div>
    </div>

    <div class="form-footer">
        <button type="submit" class="btn">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Import Speakers
        </button>
        <a href="{{ route('admin.speakers.import') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
// Highlight duplicate mappings
function refreshMappingState() {
    const selects = document.querySelectorAll('.map-target');
    const counts = {};
    selects.forEach(s => {
        const v = s.value;
        if (v && v !== '_skip') counts[v] = (counts[v] || 0) + 1;
    });
    selects.forEach(s => {
        s.classList.toggle('skip', s.value === '_skip');
        s.classList.toggle('dup', s.value !== '_skip' && counts[s.value] > 1);
    });
}
document.querySelectorAll('.map-target').forEach(s => s.addEventListener('change', refreshMappingState));
refreshMappingState();
</script>

@endsection
