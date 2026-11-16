<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Manager · People &amp; QR Codes</title>
    @vite(['resources/css/app.css'])
    <style>
        :root { color-scheme: light; --ink: #20283a; --muted: #7b8496; --line: #e8eaf0; --paper: #fff; --wash: #f7f8fb; --teal: #8157d8; --teal-dark: #7045c5; --danger: #a83333; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--wash); color: var(--ink); font: 16px/1.5 ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        button, input, select, textarea { font: inherit; }
        button, .button { border: 0; border-radius: 10px; background: var(--teal); color: #fff; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; padding: 10px 16px; text-decoration: none; font-weight: 650; }
        button:hover, .button:hover { background: var(--teal-dark); }
        button:disabled { opacity: .55; cursor: wait; }
        button.secondary, .button.secondary { color: var(--ink); background: #e8eff1; }
        button.secondary:hover, .button.secondary:hover { background: #dbe6e8; }
        button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { outline: 3px solid #75c8c0; outline-offset: 2px; }
        .shell { max-width: 1120px; margin: 0 auto; padding: 30px 20px 56px; }
        .masthead { display: flex; align-items: center; gap: 14px; margin-bottom: 26px; }
        .mark { display: grid; place-items: center; width: 46px; height: 46px; border-radius: 14px; background: #eee9fb; color: var(--teal-dark); font-size: 25px; font-weight: 800; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin-bottom: 3px; font-size: clamp(24px, 4vw, 32px); letter-spacing: -.035em; }
        .subtitle { margin: 0; color: var(--muted); }
        .privacy-note { margin: 0 0 22px; padding: 14px 16px; border: 1px solid #c6e4df; border-radius: 12px; background: #e9f7f4; color: #164c47; }
        .tabs { display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid var(--line); }
        .tab { min-height: 44px; padding: 9px 16px; border-radius: 10px 10px 0 0; color: var(--muted); background: transparent; }
        .tab:hover { color: var(--ink); background: #e8eff1; }
        .tab[aria-selected="true"] { color: #fff; background: var(--teal); }
        .panel { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(280px, .9fr); gap: 18px; align-items: start; }
        .card { min-width: 0; padding: 22px; border: 1px solid var(--line); border-radius: 16px; background: var(--paper); box-shadow: 0 8px 24px rgb(22 41 52 / 4%); }
        .card h2 { margin-bottom: 6px; font-size: 20px; }
        .card-intro { margin-bottom: 19px; color: var(--muted); }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { min-width: 0; }
        .field.wide { grid-column: 1 / -1; }
        .field label { display: block; margin-bottom: 5px; font-weight: 650; font-size: 14px; }
        .field input, .field select, .field textarea, .camera-select { width: 100%; min-height: 44px; padding: 10px 11px; border: 1px solid #bdcbd2; border-radius: 9px; color: var(--ink); background: #fff; }
        .field textarea { min-height: 84px; resize: vertical; }
        .include-toggle { display: flex !important; align-items: center; gap: 7px; margin-top: 7px; color: var(--muted); font-weight: 500 !important; }
        .include-toggle input { width: 17px; min-height: 17px; height: 17px; accent-color: var(--teal); }
        .section-label { margin: 20px 0 8px; font-size: 14px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .helper { margin: 5px 0 0; color: var(--muted); font-size: 13px; }
        .submit-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .error-box { margin-bottom: 16px; padding: 12px 15px; border: 1px solid #e4b4b4; border-radius: 10px; color: #771e1e; background: #fff3f3; }
        .error-box ul { margin: 0; padding-left: 20px; }
        .result-card { text-align: center; }
        .qr-image { display: block; width: min(100%, 400px); height: auto; margin: 16px auto; image-rendering: auto; background: #fff; }
        .result-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 9px; }
        .payload-summary { margin: 12px 0 0; color: var(--muted); font-size: 13px; }
        .payload-preview { margin-top: 16px; text-align: left; }
        .payload-preview summary { color: var(--muted); cursor: pointer; }
        .payload-preview pre { overflow-wrap: anywhere; white-space: pre-wrap; max-height: 220px; overflow: auto; padding: 12px; border-radius: 8px; background: #f2f5f6; font-size: 12px; }
        .scanner-card { max-width: 790px; margin: 0 auto; }
        .camera-actions, .upload-actions { display: flex; flex-wrap: wrap; gap: 9px; align-items: center; }
        .camera-select { flex: 1 1 220px; }
        .video-wrap { position: relative; overflow: hidden; width: 100%; min-height: 220px; margin: 16px 0; border-radius: 14px; background: #101719; }
        #qr-video { display: block; width: 100%; max-height: 70vh; min-height: 220px; object-fit: contain; background: #101719; }
        .video-hint { position: absolute; inset: 0; display: grid; place-items: center; padding: 20px; color: #d4e2e4; text-align: center; pointer-events: none; }
        #qr-video:not([src]) + .video-hint { display: grid; }
        .scan-status { min-height: 25px; margin: 10px 0; color: var(--muted); }
        .scan-status.error { color: var(--danger); }
        .upload { display: block; margin-top: 18px; padding-top: 17px; border-top: 1px solid var(--line); }
        .upload input { display: block; max-width: 100%; margin-top: 9px; }
        .decoded { margin-top: 18px; padding: 18px; border-radius: 12px; background: #f1f7f6; }
        .decoded[hidden] { display: none; }
        .decoded h3 { margin-bottom: 5px; }
        .signature-note { margin-bottom: 14px; color: var(--muted); font-size: 13px; }
        .person-data { display: grid; grid-template-columns: minmax(120px, .35fr) 1fr; gap: 8px 12px; margin: 0; }
        .person-data dt { color: var(--muted); font-weight: 650; }
        .person-data dd { margin: 0; overflow-wrap: anywhere; white-space: pre-wrap; }
        .raw-data { margin-top: 14px; }
        .raw-data pre { overflow-wrap: anywhere; white-space: pre-wrap; }
        .footer-note { margin: 22px 0 0; color: var(--muted); font-size: 13px; }
        .local-save { display: flex; align-items: flex-start; gap: 9px; margin-top: 16px; padding: 12px; border: 1px solid var(--line); border-radius: 10px; background: #faf9fd; }
        .local-save input { flex: 0 0 auto; width: 17px; height: 17px; margin-top: 2px; accent-color: var(--teal); }
        .local-save strong, .local-save small { display: block; }
        .local-save strong { font-size: 13px; }
        .local-save small { margin-top: 3px; color: var(--muted); font-size: 11px; }
        .people-toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
        .people-toolbar input { flex: 1 1 220px; min-height: 44px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 9px; }
        .local-person-list { display: grid; gap: 10px; }
        .local-person { display: flex; align-items: center; gap: 12px; padding: 13px; border: 1px solid var(--line); border-radius: 10px; }
        .local-person-avatar { display: grid; place-items: center; flex: 0 0 40px; width: 40px; height: 40px; border-radius: 50%; background: #eee9fb; color: var(--teal-dark); font-weight: 700; }
        .local-person-main { flex: 1; min-width: 0; }
        .local-person-main strong, .local-person-main small { display: block; overflow-wrap: anywhere; }
        .local-person-main small { margin-top: 3px; color: var(--muted); }
        .local-person-actions { display: flex; flex-wrap: wrap; gap: 6px; }
        .local-person-actions button { min-height: 34px; padding: 7px 10px; font-size: 12px; }
        .local-storage-error { color: var(--danger); }
        [hidden] { display: none !important; }
        @media (max-width: 760px) { .panel { grid-template-columns: 1fr; } .shell { padding-inline: 14px; } .card { padding: 18px; } }
        @media (max-width: 520px) { .form-grid { grid-template-columns: 1fr; } .field.wide { grid-column: auto; } .person-data { grid-template-columns: 1fr; gap: 2px; } .person-data dd { margin-bottom: 9px; } }
        @media print { body { background: white; } .masthead, .privacy-note, .tabs, .form-card, .scanner-card, .footer-note, .result-actions, .payload-preview { display: none !important; } .shell { max-width: none; padding: 0; } .panel { display: block; } .result-card { border: 0; box-shadow: none; } .qr-image { width: 3.5in; max-width: 90vw; } }
    </style>
</head>
<body class="font-sans antialiased">
    @php
        $optionalFields = [
            ['key' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'wide' => false],
            ['key' => 'gender', 'label' => 'Gender', 'type' => 'text', 'wide' => false, 'max' => 40],
            ['key' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'wide' => false, 'max' => 40],
            ['key' => 'email', 'label' => 'Email address', 'type' => 'email', 'wide' => false, 'max' => 254],
            ['key' => 'address', 'label' => 'Address', 'type' => 'textarea', 'wide' => true, 'max' => 500],
            ['key' => 'organization', 'label' => 'Organization', 'type' => 'text', 'wide' => false, 'max' => 160],
            ['key' => 'department', 'label' => 'Department', 'type' => 'text', 'wide' => false, 'max' => 120],
            ['key' => 'designation', 'label' => 'Designation', 'type' => 'text', 'wide' => false, 'max' => 120],
            ['key' => 'reference_number', 'label' => 'Reference number', 'type' => 'text', 'wide' => false, 'max' => 80],
            ['key' => 'emergency_contact', 'label' => 'Emergency contact', 'type' => 'text', 'wide' => false, 'max' => 80],
            ['key' => 'notes', 'label' => 'Additional notes', 'type' => 'textarea', 'wide' => true, 'max' => 800],
        ];
        $formPerson = old('person', request()->input('person', []));
        $includedFields = old('include', request()->input('include', ['phone', 'email', 'organization']));
        $selectedCorrection = old('error_correction', request()->input('error_correction', $defaultErrorCorrection ?? 'Q'));
    @endphp
    <div class="shell">
        <header class="masthead">
            <div class="mark" aria-hidden="true">⌗</div>
            <div>
                <h1>QR Manager</h1>
                <p class="subtitle">Create, keep, and scan person QR codes from this browser.</p>
            </div>
        </header>

        <p class="privacy-note"><strong>Local storage:</strong> Saved people stay in this browser on this device and are not uploaded to the application database. QR contents can be read by anyone who scans them; only include details you want to share.</p>

        <div class="tabs" role="tablist" aria-label="QR tools">
            <button class="tab" id="generate-tab" type="button" role="tab" aria-selected="{{ ($scannerOnly ?? false) ? 'false' : 'true' }}" aria-controls="generate-panel" @if ($scannerOnly ?? false) hidden @endif>Generate a QR</button>
            <button class="tab" id="people-tab" type="button" role="tab" aria-selected="false" aria-controls="people-panel" @if ($scannerOnly ?? false) hidden @endif>People saved here</button>
            <button class="tab" id="scan-tab" type="button" role="tab" aria-selected="{{ ($scannerOnly ?? false) ? 'true' : 'false' }}" aria-controls="scan-panel">Scan a QR</button>
        </div>

        <section id="generate-panel" role="tabpanel" aria-labelledby="generate-tab" @if ($scannerOnly ?? false) hidden @endif>
            @if ($errors->any())
                <div class="error-box" role="alert">
                    <strong>QR code could not be generated.</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="panel">
                <form id="qr-generator" class="card form-card" method="post" action="{{ route('qr.generate') }}">
                    @csrf
                    <input type="hidden" name="local_record_id" id="local_record_id" value="">
                    <h2>Person information</h2>
                    <p class="card-intro">Choose which optional fields to encode. Values are never cut to fit.</p>

                    <div class="field">
                        <label for="full_name">Full name <span aria-hidden="true">*</span></label>
                        <input id="full_name" name="person[full_name]" value="{{ $formPerson['full_name'] ?? '' }}" maxlength="120" autocomplete="name" required>
                    </div>

                    <h3 class="section-label">Optional fields</h3>
                    <div class="form-grid">
                        @foreach ($optionalFields as $field)
                            <div class="field {{ ($field['wide'] ?? false) ? 'wide' : '' }}">
                                <label for="{{ $field['key'] }}">{{ $field['label'] }}</label>
                                @if ($field['type'] === 'textarea')
                                    <textarea id="{{ $field['key'] }}" name="person[{{ $field['key'] }}]" maxlength="{{ $field['max'] }}">{{ $formPerson[$field['key']] ?? '' }}</textarea>
                                @else
                                    <input id="{{ $field['key'] }}" type="{{ $field['type'] }}" name="person[{{ $field['key'] }}]" value="{{ $formPerson[$field['key']] ?? '' }}" @if (isset($field['max'])) maxlength="{{ $field['max'] }}" @endif>
                                @endif
                                <label class="include-toggle" for="include-{{ $field['key'] }}">
                                    <input id="include-{{ $field['key'] }}" type="checkbox" name="include[]" value="{{ $field['key'] }}" @checked(in_array($field['key'], (array) $includedFields, true))>
                                    Include in QR
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <h3 class="section-label">QR quality</h3>
                    <div class="field">
                        <label for="error_correction">Error correction</label>
                        <select id="error_correction" name="error_correction">
                            <option value="Q" @selected($selectedCorrection === 'Q')>Q — recommended balance</option>
                            <option value="H" @selected($selectedCorrection === 'H')>H — more recovery for wear or damage</option>
                        </select>
                        <p class="helper">Higher correction can help with damaged prints, but fits less data. The QR version grows automatically.</p>
                    </div>
                    <div class="submit-row">
                        <button type="submit">Generate QR code</button>
                        <button id="save-person-only" class="secondary" type="button">Save person only</button>
                    </div>
                    <label class="local-save"><input id="save-locally" type="checkbox" checked><span><strong>Keep this person in this browser</strong><small>Saved locally only. Clear this browser’s data to remove the saved records.</small></span></label>
                </form>

                <section id="qr-result" class="card result-card" aria-live="polite" aria-label="Generated QR code">
                    @if (isset($result))
                        <h2>QR code ready</h2>
                        <img id="qr-image" class="qr-image" src="{{ $result['svg'] }}" alt="QR code for {{ $result['person']['full_name'] }}" width="400" height="400">
                        <div id="qr-result-actions" class="result-actions">
                            <a id="download-png" class="button" href="{{ $result['png'] }}" download="person-qr.png">Download PNG</a>
                            <a id="download-svg" class="button secondary" href="{{ $result['svg'] }}" download="person-qr.svg">Download SVG</a>
                            <button class="secondary" type="button" onclick="window.print()">Print</button>
                        </div>
                        <p id="payload-summary" class="payload-summary">Version 1 · {{ $result['bytes'] }} UTF-8 bytes · error correction {{ $result['errorCorrection'] }}</p>
                        <details id="payload-preview" class="payload-preview">
                            <summary>View encoded JSON</summary>
                            <pre id="payload-text">{{ $result['payload'] }}</pre>
                        </details>
                    @else
                        <h2 id="qr-placeholder-heading">Your QR preview</h2>
                        <p class="card-intro">The code will appear here with a white quiet zone and high-contrast modules.</p>
                        <img id="qr-image" class="qr-image" alt="Generated QR code" width="400" height="400" hidden>
                        <div id="qr-result-actions" class="result-actions" hidden><a id="download-png" class="button" download="person-qr.png">Download PNG</a><a id="download-svg" class="button secondary" download="person-qr.svg">Download SVG</a><button class="secondary" type="button" onclick="window.print()">Print</button></div>
                        <p id="payload-summary" class="payload-summary" hidden></p>
                        <details id="payload-preview" class="payload-preview" hidden><summary>View encoded JSON</summary><pre id="payload-text"></pre></details>
                    @endif
                </section>
            </div>
        </section>

        <section id="people-panel" role="tabpanel" aria-labelledby="people-tab" hidden>
            <section class="card">
                <h2>People saved in this browser</h2>
                <p class="card-intro">These records exist only in this browser’s local storage. They are not shared between devices or sent to the server.</p>
                <div class="people-toolbar"><input id="people-search" type="search" placeholder="Search by name, email, phone, or ID" aria-label="Search saved people"><button id="export-local-people" class="secondary" type="button">Export records</button><label class="button secondary" for="import-local-people">Import records<input id="import-local-people" type="file" accept="application/json,.json" hidden></label></div>
                <p id="local-storage-status" class="scan-status" role="status" aria-live="polite"></p>
                <div id="local-person-list" class="local-person-list"></div>
            </section>
        </section>

        <section id="scan-panel" role="tabpanel" aria-labelledby="scan-tab" @if (! ($scannerOnly ?? false)) hidden @endif>
            <div class="card scanner-card">
                <h2>Scan a QR code</h2>
                <p class="card-intro">Use a camera or choose a PNG, JPG, JPEG, or WEBP image. Camera access requires HTTPS, except on localhost.</p>
                <div class="camera-actions">
                    <button id="start-camera" type="button">Start camera</button>
                    <button id="stop-camera" class="secondary" type="button" disabled>Stop camera</button>
                    <select id="camera-select" class="camera-select" aria-label="Choose camera" hidden></select>
                </div>
                <div class="video-wrap">
                    <video id="qr-video" playsinline muted></video>
                    <div class="video-hint" id="video-hint">Camera preview appears here. On phones, the rear camera is preferred.</div>
                </div>
                <p id="scan-status" class="scan-status" role="status" aria-live="polite">Ready. Start the camera or choose a QR image.</p>
                <label class="upload" for="qr-image-file">
                    <strong>Decode an image</strong>
                    <span class="helper">PNG, JPG, JPEG, or WEBP · up to 15 MB</span>
                    <input id="qr-image-file" type="file" accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp">
                </label>
                <section id="scan-result" class="decoded" aria-live="polite" hidden>
                    <h3 id="result-heading"></h3>
                    <p id="signature-note" class="signature-note"></p>
                    <dl id="person-data" class="person-data"></dl>
                    <details id="raw-data" class="raw-data" hidden>
                        <summary>Show decoded text</summary>
                        <pre id="raw-text"></pre>
                    </details>
                    <div class="submit-row">
                        <button id="copy-record" class="secondary" type="button">Copy information</button>
                        <button id="scan-again" type="button">Scan another QR</button>
                    </div>
                </section>
            </div>
        </section>

        <p class="footer-note">QR version 1 person payloads are schema-checked before display. A schema-valid record is not proof of who issued it; this application does not add a digital signature.</p>
    </div>

    <script src="{{ asset('js/zxing-browser.min.js') }}" defer></script>
    <script type="module" src="{{ asset('js/qr-local-people.js') }}?v={{ filemtime(public_path('js/qr-local-people.js')) }}"></script>
    <script src="{{ asset('js/qr-scanner.js') }}?v={{ filemtime(public_path('js/qr-scanner.js')) }}" defer></script>
</body>
</html>
