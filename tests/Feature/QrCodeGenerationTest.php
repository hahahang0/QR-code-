<?php

use App\Services\QrCodeService;
use App\Services\QrPayloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Zxing\QrReader;

uses(RefreshDatabase::class);

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
        ->assertSee('Start Scanning')
        ->assertSee('Upload QR Image')
        ->assertSee('Older person QR codes are still recognized');
});

it('generates independently decodable URL QR codes with Q or H error correction', function (string $correction) {
    $payload = 'https://portfolio.example.test/profile/alice-example-ab12cd34';
    $images = app(QrCodeService::class)->generate($payload, $correction, 512);

    expect(independentlyDecodePngDataUri($images['png']))->toBe($payload)
        ->and($images['svg'])->toStartWith('data:image/svg+xml;base64,');
})->with(['Q', 'H']);

it('keeps the QR encoder UTF-8 compatible for legacy content and special characters', function () {
    $payload = '{"version":1,"type":"person","person":{"full_name":"Zoë 李 Sharma","address":"Café #2 — Kathmandu"}}';
    $images = app(QrCodeService::class)->generate($payload, 'Q', 512);

    expect(independentlyDecodePngDataUri($images['png']))->toBe($payload);
});

it('validates historical version 1 person payloads for backward compatibility', function () {
    $decoded = app(QrPayloadService::class)->validate('{"version":1,"type":"person","person":{"full_name":"Legacy Person","phone":"+1 555 0100"}}');

    expect($decoded['person'])->toBe(['full_name' => 'Legacy Person', 'phone' => '+1 555 0100']);
});

it('rejects malformed, unsupported, or invalid legacy schemas', function (string $payload) {
    expect(fn () => app(QrPayloadService::class)->validate($payload))->toThrow(InvalidArgumentException::class);
})->with([
    'invalid JSON' => '{broken',
    'unsupported version' => '{"version":2,"type":"person","person":{"full_name":"A"}}',
    'unsupported type' => '{"version":1,"type":"other","person":{"full_name":"A"}}',
    'missing name' => '{"version":1,"type":"person","person":{"email":"a@example.test"}}',
    'wrong data type' => '{"version":1,"type":"person","person":{"full_name":42}}',
    'unknown field' => '{"version":1,"type":"person","person":{"full_name":"A","admin":true}}',
]);
