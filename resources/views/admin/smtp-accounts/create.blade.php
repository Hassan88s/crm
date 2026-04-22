@extends('layouts.admin-sidebar')

@section('title', 'Add SMTP Account')
@section('page-title', 'Add SMTP Account')

@section('extra-styles')
<style>
    .form-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:2rem; max-width:720px; }
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
    .form-input:focus, .form-select:focus {
        border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,0.1);
    }
    .form-input.is-error { border-color:#ef4444; }
    .error-msg { font-size:0.78rem; color:#dc2626; margin-top:2px; }
    .form-footer { display:flex; align-items:center; gap:0.75rem; margin-top:1.75rem; padding-top:1.5rem; border-top:1px solid #f1f5f9; }
    .toggle-wrap { display:flex; align-items:center; gap:10px; }
    .toggle-wrap input { width:18px; height:18px; }
</style>
@endsection

@section('content')

<div style="margin-bottom:1.25rem;">
    <a href="{{ route('admin.smtp-accounts.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.875rem;text-decoration:none;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to SMTP Accounts
    </a>
</div>

<div class="form-card">
    <h2 style="font-size:1.15rem;font-weight:700;color:#0f172a;margin-bottom:1.5rem;">Add New SMTP Account</h2>

    <form action="{{ route('admin.smtp-accounts.store') }}" method="POST">
        @csrf

        <div class="form-grid">
            <div class="form-group full">
                <label class="form-label">Account Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="form-input {{ $errors->has('name') ? 'is-error' : '' }}"
                       placeholder="e.g. Hostinger 1, Gmail Backup">
                @error('name') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">SMTP Host <span style="color:#ef4444;">*</span></label>
                <input type="text" name="host" value="{{ old('host') }}"
                       class="form-input {{ $errors->has('host') ? 'is-error' : '' }}"
                       placeholder="smtp.hostinger.com">
                @error('host') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Port <span style="color:#ef4444;">*</span></label>
                <input type="number" name="port" value="{{ old('port', 587) }}"
                       class="form-input {{ $errors->has('port') ? 'is-error' : '' }}"
                       placeholder="587">
                @error('port') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Username / Email <span style="color:#ef4444;">*</span></label>
                <input type="text" name="username" value="{{ old('username') }}"
                       class="form-input {{ $errors->has('username') ? 'is-error' : '' }}"
                       placeholder="user@domain.com">
                @error('username') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Password <span style="color:#ef4444;">*</span></label>
                <input type="password" name="password" value="{{ old('password') }}"
                       class="form-input {{ $errors->has('password') ? 'is-error' : '' }}">
                @error('password') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Encryption <span style="color:#ef4444;">*</span></label>
                <select name="encryption" class="form-select">
                    <option value="tls" {{ old('encryption','tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ old('encryption') === 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="none" {{ old('encryption') === 'none' ? 'selected' : '' }}>None</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">From Email <span style="color:#ef4444;">*</span></label>
                <input type="email" name="from_address" value="{{ old('from_address') }}"
                       class="form-input {{ $errors->has('from_address') ? 'is-error' : '' }}"
                       placeholder="sender@domain.com">
                @error('from_address') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">From Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="from_name" value="{{ old('from_name') }}"
                       class="form-input {{ $errors->has('from_name') ? 'is-error' : '' }}"
                       placeholder="Your Name">
                @error('from_name') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group full">
                <label class="toggle-wrap">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <span class="form-label" style="margin-bottom:0;">Active (include in rotation)</span>
                </label>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn">Add Account</button>
            <a href="{{ route('admin.smtp-accounts.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

@endsection
