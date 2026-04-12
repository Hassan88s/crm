@extends('layouts.admin-sidebar')
@section('title', 'Customers')
@section('page-title', 'Customers')

@section('extra-styles')
<style>
    .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; }
    table { width:100%; border-collapse:collapse; }
    th { text-align:left; font-size:0.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; padding:0.65rem 1.25rem; background:#f8fafc; }
    td { padding:0.85rem 1.25rem; font-size:0.875rem; color:#374151; border-top:1px solid #f1f5f9; }
    tr:hover td { background:#f8fafc; }
    .badge { display:inline-block; padding:2px 9px; border-radius:999px; font-size:0.72rem; font-weight:600; }
    .badge-green { background:#dcfce7; color:#16a34a; }
    .badge-yellow { background:#fef9c3; color:#854d0e; }
    .badge-gray { background:#f1f5f9; color:#475569; }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1 style="font-size:1.3rem;font-weight:700;color:#0f172a;">Customers</h1>
        <p style="color:#64748b;font-size:0.875rem;margin-top:3px;">248 total customers</p>
    </div>
    <button class="btn">+ Add Customer</button>
</div>

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            @foreach([
                ['Sarah Johnson','sarah@example.com','+1 555-0101','Active','Jan 12, 2026'],
                ['Mark Rivera','mark@example.com','+1 555-0102','Active','Jan 10, 2026'],
                ['Emily Chen','emily@example.com','+1 555-0103','Pending','Jan 9, 2026'],
                ['Tom Walker','tom@example.com','+1 555-0104','Inactive','Jan 7, 2026'],
                ['Priya Patel','priya@example.com','+1 555-0105','Active','Jan 5, 2026'],
                ['John Smith','john@example.com','+1 555-0106','Active','Dec 28, 2025'],
                ['Lisa Brown','lisa@example.com','+1 555-0107','Active','Dec 20, 2025'],
            ] as $c)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:9px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.78rem;flex-shrink:0;">
                            {{ strtoupper(substr($c[0],0,1)) }}
                        </div>
                        <span style="font-weight:500;">{{ $c[0] }}</span>
                    </div>
                </td>
                <td style="color:#64748b;">{{ $c[1] }}</td>
                <td style="color:#64748b;">{{ $c[2] }}</td>
                <td>
                    <span class="badge {{ $c[3]==='Active'?'badge-green':($c[3]==='Pending'?'badge-yellow':'badge-gray') }}">
                        {{ $c[3] }}
                    </span>
                </td>
                <td style="color:#94a3b8;">{{ $c[4] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
