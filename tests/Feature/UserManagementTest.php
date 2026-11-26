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
    UserRole::Judge,
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
    'judge' => [UserRole::Judge, false],
]);

test('administrator can create a judge without an officer profile', function () {
    $admin = userManagementAdmin();
    $payload = validUserManagementPayload([
        'name' => 'Court Judge',
        'role' => UserRole::Judge->value,
    ]);

    $this->actingAs($admin)
        ->post(route('users.store'), $payload)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-created')
        ->assertSessionHasNoErrors();

    $judge = User::where('email', $payload['email'])->firstOrFail();

    $this->assertDatabaseHas('users', [
        'id' => $judge->id,
        'role' => UserRole::Judge->value,
        'is_active' => true,
    ]);
    $this->assertDatabaseMissing('officer_profiles', ['user_id' => $judge->id]);

    expect($judge->role)->toBe(UserRole::Judge)
        ->and($judge->officerProfile()->exists())->toBeFalse();
});

test('non-administrators cannot create judge accounts', function (UserRole $role) {
    $actor = userManagementUser($role);
    $payload = validUserManagementPayload([
        'name' => 'Unauthorized Judge',
        'role' => UserRole::Judge->value,
    ]);

    $this->actingAs($actor)
        ->post(route('users.store'), $payload)
        ->assertForbidden();

    $this->assertDatabaseCount('users', 1)
        ->assertDatabaseMissing('users', ['email' => $payload['email']]);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
    UserRole::Judge,
]);

test('non-administrators cannot change a user role to judge', function (UserRole $role) {
    $actor = userManagementUser($role);
    $target = userManagementUser(UserRole::PoliceOfficer, ['email' => 'role-change-target@example.com']);

    $this->actingAs($actor)
        ->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => UserRole::Judge->value,
        ])
        ->assertForbidden();

    expect($target->fresh()->role)->toBe(UserRole::PoliceOfficer);

    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'role' => UserRole::PoliceOfficer->value,
    ]);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
    UserRole::Judge,
]);

test('administrator can change a user role to judge', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::Administrator);

    $this->actingAs($admin)
        ->patch(route('users.update', $target), [
            'name' => 'Assigned Judge',
            'email' => 'assigned-judge@example.com',
            'role' => UserRole::Judge->value,
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-updated')
        ->assertSessionHasNoErrors();

    $freshTarget = $target->fresh();

    expect($freshTarget->role)->toBe(UserRole::Judge)
        ->and($freshTarget->officerProfile()->exists())->toBeFalse();
});

test('changing an officer to judge archives the officer profile', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer);
    $profile = OfficerProfile::factory()->create([
        'user_id' => $target->id,
        'badge_number' => 'BADGE-ARCHIVED',
        'rank' => 'Inspector',
        'department' => 'Patrol',
        'police_station' => 'Central Police Station',
        'phone' => '555-0199',
        'employment_status' => EmploymentStatus::Active,
    ]);

    $this->actingAs($admin)
        ->patch(route('users.update', $target), [
            'name' => 'Former Officer Judge',
            'email' => 'former-officer-judge@example.com',
            'role' => UserRole::Judge->value,
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-updated')
        ->assertSessionHasNoErrors();

    expect($target->fresh()->role)->toBe(UserRole::Judge)
        ->and($target->officerProfile()->exists())->toBeFalse()
        ->and($target->archivedOfficerProfile()->exists())->toBeTrue();

    $archivedProfile = OfficerProfile::withTrashed()->findOrFail($profile->id);

    $this->assertSoftDeleted($archivedProfile)
        ->assertDatabaseHas('officer_profiles', [
            'id' => $profile->id,
            'user_id' => $target->id,
            'badge_number' => 'BADGE-ARCHIVED',
            'rank' => 'Inspector',
            'department' => 'Patrol',
            'police_station' => 'Central Police Station',
            'phone' => '555-0199',
            'employment_status' => EmploymentStatus::Active->value,
        ]);
});

