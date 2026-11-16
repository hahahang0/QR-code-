<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplicationSettingsService
{
    public function defaultErrorCorrection(): string
    {
        return ApplicationSetting::query()->whereKey('default_error_correction')->value('value') ?? 'Q';
    }

    public function defaultImageSize(): int
    {
        $value = (int) (ApplicationSetting::query()->whereKey('default_qr_image_size')->value('value') ?? 512);

        return in_array($value, [512, 1024, 2048], true) ? $value : 512;
    }

    public function updateDefaultErrorCorrection(string $errorCorrection, User $actor): void
    {
        DB::transaction(function () use ($errorCorrection, $actor): void {
            ApplicationSetting::query()->updateOrCreate(
                ['key' => 'default_error_correction'],
                ['value' => $errorCorrection, 'updated_by' => $actor->id],
            );
        });
    }

    public function updateQrConfiguration(string $errorCorrection, int $imageSize, User $actor): void
    {
        DB::transaction(function () use ($errorCorrection, $imageSize, $actor): void {
            foreach (['default_error_correction' => $errorCorrection, 'default_qr_image_size' => (string) $imageSize] as $key => $value) {
                ApplicationSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $actor->id]);
            }
        });
    }
}
