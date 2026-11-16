<?php

use App\Services\QrPayloadService;
use Zxing\QrReader;

function independentlyDecodePngDataUri(string $dataUri): string
{
    expect($dataUri)->toStartWith('data:image/png;base64,');

    $pngBytes = base64_decode(substr($dataUri, strlen('data:image/png;base64,')), true);

    expect($pngBytes)->not->toBeFalse();
    expect(substr($pngBytes, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
    expect(getimagesizefromstring($pngBytes))->not->toBeFalse();

    $reader = new QrReader($pngBytes, QrReader::SOURCE_TYPE_BLOB);

    return (string) $reader->text();
}

it('opens the scanner from its dedicated route', function () {
    $this->get(route('scanner'))
        ->assertOk()
        ->assertViewHas('scannerOnly', true);
});

it('generates a compact schema v1 QR with standard error correction Q', function () {
    $response = $this->post(route('qr.generate'), [
        'person' => ['full_name' => 'Alice Example'],
        'error_correction' => 'Q',
    ]);

    $response->assertOk();
    $result = $response->viewData('result');
    $decodedPayload = independentlyDecodePngDataUri($result['png']);

    expect(json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR))->toBe([
        'version' => 1,
        'type' => 'person',
        'person' => ['full_name' => 'Alice Example'],
    ]);
    expect($result['errorCorrection'])->toBe('Q');
});

it('preserves Unicode and special characters through JSON encoding and independent decoding', function () {
    $response = $this->post(route('qr.generate'), [
        'person' => [
            'full_name' => 'Zoë 李 शर्मा',
            'address' => 'Unit 4, 5th Ave #9 — café \\"east\\"',
            'phone' => '+977 980-123-4567',
            'department' => 'Développement',
            'designation' => 'Ingénieure',
        ],
        'include' => ['address', 'phone', 'department', 'designation'],
        'error_correction' => 'Q',
    ]);

    $response->assertOk();
    $decoded = json_decode(independentlyDecodePngDataUri($response->viewData('result')['png']), true, 512, JSON_THROW_ON_ERROR);

    expect($decoded['person'])->toBe([
        'full_name' => 'Zoë 李 शर्मा',
        'phone' => '+977 980-123-4567',
        'address' => 'Unit 4, 5th Ave #9 — café \\"east\\"',
        'department' => 'Développement',
        'designation' => 'Ingénieure',
    ]);
});

it('generates and independently decodes a larger payload at the tested Q capacity', function () {
    $payloads = app(QrPayloadService::class);
    $sample = [
        'full_name' => 'Medium Sample',
        'notes' => 'R',
        'address' => str_repeat('A', 400),
        'organization' => str_repeat('O', 160),
        'email' => 'person@example.test',
        'phone' => '+1 555 0100',
    ];
    $includedFields = ['notes', 'address', 'organization', 'email', 'phone'];
    $payloadWithOneNoteByte = $payloads->create($sample, $includedFields);
    $notes = str_repeat('R', QrPayloadService::MAX_BYTES_Q - strlen($payloadWithOneNoteByte) + 1);
    $response = $this->post(route('qr.generate'), [
        'person' => [
            'full_name' => 'Medium Sample',
            ...$sample,
            'notes' => $notes,
        ],
        'include' => $includedFields,
        'error_correction' => 'Q',
    ]);

    $response->assertOk();
    $result = $response->viewData('result');
    $decodedPayload = independentlyDecodePngDataUri($result['png']);

    expect($decodedPayload)->toBe($result['payload']);
    $decoded = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
    expect($decoded['person']['notes'])->toBe($notes);
    expect($decoded['person']['address'])->toBe($sample['address']);
    expect(strlen($result['payload']))->toBe(QrPayloadService::MAX_BYTES_Q);
    expect($result['errorCorrection'])->toBe('Q');
});

it('generates independently readable H codes for compact payloads', function () {
    $response = $this->post(route('qr.generate'), [
        'person' => [
            'full_name' => 'Taylor Example',
            'phone' => '+1 555 0100',
        ],
        'include' => ['phone'],
        'error_correction' => 'H',
    ]);

    $response->assertOk();
    $result = $response->viewData('result');
    $decodedPayload = independentlyDecodePngDataUri($result['png']);

    expect($decodedPayload)->toBe($result['payload']);
    expect(strlen($result['payload']))->toBeLessThanOrEqual(QrPayloadService::MAX_BYTES_H);
    expect($result['errorCorrection'])->toBe('H');
});

it('does not include optional fields unless selected', function () {
    $response = $this->post(route('qr.generate'), [
        'person' => [
            'full_name' => 'Taylor Example',
            'phone' => '+1 555 0100',
            'address' => 'Private address',
        ],
        'include' => ['phone'],
        'error_correction' => 'Q',
    ]);

    $response->assertOk();
    $decodedPayload = independentlyDecodePngDataUri($response->viewData('result')['png']);
    expect($decodedPayload)->toBe($response->viewData('result')['payload']);
    $decoded = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded['person'])->toBe([
        'full_name' => 'Taylor Example',
        'phone' => '+1 555 0100',
    ]);
});

it('rejects unsupported correction values and overlong payloads without truncating', function () {
    $response = $this->from('/')->post(route('qr.generate'), [
        'person' => [
            'full_name' => 'Long Payload Example',
            'phone' => str_repeat('1', 40),
            'email' => 'person@example.test',
            'address' => str_repeat('A', 500),
            'organization' => str_repeat('B', 160),
            'reference_number' => str_repeat('C', 80),
            'emergency_contact' => str_repeat('2', 80),
            'notes' => str_repeat('D', 800),
        ],
        'include' => ['phone', 'email', 'address', 'organization', 'reference_number', 'emergency_contact', 'notes'],
        'error_correction' => 'Q',
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors('person');
    expect(session('errors')->first('person'))->toContain('No information was truncated');
});

it('rejects malformed or unsupported schema payloads', function (string $payload) {
    $service = app(QrPayloadService::class);

    expect(fn () => $service->validate($payload))->toThrow(InvalidArgumentException::class);
})->with([
    'invalid JSON' => '{broken',
    'unsupported version' => '{"version":2,"type":"person","person":{"full_name":"A"}}',
    'unsupported type' => '{"version":1,"type":"other","person":{"full_name":"A"}}',
    'missing name' => '{"version":1,"type":"person","person":{"email":"a@example.test"}}',
    'wrong data type' => '{"version":1,"type":"person","person":{"full_name":42}}',
    'unknown field' => '{"version":1,"type":"person","person":{"full_name":"A","admin":true}}',
]);
