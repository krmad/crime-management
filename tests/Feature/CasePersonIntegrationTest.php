<?php

use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\Suspect;
use App\Models\User;
use App\Models\Victim;
use Illuminate\Support\Facades\Gate;

function casePersonUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

test('guests cannot access case association routes', function () {
    $case = CrimeCase::factory()->create();
    $suspect = Suspect::factory()->create();
    $victim = Victim::factory()->create();

    $routes = [
        ['post', route('cases.suspects.attach', $case)],
        ['post', route('cases.suspects.store_new', $case)],
        ['patch', route('cases.suspects.update', [$case, $suspect])],
        ['post', route('cases.victims.attach', $case)],
        ['post', route('cases.victims.store_new', $case)],
        ['patch', route('cases.victims.update', [$case, $victim])],
    ];

    foreach ($routes as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }

    $this->assertGuest();
});

test('case details expose linked people but not hidden people or forms without permission', function () {
    $officer = casePersonUser(UserRole::PoliceOfficer);
    $otherOfficer = casePersonUser(UserRole::PoliceOfficer);

    $case = CrimeCase::factory()->withCreator($officer)->create(['title' => 'Association Detail Case']);
    $linkedSuspect = Suspect::factory()->create(['full_name' => 'Linked Suspect']);
    $linkedVictim = Victim::factory()->create(['full_name' => 'Linked Victim']);
    $case->suspects()->attach($linkedSuspect, ['status' => 'identified']);
    $case->victims()->attach($linkedVictim);

    $hiddenSuspect = Suspect::factory()->create(['full_name' => 'Hidden Case Suspect']);
    $hiddenVictim = Victim::factory()->create(['full_name' => 'Hidden Case Victim']);
    $otherCase = CrimeCase::factory()->withCreator($otherOfficer)->create(['title' => 'Unrelated Case']);
    $otherCase->suspects()->attach($hiddenSuspect);
    $otherCase->victims()->attach($hiddenVictim);

    $response = $this->actingAs($officer)->get(route('cases.show', $case));

    $response->assertOk()
        ->assertSee('Linked Suspect')
        ->assertSee('Linked Victim')
        ->assertDontSee('Hidden Case Suspect')
        ->assertDontSee('Hidden Case Victim')
        ->assertSee(route('cases.suspects.attach', $case), false)
        ->assertSee(route('cases.victims.attach', $case), false)
        ->assertViewHas('availableSuspects', fn ($suspects) => ! $suspects->contains($linkedSuspect) && ! $suspects->contains($hiddenSuspect))
        ->assertViewHas('availableVictims', fn ($victims) => ! $victims->contains($linkedVictim) && ! $victims->contains($hiddenVictim));

    $this->actingAs($otherOfficer)->get(route('cases.show', $case))->assertForbidden();
});

test('manage people permission follows case visibility rules', function (UserRole $role, bool $expected) {
    $actor = casePersonUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : casePersonUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : casePersonUser(UserRole::Investigator);

    $case = CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->create();

    expect(Gate::forUser($actor)->allows('managePeople', $case))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'creator officer' => [UserRole::PoliceOfficer, true],
    'assigned investigator' => [UserRole::Investigator, true],
]);

