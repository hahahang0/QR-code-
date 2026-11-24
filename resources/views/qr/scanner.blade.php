@extends('admin.layouts.app')

@section('title', 'QR Scanner')

@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">QR SCANNER</p>
        <h1>Scan a QR code</h1>
        <p class="muted">Scan a portfolio QR code or upload an image. Older person QR codes are still recognized.</p>
    </div>
</div>

<div class="scanner-grid">
    <section class="panel-card">
        <div class="scanner-preview">
            <video id="qr-video" playsinline muted></video>
            <div class="scan-overlay">
                <div class="scan-frame"><div><span class="scan-glyph">⌗</span><span id="video-hint">Position a QR code inside the frame</span></div></div>
                <p class="scan-security">Scanning happens securely in your browser</p>
            </div>
        </div>
        <div class="scanner-controls">
            <select id="camera-select" class="camera-select" aria-label="Choose camera" hidden></select>
            <div class="heading-actions">
                <button id="start-camera" class="button button-primary" type="button">◎ Start Scanning</button>
                <button id="stop-camera" class="button button-light" type="button" disabled>Stop</button>
                <label class="button button-light" for="qr-image-file">↑ Upload QR Image<input id="qr-image-file" type="file" accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp" hidden></label>
            </div>
            <p class="muted">Use HTTPS for camera access. You can also upload PNG, JPG, JPEG, or WEBP images.</p>
        </div>
        <p id="scan-status" class="scan-status" role="status" aria-live="polite">Ready. Start the camera or choose a QR image.</p>
        <section id="scan-result" class="decoded" aria-live="polite" hidden>
            <h2 id="result-heading"></h2>
            <p id="signature-note" class="signature-note"></p>
            <dl id="person-data" class="person-data"></dl>
            <details id="raw-data" class="raw-data" hidden><summary>Show decoded text</summary><pre id="raw-text"></pre></details>
            <div class="submit-row">
                <a id="open-profile" class="button button-primary" href="#" target="_blank" rel="noopener noreferrer" hidden>Open profile</a>
                <button id="copy-record" class="button button-light" type="button" hidden>Copy link or information</button>
                <button id="scan-again" class="button button-light" type="button">Scan another QR</button>
            </div>
        </section>
    </section>
    <aside class="panel-card scan-guide">
        <h2>How it works</h2>
        <p>New QR codes contain a link to a public portfolio. Review the destination before opening it.</p>
        <p>QR codes from the previous version may contain person details directly; the scanner identifies those as an older format.</p>
        <p>Camera and image decoding happen on this device.</p>
    </aside>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/zxing-browser.min.js') }}" defer></script>
<script src="{{ asset('js/qr-scanner.js') }}?v={{ filemtime(public_path('js/qr-scanner.js')) }}" defer></script>
@endpush
