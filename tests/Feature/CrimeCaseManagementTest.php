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

test('guests cannot access case details', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $case = caseManagementCase($officer);

    $this->get(route('cases.show', $case))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('administrator can view any case details', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer, $investigator, [
        'title' => 'Admin Visible Case',
        'description' => 'Detailed description for administrators.',
        'judgment_notes' => 'Judgment notes recorded for this case.',
        'closed_at' => now(),
    ]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertViewIs('cases.show')
        ->assertSee('Admin Visible Case')
        ->assertSee('Detailed description for administrators.')
        ->assertSee($investigator->name)
        ->assertSee('Incident date')
        ->assertSee('Created at')
        ->assertSee('Updated at')
        ->assertSee('Judgment notes recorded for this case.')
        ->assertSee('Closure date');
});

test('police officer can view own case but not another officer case', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $otherOfficer = caseManagementUser(UserRole::PoliceOfficer);
    $ownCase = caseManagementCase($officer, null, ['title' => 'Own Details Case']);
    $otherCase = caseManagementCase($otherOfficer, null, ['title' => 'Other Details Case']);

    $this->actingAs($officer)->get(route('cases.show', $ownCase))
        ->assertOk()
        ->assertSee('Own Details Case')
        ->assertDontSee('Other Details Case');

    $this->actingAs($officer)->get(route('cases.show', $otherCase))
        ->assertForbidden();
});

test('investigator can view assigned case but not unassigned case', function () {
    $investigator = caseManagementUser(UserRole::Investigator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $assignedCase = caseManagementCase($officer, $investigator, ['title' => 'Assigned Details Case']);
    $unassignedCase = caseManagementCase($officer, null, ['title' => 'Unassigned Details Case']);

    $this->actingAs($investigator)->get(route('cases.show', $assignedCase))
        ->assertOk()
        ->assertSee('Assigned Details Case');

    $this->actingAs($investigator)->get(route('cases.show', $unassignedCase))
        ->assertForbidden();
});

test('case details return 404 for missing case', function () {
    $admin = caseManagementUser(UserRole::Administrator);

    $this->actingAs($admin)
        ->get(route('cases.show', ['case' => 999999]))
        ->assertNotFound();
});

test('case listing displays view details links for visible cases', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $otherOfficer = caseManagementUser(UserRole::PoliceOfficer);
    $visibleCase = caseManagementCase($officer, null, ['title' => 'Linked Case']);
    $hiddenCase = caseManagementCase($otherOfficer, null, ['title' => 'Hidden Case']);

    $this->actingAs($officer)->get(route('cases.index'))
        ->assertOk()
        ->assertSee('View Details')
        ->assertSee(route('cases.show', $visibleCase), false)
        ->assertDontSee(route('cases.show', $hiddenCase), false);
});

test('case details hide judgment notes and closure date when absent', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $case = caseManagementCase($officer, null, ['title' => 'Plain Details Case']);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertDontSee('Judgment notes')
        ->assertDontSee('Closure date');
});

test('guests cannot assign investigators', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer);

    $this->patch(route('cases.assign', $case), ['investigator_id' => $investigator->id])
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('administrator can assign an investigator', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer, null, ['title' => 'Assignable Case']);

    $response = $this->actingAs($admin)->patch(route('cases.assign', $case), [
        'investigator_id' => $investigator->id,
    ]);

    $response->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'case-assigned');

    $this->assertDatabaseHas('cases', [
        'id' => $case->id,
        'investigator_id' => $investigator->id,
        'status' => CaseStatus::Open->value,
    ]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertSee('Case assigned.');
});

test('administrator can reassign an investigator', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $firstInvestigator = caseManagementUser(UserRole::Investigator);
    $secondInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer, $firstInvestigator, ['title' => 'Reassignable Case']);
    $originalStatus = $case->status;

    $this->actingAs($admin)->patch(route('cases.assign', $case), [
        'investigator_id' => $secondInvestigator->id,
    ])->assertRedirect(route('cases.show', $case));

    $this->assertDatabaseHas('cases', [
        'id' => $case->id,
        'investigator_id' => $secondInvestigator->id,
    ]);

    expect($case->fresh()->status)->toBe($originalStatus);
});

test('assignment requires an investigator', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $originalInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer, $originalInvestigator);

    $this->actingAs($admin)->patch(route('cases.assign', $case), [])
        ->assertSessionHasErrors('investigator_id');

    expect($case->fresh()->investigator_id)->toBe($originalInvestigator->id);
});

test('assignment rejects users who are not investigators and nonexistent ids', function (?UserRole $role, bool $existing) {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $originalInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer, $originalInvestigator);
    $invalidInvestigatorId = $existing ? caseManagementUser($role)->id : 999999;

    $this->actingAs($admin)->patch(route('cases.assign', $case), [
        'investigator_id' => $invalidInvestigatorId,
    ])->assertSessionHasErrors('investigator_id');

    expect($case->fresh()->investigator_id)->toBe($originalInvestigator->id);
})->with([
    'police officer' => [UserRole::PoliceOfficer, true],
    'administrator' => [UserRole::Administrator, true],
    'nonexistent id' => [null, false],
]);

test('police officers and investigators cannot submit assignments', function (UserRole $role) {
    $actor = caseManagementUser($role);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $originalInvestigator = caseManagementUser(UserRole::Investigator);
    $newInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer, $originalInvestigator);

    $this->actingAs($actor)->patch(route('cases.assign', $case), [
        'investigator_id' => $newInvestigator->id,
    ])->assertForbidden();

    expect($case->fresh()->investigator_id)->toBe($originalInvestigator->id);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('assignment returns 404 for a missing case', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $investigator = caseManagementUser(UserRole::Investigator);

    $this->actingAs($admin)->patch(route('cases.assign', ['case' => 999999]), [
        'investigator_id' => $investigator->id,
    ])->assertNotFound();
});

test('assignment form is visible only to administrators', function (UserRole $role, bool $expected) {
    $actor = caseManagementUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : caseManagementUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : caseManagementUser(UserRole::Investigator);
    $nonInvestigator = caseManagementUser(UserRole::PoliceOfficer);
    $case = caseManagementCase($officer, $investigator, ['title' => 'Assignment Form Case']);

    $response = $this->actingAs($actor)->get(route('cases.show', $case));

    $response->assertOk();

    if ($expected) {
        $response->assertSee('Assign investigator')
            ->assertSee(route('cases.assign', $case), false)
            ->assertSee($investigator->name)
            ->assertDontSee($nonInvestigator->name);
    } else {
        $response->assertDontSee('Assign investigator')
            ->assertDontSee(route('cases.assign', $case), false);
    }
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
    'investigator' => [UserRole::Investigator, false],
]);
