@extends('admin.layouts.app')
@section('title', 'QR Code Management')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">QR LIBRARY</p><h1>QR Code Management</h1><p class="muted">Review, download, and manage personal QR codes.</p></div>
    <a class="button button-primary" href="{{ route('admin.people.index', ['qr_status' => 'missing']) }}">＋ Generate QR Code</a>
</div>
<section class="stat-grid compact-stats">
    <a class="mini-stat" href="{{ route('admin.qr-codes.index') }}"><span>Total QR versions</span><strong>{{ $counts['all'] }}</strong></a>
    <a class="mini-stat" href="{{ route('admin.qr-codes.index', ['status' => 'current']) }}"><span>Current</span><strong>{{ $counts['current'] }}</strong></a>
    <a class="mini-stat" href="{{ route('admin.qr-codes.index', ['status' => 'previous']) }}"><span>Previous versions</span><strong>{{ $counts['previous'] }}</strong></a>
</section>
<section class="panel-card">
    <form class="toolbar" method="get">
        <label class="search-box"><span>⌕</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Search by person name or ID"></label>
        <select name="status" aria-label="Filter QR codes"><option value="">All versions</option><option value="current" @selected(request('status') === 'current')>Current</option><option value="previous" @selected(request('status') === 'previous')>Previous</option></select>
        <button class="button button-light">Apply filters</button>
    </form>
    <div class="table-scroll"><table>
        <thead><tr><th>QR code</th><th>Person</th><th>Status</th><th>Correction</th><th>Generated</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($qrCodes as $qrCode)
            <tr>
                <td><img class="qr-thumb" src="{{ route('admin.qr-codes.preview', [$qrCode, 'png']) }}" alt="QR code for {{ $qrCode->person->full_name }}" loading="lazy"></td>
                <td>@if ($qrCode->person->trashed)<span class="person-link">{{ $qrCode->person->full_name }}<small>Archived · {{ $qrCode->person->reference_number ?: 'Record '.$qrCode->person->id }}</small></span>@else<a class="person-link" href="{{ route('admin.people.show', $qrCode->person) }}"><span>{{ $qrCode->person->full_name }}<small>{{ $qrCode->person->reference_number ?: 'Record '.$qrCode->person->id }}</small></span></a>@endif</td>
                <td><span class="badge {{ $qrCode->is_current ? 'badge-green' : 'badge-gray' }}">{{ $qrCode->is_current ? 'Current' : 'Previous' }}</span></td>
                <td>{{ $qrCode->error_correction }} · v{{ $qrCode->schema_version }}</td>
                <td>{{ $qrCode->created_at->format('M j, Y') }}<small class="table-sub">{{ $qrCode->created_at->format('g:i A') }}</small></td>
                <td><div class="row-actions"><a href="{{ route('admin.people.show', $qrCode->person) }}">View</a><a href="{{ route('admin.qr-codes.download', [$qrCode, 'png']) }}">PNG</a><a href="{{ route('admin.qr-codes.download', [$qrCode, 'svg']) }}">SVG</a>@if ($qrCode->is_current)<form method="post" action="{{ route('admin.qr-codes.deactivate', $qrCode) }}">@csrf<button class="link-danger" data-confirm="Deactivate this QR code? Existing printed copies cannot be revoked.">Deactivate</button></form>@endif</div></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty-state compact"><strong>No QR codes found</strong><p>Generate one from a person’s profile.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="pagination-row"><span>Showing {{ $qrCodes->firstItem() ?? 0 }}–{{ $qrCodes->lastItem() ?? 0 }} of {{ $qrCodes->total() }} versions</span>{{ $qrCodes->links() }}</div>
    <p class="helper-note">A QR code stores its selected personal information directly. Deleting or archiving a person does not erase QR code copies already downloaded or printed.</p>
</section>
@endsection
