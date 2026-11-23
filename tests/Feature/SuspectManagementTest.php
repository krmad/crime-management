<?php

use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\Suspect;
use App\Models\User;

function suspectTestUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

test('guests cannot access suspect routes', function () {
    $suspect = Suspect::factory()->create();

    $routes = [
        ['get', route('suspects.index')],
        ['get', route('suspects.create')],
        ['post', route('suspects.store')],
        ['get', route('suspects.show', $suspect)],
        ['get', route('suspects.edit', $suspect)],
        ['patch', route('suspects.update', $suspect)],
    ];

    foreach ($routes as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }

    $this->assertGuest();
});

test('administrator can create a standalone suspect with complete details', function () {
    $admin = suspectTestUser(UserRole::Administrator);

    $this->actingAs($admin)->get(route('suspects.create'))->assertOk();

    $this->actingAs($admin)->post(route('suspects.store'), [
        'full_name' => 'Standalone Suspect',
        'alias' => 'Night Owl',
        'date_of_birth' => '1990-04-12',
        'gender' => 'Female',
        'address' => '12 Test Street',
        'phone' => '0912345678',
    ])->assertRedirect(route('suspects.index'))
        ->assertSessionHas('status', 'suspect-created');

    $this->assertDatabaseHas('suspects', [
        'full_name' => 'Standalone Suspect',
        'alias' => 'Night Owl',
        'date_of_birth' => '1990-04-12 00:00:00',
        'gender' => 'Female',
        'address' => '12 Test Street',
        'phone' => '0912345678',
    ]);
});

