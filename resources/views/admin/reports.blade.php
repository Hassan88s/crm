@extends('layouts.admin-sidebar')
@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
<div style="margin-bottom:1.5rem;">
    <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Reports & Analytics</h1>
    <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">Overview for January 2026</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem;margin-bottom:1.75rem;">
    @foreach([
        ['Total Revenue','$84,200','+18%','#2563eb','#eff6ff'],
        ['Deals Closed','24','+5','#16a34a','#f0fdf4'],
        ['New Leads','63','+12','#ca8a04','#fefce8'],
        ['Conversion Rate','38%','+4%','#9333ea','#fdf4ff'],
    ] as $s)
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">
            <span style="font-size:0.8rem;font-weight:600;color:#64748b;">{{ $s[0] }}</span>
            <span style="font-size:0.75rem;font-weight:700;color:{{ $s[2][0]==='+' ? '#16a34a' : '#dc2626' }};">{{ $s[2] }}</span>
        </div>
        <div style="font-size:1.7rem;font-weight:700;color:#0f172a;">{{ $s[1] }}</div>
    </div>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;">
        <div style="font-weight:700;color:#0f172a;margin-bottom:1rem;">Revenue by Month</div>
        @foreach([['Oct','$62k',62],['Nov','$71k',71],['Dec','$79k',79],['Jan','$84k',84]] as $m)
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:0.75rem;">
            <span style="width:30px;font-size:0.8rem;color:#64748b;">{{ $m[0] }}</span>
            <div style="flex:1;height:20px;background:#f1f5f9;border-radius:6px;overflow:hidden;">
                <div style="height:100%;background:#2563eb;border-radius:6px;width:{{ $m[2] }}%;"></div>
            </div>
            <span style="font-size:0.85rem;font-weight:600;color:#0f172a;width:40px;text-align:right;">{{ $m[1] }}</span>
        </div>
        @endforeach
    </div>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;">
        <div style="font-weight:700;color:#0f172a;margin-bottom:1rem;">Leads by Source</div>
        @foreach([['Website','38%','#2563eb'],['Referral','27%','#16a34a'],['LinkedIn','20%','#9333ea'],['Events','15%','#ca8a04']] as $src)
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:0.75rem;">
            <div style="width:10px;height:10px;border-radius:50%;background:{{ $src[2] }};flex-shrink:0;"></div>
            <span style="flex:1;font-size:0.875rem;color:#374151;">{{ $src[0] }}</span>
            <div style="width:80px;height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;">
                <div style="height:100%;background:{{ $src[2] }};border-radius:999px;width:{{ $src[1] }};"></div>
            </div>
            <span style="font-size:0.8rem;font-weight:600;color:#64748b;width:35px;text-align:right;">{{ $src[1] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endsection
