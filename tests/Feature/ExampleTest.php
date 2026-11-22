<?php

use App\Enums\UserRole;
use App\Models\User;

it('redirects guests to login', function () {
    $response = $this->get('/');

    $response->assertRedirectToRoute('login');

    $this->assertGuest();
});

it('renders the dashboard for authenticated users', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::PoliceOfficer])->save();

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();

    $response->assertViewIs('dashboard');
});
