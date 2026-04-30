@extends('layouts.admin-sidebar')

@section('title', 'Reply — ' . ($reply->from_name ?: $reply->from_email))
@section('page-title', 'Reply Detail')

@section('extra-styles')
<style>
    .back-link {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 0.85rem; font-weight: 600; color: #64748b;
        text-decoration: none; margin-bottom: 1.25rem;
        transition: color 120ms;
    }
    .back-link:hover { color: #0f172a; }

    /* ── Layout ── */
    .reply-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 1.25rem;
        align-items: start;
    }
    @media (max-width: 900px) {
        .reply-layout { grid-template-columns: 1fr; }
    }

    /* ── Email card ── */
    .email-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }
    .email-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .email-subject {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.85rem;
        line-height: 1.35;
    }
    .email-meta {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .meta-row {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 0.84rem;
    }
    .meta-label {
        font-weight: 600;
        color: #94a3b8;
        min-width: 40px;
        flex-shrink: 0;
    }
    .meta-value { color: #374151; }

    /* ── Email body ── */
    .email-body-wrap {
        padding: 1.5rem;
    }
    .email-body {
        font-size: 0.92rem;
        color: #1e293b;
        line-height: 1.75;
        white-space: pre-wrap;
        word-break: break-word;
    }

    /* ── Thread (full conversation) ── */
    .thread-wrap { padding: 0; border-top: 1px solid #e2e8f0; }
    .thread-bar {
        padding: 0.7rem 1.25rem;
        background: #f8fafc;
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .thread-list { padding: 1rem 1.25rem 0.5rem; display: flex; flex-direction: column; gap: 0.85rem; }
    .msg {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }
    .msg.outbound { background: #fafbff; border-color: #dbeafe; margin-left: 2.5rem; }
    .msg.inbound  { background: #fff;    border-color: #e2e8f0; margin-right: 2.5rem; }
    .msg-head {
        display: flex; align-items: center; gap: 10px; padding: 0.7rem 1rem;
        background: rgba(248,250,252,0.6); border-bottom: 1px solid #f1f5f9;
        cursor: pointer; user-select: none;
    }
    .msg-head:hover { background: #f1f5f9; }
    .msg-dot {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.78rem; font-weight: 700; color: #fff;
    }
    .msg.outbound .msg-dot { background: #2563eb; }
    .msg.inbound  .msg-dot { background: #16a34a; }
    .msg-info { flex: 1; min-width: 0; }
    .msg-from { font-size: 0.85rem; font-weight: 600; color: #0f172a; line-height: 1.2; }
    .msg-meta { font-size: 0.74rem; color: #94a3b8; margin-top: 1px; }
    .msg-time { font-size: 0.74rem; color: #94a3b8; flex-shrink: 0; }
    .msg-pill {
        font-size: 0.66rem; padding: 1px 7px; border-radius: 999px;
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
    }
    .msg.outbound .msg-pill { background: #dbeafe; color: #1d4ed8; }
    .msg.inbound  .msg-pill { background: #dcfce7; color: #16a34a; }
    .msg-body {
        padding: 0.85rem 1rem;
        font-size: 0.88rem;
        color: #1e293b;
        line-height: 1.65;
        word-break: break-word;
    }
    .msg-body.collapsed { display: none; }
    .msg-body.plain { white-space: pre-wrap; }
    .msg-subject { font-size: 0.8rem; color: #475569; font-style: italic; margin-bottom: 0.5rem; padding-bottom: 0.4rem; border-bottom: 1px dashed #e2e8f0; }

    /* ── Sidebar cards ── */
    .side-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .side-card-head {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.8rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .side-card-body { padding: 1rem; }

    /* ── Category badge ── */
    .cat-badge-lg {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.45rem 1rem;
        border-radius: 999px;
        font-size: 0.875rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    /* ── Score display ── */
    .score-display {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1rem;
    }
    .score-circle {
        width: 52px; height: 52px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; font-weight: 800;
        color: #fff;
        flex-shrink: 0;
    }
    .score-label { font-size: 0.78rem; color: #64748b; }
    .score-big { font-size: 1.4rem; font-weight: 800; color: #0f172a; line-height: 1; }

    /* ── Score bar ── */
    .score-bar-full {
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        margin-bottom: 0.4rem;
    }
    .score-bar-fill-full {
        height: 100%;
        border-radius: 999px;
    }

    /* ── Speaker info ── */
    .speaker-info-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.84rem;
    }
    .speaker-info-row:last-child { border-bottom: none; }
    .info-icon { width: 18px; height: 18px; flex-shrink: 0; color: #94a3b8; }
    .info-label { font-size: 0.72rem; color: #94a3b8; font-weight: 600; display: block; }
    .info-val { color: #1e293b; font-weight: 500; }

    /* ── Action buttons ── */
    .action-btn {
        display: flex; align-items: center; gap: 7px;
        width: 100%; padding: 0.6rem 0.85rem;
        border-radius: 8px; font-size: 0.84rem; font-weight: 600;
        border: 1.5px solid #e2e8f0; background: #fff;
        color: #374151; cursor: pointer; text-align: left;
        transition: background 120ms, border-color 120ms;
        text-decoration: none;
        margin-bottom: 0.5rem;
    }
    .action-btn:last-child { margin-bottom: 0; }
    .action-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
    .action-btn.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
    .action-btn.primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
    .action-btn.danger { background: #fff; color: #dc2626; border-color: #fecaca; }
    .action-btn.danger:hover { background: #fef2f2; border-color: #fca5a5; }

    /* ── AI raw detail ── */
    .ai-detail-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.45rem 0;
        font-size: 0.82rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .ai-detail-row:last-child { border-bottom: none; }
    .ai-detail-key { color: #64748b; font-weight: 600; }
    .ai-detail-val { color: #0f172a; font-weight: 700; }

    /* ── Reclassify spinner ── */
    #reclassify-result { font-size: 0.78rem; margin-top: 0.5rem; min-height: 18px; }
    #reclassify-result.ok  { color: #16a34a; }
    #reclassify-result.err { color: #dc2626; }
    .mini-spin {
        display:inline-block; width:12px; height:12px;
        border:2px solid #e2e8f0; border-top-color:#2563eb;
        border-radius:50%; animation:spin 0.7s linear infinite; vertical-align: middle;
    }
    @keyframes spin { to { transform:rotate(360deg); } }

    /* ── Reply composer ── */
    .reply-composer {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        overflow: hidden; margin-top: 1.25rem;
    }
    .reply-composer-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.85rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        cursor: pointer; user-select: none;
        transition: background 120ms;
    }
    .reply-composer-head:hover { background: #f8fafc; }
    .reply-composer-title {
        display: flex; align-items: center; gap: 8px;
        font-size: 0.92rem; font-weight: 700; color: #0f172a;
    }
    .reply-composer-title svg { width: 16px; height: 16px; color: #2563eb; }
    .reply-to-badge {
        font-size: 0.78rem; color: #64748b; font-weight: 500;
    }
    .reply-chevron {
        transition: transform 200ms;
    }
    .reply-chevron.open { transform: rotate(180deg); }
    .reply-composer-body {
        padding: 1.25rem;
        display: none;
    }
    .reply-composer-body.open { display: block; }
    .reply-form-group {
        display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.85rem;
    }
    .reply-form-label { font-size: 0.82rem; font-weight: 600; color: #374151; }
    .reply-form-input {
        width: 100%; padding: 0.6rem 0.85rem;
        border: 1.5px solid #d1d5db; border-radius: 8px;
        font-size: 0.875rem; color: #0f172a; outline: none; font-family: inherit;
        transition: border-color 140ms;
    }
    .reply-form-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .reply-send-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 0.6rem 1.25rem; border-radius: 8px;
        background: #2563eb; color: #fff; font-size: 0.875rem; font-weight: 700;
        border: none; cursor: pointer; transition: background 140ms;
    }
    .reply-send-btn:hover { background: #1d4ed8; }
    .reply-alert {
        display: flex; align-items: center; gap: 8px;
        padding: 0.7rem 1rem; border-radius: 8px;
        font-size: 0.84rem; font-weight: 500; margin-bottom: 1rem;
    }
    .reply-alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
    .reply-alert.error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

    /* Summernote tweaks */
    .note-editor.note-frame { border: 1.5px solid #d1d5db !important; border-radius: 8px !important; }
    .note-editor.note-frame .note-toolbar { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0 !important; border-radius: 8px 8px 0 0 !important; }

    /* Quoted original */
    .quoted-original {
        margin-top: 0.75rem; padding: 0.75rem 1rem;
        background: #f8fafc; border-left: 3px solid #e2e8f0; border-radius: 0 6px 6px 0;
        font-size: 0.78rem; color: #64748b; line-height: 1.6;
        max-height: 120px; overflow-y: auto; white-space: pre-wrap;
    }
</style>
{{-- Summernote CSS --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css">
@endsection

@section('content')

<a href="{{ route('admin.replies.index') }}" class="back-link">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Replies
</a>

<div class="reply-layout">

    {{-- ── Left: Email message ── --}}
    <div class="email-card">

        {{-- Header --}}
        <div class="email-header">
            <div class="email-subject">{{ $reply->subject ?: '(no subject)' }}</div>
            <div class="email-meta">
                <div class="meta-row">
                    <span class="meta-label">From</span>
                    <span class="meta-value">
                        @if($reply->from_name)
                            <strong>{{ $reply->from_name }}</strong>
                            &lt;{{ $reply->from_email }}&gt;
                        @else
                            {{ $reply->from_email }}
                        @endif
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Date</span>
                    <span class="meta-value">
                        {{ $reply->received_at->format('D, d M Y H:i') }}
                        <span style="color:#94a3b8; margin-left:6px; font-size:0.8rem;">({{ $reply->received_at->diffForHumans() }})</span>
                    </span>
                </div>
                @if($reply->speaker)
                <div class="meta-row">
                    <span class="meta-label">Speaker</span>
                    <span class="meta-value">
                        <a href="{{ route('admin.speakers.edit', $reply->speaker) }}" style="color:#2563eb; font-weight:600; text-decoration:none;">
                            {{ $reply->speaker->full_name }}
                        </a>
                        @if($reply->speaker->company)
                            &mdash; {{ $reply->speaker->company }}
                        @endif
                    </span>
                </div>
                @endif
            </div>
        </div>

        {{-- Body — full conversation thread --}}
        @php
            $thread = $thread ?? collect();
            $latestId = optional($thread->last())->id;
        @endphp

        <div class="thread-wrap">
            <div class="thread-bar">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Conversation ({{ $thread->count() }} message{{ $thread->count() === 1 ? '' : 's' }})
            </div>

            @if($thread->isEmpty())
                <div style="padding:2rem 1.25rem; color:#94a3b8; font-style:italic; font-size:0.875rem;">
                    No messages exchanged yet.
                </div>
            @else
                <div class="thread-list">
                    @foreach($thread as $i => $msg)
                        @php
                            $expanded = ($msg->id === $latestId);
                            $personName = $msg->direction === 'inbound'
                                ? ($msg->from_name ?? $msg->from_email)
                                : ($msg->to_name ?? $msg->to_email);
                            $personEmail = $msg->direction === 'inbound' ? $msg->from_email : $msg->to_email;
                            $initial = mb_strtoupper(mb_substr($personName, 0, 1));
                        @endphp
                        <div class="msg {{ $msg->direction }}">
                            <div class="msg-head" onclick="toggleMsg(this)">
                                <div class="msg-dot">{{ $initial }}</div>
                                <div class="msg-info">
                                    <div class="msg-from">{{ $personName }}</div>
                                    <div class="msg-meta">
                                        @if($msg->direction === 'inbound')
                                            From {{ $msg->from_email }}
                                            @if(!empty($msg->category)) · {{ $msg->category }} @endif
                                        @else
                                            To {{ $msg->to_email }}
                                            @if(!empty($msg->smtp_account)) · via {{ $msg->smtp_account }} @endif
                                        @endif
                                    </div>
                                </div>
                                <span class="msg-pill">{{ $msg->direction === 'inbound' ? 'Received' : 'Sent' }}</span>
                                <span class="msg-time" title="{{ $msg->at?->format('Y-m-d H:i') }}">
                                    {{ $msg->at?->diffForHumans() }}
                                </span>
                            </div>
                            <div class="msg-body {{ $msg->is_html ? '' : 'plain' }} {{ $expanded ? '' : 'collapsed' }}">
                                @if(!empty($msg->subject))
                                    <div class="msg-subject"><strong>Subject:</strong> {{ $msg->subject }}</div>
                                @endif
                                @if($msg->is_html)
                                    {!! $msg->body !!}
                                @else
                                    {{ $msg->body }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── Reply Composer ── --}}
    <div class="reply-composer" id="reply-composer">
        <div class="reply-composer-head" onclick="toggleReply()">
            <div class="reply-composer-title">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                Reply
                <span class="reply-to-badge">to {{ $reply->from_name ?: $reply->from_email }}</span>
            </div>
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="2.5" class="reply-chevron" id="reply-chevron">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
        <div class="reply-composer-body" id="reply-body">

            @if(session('reply_success'))
                <div class="reply-alert success">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ session('reply_success') }}
                </div>
            @endif
            @if(session('reply_error'))
                <div class="reply-alert error">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('reply_error') }}
                </div>
            @endif

            <form action="{{ route('admin.replies.sendReply', $reply) }}" method="POST" id="reply-form">
                @csrf

                {{-- To (read-only) --}}
                <div class="reply-form-group">
                    <label class="reply-form-label">To</label>
                    <input type="text" class="reply-form-input" value="{{ $reply->from_name ? $reply->from_name . ' <' . $reply->from_email . '>' : $reply->from_email }}" readonly style="background:#f8fafc; color:#64748b;">
                </div>

                {{-- Subject --}}
                <div class="reply-form-group">
                    <label class="reply-form-label">Subject</label>
                    <input type="text" name="subject" class="reply-form-input" id="reply-subject"
                           value="{{ old('subject', 'Re: ' . ($reply->subject ?: '(no subject)')) }}">
                </div>

                {{-- Body editor --}}
                <div class="reply-form-group">
                    <label class="reply-form-label">Message</label>
                    <textarea name="body" id="reply-body-input" style="display:none;">{{ old('body') }}</textarea>
                    <div id="reply-editor"></div>
                </div>

                {{-- Quoted original --}}
                @if(trim($reply->body_plain))
                <div class="quoted-original">
                    <div style="font-weight:600; color:#475569; margin-bottom:4px;">
                        On {{ $reply->received_at->format('D, d M Y H:i') }}, {{ $reply->from_name ?: $reply->from_email }} wrote:
                    </div>
                    {{ Str::limit($reply->body_plain, 500) }}
                </div>
                @endif

                {{-- Send --}}
                <div style="display:flex; align-items:center; gap:0.75rem; margin-top:1rem;">
                    <button type="submit" class="reply-send-btn" onclick="return confirmReply()">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Reply
                    </button>
                    <span style="font-size:0.78rem; color:#94a3b8;">Will be sent via your configured SMTP</span>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Right: Sidebar ── --}}
    <div>

        {{-- Classification result --}}
        <div class="side-card">
            <div class="side-card-head">AI Classification</div>
            <div class="side-card-body">

                {{-- Category badge --}}
                <div id="cat-badge-wrap">
                    <span class="cat-badge-lg" id="cat-badge"
                          style="background:{{ $reply->category_bg }};color:{{ $reply->category_color }};">
                        {{ $reply->category_icon }} {{ $reply->category }}
                    </span>
                </div>

                {{-- Score --}}
                @php
                    $score    = $reply->ai_score;
                    $bgColor  = '#94a3b8';
                    $barColor = '#94a3b8';
                    if ($score !== null) {
                        if ($score >= 60) { $bgColor = '#16a34a'; $barColor = '#16a34a'; }
                        elseif ($score >= 30) { $bgColor = '#f59e0b'; $barColor = '#f59e0b'; }
                        else { $bgColor = '#ef4444'; $barColor = '#ef4444'; }
                    }
                @endphp
                @if($score !== null)
                <div class="score-display">
                    <div class="score-circle" style="background:{{ $bgColor }};">{{ $score }}</div>
                    <div>
                        <div class="score-big" id="score-val">{{ $score }}</div>
                        <div class="score-label">Lead score / 100</div>
                    </div>
                </div>
                <div class="score-bar-full">
                    <div class="score-bar-fill-full" id="score-bar"
                         style="width:{{ $score }}%;background:{{ $barColor }};"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.7rem;color:#94a3b8;margin-top:2px;margin-bottom:0.75rem;">
                    <span>Low intent</span><span>High intent</span>
                </div>
                @else
                <div style="color:#94a3b8;font-size:0.82rem;margin-bottom:0.75rem;">Score not available</div>
                @endif

                {{-- AI details --}}
                @if($reply->classified_at)
                <div class="ai-detail-row">
                    <span class="ai-detail-key">Classified</span>
                    <span class="ai-detail-val" style="font-weight:500;color:#64748b;">{{ $reply->classified_at->diffForHumans() }}</span>
                </div>
                @endif
                <div class="ai-detail-row">
                    <span class="ai-detail-key">Model</span>
                    <span class="ai-detail-val" style="font-weight:500;color:#64748b;">gpt-4o-mini</span>
                </div>

                {{-- Reclassify --}}
                <div style="margin-top:0.85rem;">
                    <button class="action-btn" onclick="reclassify()" id="reclassify-btn">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Re-run AI Classification
                    </button>
                    <div id="reclassify-result"></div>
                </div>
            </div>
        </div>

        {{-- Speaker card --}}
        @if($reply->speaker)
        <div class="side-card">
            <div class="side-card-head">Matched Speaker</div>
            <div class="side-card-body" style="padding:0.75rem 1rem;">
                @php $sp = $reply->speaker; @endphp
                <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:0.75rem;">
                    <div style="width:38px;height:38px;border-radius:50%;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;flex-shrink:0;">
                        {{ strtoupper(substr($sp->first_name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:700;color:#0f172a;font-size:0.9rem;">{{ $sp->full_name }}</div>
                        @if($sp->title)<div style="font-size:0.78rem;color:#64748b;">{{ $sp->title }}</div>@endif
                    </div>
                </div>
                @if($sp->company)
                <div class="speaker-info-row">
                    <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <div><span class="info-label">Company</span><span class="info-val">{{ $sp->company }}</span></div>
                </div>
                @endif
                @if($sp->email)
                <div class="speaker-info-row">
                    <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <div><span class="info-label">Email</span><span class="info-val">{{ $sp->email }}</span></div>
                </div>
                @endif
                @if($sp->country)
                <div class="speaker-info-row">
                    <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                    <div><span class="info-label">Country</span><span class="info-val">{{ $sp->country }}</span></div>
                </div>
                @endif
                @if($sp->seniority)
                <div class="speaker-info-row">
                    <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <div><span class="info-label">Seniority</span><span class="info-val">{{ $sp->seniority }}</span></div>
                </div>
                @endif
                <div style="margin-top:0.85rem;">
                    <a href="{{ route('admin.speakers.edit', $sp) }}" class="action-btn primary" style="display:inline-flex;width:auto;">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        View Speaker Profile
                    </a>
                </div>
            </div>
        </div>
        @else
        <div class="side-card">
            <div class="side-card-head">Speaker Match</div>
            <div class="side-card-body">
                <div style="display:flex;align-items:center;gap:8px;color:#94a3b8;font-size:0.85rem;">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    No speaker in your database matched <strong style="color:#475569;">{{ $reply->from_email }}</strong>
                </div>
            </div>
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="side-card">
            <div class="side-card-head">Quick Actions</div>
            <div class="side-card-body">
                @if($reply->speaker)
                <a href="{{ route('admin.emails.index') }}" class="action-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Send Follow-up Email
                </a>
                @endif
                <a href="{{ route('admin.replies.index') }}" class="action-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Back to All Replies
                </a>
            </div>
        </div>

    </div>
</div>

{{-- jQuery + Summernote --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script>
const RECLASSIFY_URL = '{{ route("admin.replies.reclassify", $reply) }}';
const CSRF           = '{{ csrf_token() }}';

async function reclassify() {
    const btn    = document.getElementById('reclassify-btn');
    const result = document.getElementById('reclassify-result');

    btn.disabled = true;
    btn.innerHTML = '<span class="mini-spin"></span> Classifying…';
    result.textContent = '';
    result.className   = '';

    try {
        const resp = await fetch(RECLASSIFY_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const data = await resp.json();

        if (data.ok) {
            // Update badge and score in-place
            const catColors = {
                'Interested':     { bg:'#f0fdf4', color:'#16a34a', icon:'🟢' },
                'Not Interested': { bg:'#f8fafc', color:'#64748b', icon:'⚫' },
                'Info Request':   { bg:'#eff6ff', color:'#2563eb', icon:'🔵' },
                'Out of Office':  { bg:'#fefce8', color:'#ca8a04', icon:'🟡' },
                'Spam':           { bg:'#fef2f2', color:'#dc2626', icon:'🔴' },
                'Negative':       { bg:'#fff1f2', color:'#9f1239', icon:'🚫' },
                'Manual Review':  { bg:'#f1f5f9', color:'#94a3b8', icon:'⚪' },
                'No Reply':       { bg:'#fff7ed', color:'#f97316', icon:'🟠' },
                'Bounced':        { bg:'#f5f3ff', color:'#7c3aed', icon:'↩️' },
            };
            const info = catColors[data.category] || catColors['Manual Review'];
            document.getElementById('cat-badge').style.background = info.bg;
            document.getElementById('cat-badge').style.color      = info.color;
            document.getElementById('cat-badge').textContent      = info.icon + ' ' + data.category;

            if (data.score !== null && data.score !== undefined) {
                const scoreColor = data.score >= 60 ? '#16a34a' : data.score >= 30 ? '#f59e0b' : '#ef4444';
                const sv = document.getElementById('score-val');
                const sb = document.getElementById('score-bar');
                if (sv) sv.textContent = data.score;
                if (sb) { sb.style.width = data.score + '%'; sb.style.background = scoreColor; }
            }

            result.textContent = '✓ Reclassified as ' + data.category;
            result.className   = 'ok';
        } else {
            result.textContent = data.error || 'Classification failed.';
            result.className   = 'err';
        }
    } catch(e) {
        result.textContent = 'Network error: ' + e.message;
        result.className   = 'err';
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Re-run AI Classification`;
    }
}

// ── Reply composer ───────────────────────────────────────────────────
let replyOpen = {{ session('reply_success') || session('reply_error') ? 'true' : 'false' }};

function toggleReply() {
    replyOpen = !replyOpen;
    document.getElementById('reply-body').classList.toggle('open', replyOpen);
    document.getElementById('reply-chevron').classList.toggle('open', replyOpen);
    if (replyOpen && !window._editorInit) {
        initEditor();
    }
}

function initEditor() {
    window._editorInit = true;
    $('#reply-editor').summernote({
        height: 200,
        placeholder: 'Write your reply…',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'hr']],
            ['view', ['codeview']],
        ],
        callbacks: {
            onChange: function(contents) {
                document.getElementById('reply-body-input').value = contents;
            }
        }
    });
    // Prefill old body if exists
    const old = document.getElementById('reply-body-input').value;
    if (old) $('#reply-editor').summernote('code', old);
}

function confirmReply() {
    const body = $('#reply-editor').summernote('code');
    if (!body || body === '<p><br></p>') {
        alert('Please write a reply message.');
        return false;
    }
    document.getElementById('reply-body-input').value = body;
    return confirm('Send reply to {{ $reply->from_email }}?');
}

// Auto-open if there was a success/error flash
@if(session('reply_success') || session('reply_error'))
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('reply-body').classList.add('open');
    document.getElementById('reply-chevron').classList.add('open');
    initEditor();
});
@endif

// ── Thread message expand/collapse ───────────────────────────────────
function toggleMsg(headEl) {
    const body = headEl.nextElementSibling;
    if (body && body.classList.contains('msg-body')) {
        body.classList.toggle('collapsed');
    }
}
</script>
@endsection
