@extends('layouts.admin-sidebar')
@section('title', 'Add Speaker')
@section('page-title', 'Add Speaker')

@section('extra-styles')
<style>
    .form-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:2rem; max-width:760px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }
    .form-group { display:flex; flex-direction:column; gap:0.4rem; }
    .form-group.full { grid-column:1/-1; }
    .form-label { font-size:0.875rem; font-weight:600; color:#374151; }
    .form-input, .form-select {
        width:100%; padding:0.7rem 0.9rem;
        border:1.5px solid #d1d5db; border-radius:8px;
        font-size:0.875rem; outline:none; color:#0f172a;
        font-family:inherit; background:#fff;
        transition:border-color 140ms, box-shadow 140ms;
    }
    .form-input:focus, .form-select:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1); }
    .form-input.is-error { border-color:#ef4444; }
    .error-msg { font-size:0.78rem; color:#dc2626; }

    .upload-area {
        border:2px dashed #d1d5db; border-radius:10px; padding:1.5rem;
        text-align:center; cursor:pointer; transition:all 140ms;
        background:#fafafa; position:relative;
    }
    .upload-area:hover { border-color:#2563eb; background:#eff6ff; }
    .upload-area input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .upload-text { font-size:0.875rem; color:#64748b; }
    .upload-text strong { color:#2563eb; }
    .upload-hint { font-size:0.75rem; color:#94a3b8; margin-top:4px; }
    #preview-wrap { display:none; margin-top:0.75rem; text-align:center; }
    #img-preview { width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #e2e8f0; }

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

<div class="form-card">
    <h2 style="font-size:1.15rem; font-weight:700; color:#0f172a; margin-bottom:1.5rem;">Add New Speaker</h2>

    <form action="{{ route('admin.speakers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">

            <div class="form-group">
                <label class="form-label">First Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name') }}"
                       class="form-input {{ $errors->has('first_name') ? 'is-error' : '' }}"
                       placeholder="e.g. James">
                @error('first_name') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Last Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="last_name" value="{{ old('last_name') }}"
                       class="form-input {{ $errors->has('last_name') ? 'is-error' : '' }}"
                       placeholder="e.g. Lee">
                @error('last_name') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" value="{{ old('title') }}"
                       class="form-input" placeholder="e.g. Head of AI Research">
                @error('title') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Company</label>
                <input type="text" name="company" value="{{ old('company') }}"
                       class="form-input" placeholder="e.g. OpenAI">
                @error('company') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Email <span style="color:#ef4444;">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="form-input {{ $errors->has('email') ? 'is-error' : '' }}"
                       placeholder="james@example.com">
                @error('email') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Seniority</label>
                <input type="text" name="seniority" value="{{ old('seniority') }}"
                       class="form-input" placeholder="e.g. VP, Director, Manager, C-Level">
                @error('seniority') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Country</label>
                <input type="text" name="country" value="{{ old('country') }}"
                       class="form-input" placeholder="e.g. United States">
                @error('country') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Assign to Event</label>
                <select name="event_id" class="form-select">
                    <option value="">— No event —</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->name }}
                        </option>
                    @endforeach
                </select>
                @error('event_id') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group full">
                <label class="form-label">Photo</label>
                <div class="upload-area">
                    <input type="file" name="photo" accept="image/*" id="photo-input" onchange="previewPhoto(this)">
                    <div class="upload-text"><strong>Click to upload photo</strong></div>
                    <div class="upload-hint">PNG, JPG up to 2MB</div>
                </div>
                <div id="preview-wrap">
                    <img id="img-preview" src="" alt="Preview">
                    <div style="margin-top:6px;">
                        <button type="button" onclick="removePhoto()" style="font-size:0.78rem;color:#dc2626;background:none;border:none;cursor:pointer;">✕ Remove</button>
                    </div>
                </div>
                @error('photo') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

        </div>
        <div class="form-footer">
            <button type="submit" class="btn">Add Speaker</button>
            <a href="{{ route('admin.speakers.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('img-preview').src = e.target.result;
            document.getElementById('preview-wrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function removePhoto() {
    document.getElementById('photo-input').value = '';
    document.getElementById('preview-wrap').style.display = 'none';
}
</script>
@endsection
