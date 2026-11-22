<?php

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use App\Models\OfficerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function userManagementAdmin(): User
{
    $user = User::factory()->create(['name' => 'Management Administrator']);
    $user->forceFill(['role' => UserRole::Administrator])->save();

    return $user;
}

function userManagementUser(UserRole $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function validUserManagementPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Managed Account',
        'email' => 'managed-'.fake()->unique()->numberBetween(10000, 99999).'@example.com',
        'password' => 'temporary-password',
        'role' => UserRole::PoliceOfficer->value,
        'badge_number' => 'BADGE-'.fake()->unique()->numberBetween(10000, 99999),
        'rank' => 'Sergeant',
        'department' => 'Investigation',
        'police_station' => 'Central Police Station',
        'phone' => '555-0100',
        'employment_status' => EmploymentStatus::Active->value,
    ], $overrides);
}

test('guests are redirected to login from user management', function () {
    $this->get(route('users.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('police officers and investigators cannot access user management', function (UserRole $role) {
    $actor = userManagementUser($role);
    $target = userManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($actor)
        ->get(route('users.index'))
        ->assertForbidden();

    $this->actingAs($actor)
        ->get(route('users.create'))
        ->assertForbidden();

    $this->actingAs($actor)
        ->get(route('users.edit', $target))
        ->assertForbidden();

    $this->actingAs($actor)
        ->post(route('users.store'), validUserManagementPayload())
        ->assertForbidden();

    $this->actingAs($actor)
        ->patch(route('users.update', $target), [])
        ->assertForbidden();

    $this->actingAs($actor)
        ->patch(route('users.deactivate', $target))
        ->assertForbidden();

    $this->actingAs($actor)
        ->patch(route('users.activate', $target))
        ->assertForbidden();

    expect($target->fresh()->is_active)->toBeTrue();
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('administrator can search and filter user accounts', function () {
    $admin = userManagementAdmin();
    $investigator = userManagementUser(UserRole::Investigator, ['name' => 'Alice Investigator']);
    $inactiveOfficer = userManagementUser(UserRole::PoliceOfficer, ['name' => 'Bob Officer']);
    $inactiveOfficer->forceFill(['is_active' => false])->save();

    $response = $this->actingAs($admin)->get(route('users.index', [
        'search' => 'Alice',
        'role' => UserRole::Investigator->value,
        'status' => 'active',
    ]));

    $response->assertOk()
        ->assertViewIs('users.index')
        ->assertSee('Alice Investigator')
        ->assertDontSee('Bob Officer');

    $userIds = $response->viewData('users')->getCollection()->pluck('id');

    expect($userIds)
        ->toContain($investigator->id)
        ->not->toContain($inactiveOfficer->id)
        ->not->toContain($admin->id);
});

test('administrator can create a user with the selected role and matching officer profile', function (UserRole $role, bool $expectsProfile) {
    $admin = userManagementAdmin();
    $payload = validUserManagementPayload([
        'name' => 'Role Target',
        'role' => $role->value,
    ]);

    $this->actingAs($admin)
        ->post(route('users.store'), $payload)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-created');

    $createdUser = User::where('email', $payload['email'])->firstOrFail();

    $this->assertDatabaseHas('users', [
        'id' => $createdUser->id,
        'name' => 'Role Target',
        'email' => $payload['email'],
        'role' => $role->value,
        'is_active' => true,
    ]);

    expect(Hash::check('temporary-password', $createdUser->password))->toBeTrue()
        ->and($createdUser->officerProfile()->exists())->toBe($expectsProfile);

    if ($expectsProfile) {
        $this->assertDatabaseHas('officer_profiles', [
            'user_id' => $createdUser->id,
            'badge_number' => $payload['badge_number'],
            'rank' => 'Sergeant',
            'employment_status' => EmploymentStatus::Active->value,
        ]);
    }
})->with([
    'administrator' => [UserRole::Administrator, false],
    'police officer' => [UserRole::PoliceOfficer, true],
    'investigator' => [UserRole::Investigator, true],
]);

test('invalid user details are rejected without creating an account', function () {
    $admin = userManagementAdmin();
    $existingUser = userManagementUser(UserRole::PoliceOfficer);
    $existingProfile = OfficerProfile::factory()->create(['user_id' => $existingUser->id]);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'role' => 'superuser',
            'badge_number' => $existingProfile->badge_number,
        ])
        ->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'role',
            'badge_number',
        ]);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseCount('officer_profiles', 1);
});

test('duplicate email addresses are rejected', function () {
    $admin = userManagementAdmin();
    $existingUser = userManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($admin)
        ->post(route('users.store'), validUserManagementPayload([
            'email' => $existingUser->email,
        ]))
        ->assertSessionHasErrors('email');

    $this->assertDatabaseCount('users', 2);
});

test('administrator can update account details and password', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer, [
        'name' => 'Before Update',
        'email' => 'before-update@example.com',
    ]);

    $this->actingAs($admin)
        ->patch(route('users.update', $target), [
            'name' => 'After Update',
            'email' => 'after-update@example.com',
            'password' => 'replacement-password',
            'role' => UserRole::Investigator->value,
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-updated');

    $freshTarget = $target->fresh();

    expect($freshTarget->name)->toBe('After Update')
        ->and($freshTarget->email)->toBe('after-update@example.com')
        ->and($freshTarget->role)->toBe(UserRole::Investigator)
        ->and(Hash::check('replacement-password', $freshTarget->password))->toBeTrue();
});

test('updating an account may keep its current email address', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer, ['email' => 'keep-me@example.com']);

    $this->actingAs($admin)
        ->patch(route('users.update', $target), [
            'name' => 'Renamed Officer',
            'email' => $target->email,
            'role' => $target->role->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->name)->toBe('Renamed Officer');
});

test('administrators cannot deactivate their own account', function () {
    $admin = userManagementAdmin();

    $this->actingAs($admin)
        ->patch(route('users.deactivate', $admin))
        ->assertSessionHasErrors('is_active');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('the last active administrator cannot be demoted', function () {
    $admin = userManagementAdmin();

    $this->actingAs($admin)
        ->patch(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::PoliceOfficer->value,
        ])
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe(UserRole::Administrator);
});

test('the last active administrator cannot be deactivated', function () {
    $admin = userManagementAdmin();
    $inactiveAdmin = userManagementUser(UserRole::Administrator);
    $inactiveAdmin->forceFill(['is_active' => false])->save();

    $this->actingAs($inactiveAdmin)
        ->patch(route('users.deactivate', $admin))
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('deactivation blocks login and reactivation restores it', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer, ['email' => 'login-target@example.com']);

    $this->actingAs($admin)
        ->patch(route('users.deactivate', $target))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-deactivated');

    expect($target->fresh()->is_active)->toBeFalse();

    $this->post(route('logout'))->assertRedirect('/');

    $this->post('/login', [
        'email' => $target->email,
        'password' => 'password',
    ])
        ->assertSessionHasErrors([
            'email' => 'This account has been deactivated. Contact an administrator.',
        ]);

    $this->assertGuest();

    $this->actingAs($admin)
        ->patch(route('users.activate', $target))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-activated');

    $this->post(route('logout'))->assertRedirect('/');

    $this->post('/login', [
        'email' => $target->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($target->fresh());
});

test('user list includes a confirmation prompt before deactivation', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('action="'.route('users.deactivate', $target).'"', false)
        ->assertSee("onsubmit=\"return confirm('Deactivate this account? The user will no longer be able to log in.');\"", false);
});
