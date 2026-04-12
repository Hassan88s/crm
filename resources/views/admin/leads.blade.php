@extends('layouts.admin-sidebar')
@section('title', 'Leads')
@section('page-title', 'Leads')

@section('extra-styles')
<style>
    table { width:100%; border-collapse:collapse; }
    th { text-align:left; font-size:0.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; padding:0.65rem 1.25rem; background:#f8fafc; }
    td { padding:0.85rem 1.25rem; font-size:0.875rem; color:#374151; border-top:1px solid #f1f5f9; }
    tr:hover td { background:#f8fafc; }
    .badge { display:inline-block; padding:2px 9px; border-radius:999px; font-size:0.72rem; font-weight:600; }
    .badge-green { background:#dcfce7; color:#16a34a; }
    .badge-blue { background:#dbeafe; color:#1d4ed8; }
    .badge-yellow { background:#fef9c3; color:#854d0e; }
    .badge-red { background:#fee2e2; color:#dc2626; }
    .badge-gray { background:#f1f5f9; color:#475569; }
    .progress-bar { height:6px; border-radius:999px; background:#e2e8f0; overflow:hidden; width:80px; }
    .progress-fill { height:100%; border-radius:999px; background:#2563eb; }
</style>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Leads</h1>
        <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">63 active leads</p>
    </div>
    <button class="btn">+ New Lead</button>
</div>

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Lead</th>
                <th>Company</th>
                <th>Source</th>
                <th>Stage</th>
                <th>Score</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach([
                ['Alice Foster','TechCorp','Website','Qualified','85','$24,000'],
                ['Brian Nguyen','StartupXYZ','Referral','Demo','70','$8,500'],
                ['Carmen Lopez','DesignStudio','LinkedIn','New','45','$5,200'],
                ['Dan Mueller','FinanceInc','Event','Proposal','90','$55,000'],
                ['Eva Petrov','RetailCo','Cold Email','New','30','$3,800'],
                ['Felix Okafor','TechStartup','Website','Qualified','75','$18,000'],
                ['Grace Kim','MediaHouse','Referral','Demo','60','$12,400'],
            ] as $l)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:9px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:#f0fdf4;color:#16a34a;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.78rem;flex-shrink:0;">
                            {{ strtoupper(substr($l[0],0,1)) }}
                        </div>
                        <span style="font-weight:500;">{{ $l[0] }}</span>
                    </div>
                </td>
                <td style="color:#64748b;">{{ $l[1] }}</td>
                <td>
                    <span class="badge badge-gray">{{ $l[2] }}</span>
                </td>
                <td>
                    <span class="badge {{ $l[3]==='Qualified'?'badge-blue':($l[3]==='Demo'?'badge-yellow':($l[3]==='Proposal'?'badge-green':'badge-gray')) }}">
                        {{ $l[3] }}
                    </span>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ $l[4] }}%;"></div>
                        </div>
                        <span style="font-size:0.8rem;color:#64748b;">{{ $l[4] }}</span>
                    </div>
                </td>
                <td style="font-weight:600;color:#16a34a;">{{ $l[5] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
