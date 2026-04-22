@extends('layouts.admin-sidebar')
@section('title', $folderLabel . ' — Mail')
@section('page-title', $folderLabel)

@section('extra-styles')
<style>
    /* ── Mail layout ── */
    .mail-layout { display:grid; grid-template-columns:200px 1fr; gap:0; min-height:calc(100vh - 200px); }
    @media(max-width:800px){ .mail-layout{grid-template-columns:1fr;} .folder-panel{display:none;} }

    /* ── Folder panel ── */
    .folder-panel { background:#fff; border:1px solid #e2e8f0; border-radius:12px 0 0 12px; overflow:hidden; }
    .folder-head { padding:0.85rem 1rem; border-bottom:1px solid #f1f5f9; font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; }
    .folder-list { padding:0.5rem; }
    .folder-item {
        display:flex; align-items:center; gap:8px;
        padding:0.55rem 0.75rem; border-radius:8px;
        text-decoration:none; color:#374151; font-size:0.85rem; font-weight:500;
        transition:all 120ms; margin-bottom:2px;
    }
    .folder-item:hover { background:#f1f5f9; }
    .folder-item.active { background:#eff6ff; color:#2563eb; font-weight:700; }
    .folder-item.disabled { opacity:0.4; pointer-events:none; }
    .folder-item svg { width:16px; height:16px; flex-shrink:0; opacity:0.65; }
    .folder-item.active svg { opacity:1; color:#2563eb; }
    .folder-badge { margin-left:auto; font-size:0.7rem; font-weight:700; background:#2563eb; color:#fff; min-width:18px; height:18px; border-radius:999px; display:flex; align-items:center; justify-content:center; padding:0 5px; }
    .folder-count { margin-left:auto; font-size:0.72rem; color:#94a3b8; font-weight:600; }
    .folder-divider { height:1px; background:#f1f5f9; margin:0.5rem 0.75rem; }

    /* Contacts link */
    .contacts-link {
        display:flex; align-items:center; gap:8px;
        padding:0.55rem 0.75rem; border-radius:8px;
        text-decoration:none; color:#374151; font-size:0.85rem; font-weight:500;
        transition:all 120ms;
    }
    .contacts-link:hover { background:#f1f5f9; }
    .contacts-link svg { width:16px; height:16px; flex-shrink:0; opacity:0.65; }

    /* ── Email list panel ── */
    .list-panel { background:#fff; border:1px solid #e2e8f0; border-left:none; border-radius:0 12px 12px 0; overflow:hidden; }

    /* Toolbar */
    .list-toolbar { display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; border-bottom:1px solid #f1f5f9; flex-wrap:wrap; }
    .search-box {
        display:flex; align-items:center; gap:8px;
        background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px;
        padding:0.45rem 0.75rem; flex:1; min-width:180px;
    }
    .search-box svg { width:14px; height:14px; color:#94a3b8; flex-shrink:0; }
    .search-box input { border:none; outline:none; font-size:0.84rem; color:#0f172a; background:transparent; width:100%; font-family:inherit; }

    .toolbar-btn {
        display:inline-flex; align-items:center; gap:5px;
        padding:0.4rem 0.8rem; border-radius:7px; border:1px solid #e2e8f0;
        background:#fff; color:#475569; font-size:0.8rem; font-weight:600;
        cursor:pointer; text-decoration:none; transition:all 120ms;
    }
    .toolbar-btn:hover { background:#f8fafc; border-color:#cbd5e1; }
    .toolbar-btn svg { width:14px; height:14px; }

    /* Email list */
    .email-row {
        display:grid; grid-template-columns:auto 1fr auto;
        align-items:center; gap:0.75rem;
        padding:0.75rem 1rem; border-bottom:1px solid #f1f5f9;
        cursor:pointer; text-decoration:none; color:inherit;
        transition:background 120ms;
    }
    .email-row:last-child { border-bottom:none; }
    .email-row:hover { background:#f8fafc; }
    .email-row.unread { background:#fafbff; }
    .email-row.unread .em-subject { font-weight:700; color:#0f172a; }
    .email-row.unread .em-from { font-weight:700; color:#1e293b; }

    .em-avatar {
        width:34px; height:34px; border-radius:50%;
        background:#2563eb; color:#fff;
        display:flex; align-items:center; justify-content:center;
        font-size:0.75rem; font-weight:700; flex-shrink:0;
    }
    .em-from { font-size:0.82rem; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px; }
    .em-subject { font-size:0.85rem; color:#475569; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .em-date { font-size:0.72rem; color:#94a3b8; white-space:nowrap; text-align:right; }
    .em-unread-dot { width:7px; height:7px; border-radius:50%; background:#2563eb; flex-shrink:0; }

    /* Action icons on hover */
    .em-actions { display:flex; gap:4px; align-items:center; opacity:0; transition:opacity 100ms; }
    .email-row:hover .em-actions { opacity:1; }
    .em-action-btn {
        background:none; border:none; cursor:pointer; color:#cbd5e1; padding:3px;
        border-radius:4px; transition:color 100ms, background 100ms;
    }
    .em-action-btn:hover { color:#ef4444; background:#fef2f2; }
    .em-action-btn.move-inbox:hover { color:#2563eb; background:#eff6ff; }
    .em-action-btn svg { width:13px; height:13px; display:block; }

    /* States */
    .state-box { text-align:center; padding:4rem 2rem; }
    .state-box svg { width:48px; height:48px; margin:0 auto 1rem; display:block; color:#cbd5e1; }
    .state-box h3 { font-size:1rem; font-weight:700; color:#64748b; margin-bottom:0.4rem; }
    .state-box p { font-size:0.85rem; color:#94a3b8; }

    /* Pagination */
    .page-bar { display:flex; align-items:center; justify-content:space-between; padding:0.65rem 1rem; border-top:1px solid #f1f5f9; font-size:0.78rem; color:#94a3b8; }
    .page-btns { display:flex; gap:3px; }
    .page-btn { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:28px; border-radius:6px; border:1px solid #e2e8f0; font-size:0.78rem; font-weight:600; color:#374151; text-decoration:none; background:#fff; transition:all 120ms; padding:0 6px; }
    .page-btn:hover:not(.disabled):not(.active) { background:#f1f5f9; }
    .page-btn.active { background:#2563eb; color:#fff; border-color:#2563eb; }
    .page-btn.disabled { color:#d1d5db; pointer-events:none; }

    .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; border-radius:8px; padding:0.65rem 1rem; font-size:0.84rem; margin-bottom:1rem; display:flex; align-items:center; gap:8px; }
    .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; border-radius:8px; padding:0.65rem 1rem; font-size:0.84rem; margin-bottom:1rem; display:flex; align-items:center; gap:8px; }
    .setup-banner { background:#fff; border:2px dashed #e2e8f0; border-radius:12px; padding:3rem 2rem; text-align:center; }
</style>
@endsection

@section('content')

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
    <div>
        <h1 style="font-size:1.25rem; font-weight:700; color:#0f172a;">Mail</h1>
        <p style="color:#64748b; font-size:0.84rem; margin-top:2px;">{{ $folderLabel }} — {{ $total }} message{{ $total !== 1 ? 's' : '' }}</p>
    </div>
    <div style="display:flex; gap:0.5rem;">
        <a href="{{ route('admin.inbox.index', ['folder' => $folder]) }}" class="toolbar-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Refresh
        </a>
        <a href="{{ route('admin.settings') }}#imap" class="toolbar-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Settings
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
@endif
@if(session('error') || $error)
    <div class="alert-error">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ session('error') ?? $error }}
    </div>
@endif

@if(!$configured)
    <div class="setup-banner">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 1rem; display:block;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <h3 style="font-size:1.05rem; font-weight:700; color:#0f172a; margin-bottom:0.5rem;">IMAP Not Configured</h3>
        <p style="color:#64748b; font-size:0.85rem; margin-bottom:1.25rem;">Set up your IMAP server credentials in Settings.</p>
        <a href="{{ route('admin.settings') }}#imap" class="btn">Configure IMAP</a>
    </div>
@else

<div class="mail-layout">

    {{-- ── Folder panel ── --}}
    <div class="folder-panel">
        <div class="folder-head">Folders</div>
        <div class="folder-list">
            @php
                $folderIcons = [
                    'inbox'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>',
                    'sent'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>',
                    'drafts' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                    'spam'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                    'trash'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
                ];
            @endphp

            @foreach($folders as $key => $f)
                <a href="{{ $f['available'] ? route('admin.inbox.index', ['folder' => $key]) : '#' }}"
                   class="folder-item {{ $folder === $key ? 'active' : '' }} {{ !$f['available'] ? 'disabled' : '' }}"
                   @if(!$f['available']) title="Not available on this server" @endif>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! $folderIcons[$key] ?? '' !!}</svg>
                    {{ $f['label'] }}
                    @if($key === 'inbox' && $f['unread'] > 0)
                        <span class="folder-badge">{{ $f['unread'] }}</span>
                    @elseif($f['available'] && $f['total'] > 0)
                        <span class="folder-count">{{ $f['total'] }}</span>
                    @endif
                </a>
            @endforeach

            <div class="folder-divider"></div>
            <a href="{{ route('admin.inbox.contacts') }}" class="contacts-link">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Contacts
            </a>
        </div>
    </div>

    {{-- ── Email list panel ── --}}
    <div class="list-panel">

        {{-- Toolbar --}}
        <div class="list-toolbar">
            <div class="search-box">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="search-input" placeholder="Search {{ strtolower($folderLabel) }}…" oninput="filterEmails(this.value)">
            </div>
            <span style="font-size:0.78rem; color:#94a3b8;">{{ $total }} message{{ $total !== 1 ? 's' : '' }}{{ $unread > 0 ? " · {$unread} unread" : '' }}</span>
        </div>

        @if(count($emails) === 0 && !$error)
            <div class="state-box">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <h3>{{ $folderLabel }} is empty</h3>
                <p>No messages in this folder.</p>
            </div>
        @elseif(count($emails) > 0)

            {{-- Email list --}}
            <div id="email-list">
                @php
                    $avatarColors = ['#2563eb','#16a34a','#9333ea','#ef4444','#ca8a04','#0891b2','#d946ef','#f97316'];
                    $i = 0;
                @endphp
                @foreach($emails as $email)
                    @php
                        $fromName  = isset($email->from) ? imap_utf8($email->from) : 'Unknown';
                        $fromName  = preg_replace('/<.*?>/', '', $fromName);
                        $fromName  = trim($fromName, '" ');
                        $subject   = isset($email->subject) ? imap_utf8($email->subject) : '(no subject)';
                        $isUnread  = !$email->seen;
                        $date      = isset($email->date) ? \Carbon\Carbon::parse($email->date) : null;
                        $initial   = strtoupper(substr($fromName ?: 'U', 0, 1));
                        $color     = $avatarColors[$i % count($avatarColors)];
                        $i++;
                    @endphp
                    @php
                        $acctId    = $email->_account_id ?? null;
                        $acctName  = $email->_account_name ?? '';
                        $acctColor = $email->_account_color ?? '#94a3b8';
                    @endphp
                    <a href="{{ route('admin.inbox.show', ['uid' => $email->msgno, 'folder' => $folder, 'account_id' => $acctId]) }}"
                       class="email-row {{ $isUnread ? 'unread' : '' }}"
                       data-search="{{ strtolower($fromName . ' ' . $subject . ' ' . $acctName) }}">
                        <div style="display:flex; align-items:center; gap:7px; flex-shrink:0;">
                            @if($isUnread)
                                <div class="em-unread-dot"></div>
                            @else
                                <div style="width:7px;"></div>
                            @endif
                            <div class="em-avatar" style="background:{{ $color }};">{{ $initial }}</div>
                        </div>
                        <div style="min-width:0;">
                            <div style="display:flex; align-items:baseline; gap:0.5rem; margin-bottom:2px; flex-wrap:wrap;">
                                <div class="em-from">{{ $fromName }}</div>
                                @if($acctName)
                                    <span style="display:inline-flex;align-items:center;gap:4px;background:{{ $acctColor }}1a;color:{{ $acctColor }};border:1px solid {{ $acctColor }}33;padding:1px 7px;border-radius:999px;font-size:0.65rem;font-weight:700;letter-spacing:0.02em;">
                                        <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:{{ $acctColor }};"></span>
                                        {{ $acctName }}
                                    </span>
                                @endif
                            </div>
                            <div class="em-subject">{{ $subject }}</div>
                        </div>
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex-shrink:0;">
                            <div class="em-date">
                                @if($date)
                                    @if($date->isToday()) {{ $date->format('H:i') }}
                                    @elseif($date->isYesterday()) Yesterday
                                    @elseif($date->year === now()->year) {{ $date->format('d M') }}
                                    @else {{ $date->format('d M Y') }}
                                    @endif
                                @endif
                            </div>
                            <div class="em-actions" onclick="event.preventDefault(); event.stopPropagation();">
                                @if($folder === 'trash')
                                    {{-- In Trash: Move to Inbox --}}
                                    <form action="{{ route('admin.inbox.move', $email->msgno) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="from_folder" value="trash">
                                        <input type="hidden" name="to_folder" value="inbox">
                                        <input type="hidden" name="account_id" value="{{ $acctId }}">
                                        <button type="submit" class="em-action-btn move-inbox" title="Move to Inbox">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        </button>
                                    </form>
                                    {{-- Permanent delete --}}
                                    <form action="{{ route('admin.inbox.destroy', ['uid' => $email->msgno, 'folder' => 'trash', 'account_id' => $acctId]) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete permanently?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="em-action-btn" title="Delete permanently">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @elseif($folder === 'spam')
                                    {{-- In Spam: Not Spam (move to inbox) --}}
                                    <form action="{{ route('admin.inbox.move', $email->msgno) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="from_folder" value="spam">
                                        <input type="hidden" name="to_folder" value="inbox">
                                        <input type="hidden" name="account_id" value="{{ $acctId }}">
                                        <button type="submit" class="em-action-btn move-inbox" title="Not Spam — move to Inbox">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.inbox.destroy', ['uid' => $email->msgno, 'folder' => 'spam', 'account_id' => $acctId]) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('Delete permanently?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="em-action-btn" title="Delete permanently">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @else
                                    {{-- Normal: Move to Spam / Move to Trash --}}
                                    <form action="{{ route('admin.inbox.move', $email->msgno) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="from_folder" value="{{ $folder }}">
                                        <input type="hidden" name="to_folder" value="spam">
                                        <input type="hidden" name="account_id" value="{{ $acctId }}">
                                        <button type="submit" class="em-action-btn" title="Move to Spam" style="color:#f59e0b;" onmouseover="this.style.background='#fefce8'" onmouseout="this.style.background='transparent'">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.inbox.move', $email->msgno) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="from_folder" value="{{ $folder }}">
                                        <input type="hidden" name="to_folder" value="trash">
                                        <input type="hidden" name="account_id" value="{{ $acctId }}">
                                        <button type="submit" class="em-action-btn" title="Move to Trash">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div id="no-results" style="display:none;" class="state-box" style="padding:2rem;">
                <p>No emails match your search.</p>
            </div>

            {{-- Pagination --}}
            @if($lastPage > 1)
            <div class="page-bar">
                <span>Page {{ $page }} of {{ $lastPage }}</span>
                <div class="page-btns">
                    @if($page > 1)
                        <a href="{{ route('admin.inbox.index', ['folder' => $folder, 'page' => $page - 1]) }}" class="page-btn">&lsaquo;</a>
                    @else
                        <span class="page-btn disabled">&lsaquo;</span>
                    @endif
                    @for($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++)
                        <a href="{{ route('admin.inbox.index', ['folder' => $folder, 'page' => $p]) }}"
                           class="page-btn {{ $p === $page ? 'active' : '' }}">{{ $p }}</a>
                    @endfor
                    @if($page < $lastPage)
                        <a href="{{ route('admin.inbox.index', ['folder' => $folder, 'page' => $page + 1]) }}" class="page-btn">&rsaquo;</a>
                    @else
                        <span class="page-btn disabled">&rsaquo;</span>
                    @endif
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

@endif

<script>
function filterEmails(q) {
    q = q.toLowerCase().trim();
    let found = 0;
    document.querySelectorAll('#email-list .email-row').forEach(row => {
        const match = !q || (row.getAttribute('data-search') || '').includes(q);
        row.style.display = match ? '' : 'none';
        if (match) found++;
    });
    const noRes = document.getElementById('no-results');
    if (noRes) noRes.style.display = (found === 0 && q) ? 'block' : 'none';
}
</script>
@endsection
