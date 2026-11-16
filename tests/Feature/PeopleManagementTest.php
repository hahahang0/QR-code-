<?php

use App\Models\ActivityLog;
use App\Models\Person;
use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates QR output without persisting people, QR records, or activities', function () {
    $this->postJson(route('qr.generate'), [
        'person' => [
            'full_name' => 'Zoë 李',
            'email' => 'zoe@example.test',
            'phone' => '+977 980 123 4567',
        ],
        'include' => ['email', 'phone'],
        'error_correction' => 'Q',
    ])->assertOk()
        ->assertJsonPath('errorCorrection', 'Q')
        ->assertJsonPath('person.full_name', 'Zoë 李');

    expect(Person::query()->count())->toBe(0)
        ->and(QrCode::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});

it('does not expose database-backed people create, update, or delete routes', function () {
    $this->get('/admin/people')->assertNotFound();
    $this->post('/admin/people', ['full_name' => 'Not stored'])->assertNotFound();
});
