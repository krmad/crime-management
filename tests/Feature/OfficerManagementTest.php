<?php

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use App\Models\OfficerProfile;
use App\Models\User;

function officerManagementAdmin(): User
{
    $user = User::factory()->create(['name' => 'Officer Administrator']);
    $user->forceFill(['role' => UserRole::Administrator])->save();

    return $user;
}

function officerManagementUser(UserRole $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function validOfficerManagementPayload(User $user, array $overrides = []): array
{
    return array_merge([
        'user_id' => $user->id,
        'badge_number' => 'BADGE-'.fake()->unique()->numberBetween(10000, 99999),
        'rank' => 'Constable',
        'department' => 'Patrol',
        'police_station' => 'North Police Station',
        'phone' => '555-0200',
        'employment_status' => EmploymentStatus::Active->value,
    ], $overrides);
}

test('guests are redirected to login from officers management', function () {
    $this->get(route('officers.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('police officers and investigators cannot access officers management', function (UserRole $role) {
    $actor = officerManagementUser($role);
    $profile = OfficerProfile::factory()->create();
    $eligibleUser = officerManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($actor)
        ->get(route('officers.index'))
        ->assertForbidden();

    $this->actingAs($actor)
        ->get(route('officers.create'))
        ->assertForbidden();

    $this->actingAs($actor)
        ->post(route('officers.store'), validOfficerManagementPayload($eligibleUser))
        ->assertForbidden();

    $this->actingAs($actor)
        ->get(route('officers.show', $profile))
        ->assertForbidden();

    $this->actingAs($actor)
        ->get(route('officers.edit', $profile))
        ->assertForbidden();

    $this->actingAs($actor)
        ->patch(route('officers.update', $profile), [])
        ->assertForbidden();

    $this->assertDatabaseCount('officer_profiles', 1);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('administrator can search and filter officer profiles', function () {
    $admin = officerManagementAdmin();
    $alphaUser = officerManagementUser(UserRole::PoliceOfficer, ['name' => 'Alice Officer']);
    $alphaProfile = OfficerProfile::factory()->create([
        'user_id' => $alphaUser->id,
        'badge_number' => 'BADGE-ALPHA',
        'rank' => 'Sergeant',
        'employment_status' => EmploymentStatus::Active,
    ]);
    $betaProfile = OfficerProfile::factory()->create([
        'badge_number' => 'BADGE-BETA',
        'rank' => 'Constable',
        'employment_status' => EmploymentStatus::Suspended,
    ]);

    $response = $this->actingAs($admin)->get(route('officers.index', [
        'search' => 'ALPHA',
        'rank' => 'Sergeant',
        'employment_status' => EmploymentStatus::Active->value,
    ]));

    $response->assertOk()
        ->assertViewIs('officers.index')
        ->assertSee('Alice Officer')
        ->assertSee('BADGE-ALPHA')
        ->assertDontSee('BADGE-BETA');

    $profileIds = $response->viewData('officers')->getCollection()->pluck('id');

    expect($profileIds)
        ->toContain($alphaProfile->id)
        ->not->toContain($betaProfile->id);
});

test('create form offers only eligible accounts without an officer profile', function () {
    $admin = officerManagementAdmin();
    $eligibleOfficer = officerManagementUser(UserRole::PoliceOfficer, ['name' => 'Eligible Officer']);
    $eligibleInvestigator = officerManagementUser(UserRole::Investigator, ['name' => 'Eligible Investigator']);
    $linkedUser = officerManagementUser(UserRole::PoliceOfficer, ['name' => 'Linked Officer']);
    OfficerProfile::factory()->create(['user_id' => $linkedUser->id]);

    $response = $this->actingAs($admin)
        ->get(route('officers.create'))
        ->assertOk()
        ->assertSee('Eligible Officer')
        ->assertSee('Eligible Investigator')
        ->assertDontSee('Linked Officer');

    $userIds = $response->viewData('eligibleUsers')->pluck('id');

    expect($userIds)
        ->toContain($eligibleOfficer->id)
        ->toContain($eligibleInvestigator->id)
        ->not->toContain($linkedUser->id)
        ->not->toContain($admin->id);
});

test('archived officer profiles stay hidden from officer management', function () {
    $admin = officerManagementAdmin();
    $officer = officerManagementUser(UserRole::PoliceOfficer, ['name' => 'Archived Officer']);
    $profile = OfficerProfile::factory()->create([
        'user_id' => $officer->id,
        'badge_number' => 'BADGE-HIDDEN',
    ]);
    $profile->delete();

    $indexResponse = $this->actingAs($admin)
        ->get(route('officers.index'))
        ->assertOk()
        ->assertDontSee('BADGE-HIDDEN');

    expect($indexResponse->viewData('officers')->getCollection()->pluck('id'))
        ->not->toContain($profile->id);

    $createResponse = $this->actingAs($admin)
        ->get(route('officers.create'))
        ->assertOk()
        ->assertDontSee('Archived Officer');

    expect($createResponse->viewData('eligibleUsers')->pluck('id'))
        ->not->toContain($officer->id);

    $this->assertSoftDeleted(OfficerProfile::withTrashed()->findOrFail($profile->id))
        ->assertDatabaseHas('officer_profiles', [
            'id' => $profile->id,
            'user_id' => $officer->id,
            'badge_number' => 'BADGE-HIDDEN',
        ]);
});

test('archived badge numbers remain reserved', function () {
    $admin = officerManagementAdmin();
    $archivedProfile = OfficerProfile::factory()->create(['badge_number' => 'BADGE-ARCHIVED-RESERVED']);
    $archivedProfile->delete();
    $newOfficer = officerManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($admin)
        ->post(route('officers.store'), validOfficerManagementPayload($newOfficer, [
            'badge_number' => $archivedProfile->badge_number,
        ]))
        ->assertSessionHasErrors('badge_number');

    $this->assertDatabaseCount('officer_profiles', 1);
});

test('administrator can create an officer profile without creating a duplicate user', function () {
    $admin = officerManagementAdmin();
    $officer = officerManagementUser(UserRole::PoliceOfficer, ['name' => 'Profile Target']);
    $payload = validOfficerManagementPayload($officer, [
        'badge_number' => 'BADGE-NEW',
        'rank' => 'Inspector',
    ]);

    $this->actingAs($admin)
        ->post(route('officers.store'), $payload)
        ->assertRedirect(route('officers.index'))
        ->assertSessionHas('status', 'officer-created');

    $this->assertDatabaseHas('officer_profiles', [
        'user_id' => $officer->id,
        'badge_number' => 'BADGE-NEW',
        'rank' => 'Inspector',
        'department' => 'Patrol',
        'police_station' => 'North Police Station',
        'phone' => '555-0200',
        'employment_status' => EmploymentStatus::Active->value,
    ]);

    expect(User::count())->toBe(2)
        ->and($officer->fresh()->officerProfile()->exists())->toBeTrue();
});

test('administrator cannot create a profile for an administrator account', function () {
    $admin = officerManagementAdmin();

    $this->actingAs($admin)
        ->post(route('officers.store'), validOfficerManagementPayload($admin))
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('officer_profiles', 0);
});

test('administrator cannot create two profiles for the same account', function () {
    $admin = officerManagementAdmin();
    $officer = officerManagementUser(UserRole::Investigator);
    OfficerProfile::factory()->create(['user_id' => $officer->id]);

    $this->actingAs($admin)
        ->post(route('officers.store'), validOfficerManagementPayload($officer))
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseCount('officer_profiles', 1);
});

test('administrator cannot reuse another officer badge number', function () {
    $admin = officerManagementAdmin();
    $existingProfile = OfficerProfile::factory()->create(['badge_number' => 'BADGE-EXISTING']);
    $newOfficer = officerManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($admin)
        ->post(route('officers.store'), validOfficerManagementPayload($newOfficer, [
            'badge_number' => $existingProfile->badge_number,
        ]))
        ->assertSessionHasErrors('badge_number');

    $this->assertDatabaseCount('officer_profiles', 1);
});

test('administrator can view and update an officer profile', function () {
    $admin = officerManagementAdmin();
    $officer = officerManagementUser(UserRole::PoliceOfficer, ['name' => 'Editable Officer']);
    $profile = OfficerProfile::factory()->create([
        'user_id' => $officer->id,
        'badge_number' => 'BADGE-EDIT',
        'rank' => 'Constable',
        'employment_status' => EmploymentStatus::Active,
    ]);

    $this->actingAs($admin)
        ->get(route('officers.show', $profile))
        ->assertOk()
        ->assertSee('Editable Officer')
        ->assertSee('BADGE-EDIT')
        ->assertSee('Active');

    $this->actingAs($admin)
        ->get(route('officers.edit', $profile))
        ->assertOk()
        ->assertSee('Edit officer profile');

    $this->actingAs($admin)
        ->patch(route('officers.update', $profile), [
            'badge_number' => $profile->badge_number,
            'rank' => 'Inspector',
            'department' => 'Investigation',
            'police_station' => 'Central Police Station',
            'phone' => '555-0300',
            'employment_status' => EmploymentStatus::Suspended->value,
        ])
        ->assertRedirect(route('officers.index'))
        ->assertSessionHas('status', 'officer-updated');

    $this->assertDatabaseHas('officer_profiles', [
        'id' => $profile->id,
        'badge_number' => 'BADGE-EDIT',
        'rank' => 'Inspector',
        'department' => 'Investigation',
        'police_station' => 'Central Police Station',
        'phone' => '555-0300',
        'employment_status' => EmploymentStatus::Suspended->value,
    ]);
});

test('officer profile update rejects another profile badge but allows its own', function () {
    $admin = officerManagementAdmin();
    $profile = OfficerProfile::factory()->create(['badge_number' => 'BADGE-KEEP']);
    $otherProfile = OfficerProfile::factory()->create(['badge_number' => 'BADGE-OTHER']);

    $this->actingAs($admin)
        ->patch(route('officers.update', $profile), [
            'badge_number' => $otherProfile->badge_number,
            'rank' => $profile->rank,
            'department' => $profile->department,
            'police_station' => $profile->police_station,
            'phone' => $profile->phone,
            'employment_status' => $profile->employment_status->value,
        ])
        ->assertSessionHasErrors('badge_number');

    expect($profile->fresh()->badge_number)->toBe('BADGE-KEEP');

    $this->actingAs($admin)
        ->patch(route('officers.update', $profile), [
            'badge_number' => $profile->badge_number,
            'rank' => $profile->rank,
            'department' => $profile->department,
            'police_station' => $profile->police_station,
            'phone' => $profile->phone,
            'employment_status' => $profile->employment_status->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('officers.index'));
});
