<?php

use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\User;
use App\Models\Victim;

function victimTestUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

test('guests cannot access victim routes', function () {
    $victim = Victim::factory()->create();

    $routes = [
        ['get', route('victims.index')],
        ['get', route('victims.create')],
        ['post', route('victims.store')],
        ['get', route('victims.show', $victim)],
        ['get', route('victims.edit', $victim)],
        ['patch', route('victims.update', $victim)],
    ];

    foreach ($routes as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }

    $this->assertGuest();
});

test('administrator can create a standalone victim with complete details', function () {
    $admin = victimTestUser(UserRole::Administrator);

    $this->actingAs($admin)->get(route('victims.create'))->assertOk();

    $this->actingAs($admin)->post(route('victims.store'), [
        'full_name' => 'Standalone Victim',
        'date_of_birth' => '1988-08-03',
        'gender' => 'Male',
        'address' => '44 Sample Avenue',
        'phone' => '0987654321',
    ])->assertRedirect(route('victims.index'))
        ->assertSessionHas('status', 'victim-created');

    $this->assertDatabaseHas('victims', [
        'full_name' => 'Standalone Victim',
        'date_of_birth' => '1988-08-03 00:00:00',
        'gender' => 'Male',
        'address' => '44 Sample Avenue',
        'phone' => '0987654321',
    ]);
});