test('non administrators cannot create standalone suspects', function (UserRole $role) {
    $user = suspectTestUser($role);

    $this->actingAs($user)->get(route('suspects.create'))->assertForbidden();
    $this->actingAs($user)->post(route('suspects.store'), ['full_name' => 'Forbidden Suspect'])->assertForbidden();

    $this->assertDatabaseMissing('suspects', ['full_name' => 'Forbidden Suspect']);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('suspect listing is scoped to visible cases for each role', function (UserRole $role) {
    $actor = suspectTestUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : suspectTestUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : suspectTestUser(UserRole::Investigator);
    $otherOfficer = suspectTestUser(UserRole::PoliceOfficer);

    $officerSuspect = Suspect::factory()->create(['full_name' => 'Officer Suspect']);
    $investigatorSuspect = Suspect::factory()->create(['full_name' => 'Investigator Suspect']);

    CrimeCase::factory()->withCreator($officer)->create()->suspects()->attach($officerSuspect);
    CrimeCase::factory()->withCreator($otherOfficer)->assignedTo($investigator)->create()->suspects()->attach($investigatorSuspect);

    $response = $this->actingAs($actor)->get(route('suspects.index'));

    $response->assertOk()
        ->assertViewIs('suspects.index')
        ->assertViewHas('suspects');

    $ids = $response->viewData('suspects')->getCollection()->pluck('id');

    if ($role === UserRole::Administrator) {
        expect($ids)->toContain($officerSuspect->id)->toContain($investigatorSuspect->id);

        return;
    }

    if ($role === UserRole::PoliceOfficer) {
        expect($ids)->toContain($officerSuspect->id)->not->toContain($investigatorSuspect->id);

        return;
    }

    expect($ids)->toContain($investigatorSuspect->id)->not->toContain($officerSuspect->id);
})->with([
    UserRole::Administrator,
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('suspect profile hides case associations outside the users access', function () {
    $officer = suspectTestUser(UserRole::PoliceOfficer);
    $otherOfficer = suspectTestUser(UserRole::PoliceOfficer);

    $suspect = Suspect::factory()->create(['full_name' => 'Shared Suspect']);
    $visibleCase = CrimeCase::factory()->withCreator($officer)->create(['title' => 'Visible Suspect Case']);
    $hiddenCase = CrimeCase::factory()->withCreator($otherOfficer)->create(['title' => 'Hidden Suspect Case']);

    $visibleCase->suspects()->attach($suspect);
    $hiddenCase->suspects()->attach($suspect);

    $this->actingAs($officer)->get(route('suspects.show', $suspect))
        ->assertOk()
        ->assertSee('Visible Suspect Case')
        ->assertDontSee('Hidden Suspect Case');

    $hiddenSuspect = Suspect::factory()->create(['full_name' => 'Hidden Suspect']);
    $hiddenCase->suspects()->attach($hiddenSuspect);

    $this->actingAs($officer)->get(route('suspects.show', $hiddenSuspect))->assertForbidden();
});

test('suspect search filters by name and alias without leaking hidden records', function () {
    $officer = suspectTestUser(UserRole::PoliceOfficer);
    $otherOfficer = suspectTestUser(UserRole::PoliceOfficer);

    $visible = Suspect::factory()->create(['full_name' => 'Searchable Visible', 'alias' => 'Night Owl']);
    $hidden = Suspect::factory()->create(['full_name' => 'Searchable Hidden', 'alias' => 'Ghost']);

    CrimeCase::factory()->withCreator($officer)->create()->suspects()->attach($visible);
    CrimeCase::factory()->withCreator($otherOfficer)->create()->suspects()->attach($hidden);

    $byName = $this->actingAs($officer)->get(route('suspects.index', ['search' => 'Searchable Visible']));
    $byAlias = $this->actingAs($officer)->get(route('suspects.index', ['search' => 'Night Owl']));

    $byName->assertOk()->assertSee('Searchable Visible')->assertDontSee('Searchable Hidden');
    $byAlias->assertOk()->assertSee('Searchable Visible')->assertDontSee('Searchable Hidden');

    expect($byName->viewData('suspects')->getCollection()->pluck('id')->all())->toBe([$visible->id]);
});

test('suspect listing paginates visible records deterministically', function () {
    $officer = suspectTestUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()->withCreator($officer)->create();

    $suspects = collect(range(1, 16))
        ->map(fn (int $number): Suspect => Suspect::factory()->create(['full_name' => sprintf('Paged Suspect %02d', $number)]));

    $suspects->each(fn (Suspect $suspect) => $case->suspects()->attach($suspect));

    $firstPage = $this->actingAs($officer)->get(route('suspects.index', ['page' => 1]));
    $secondPage = $this->actingAs($officer)->get(route('suspects.index', ['page' => 2]));

    $firstPage->assertOk();
    $secondPage->assertOk();

    expect($firstPage->viewData('suspects')->getCollection())
        ->toHaveCount(15)
        ->and($secondPage->viewData('suspects')->getCollection())
        ->toHaveCount(1);
});

test('officers can edit suspects linked only to their cases but not mixed visibility suspects', function () {
    $officer = suspectTestUser(UserRole::PoliceOfficer);
    $otherOfficer = suspectTestUser(UserRole::PoliceOfficer);

    $ownSuspect = Suspect::factory()->create(['full_name' => 'Own Suspect']);
    $mixedSuspect = Suspect::factory()->create(['full_name' => 'Mixed Suspect']);

    CrimeCase::factory()->withCreator($officer)->create()->suspects()->attach($ownSuspect);
    CrimeCase::factory()->withCreator($officer)->create()->suspects()->attach($mixedSuspect);
    CrimeCase::factory()->withCreator($otherOfficer)->create()->suspects()->attach($mixedSuspect);

    $this->actingAs($officer)->get(route('suspects.edit', $ownSuspect))->assertOk();

    $this->actingAs($officer)->patch(route('suspects.update', $ownSuspect), [
        'full_name' => 'Updated Own Suspect',
    ])->assertRedirect(route('suspects.index'));

    $this->assertDatabaseHas('suspects', ['id' => $ownSuspect->id, 'full_name' => 'Updated Own Suspect']);

    $this->actingAs($officer)->get(route('suspects.edit', $mixedSuspect))->assertForbidden();
    $this->actingAs($officer)->patch(route('suspects.update', $mixedSuspect), ['full_name' => 'Illegal Update'])->assertForbidden();

    $this->assertDatabaseMissing('suspects', ['id' => $mixedSuspect->id, 'full_name' => 'Illegal Update']);
});

test('administrator can edit any suspect', function () {
    $admin = suspectTestUser(UserRole::Administrator);
    $suspect = Suspect::factory()->create(['full_name' => 'Admin Edit Suspect']);

    $this->actingAs($admin)->get(route('suspects.edit', $suspect))->assertOk();

    $this->actingAs($admin)->patch(route('suspects.update', $suspect), [
        'full_name' => 'Administratively Updated',
        'alias' => 'Updated Alias',
    ])->assertRedirect(route('suspects.index'));

    $this->assertDatabaseHas('suspects', [
        'id' => $suspect->id,
        'full_name' => 'Administratively Updated',
        'alias' => 'Updated Alias',
    ]);
});

test('suspect create validation rejects invalid details', function () {
    $admin = suspectTestUser(UserRole::Administrator);

    $this->actingAs($admin)->post(route('suspects.store'), [
        'full_name' => '',
        'date_of_birth' => '2099-01-01',
        'address' => str_repeat('a', 501),
    ])->assertSessionHasErrors(['full_name', 'date_of_birth', 'address']);
});
