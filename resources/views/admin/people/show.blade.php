@extends('admin.layouts.app')
@section('title', $person->full_name)
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">PERSON PROFILE</p><h1>{{ $person->full_name }}</h1><p class="muted">{{ $person->designation ?: 'Person record' }}{{ $person->organization ? ' · '.$person->organization : '' }} · {{ $person->reference_number ?: 'Record '.$person->id }}</p></div>
    <div class="heading-actions"><form method="post" action="{{ route('admin.people.destroy', $person) }}">@csrf @method('delete')<button class="button button-danger-light" data-confirm="Archive {{ $person->full_name }}?">Archive</button></form><a class="button button-primary" href="{{ route('admin.people.edit', $person) }}">Edit person</a></div>
</div>
<div class="profile-grid">
    <div class="profile-main">
        <section class="panel-card">
            <div class="profile-title">
                @if ($person->profile_photo_path)<img class="profile-avatar" src="{{ route('profiles.photo', ['slug' => $person->public_slug]) }}" alt="Profile photo of {{ $person->full_name }}">@else<span class="profile-avatar">{{ strtoupper(substr($person->full_name, 0, 1)) }}</span>@endif
                <div><span class="badge {{ $person->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($person->status) }}</span><h2>{{ $person->full_name }}</h2><p class="muted">Registered {{ $person->created_at->format('F j, Y') }}</p></div>
            </div>
            <div class="detail-section"><h3>Personal information</h3><dl class="detail-grid"><dt>Person ID</dt><dd>{{ $person->reference_number ?: '—' }}</dd><dt>Date of birth</dt><dd>{{ $person->date_of_birth?->format('F j, Y') ?: '—' }}</dd><dt>Gender</dt><dd>{{ $person->gender ?: '—' }}</dd></dl></div>
            <div class="detail-section"><h3>Contact information</h3><dl class="detail-grid"><dt>Email</dt><dd>{{ $person->email ?: '—' }}</dd><dt>Phone</dt><dd>{{ $person->phone ?: '—' }}</dd><dt>Address</dt><dd>{{ $person->address ?: '—' }}</dd><dt>Emergency contact</dt><dd>{{ $person->emergency_contact ?: '—' }}</dd></dl></div>
            <div class="detail-section"><h3>Organization</h3><dl class="detail-grid"><dt>Organization</dt><dd>{{ $person->organization ?: '—' }}</dd><dt>Department</dt><dd>{{ $person->department ?: '—' }}</dd><dt>Designation</dt><dd>{{ $person->designation ?: '—' }}</dd></dl></div>
            @if ($person->notes)<div class="detail-section"><h3>Additional notes</h3><p>{{ $person->notes }}</p></div>@endif
        </section>
        <section class="panel-card"><div class="section-heading"><div><h2>QR code history</h2><p class="muted">Previously generated versions for this person.</p></div></div>
            @forelse ($person->qrCodes as $qrCode)
                <div class="history-row"><span class="stat-icon violet">▦</span><div><strong>QR code · {{ $qrCode->error_correction }} correction</strong><p class="muted">{{ $qrCode->created_at->format('M j, Y · g:i A') }} · schema v{{ $qrCode->schema_version }}</p></div><span class="badge {{ $qrCode->is_current ? 'badge-green' : 'badge-gray' }}">{{ $qrCode->is_current ? 'Current' : 'Previous' }}</span><a class="text-link" href="{{ route('admin.qr-codes.download', [$qrCode, 'png']) }}">Download</a></div>
            @empty<div class="empty-state compact"><p>No QR code has been generated.</p></div>@endforelse
        </section>
    </div>
    <aside class="profile-side">
        <section class="panel-card qr-profile">
            <div class="section-heading"><div><h2>QR code</h2><p class="muted">Scan to open this person’s public portfolio.</p></div>@if ($person->currentQrCode)<span class="badge badge-green">Active</span>@endif</div>
            @if ($person->currentQrCode)
                <img class="profile-qr" src="{{ route('admin.qr-codes.preview', [$person->currentQrCode, 'svg']) }}" alt="QR code for {{ $person->full_name }}">
                <strong>{{ $person->full_name }}</strong><small>{{ $person->reference_number ?: 'Person record' }}</small>
                <p class="public-profile-url"><a href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer">{{ $profileUrl }}</a></p>
                <p class="muted">Public profile URL · {{ $person->currentQrCode->error_correction }} error correction</p>
                <div class="button-stack"><a class="button button-primary" href="{{ route('admin.qr-codes.download', [$person->currentQrCode, 'png']) }}">Download PNG</a><a class="button button-light" href="{{ route('admin.qr-codes.download', [$person->currentQrCode, 'svg']) }}">Download SVG</a><button class="button button-light" onclick="window.print()" type="button">Print QR code</button></div>
                <button class="button button-light full-button" type="button" data-copy-profile-url="{{ $profileUrl }}">Copy profile URL</button>
                <a class="button button-light full-button" href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer">Preview public profile</a>
            @else<p class="empty-state compact">No QR code generated yet.</p>@endif
            <form method="post" action="{{ route('admin.people.qr-codes.store', $person) }}" class="generate-qr-form">@csrf
                <p class="muted">Generate or replace the current code.</p>
                <div class="field full"><label for="person-qr-correction">Error correction</label><select id="person-qr-correction" name="error_correction"><option value="Q" @selected(($person->currentQrCode?->error_correction ?? $defaultErrorCorrection ?? 'Q') === 'Q')>Q · Recommended</option><option value="H" @selected(($person->currentQrCode?->error_correction ?? $defaultErrorCorrection ?? 'Q') === 'H')>H · Maximum recovery</option></select></div>
                <button class="button button-outline full-button">Generate QR code</button>
            </form>
            <p class="helper-note">This QR code contains the public portfolio URL. Information shown on that page is accessible to anyone with the link.</p>
        </section>
    </aside>
</div>
<script>
document.querySelectorAll('[data-copy-profile-url]').forEach((button) => button.addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(button.dataset.copyProfileUrl);
        button.textContent = 'Copied';
    } catch {
        window.prompt('Copy this public profile URL:', button.dataset.copyProfileUrl);
    }
}));
</script>
@endsection
