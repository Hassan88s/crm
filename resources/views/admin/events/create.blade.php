@extends('layouts.admin-sidebar')
@section('title', 'Add Event')
@section('page-title', 'Add Event')

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

    /* Image upload */
    .upload-area {
        border:2px dashed #d1d5db; border-radius:10px; padding:2rem;
        text-align:center; cursor:pointer; transition:all 140ms;
        background:#fafafa; position:relative;
    }
    .upload-area:hover { border-color:#2563eb; background:#eff6ff; }
    .upload-area input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .upload-icon { color:#94a3b8; margin-bottom:0.5rem; }
    .upload-icon svg { width:36px; height:36px; }
    .upload-text { font-size:0.875rem; color:#64748b; }
    .upload-text strong { color:#2563eb; }
    .upload-hint { font-size:0.75rem; color:#94a3b8; margin-top:4px; }
    #preview-wrap { display:none; margin-top:1rem; }
    #img-preview { width:100%; max-height:200px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0; }

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
    <h2 style="font-size:1.15rem;font-weight:700;color:#0f172a;margin-bottom:1.5rem;">Create New Event</h2>

    <form action="{{ route('admin.events.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-grid">

            {{-- Event Name --}}
            <div class="form-group full">
                <label class="form-label">Event Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                       placeholder="e.g. Tech Summit 2026">
                @error('name') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Location --}}
            <div class="form-group full">
                <label class="form-label">Location <span style="color:#ef4444;">*</span></label>
                <input type="text" name="location" value="{{ old('location') }}"
                       class="form-input {{ $errors->has('location') ? 'is-error' : '' }}"
                       placeholder="e.g. San Francisco, CA">
                @error('location') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Start Date --}}
            <div class="form-group">
                <label class="form-label">Start Date <span style="color:#ef4444;">*</span></label>
                <input type="date" name="date" value="{{ old('date') }}"
                       class="form-input {{ $errors->has('date') ? 'is-error' : '' }}">
                @error('date') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- End Date --}}
            <div class="form-group">
                <label class="form-label">End Date <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <input type="date" name="end_date" value="{{ old('end_date') }}"
                       class="form-input {{ $errors->has('end_date') ? 'is-error' : '' }}">
                @error('end_date') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Time --}}
            <div class="form-group">
                <label class="form-label">Time <span style="color:#ef4444;">*</span></label>
                <input type="time" name="time" value="{{ old('time') }}"
                       class="form-input {{ $errors->has('time') ? 'is-error' : '' }}">
                @error('time') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Status --}}
            <div class="form-group">
                <label class="form-label">Status <span style="color:#ef4444;">*</span></label>
                <select name="status" class="form-select {{ $errors->has('status') ? 'is-error' : '' }}">
                    <option value="draft"     {{ old('status','draft') === 'draft'     ? 'selected' : '' }}>Draft</option>
                    <option value="planning"  {{ old('status') === 'planning'          ? 'selected' : '' }}>Planning</option>
                    <option value="confirmed" {{ old('status') === 'confirmed'         ? 'selected' : '' }}>Confirmed</option>
                </select>
                @error('status') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Description --}}
            <div class="form-group full">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea {{ $errors->has('description') ? 'is-error' : '' }}"
                          placeholder="Brief description of the event...">{{ old('description') }}</textarea>
                @error('description') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            {{-- Image Upload --}}
            <div class="form-group full">
                <label class="form-label">Event Image</label>
                <div class="upload-area" id="upload-area">
                    <input type="file" name="image" accept="image/*" id="img-input"
                           onchange="previewImage(this)">
                    <div class="upload-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="upload-text"><strong>Click to upload</strong> or drag and drop</div>
                    <div class="upload-hint">PNG, JPG, WEBP up to 2MB</div>
                </div>
                <div id="preview-wrap">
                    <img id="img-preview" src="" alt="Preview">
                    <button type="button" onclick="removeImage()"
                            style="margin-top:0.5rem;font-size:0.78rem;color:#dc2626;background:none;border:none;cursor:pointer;">
                        ✕ Remove image
                    </button>
                </div>
                @error('image') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

        </div>

        <div class="form-footer">
            <button type="submit" class="btn">Create Event</button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('img-preview').src = e.target.result;
            document.getElementById('preview-wrap').style.display = 'block';
            document.getElementById('upload-area').style.borderColor = '#2563eb';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function removeImage() {
    document.getElementById('img-input').value = '';
    document.getElementById('preview-wrap').style.display = 'none';
    document.getElementById('upload-area').style.borderColor = '#d1d5db';
}
</script>
@endsection
