<?php

use App\Models\CrimeCase;
use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user without associated cases can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('account deletion is rejected when the user created a case', function () {
    $user = User::factory()->create();

    CrimeCase::factory()->withCreator($user)->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', [
            'account_deletion' => 'This account cannot be deleted while it has associated crime cases.',
        ])
        ->assertRedirect('/profile');

    $this->assertAuthenticatedAs($user);
});

test('account deletion is rejected when the user is assigned to a case', function () {
    $user = User::factory()->create();

    CrimeCase::factory()->assignedTo($user)->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', [
            'account_deletion' => 'This account cannot be deleted while it has associated crime cases.',
        ])
        ->assertRedirect('/profile');

    $this->assertAuthenticatedAs($user);
});

test('rejected deletion leaves the user and case relationships unchanged', function () {
    $user = User::factory()->create();
    $case = CrimeCase::factory()->withCreator($user)->assignedTo($user)->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'account_deletion')
        ->assertRedirect('/profile');

    $this->assertAuthenticatedAs($user);
    $this->assertModelExists($user);
    $this->assertModelExists($case);
    $this->assertDatabaseHas('cases', [
        'id' => $case->id,
        'created_by' => $user->id,
        'investigator_id' => $user->id,
    ]);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
