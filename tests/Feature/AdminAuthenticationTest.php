<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provides database management and scanning without requiring an administrator login', function () {
    $this->get('/')->assertOk()->assertSee('QR Manager');
    $this->get('/admin/people')->assertOk()->assertSee('People Management');
    $this->get('/scanner')->assertOk()->assertSee('Start Scanning');
});

it('does not expose the old administrator or sign-in screens', function () {
    $this->get('/login')->assertNotFound();
    $this->get('/admin')->assertRedirect('/');
});
