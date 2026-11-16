<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdministratorProfileRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Services\ActivityLogService;
use App\Services\ApplicationSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function edit(ApplicationSettingsService $settings): View
    {
        return view('admin.settings', ['defaultErrorCorrection' => $settings->defaultErrorCorrection(), 'defaultImageSize' => $settings->defaultImageSize()]);
    }

    public function update(UpdateSettingsRequest $request, ApplicationSettingsService $settings, ActivityLogService $activityLogs): RedirectResponse
    {
        $values = $request->validated();
        $settings->updateQrConfiguration($values['default_error_correction'], (int) ($values['default_qr_image_size'] ?? $settings->defaultImageSize()), $request->user());
        $activityLogs->record('settings.updated', $request, metadata: ['default_error_correction' => $values['default_error_correction'], 'default_qr_image_size' => $values['default_qr_image_size'] ?? $settings->defaultImageSize()]);

        return back()->with('status', 'Settings saved successfully.');
    }

    public function updateProfile(UpdateAdministratorProfileRequest $request, ActivityLogService $activityLogs): RedirectResponse
    {
        $request->user()->update($request->validated());
        $activityLogs->record('administrator.profile_updated', $request);

        return back()->with('status', 'Administrator profile updated successfully.');
    }
}
