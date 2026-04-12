@extends('layouts.admin-sidebar')
@section('title', 'Events')
@section('page-title', 'Events')

@section('extra-styles')
<style>
    .events-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.25rem; }
    .event-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .event-banner { height:80px; display:flex; align-items:center; justify-content:center; }
    .event-body { padding:1.1rem 1.25rem; }
    .event-title { font-weight:700; font-size:1rem; color:#0f172a; margin-bottom:4px; }
    .event-meta { font-size:0.8rem; color:#64748b; display:flex; align-items:center; gap:6px; margin-bottom:8px; }
    .badge { display:inline-block; padding:2px 9px; border-radius:999px; font-size:0.72rem; font-weight:600; }
    .badge-green { background:#dcfce7; color:#16a34a; }
    .badge-blue { background:#dbeafe; color:#1d4ed8; }
    .badge-gray { background:#f1f5f9; color:#475569; }
    .event-footer { display:flex; align-items:center; justify-content:space-between; margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid #f1f5f9; }
</style>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Events</h1>
        <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">14 upcoming events</p>
    </div>
    <button class="btn">+ New Event</button>
</div>

<div class="events-grid">
    @foreach([
        ['Tech Summit 2026','Feb 14, 2026','San Francisco, CA','Confirmed','#2563eb','320'],
        ['Sales Workshop','Feb 20, 2026','New York, NY','Planning','#16a34a','80'],
        ['Product Launch','Mar 1, 2026','Online','Confirmed','#9333ea','500'],
        ['Leadership Conference','Mar 15, 2026','Chicago, IL','Planning','#ca8a04','150'],
        ['Developer Meetup','Apr 3, 2026','Austin, TX','Draft','#475569','60'],
        ['Growth Summit','Apr 20, 2026','Miami, FL','Confirmed','#ef4444','250'],
    ] as $e)
    <div class="event-card">
        <div class="event-banner" style="background:{{ $e[4] }}18;">
            <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="{{ $e[4] }}" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div class="event-body">
            <div class="event-title">{{ $e[0] }}</div>
            <div class="event-meta">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ $e[2] }}
            </div>
            <span class="badge {{ $e[3]==='Confirmed'?'badge-green':($e[3]==='Planning'?'badge-blue':'badge-gray') }}">
                {{ $e[3] }}
            </span>
            <div class="event-footer">
                <span style="font-size:0.8rem;color:#64748b;">📅 {{ $e[1] }}</span>
                <span style="font-size:0.8rem;color:#64748b;">👥 {{ $e[5] }} attendees</span>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