test('non administrators cannot create standalone victims', function (UserRole $role) {
    $user = victimTestUser($role);

    $this->actingAs($user)->get(route('victims.create'))->assertForbidden();
    $this->actingAs($user)->post(route('victims.store'), ['full_name' => 'Forbidden Victim'])->assertForbidden();

    $this->assertDatabaseMissing('victims', ['full_name' => 'Forbidden Victim']);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('victim listing is scoped to visible cases for each role', function (UserRole $role) {
    $actor = victimTestUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : victimTestUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : victimTestUser(UserRole::Investigator);
    $otherOfficer = victimTestUser(UserRole::PoliceOfficer);

    $officerVictim = Victim::factory()->create(['full_name' => 'Officer Victim']);
    $investigatorVictim = Victim::factory()->create(['full_name' => 'Investigator Victim']);

    CrimeCase::factory()->withCreator($officer)->create()->victims()->attach($officerVictim);
    CrimeCase::factory()->withCreator($otherOfficer)->assignedTo($investigator)->create()->victims()->attach($investigatorVictim);

    $response = $this->actingAs($actor)->get(route('victims.index'));

    $response->assertOk()
        ->assertViewIs('victims.index')
        ->assertViewHas('victims');

    $ids = $response->viewData('victims')->getCollection()->pluck('id');

    if ($role === UserRole::Administrator) {
        expect($ids)->toContain($officerVictim->id)->toContain($investigatorVictim->id);

        return;
    }

    if ($role === UserRole::PoliceOfficer) {
        expect($ids)->toContain($officerVictim->id)->not->toContain($investigatorVictim->id);

        return;
    }

    expect($ids)->toContain($investigatorVictim->id)->not->toContain($officerVictim->id);
})->with([
    UserRole::Administrator,
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('victim profile hides case associations outside the users access', function () {
    $officer = victimTestUser(UserRole::PoliceOfficer);
    $otherOfficer = victimTestUser(UserRole::PoliceOfficer);

    $victim = Victim::factory()->create(['full_name' => 'Shared Victim']);
    $visibleCase = CrimeCase::factory()->withCreator($officer)->create(['title' => 'Visible Victim Case']);
    $hiddenCase = CrimeCase::factory()->withCreator($otherOfficer)->create(['title' => 'Hidden Victim Case']);

    $visibleCase->victims()->attach($victim);
    $hiddenCase->victims()->attach($victim);

    $this->actingAs($officer)->get(route('victims.show', $victim))
        ->assertOk()
        ->assertSee('Visible Victim Case')
        ->assertDontSee('Hidden Victim Case');

    $hiddenVictim = Victim::factory()->create(['full_name' => 'Hidden Victim']);
    $hiddenCase->victims()->attach($hiddenVictim);

    $this->actingAs($officer)->get(route('victims.show', $hiddenVictim))->assertForbidden();
});

test('victim search filters by name without leaking hidden records', function () {
    $officer = victimTestUser(UserRole::PoliceOfficer);
    $otherOfficer = victimTestUser(UserRole::PoliceOfficer);

    $visible = Victim::factory()->create(['full_name' => 'Searchable Visible Victim']);
    $hidden = Victim::factory()->create(['full_name' => 'Searchable Hidden Victim']);

    CrimeCase::factory()->withCreator($officer)->create()->victims()->attach($visible);
    CrimeCase::factory()->withCreator($otherOfficer)->create()->victims()->attach($hidden);

    $response = $this->actingAs($officer)->get(route('victims.index', ['search' => 'Searchable Visible Victim']));

    $response->assertOk()->assertSee('Searchable Visible Victim')->assertDontSee('Searchable Hidden Victim');

    expect($response->viewData('victims')->getCollection()->pluck('id')->all())->toBe([$visible->id]);
});

test('victim listing paginates visible records deterministically', function () {
    $officer = victimTestUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()->withCreator($officer)->create();

    $victims = collect(range(1, 16))
        ->map(fn (int $number): Victim => Victim::factory()->create(['full_name' => sprintf('Paged Victim %02d', $number)]));

    $victims->each(fn (Victim $victim) => $case->victims()->attach($victim));

    $firstPage = $this->actingAs($officer)->get(route('victims.index', ['page' => 1]));
    $secondPage = $this->actingAs($officer)->get(route('victims.index', ['page' => 2]));

    $firstPage->assertOk();
    $secondPage->assertOk();

    expect($firstPage->viewData('victims')->getCollection())
        ->toHaveCount(15)
        ->and($secondPage->viewData('victims')->getCollection())
        ->toHaveCount(1);
});

test('officers can edit victims linked only to their cases but not mixed visibility victims', function () {
    $officer = victimTestUser(UserRole::PoliceOfficer);
    $otherOfficer = victimTestUser(UserRole::PoliceOfficer);

    $ownVictim = Victim::factory()->create(['full_name' => 'Own Victim']);
    $mixedVictim = Victim::factory()->create(['full_name' => 'Mixed Victim']);

    CrimeCase::factory()->withCreator($officer)->create()->victims()->attach($ownVictim);
    CrimeCase::factory()->withCreator($officer)->create()->victims()->attach($mixedVictim);
    CrimeCase::factory()->withCreator($otherOfficer)->create()->victims()->attach($mixedVictim);

    $this->actingAs($officer)->get(route('victims.edit', $ownVictim))->assertOk();

    $this->actingAs($officer)->patch(route('victims.update', $ownVictim), [
        'full_name' => 'Updated Own Victim',
    ])->assertRedirect(route('victims.index'));

    $this->assertDatabaseHas('victims', ['id' => $ownVictim->id, 'full_name' => 'Updated Own Victim']);

    $this->actingAs($officer)->get(route('victims.edit', $mixedVictim))->assertForbidden();
    $this->actingAs($officer)->patch(route('victims.update', $mixedVictim), ['full_name' => 'Illegal Update'])->assertForbidden();

    $this->assertDatabaseMissing('victims', ['id' => $mixedVictim->id, 'full_name' => 'Illegal Update']);
});

test('administrator can edit any victim', function () {
    $admin = victimTestUser(UserRole::Administrator);
    $victim = Victim::factory()->create(['full_name' => 'Admin Edit Victim']);

    $this->actingAs($admin)->get(route('victims.edit', $victim))->assertOk();

    $this->actingAs($admin)->patch(route('victims.update', $victim), [
        'full_name' => 'Administratively Updated Victim',
        'phone' => '0111222333',
    ])->assertRedirect(route('victims.index'));

    $this->assertDatabaseHas('victims', [
        'id' => $victim->id,
        'full_name' => 'Administratively Updated Victim',
        'phone' => '0111222333',
    ]);
});

test('victim create validation rejects invalid details', function () {
    $admin = victimTestUser(UserRole::Administrator);

    $this->actingAs($admin)->post(route('victims.store'), [
        'full_name' => '',
        'date_of_birth' => '2099-01-01',
        'address' => str_repeat('a', 501),
    ])->assertSessionHasErrors(['full_name', 'date_of_birth', 'address']);
});
