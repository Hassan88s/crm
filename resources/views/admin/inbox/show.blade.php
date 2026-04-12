@extends('layouts.admin-sidebar')
@section('title', 'View Email — ' . $folderLabel)
@section('page-title', $folderLabel)

@section('extra-styles')
<style>
    .email-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .email-header { padding:1.5rem; border-bottom:1px solid #f1f5f9; }
    .email-subject { font-size:1.15rem; font-weight:700; color:#0f172a; margin-bottom:1rem; word-break:break-word; line-height:1.35; }
    .email-meta { display:flex; flex-wrap:wrap; gap:0.75rem 1.5rem; font-size:0.82rem; }
    .meta-item { display:flex; align-items:center; gap:5px; color:#64748b; }
    .meta-item strong { color:#374151; }
    .meta-avatar {
        width:40px; height:40px; border-radius:50%; background:#2563eb;
        display:flex; align-items:center; justify-content:center;
        font-size:0.9rem; font-weight:700; color:#fff; flex-shrink:0;
    }
    .email-actions { display:flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; border-bottom:1px solid #f1f5f9; background:#fafbfc; flex-wrap:wrap; }
    .action-btn {
        display:inline-flex; align-items:center; gap:5px;
        padding:0.4rem 0.85rem; border-radius:7px;
        font-size:0.82rem; font-weight:600; border:1px solid #e2e8f0;
        background:#fff; color:#475569; cursor:pointer; text-decoration:none;
        transition:all 120ms;
    }
    .action-btn:hover { background:#f8fafc; border-color:#cbd5e1; }
    .action-btn.danger { color:#dc2626; border-color:#fecaca; }
    .action-btn.danger:hover { background:#fef2f2; }
    .action-btn.blue { color:#2563eb; border-color:#bfdbfe; }
    .action-btn.blue:hover { background:#eff6ff; }
    .action-btn.warn { color:#d97706; border-color:#fde68a; }
    .action-btn.warn:hover { background:#fffbeb; }
    .action-btn svg { width:13px; height:13px; }

    .folder-badge-show { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:0.72rem; font-weight:700; background:#eff6ff; color:#2563eb; margin-left:0.5rem; }

    .email-body { padding:1.5rem; }
    .email-body iframe { width:100%; border:none; min-height:400px; border-radius:8px; }
    .plain-body {
        font-size:0.875rem; color:#374151; line-height:1.75;
        white-space:pre-wrap; word-break:break-word;
        background:#f8fafc; border-radius:8px; padding:1.25rem;
        max-height:600px; overflow-y:auto;
    }
    .attachments { padding:1rem 1.5rem; border-top:1px solid #f1f5f9; background:#fafbfc; }
    .attachment-chip {
        display:inline-flex; align-items:center; gap:6px;
        background:#fff; border:1px solid #e2e8f0; border-radius:8px;
        padding:0.4rem 0.75rem; font-size:0.8rem; color:#374151; margin:3px;
    }
    .attachment-chip svg { width:14px; height:14px; color:#94a3b8; }
</style>
@endsection

@section('content')

<div style="margin-bottom:1rem;">
    <a href="{{ route('admin.inbox.index', ['folder' => $folder]) }}"
       style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.85rem;text-decoration:none;font-weight:600;transition:color 120ms;"
       onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to {{ $folderLabel }}
    </a>
</div>

@php
    $fromName    = '';
    $fromEmail   = '';
    $toField     = '';
    $dateStr     = '';

    if ($header) {
        if (!empty($header->from)) {
            $f = $header->from[0];
            $fromName  = isset($f->personal) ? imap_utf8($f->personal) : '';
            $fromEmail = ($f->mailbox ?? '') . '@' . ($f->host ?? '');
            if (!$fromName) $fromName = $fromEmail;
        }
        if (!empty($header->to)) {
            $t = $header->to[0];
            $toField = ($t->mailbox ?? '') . '@' . ($t->host ?? '');
        }
        if (!empty($header->date)) {
            try {
                $dateStr = \Carbon\Carbon::parse($header->date)->format('d M Y, H:i');
            } catch (\Exception $e) {
                $dateStr = $header->date;
            }
        }
    }
    $subject = isset($header->subject) ? imap_utf8($header->subject) : '(no subject)';
    $initial = strtoupper(substr($fromName ?: 'U', 0, 1));
@endphp

<div class="email-card">

    {{-- Subject & Meta --}}
    <div class="email-header">
        <div class="email-subject">
            {{ $subject }}
            <span class="folder-badge-show">{{ $folderLabel }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:0.85rem; flex-wrap:wrap;">
            <div class="meta-avatar">{{ $initial }}</div>
            <div>
                <div style="font-size:0.875rem; font-weight:600; color:#0f172a;">{{ $fromName }}</div>
                <div style="font-size:0.78rem; color:#94a3b8;">{{ $fromEmail }}</div>
            </div>
            <div class="email-meta" style="margin-left:auto;">
                @if($toField)
                <div class="meta-item">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                    <span><strong>To:</strong> {{ $toField }}</span>
                </div>
                @endif
                @if($dateStr)
                <div class="meta-item">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $dateStr }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Action bar --}}
    <div class="email-actions">
        @if($folder === 'trash')
            {{-- In Trash --}}
            <form action="{{ route('admin.inbox.move', $uid) }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="from_folder" value="trash">
                <input type="hidden" name="to_folder" value="inbox">
                <button type="submit" class="action-btn blue">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Move to Inbox
                </button>
            </form>
            <form action="{{ route('admin.inbox.destroy', ['uid' => $uid, 'folder' => 'trash']) }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('Delete this email permanently? This cannot be undone.');">
                @csrf @method('DELETE')
                <button type="submit" class="action-btn danger">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete Permanently
                </button>
            </form>
        @elseif($folder === 'spam')
            {{-- In Spam --}}
            <form action="{{ route('admin.inbox.move', $uid) }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="from_folder" value="spam">
                <input type="hidden" name="to_folder" value="inbox">
                <button type="submit" class="action-btn blue">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Not Spam
                </button>
            </form>
            <form action="{{ route('admin.inbox.destroy', ['uid' => $uid, 'folder' => 'spam']) }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('Delete permanently?');">
                @csrf @method('DELETE')
                <button type="submit" class="action-btn danger">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                </button>
            </form>
        @else
            {{-- Normal folders --}}
            <form action="{{ route('admin.inbox.move', $uid) }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="from_folder" value="{{ $folder }}">
                <input type="hidden" name="to_folder" value="spam">
                <button type="submit" class="action-btn warn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Spam
                </button>
            </form>
            <form action="{{ route('admin.inbox.move', $uid) }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="from_folder" value="{{ $folder }}">
                <input type="hidden" name="to_folder" value="trash">
                <button type="submit" class="action-btn danger">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Trash
                </button>
            </form>
        @endif

        <a href="{{ route('admin.inbox.index', ['folder' => $folder]) }}" class="action-btn" style="margin-left:auto;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
    </div>

    {{-- Body --}}
    <div class="email-body">
        @if($isHtml)
            <div class="html-body" style="line-height:1.6; word-break:break-word;">{!! $body !!}</div>
        @else
            <div class="plain-body">{{ $body }}</div>
        @endif
    </div>

    {{-- Attachments --}}
    @if(count($attachments) > 0)
    <div class="attachments">
        <div style="font-size:0.8rem; font-weight:700; color:#374151; margin-bottom:0.5rem;">
            {{ count($attachments) }} Attachment{{ count($attachments) !== 1 ? 's' : '' }}
        </div>
        @foreach($attachments as $att)
        <span class="attachment-chip">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            {{ $att['name'] }}
            <span style="color:#94a3b8;">({{ number_format($att['bytes'] / 1024, 1) }} KB)</span>
        </span>
        @endforeach
    </div>
    @endif
</div>
@endsection
