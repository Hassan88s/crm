@extends('layouts.admin-sidebar')
@section('title', 'Run Emails')
@section('page-title', 'Run Emails')

@section('extra-styles')
{{-- Summernote CSS --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css">
<style>
    .email-layout { display:grid; grid-template-columns:1fr 340px; gap:1.25rem; align-items:start; }

    /* Stat chips */
    .stat-chips { display:flex; gap:0.75rem; margin-bottom:1.5rem; flex-wrap:wrap; }
    .stat-chip { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:0.65rem 1rem; }
    .stat-chip-val { font-size:1.3rem; font-weight:700; color:#0f172a; line-height:1; }
    .stat-chip-lbl { font-size:0.75rem; color:#64748b; }

    /* Composer card */
    .compose-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .compose-head { padding:1rem 1.25rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .compose-body { padding:1.25rem; }

    .form-group { display:flex; flex-direction:column; gap:0.35rem; margin-bottom:1rem; }
    .form-label { font-size:0.85rem; font-weight:600; color:#374151; }
    .form-input { width:100%; padding:0.65rem 0.9rem; border:1.5px solid #d1d5db; border-radius:8px; font-size:0.875rem; outline:none; color:#0f172a; font-family:inherit; transition:border-color 140ms; }
    .form-input:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }

    /* Variables bar */
    .vars-bar { display:flex; flex-wrap:wrap; gap:5px; margin-bottom:0.75rem; }
    .var-chip {
        display:inline-flex; align-items:center; gap:4px;
        padding:3px 9px; border-radius:6px; background:#eff6ff; border:1px solid #bfdbfe;
        color:#1d4ed8; font-size:0.75rem; font-weight:600; cursor:pointer;
        transition:all 120ms; font-family:monospace;
    }
    .var-chip:hover { background:#dbeafe; }

    /* Summernote override */
    .note-editor.note-frame { border:1.5px solid #d1d5db !important; border-radius:8px !important; }
    .note-editor.note-frame .note-toolbar { background:#f8fafc !important; border-bottom:1px solid #e2e8f0 !important; border-radius:8px 8px 0 0 !important; }

    /* Speaker picker */
    .picker-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .picker-head { padding:0.85rem 1rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .picker-title { font-size:0.875rem; font-weight:700; color:#0f172a; }
    .picker-search { width:100%; padding:0.55rem 0.75rem; border:1.5px solid #e2e8f0; border-radius:7px; font-size:0.8rem; outline:none; font-family:inherit; margin:0.75rem; width:calc(100% - 1.5rem); }
    .picker-search:focus { border-color:#2563eb; }
    .speaker-list { max-height:340px; overflow-y:auto; }
    .speaker-row {
        display:flex; align-items:center; gap:8px;
        padding:0.55rem 1rem; cursor:pointer; transition:background 120ms;
        border-bottom:1px solid #f9fafb;
    }
    .speaker-row:hover { background:#f8fafc; }
    .speaker-row input[type=checkbox] { accent-color:#2563eb; width:15px; height:15px; flex-shrink:0; }
    .sp-avatar { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.68rem; color:#fff; flex-shrink:0; }
    .sp-name { font-size:0.82rem; font-weight:600; color:#0f172a; }
    .sp-email { font-size:0.72rem; color:#94a3b8; }
    .picker-footer { padding:0.75rem 1rem; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
    .sel-count { font-size:0.8rem; color:#64748b; }

    /* Alert */
    .alert { display:flex; align-items:flex-start; gap:8px; padding:0.85rem 1rem; border-radius:8px; font-size:0.875rem; font-weight:500; margin-bottom:1rem; }
    .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
    .alert-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

    /* AI Draft panel */
    .ai-panel { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; margin-bottom:1.25rem; }
    .ai-panel-head { display:flex; align-items:center; gap:8px; margin-bottom:1rem; }
    .ai-panel-title { font-size:0.95rem; font-weight:700; color:#0f172a; }
    .ai-panel-sub { font-size:0.75rem; color:#64748b; margin-top:1px; }
    .ai-badge { background:linear-gradient(90deg,#6366f1,#8b5cf6); color:#fff; font-size:0.65rem; font-weight:700; padding:2px 7px; border-radius:20px; letter-spacing:0.04em; }
    .ai-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem; }
    .ai-field label { font-size:0.75rem; font-weight:600; color:#374151; display:block; margin-bottom:4px; }
    .ai-input, .ai-select {
        width:100%; padding:0.6rem 0.85rem;
        background:#fff; border:1.5px solid #d1d5db; border-radius:8px;
        font-size:0.82rem; color:#0f172a; outline:none; font-family:inherit;
        transition:border-color 140ms;
    }
    .ai-input:focus, .ai-select:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,0.1); }
    .ai-input::placeholder { color:#9ca3af; }
    .ai-generate-btn {
        display:inline-flex; align-items:center; gap:7px;
        padding:0.65rem 1.25rem; border-radius:8px;
        background:linear-gradient(90deg,#6366f1,#8b5cf6);
        color:#fff; font-size:0.84rem; font-weight:700;
        border:none; cursor:pointer; transition:opacity 140ms;
    }
    .ai-generate-btn:hover { opacity:0.9; }
    .ai-generate-btn:disabled { opacity:0.5; cursor:not-allowed; }
    .ai-spinner { width:14px; height:14px; border:2px solid rgba(255,255,255,0.3); border-top-color:#fff; border-radius:50%; animation:wspin 0.7s linear infinite; }
    .ai-result-box { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:8px; padding:0.85rem 1rem; margin-top:0.75rem; display:none; }
    .ai-result-box label { font-size:0.72rem; font-weight:600; color:#6366f1; text-transform:uppercase; letter-spacing:0.06em; display:block; margin-bottom:6px; }
    .ai-subject-preview { font-size:0.85rem; color:#0f172a; font-weight:600; margin-bottom:0.6rem; padding-bottom:0.6rem; border-bottom:1px solid #e2e8f0; }
    .ai-body-preview { font-size:0.78rem; color:#374151; line-height:1.7; max-height:140px; overflow-y:auto; white-space:pre-wrap; }
    .ai-use-btn { display:inline-flex; align-items:center; gap:5px; margin-top:0.75rem; padding:0.5rem 1rem; background:#22c55e; color:#fff; border:none; border-radius:7px; font-size:0.8rem; font-weight:700; cursor:pointer; transition:background 140ms; }
    .ai-use-btn:hover { background:#16a34a; }
    .ai-error-msg { color:#dc2626; font-size:0.78rem; margin-top:0.5rem; display:none; }
    /* Attachment upload */
    .attach-zone { border:2px dashed #d1d5db; border-radius:8px; padding:0.75rem 1rem; display:flex; align-items:center; gap:0.75rem; cursor:pointer; transition:border-color 140ms; background:#fafafa; }
    .attach-zone:hover { border-color:#6366f1; background:#f5f3ff; }
    .attach-zone input[type=file] { display:none; }
    .attach-label { font-size:0.82rem; color:#64748b; flex:1; }
    .attach-name { font-size:0.82rem; color:#6366f1; font-weight:600; }

    /* Log table */
    .log-table { width:100%; border-collapse:collapse; }
    .log-table th { text-align:left; font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; padding:0.6rem 1rem; background:#f8fafc; }
    .log-table td { padding:0.7rem 1rem; font-size:0.8rem; color:#374151; border-top:1px solid #f1f5f9; }
    .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.7rem; font-weight:700; }
    .badge-sent   { background:#dcfce7; color:#16a34a; }
    .badge-failed { background:#fee2e2; color:#dc2626; }
</style>
@endsection

@section('content')

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
    <div>
        <h1 style="font-size:1.3rem; font-weight:700; color:#0f172a;">Run Emails</h1>
        <p style="color:#64748b; font-size:0.875rem; margin-top:3px;">Compose and send personalised emails to speakers.</p>
    </div>
</div>

{{-- Stat chips --}}
<div class="stat-chips">
    <div class="stat-chip">
        <div style="width:36px;height:36px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div><div class="stat-chip-val">{{ $sentTotal }}</div><div class="stat-chip-lbl">Emails Sent</div></div>
    </div>
    <div class="stat-chip">
        <div style="width:36px;height:36px;border-radius:8px;background:#fef2f2;display:flex;align-items:center;justify-content:center;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <div><div class="stat-chip-val">{{ $failedTotal }}</div><div class="stat-chip-lbl">Failed</div></div>
    </div>
    <div class="stat-chip">
        <div style="width:36px;height:36px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div><div class="stat-chip-val">{{ $speakers->count() }}</div><div class="stat-chip-lbl">Total Speakers</div></div>
    </div>
</div>

@if(session('send_result'))
    <div class="alert {{ session('send_failed', 0) > 0 && session('send_sent', 0) === 0 ? 'alert-error' : 'alert-success' }}">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('send_result') }}
    </div>
@endif

{{-- ── AI Draft Invite ── --}}
<div class="ai-panel">
    <div class="ai-panel-head">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="url(#ai-grad)" stroke-width="2" style="flex-shrink:0;">
            <defs><linearGradient id="ai-grad" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#6366f1"/><stop offset="100%" stop-color="#8b5cf6"/></linearGradient></defs>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
        <div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="ai-panel-title">AI Draft Invite</span>
                <span class="ai-badge">GPT-4o-mini</span>
            </div>
            <div class="ai-panel-sub">Pick a speaker · assign a topic · AI researches & writes a personalised invitation draft</div>
        </div>
    </div>

    <div class="ai-grid">
        <div class="ai-field">
            <label>Speaker</label>
            <select id="ai-speaker-select" class="ai-select">
                <option value="">— Select a speaker —</option>
                @foreach($speakers as $speaker)
                <option value="{{ $speaker->id }}"
                        data-name="{{ $speaker->full_name }}"
                        data-title="{{ $speaker->title }}"
                        data-company="{{ $speaker->company }}"
                        data-seniority="{{ $speaker->seniority }}"
                        data-country="{{ $speaker->country }}"
                        data-email="{{ $speaker->email }}">
                    {{ $speaker->full_name }}{{ $speaker->company ? ' — '.$speaker->company : '' }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="ai-field">
            <label>Event / Context <span style="color:#475569;">(optional)</span></label>
            <select id="ai-event-select" class="ai-select">
                <option value="">— No specific event —</option>
                @foreach($events as $event)
                <option value="{{ $event->id }}" data-name="{{ $event->name }}" data-date="{{ $event->date->format('F j, Y') }}" data-location="{{ $event->location }}">
                    {{ $event->name }} · {{ $event->date->format('M Y') }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="ai-field">
            <label>Topic / Speaking Role</label>
            <input type="text" id="ai-topic" class="ai-input" placeholder="e.g. Keynote on Future of Digital Banking">
        </div>
        <div class="ai-field">
            <label>Attachment (mention in email) <span style="color:#475569;">(optional)</span></label>
            <input type="text" id="ai-attachment" class="ai-input" placeholder="e.g. Event Brochure PDF, Agenda 2025">
        </div>
    </div>

    <div style="display:flex; align-items:center; gap:0.75rem;">
        <button type="button" class="ai-generate-btn" id="ai-gen-btn" onclick="generateAIDraft()">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" id="ai-btn-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span id="ai-btn-text">Generate Draft</span>
        </button>
        <span style="font-size:0.75rem; color:#475569;">AI will research the speaker and write a personalised invitation</span>
    </div>

    <div class="ai-error-msg" id="ai-error"></div>

    {{-- Result preview --}}
    <div class="ai-result-box" id="ai-result-box">
        <label>Generated Draft — preview</label>
        <div class="ai-subject-preview" id="ai-subject-preview"></div>
        <div class="ai-body-preview" id="ai-body-preview"></div>
        <button type="button" class="ai-use-btn" onclick="useAIDraft()">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Use This Draft
        </button>
    </div>
</div>

<form action="{{ route('admin.emails.send') }}" method="POST" id="email-form" enctype="multipart/form-data">
    @csrf
<div class="email-layout">

    {{-- ── Composer ── --}}
    <div>
        <div class="compose-card">
            <div class="compose-head">
                <span style="font-size:0.95rem; font-weight:700; color:#0f172a;">Compose Message</span>
                <span style="font-size:0.78rem; color:#94a3b8;">Variables are replaced per recipient</span>
            </div>
            <div class="compose-body">

                {{-- Subject --}}
                <div class="form-group">
                    <label class="form-label">Subject Line</label>
                    <input type="text" name="subject" id="subject-input"
                           class="form-input @error('subject') is-error @enderror"
                           placeholder="e.g. You're invited to speak at {first_name}'s event!"
                           value="{{ old('subject') }}" required>
                    @error('subject') <span style="font-size:0.78rem;color:#dc2626;">{{ $message }}</span> @enderror
                </div>

                {{-- Variable chips --}}
                <div class="form-group">
                    <label class="form-label">Insert Variable</label>
                    <div class="vars-bar">
                        @foreach([
                            '{first_name}' => 'First Name',
                            '{last_name}'  => 'Last Name',
                            '{name}'       => 'Name',
                            '{full_name}'  => 'Full Name',
                            '{email}'      => 'Email',
                            '{company}'    => 'Company',
                            '{title}'      => 'Title',
                            '{seniority}'  => 'Seniority',
                            '{country}'    => 'Country',
                        ] as $var => $label)
                        <span class="var-chip" onclick="insertVar('{{ $var }}', event)" title="{{ $label }}">
                            {{ $var }}
                        </span>
                        @endforeach
                    </div>
                </div>

                {{-- Summernote editor --}}
                <div class="form-group">
                    <label class="form-label">Email Body</label>
                    <textarea name="body" id="email-body" style="display:none;">{{ old('body') }}</textarea>
                    <div id="summernote-editor"></div>
                    @error('body') <span style="font-size:0.78rem;color:#dc2626;">{{ $message }}</span> @enderror
                </div>

                {{-- File Attachment --}}
                <div class="form-group">
                    <label class="form-label">Attachment <span style="font-weight:400; color:#94a3b8;">(optional — sent to all recipients)</span></label>
                    <label class="attach-zone" for="email-attachment" id="attach-zone">
                        <input type="file" name="attachment" id="email-attachment" onchange="onAttachChange(this)">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#6366f1" stroke-width="2" style="flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span class="attach-label" id="attach-label">Click to attach a file (PDF, DOCX, etc.)</span>
                        <span id="attach-remove" onclick="removeAttachment(event)" style="display:none; font-size:0.75rem; color:#ef4444; font-weight:600; cursor:pointer; padding:2px 6px; border-radius:4px; border:1px solid #fecaca;">✕ Remove</span>
                    </label>
                </div>

                {{-- Send button --}}
                <div style="display:flex; align-items:center; gap:0.75rem; margin-top:1rem; padding-top:1rem; border-top:1px solid #f1f5f9;">
                    <button type="submit" class="btn" id="send-btn" onclick="return confirmSend()">
                        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Emails
                    </button>
                    <span id="send-summary" style="font-size:0.82rem; color:#64748b;"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Speaker Picker ── --}}
    <div>

        {{-- Event Quick Send --}}
        @if($events->isNotEmpty())
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; margin-bottom:1rem;">
            <div style="font-size:0.8rem; font-weight:700; color:#374151; margin-bottom:0.5rem; display:flex; align-items:center; gap:6px;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Send to Entire Event
            </div>
            <select id="event-select" onchange="onEventSelect(this)"
                    style="width:100%; padding:0.55rem 0.75rem; border:1.5px solid #e2e8f0; border-radius:7px; font-size:0.82rem; outline:none; color:#0f172a; background:#fff; font-family:inherit; cursor:pointer; margin-bottom:0.6rem;">
                <option value="">— Pick an event —</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" data-count="{{ $event->speakers_count }}">
                        {{ $event->name }} ({{ $event->speakers_count }} speaker{{ $event->speakers_count !== 1 ? 's' : '' }})
                    </option>
                @endforeach
            </select>
            {{-- Event mode banner --}}
            <div id="event-mode-banner" style="display:none; background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:8px; padding:0.6rem 0.85rem; font-size:0.82rem; color:#1d4ed8; font-weight:600; display:none; align-items:center; justify-content:space-between;">
                <span id="event-mode-text"></span>
                <button type="button" onclick="clearEventMode()" style="background:none;border:none;cursor:pointer;color:#64748b;font-size:0.75rem;padding:0;">✕ Clear</button>
            </div>
            <input type="hidden" name="event_id" id="event-id-input" value="">
        </div>
        @endif

        <div class="picker-card" style="margin-bottom:1.25rem;" id="manual-picker">
            <div class="picker-head">
                <span class="picker-title">Manual Selection</span>
                <div style="display:flex; gap:6px;">
                    <button type="button" class="btn btn-outline" style="font-size:0.75rem; padding:0.3rem 0.7rem;" onclick="selectAll(true)">All</button>
                    <button type="button" class="btn btn-outline" style="font-size:0.75rem; padding:0.3rem 0.7rem;" onclick="selectAll(false)">None</button>
                </div>
            </div>
            <input type="text" class="picker-search" placeholder="Search speakers…" oninput="filterList(this.value)" id="picker-search">
            <div class="speaker-list" id="speaker-list">
                @php $colors = ['#2563eb','#16a34a','#9333ea','#ef4444','#ca8a04','#0891b2']; @endphp
                @foreach($speakers as $i => $speaker)
                <label class="speaker-row"
                       data-search="{{ strtolower($speaker->full_name . ' ' . $speaker->email . ' ' . $speaker->company) }}"
                       data-event="{{ $speaker->event_id ?? '' }}">
                    <input type="checkbox" name="speaker_ids[]" value="{{ $speaker->id }}" onchange="updateCount()">
                    <div class="sp-avatar" style="background:{{ $colors[$i % count($colors)] }};">
                        {{ strtoupper(substr($speaker->first_name, 0, 1)) }}
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div class="sp-name">{{ $speaker->full_name }}</div>
                        <div class="sp-email">{{ $speaker->email }}</div>
                    </div>
                    @if($speaker->event)
                        <span style="font-size:0.65rem;color:#2563eb;background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;padding:1px 5px;white-space:nowrap;flex-shrink:0;">
                            {{ Str::limit($speaker->event->name, 14) }}
                        </span>
                    @endif
                </label>
                @endforeach
            </div>
            <div class="picker-footer">
                <span class="sel-count" id="sel-count">0 selected</span>
                @error('speaker_ids') <span style="font-size:0.75rem;color:#dc2626;">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Quick presets --}}
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem;">
            <div style="font-size:0.8rem; font-weight:700; color:#374151; margin-bottom:0.6rem;">Quick Select by Seniority</div>
            @foreach(['Head','Manager','C suite'] as $sen)
            <button type="button" onclick="selectBySeniority('{{ strtolower($sen) }}')"
                    style="display:block;width:100%;text-align:left;padding:0.45rem 0.6rem;border-radius:6px;border:1px solid #e2e8f0;background:#f8fafc;font-size:0.8rem;font-weight:500;color:#374151;cursor:pointer;margin-bottom:4px;transition:all 120ms;"
                    onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='#f8fafc'">
                + Select all {{ $sen }}s
            </button>
            @endforeach
        </div>
    </div>

</div>
</form>

{{-- ── Email Log ── --}}
@if($logs->isNotEmpty())
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-top:1.5rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:0.85rem 1.25rem; border-bottom:1px solid #f1f5f9;">
        <span style="font-size:0.95rem; font-weight:700; color:#0f172a;">Recent Send Log</span>
        <span style="font-size:0.78rem; color:#94a3b8;">Last 20 emails</span>
    </div>
    <table class="log-table">
        <thead>
            <tr>
                <th>Recipient</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Sent At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>
                    <div style="font-weight:600;">{{ $log->to_name }}</div>
                    <div style="color:#94a3b8;font-size:0.75rem;">{{ $log->to_email }}</div>
                </td>
                <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $log->subject }}</td>
                <td>
                    <span class="badge badge-{{ $log->status }}">{{ ucfirst($log->status) }}</span>
                    @if($log->error)
                        <div style="font-size:0.7rem;color:#ef4444;margin-top:2px;" title="{{ $log->error }}">
                            {{ Str::limit($log->error, 40) }}
                        </div>
                    @endif
                </td>
                <td style="color:#94a3b8;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- jQuery + Bootstrap (Summernote deps) + Summernote --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
    $('#summernote-editor').summernote({
        height: 320,
        placeholder: 'Write your email here… Use variables like {first_name} to personalise.',
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
                document.getElementById('email-body').value = contents;
            },
            onInit: function() {
                // Pre-fill if old value exists
                const old = document.getElementById('email-body').value;
                if (old) $('#summernote-editor').summernote('code', old);
            }
        }
    });
});

// Insert variable at cursor in subject or Summernote
let lastFocused = 'editor'; // 'subject' or 'editor'
document.getElementById('subject-input').addEventListener('focus', () => lastFocused = 'subject');
document.getElementById('summernote-editor').addEventListener('focusin', () => lastFocused = 'editor');

function insertVar(varText, e) {
    e.preventDefault();
    if (lastFocused === 'subject') {
        const inp = document.getElementById('subject-input');
        const s = inp.selectionStart, en = inp.selectionEnd;
        inp.value = inp.value.substring(0, s) + varText + inp.value.substring(en);
        inp.focus();
        inp.setSelectionRange(s + varText.length, s + varText.length);
    } else {
        $('#summernote-editor').summernote('insertText', varText);
    }
}

// ── Event mode ────────────────────────────────────────────────────
let eventMode = false; // true = sending by event_id, false = manual checkboxes

function onEventSelect(sel) {
    const eventId = sel.value;
    const banner  = document.getElementById('event-mode-banner');
    const input   = document.getElementById('event-id-input');

    if (!eventId) {
        clearEventMode();
        return;
    }

    const count   = sel.options[sel.selectedIndex].dataset.count;
    const name    = sel.options[sel.selectedIndex].text;

    // Set event mode
    eventMode = true;
    input.value = eventId;

    // Show banner
    banner.style.display = 'flex';
    document.getElementById('event-mode-text').textContent =
        `Event mode: "${name}" — ${count} speaker${count != 1 ? 's' : ''} will receive this email`;

    // In manual picker: filter to only show this event's speakers, check them all
    document.querySelectorAll('#speaker-list .speaker-row').forEach(row => {
        const rowEvent = row.getAttribute('data-event');
        if (rowEvent == eventId) {
            row.style.display = '';
            row.querySelector('input[type=checkbox]').checked = true;
        } else {
            row.style.display = 'none';
            row.querySelector('input[type=checkbox]').checked = false;
        }
    });

    // Update summary
    document.getElementById('send-summary').textContent =
        `Event mode — will send to ${count} speaker${count != 1 ? 's' : ''}`;
    document.getElementById('sel-count').textContent = count + ' selected (event)';
}

function clearEventMode() {
    eventMode = false;
    document.getElementById('event-id-input').value = '';
    document.getElementById('event-select').value = '';
    document.getElementById('event-mode-banner').style.display = 'none';

    // Un-hide all rows and uncheck all
    document.querySelectorAll('#speaker-list .speaker-row').forEach(row => {
        row.style.display = '';
        row.querySelector('input[type=checkbox]').checked = false;
    });
    updateCount();
}

// ── Speaker picker ─────────────────────────────────────────────────
function updateCount() {
    if (eventMode) return; // count managed by event mode
    const checked = document.querySelectorAll('#speaker-list input[type=checkbox]:checked').length;
    document.getElementById('sel-count').textContent = checked + ' selected';
    document.getElementById('send-summary').textContent = checked
        ? 'Will send to ' + checked + ' speaker' + (checked !== 1 ? 's' : '')
        : '';
}

function selectAll(val) {
    clearEventMode(); // switching to manual
    document.querySelectorAll('#speaker-list input[type=checkbox]').forEach(cb => {
        const row = cb.closest('.speaker-row');
        if (row.style.display !== 'none') cb.checked = val;
    });
    updateCount();
}

function filterList(q) {
    if (eventMode) return; // don't override event filter
    q = q.toLowerCase();
    document.querySelectorAll('#speaker-list .speaker-row').forEach(row => {
        const match = !q || row.getAttribute('data-search').includes(q);
        row.style.display = match ? '' : 'none';
    });
}

function selectBySeniority(sen) {
    clearEventMode();
    const mapping = @json($speakers->mapWithKeys(fn($s) => [$s->id => strtolower($s->seniority ?? '')]));
    document.querySelectorAll('#speaker-list input[type=checkbox]').forEach(cb => {
        const sid = cb.value;
        if (mapping[sid] && mapping[sid].includes(sen)) cb.checked = true;
    });
    updateCount();
}

// ── AI Draft ──────────────────────────────────────────────────────
let aiDraftSubject = '';
let aiDraftBody    = '';

async function generateAIDraft() {
    const speakerSel = document.getElementById('ai-speaker-select');
    const speakerId  = speakerSel.value;
    if (!speakerId) { alert('Please select a speaker first.'); return; }

    const topic      = document.getElementById('ai-topic').value.trim();
    const attachment = document.getElementById('ai-attachment').value.trim();
    const eventSel   = document.getElementById('ai-event-select');
    const eventId    = eventSel.value;
    const eventName  = eventSel.value ? eventSel.options[eventSel.selectedIndex].dataset.name : '';
    const eventDate  = eventSel.value ? eventSel.options[eventSel.selectedIndex].dataset.date : '';
    const eventLoc   = eventSel.value ? eventSel.options[eventSel.selectedIndex].dataset.location : '';

    const btn    = document.getElementById('ai-gen-btn');
    const errBox = document.getElementById('ai-error');
    const resBox = document.getElementById('ai-result-box');

    btn.disabled = true;
    document.getElementById('ai-btn-text').textContent = 'Generating…';
    document.getElementById('ai-btn-icon').outerHTML = '<span class="ai-spinner" id="ai-btn-icon"></span>';
    errBox.style.display = 'none';
    resBox.style.display = 'none';

    try {
        const resp = await fetch('{{ route("admin.emails.ai_draft") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ speaker_id: speakerId, topic, attachment, event_name: eventName, event_date: eventDate, event_location: eventLoc }),
        });
        const data = await resp.json();
        if (!resp.ok || data.error) {
            errBox.textContent  = data.error || 'Failed to generate draft.';
            errBox.style.display = 'block';
        } else {
            aiDraftSubject = data.subject;
            aiDraftBody    = data.body;
            document.getElementById('ai-subject-preview').textContent = data.subject;
            document.getElementById('ai-body-preview').textContent    = data.body_plain;
            resBox.style.display = 'block';
        }
    } catch(e) {
        errBox.textContent   = 'Network error: ' + e.message;
        errBox.style.display = 'block';
    } finally {
        btn.disabled = false;
        document.getElementById('ai-btn-text').textContent = 'Generate Draft';
        const spinner = document.getElementById('ai-btn-icon');
        if (spinner) spinner.outerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" id="ai-btn-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>';
    }
}

function useAIDraft() {
    // Fill subject
    document.getElementById('subject-input').value = aiDraftSubject;
    // Fill Summernote body
    $('#summernote-editor').summernote('code', aiDraftBody);
    document.getElementById('email-body').value = aiDraftBody;

    // Auto-select this speaker in the picker
    const speakerId = document.getElementById('ai-speaker-select').value;
    if (speakerId) {
        clearEventMode();
        document.querySelectorAll('#speaker-list input[type=checkbox]').forEach(cb => {
            cb.checked = (cb.value == speakerId);
        });
        updateCount();
    }

    // Scroll down to composer
    document.getElementById('email-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Attachment zone ───────────────────────────────────────────────
function onAttachChange(input) {
    const label  = document.getElementById('attach-label');
    const remove = document.getElementById('attach-remove');
    const zone   = document.getElementById('attach-zone');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const size = file.size > 1048576
            ? (file.size / 1048576).toFixed(1) + ' MB'
            : Math.round(file.size / 1024) + ' KB';
        label.innerHTML = `<span class="attach-name">${file.name}</span> <span style="color:#94a3b8;font-size:0.75rem;">(${size})</span>`;
        remove.style.display = 'inline-block';
        zone.style.borderColor = '#6366f1';
        // Auto-fill AI attachment field with filename (minus extension)
        const baseName = file.name.replace(/\.[^/.]+$/, '');
        const aiAttach = document.getElementById('ai-attachment');
        if (!aiAttach.value) aiAttach.value = baseName;
    }
}

function removeAttachment(e) {
    e.preventDefault();
    e.stopPropagation();
    const input  = document.getElementById('email-attachment');
    const label  = document.getElementById('attach-label');
    const remove = document.getElementById('attach-remove');
    const zone   = document.getElementById('attach-zone');
    input.value  = '';
    label.innerHTML = 'Click to attach a file (PDF, DOCX, etc.)';
    remove.style.display = 'none';
    zone.style.borderColor = '#d1d5db';
}

function confirmSend() {
    const subj = document.getElementById('subject-input').value;
    if (!subj) { alert('Please enter a subject line.'); return false; }

    const body = $('#summernote-editor').summernote('code');
    if (!body || body === '<p><br></p>') { alert('Please write the email body.'); return false; }
    document.getElementById('email-body').value = body;

    if (eventMode) {
        const text = document.getElementById('event-mode-text').textContent;
        return confirm(`${text}\n\nProceed?`);
    }

    const count = document.querySelectorAll('#speaker-list input[type=checkbox]:checked').length;
    if (count === 0) { alert('Please select at least one speaker or choose an event.'); return false; }
    return confirm(`Send email to ${count} speaker${count !== 1 ? 's' : ''}?`);
}
</script>
@endsection
