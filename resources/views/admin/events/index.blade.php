@extends('layouts.admin-sidebar')
@section('title', 'Events')
@section('page-title', 'Events')

@section('extra-styles')
<style>
    .badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:999px; font-size:0.72rem; font-weight:600; }
    .badge::before { content:''; width:6px; height:6px; border-radius:50%; }
    .badge-confirmed { background:#dcfce7; color:#16a34a; }
    .badge-confirmed::before { background:#16a34a; }
    .badge-planning  { background:#dbeafe; color:#1d4ed8; }
    .badge-planning::before  { background:#2563eb; }
    .badge-draft     { background:#f1f5f9; color:#475569; }
    .badge-draft::before     { background:#94a3b8; }

    .alert-success {
        background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px;
        padding:0.85rem 1.1rem; margin-bottom:1.5rem;
        color:#16a34a; font-size:0.875rem; font-weight:500;
        display:flex; align-items:center; gap:8px;
    }

    /* Cards grid */
    .events-grid {
        display:grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap:1.25rem;
    }

    .event-card {
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:14px;
        overflow:hidden;
        display:flex;
        flex-direction:column;
        transition:box-shadow 180ms, transform 180ms;
    }
    .event-card:hover {
        box-shadow:0 8px 28px -8px rgba(0,0,0,0.12);
        transform:translateY(-2px);
    }

    /* Image area */
    .event-card-img {
        width:100%; height:180px;
        object-fit:cover;
        display:block;
    }
    .event-card-img-placeholder {
        width:100%; height:180px;
        background:linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        display:flex; flex-direction:column;
        align-items:center; justify-content:center;
        color:#cbd5e1; gap:8px;
    }
    .event-card-img-placeholder svg { width:40px; height:40px; }
    .event-card-img-placeholder span { font-size:0.78rem; font-weight:500; }

    /* Body */
    .event-card-body { padding:1.1rem 1.25rem; flex:1; display:flex; flex-direction:column; gap:0.6rem; }

    .event-card-title {
        font-weight:700; font-size:1rem; color:#0f172a;
        line-height:1.3;
    }
    .event-card-desc {
        font-size:0.8rem; color:#94a3b8; line-height:1.5;
    }

    .event-meta-row {
        display:flex; align-items:center; gap:6px;
        font-size:0.8rem; color:#64748b;
    }
    .event-meta-row svg { width:14px; height:14px; flex-shrink:0; color:#94a3b8; }

    /* Footer */
    .event-card-footer {
        display:flex; align-items:center; justify-content:space-between;
        padding:0.85rem 1.25rem;
        border-top:1px solid #f1f5f9;
        background:#fafafa;
    }
    .action-btn {
        display:inline-flex; align-items:center; gap:5px;
        padding:0.4rem 0.75rem; border-radius:6px; border:1px solid #e2e8f0;
        font-size:0.78rem; font-weight:600; cursor:pointer; text-decoration:none;
        background:#fff; color:#374151; transition:all 120ms;
    }
    .action-btn:hover { background:#f1f5f9; }
    .action-btn.danger { color:#dc2626; border-color:#fecaca; background:#fff; }
    .action-btn.danger:hover { background:#fef2f2; }

    /* Empty state */
    .empty-state {
        background:#fff; border:1px solid #e2e8f0; border-radius:14px;
        text-align:center; padding:5rem 2rem; color:#94a3b8;
    }
    .empty-state svg { width:52px; height:52px; margin:0 auto 1rem; opacity:0.35; display:block; }
    .empty-state h3 { font-size:1rem; font-weight:700; color:#64748b; margin-bottom:0.4rem; }
    .empty-state p  { font-size:0.875rem; margin-bottom:1.5rem; }
</style>
@endsection

@section('content')

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
    <div>
        <h1 style="font-size:1.3rem; font-weight:700; color:#0f172a;">Events</h1>
        <p style="color:#64748b; font-size:0.875rem; margin-top:3px;">{{ $events->count() }} total event{{ $events->count() !== 1 ? 's' : '' }}</p>
    </div>
    <a href="{{ route('admin.events.create') }}" class="btn">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Add Event
    </a>
</div>

{{-- Success alert --}}
@if(session('success'))
    <div class="alert-success">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

{{-- Empty state --}}
@if($events->isEmpty())
    <div class="empty-state">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h3>No events yet</h3>
        <p>Get started by creating your first event.</p>
        <a href="{{ route('admin.events.create') }}" class="btn">+ Add Event</a>
    </div>

{{-- Cards grid --}}
@else
    <div class="events-grid">
        @foreach($events as $event)
        <div class="event-card">

            {{-- Image --}}
            @if($event->image)
                <img src="{{ Storage::url($event->image) }}"
                     alt="{{ $event->name }}"
                     class="event-card-img">
            @else
                <div class="event-card-img-placeholder">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>No image</span>
                </div>
            @endif

            {{-- Body --}}
            <div class="event-card-body">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.5rem;">
                    <div class="event-card-title">{{ $event->name }}</div>
                    <span class="badge badge-{{ $event->status }}" style="flex-shrink:0;">
                        {{ ucfirst($event->status) }}
                    </span>
                </div>

                @if($event->description)
                    <div class="event-card-desc">{{ Str::limit($event->description, 80) }}</div>
                @endif

                <div style="display:flex; flex-direction:column; gap:5px; margin-top:auto; padding-top:0.5rem;">
                    <div class="event-meta-row">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $event->date->format('M d, Y') }}@if($event->end_date) — {{ $event->end_date->format('M d, Y') }}@endif
                        &nbsp;·&nbsp;
                        {{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}
                    </div>
                    <div class="event-meta-row">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $event->location }}
                    </div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="event-card-footer">
                <a href="{{ route('admin.events.edit', $event) }}" class="action-btn">
                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                <form action="{{ route('admin.events.destroy', $event) }}" method="POST"
                      onsubmit="return confirm('Delete « {{ $event->name }} »?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="action-btn danger">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </form>
            </div>

        </div>
        @endforeach
    </div>
@endif

@endsection
