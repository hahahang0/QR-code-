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
    ) {}

    /** @param list<string> $includedFields */
    public function generate(Person $person, User $actor, array $includedFields, string $errorCorrection = 'Q'): QrCodeRecord
    {
        $payload = $this->payloadService->create($person->getAttributes(), $includedFields);
        $this->payloadService->assertWithinCapacity($payload, $errorCorrection);

        return DB::transaction(function () use ($person, $actor, $includedFields, $errorCorrection, $payload): QrCodeRecord {
            $person->qrCodes()->where('is_current', true)->update(['is_current' => false]);

            return $person->qrCodes()->create([
                'generated_by' => $actor->id,
                'schema_version' => 1,
                'payload' => $payload,
                'included_fields' => $includedFields,
                'error_correction' => $errorCorrection,
                'is_current' => true,
            ]);
        });
    }

    /** @return array{svg: string, png: string} */
    public function render(QrCodeRecord $qrCode): array
    {
        $this->payloadService->validate($qrCode->payload);

        return $this->qrCodeService->generate($qrCode->payload, $qrCode->error_correction, $this->settings->defaultImageSize());
    }
}
