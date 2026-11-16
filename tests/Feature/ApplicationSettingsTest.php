<?php

use App\Models\ActivityLog;
use App\Models\ApplicationSetting;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not persist settings, scanned records, or scan logs to the database', function () {
    $this->postJson('/scanner/events', [
        'outcome' => 'valid',
        'payload' => '{"person":{"full_name":"Private Name"}}',
    ])->assertNotFound();
    $this->get('/admin/settings')->assertNotFound();

    expect(ApplicationSetting::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0)
        ->and(Person::query()->count())->toBe(0);
});
