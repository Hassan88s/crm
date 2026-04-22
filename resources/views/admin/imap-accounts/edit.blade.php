@extends('layouts.admin-sidebar')

@section('title', 'Edit IMAP Account')
@section('page-title', 'Edit IMAP Account')

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
    <a href="{{ route('admin.imap-accounts.index') }}" style="display:inline-flex;align-items:center;gap:6px;color:#64748b;font-size:0.875rem;text-decoration:none;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to IMAP Accounts
    </a>
</div>

<div class="form-card">
    <h2 style="font-size:1.15rem;font-weight:700;color:#0f172a;margin-bottom:1.5rem;">Edit IMAP Account</h2>

    <form action="{{ route('admin.imap-accounts.update', $account) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-group full">
                <label class="form-label">Account Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name', $account->name) }}"
                       class="form-input {{ $errors->has('name') ? 'is-error' : '' }}">
                @error('name') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">IMAP Host <span style="color:#ef4444;">*</span></label>
                <input type="text" name="host" value="{{ old('host', $account->host) }}"
                       class="form-input {{ $errors->has('host') ? 'is-error' : '' }}">
                @error('host') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Port <span style="color:#ef4444;">*</span></label>
                <input type="number" name="port" value="{{ old('port', $account->port) }}"
                       class="form-input {{ $errors->has('port') ? 'is-error' : '' }}">
                @error('port') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Username / Email <span style="color:#ef4444;">*</span></label>
                <input type="text" name="username" value="{{ old('username', $account->username) }}"
                       class="form-input {{ $errors->has('username') ? 'is-error' : '' }}">
                @error('username') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Password <span style="font-weight:400;color:#94a3b8;">(leave blank to keep current)</span></label>
                <input type="password" name="password" value=""
                       class="form-input {{ $errors->has('password') ? 'is-error' : '' }}"
                       placeholder="••••••••">
                @error('password') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Encryption <span style="color:#ef4444;">*</span></label>
                <select name="encryption" class="form-select">
                    <option value="ssl" {{ old('encryption', $account->encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="tls" {{ old('encryption', $account->encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="starttls" {{ old('encryption', $account->encryption) === 'starttls' ? 'selected' : '' }}>STARTTLS</option>
                    <option value="none" {{ old('encryption', $account->encryption) === 'none' ? 'selected' : '' }}>None</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Badge Color</label>
                <input type="color" name="color" value="{{ old('color', $account->color) }}" class="form-input" style="height:42px; padding:4px;">
            </div>

            <div class="form-group full">
                <label class="toggle-wrap">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }}>
                    <span class="form-label" style="margin-bottom:0;">Active (include in unified inbox)</span>
                </label>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn">Update Account</button>
            <a href="{{ route('admin.imap-accounts.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

@endsection
