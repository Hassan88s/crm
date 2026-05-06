@extends('layouts.admin-sidebar')

@section('title', 'New Manual Campaign')
@section('page-title', 'New Manual Campaign')

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
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1);
    }
    .form-textarea { resize:vertical; min-height:80px; font-family:ui-monospace,Menlo,monospace; font-size:0.82rem; }

    .speakers-list { max-height:300px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; }
    .speakers-list label { display:flex; align-items:center; gap:8px; padding:0.5rem 0.75rem; border-bottom:1px solid #f1f5f9; font-size:0.85rem; cursor:pointer; }
    .speakers-list label:last-child { border-bottom:none; }
    .speakers-list label:hover { background:#f8fafc; }

    .footer-bar { display:flex; gap:0.75rem; align-items:center; margin-top:1rem; padding-top:1rem; border-top:1px solid #f1f5f9; flex-wrap:wrap; }

    .pill-token {
        display:inline-block; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb;
        font-family:ui-monospace,Menlo,monospace; font-size:0.74rem;
        padding:1px 7px; border-radius:5px; margin:1px; cursor:pointer;
    }
    .pill-token:hover { background:#dbeafe; }

    /* Audience radio cards */
    .aud-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem; }
    .aud-card {
        border:2px solid #e2e8f0; border-radius:10px; padding:0.85rem 1rem;
        cursor:pointer; transition:all 120ms; background:#fff;
    }
    .aud-card:hover { border-color:#cbd5e1; }
    .aud-card.active { border-color:#2563eb; background:#eff6ff; }
    .aud-card .aud-title { font-weight:700; font-size:0.85rem; color:#0f172a; }
    .aud-card .aud-sub   { font-size:0.74rem; color:#64748b; margin-top:2px; }
    .aud-card input[type=radio] { display:none; }

    .info-banner {
        background:#fefce8; border:1px solid #fde68a; border-radius:10px;
        padding:0.85rem 1.1rem; margin-bottom:1rem;
        display:flex; align-items:flex-start; gap:0.7rem;
        font-size:0.82rem; color:#92400e;
    }
    .info-banner svg { width:18px; height:18px; flex-shrink:0; color:#ca8a04; margin-top:1px; }
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
<div class="info-banner" style="background:#fef2f2; border-color:#fecaca; color:#dc2626;">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        @foreach($errors->all() as $err) <div>{{ $err }}</div> @endforeach
    </div>
</div>
@endif

<div class="info-banner">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span><strong>Manual mode:</strong> No AI, no agenda PDF. Your subject and body are sent as-is, with template tokens like <code>{first_name}</code>, <code>{full_name}</code>, <code>{company}</code>, <code>{title}</code>, <code>{country}</code>, <code>{event_name}</code>, <code>{deadline_date}</code>, <code>{smtp_from_name}</code> replaced per recipient.</span>
</div>

<form action="{{ route('admin.campaigns.storeManual') }}" method="POST" id="manual-campaign-form">
    @csrf

    {{-- 1. Basics --}}
    <div class="step-card">
        <h2>Campaign basics</h2>
        <p class="hint">Give it a name. The event is optional — pick one only if you want <code>{event_name}</code> to render.</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Campaign name</label>
                <input type="text" name="name" class="form-input" required value="{{ old('name') }}" placeholder="e.g. Bounce follow-up — May 2026">
            </div>
            <div class="form-group">
                <label class="form-label">Event (optional)</label>
                <select name="event_id" class="form-select" id="event-select">
                    <option value="">— No event —</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ old('event_id', $preselectedEventId) == $event->id ? 'selected' : '' }}>
                            {{ $event->name }} ({{ $event->speakers_count }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- 2. Audience --}}
    <div class="step-card">
        <h2>Audience</h2>
        <p class="hint">Pick who this campaign goes to.</p>

        <div class="aud-grid">
            <label class="aud-card" data-aud="event">
                <input type="radio" name="audience_type" value="event" {{ old('audience_type', 'category') === 'event' ? 'checked' : '' }}>
                <div class="aud-title">📅 By event</div>
                <div class="aud-sub">Send to every speaker assigned to the event picked above.</div>
            </label>
            <label class="aud-card" data-aud="category">
                <input type="radio" name="audience_type" value="category" {{ old('audience_type', 'category') === 'category' ? 'checked' : '' }}>
                <div class="aud-title">🏷️ By reply category</div>
                <div class="aud-sub">Send to speakers grouped by their classified reply (No Reply, Bounced, etc.).</div>
            </label>
            <label class="aud-card" data-aud="manual">
                <input type="radio" name="audience_type" value="manual" {{ old('audience_type') === 'manual' ? 'checked' : '' }}>
                <div class="aud-title">✋ Pick speakers</div>
                <div class="aud-sub">Choose individual speakers from the list.</div>
            </label>
        </div>

        {{-- Audience: by category --}}
        <div id="aud-category" style="margin-top:1rem; display:none;">
            <label class="form-label" style="margin-bottom:0.4rem;">Pick a reply category</label>
            <select name="category" class="form-select">
                @foreach($categories as $c)
                    @php $cnt = (int)($categoryCounts[$c] ?? 0); @endphp
                    <option value="{{ $c }}" {{ old('category') === $c ? 'selected' : '' }}>
                        {{ $c }} ({{ $cnt }} speaker{{ $cnt === 1 ? '' : 's' }})
                    </option>
                @endforeach
            </select>
            <p style="font-size:0.74rem; color:#94a3b8; margin-top:5px; line-height:1.5;">
                <strong>Bounced:</strong> matched by parsing bounce-notification bodies, so these are speakers whose addresses actually failed delivery.<br>
                <strong>No Reply:</strong> follow-up cooldown — only includes speakers whose <em>last</em> email was at least
                <strong>{{ $noReplyCooldownDays ?? 2 }} day{{ ($noReplyCooldownDays ?? 2) === 1 ? '' : 's' }}</strong> ago,
                so you don't email the same person twice in quick succession.
            </p>
        </div>

        {{-- Audience: manual speaker picker --}}
        <div id="aud-manual" style="margin-top:1rem; display:none;">
            <input type="text" id="speaker-search" placeholder="Type to filter speakers…"
                   style="width:100%; padding:0.5rem 0.7rem; border:1.5px solid #e2e8f0; border-radius:8px; font-size:0.85rem; margin-bottom:0.5rem;">
            <div class="speakers-list" id="speakers-list">
                @foreach($speakers as $sp)
                    <label data-key="{{ strtolower($sp->full_name . ' ' . $sp->company . ' ' . $sp->email) }}">
                        <input type="checkbox" name="speaker_ids[]" value="{{ $sp->id }}"
                               {{ in_array($sp->id, old('speaker_ids', [])) ? 'checked' : '' }}>
                        <span><strong>{{ $sp->full_name }}</strong>
                            <span style="color:#94a3b8;">({{ $sp->email ?: 'no email' }}{{ $sp->company ? ' · '.$sp->company : '' }})</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 3. Subject + Body --}}
    <div class="step-card">
        <h2>Email content</h2>
        <p class="hint">Write the message exactly as you want it sent. Click a token below to insert into the body.</p>

        <div style="margin-bottom:0.5rem;">
            @foreach(['{first_name}','{last_name}','{full_name}','{name}','{email}','{company}','{title}','{seniority}','{country}','{event_name}','{deadline_date}','{smtp_from_name}'] as $t)
                <span class="pill-token" onclick="insertToken('{{ $t }}')">{{ $t }}</span>
            @endforeach
        </div>

        <div class="form-row" style="margin-top:0.5rem;">
            <div class="form-group full">
                <label class="form-label">Subject</label>
                <input type="text" name="subject_template" class="form-input" required
                       value="{{ old('subject_template') }}"
                       placeholder="e.g. Following up on your invitation — {first_name}">
            </div>

            <div class="form-group full" style="margin-top:0.5rem;">
                <label class="form-label">Body (HTML)</label>
                <textarea name="body_template" id="body-textarea" style="display:none;">{{ old('body_template') }}</textarea>
                <div id="body-editor"></div>
            </div>

            <div class="form-group full" style="margin-top:1rem;">
                <label class="form-label">Signature</label>
                <p class="hint" style="margin-bottom:0.4rem;">Use <code>{smtp_from_name}</code> — replaced at send-time with the rotated SMTP's display name.</p>
                <textarea name="signature_template" rows="6" class="form-input"
                          style="font-family:ui-monospace,Menlo,monospace; font-size:0.82rem;">{{ old('signature_template', $defaultSignature) }}</textarea>
            </div>
        </div>
    </div>

    {{-- 4. Throttle / start --}}
    <div class="step-card">
        <h2>Throttle &amp; launch</h2>
        <p class="hint">Wait time between sends so SMTP accounts don't get rate-limited. The campaign uses your active SMTP rotation.</p>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Throttle (seconds between sends)</label>
                <input type="number" name="throttle_seconds" class="form-input" min="30" max="3600" required value="{{ old('throttle_seconds', 120) }}">
            </div>
        </div>

        <div class="footer-bar">
            <button type="submit" name="start_now" value="0" class="btn btn-outline">Save as draft</button>
            <button type="submit" name="start_now" value="1" class="btn">Save &amp; Start</button>
        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script>
$('#body-editor').summernote({
    placeholder: 'Write your email…',
    tabsize: 2,
    height: 280,
    callbacks: {
        onChange: contents => { document.getElementById('body-textarea').value = contents; },
        onInit: () => {
            const old = document.getElementById('body-textarea').value;
            if (old) $('#body-editor').summernote('code', old);
        },
    },
});

function insertToken(t) {
    $('#body-editor').summernote('insertText', t);
}

// Audience type radio toggle
const audSections = {
    event:    document.getElementById('event-select'),
    category: document.getElementById('aud-category'),
    manual:   document.getElementById('aud-manual'),
};
function refreshAudience() {
    const v = document.querySelector('input[name="audience_type"]:checked')?.value || 'category';
    document.querySelectorAll('.aud-card').forEach(c => c.classList.toggle('active', c.dataset.aud === v));
    if (audSections.category) audSections.category.style.display = (v === 'category') ? '' : 'none';
    if (audSections.manual)   audSections.manual.style.display   = (v === 'manual')   ? '' : 'none';
}
document.querySelectorAll('input[name="audience_type"]').forEach(r => r.addEventListener('change', refreshAudience));
refreshAudience();

// Speaker filter
document.getElementById('speaker-search')?.addEventListener('input', e => {
    const q = e.target.value.toLowerCase();
    document.querySelectorAll('#speakers-list label').forEach(l => {
        l.style.display = !q || l.dataset.key.includes(q) ? '' : 'none';
    });
});

// Make sure the body textarea is filled before submit
document.getElementById('manual-campaign-form').addEventListener('submit', () => {
    document.getElementById('body-textarea').value = $('#body-editor').summernote('code');
});
</script>

@endsection
