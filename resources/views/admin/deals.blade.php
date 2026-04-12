@extends('layouts.admin-sidebar')
@section('title', 'Deals')
@section('page-title', 'Deals')

@section('extra-styles')
<style>
    .pipeline { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; }
    .pipeline-col { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; }
    .col-header { font-size:0.8rem; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.75rem; display:flex; justify-content:space-between; }
    .col-count { background:#e2e8f0; color:#475569; border-radius:999px; padding:1px 7px; font-size:0.72rem; }
    .deal-card { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:0.85rem; margin-bottom:0.6rem; cursor:pointer; transition:box-shadow 140ms; }
    .deal-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.06); }
    .deal-title { font-weight:600; font-size:0.875rem; color:#0f172a; margin-bottom:4px; }
    .deal-company { font-size:0.78rem; color:#64748b; margin-bottom:6px; }
    .deal-value { font-size:0.85rem; font-weight:700; color:#16a34a; }
    .deal-meta { display:flex; align-items:center; justify-content:space-between; margin-top:6px; }
    .badge { display:inline-block; padding:2px 7px; border-radius:999px; font-size:0.68rem; font-weight:600; }
    .badge-blue { background:#dbeafe; color:#1d4ed8; }
    .badge-yellow { background:#fef9c3; color:#854d0e; }
    .badge-green { background:#dcfce7; color:#16a34a; }
    .badge-red { background:#fee2e2; color:#dc2626; }
</style>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Deal Pipeline</h1>
        <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">Total pipeline value: <strong style="color:#16a34a;">$284,600</strong></p>
    </div>
    <button class="btn">+ New Deal</button>
</div>

<div class="pipeline">
    @foreach([
        ['Prospecting', 'badge-blue', [
            ['Website Redesign','StartupXYZ','$8,500','2d'],
            ['SEO Audit','MediaHouse','$3,200','5d'],
        ]],
        ['Proposal', 'badge-yellow', [
            ['Enterprise License','FinanceInc','$55,000','1d'],
            ['Annual Support','TechCorp','$24,000','3d'],
        ]],
        ['Negotiation', 'badge-blue', [
            ['Cloud Migration','RetailCo','$42,000','1d'],
            ['Dev Tooling','TechStartup','$18,000','6d'],
        ]],
        ['Closed Won', 'badge-green', [
            ['CRM Setup','DesignStudio','$12,400','—'],
            ['Training Package','MediaHouse','$5,200','—'],
        ]],
    ] as [$stage, $badgeClass, $deals])
    <div class="pipeline-col">
        <div class="col-header">
            {{ $stage }}
            <span class="col-count">{{ count($deals) }}</span>
        </div>
        @foreach($deals as $d)
        <div class="deal-card">
            <div class="deal-title">{{ $d[0] }}</div>
            <div class="deal-company">{{ $d[1] }}</div>
            <div class="deal-meta">
                <span class="deal-value">{{ $d[2] }}</span>
                <span class="badge {{ $badgeClass }}" style="font-size:0.68rem;">{{ $d[3] }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach
</div>
@endsection