test('returning a judge to an officer role restores the archived profile', function (UserRole $role) {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::Judge);
    $profile = OfficerProfile::factory()->create([
        'user_id' => $target->id,
        'badge_number' => 'BADGE-RESTORED',
        'rank' => 'Sergeant',
        'department' => 'Investigation',
        'police_station' => 'West Police Station',
        'phone' => '555-0188',
        'employment_status' => EmploymentStatus::Active,
    ]);
    $profile->delete();

    expect($target->officerProfile()->exists())->toBeFalse()
        ->and($target->archivedOfficerProfile()->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('users.update', $target), [
            'name' => 'Restored Officer',
            'email' => 'restored-officer@example.com',
            'role' => $role->value,
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-updated')
        ->assertSessionHasNoErrors();

    $freshTarget = $target->fresh();
    $restoredProfile = $freshTarget->officerProfile;

    expect($freshTarget->role)->toBe($role)
        ->and($restoredProfile)->not->toBeNull()
        ->and($restoredProfile->is($profile))->toBeTrue()
        ->and($restoredProfile->trashed())->toBeFalse()
        ->and($restoredProfile->badge_number)->toBe('BADGE-RESTORED')
        ->and($restoredProfile->rank)->toBe('Sergeant')
        ->and($restoredProfile->department)->toBe('Investigation')
        ->and($restoredProfile->police_station)->toBe('West Police Station')
        ->and($restoredProfile->phone)->toBe('555-0188')
        ->and($restoredProfile->employment_status)->toBe(EmploymentStatus::Active);

    $this->assertNotSoftDeleted($restoredProfile)
        ->assertDatabaseHas('officer_profiles', [
            'id' => $profile->id,
            'user_id' => $target->id,
            'badge_number' => 'BADGE-RESTORED',
        ])
        ->assertDatabaseCount('officer_profiles', 1);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('repeated judge role changes preserve one officer profile', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer);
    $profile = OfficerProfile::factory()->create([
        'user_id' => $target->id,
        'badge_number' => 'BADGE-CYCLE',
    ]);

    foreach ([UserRole::Judge, UserRole::PoliceOfficer, UserRole::Judge, UserRole::PoliceOfficer] as $role) {
        $this->actingAs($admin)
            ->patch(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => $role->value,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('status', 'user-updated')
            ->assertSessionHasNoErrors();

        expect($target->fresh()->role)->toBe($role)
            ->and(OfficerProfile::withTrashed()->where('user_id', $target->id)->count())->toBe(1);
    }

    $profiles = OfficerProfile::withTrashed()->where('user_id', $target->id)->get();
    $activeProfile = $target->fresh()->officerProfile;

    expect($profiles)->toHaveCount(1)
        ->and($profiles->first()->id)->toBe($profile->id)
        ->and($activeProfile?->id)->toBe($profile->id)
        ->and($activeProfile?->trashed())->toBeFalse();

    $this->assertDatabaseCount('officer_profiles', 1)
        ->assertNotSoftDeleted($profiles->first());
});

test('officer profile remains intact when changing between non-judge roles', function () {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer);
    $profile = OfficerProfile::factory()->create(['user_id' => $target->id]);

    $this->actingAs($admin)
        ->patch(route('users.update', $target), [
            'name' => 'Promoted Investigator',
            'email' => 'promoted-investigator@example.com',
            'role' => UserRole::Investigator->value,
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status', 'user-updated')
        ->assertSessionHasNoErrors();

    expect($target->fresh()->role)->toBe(UserRole::Investigator)
        ->and($target->officerProfile()->exists())->toBeTrue()
        ->and($profile->fresh()->is($profile))->toBeTrue();

    $this->assertDatabaseHas('officer_profiles', [
        'id' => $profile->id,
        'user_id' => $target->id,
        'badge_number' => $profile->badge_number,
    ]);
});

test('invalid roles are rejected when updating a user', function (string $role) {
    $admin = userManagementAdmin();
    $target = userManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($admin)
        ->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => $role,
        ])
        ->assertSessionHasErrors('role');

    expect($target->fresh()->role)->toBe(UserRole::PoliceOfficer);
})->with(['superuser', 'JUDGE', 'judge-invalid']);

test('judge role is available in user management forms and lists', function () {
    $admin = userManagementAdmin();
    $judge = userManagementUser(UserRole::Judge, ['name' => 'Visible Judge']);

    $this->actingAs($admin)
        ->get(route('users.create'))
        ->assertOk()
        ->assertSee('value="judge"', false)
        ->assertSee('Judge');

    $this->actingAs($admin)
        ->get(route('users.edit', $judge))
        ->assertOk()
        ->assertSee('value="judge"', false);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee('Visible Judge')
        ->assertSee('Judge');
});

test('judge role label appears in navigation', function () {
    $judge = userManagementUser(UserRole::Judge);

    $this->actingAs($judge)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Judge access');
});

test('judge role label is localized in user management and navigation', function (string $locale, string $label) {
    $admin = userManagementAdmin();
    $judge = userManagementUser(UserRole::Judge);

    $this->actingAs($admin)
        ->withSession(['locale' => $locale])
        ->get(route('users.index'))
        ->assertOk()
        ->assertSee($label);

    $this->actingAs($admin)
        ->withSession(['locale' => $locale])
        ->get(route('users.create'))
        ->assertOk()
        ->assertSee($label);

    $this->actingAs($judge)
        ->withSession(['locale' => $locale])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee($label.' '.__('access', [], $locale));
})->with([
    'Amharic' => ['am', 'ዳኛ'],
    'Afaan Oromoo' => ['om', 'Daanyaa'],
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
