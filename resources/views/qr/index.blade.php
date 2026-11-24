<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR Manager · People &amp; QR Codes</title>
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}-scanner-shared-layout">
    <style>
        :root { color-scheme: light; --purple: #8157d8; --purple-dark: #7045c5; --ink: #20283a; --muted: #7b8496; --line: #e8eaf0; --paper: #fff; --wash: #f7f8fb; }
        body { margin: 0; background: var(--wash); color: var(--ink); font: 16px/1.5 Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .sidebar { gap: 0; }
        .brand { padding-bottom: 27px; }
        .brand small { display: none; }
        .side-nav .nav-button { display: flex; align-items: center; gap: 12px; width: 100%; min-height: 42px; padding: 9px 11px; border: 0; border-radius: 9px; color: #666f82; background: transparent; text-align: left; cursor: pointer; font-size: 15px; font-weight: 500; }
        .side-nav .nav-button span { width: 18px; color: #8b93a4; text-align: center; font-size: 19px; }
        .side-nav .nav-button .nav-count { margin-left: auto; color: #737b8d; font-size: 12px; }
        .side-nav .nav-button:hover, .side-nav .nav-button.active { color: #6c48bd; background: #f1edfb; }
        .side-nav .nav-button.active span { color: #8058d4; }
        .main-content { min-height: 100vh; margin-left: 252px; }
        .content-wrap { max-width: 1680px; padding: 0 38px 18px; }
        .mobile-topbar { position: sticky; top: 0; z-index: 4; display: none; align-items: center; gap: 12px; height: 58px; padding: 0 15px; border-bottom: 1px solid var(--line); background: #fff; }
        .workspace-header { position: sticky; top: 0; z-index: 3; display: flex; align-items: center; justify-content: space-between; gap: 18px; min-height: 82px; margin: 0 -38px 29px; padding: 16px 38px; border-bottom: 1px solid var(--line); background: #fff; }
        .breadcrumbs { display: flex; align-items: center; gap: 12px; color: #8991a2; font-size: 14px; }
        .breadcrumbs strong { color: #3d4658; font-weight: 600; }
        .header-actions { display: flex; align-items: center; gap: 12px; }
        .global-search { width: min(250px, 25vw); min-height: 37px; padding: 8px 11px; border: 1px solid #e6e8ee; border-radius: 8px; color: var(--ink); background: #fff; }
        .shell { max-width: none; margin: 0; padding: 0; }
        .page-panel[hidden] { display: none !important; }
        .page-heading { margin-top: 0; }
        .page-heading h1 { font-size: 31px; }
        .privacy-note { margin: 0 0 17px; padding: 12px 14px; border: 1px solid #e8e0f8; border-radius: 9px; color: #6b5a8e; background: #f7f4fd; font-size: 12px; }
        .stat-card { min-height: 126px; }
        .stat-card strong { font-size: 31px; }
        .registration-chart { height: 206px; }
        .registration-chart .chart-track { height: 145px; }
        .chart-legend { display: flex; gap: 15px; margin: 5px 0 0; color: #7d8494; font-size: 12px; }
        .chart-legend span::before { display: inline-block; width: 7px; height: 7px; margin-right: 6px; border-radius: 50%; background: #8058ce; content: ""; }
        .dashboard-grid { grid-template-columns: minmax(0, 1.5fr) minmax(310px, 1fr); }
        .dashboard-grid .panel-card { min-width: 0; }
        .status-ring { display: grid; place-items: center; width: 170px; height: 170px; margin: 18px auto; border-radius: 50%; background: conic-gradient(#8157d8 0deg 280deg, #c8b6ee 280deg 320deg, #eeeaf6 320deg 360deg); }
        .status-ring-inner { display: grid; place-content: center; width: 125px; height: 125px; border-radius: 50%; background: #fff; text-align: center; }
        .status-ring-inner small { color: var(--muted); font-size: 12px; }
        .status-ring-inner strong { font-size: 27px; }
        .status-key { display: flex; justify-content: space-between; gap: 12px; color: #777f90; font-size: 12px; }
        .status-key b { color: var(--ink); }
        .people-tabs { display: flex; gap: 18px; margin: 0 0 14px; border-bottom: 1px solid var(--line); }
        .people-filter { padding: 9px 0; border: 0; border-bottom: 2px solid transparent; color: #7d8494; background: none; cursor: pointer; font-size: 12px; }
        .people-filter.active { border-bottom-color: var(--purple); color: #7651c6; }
        .people-filter span { margin-left: 4px; padding: 2px 5px; border-radius: 4px; background: #f0ebfa; color: #7651c6; }
        .toolbar { margin-bottom: 14px; }
        .qr-config .checkbox-list { grid-template-columns: 1fr; }
        .checkbox-list label { font-size: 12px; }
        .checkbox-list input { width: 15px; height: 15px; }
        .form-layout { grid-template-columns: minmax(0, 1fr) 320px; }
        .form-main { display: grid; gap: 15px; }
        .form-main .panel-card { margin: 0; padding: 20px; }
        .form-main .field { margin-bottom: 3px; }
        .form-main .field input, .form-main .field select, .form-main .field textarea { min-height: 42px; padding: 10px 12px; font-size: 14px; }
        .form-main .field textarea { min-height: 76px; }
        .form-layout .form-side { position: sticky; top: 98px; }
        .qr-config { margin-bottom: 13px; }
        .qr-config .muted { font-size: 12px; }
        .form-submit { margin: 0; }
        .person-dialog .form-submit { gap: 10px; margin-top: 20px; }
        .result-card { position: relative; padding: 18px; text-align: center; }
        .result-card h2 { font-size: 17px; }
        .qr-image { width: min(100%, 250px); margin: 8px auto; }
        .result-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin: 15px 0 8px; }
        .result-actions .button { min-height: 36px; padding-inline: 13px; font-size: 12px; }
        .payload-summary { margin: 8px 0 0; overflow-wrap: anywhere; color: var(--muted); font-size: 12px; line-height: 1.5; }
        .card { min-width: 0; }
        .decoded { background: #f7f4fd; }
        .scan-layout { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(280px, .8fr); gap: 16px; }
        .scan-preview { position: relative; display: grid; place-items: center; min-height: 390px; overflow: hidden; padding: 25px; border-radius: 9px; background: #171622; color: #d4c5f4; text-align: center; }
        #qr-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; background: #171622; }
        .scan-overlay { position: relative; z-index: 1; pointer-events: none; }
        .scan-frame { display: grid; place-items: center; width: min(100%, 310px); aspect-ratio: 1; border: 3px solid #a77ee7; border-radius: 24px; color: #c4b1ed; }
        .scan-frame > div { display: grid; gap: 10px; justify-items: center; max-width: 210px; }
        .scan-frame .scan-glyph { font-size: 47px; }
        .scan-preview .scan-security { margin: 18px 0 0; font-size: 12px; color: #a9a4b8; }
        .scan-controls { display: grid; gap: 10px; margin-top: 15px; }
        .scan-controls .camera-select { width: 100%; }
        .scan-guide { display: grid; gap: 20px; padding: 20px; }
        .scan-guide-item { display: flex; gap: 12px; }
        .scan-step { display: grid; place-items: center; flex: 0 0 27px; width: 27px; height: 27px; border-radius: 50%; color: #7651c6; background: #f0ebfa; }
        .decoded { margin-top: 12px; padding: 16px; border: 1px solid #e8e0f8; border-radius: 9px; background: #f7f4fd; }
        .decoded h3 { margin: 0; color: var(--ink); font-size: 16px; }
        .signature-note { margin: 6px 0 12px; color: var(--muted); font-size: 12px; line-height: 1.5; }
        .person-data { display: grid; grid-template-columns: minmax(95px, .4fr) minmax(0, 1fr); gap: 8px 12px; margin: 0; }
        .person-data dt { color: var(--muted); font-size: 12px; }
        .person-data dd { margin: 0; color: var(--ink); font-size: 12px; overflow-wrap: anywhere; white-space: pre-wrap; }
        .raw-data { margin-top: 12px; padding-top: 10px; border-top: 1px solid #e8e0f8; color: #626b7b; font-size: 12px; }
        .raw-data pre { max-height: 180px; overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; }
        .submit-row { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 14px; }
        .scan-status { margin: 10px 0 0; color: var(--muted); font-size: 12px; line-height: 1.5; }
        .scan-status.error { color: #a23c43; }
        .local-storage-error { color: #a23c43; }
        .local-status { margin: 12px 0; }
        .local-person-list td { vertical-align: middle; }
        .local-person-list .person-link { white-space: normal; }
        .local-person-list .table-avatar { width: 36px; height: 36px; }
        .local-person-list .row-actions { gap: 8px; }
        .qr-thumbnail { display: grid; place-items: center; width: 46px; height: 46px; padding: 2px; border: 1px solid var(--line); border-radius: 6px; background: #fff; color: var(--purple); font-size: 27px; }
        .activity-row { align-items: flex-start; }
        .person-dialog { position: fixed; top: 50%; left: 50%; width: min(620px, calc(100% - 28px)); max-height: min(82vh, 760px); margin: 0; padding: 0; overflow: auto; border: 0; border-radius: 12px; background: transparent; transform: translate(-50%, -50%); }
        .person-dialog::backdrop { background: rgb(24 27 38 / 45%); backdrop-filter: blur(2px); }
        .person-dialog[open] { display: block; }
        .page-footer { margin-top: 30px; }
        [hidden] { display: none !important; }
        @media (max-width: 1120px) { .content-wrap { padding-inline: 23px; } .workspace-header { margin-inline: -23px; padding-inline: 23px; } .dashboard-grid { grid-template-columns: 1fr; } .form-layout { grid-template-columns: minmax(0, 1fr) 285px; } }
        @media (max-width: 820px) { .main-content { margin-left: 0; } .mobile-topbar { display: flex; } .workspace-header { position: static; min-height: 64px; margin: 0 -16px 22px; padding: 12px 16px; } .content-wrap { padding: 0 16px 18px; } .sidebar { padding-top: 17px; } .form-layout, .scan-layout { grid-template-columns: 1fr; } .form-layout .form-side { position: static; } }
        @media (max-width: 540px) { .content-wrap { padding-inline: 12px; } .workspace-header { margin-inline: -12px; padding-inline: 12px; } .global-search { display: none; } .header-actions { margin-left: auto; } .header-actions .button { min-height: 34px; padding: 8px 10px; font-size: 12px; } .page-heading { flex-direction: column; align-items: flex-start; } .people-tabs { overflow-x: auto; } .people-filter { white-space: nowrap; } .form-layout { display: block; } .form-side { margin-top: 14px; } .status-ring { width: 145px; height: 145px; } .status-ring-inner { width: 108px; height: 108px; } }
        @media print { .sidebar, .mobile-topbar, .workspace-header, .page-footer, .page-heading, .form-main, .qr-config, .form-submit, .scan-layout, .privacy-note, .people-panel, .dashboard-grid { display: none !important; } .main-content { margin: 0; } .content-wrap { padding: 0; } #generate-panel[hidden] { display: block !important; } .shell { display: block; } .form-layout { display: block; } .form-side { position: static !important; } .result-card { border: 0; box-shadow: none; } .qr-image { width: 3.5in; max-width: 90vw; } }
    </style>
</head>
<body class="font-sans antialiased" data-default-screen="{{ ($scannerOnly ?? false) ? 'scan' : 'dashboard' }}">
    @php
        $optionalFields = [
            ['key' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'max' => 10],
            ['key' => 'gender', 'label' => 'Gender', 'type' => 'text', 'max' => 40],
            ['key' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'max' => 40],
            ['key' => 'email', 'label' => 'Email address', 'type' => 'email', 'max' => 254],
            ['key' => 'address', 'label' => 'Residential address', 'type' => 'textarea', 'max' => 500],
            ['key' => 'organization', 'label' => 'Organization', 'type' => 'text', 'max' => 160],
            ['key' => 'department', 'label' => 'Department', 'type' => 'text', 'max' => 120],
            ['key' => 'designation', 'label' => 'Designation', 'type' => 'text', 'max' => 120],
            ['key' => 'reference_number', 'label' => 'Identification number', 'type' => 'text', 'max' => 80],
            ['key' => 'emergency_contact', 'label' => 'Emergency contact', 'type' => 'text', 'max' => 80],
            ['key' => 'notes', 'label' => 'Additional notes', 'type' => 'textarea', 'max' => 800],
        ];
        $formPerson = old('person', request()->input('person', []));
        $includedFields = old('include', request()->input('include', ['phone', 'email', 'organization']));
        $selectedCorrection = old('error_correction', request()->input('error_correction', 'Q'));
    @endphp
    <div class="admin-shell min-h-screen bg-slate-50">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('qr.index') }}"><span class="brand-mark">▦</span><span>QR Manager</span></a>
            <p class="nav-caption">MANAGEMENT</p>
            <nav class="side-nav" aria-label="QR Manager navigation">
                <button class="nav-button" id="dashboard-tab" type="button" role="tab" aria-controls="dashboard-panel"><span>◫</span>Dashboard</button>
                <button class="nav-button" id="people-tab" type="button" role="tab" aria-controls="people-panel"><span>♙</span>People Management<small class="nav-count" id="sidebar-people-count"></small></button>
                <button class="nav-button" id="qr-tab" type="button" role="tab" aria-controls="qr-panel"><span>▦</span>QR Code Management</button>
                <button class="nav-button" id="scan-tab" type="button" role="tab" aria-controls="scan-panel"><span>⌗</span>QR Scanner</button>
                <button class="nav-button" id="activity-tab" type="button" role="tab" aria-controls="activity-panel"><span>◷</span>Activity Logs</button>
            </nav>
        </aside>
        <main class="main-content">
            <header class="mobile-topbar"><button type="button" class="icon-button" onclick="document.body.classList.toggle('nav-open')" aria-label="Toggle navigation">☰</button><a class="mobile-brand" href="{{ route('qr.index') }}">QR Manager</a></header>
            <div class="content-wrap">
                <header class="workspace-header">
                    <div class="breadcrumbs"><span>QR Manager</span><span aria-hidden="true">›</span><strong id="breadcrumb-current">Dashboard</strong></div>
                    <div class="header-actions"><input id="global-search" class="global-search" type="search" placeholder="Search people..." aria-label="Search people"><button id="generate-tab" class="button button-primary" type="button" role="tab" aria-controls="generate-panel">＋ Add New Person</button></div>
                </header>
                <div class="shell">
                    <section id="dashboard-panel" class="page-panel" role="tabpanel" aria-labelledby="dashboard-tab" @if (($scannerOnly ?? false)) hidden @endif>
                        <div class="page-heading"><div><h1>Dashboard overview</h1><p class="muted">A quick look at people and their QR codes saved in this browser.</p></div><div class="heading-actions"><select id="dashboard-range" class="range-select" aria-label="Dashboard range"><option value="7">Last 7 days</option><option value="30">Last 30 days</option></select><button class="button button-primary" type="button" data-open-screen="generate">＋ Add New Person</button></div></div>
                        <p class="privacy-note"><strong>Stored in this browser:</strong> Person records stay on this device and are not uploaded to the application database. Anyone can read the details embedded in a QR code.</p>
                        <section class="stat-grid">
                            <article class="stat-card"><span class="stat-icon violet">♙</span><span class="muted">Total People</span><strong id="stat-total-people">0</strong><small>Saved in this browser</small></article>
                            <article class="stat-card"><span class="stat-icon blue">▦</span><span class="muted">QR Codes Generated</span><strong id="stat-qr-generated">0</strong><small>Current person QR codes</small></article>
                            <article class="stat-card"><span class="stat-icon green">✓</span><span class="muted">Active QR Codes</span><strong id="stat-qr-active">0</strong><small>Available to download</small></article>
                            <article class="stat-card"><span class="stat-icon amber">!</span><span class="muted">Inactive Records</span><strong id="stat-inactive">0</strong><small>Archived people</small></article>
                        </section>
                        <div class="dashboard-grid">
                            <section class="panel-card"><div class="section-heading"><div><h2>Registration overview</h2><p class="muted">People added to this browser over time.</p></div></div><div class="chart-legend"><span>People registered</span></div><div id="registration-chart" class="registration-chart" aria-label="People registered over time"></div></section>
                            <section class="panel-card"><div class="section-heading"><div><h2>QR code status</h2><p class="muted">Current generated codes.</p></div><button class="text-link" type="button" data-open-screen="qr">View codes →</button></div><div id="qr-status-ring" class="status-ring"><div class="status-ring-inner"><small>Total QR codes</small><strong id="qr-ring-total">0</strong></div></div><div class="status-key"><span>● Generated</span><b id="qr-ring-generated">0</b></div><div class="status-key"><span>● Not generated</span><b id="qr-ring-missing">0</b></div></section>
                            <section class="panel-card"><div class="section-heading"><div><h2>Recently registered people</h2><p class="muted">The latest records saved here.</p></div><button class="text-link" type="button" data-open-screen="people">View all →</button></div><div class="table-scroll"><table><thead><tr><th>Person</th><th>Organization</th><th>QR status</th><th>Added</th></tr></thead><tbody id="dashboard-recent-people"></tbody></table></div></section>
                            <section class="panel-card"><div class="section-heading"><div><h2>Recent activity</h2><p class="muted">Updates saved in this browser.</p></div><button class="text-link" type="button" data-open-screen="activity">View activity →</button></div><div id="dashboard-recent-activity"></div><div class="scanner-callout"><span>⌗</span><div><strong>Scan without a database lookup</strong><p>Person information is embedded directly in each QR code.</p><button class="text-link" type="button" data-open-screen="scan">Open QR Scanner →</button></div></div></section>
                        </div>
                    </section>

                    <section id="people-panel" class="page-panel" role="tabpanel" aria-labelledby="people-tab" hidden>
                        <div class="page-heading"><div><h1>People Management</h1><p class="muted">Manage local person records and the information shared through their QR codes.</p></div><div class="heading-actions"><button id="export-local-people" class="button button-light" type="button">↓ Export</button><label class="button button-light" for="import-local-people">↑ Import<input id="import-local-people" type="file" accept="application/json,.json" hidden></label><button class="button button-primary" type="button" data-open-screen="generate">＋ Add New Person</button></div></div>
                        <section class="stat-grid compact-stats"><button class="mini-stat people-filter active" type="button" data-filter="all"><span>All people</span><strong id="people-count-all">0</strong></button><button class="mini-stat people-filter" type="button" data-filter="active"><span>Active</span><strong id="people-count-active">0</strong></button><button class="mini-stat people-filter" type="button" data-filter="inactive"><span>Inactive</span><strong id="people-count-inactive">0</strong></button><button class="mini-stat people-filter" type="button" data-filter="not-generated"><span>Not Generated</span><strong id="people-count-missing">0</strong></button></section>
                        <section class="panel-card"><div class="toolbar"><label class="search-box"><span>⌕</span><input id="people-search" type="search" placeholder="Search by name, email, or ID" aria-label="Search people"></label><select id="people-status-filter" aria-label="Filter people by status"><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select><button id="reset-people-filter" class="button button-light" type="button">Filters</button></div><div class="table-scroll"><table class="local-person-list"><thead><tr><th><input id="select-all-people" type="checkbox" aria-label="Select all visible people"></th><th>Full name</th><th>Phone number</th><th>Organization</th><th>QR code status</th><th>Registration date</th><th>Actions</th></tr></thead><tbody id="local-person-list"></tbody></table></div><div id="people-empty" class="empty-state compact" hidden><strong>No matching people</strong><p>Try another search, or add a person.</p></div><div class="pagination-row"><span id="people-count-summary">0 people</span><div class="row-actions"><button class="button button-light" type="button" data-page="previous">‹ Previous</button><span id="people-page-label">Page 1</span><button class="button button-light" type="button" data-page="next">Next ›</button></div></div><p id="local-storage-status" class="scan-status local-status" role="status" aria-live="polite"></p></section>
                    </section>

                    <section id="generate-panel" class="page-panel" role="tabpanel" aria-labelledby="generate-tab" hidden>
                        <div class="page-heading"><div><h1 id="form-page-title">Register New Person</h1><p class="muted">Add a new individual and choose the information to embed in their personal QR code.</p></div><button class="button button-light" type="button" data-open-screen="people">← Back to people</button></div>
                        <p id="generator-status" class="scan-status local-status" role="status" aria-live="polite"></p>
                        <form id="qr-generator" class="form-layout" method="post" action="{{ route('qr.generate') }}">
                            @csrf
                            <input type="hidden" name="local_record_id" id="local_record_id" value="">
                            <div class="form-main">
                                <section class="panel-card"><div class="section-heading"><div><h2>Personal information</h2><p class="muted">Basic identity details for this record.</p></div></div><div class="form-grid">
                                    <div class="field"><label for="full_name">Full name <em>*</em></label><input id="full_name" name="person[full_name]" value="{{ $formPerson['full_name'] ?? '' }}" maxlength="120" autocomplete="name" placeholder="e.g. Olivia Rhye" required></div>
                                    <div class="field"><label for="reference_number">Identification number</label><input id="reference_number" name="person[reference_number]" value="{{ $formPerson['reference_number'] ?? '' }}" maxlength="80" placeholder="Assigned automatically"><small>A unique, locally assigned identifier.</small></div>
                                    <div class="field"><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="person[date_of_birth]" value="{{ $formPerson['date_of_birth'] ?? '' }}" maxlength="10"></div>
                                    <div class="field"><label for="gender">Gender</label><select id="gender" name="person[gender]" maxlength="40"><option value="">Select gender</option>@foreach (['Female', 'Male', 'Non-binary', 'Prefer not to say'] as $gender)<option value="{{ $gender }}" @selected(($formPerson['gender'] ?? '') === $gender)>{{ $gender }}</option>@endforeach</select></div>
                                </div></section>
                                <section class="panel-card"><div class="section-heading"><div><h2>Contact information</h2><p class="muted">How to reach this person.</p></div></div><div class="form-grid">
                                    @foreach (array_slice($optionalFields, 2, 3) as $field)<div class="field {{ $field['key'] === 'address' ? 'full' : '' }}"><label for="{{ $field['key'] }}">{{ $field['label'] }}@if (in_array($field['key'], ['email', 'phone'], true)) <em>*</em>@endif</label>@if ($field['type'] === 'textarea')<textarea id="{{ $field['key'] }}" name="person[{{ $field['key'] }}]" maxlength="{{ $field['max'] }}" placeholder="Street address, city, state, and postal code">{{ $formPerson[$field['key']] ?? '' }}</textarea>@else<input id="{{ $field['key'] }}" type="{{ $field['type'] }}" name="person[{{ $field['key'] }}]" value="{{ $formPerson[$field['key']] ?? '' }}" maxlength="{{ $field['max'] }}" @required(in_array($field['key'], ['email', 'phone'], true))>@endif</div>@endforeach
                                    <div class="field full"><label for="emergency_contact">Emergency contact</label><input id="emergency_contact" name="person[emergency_contact]" value="{{ $formPerson['emergency_contact'] ?? '' }}" maxlength="80"></div>
                                </div></section>
                                <section class="panel-card"><div class="section-heading"><div><h2>Organization information</h2><p class="muted">Where this person works, studies, or belongs.</p></div></div><div class="form-grid">
                                    @foreach (array_slice($optionalFields, 5, 3) as $field)<div class="field"><label for="{{ $field['key'] }}">{{ $field['label'] }}</label><input id="{{ $field['key'] }}" type="text" name="person[{{ $field['key'] }}]" value="{{ $formPerson[$field['key']] ?? '' }}" maxlength="{{ $field['max'] }}"></div>@endforeach
                                    <div class="field full"><label for="notes">Additional notes</label><textarea id="notes" name="person[notes]" maxlength="800" rows="3">{{ $formPerson['notes'] ?? '' }}</textarea></div>
                                </div></section>
                                <div class="form-submit"><button type="submit" class="button button-primary">Generate QR Code</button><button id="save-person-only" class="button button-light" type="button">Save Person</button><button class="button button-light" type="button" data-open-screen="people">Cancel</button></div>
                            </div>
                            <aside class="form-side"><section class="panel-card qr-config"><span class="stat-icon violet">▦</span><h2>QR code configuration</h2><p class="muted">You’re in control of what’s shared. Selected information is stored directly in the QR code.</p><div class="checkbox-list"><strong>Information to include in QR code</strong><label><input type="checkbox" checked disabled>Full name</label>@foreach ($optionalFields as $field)<label><input type="checkbox" name="include[]" value="{{ $field['key'] }}" @checked(in_array($field['key'], (array) $includedFields, true))>{{ $field['label'] }}</label>@endforeach</div><div class="field"><label for="error_correction">Error correction</label><select id="error_correction" name="error_correction"><option value="Q" @selected($selectedCorrection === 'Q')>Q · Recommended (25%)</option><option value="H" @selected($selectedCorrection === 'H')>H · Maximum recovery (30%)</option></select><small>Higher correction helps recover from damage but fits less data.</small></div><label class="check-row"><input id="save-locally" type="checkbox" checked> Keep this person in this browser</label><div class="privacy-callout"><strong>Privacy by design</strong><span>QR codes are not encrypted. Anyone who scans the code can read the selected details. Unselected information stays out of the QR.</span></div></section>
                                <section id="qr-result" class="panel-card result-card" aria-live="polite" aria-label="Generated QR code"><h2 id="qr-placeholder-heading">Your QR preview</h2><p class="muted">The code will appear here after generation.</p><img id="qr-image" class="qr-image" alt="Generated QR code" width="400" height="400" hidden><div id="qr-result-actions" class="result-actions" hidden><a id="download-png" class="button button-primary" download="person-qr.png">Download PNG</a><a id="download-svg" class="button button-light" download="person-qr.svg">Download SVG</a><button class="button button-light" type="button" onclick="window.print()">Print</button></div><p id="payload-summary" class="payload-summary" hidden></p></section></aside>
                        </form>
                    </section>

                    <section id="qr-panel" class="page-panel" role="tabpanel" aria-labelledby="qr-tab" hidden>
                        <div class="page-heading"><div><h1>QR Code Management</h1><p class="muted">Review QR codes generated for people saved in this browser.</p></div><button class="button button-primary" type="button" data-open-screen="generate">＋ Generate QR Code</button></div>
                        <section class="stat-grid compact-stats"><div class="mini-stat"><span>All QR codes</span><strong id="qr-count-all">0</strong></div><div class="mini-stat"><span>Active</span><strong id="qr-count-active">0</strong></div><div class="mini-stat"><span>Not generated</span><strong id="qr-count-missing">0</strong></div></section>
                        <section class="panel-card"><div class="toolbar"><label class="search-box"><span>⌕</span><input id="qr-search" type="search" placeholder="Search by person name or ID" aria-label="Search QR codes"></label><select id="qr-status-filter" aria-label="Filter QR codes"><option value="all">All QR codes</option><option value="generated">Generated</option><option value="missing">Not generated</option></select></div><div class="table-scroll"><table><thead><tr><th>QR code</th><th>Person</th><th>Status</th><th>Payload version</th><th>Generated on</th><th>Last updated</th><th>Actions</th></tr></thead><tbody id="local-qr-list"></tbody></table></div><div id="qr-empty" class="empty-state compact" hidden><strong>No QR codes match</strong><p>Generate a code from a person’s local profile.</p></div><div class="pagination-row"><span id="qr-count-summary">0 QR codes</span><div class="row-actions"><button class="button button-light" type="button" data-qr-page="previous">‹ Previous</button><span id="qr-page-label">Page 1</span><button class="button button-light" type="button" data-qr-page="next">Next ›</button></div></div></section>
                    </section>

                    <section id="scan-panel" class="page-panel" role="tabpanel" aria-labelledby="scan-tab" hidden>
                        <div class="page-heading"><div><h1>QR Scanner</h1><p class="muted">Scan from a camera or upload a QR image to display its embedded person details.</p></div></div>
                        <div class="scan-layout"><div class="panel-card"><div class="scan-preview"><video id="qr-video" playsinline muted></video><div class="scan-overlay"><div class="scan-frame"><div><span class="scan-glyph">⌗</span><span id="video-hint">Position a QR code inside the frame</span></div></div><p class="scan-security">◉ Scanning happens securely in your browser</p></div></div><div class="scan-controls"><select id="camera-select" class="camera-select" aria-label="Choose camera" hidden></select><div class="heading-actions"><button id="start-camera" class="button button-primary" type="button">◎ Start Scanning</button><button id="stop-camera" class="button button-light" type="button" disabled>Stop</button><label class="button button-light" for="qr-image-file">↑ Upload QR Image<input id="qr-image-file" type="file" accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp" hidden></label></div><p class="muted">Use a well-lit image with the entire QR code visible. Camera access requires HTTPS, except on localhost.</p></div><p id="scan-status" class="scan-status" role="status" aria-live="polite">Ready. Start the camera or choose a QR image.</p><section id="scan-result" class="decoded" aria-live="polite" hidden><h3 id="result-heading"></h3><p id="signature-note" class="signature-note"></p><dl id="person-data" class="person-data"></dl><details id="raw-data" class="raw-data" hidden><summary>Show decoded text</summary><pre id="raw-text"></pre></details><div class="submit-row"><button id="copy-record" class="button button-light" type="button">Copy information</button><button id="scan-again" class="button button-primary" type="button">Scan another QR</button></div></section></div><aside><section class="panel-card scan-guide"><h2>How to scan</h2><div class="scan-guide-item"><span class="scan-step">1</span><div><strong>Enable your camera</strong><p class="muted">Click Start Scanning and allow camera permissions in your browser.</p></div></div><div class="scan-guide-item"><span class="scan-step">2</span><div><strong>Center the QR code</strong><p class="muted">Keep the entire code inside the frame. Good lighting helps.</p></div></div><div class="scan-guide-item"><span class="scan-step">3</span><div><strong>View the information</strong><p class="muted">Details appear when a code is recognized.</p></div></div></section><div class="privacy-callout"><strong>No camera?</strong><span>Upload a QR image. Camera feed and uploaded images are decoded locally and never sent to the server.</span></div></aside></div>
                    </section>

                    <section id="activity-panel" class="page-panel" role="tabpanel" aria-labelledby="activity-tab" hidden>
                        <div class="page-heading"><div><h1>Activity Logs</h1><p class="muted">Review QR and person updates recorded locally in this browser.</p></div><button id="export-activity" class="button button-light" type="button">↓ Export logs</button></div>
                        <section class="panel-card"><div class="section-heading"><div><h2>Local activity</h2><p class="muted">Activity details remain on this device.</p></div></div><div class="table-scroll"><table><thead><tr><th>Action</th><th>Person affected</th><th>Date &amp; time</th><th>Result</th></tr></thead><tbody id="local-activity-list"></tbody></table></div><div id="activity-empty" class="empty-state compact" hidden>No local activity has been recorded yet.</div></section>
                    </section>
                    <dialog id="person-details-dialog" class="person-dialog"><section class="panel-card"><div class="section-heading"><div><span id="person-details-status" class="badge badge-green">Active</span><h2 id="person-details-name"></h2><p id="person-details-subtitle" class="muted"></p></div><button class="button button-light" type="button" data-close-details aria-label="Close person details">×</button></div><dl id="person-detail-list" class="detail-grid"></dl><div class="form-submit"><button class="button button-primary" type="button" data-dialog-action="edit">Edit person</button><button class="button button-light" type="button" data-dialog-action="generate">Generate QR</button></div></section></dialog>
                    <p class="footer-note">Person data is stored only in this browser. QR version 1 payloads are schema-checked; no digital signature is implemented, so issuer identity is not verified.</p>
                </div>
                <footer class="page-footer">© {{ date('Y') }} QR Manager <span>Local browser storage · v1.0.0</span></footer>
            </div>
        </main>
    </div>
    <script src="{{ asset('js/zxing-browser.min.js') }}" defer></script>
    <script type="module" src="{{ asset('js/qr-local-people.js') }}?v={{ filemtime(public_path('js/qr-local-people.js')) }}"></script>
    <script src="{{ asset('js/qr-scanner.js') }}?v={{ filemtime(public_path('js/qr-scanner.js')) }}" defer></script>
    <script src="{{ asset('js/page-loading.js') }}?v={{ filemtime(public_path('js/page-loading.js')) }}" defer></script>
</body>
</html>
