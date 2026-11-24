<?php

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates and persists a person record through the management form without authentication', function () {
    $this->post(route('admin.people.store'), [
        'full_name' => 'Zoë 李',
        'email' => 'zoe@example.test',
        'phone' => '+977 980 123 4567',
    ])->assertRedirect();

    expect(Person::query()->count())->toBe(1)
        ->and(Person::query()->firstOrFail()->public_slug)->not->toBeEmpty();
});

it('does not expose the obsolete direct JSON QR generation endpoint', function () {
    $this->post('/qr/generate', ['person' => ['full_name' => 'Not stored']])->assertNotFound();
});

it('searches actual people records in the management table', function () {
    Person::factory()->create(['full_name' => 'Searchable Engineer']);
    Person::factory()->create(['full_name' => 'Other Record']);

    $this->get(route('admin.people.index', ['search' => 'Searchable']))
        ->assertOk()
        ->assertSee('Searchable Engineer')
        ->assertDontSee('Other Record');
});
