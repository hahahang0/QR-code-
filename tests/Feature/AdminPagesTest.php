<?php

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the database-backed management dashboard without workspace, preferences, or authentication UI', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Dashboard overview')
        ->assertSee('People Management')
        ->assertSee('QR Code Management')
        ->assertSee('Activity Logs')
        ->assertDontSee('My Workspace')
        ->assertDontSee('PREFERENCES')
        ->assertDontSee('Admin Profile')
        ->assertDontSee('Log out');
});

it('provides the scanner separately from the QR generator', function () {
    $this->get(route('scanner'))
        ->assertOk()
        ->assertSee('data-profile-origin')
        ->assertSee('Start Scanning')
        ->assertSee('Upload QR Image')
        ->assertSee('qr-scanner.js')
        ->assertDontSee('qr-local-people.js');
});

it('renders the add and edit portfolio forms without requiring an account', function () {
    $person = Person::factory()->create();

    $this->get(route('admin.people.create'))
        ->assertOk()
        ->assertSee('Personal Information')
        ->assertSee('Current and Previous Employment')
        ->assertSee('Education')
        ->assertSee('Projects and achievements')
        ->assertSee('Certifications and awards');

    $this->get(route('admin.people.edit', $person))
        ->assertOk()
        ->assertSee($person->full_name)
        ->assertSee('Public QR profile')
        ->assertSee('Save and Generate QR');
});