test('unassigned investigators and unrelated officers cannot manage people', function (UserRole $role) {
    $actor = casePersonUser($role);
    $officer = casePersonUser(UserRole::PoliceOfficer);
    $investigator = casePersonUser(UserRole::Investigator);

    $case = CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->create();

    expect(Gate::forUser($actor)->allows('managePeople', $case))->toBeFalse();
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('an officer can link only visible suspects and duplicates are rejected', function () {
    $officer = casePersonUser(UserRole::PoliceOfficer);
    $otherOfficer = casePersonUser(UserRole::PoliceOfficer);

    $case = CrimeCase::factory()->withCreator($officer)->create();
    $visibleSuspect = Suspect::factory()->create(['full_name' => 'Visible Attach Suspect']);
    $hiddenSuspect = Suspect::factory()->create(['full_name' => 'Hidden Attach Suspect']);
    CrimeCase::factory()->withCreator($officer)->create()->suspects()->attach($visibleSuspect);
    CrimeCase::factory()->withCreator($otherOfficer)->create()->suspects()->attach($hiddenSuspect);

    $this->actingAs($officer)->post(route('cases.suspects.attach', $case), [
        'suspect_id' => $visibleSuspect->id,
        'notes' => 'Witness statement pending',
        'status' => 'under_investigation',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'suspect-linked');

    $this->assertDatabaseHas('case_suspect', [
        'case_id' => $case->id,
        'suspect_id' => $visibleSuspect->id,
        'notes' => 'Witness statement pending',
        'status' => 'under_investigation',
    ]);

    $this->actingAs($officer)->post(route('cases.suspects.attach', $case), [
        'suspect_id' => $visibleSuspect->id,
        'status' => 'identified',
    ])->assertSessionHasErrors('suspect_id');

    $this->actingAs($officer)->post(route('cases.suspects.attach', $case), [
        'suspect_id' => $hiddenSuspect->id,
        'status' => 'identified',
    ])->assertSessionHasErrors('suspect_id');

    expect($case->suspects()->count())->toBe(1);
});

test('suspect association validates status and profile details', function () {
    $admin = casePersonUser(UserRole::Administrator);
    $case = CrimeCase::factory()->create();
    $suspect = Suspect::factory()->create();

    $this->actingAs($admin)->post(route('cases.suspects.attach', $case), [
        'suspect_id' => $suspect->id,
        'status' => 'unknown-status',
    ])->assertSessionHasErrors('status');

    $this->actingAs($admin)->post(route('cases.suspects.store_new', $case), [
        'full_name' => '',
        'status' => 'not-a-status',
    ])->assertSessionHasErrors(['full_name', 'status']);

    $this->assertDatabaseCount('case_suspect', 0);
});

test('a suspect can be created and linked from case details in one transaction', function () {
    $officer = casePersonUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()->withCreator($officer)->create();

    $this->actingAs($officer)->post(route('cases.suspects.store_new', $case), [
        'full_name' => 'New Linked Suspect',
        'alias' => 'New Alias',
        'date_of_birth' => '1992-02-02',
        'notes' => 'Created from case details',
        'status' => 'charged',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'suspect-created');

    $suspect = Suspect::where('full_name', 'New Linked Suspect')->firstOrFail();

    $this->assertDatabaseHas('case_suspect', [
        'case_id' => $case->id,
        'suspect_id' => $suspect->id,
        'notes' => 'Created from case details',
        'status' => 'charged',
    ]);
});

test('case suspect notes and status can be updated without changing the shared profile', function () {
    $admin = casePersonUser(UserRole::Administrator);
    $case = CrimeCase::factory()->create();
    $suspect = Suspect::factory()->create(['full_name' => 'Stable Suspect Name']);
    $case->suspects()->attach($suspect, ['status' => 'identified']);

    $this->actingAs($admin)->patch(route('cases.suspects.update', [$case, $suspect]), [
        'notes' => 'Updated case notes',
        'status' => 'cleared',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'suspect-updated');

    $this->assertDatabaseHas('case_suspect', [
        'case_id' => $case->id,
        'suspect_id' => $suspect->id,
        'notes' => 'Updated case notes',
        'status' => 'cleared',
    ]);

    $this->assertDatabaseHas('suspects', [
        'id' => $suspect->id,
        'full_name' => 'Stable Suspect Name',
    ]);
});

test('a suspect linked to another case cannot be updated through this case', function () {
    $admin = casePersonUser(UserRole::Administrator);
    $case = CrimeCase::factory()->create();
    $otherCase = CrimeCase::factory()->create();
    $suspect = Suspect::factory()->create();
    $otherCase->suspects()->attach($suspect);

    $this->actingAs($admin)->patch(route('cases.suspects.update', [$case, $suspect]), [
        'notes' => 'Illegal update',
        'status' => 'cleared',
    ])->assertNotFound();

    $this->assertDatabaseMissing('case_suspect', [
        'case_id' => $case->id,
        'suspect_id' => $suspect->id,
    ]);
});

test('an officer can link only visible victims and duplicates are rejected', function () {
    $officer = casePersonUser(UserRole::PoliceOfficer);
    $otherOfficer = casePersonUser(UserRole::PoliceOfficer);

    $case = CrimeCase::factory()->withCreator($officer)->create();
    $visibleVictim = Victim::factory()->create(['full_name' => 'Visible Attach Victim']);
    $hiddenVictim = Victim::factory()->create(['full_name' => 'Hidden Attach Victim']);
    CrimeCase::factory()->withCreator($officer)->create()->victims()->attach($visibleVictim);
    CrimeCase::factory()->withCreator($otherOfficer)->create()->victims()->attach($hiddenVictim);

    $this->actingAs($officer)->post(route('cases.victims.attach', $case), [
        'victim_id' => $visibleVictim->id,
        'notes' => 'Initial statement recorded',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'victim-linked');

    $this->assertDatabaseHas('case_victim', [
        'case_id' => $case->id,
        'victim_id' => $visibleVictim->id,
        'notes' => 'Initial statement recorded',
    ]);

    $this->actingAs($officer)->post(route('cases.victims.attach', $case), [
        'victim_id' => $visibleVictim->id,
    ])->assertSessionHasErrors('victim_id');

    $this->actingAs($officer)->post(route('cases.victims.attach', $case), [
        'victim_id' => $hiddenVictim->id,
    ])->assertSessionHasErrors('victim_id');

    expect($case->victims()->count())->toBe(1);
});

test('a victim can be created and linked from case details', function () {
    $officer = casePersonUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()->withCreator($officer)->create();

    $this->actingAs($officer)->post(route('cases.victims.store_new', $case), [
        'full_name' => 'New Linked Victim',
        'date_of_birth' => '1995-06-15',
        'notes' => 'Injured during incident',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'victim-created');

    $victim = Victim::where('full_name', 'New Linked Victim')->firstOrFail();

    $this->assertDatabaseHas('case_victim', [
        'case_id' => $case->id,
        'victim_id' => $victim->id,
        'notes' => 'Injured during incident',
    ]);
});

test('case victim notes can be updated through the owning case', function () {
    $admin = casePersonUser(UserRole::Administrator);
    $case = CrimeCase::factory()->create();
    $victim = Victim::factory()->create(['full_name' => 'Stable Victim Name']);
    $case->victims()->attach($victim, ['notes' => 'Initial notes']);

    $this->actingAs($admin)->patch(route('cases.victims.update', [$case, $victim]), [
        'notes' => 'Revised notes',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'victim-updated');

    $this->assertDatabaseHas('case_victim', [
        'case_id' => $case->id,
        'victim_id' => $victim->id,
        'notes' => 'Revised notes',
    ]);

    $this->assertDatabaseHas('victims', [
        'id' => $victim->id,
        'full_name' => 'Stable Victim Name',
    ]);
});

test('unauthorized users cannot mutate people on a case they cannot manage', function (UserRole $role) {
    $actor = casePersonUser($role);
    $officer = casePersonUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()->withCreator($officer)->create();
    $suspect = Suspect::factory()->create();
    $victim = Victim::factory()->create();

    $this->actingAs($actor)->post(route('cases.suspects.attach', $case), [
        'suspect_id' => $suspect->id,
        'status' => 'identified',
    ])->assertForbidden();

    $this->actingAs($actor)->post(route('cases.victims.attach', $case), [
        'victim_id' => $victim->id,
    ])->assertForbidden();

    $this->assertDatabaseCount('case_suspect', 0);
    $this->assertDatabaseCount('case_victim', 0);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('closed cases do not allow people management forms or updates', function () {
    $admin = casePersonUser(UserRole::Administrator);
    $case = CrimeCase::factory()->closed()->create();
    $suspect = Suspect::factory()->create();

    expect(Gate::forUser($admin)->allows('managePeople', $case))->toBeFalse();

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertDontSee(route('cases.suspects.attach', $case), false)
        ->assertDontSee(route('cases.victims.attach', $case), false);

    $this->actingAs($admin)->post(route('cases.suspects.attach', $case), [
        'suspect_id' => $suspect->id,
        'status' => 'identified',
    ])->assertForbidden();

    $this->assertDatabaseCount('case_suspect', 0);
});

test('dashboard and application navigation expose suspect and victim management links', function (UserRole $role) {
    $user = casePersonUser($role);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('suspects.index'), false)
        ->assertSee(route('victims.index'), false);

    $this->actingAs($user)->get(route('suspects.index'))
        ->assertOk()
        ->assertSee(route('suspects.index'), false)
        ->assertSee(route('victims.index'), false);
})->with([
    UserRole::Administrator,
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);
