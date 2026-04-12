@extends('layouts.admin-sidebar')
@section('title', 'Import Speakers')
@section('page-title', 'Import Speakers')

@section('extra-styles')
<style>
    .import-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:2rem; max-width:680px; }
    .upload-area {
        border:2px dashed #d1d5db; border-radius:12px; padding:3rem 2rem;
        text-align:center; cursor:pointer; transition:all 160ms;
        background:#fafafa; position:relative;
    }
    .upload-area:hover, .upload-area.drag-over { border-color:#2563eb; background:#eff6ff; }
    .upload-area input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .upload-icon svg { width:44px; height:44px; color:#94a3b8; margin:0 auto 0.75rem; display:block; }
    .upload-title { font-size:1rem; font-weight:600; color:#374151; margin-bottom:4px; }
    .upload-title strong { color:#2563eb; }
    .upload-sub { font-size:0.8rem; color:#94a3b8; }
    .file-selected { display:none; align-items:center; gap:10px; background:#eff6ff; border:1.5px solid #bfdbfe; border-radius:8px; padding:0.75rem 1rem; margin-top:1rem; }
    .file-selected svg { width:20px; height:20px; color:#2563eb; flex-shrink:0; }
    .file-selected .file-name { font-size:0.875rem; font-weight:600; color:#1d4ed8; }
    .file-selected .file-size { font-size:0.75rem; color:#64748b; }
    .error-msg { font-size:0.78rem; color:#dc2626; margin-top:4px; display:block; }

    /* Column map table */
    .col-map { width:100%; border-collapse:collapse; margin-top:0.75rem; }
    .col-map th { text-align:left; font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; padding:0.5rem 0.75rem; background:#f8fafc; }
    .col-map td { padding:0.5rem 0.75rem; font-size:0.82rem; color:#374151; border-top:1px solid #f1f5f9; }
    .col-req { display:inline-block; background:#fee2e2; color:#dc2626; font-size:0.68rem; font-weight:700; padding:1px 5px; border-radius:4px; margin-left:4px; }

    /* Steps */
    .steps { display:flex; align-items:flex-start; gap:0.5rem; margin-bottom:1.5rem; }
    .step { display:flex; align-items:center; gap:8px; font-size:0.82rem; color:#64748b; }
    .step-num { width:22px; height:22px; border-radius:50%; background:#2563eb; color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; flex-shrink:0; }
    .step-sep { color:#d1d5db; margin:0 2px; }

    .form-footer { display:flex; gap:0.75rem; margin-top:1.75rem; padding-top:1.5rem; border-top:1px solid #f1f5f9; }
</style>
@endsection

@section('content')

<div style="margin-bottom:1.25rem;">
    <a href="{{ route('admin.speakers.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.875rem;text-decoration:none;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Speakers
    </a>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:1.25rem; align-items:start;">

    {{-- Upload form --}}
    <div class="import-card">
        <h2 style="font-size:1.15rem; font-weight:700; color:#0f172a; margin-bottom:0.4rem;">Import Speakers from CSV</h2>
        <p style="font-size:0.875rem; color:#64748b; margin-bottom:1.5rem;">Upload a CSV file to bulk-import speakers. Duplicate emails will be skipped.</p>

        <form action="{{ route('admin.speakers.import.post') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="upload-area" id="drop-area">
                <input type="file" name="csv_file" accept=".csv,.txt" id="csv-input" onchange="onFileSelect(this)">
                <div class="upload-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="upload-title"><strong>Click to browse</strong> or drag & drop</div>
                <div class="upload-sub">CSV files only · Max 10MB</div>
            </div>

            <div class="file-selected" id="file-info">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <div class="file-name" id="file-name"></div>
                    <div class="file-size" id="file-size"></div>
                </div>
                <button type="button" onclick="clearFile()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1rem;">✕</button>
            </div>

            @error('csv_file') <span class="error-msg">{{ $message }}</span> @enderror

            {{-- Event assignment --}}
            @if($events->isNotEmpty())
            <div style="margin-top:1.25rem;">
                <label style="font-size:0.875rem; font-weight:600; color:#374151; display:block; margin-bottom:0.4rem;">
                    Assign to Event <span style="color:#94a3b8; font-weight:400;">(optional)</span>
                </label>
                <select name="event_id" style="width:100%; padding:0.65rem 0.9rem; border:1.5px solid #d1d5db; border-radius:8px; font-size:0.875rem; outline:none; color:#0f172a; font-family:inherit; background:#fff;">
                    <option value="">— No event —</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}">{{ $event->name }}</option>
                    @endforeach
                </select>
                <p style="font-size:0.75rem; color:#94a3b8; margin-top:4px;">All imported speakers will be linked to the selected event.</p>
            </div>
            @endif

            <div class="form-footer">
                <button type="submit" class="btn" id="import-btn" disabled style="opacity:0.5;cursor:not-allowed;">
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import Speakers
                </button>
                <a href="{{ route('admin.speakers.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    {{-- Instructions panel --}}
    <div>
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; margin-bottom:1rem;">
            <div style="font-weight:700; font-size:0.9rem; color:#0f172a; margin-bottom:0.75rem;">
                📋 Expected Columns
            </div>
            <table class="col-map">
                <thead>
                    <tr><th>Column</th><th>Required</th></tr>
                </thead>
                <tbody>
                    @foreach([
                        ['First Name', true],
                        ['Last Name', true],
                        ['Email', true],
                        ['Title', false],
                        ['Company', false],
                        ['Seniority', false],
                        ['Country', false],
                    ] as $col)
                    <tr>
                        <td style="font-family:monospace; font-size:0.8rem;">{{ $col[0] }}</td>
                        <td>
                            @if($col[1])
                                <span class="col-req">Required</span>
                            @else
                                <span style="color:#94a3b8; font-size:0.75rem;">Optional</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:1.25rem;">
            <div style="font-weight:700; font-size:0.875rem; color:#92400e; margin-bottom:0.5rem;">💡 Tips</div>
            <ul style="font-size:0.8rem; color:#78350f; padding-left:1.1rem; line-height:1.7; margin:0;">
                <li>First row must be the header row</li>
                <li>Duplicate emails are automatically skipped</li>
                <li>Column order doesn't matter</li>
                <li>UTF-8 encoding recommended</li>
                <li>Save Excel files as "CSV UTF-8"</li>
            </ul>
        </div>

        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:1rem; margin-top:1rem;">
            <div style="font-weight:700; font-size:0.8rem; color:#166534; margin-bottom:0.4rem;">Example header row:</div>
            <code style="font-size:0.75rem; color:#15803d; display:block; line-height:1.6;">
                First Name,Last Name,Title,<br>Company,Email,Seniority,Country
            </code>
        </div>
    </div>
</div>

<script>
function onFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    const info = document.getElementById('file-info');
    document.getElementById('file-name').textContent = file.name;
    document.getElementById('file-size').textContent = (file.size / 1024).toFixed(1) + ' KB';
    info.style.display = 'flex';
    const btn = document.getElementById('import-btn');
    btn.disabled = false;
    btn.style.opacity = '1';
    btn.style.cursor = 'pointer';
}
function clearFile() {
    document.getElementById('csv-input').value = '';
    document.getElementById('file-info').style.display = 'none';
    const btn = document.getElementById('import-btn');
    btn.disabled = true;
    btn.style.opacity = '0.5';
    btn.style.cursor = 'not-allowed';
}
// Drag & drop
const drop = document.getElementById('drop-area');
['dragenter','dragover'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.add('drag-over'); }));
['dragleave','drop'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.remove('drag-over'); }));
drop.addEventListener('drop', ev => {
    const file = ev.dataTransfer.files[0];
    if (file) {
        const input = document.getElementById('csv-input');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        onFileSelect(input);
    }
});
</script>
@endsection
