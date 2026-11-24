<?php

namespace App\Services;

use App\Models\Person;
use App\Models\QrCode as QrCodeRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QrRecordService
{
    public function __construct(
        private readonly QrPayloadService $payloadService,
        private readonly QrCodeService $qrCodeService,
        private readonly ApplicationSettingsService $settings,
        private readonly PublicProfileUrlService $profileUrls,
    ) {}

    public function generate(Person $person, ?User $actor, string $errorCorrection = 'Q'): QrCodeRecord
    {
        $payload = $this->profileUrls->for($person);

        return DB::transaction(function () use ($person, $actor, $errorCorrection, $payload): QrCodeRecord {
            $person->qrCodes()->where('is_current', true)->update(['is_current' => false]);

            return $person->qrCodes()->create([
                'generated_by' => $actor?->id,
                'schema_version' => 2,
                'payload' => $payload,
                'included_fields' => [],
                'error_correction' => $errorCorrection,
                'is_current' => true,
            ]);
        });
    }

    /** @return array{svg: string, png: string} */
    public function render(QrCodeRecord $qrCode): array
    {
        if ($qrCode->schema_version === 1) {
            $this->payloadService->validate($qrCode->payload);
        } elseif (
            filter_var($qrCode->payload, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($qrCode->payload, PHP_URL_SCHEME)), ['http', 'https'], true)
        ) {
            throw new \InvalidArgumentException('This QR code does not contain a valid public profile URL.');
        }

        return $this->qrCodeService->generate($qrCode->payload, $qrCode->error_correction, $this->settings->defaultImageSize());
    }
}
