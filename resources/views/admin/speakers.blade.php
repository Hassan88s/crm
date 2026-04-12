@extends('layouts.admin-sidebar')
@section('title', 'Speakers')
@section('page-title', 'Speakers')

@section('extra-styles')
<style>
    .speakers-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1.25rem; }
    .speaker-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; display:flex; flex-direction:column; align-items:center; text-align:center; }
    .speaker-avatar { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:700; color:#fff; margin-bottom:0.75rem; }
    .speaker-name { font-weight:700; font-size:1rem; color:#0f172a; }
    .speaker-topic { font-size:0.8rem; color:#64748b; margin-top:3px; margin-bottom:0.75rem; }
    .speaker-tags { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; }
    .tag { font-size:0.72rem; font-weight:600; padding:2px 8px; border-radius:999px; background:#f1f5f9; color:#475569; }
    .speaker-footer { margin-top:1rem; padding-top:1rem; border-top:1px solid #f1f5f9; width:100%; display:flex; justify-content:center; gap:0.5rem; }
</style>
@endsection

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Speakers</h1>
        <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">18 registered speakers</p>
    </div>
    <button class="btn">+ Add Speaker</button>
</div>

<div class="speakers-grid">
    @foreach([
        ['James Lee','AI & Machine Learning','#2563eb',['AI','ML','Tech']],
        ['Maria Santos','Product Strategy','#16a34a',['Product','Growth']],
        ['Kevin Park','Cloud Architecture','#9333ea',['Cloud','DevOps']],
        ['Aisha Nwosu','Digital Marketing','#ef4444',['Marketing','SEO']],
        ['David Bloom','FinTech & Startups','#ca8a04',['Finance','Startup']],
        ['Raj Mehta','Cybersecurity','#0891b2',['Security','Tech']],
        ['Sophia Müller','UX & Design','#d946ef',['Design','UX']],
        ['Carlos Reyes','Sales Leadership','#f97316',['Sales','Leadership']],
    ] as $s)
    <div class="speaker-card">
        <div class="speaker-avatar" style="background:{{ $s[2] }};">
            {{ strtoupper(substr($s[0], 0, 1)) }}
        </div>
        <div class="speaker-name">{{ $s[0] }}</div>
        <div class="speaker-topic">{{ $s[1] }}</div>
        <div class="speaker-tags">
            @foreach($s[3] as $tag)
                <span class="tag">{{ $tag }}</span>
            @endforeach
        </div>
        <div class="speaker-footer">
            <button class="btn btn-outline" style="font-size:0.8rem;padding:0.4rem 0.8rem;">View Profile</button>
            <button class="btn" style="font-size:0.8rem;padding:0.4rem 0.8rem;">Assign Event</button>
        </div>
    </div>
    @endforeach
</div>
@endsection
