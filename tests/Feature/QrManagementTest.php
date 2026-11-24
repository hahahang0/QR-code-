<?php

use App\Models\Person;
use App\Models\QrCode as QrCodeRecord;
use App\Services\QrRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists generated profile URL QR codes in the management screen', function () {
    $person = Person::factory()->create();
    $qrCode = app(QrRecordService::class)->generate($person, null);

    $this->get(route('admin.qr-codes.index'))
        ->assertOk()
        ->assertSee($person->full_name)
        ->assertSee('Q · v2');

    expect(QrCodeRecord::query()->count())->toBe(1)
        ->and($qrCode->payload)->toContain('/profile/'.$person->public_slug);
});

it('exposes working PNG and SVG downloads for an unauthenticated administrator', function () {
    $person = Person::factory()->create();
    $qrCode = app(QrRecordService::class)->generate($person, null);

    $this->get(route('admin.qr-codes.download', [$qrCode, 'png']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
    $this->get(route('admin.qr-codes.download', [$qrCode, 'svg']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');
});
