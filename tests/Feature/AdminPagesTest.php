<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the local QR Manager workspace and browser storage controls', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('People saved here')
        ->assertSee('Keep this person in this browser')
        ->assertSee('Export records')
        ->assertSee('Import records')
        ->assertSee('qr-local-people.js');
});

it('provides the scanner separately from the QR generator', function () {
    $this->get(route('scanner'))
        ->assertOk()
        ->assertViewHas('scannerOnly', true)
        ->assertSee('Start camera')
        ->assertSee('Decode an image');
});
