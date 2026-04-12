@extends('layouts.admin-sidebar')
@section('title', 'Contacts')
@section('page-title', 'Contacts')

@section('extra-styles')
<style>
    .contacts-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:1.25rem; flex-wrap:wrap; gap:0.75rem; }
    .contacts-header h1 { font-size:1.25rem; font-weight:700; color:#0f172a; }
    .contacts-header p { font-size:0.84rem; color:#64748b; margin-top:2px; }

    .stat-chips { display:flex; gap:0.75rem; margin-bottom:1.25rem; flex-wrap:wrap; }
    .stat-chip { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:0.55rem 0.9rem; }
    .stat-chip-val { font-size:1.15rem; font-weight:700; color:#0f172a; line-height:1; }
    .stat-chip-lbl { font-size:0.72rem; color:#64748b; }

    .search-bar {
        display:flex; align-items:center; gap:8px;
        background:#fff; border:1.5px solid #e2e8f0; border-radius:8px;
        padding:0.5rem 0.85rem; margin-bottom:1.25rem; max-width:400px;
    }
    .search-bar svg { width:15px; height:15px; color:#94a3b8; flex-shrink:0; }
    .search-bar input { border:none; outline:none; font-size:0.85rem; color:#0f172a; background:transparent; width:100%; font-family:inherit; }

    .contacts-table { width:100%; border-collapse:collapse; }
    .contacts-table th {
        text-align:left; font-size:0.72rem; font-weight:700; color:#64748b;
        text-transform:uppercase; letter-spacing:0.05em;
        padding:0.65rem 1rem; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    }
    .contacts-table td { padding:0.7rem 1rem; font-size:0.84rem; color:#374151; border-bottom:1px solid #f1f5f9; }
    .contacts-table tr:hover td { background:#f8fafc; }

    .contact-avatar {
        width:32px; height:32px; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:0.72rem; color:#fff; flex-shrink:0;
    }
    .contact-name { font-weight:600; color:#0f172a; }
    .contact-email { font-size:0.78rem; color:#94a3b8; }

    .source-badge {
        display:inline-block; padding:2px 7px; border-radius:999px;
        font-size:0.7rem; font-weight:700;
    }
    .source-speaker  { background:#eff6ff; color:#2563eb; }
    .source-received { background:#f0fdf4; color:#16a34a; }
    .source-sent     { background:#fef3c7; color:#d97706; }

    .send-btn {
        display:inline-flex; align-items:center; gap:4px;
        padding:3px 8px; border-radius:5px; border:1px solid #e2e8f0;
        background:#fff; color:#475569; font-size:0.75rem; font-weight:600;
        cursor:pointer; text-decoration:none; transition:all 120ms;
    }
    .send-btn:hover { background:#eff6ff; border-color:#bfdbfe; color:#2563eb; }

    .empty-state { text-align:center; padding:3.5rem 1rem; color:#94a3b8; }
    .empty-state h3 { font-size:1rem; font-weight:600; color:#64748b; margin-bottom:0.4rem; }
</style>
@endsection

@section('content')

<div class="contacts-header">
    <div>
        <h1>Contacts</h1>
        <p>All contacts from your sent emails, inbox replies, and speaker database.</p>
    </div>
    <a href="{{ route('admin.inbox.index') }}" class="btn btn-outline" style="font-size:0.84rem;">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Inbox
    </a>
</div>

<div class="stat-chips">
    <div class="stat-chip">
        <div style="width:34px;height:34px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#2563eb" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div><div class="stat-chip-val">{{ $totalCount }}</div><div class="stat-chip-lbl">Total Contacts</div></div>
    </div>
    <div class="stat-chip">
        <div style="width:34px;height:34px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div><div class="stat-chip-val">{{ $speakerCount }}</div><div class="stat-chip-lbl">Matched Speakers</div></div>
    </div>
</div>

<form action="{{ route('admin.inbox.contacts') }}" method="GET">
    <div class="search-bar">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" placeholder="Search contacts by name, email, or company…" value="{{ $search }}">
    </div>
</form>

<div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
    @if($contacts->count() === 0)
        <div class="empty-state">
            <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 1rem;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <h3>No contacts found</h3>
            <p style="font-size:0.85rem;">{{ $search ? 'No contacts match your search.' : 'Contacts will appear here once you send or receive emails.' }}</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table class="contacts-table">
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Company / Title</th>
                        <th>Source</th>
                        <th>Emails</th>
                        <th>Last Contact</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $colors = ['#2563eb','#16a34a','#9333ea','#ef4444','#ca8a04','#0891b2','#d946ef','#f97316']; @endphp
                    @foreach($contacts as $i => $contact)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="contact-avatar" style="background:{{ $colors[$i % count($colors)] }};">
                                    {{ strtoupper(substr($contact->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="contact-name">{{ $contact->name }}</div>
                                    <div class="contact-email">{{ $contact->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if(!empty($contact->company))
                                <div style="font-weight:500;">{{ $contact->company }}</div>
                            @endif
                            @if(!empty($contact->title))
                                <div style="font-size:0.78rem; color:#94a3b8;">{{ $contact->title }}</div>
                            @endif
                            @if(empty($contact->company) && empty($contact->title))
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @foreach(explode(', ', $contact->source) as $src)
                                @php
                                    $cls = match(trim($src)) {
                                        'Speaker'  => 'source-speaker',
                                        'Sent'     => 'source-sent',
                                        'Received' => 'source-received',
                                        default    => 'source-received',
                                    };
                                @endphp
                                <span class="source-badge {{ $cls }}">{{ trim($src) }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($contact->times > 0)
                                <span style="font-weight:600;">{{ $contact->times }}</span>
                            @else
                                <span style="color:#cbd5e1;">0</span>
                            @endif
                        </td>
                        <td>
                            @if($contact->last_contact)
                                <span style="font-size:0.8rem; color:#64748b;" title="{{ $contact->last_contact }}">
                                    {{ \Carbon\Carbon::parse($contact->last_contact)->diffForHumans() }}
                                </span>
                            @else
                                <span style="color:#cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($contact->speaker_id)
                                <a href="{{ route('admin.speakers.edit', $contact->speaker_id) }}" class="send-btn" title="View speaker profile">
                                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Profile
                                </a>
                            @endif
                            <a href="{{ route('admin.emails.index') }}" class="send-btn" title="Send email">
                                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Email
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
