@extends('layouts.admin-sidebar')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('extra-styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    .stat-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 1.25rem 1.5rem; display: flex; align-items: flex-start; gap: 1rem;
    }
    .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .stat-icon svg { width:22px; height:22px; }
    .stat-value { font-size:1.6rem; font-weight:700; color:#0f172a; line-height:1; }
    .stat-label { font-size:0.8rem; color:#64748b; margin-top:4px; }
    .stat-sub   { font-size:0.75rem; font-weight:600; margin-top:6px; color:#64748b; }

    .section-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .section-header { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #f1f5f9; }
    .section-title  { font-size:0.95rem; font-weight:700; color:#0f172a; }
    .section-action { font-size:0.8rem; color:#2563eb; text-decoration:none; font-weight:500; }
    .section-action:hover { text-decoration:underline; }

    .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:0.72rem; font-weight:600; }
    .badge::before { content:''; width:6px; height:6px; border-radius:50%; }
    .badge-confirmed { background:#dcfce7; color:#16a34a; }
    .badge-confirmed::before { background:#16a34a; }
    .badge-planning  { background:#dbeafe; color:#1d4ed8; }
    .badge-planning::before  { background:#2563eb; }
    .badge-draft     { background:#f1f5f9; color:#475569; }
    .badge-draft::before     { background:#94a3b8; }

    .event-row { display:flex; align-items:center; gap:12px; padding:0.85rem 1.25rem; border-top:1px solid #f1f5f9; }
    .event-row:first-child { border-top:none; }
    .event-thumb { width:42px; height:42px; border-radius:8px; object-fit:cover; flex-shrink:0; }
    .event-thumb-placeholder { width:42px; height:42px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#cbd5e1; flex-shrink:0; }

    .quick-actions { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; padding:1.25rem; }
    .quick-btn {
        display:flex; align-items:center; gap:8px;
        padding:0.7rem 1rem; border-radius:8px;
        border:1.5px solid #e2e8f0; background:#f8fafc;
        font-size:0.85rem; font-weight:600; color:#374151;
        text-decoration:none; transition:all 140ms;
    }
    .quick-btn:hover { background:#eff6ff; border-color:#bfdbfe; color:#2563eb; }
    .quick-btn svg { width:16px; height:16px; opacity:0.7; }

    .empty-events { text-align:center; padding:2.5rem 1rem; color:#94a3b8; font-size:0.875rem; }

    /* Weather widget */
    .weather-widget {
        display:flex; align-items:center; gap:0.75rem;
        background:#fff; border:1px solid #e2e8f0; border-radius:12px;
        padding:0.75rem 1.1rem; min-width:200px;
    }
    .weather-icon { font-size:2rem; line-height:1; }
    .weather-temp { font-size:1.5rem; font-weight:700; color:#0f172a; line-height:1; }
    .weather-city { font-size:0.75rem; font-weight:600; color:#374151; margin-top:1px; }
    .weather-desc { font-size:0.7rem; color:#94a3b8; }
    .weather-meta { display:flex; gap:0.6rem; margin-top:3px; }
    .weather-meta-item { font-size:0.68rem; color:#94a3b8; display:flex; align-items:center; gap:2px; }
    .weather-loading { font-size:0.78rem; color:#94a3b8; display:flex; align-items:center; gap:6px; }
    .weather-spinner { width:14px; height:14px; border:2px solid #e2e8f0; border-top-color:#2563eb; border-radius:50%; animation:wspin 0.8s linear infinite; }
    @keyframes wspin { to { transform:rotate(360deg); } }
</style>
@endsection

@section('content')

{{-- Welcome --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; flex-wrap:wrap; gap:1rem;">
    <div>
        @php
            $hour = now()->hour;
            if ($hour >= 5 && $hour < 12) {
                $greeting = 'Good morning';
                $timeIcon = '🌅';
                $iconBg   = '#fefce8';
                $iconClr  = '#ca8a04';
            } elseif ($hour >= 12 && $hour < 17) {
                $greeting = 'Good afternoon';
                $timeIcon = '☀️';
                $iconBg   = '#fff7ed';
                $iconClr  = '#ea580c';
            } elseif ($hour >= 17 && $hour < 20) {
                $greeting = 'Good evening';
                $timeIcon = '🌇';
                $iconBg   = '#fdf4ff';
                $iconClr  = '#9333ea';
            } else {
                $greeting = 'Good night';
                $timeIcon = '🌙';
                $iconBg   = '#eff6ff';
                $iconClr  = '#2563eb';
            }
        @endphp
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <div style="width:46px; height:46px; border-radius:12px; background:{{ $iconBg }}; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0;">{{ $timeIcon }}</div>
            <div>
                <h1 style="font-size:1.4rem; font-weight:700; color:#0f172a;">{{ $greeting }}, {{ Auth::user()->name ?? 'Admin' }}!</h1>
                <p style="color:#64748b; font-size:0.82rem; margin-top:1px;">{{ now()->format('l, F j, Y') }}</p>
            </div>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem;">
        {{-- Weather Widget --}}
        <div class="weather-widget" id="weather-widget">
            <div class="weather-loading">
                <div class="weather-spinner"></div>
                Fetching weather…
            </div>
        </div>

    </div>
</div>

{{-- Events Stats --}}
<div style="font-size:0.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.6rem;">Events</div>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#fefce8;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#ca8a04" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $totalEvents }}</div>
            <div class="stat-label">Total Events</div>
            <div class="stat-sub">All time</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $confirmedEvents }}</div>
            <div class="stat-label">Confirmed Events</div>
            <div class="stat-sub">Ready to go</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $upcomingEvents }}</div>
            <div class="stat-label">Upcoming Events</div>
            <div class="stat-sub">From today onwards</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fdf4ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#9333ea" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $totalSpeakers }}</div>
            <div class="stat-label">Total Speakers</div>
            <div class="stat-sub">All registered</div>
        </div>
    </div>
</div>

{{-- Speaker breakdown cards --}}
<div style="font-size:0.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.6rem;">Speakers</div>
<div class="stats-grid" style="margin-bottom:1.75rem;">

    <div class="stat-card">
        <div class="stat-icon" style="background:#fdf4ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#9333ea" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $totalSpeakers }}</div>
            <div class="stat-label">Total Speakers</div>
            <div class="stat-sub">All registered</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $headSpeakers }}</div>
            <div class="stat-label">Head Level</div>
            <div class="stat-sub">Head / Director</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#ca8a04" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $csuiteSpeakers }}</div>
            <div class="stat-label">C-Suite</div>
            <div class="stat-sub">CEO, CTO, CDO…</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $managerSpeakers }}</div>
            <div class="stat-label">Manager Level</div>
            <div class="stat-sub">Managers</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fff7ed;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#ea580c" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="stat-value">{{ $countriesCount }}</div>
            <div class="stat-label">Countries</div>
            <div class="stat-sub">Represented</div>
        </div>
    </div>
</div>

{{-- Main grid --}}
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.75rem;">

    {{-- Recent Events (live) --}}
    <div class="section-card">
        <div class="section-header">
            <span class="section-title">Recent Events</span>
            <a href="{{ route('admin.events.index') }}" class="section-action">View all →</a>
        </div>

        @if($recentEvents->isEmpty())
            <div class="empty-events">No events yet. <a href="{{ route('admin.events.create') }}" style="color:#2563eb;">Add one →</a></div>
        @else
            @foreach($recentEvents as $event)
            <div class="event-row">
                @if($event->image)
                    <img src="{{ Storage::url($event->image) }}" alt="{{ $event->name }}" class="event-thumb">
                @else
                    <div class="event-thumb-placeholder">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; font-size:0.875rem; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $event->name }}</div>
                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;">
                        {{ $event->date->format('M d, Y') }} · {{ $event->location }}
                    </div>
                </div>
                <span class="badge badge-{{ $event->status }}">{{ ucfirst($event->status) }}</span>
            </div>
            @endforeach
        @endif
    </div>

    {{-- Recent Speakers (live) --}}
    <div class="section-card">
        <div class="section-header">
            <span class="section-title">Recent Speakers</span>
            <a href="{{ route('admin.speakers.index') }}" class="section-action">View all →</a>
        </div>
        @php $colors = ['#2563eb','#16a34a','#9333ea','#ef4444','#ca8a04']; @endphp
        @if($recentSpeakers->isEmpty())
            <div class="empty-events">No speakers yet. <a href="{{ route('admin.speakers.create') }}" style="color:#2563eb;">Add one →</a></div>
        @else
            @foreach($recentSpeakers as $i => $speaker)
            <div style="display:flex; align-items:center; gap:10px; padding:0.75rem 1.25rem; border-top:1px solid #f1f5f9;">
                @if($speaker->photo)
                    <img src="{{ Storage::url($speaker->photo) }}" alt="{{ $speaker->full_name }}"
                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                @else
                    @php $c = $colors[$i % count($colors)]; @endphp
                    <div style="width:36px;height:36px;border-radius:50%;background:{{ $c }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.82rem;flex-shrink:0;">
                        {{ strtoupper(substr($speaker->first_name,0,1)) }}
                    </div>
                @endif
                <div style="flex:1; min-width:0;">
                    <div style="font-size:0.875rem;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $speaker->full_name }}</div>
                    <div style="font-size:0.75rem;color:#94a3b8;">{{ $speaker->title ?: $speaker->company ?: '—' }}</div>
                </div>
                @if($speaker->seniority)
                    <span style="font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:999px;background:#f1f5f9;color:#475569;white-space:nowrap;">{{ $speaker->seniority }}</span>
                @endif
            </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Quick Actions --}}
<div class="section-card">
    <div class="section-header">
        <span class="section-title">Quick Actions</span>
    </div>
    <div class="quick-actions">
        <a href="{{ route('admin.events.create') }}" class="quick-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Event
        </a>
        <a href="{{ route('admin.events.index') }}" class="quick-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            View Events
        </a>
        <a href="{{ route('admin.speakers.index') }}" class="quick-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
            </svg>
            View Speakers
        </a>
        <a href="{{ route('admin.settings') }}" class="quick-btn">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
        </a>
    </div>
</div>

<script>
(function() {
    const widget = document.getElementById('weather-widget');

    const codeToEmoji = code => {
        code = parseInt(code);
        if (code === 113) return '☀️';
        if (code === 116) return '⛅';
        if ([119, 122].includes(code)) return '☁️';
        if ([143, 248, 260].includes(code)) return '🌫️';
        if ([200, 386, 389, 392, 395].includes(code)) return '⛈️';
        if ([227, 230, 338].includes(code)) return '❄️';
        if ([263, 266, 293, 296, 176].includes(code)) return '🌦️';
        if ([299, 302, 305, 308, 353, 356, 359].includes(code)) return '🌧️';
        if ([179, 323, 326, 329, 332, 335, 362, 365, 374, 377].includes(code)) return '🌨️';
        if ([182, 185, 281, 284, 311, 314, 317, 320, 350].includes(code)) return '🌧️';
        return '🌡️';
    };

    function render(data) {
        const c       = data.current_condition[0];
        const area    = data.nearest_area[0];
        const city    = area.areaName[0].value;
        const country = area.country[0].value;
        const temp    = c.temp_C;
        const feels   = c.FeelsLikeC;
        const desc    = c.weatherDesc[0].value;
        const humidity= c.humidity;
        const wind    = c.windspeedKmph;
        const emoji   = codeToEmoji(c.weatherCode);

        widget.innerHTML = `
            <div class="weather-icon">${emoji}</div>
            <div>
                <div style="display:flex;align-items:baseline;gap:4px;">
                    <span class="weather-temp">${temp}°C</span>
                    <span style="font-size:0.72rem;color:#94a3b8;">feels ${feels}°</span>
                </div>
                <div class="weather-city">${city}, ${country}</div>
                <div class="weather-desc">${desc}</div>
                <div class="weather-meta">
                    <span class="weather-meta-item">💧 ${humidity}%</span>
                    <span class="weather-meta-item">💨 ${wind} km/h</span>
                </div>
            </div>
        `;
    }

    function showError(msg) {
        widget.innerHTML = `<div style="font-size:0.75rem;color:#94a3b8;display:flex;align-items:center;gap:5px;">
            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            ${msg}
        </div>`;
    }

    function fetchByIP() {
        fetch('https://wttr.in/?format=j1', { signal: AbortSignal.timeout(8000) })
            .then(r => r.json()).then(render).catch(() => showError('Weather unavailable'));
    }

    function fetchWeather(lat, lon) {
        fetch(`https://wttr.in/${lat},${lon}?format=j1`, { signal: AbortSignal.timeout(8000) })
            .then(r => r.json()).then(render).catch(() => showError('Weather unavailable'));
    }

    if (!navigator.geolocation) { fetchByIP(); return; }

    navigator.geolocation.getCurrentPosition(
        pos => fetchWeather(pos.coords.latitude.toFixed(4), pos.coords.longitude.toFixed(4)),
        ()  => fetchByIP(),
        { timeout: 5000 }
    );
})();
</script>

@endsection
