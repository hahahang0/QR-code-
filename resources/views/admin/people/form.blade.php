@extends('admin.layouts.app')
@php($isEditing = isset($person))
@section('title', $isEditing ? 'Edit Person' : 'Add New Person')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PEOPLE MANAGEMENT</p><h1>{{ $isEditing ? 'Edit Person' : 'Register New Person' }}</h1><p class="muted">{{ $isEditing ? 'Update the person’s information and QR preferences.' : 'Add a new person to your workspace and choose the information to share.' }}</p></div><a class="button button-light" href="{{ $isEditing ? route('admin.people.show', $person) : route('admin.people.index') }}">← Back</a></div>
<form method="post" enctype="multipart/form-data" action="{{ $isEditing ? route('admin.people.update', $person) : route('admin.people.store') }}">
    @csrf @if ($isEditing) @method('put') @endif
    <div class="form-layout"><div class="form-main">
        <section class="panel-card"><div class="section-heading"><div><h2>Personal information</h2><p class="muted">Basic identity details for this record.</p></div></div>
            <div class="form-grid">
                <div class="field full"><label for="full_name">Full name <em>*</em></label><input id="full_name" name="full_name" value="{{ old('full_name', $person->full_name ?? '') }}" maxlength="120" autocomplete="name" required></div>
                <div class="field"><label for="reference_number">Person ID <small>(optional)</small></label><input id="reference_number" name="reference_number" value="{{ old('reference_number', $person->reference_number ?? '') }}" maxlength="80" placeholder="e.g. QRM-7958614"></div>
                <div class="field"><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', isset($person) ? $person->date_of_birth?->format('Y-m-d') : '') }}"></div>
                <div class="field"><label for="gender">Gender</label><select id="gender" name="gender"><option value="">Select gender</option>@foreach (['Female', 'Male', 'Non-binary', 'Prefer not to say'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $person->gender ?? '') === $gender)>{{ $gender }}</option>@endforeach</select></div>
                <div class="field"><label for="profile_photo">Profile photograph</label><input id="profile_photo" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp"><small>JPG, PNG, or WEBP · up to 5 MB</small></div>
            </div>
        </section>
        <section class="panel-card"><div class="section-heading"><div><h2>Contact information</h2><p class="muted">Contact details are optional and can be selected for QR sharing.</p></div></div><div class="form-grid">
            <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email', $person->email ?? '') }}" maxlength="254" autocomplete="email"></div>
            <div class="field"><label for="phone">Phone number</label><input id="phone" type="tel" name="phone" value="{{ old('phone', $person->phone ?? '') }}" maxlength="40" autocomplete="tel"></div>
            <div class="field full"><label for="address">Residential address</label><textarea id="address" name="address" maxlength="500" rows="3">{{ old('address', $person->address ?? '') }}</textarea></div>
            <div class="field full"><label for="emergency_contact">Emergency contact</label><input id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', $person->emergency_contact ?? '') }}" maxlength="80"></div>
        </div></section>
        <section class="panel-card"><div class="section-heading"><div><h2>Organization information</h2><p class="muted">Optional role and organization details.</p></div></div><div class="form-grid">
            <div class="field"><label for="organization">Organization</label><input id="organization" name="organization" value="{{ old('organization', $person->organization ?? '') }}" maxlength="160"></div>
            <div class="field"><label for="department">Department</label><input id="department" name="department" value="{{ old('department', $person->department ?? '') }}" maxlength="120"></div>
            <div class="field"><label for="designation">Designation</label><input id="designation" name="designation" value="{{ old('designation', $person->designation ?? '') }}" maxlength="120"></div>
            <div class="field"><label for="status">Record status</label><select id="status" name="status"><option value="active" @selected(old('status', $person->status ?? 'active') === 'active')>Active</option><option value="archived" @selected(old('status', $person->status ?? '') === 'archived')>Inactive</option></select></div>
            <div class="field full"><label for="notes">Additional notes</label><textarea id="notes" name="notes" maxlength="800" rows="3">{{ old('notes', $person->notes ?? '') }}</textarea></div>
        </div></section>
    </div><aside class="form-side"><section class="panel-card qr-config"><span class="stat-icon violet">▦</span><h2>QR code configuration</h2><p class="muted">Choose the fields that should be embedded directly in the QR code. Anyone who scans it can read these details.</p>
        <div class="checkbox-list"><strong>Information to include</strong>@php($selected = old('qr_include', isset($person) ? ($person->currentQrCode?->included_fields ?? ['phone', 'email', 'organization']) : ['phone', 'email', 'organization']))@if ($isEditing)<input type="hidden" name="qr_include_present" value="1">@endif @foreach ([['phone','Phone number'],['email','Email address'],['organization','Organization'],['department','Department'],['designation','Designation'],['date_of_birth','Date of birth'],['gender','Gender'],['address','Address'],['emergency_contact','Emergency contact'],['reference_number','Person ID'],['notes','Additional notes']] as [$key,$label])<label><input type="checkbox" name="qr_include[]" value="{{ $key }}" @checked(in_array($key, $selected, true))>{{ $label }}</label>@endforeach</div>
        <div class="field"><label for="qr_error_correction">Error correction</label><select id="qr_error_correction" name="qr_error_correction"><option value="Q" @selected(old('qr_error_correction', $person->currentQrCode?->error_correction ?? $defaultErrorCorrection ?? 'Q') === 'Q')>Q · Recommended (25%)</option><option value="H" @selected(old('qr_error_correction', $person->currentQrCode?->error_correction ?? '') === 'H')>H · Maximum recovery (30%)</option></select></div>
        @unless ($isEditing)<label class="check-row"><input type="checkbox" name="generate_qr" value="1" @checked(old('generate_qr'))> Generate a QR code now</label>@else<p class="helper-note">Changed fields regenerate a new version automatically. Use the button below to create a QR code if none exists.</p>@endunless
        <div class="privacy-callout"><strong>Privacy reminder</strong><span>QR codes are not encrypted. Do not include passwords or sensitive information.</span></div>
    </section><div class="form-submit"><a class="button button-light" href="{{ $isEditing ? route('admin.people.show', $person) : route('admin.people.index') }}">Cancel</a><button class="button button-primary" type="submit">{{ $isEditing ? 'Save changes' : 'Save Person' }}</button><button class="button button-outline" type="submit" name="generate_qr" value="1">Save and Generate QR</button></div></aside></div>
</form>
@endsection
