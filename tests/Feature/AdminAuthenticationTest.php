<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provides the QR tools without requiring an administrator login', function () {
    $this->get('/')->assertOk()->assertSee('QR Manager');
    $this->get('/scanner')->assertOk()->assertViewHas('scannerOnly', true);
});

it('does not expose the old administrator or sign-in screens', function () {
    $this->get('/login')->assertRedirect('/');
    $this->get('/admin')->assertRedirect('/');
    $this->get('/admin/people')->assertNotFound();
});
