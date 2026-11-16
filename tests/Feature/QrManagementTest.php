<?php

use App\Models\QrCode as QrCodeRecord;
use App\Services\QrPayloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Zxing\QrReader;

uses(RefreshDatabase::class);

it('returns independently decodable PNG and SVG output without a QR database record', function () {
    $response = $this->postJson(route('qr.generate'), [
        'person' => [
            'full_name' => 'Zoë 李 शर्मा',
            'phone' => '+977-980-123-4567',
            'address' => 'Café #2 — Kathmandu',
            'department' => 'R&D',
            'designation' => 'Staff Engineer',
        ],
        'include' => ['phone', 'address', 'department', 'designation'],
        'error_correction' => 'Q',
    ])->assertOk()->assertHeader('Content-Type', 'application/json');

    $result = $response->json();
    $png = base64_decode(substr($result['png'], strlen('data:image/png;base64,')), true);
    $reader = new QrReader($png, QrReader::SOURCE_TYPE_BLOB);

    expect($reader->text())->toBe($result['payload'])
        ->and($result['person']['department'])->toBe('R&D')
        ->and($result['person']['designation'])->toBe('Staff Engineer')
        ->and($result['svg'])->toStartWith('data:image/svg+xml;base64,')
        ->and(QrCodeRecord::query()->count())->toBe(0);
});

it('keeps QR version and payload validation active without database-backed history', function () {
    $payloadService = app(QrPayloadService::class);
    expect(fn () => $payloadService->validate('{broken'))->toThrow(InvalidArgumentException::class);
    $this->get('/admin/qr-codes')->assertNotFound();
    expect(QrCodeRecord::query()->count())->toBe(0);
});
