<?php

use App\Enums\UserRole;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users are redirected to the dashboard after login', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
})->with([
    'administrator' => UserRole::Administrator,
    'investigator' => UserRole::Investigator,
    'police officer' => UserRole::PoliceOfficer,
]);

test('deactivated users cannot log in', function () {
    $user = User::factory()->create(['name' => 'Deactivated Account']);
    $user->forceFill(['is_active' => false])->save();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors([
        'email' => 'This account has been deactivated. Contact an administrator.',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
