@extends('layouts.admin-sidebar')

@section('title', 'Classified Replies')
@section('page-title', 'Classified Replies')

@section('extra-styles')
<style>
    /* ── Stats row ── */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.9rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 4px;
        cursor: pointer;
        transition: box-shadow 140ms, border-color 140ms;
        text-decoration: none;
    }
    .stat-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-color: #cbd5e1; }
    .stat-card.active { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
    .stat-card .stat-icon { font-size: 1.2rem; line-height: 1; }
    .stat-card .stat-count { font-size: 1.4rem; font-weight: 700; color: #0f172a; line-height: 1; }
    .stat-card .stat-label { font-size: 0.72rem; color: #64748b; font-weight: 500; }

    /* ── Filter tabs ── */
    .filter-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 1.25rem;
    }
    .filter-tab {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0.38rem 0.85rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        text-decoration: none;
        transition: background 120ms, border-color 120ms, color 120ms;
    }
    .filter-tab:hover { background: #f1f5f9; border-color: #cbd5e1; color: #334155; }
    .filter-tab.active { border-color: var(--accent); background: #eff6ff; color: var(--accent); }
    .filter-tab .tab-count {
        background: #e2e8f0;
        border-radius: 999px;
        font-size: 0.7rem;
        padding: 0px 6px;
        font-weight: 700;
        color: #475569;
    }
    .filter-tab.active .tab-count { background: #bfdbfe; color: var(--accent); }

    /* ── Table ── */
    .replies-table { width: 100%; border-collapse: collapse; }
    .replies-table th {
        background: #f8fafc;
        padding: 0.65rem 1rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    .replies-table td {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .replies-table tbody tr:hover { background: #f8fafc; }
    .replies-table tbody tr:last-child td { border-bottom: none; }

    /* ── Category badge ── */
    .cat-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    /* ── Score bar ── */
    .score-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 80px;
    }
    .score-bar-bg {
        flex: 1;
        height: 6px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .score-bar-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 300ms;
    }
    .score-num {
        font-size: 0.78rem;
        font-weight: 700;
        color: #475569;
        min-width: 26px;
        text-align: right;
    }

    /* ── Speaker chip ── */
    .speaker-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
    }
    .speaker-avatar {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: var(--accent);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.7rem;
        flex-shrink: 0;
    }
    .speaker-avatar.unknown { background: #cbd5e1; color: #64748b; }

    /* ── Empty state ── */
    .empty-state {
        text-align: center;
        padding: 3.5rem 1rem;
        color: #94a3b8;
    }
    .empty-state svg { width: 48px; height: 48px; margin: 0 auto 1rem; opacity: 0.35; }
    .empty-state h3 { font-size: 1.05rem; font-weight: 600; color: #64748b; margin-bottom: 0.4rem; }
    .empty-state p { font-size: 0.875rem; }

    /* ── Header row ── */
    .page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .page-header-left h1 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
    }
    .page-header-left p { font-size: 0.85rem; color: #64748b; }

    /* ── Fetch button ── */
    #fetchBtn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #2563eb;
        color: #fff;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 9px;
        font-size: 0.875rem;
        font-weight: 700;
        cursor: pointer;
        transition: filter 120ms;
    }
    #fetchBtn:hover:not(:disabled) { filter: brightness(1.1); }
    #fetchBtn:disabled { opacity: 0.65; cursor: not-allowed; }
    #fetchBtn .spinner {
        display: none;
        width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    #fetchStatus {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 4px;
        min-height: 18px;
        text-align: right;
    }
    #fetchStatus.ok { color: #16a34a; }
    #fetchStatus.err { color: #dc2626; }

    /* ── Reclassify ── */
    .reclassify-btn {
        background: none;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: background 120ms, color 120ms;
        white-space: nowrap;
    }
    .reclassify-btn:hover { background: #f1f5f9; color: #1e293b; }
    .reclassify-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    /* ── Row hover ── */
    .reply-row:hover td { background: #f8fafc; }

    /* ── From cell ── */
    .from-cell { max-width: 180px; }
    .from-name { font-weight: 600; color: #0f172a; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .from-email-small { font-size: 0.75rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* ── Subject cell ── */
    .subject-cell { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.85rem; }

    /* ── Pagination ── */
    .pagination-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.85rem 1rem;
        border-top: 1px solid #e2e8f0;
        font-size: 0.8rem;
        color: #64748b;
    }
    .pagination-links { display: flex; gap: 4px; }
    .pagination-links a,
    .pagination-links span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
        border-radius: 6px;
        padding: 0 6px;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        transition: background 120ms;
    }
    .pagination-links a:hover { background: #f1f5f9; }
    .pagination-links span.current { background: var(--accent); color: #fff; border-color: var(--accent); }
    .pagination-links span.disabled { opacity: 0.4; cursor: not-allowed; }

    /* ── Toast ── */
    #toast {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        background: #1e293b;
        color: #fff;
        padding: 0.65rem 1.1rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 250ms, transform 250ms;
        z-index: 999;
        pointer-events: none;
    }
    #toast.show { opacity: 1; transform: translateY(0); }
    #toast.ok { background: #16a34a; }
    #toast.err { background: #dc2626; }
</style>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header-left">
        <h1>Classified Replies</h1>
        <p>AI-classified email replies from your speaker outreach</p>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <button id="fetchBtn" onclick="fetchAndClassify()">
                <div class="spinner" id="btnSpinner"></div>
                <svg id="btnIcon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Fetch &amp; Classify Now
            </button>

            @if(($counts['all'] ?? 0) > 0)
                @php
                    $delScope    = $category !== 'all' ? $category : null;
                    $delCount    = $delScope ? ($counts[$delScope] ?? 0) : ($counts['all'] ?? 0);
                    $delLabel    = $delScope ? "Delete \"{$delScope}\" ({$delCount})" : "Delete All ({$delCount})";
                    $confirmText = $delScope
                        ? "Delete all {$delCount} reply(ies) in \"{$delScope}\"? This cannot be undone."
                        : "Delete ALL {$delCount} replies? This cannot be undone.";
                @endphp
                <form action="{{ route('admin.replies.destroyAll') }}" method="POST"
                      onsubmit="return confirm('{{ addslashes($confirmText) }}');" style="margin:0;">
                    @csrf @method('DELETE')
                    @if($delScope)
                        <input type="hidden" name="category" value="{{ $delScope }}">
                    @endif
                    <button type="submit"
                            style="display:inline-flex; align-items:center; gap:6px; background:#ef4444; color:#fff; border:none; padding:0.6rem 1.1rem; border-radius:9px; font-size:0.85rem; font-weight:700; cursor:pointer;">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        {{ $delLabel }}
                    </button>
                </form>
            @endif
        </div>
        <div id="fetchStatus">
            @if($lastFetch)
                Last fetch: {{ \Carbon\Carbon::parse($lastFetch)->diffForHumans() }}
            @else
                No fetches yet
            @endif
        </div>
    </div>
</div>

{{-- Stats row --}}
<div class="stats-row">
    <a href="{{ route('admin.replies.index', ['category' => 'all']) }}"
       class="stat-card {{ $category === 'all' ? 'active' : '' }}">
        <div class="stat-icon">📬</div>
        <div class="stat-count">{{ $counts['all'] ?? 0 }}</div>
        <div class="stat-label">All Replies</div>
    </a>
    @php
    $catInfo = [
        'Confirmed'      => ['icon' => '✅', 'label' => 'Confirmed'],
        'Interested'     => ['icon' => '🟢', 'label' => 'Interested'],
        'Not Interested' => ['icon' => '⚫', 'label' => 'Not Interested'],
        'Info Request'   => ['icon' => '🔵', 'label' => 'Info Request'],
        'Out of Office'  => ['icon' => '🟡', 'label' => 'Out of Office'],
        'Spam'           => ['icon' => '🔴', 'label' => 'Spam'],
        'Negative'       => ['icon' => '🚫', 'label' => 'Negative'],
        'Manual Review'  => ['icon' => '⚪', 'label' => 'Manual Review'],
        'No Reply'       => ['icon' => '🟠', 'label' => 'No Reply'],
        'Bounced'        => ['icon' => '↩️', 'label' => 'Bounced'],
    ];
    @endphp
    @foreach($catInfo as $key => $info)
    <a href="{{ route('admin.replies.index', ['category' => $key]) }}"
       class="stat-card {{ $category === $key ? 'active' : '' }}">
        <div class="stat-icon">{{ $info['icon'] }}</div>
        <div class="stat-count">{{ $counts[$key] ?? 0 }}</div>
        <div class="stat-label">{{ $info['label'] }}</div>
    </a>
    @endforeach
</div>

{{-- Filter tabs --}}
<div class="filter-tabs">
    <a href="{{ route('admin.replies.index', ['category' => 'all']) }}"
       class="filter-tab {{ $category === 'all' ? 'active' : '' }}">
        All
        <span class="tab-count">{{ $counts['all'] ?? 0 }}</span>
    </a>
    @foreach($categories as $cat)
    <a href="{{ route('admin.replies.index', ['category' => $cat]) }}"
       class="filter-tab {{ $category === $cat ? 'active' : '' }}">
        {{ $catInfo[$cat]['icon'] ?? '' }} {{ $cat }}
        <span class="tab-count">{{ $counts[$cat] ?? 0 }}</span>
    </a>
    @endforeach
</div>

{{-- Table --}}
<div class="card" style="overflow:hidden;">
    @if($replies->count() === 0)
        <div class="empty-state">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <h3>No replies found</h3>
            <p>
                @if($category !== 'all')
                    No replies classified as "{{ $category }}" yet.
                    <a href="{{ route('admin.replies.index') }}" style="color:var(--accent);">View all replies</a>
                @else
                    Click "Fetch &amp; Classify Now" to import and classify your inbox replies.
                @endif
            </p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="replies-table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Speaker</th>
                        <th>Category</th>
                        <th>Score</th>
                        <th>Received</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($replies as $reply)
                    <tr id="row-{{ $reply->id }}" class="reply-row" onclick="window.location='{{ route('admin.replies.show', $reply) }}'" style="cursor:pointer;">
                        {{-- From --}}
                        <td>
                            <div class="from-cell">
                                @if($reply->from_name)
                                    <div class="from-name" title="{{ $reply->from_name }}">{{ $reply->from_name }}</div>
                                @endif
                                <div class="from-email-small" title="{{ $reply->from_email }}">{{ $reply->from_email }}</div>
                            </div>
                        </td>

                        {{-- Subject --}}
                        <td>
                            <div class="subject-cell" title="{{ $reply->subject }}">
                                {{ $reply->subject ?: '(no subject)' }}
                            </div>
                        </td>

                        {{-- Speaker --}}
                        <td>
                            @if($reply->speaker)
                                <div class="speaker-chip">
                                    <div class="speaker-avatar">{{ strtoupper(substr($reply->speaker->first_name ?? '?', 0, 1)) }}</div>
                                    <span style="font-size:0.8rem;color:#334155;font-weight:500;">
                                        {{ $reply->speaker->first_name }} {{ $reply->speaker->last_name }}
                                    </span>
                                </div>
                            @else
                                <div class="speaker-chip">
                                    <div class="speaker-avatar unknown">?</div>
                                    <span style="font-size:0.78rem;color:#94a3b8;">Unknown</span>
                                </div>
                            @endif
                        </td>

                        {{-- Category badge --}}
                        <td>
                            <span class="cat-badge cat-{{ $reply->id }}"
                                  style="background:{{ $reply->category_bg }};color:{{ $reply->category_color }};">
                                {{ $reply->category_icon }} {{ $reply->category }}
                            </span>
                        </td>

                        {{-- Score --}}
                        <td>
                            @php
                                $score = $reply->ai_score;
                                $barColor = '#94a3b8';
                                if ($score !== null) {
                                    if ($score >= 60) $barColor = '#16a34a';
                                    elseif ($score >= 30) $barColor = '#f59e0b';
                                    else $barColor = '#ef4444';
                                }
                            @endphp
                            @if($score !== null)
                                <div class="score-wrap">
                                    <div class="score-bar-bg">
                                        <div class="score-bar-fill"
                                             style="width:{{ $score }}%;background:{{ $barColor }};"></div>
                                    </div>
                                    <div class="score-num score-num-{{ $reply->id }}">{{ $score }}</div>
                                </div>
                            @else
                                <span style="color:#94a3b8;font-size:0.78rem;">—</span>
                            @endif
                        </td>

                        {{-- Received --}}
                        <td>
                            <span title="{{ $reply->received_at->format('Y-m-d H:i') }}"
                                  style="font-size:0.8rem;color:#64748b;white-space:nowrap;">
                                {{ $reply->received_at->diffForHumans() }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td onclick="event.stopPropagation();" style="white-space:nowrap;">
                            <form action="{{ route('admin.replies.destroy', $reply) }}" method="POST"
                                  onsubmit="event.stopPropagation(); return confirm('Delete this reply from {{ addslashes($reply->from_email) }}?');"
                                  style="display:inline; margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        title="Delete this reply"
                                        style="background:none; border:1px solid #fecaca; color:#dc2626; border-radius:6px; padding:4px 9px; font-size:0.74rem; font-weight:600; cursor:pointer;">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($replies->hasPages())
        <div class="pagination-wrap">
            <div>
                Showing {{ $replies->firstItem() }}–{{ $replies->lastItem() }} of {{ $replies->total() }} replies
            </div>
            <div class="pagination-links">
                {{-- Previous --}}
                @if($replies->onFirstPage())
                    <span class="disabled">&lsaquo;</span>
                @else
                    <a href="{{ $replies->previousPageUrl() }}">&lsaquo;</a>
                @endif

                {{-- Pages --}}
                @foreach($replies->getUrlRange(1, $replies->lastPage()) as $page => $url)
                    @if($page == $replies->currentPage())
                        <span class="current">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($replies->hasMorePages())
                    <a href="{{ $replies->nextPageUrl() }}">&rsaquo;</a>
                @else
                    <span class="disabled">&rsaquo;</span>
                @endif
            </div>
        </div>
        @endif
    @endif
</div>

{{-- Toast notification --}}
<div id="toast"></div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
           || '{{ csrf_token() }}';

// ── Fetch & Classify ──────────────────────────────────────────────────────────
function fetchAndClassify() {
    const btn = document.getElementById('fetchBtn');
    const spinner = document.getElementById('btnSpinner');
    const icon = document.getElementById('btnIcon');
    const status = document.getElementById('fetchStatus');

    btn.disabled = true;
    spinner.style.display = 'block';
    icon.style.display = 'none';
    status.textContent = 'Fetching and classifying…';
    status.className = '';

    fetch('{{ route('admin.replies.fetch') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            const n = data.classified || 0;
            status.textContent = n > 0
                ? `Done! Classified ${n} new reply(ies).`
                : 'Done! No new replies to classify.';
            status.className = 'ok';
            showToast(n > 0 ? `✓ ${n} new reply(ies) classified` : '✓ No new replies found', 'ok');
            if (n > 0) setTimeout(() => location.reload(), 1400);
        } else {
            status.textContent = 'Error: ' + (data.error || 'Unknown error');
            status.className = 'err';
            showToast('✗ ' + (data.error || 'Failed'), 'err');
        }
    })
    .catch(err => {
        status.textContent = 'Network error: ' + err.message;
        status.className = 'err';
        showToast('✗ Network error', 'err');
    })
    .finally(() => {
        btn.disabled = false;
        spinner.style.display = 'none';
        icon.style.display = 'block';
    });
}

// ── Reclassify a single reply ────────────────────────────────────────────────
function reclassify(id, url) {
    const btn = document.getElementById('reclassify-' + id);
    btn.disabled = true;
    btn.textContent = '…';

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            // Update badge in-place
            const badge = document.querySelector('.cat-' + id);
            if (badge) {
                const cat = data.category;
                const colors = {
                    'Interested':     { bg: '#f0fdf4', color: '#16a34a', icon: '🟢' },
                    'Not Interested': { bg: '#f8fafc', color: '#64748b', icon: '⚫' },
                    'Info Request':   { bg: '#eff6ff', color: '#2563eb', icon: '🔵' },
                    'Out of Office':  { bg: '#fefce8', color: '#ca8a04', icon: '🟡' },
                    'Spam':           { bg: '#fef2f2', color: '#dc2626', icon: '🔴' },
                    'Negative':       { bg: '#fff1f2', color: '#9f1239', icon: '🚫' },
                    'Manual Review':  { bg: '#f1f5f9', color: '#94a3b8', icon: '⚪' },
                    'No Reply':       { bg: '#fff7ed', color: '#f97316', icon: '🟠' },
                    'Bounced':        { bg: '#f5f3ff', color: '#7c3aed', icon: '↩️' },
                    'Confirmed':      { bg: '#ecfeff', color: '#0891b2', icon: '✅' },
                };
                const c = colors[cat] || colors['Manual Review'];
                badge.style.background = c.bg;
                badge.style.color = c.color;
                badge.textContent = c.icon + ' ' + cat;
            }
            // Update score
            const scoreEl = document.querySelector('.score-num-' + id);
            if (scoreEl && data.score !== null) scoreEl.textContent = data.score;

            showToast('✓ Reclassified as ' + data.category, 'ok');
        } else {
            showToast('✗ ' + (data.error || 'Reclassification failed'), 'err');
        }
    })
    .catch(() => showToast('✗ Network error', 'err'))
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '↻ Reclassify';
    });
}

// ── Toast ────────────────────────────────────────────────────────────────────
let toastTimer;
function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'show ' + (type || '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.className = ''; }, 3000);
}
</script>
@endsection
