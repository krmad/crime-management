<?php

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\User;

function caseManagementUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function caseManagementCase(User $creator, ?User $investigator = null, array $attributes = []): CrimeCase
{
    $factory = CrimeCase::factory()->withCreator($creator);

    if ($investigator !== null) {
        $factory = $factory->assignedTo($investigator);
    }

    return $factory->create($attributes);
}

function validCasePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Reported Burglary',
        'crime_type' => 'Burglary',
        'location' => 'Downtown District',
        'description' => 'Fictional incident report for internal testing.',
        'incident_date' => '2026-11-20 10:00:00',
    ], $overrides);
}

test('guests cannot access case routes', function (string $method, string $routeName) {
    $this->{$method}(route($routeName))
        ->assertRedirect(route('login'));

    $this->assertGuest();
})->with([
    'listing' => ['get', 'cases.index'],
    'creation form' => ['get', 'cases.create'],
    'store' => ['post', 'cases.store'],
]);

test('administrator sees all cases', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);

    $adminCase = caseManagementCase($admin, null, ['title' => 'Admin Case']);
    $officerCase = caseManagementCase($officer, null, ['title' => 'Officer Case']);
    $investigatorCase = caseManagementCase($officer, $investigator, ['title' => 'Investigator Case']);
    $unrelatedCase = caseManagementCase(caseManagementUser(UserRole::PoliceOfficer), null, ['title' => 'Unrelated Case']);

    $response = $this->actingAs($admin)->get(route('cases.index'));

    $response->assertOk()
        ->assertViewIs('cases.index')
        ->assertViewHas('cases')
        ->assertSee('Admin Case')
        ->assertSee('Officer Case')
        ->assertSee('Investigator Case')
        ->assertSee('Unrelated Case');

    $caseIds = $response->viewData('cases')->getCollection()->pluck('id');

    expect($caseIds)
        ->toContain($adminCase->id)
        ->toContain($officerCase->id)
        ->toContain($investigatorCase->id)
        ->toContain($unrelatedCase->id);
});

test('police officer sees only cases they created', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $otherOfficer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);

    $ownCase = caseManagementCase($officer, null, ['title' => 'Own Officer Case']);
    $otherCase = caseManagementCase($otherOfficer, null, ['title' => 'Other Officer Case']);
    $assignedToOfficer = caseManagementCase($otherOfficer, $officer, ['title' => 'Assigned To Officer']);

    $response = $this->actingAs($officer)->get(route('cases.index'));

    $response->assertOk()
        ->assertSee('Own Officer Case')
        ->assertDontSee('Other Officer Case')
        ->assertDontSee('Assigned To Officer');

    $caseIds = $response->viewData('cases')->getCollection()->pluck('id');

    expect($caseIds)
        ->toContain($ownCase->id)
        ->not->toContain($otherCase->id)
        ->not->toContain($assignedToOfficer->id);
});

test('investigator sees only assigned cases', function () {
    $investigator = caseManagementUser(UserRole::Investigator);
    $otherInvestigator = caseManagementUser(UserRole::Investigator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $assignedCase = caseManagementCase($officer, $investigator, ['title' => 'Assigned Investigation']);
    $createdCase = caseManagementCase($investigator, null, ['title' => 'Created By Investigator']);
    $otherAssignedCase = caseManagementCase($officer, $otherInvestigator, ['title' => 'Other Investigation']);
    $unassignedCase = caseManagementCase($officer, null, ['title' => 'Unassigned Report']);

    $response = $this->actingAs($investigator)->get(route('cases.index'));

    $response->assertOk()
        ->assertSee('Assigned Investigation')
        ->assertDontSee('Created By Investigator')
        ->assertDontSee('Other Investigation')
        ->assertDontSee('Unassigned Report');

    $caseIds = $response->viewData('cases')->getCollection()->pluck('id');

    expect($caseIds)
        ->toContain($assignedCase->id)
        ->not->toContain($createdCase->id)
        ->not->toContain($otherAssignedCase->id)
        ->not->toContain($unassignedCase->id);
});

test('administrators and police officers can create cases', function (UserRole $role) {
    $user = caseManagementUser($role);

    $response = $this->actingAs($user)->post(route('cases.store'), validCasePayload([
        'title' => 'New Reported Case',
    ]));

    $response->assertRedirect(route('cases.index'))
        ->assertSessionHas('status', 'case-created');

    $this->assertDatabaseHas('cases', [
        'title' => 'New Reported Case',
        'created_by' => $user->id,
        'status' => 'open',
        'investigator_id' => null,
        'judgment_notes' => null,
        'closed_at' => null,
    ]);

    expect($user->createdCases()->count())->toBe(1);
})->with([
    'administrator' => [UserRole::Administrator],
    'police officer' => [UserRole::PoliceOfficer],
]);

test('investigator cannot view the case creation form', function () {
    $investigator = caseManagementUser(UserRole::Investigator);

    $this->actingAs($investigator)
        ->get(route('cases.create'))
        ->assertForbidden();
});

test('investigator cannot create a case', function () {
    $investigator = caseManagementUser(UserRole::Investigator);

    $this->actingAs($investigator)
        ->post(route('cases.store'), validCasePayload())
        ->assertForbidden();

    $this->assertDatabaseCount('cases', 0);
});

test('submitted sensitive fields cannot set ownership investigator or status', function () {
    $creator = caseManagementUser(UserRole::PoliceOfficer);
    $otherUser = caseManagementUser(UserRole::PoliceOfficer);

    $response = $this->actingAs($creator)->post(route('cases.store'), validCasePayload([
        'created_by' => $otherUser->id,
        'investigator_id' => $otherUser->id,
        'status' => 'closed',
        'judgment_notes' => 'Injected judgment notes.',
        'closed_at' => now()->toDateTimeString(),
    ]));

    $response->assertRedirect(route('cases.index'));

    $case = CrimeCase::firstOrFail();

    expect($case->created_by)->toBe($creator->id)
        ->and($case->investigator_id)->toBeNull()
        ->and($case->status)->toBe(CaseStatus::Open)
        ->and($case->judgment_notes)->toBeNull()
        ->and($case->closed_at)->toBeNull();
});

test('validation rejects invalid required fields', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $response = $this->actingAs($officer)->post(route('cases.store'), [
        'title' => '',
        'crime_type' => '',
        'location' => '',
        'description' => '',
        'incident_date' => 'not-a-date',
    ]);

    $response->assertSessionHasErrors([
        'title',
        'crime_type',
        'location',
        'description',
        'incident_date',
    ]);

    $this->assertDatabaseCount('cases', 0);
});
