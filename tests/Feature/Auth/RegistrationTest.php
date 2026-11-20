<?php

test('registration screen is not available', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('registration request does not create a user or authenticate the visitor', function () {
    $email = 'test@example.com';

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'administrator',
    ]);

    $response->assertNotFound();

    $this->assertGuest();

    $this->assertDatabaseMissing('users', [
        'email' => $email,
    ]);
});
