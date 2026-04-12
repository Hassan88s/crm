@extends('layouts.admin-sidebar')
@section('title', 'System Prerequisites')
@section('page-title', 'System Prerequisites')

@section('extra-styles')
<style>
    .req-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; }
    .summary-chips { display:flex; gap:0.75rem; flex-wrap:wrap; }
    .summary-chip {
        display:flex; align-items:center; gap:6px;
        padding:0.5rem 1rem; border-radius:8px; font-size:0.84rem; font-weight:600;
    }
    .chip-pass { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
    .chip-fail { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
    .chip-warn { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }

    .req-group { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:1.25rem; }
    .req-group-head {
        padding:0.85rem 1.25rem; background:#f8fafc; border-bottom:1px solid #f1f5f9;
        display:flex; align-items:center; justify-content:space-between;
    }
    .req-group-title { font-size:0.9rem; font-weight:700; color:#0f172a; }
    .req-group-badge { font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:20px; }
    .badge-all-pass { background:#f0fdf4; color:#15803d; }
    .badge-has-fail { background:#fef2f2; color:#dc2626; }
    .badge-has-warn { background:#fffbeb; color:#92400e; }

    .req-table { width:100%; border-collapse:collapse; }
    .req-table tr { border-bottom:1px solid #f1f5f9; }
    .req-table tr:last-child { border-bottom:none; }
    .req-table td { padding:0.75rem 1.25rem; font-size:0.84rem; vertical-align:middle; }

    .status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
    .dot-pass { background:#22c55e; }
    .dot-fail { background:#ef4444; }
    .dot-warn { background:#f59e0b; }

    .status-icon { width:20px; height:20px; display:flex; align-items:center; justify-content:center; border-radius:50%; flex-shrink:0; }
    .icon-pass { background:#f0fdf4; color:#16a34a; }
    .icon-fail { background:#fef2f2; color:#dc2626; }
    .icon-warn { background:#fffbeb; color:#d97706; }

    .req-label { font-weight:600; color:#374151; display:flex; align-items:center; gap:8px; }
    .req-note  { font-size:0.75rem; color:#94a3b8; margin-top:2px; }
    .req-value { font-family:monospace; font-size:0.82rem; }
    .val-pass { color:#15803d; }
    .val-fail { color:#dc2626; font-weight:600; }
    .val-warn { color:#92400e; }

    .refresh-btn { display:inline-flex; align-items:center; gap:6px; padding:0.5rem 1rem; border-radius:8px; font-size:0.82rem; font-weight:600; background:#f8fafc; border:1px solid #e2e8f0; color:#374151; cursor:pointer; text-decoration:none; transition:all 120ms; }
    .refresh-btn:hover { background:#eff6ff; border-color:#93c5fd; color:#1d4ed8; }

    .all-good-banner { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:1rem 1.25rem; display:flex; align-items:center; gap:10px; margin-bottom:1.25rem; color:#15803d; font-size:0.875rem; font-weight:600; }
    .issues-banner  { background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:1rem 1.25rem; display:flex; align-items:center; gap:10px; margin-bottom:1.25rem; color:#dc2626; font-size:0.875rem; font-weight:600; }
</style>
@endsection

@section('content')

<div class="req-header">
    <div>
        <h1 style="font-size:1.3rem; font-weight:700; color:#0f172a;">System Prerequisites</h1>
        <p style="color:#64748b; font-size:0.875rem; margin-top:3px;">Check that all required PHP extensions and settings are in place. Run this after migrating to a new server.</p>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <span style="font-size:0.75rem; color:#94a3b8;">PHP {{ PHP_VERSION }} · Laravel {{ app()->version() }}</span>
        <a href="{{ route('admin.requirements') }}" class="refresh-btn">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Refresh
        </a>
    </div>
</div>

{{-- Summary chips --}}
<div class="summary-chips" style="margin-bottom:1.25rem;">
    <div class="summary-chip chip-pass">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        {{ $totalPass }} Passed
    </div>
    @if($totalFail > 0)
    <div class="summary-chip chip-fail">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ $totalFail }} Failed
    </div>
    @endif
    @if($totalWarn > 0)
    <div class="summary-chip chip-warn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ $totalWarn }} Warnings
    </div>
    @endif
</div>

{{-- Overall status banner --}}
@if($totalFail === 0)
    <div class="all-good-banner">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        All critical requirements are met. This server is ready to run PulseCore.
    </div>
@else
    <div class="issues-banner">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ $totalFail }} critical requirement(s) not met. Fix the issues below before deploying.
    </div>
@endif

{{-- Groups --}}
@foreach($grouped as $group => $items)
@php
    $groupFails = count(array_filter($items, fn($c) => !$c['pass'] && empty($c['warn'] ?? null)));
    $groupWarns = count(array_filter($items, fn($c) => !empty($c['warn'] ?? null)));
@endphp
<div class="req-group">
    <div class="req-group-head">
        <span class="req-group-title">
            @if($group === 'PHP')
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            @elseif($group === 'PHP Extensions')
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            @elseif($group === 'Database')
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
            @elseif($group === 'File Permissions')
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            @elseif($group === 'Composer Packages')
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            @else
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            @endif
            {{ $group }}
        </span>
        @if($groupFails > 0)
            <span class="req-group-badge badge-has-fail">{{ $groupFails }} failed</span>
        @elseif($groupWarns > 0)
            <span class="req-group-badge badge-has-warn">{{ $groupWarns }} warnings</span>
        @else
            <span class="req-group-badge badge-all-pass">All good</span>
        @endif
    </div>
    <table class="req-table">
        @foreach($items as $item)
        @php
            $isWarn = !empty($item['warn'] ?? null);
            $isFail = !$item['pass'] && !$isWarn;
            $isPass = $item['pass'] && !$isWarn;
        @endphp
        <tr>
            <td style="width:36px; padding-right:0;">
                <div class="status-icon {{ $isFail ? 'icon-fail' : ($isWarn ? 'icon-warn' : 'icon-pass') }}">
                    @if($isFail)
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    @elseif($isWarn)
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>
                    @else
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </div>
            </td>
            <td>
                <div class="req-label">{{ $item['label'] }}</div>
                @if(!empty($item['note']))
                    <div class="req-note">{{ $item['note'] }}</div>
                @endif
            </td>
            <td style="text-align:right;">
                <span class="req-value {{ $isFail ? 'val-fail' : ($isWarn ? 'val-warn' : 'val-pass') }}">
                    {{ $item['value'] }}
                </span>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endforeach

{{-- Migration checklist --}}
<div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:1.25rem;">
    <div class="req-group-head">
        <span class="req-group-title">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:5px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Migration Checklist
        </span>
        <span class="req-group-badge" style="background:#f0f9ff; color:#0284c7;">Reference</span>
    </div>
    <div style="padding:1.25rem; font-size:0.83rem; color:#374151; line-height:1.8;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem 2rem;">
            <div>
                <div style="font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Server Setup</div>
                <ul style="padding-left:1rem; color:#64748b;">
                    <li>PHP >= 8.2 with all extensions above</li>
                    <li>MySQL 5.7+ or MariaDB 10.3+</li>
                    <li>Composer installed globally</li>
                    <li>Web server: Apache/Nginx with mod_rewrite</li>
                    <li>Set document root to <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">public/</code></li>
                </ul>
            </div>
            <div>
                <div style="font-weight:700; color:#0f172a; margin-bottom:0.5rem;">After Upload</div>
                <ul style="padding-left:1rem; color:#64748b;">
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">cp .env.example .env</code> and fill values</li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">composer install --no-dev</code></li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">php artisan key:generate</code></li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">php artisan migrate</code></li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">php artisan storage:link</code></li>
                    <li>Set <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">chmod -R 775 storage bootstrap/cache</code></li>
                </ul>
            </div>
            <div>
                <div style="font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Enable in php.ini</div>
                <ul style="padding-left:1rem; color:#64748b;">
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">extension=imap</code> — for inbox</li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">extension=curl</code> — for scraping</li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">extension=pdo_mysql</code> — for database</li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">extension=mbstring</code> — text processing</li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">extension=openssl</code> — HTTPS & mail</li>
                </ul>
            </div>
            <div>
                <div style="font-weight:700; color:#0f172a; margin-bottom:0.5rem;">Production Optimise</div>
                <ul style="padding-left:1rem; color:#64748b;">
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">php artisan config:cache</code></li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">php artisan route:cache</code></li>
                    <li><code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">php artisan view:cache</code></li>
                    <li>Set <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">APP_ENV=production</code></li>
                    <li>Set <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;">APP_DEBUG=false</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
