@extends('layouts.admin-sidebar')

@section('title', 'New Campaign')
@section('page-title', 'New Campaign')

@section('extra-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css">
<style>
    .step-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; margin-bottom:1rem; }
    .step-card h2 { font-size:1rem; font-weight:700; color:#0f172a; margin-bottom:0.25rem; }
    .step-card p.hint { font-size:0.82rem; color:#64748b; margin-bottom:1rem; }

    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .form-group { display:flex; flex-direction:column; gap:0.4rem; }
    .form-group.full { grid-column:1/-1; }
    .form-label { font-size:0.875rem; font-weight:600; color:#374151; }
    .form-input, .form-select, .form-textarea {
        width:100%; padding:0.7rem 0.9rem;
        border:1.5px solid #d1d5db; border-radius:8px;
        font-size:0.875rem; outline:none; color:#0f172a; font-family:inherit; background:#fff;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
    .form-textarea { resize:vertical; min-height:80px; font-family:ui-monospace,Menlo,monospace; font-size:0.82rem; }

    .upload-area {
        border:2px dashed #d1d5db; border-radius:10px; padding:1.5rem;
        text-align:center; cursor:pointer; transition:all 140ms;
        background:#fafafa; position:relative;
    }
    .upload-area:hover { border-color:#2563eb; background:#eff6ff; }
    .upload-area input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }

    .speakers-list { max-height:300px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; }
    .speakers-list label { display:flex; align-items:center; gap:8px; padding:0.5rem 0.75rem; border-bottom:1px solid #f1f5f9; font-size:0.85rem; cursor:pointer; }
    .speakers-list label:last-child { border-bottom:none; }
    .speakers-list label:hover { background:#f8fafc; }

    .toggle { display:inline-flex; align-items:center; gap:8px; }
    .toggle input { width:17px; height:17px; }

    .footer-bar { display:flex; gap:0.75rem; align-items:center; margin-top:1rem; padding-top:1rem; border-top:1px solid #f1f5f9; flex-wrap:wrap; }

    .pill-token {
        display:inline-block; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb;
        font-family:ui-monospace,Menlo,monospace; font-size:0.74rem;
        padding:1px 7px; border-radius:5px; margin:1px; cursor:pointer;
    }
    .pill-token:hover { background:#dbeafe; }
</style>
@endsection

@section('content')

<div style="margin-bottom:1rem;">
    <a href="{{ route('admin.campaigns.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.875rem;text-decoration:none;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Campaigns
    </a>
</div>

@if($errors->any())
<div class="alert-success" style="margin-bottom:1rem; background:#fef2f2; border-color:#fecaca; color:#dc2626;">
    @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
</div>
@endif

<form action="{{ route('admin.campaigns.store') }}" method="POST" enctype="multipart/form-data" id="campaign-form">
    @csrf

    {{-- 1. Basics --}}
    <div class="step-card">
        <h2>Campaign basics</h2>
        <p class="hint">Give it a name and pick the event this campaign is for (optional but recommended).</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="e.g. Banking Summit 2026 — Wave 1" required>
            </div>
            <div class="form-group">
                <label class="form-label">Event (for context)</label>
                <select name="event_id" id="event-select" class="form-select">
                    <option value="">— No event —</option>
                    @foreach($events as $e)
                        <option value="{{ $e->id }}"
                                data-count="{{ $e->speakers_count }}"
                                {{ (string)old('event_id', $preselectedEventId) === (string)$e->id ? 'selected' : '' }}>
                            {{ $e->name }} ({{ $e->speakers_count }} speakers)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- 2. Agenda PDF --}}
    <div class="step-card">
        <h2>Agenda PDF</h2>
        <p class="hint">Uploaded once. The AI reads it for every recipient to pick the topic that best matches them.</p>
        <div class="upload-area" id="upload-area">
            <input type="file" name="agenda_pdf" id="pdf-input" accept="application/pdf" onchange="onPdfChange(this)">
            <div id="pdf-default">
                <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:0.4rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                <div style="font-size:0.85rem; color:#64748b;"><strong style="color:#2563eb;">Click to upload</strong> or drag a PDF here</div>
                <div style="font-size:0.74rem; color:#94a3b8; margin-top:2px;">Max 20 MB</div>
            </div>
            <div id="pdf-selected" style="display:none; font-size:0.9rem; color:#0f172a; font-weight:600;"></div>
        </div>

        <label class="toggle" style="margin-top:0.75rem;">
            <input type="checkbox" name="attach_agenda" value="1" {{ old('attach_agenda') ? 'checked' : '' }}>
            <span class="form-label" style="margin:0;">Also attach this PDF to every outgoing email</span>
        </label>
        <p style="font-size:0.75rem; color:#94a3b8; margin-top:4px;">
            Off by default — the PDF is always read by the AI to pick the topic, but it's not sent to the speaker unless you tick this.
        </p>
    </div>

    {{-- 3. Recipients --}}
    <div class="step-card">
        <h2>Recipients</h2>
        <p class="hint">Choose by event (uses all speakers in the event) or pick speakers manually.</p>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Mode</label>
                <select id="recipient-mode" class="form-select">
                    <option value="event">All speakers in selected event</option>
                    <option value="manual" {{ !old('event_id', $preselectedEventId) && old('speaker_ids') ? 'selected' : '' }}>Pick speakers manually</option>
                </select>
            </div>
            <div class="form-group" id="manual-search-wrap" style="display:none;">
                <label class="form-label">Search</label>
                <input type="text" id="manual-search" class="form-input" placeholder="Filter by name, email, company…">
            </div>
        </div>

        <div id="manual-speakers-wrap" style="display:none; margin-top:0.75rem;">
            <div class="speakers-list" id="speakers-list">
                @foreach($speakers as $s)
                    <label data-search="{{ strtolower($s->full_name . ' ' . $s->email . ' ' . $s->company) }}">
                        <input type="checkbox" name="speaker_ids[]" value="{{ $s->id }}"
                               {{ in_array($s->id, old('speaker_ids', [])) ? 'checked' : '' }}>
                        <span style="flex:1;">
                            <strong>{{ $s->full_name }}</strong>
                            <span style="color:#94a3b8; font-size:0.75rem;"> · {{ $s->email ?: 'no email' }}</span>
                            @if($s->company)
                                <span style="color:#64748b; font-size:0.75rem;"> · {{ $s->company }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            <div style="font-size:0.78rem; color:#64748b; margin-top:0.4rem;">
                <a href="#" onclick="toggleAll(true); return false;" style="color:#2563eb;">Select all visible</a>
                ·
                <a href="#" onclick="toggleAll(false); return false;" style="color:#64748b;">Clear</a>
            </div>
        </div>
    </div>

    {{-- 4. Templates --}}
    <div class="step-card">
        <h2>Subject &amp; body templates</h2>
        <p class="hint">
            Tokens you can use:
            @foreach(['{first_name}','{last_name}','{full_name}','{topic}','{deadline_date}','{event_name}','{event_location}','{event_date}','{company}','{title}'] as $t)
                <span class="pill-token" onclick="insertToken('{{ $t }}')">{{ $t }}</span>
            @endforeach
        </p>

        <div class="form-group" style="margin-bottom:0.75rem;">
            <label class="form-label">Subject</label>
            <input type="text" name="subject_template" id="subject-input" class="form-input" value="{{ old('subject_template', $defaultSubject) }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Body (HTML)</label>
            <textarea name="body_template" id="body-textarea" style="display:none;">{{ old('body_template', $defaultBody) }}</textarea>
            <div id="body-editor"></div>
        </div>
    </div>

    {{-- 5. Throttle / start --}}
    <div class="step-card">
        <h2>Throttle &amp; launch</h2>
        <p class="hint">
            Wait this many seconds between sends so SMTPs don't burn out. With multiple SMTP accounts each one rotates,
            so the effective rate per inbox is even lower.
        </p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Throttle (seconds between sends)</label>
                <input type="number" name="throttle_seconds" value="{{ old('throttle_seconds', 120) }}" min="30" max="3600" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="toggle" style="margin-top:1.7rem;">
                    <input type="checkbox" name="start_now" value="1" checked>
                    <span class="form-label" style="margin:0;">Start sending immediately after save</span>
                </label>
            </div>
        </div>

        <div class="footer-bar">
            <button type="submit" class="btn">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Save Campaign
            </button>
            <a href="{{ route('admin.campaigns.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script>
const subjectInput = document.getElementById('subject-input');
const bodyTextarea = document.getElementById('body-textarea');

$('#body-editor').summernote({
    height: 360,
    placeholder: 'Email body…',
    toolbar: [
        ['style', ['bold','italic','underline','clear']],
        ['font', ['fontsize']],
        ['para', ['ul','ol','paragraph']],
        ['insert',['link']],
        ['view',['codeview']],
    ],
    callbacks: {
        onChange: function (contents) { bodyTextarea.value = contents; },
        onInit: function () {
            const old = bodyTextarea.value;
            if (old) $('#body-editor').summernote('code', old);
        },
    },
});

let lastFocused = 'subject';
subjectInput.addEventListener('focusin', () => lastFocused = 'subject');
document.getElementById('body-editor').addEventListener('focusin', () => lastFocused = 'body');

function insertToken(t) {
    if (lastFocused === 'subject') {
        const start = subjectInput.selectionStart;
        const end = subjectInput.selectionEnd;
        subjectInput.value = subjectInput.value.slice(0,start) + t + subjectInput.value.slice(end);
        subjectInput.focus();
        subjectInput.setSelectionRange(start + t.length, start + t.length);
    } else {
        $('#body-editor').summernote('insertText', t);
    }
}

// Recipient mode toggle
const eventSelect = document.getElementById('event-select');
const modeSelect  = document.getElementById('recipient-mode');
const manualWrap  = document.getElementById('manual-speakers-wrap');
const manualSearchWrap = document.getElementById('manual-search-wrap');

function refreshMode() {
    const isManual = modeSelect.value === 'manual';
    manualWrap.style.display = isManual ? '' : 'none';
    manualSearchWrap.style.display = isManual ? '' : 'none';
    eventSelect.disabled = isManual;
    // unselect speakers if switching back to event mode
    if (!isManual) {
        document.querySelectorAll('#speakers-list input[type=checkbox]').forEach(c => c.checked = false);
    }
}
modeSelect.addEventListener('change', refreshMode);
refreshMode();

// Search filter
document.getElementById('manual-search').addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('#speakers-list label').forEach(lb => {
        const match = !q || lb.dataset.search.includes(q);
        lb.style.display = match ? '' : 'none';
    });
});

function toggleAll(on) {
    document.querySelectorAll('#speakers-list label').forEach(lb => {
        if (lb.style.display !== 'none') lb.querySelector('input').checked = on;
    });
}

// PDF UI
function onPdfChange(input) {
    if (input.files && input.files[0]) {
        document.getElementById('pdf-default').style.display = 'none';
        const sel = document.getElementById('pdf-selected');
        sel.style.display = 'block';
        sel.innerHTML = '📄 ' + input.files[0].name + ' <span style="color:#94a3b8; font-weight:400; font-size:0.8rem;">(' + Math.round(input.files[0].size/1024) + ' KB)</span>';
    }
}

// Sync body before submit
document.getElementById('campaign-form').addEventListener('submit', () => {
    bodyTextarea.value = $('#body-editor').summernote('code');
});
</script>

@endsection
