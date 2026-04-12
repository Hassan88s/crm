@extends('layouts.admin-sidebar')
@section('title', 'Edit Event')
@section('page-title', 'Edit Event')

@section('extra-styles')
<style>
    .form-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:2rem; max-width:720px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }
    .form-group { display:flex; flex-direction:column; gap:0.4rem; }
    .form-group.full { grid-column:1/-1; }
    .form-label { font-size:0.875rem; font-weight:600; color:#374151; }
    .form-input, .form-select, .form-textarea {
        width:100%; padding:0.7rem 0.9rem;
        border:1.5px solid #d1d5db; border-radius:8px;
        font-size:0.875rem; outline:none; color:#0f172a;
        font-family:inherit; background:#fff;
        transition:border-color 140ms, box-shadow 140ms;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1);
    }
    .form-input.is-error, .form-select.is-error, .form-textarea.is-error { border-color:#ef4444; }
    .form-textarea { resize:vertical; min-height:100px; }
    .error-msg { font-size:0.78rem; color:#dc2626; margin-top:2px; }

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

    .current-img { width:100%; max-height:200px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:0.75rem; }
    #new-preview { display:none; width:100%; max-height:200px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; margin-top:0.75rem; }
    .form-footer { display:flex; align-items:center; gap:0.75rem; margin-top:1.75rem; padding-top:1.5rem; border-top:1px solid #f1f5f9; }
</style>
@endsection

@section('content')

<div style="margin-bottom:1.25rem;">
    <a href="{{ route('admin.events.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.875rem;text-decoration:none;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to Events
    </a>
</div>

<div class="form-card">
    <h2 style="font-size:1.15rem;font-weight:700;color:#0f172a;margin-bottom:1.5rem;">Edit Event</h2>

    <form action="{{ route('admin.events.update', $event) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-grid">

            <div class="form-group full">
                <label class="form-label">Event Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name', $event->name) }}"
                       class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                       placeholder="e.g. Tech Summit 2026">
                @error('name') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group full">
                <label class="form-label">Location <span style="color:#ef4444;">*</span></label>
                <input type="text" name="location" value="{{ old('location', $event->location) }}"
                       class="form-input {{ $errors->has('location') ? 'is-error' : '' }}"
                       placeholder="e.g. San Francisco, CA">
                @error('location') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Date <span style="color:#ef4444;">*</span></label>
                <input type="date" name="date" value="{{ old('date', $event->date->format('Y-m-d')) }}"
                       class="form-input {{ $errors->has('date') ? 'is-error' : '' }}">
                @error('date') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Time <span style="color:#ef4444;">*</span></label>
                <input type="time" name="time" value="{{ old('time', \Carbon\Carbon::parse($event->time)->format('H:i')) }}"
                       class="form-input {{ $errors->has('time') ? 'is-error' : '' }}">
                @error('time') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444;">*</span></label>
                <select name="status" class="form-select {{ $errors->has('status') ? 'is-error' : '' }}">
                    <option value="draft"     {{ old('status', $event->status) === 'draft'     ? 'selected' : '' }}>Draft</option>
                    <option value="planning"  {{ old('status', $event->status) === 'planning'  ? 'selected' : '' }}>Planning</option>
                    <option value="confirmed" {{ old('status', $event->status) === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                </select>
                @error('status') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group full">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea {{ $errors->has('description') ? 'is-error' : '' }}"
                          placeholder="Brief description of the event...">{{ old('description', $event->description) }}</textarea>
                @error('description') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group full">
                <label class="form-label">Event Image</label>

                @if($event->image)
                    <div style="margin-bottom:0.75rem;">
                        <p style="font-size:0.78rem;color:#64748b;margin-bottom:6px;">Current image:</p>
                        <img src="{{ Storage::url($event->image) }}" alt="Current image" class="current-img" id="current-img">
                    </div>
                @endif

                <div class="upload-area">
                    <input type="file" name="image" accept="image/*" id="img-input" onchange="previewNew(this)">
                    <div class="upload-text">
                        <strong>{{ $event->image ? 'Upload new image to replace' : 'Click to upload' }}</strong>
                    </div>
                    <div class="upload-hint">PNG, JPG, WEBP up to 2MB — leave empty to keep current</div>
                </div>
                <img id="new-preview" src="" alt="New preview">
                @error('image') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

        </div>

        <div class="form-footer">
            <button type="submit" class="btn">Save Changes</button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
function previewNew(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('new-preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
            const cur = document.getElementById('current-img');
            if (cur) cur.style.opacity = '0.4';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
