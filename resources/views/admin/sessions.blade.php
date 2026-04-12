@extends('layouts.admin-sidebar')
@section('title', 'Sessions')
@section('page-title', 'Sessions')

@section('extra-styles')
<style>
    table { width:100%; border-collapse:collapse; }
    th { text-align:left; font-size:0.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; padding:0.65rem 1.25rem; background:#f8fafc; }
    td { padding:0.85rem 1.25rem; font-size:0.875rem; color:#374151; border-top:1px solid #f1f5f9; }
    tr:hover td { background:#f8fafc; }
    .badge { display:inline-block; padding:2px 9px; border-radius:999px; font-size:0.72rem; font-weight:600; }
    .badge-green { background:#dcfce7; color:#16a34a; }
    .badge-blue { background:#dbeafe; color:#1d4ed8; }
    .badge-purple { background:#f3e8ff; color:#7e22ce; }
    .badge-gray { background:#f1f5f9; color:#475569; }
</style>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Sessions</h1>
        <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">32 sessions across all events</p>
    </div>
    <button class="btn">+ New Session</button>
</div>

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Session Title</th>
                <th>Event</th>
                <th>Speaker</th>
                <th>Time</th>
                <th>Track</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach([
                ['AI in Business','Tech Summit 2026','James Lee','9:00 – 10:00 AM','Technology','Confirmed'],
                ['Product Roadmapping','Tech Summit 2026','Maria Santos','10:30 – 11:30 AM','Product','Confirmed'],
                ['Cloud Security Best Practices','Tech Summit 2026','Kevin Park','1:00 – 2:00 PM','Cloud','Confirmed'],
                ['Growing Your Pipeline','Sales Workshop','Carlos Reyes','9:00 – 11:00 AM','Sales','Scheduled'],
                ['UX for SaaS Products','Product Launch','Sophia Müller','2:00 – 3:00 PM','Design','Draft'],
                ['FinTech Disruption','Growth Summit','David Bloom','11:00 – 12:00 PM','Finance','Scheduled'],
                ['Zero-Day Threats','Developer Meetup','Raj Mehta','3:00 – 4:00 PM','Security','Draft'],
            ] as $s)
            <tr>
                <td style="font-weight:500;">{{ $s[0] }}</td>
                <td style="color:#64748b;">{{ $s[1] }}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:7px;">
                        <div style="width:26px;height:26px;border-radius:50%;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.7rem;flex-shrink:0;">
                            {{ strtoupper(substr($s[2],0,1)) }}
                        </div>
                        {{ $s[2] }}
                    </div>
                </td>
                <td style="color:#64748b;white-space:nowrap;font-size:0.8rem;">{{ $s[3] }}</td>
                <td>
                    <span class="badge badge-purple">{{ $s[4] }}</span>
                </td>
                <td>
                    <span class="badge {{ $s[5]==='Confirmed'?'badge-green':($s[5]==='Scheduled'?'badge-blue':'badge-gray') }}">
                        {{ $s[5] }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
