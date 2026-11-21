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

function caseManagementAssignedCase(User $creator, User $investigator, CaseStatus $status = CaseStatus::Open, array $attributes = []): CrimeCase
{
    $case = caseManagementCase($creator, null, array_merge($attributes, ['status' => $status]));
    $case->forceFill(['investigator_id' => $investigator->id])->save();

    return $case;
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

test('guests cannot update investigation notes', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator);

    $this->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Unauthorized guest note',
    ])->assertRedirect(route('login'));

    $this->assertGuest();

    expect($case->fresh()->investigation_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::Open);
});

test('assigned investigator can record investigation notes and starts investigation', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::Open);

    $response = $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Interviewed the witness near the depot.',
    ]);

    $response->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'investigation-updated');

    $this->assertDatabaseHas('cases', [
        'id' => $case->id,
        'investigation_notes' => 'Interviewed the witness near the depot.',
        'status' => CaseStatus::UnderInvestigation->value,
    ]);

    $this->actingAs($investigator)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Investigation notes saved.')
        ->assertSee('Interviewed the witness near the depot.');
});

test('investigator can add further notes without changing under investigation status', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Initial case notes.',
    ]);

    $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Follow-up notes after reviewing footage.',
    ])->assertRedirect(route('cases.show', $case));

    $freshCase = $case->fresh();

    expect($freshCase->investigation_notes)->toBe('Follow-up notes after reviewing footage.')
        ->and($freshCase->status)->toBe(CaseStatus::UnderInvestigation);
});

test('unassigned investigator cannot update investigation notes', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementCase($officer);

    $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Unassigned note attempt',
    ])->assertForbidden();

    expect($case->fresh()->investigation_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::Open);
});

test('investigator cannot update investigation notes for another investigator case', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $assignedInvestigator = caseManagementUser(UserRole::Investigator);
    $otherInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $assignedInvestigator, CaseStatus::UnderInvestigation);

    $this->actingAs($otherInvestigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Other investigator note attempt',
    ])->assertForbidden();

    expect($case->fresh()->investigation_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::UnderInvestigation);
});

test('police officer cannot update investigation notes on own case', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator);

    $this->actingAs($officer)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Officer note attempt',
    ])->assertForbidden();

    expect($case->fresh()->investigation_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::Open);
});

test('administrator can record investigation notes on any case', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $case = caseManagementCase($officer);

    $this->actingAs($admin)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Administrator oversight notes.',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'investigation-updated');

    expect($case->fresh()->investigation_notes)->toBe('Administrator oversight notes.')
        ->and($case->fresh()->status)->toBe(CaseStatus::UnderInvestigation);
});

test('administrator can record investigation notes without downgrading a terminal case', function (CaseStatus $status) {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, $status);

    $this->actingAs($admin)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Administrator retrospective notes.',
    ])->assertRedirect(route('cases.show', $case));

    expect($case->fresh()->investigation_notes)->toBe('Administrator retrospective notes.')
        ->and($case->fresh()->status)->toBe($status);
})->with([
    CaseStatus::JudgmentRecorded,
    CaseStatus::Closed,
]);

test('invalid investigation notes are rejected without modifying the case', function (array $payload) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Existing investigation notes.',
    ]);

    $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), $payload)
        ->assertSessionHasErrors('investigation_notes');

    $freshCase = $case->fresh();

    expect($freshCase->investigation_notes)->toBe('Existing investigation notes.')
        ->and($freshCase->status)->toBe(CaseStatus::UnderInvestigation);
})->with([
    'missing notes' => [[]],
    'empty notes' => [['investigation_notes' => '']],
    'notes too long' => [['investigation_notes' => str_repeat('a', 5001)]],
    'non string notes' => [['investigation_notes' => ['unexpected']]],
]);

test('investigation validation errors are displayed on the case details page', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator);

    $this->actingAs($investigator)
        ->followingRedirects()
        ->from(route('cases.show', $case))
        ->patch(route('cases.update_investigation', $case), [])
        ->assertOk()
        ->assertSee('The investigation notes field is required.');
});

test('investigators cannot update investigation notes on judgment recorded or closed cases', function (CaseStatus $status) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, $status, [
        'investigation_notes' => 'Existing investigation notes.',
    ]);

    $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Attempted terminal status update.',
    ])->assertForbidden();

    expect($case->fresh()->investigation_notes)->toBe('Existing investigation notes.')
        ->and($case->fresh()->status)->toBe($status);
})->with([
    CaseStatus::JudgmentRecorded,
    CaseStatus::Closed,
]);

test('investigation update ignores protected case fields', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $otherInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator);

    $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Valid investigation notes.',
        'status' => CaseStatus::Closed->value,
        'judgment_notes' => 'Injected judgment notes.',
        'closed_at' => now()->toDateTimeString(),
        'investigator_id' => $otherInvestigator->id,
    ])->assertRedirect(route('cases.show', $case));

    $freshCase = $case->fresh();

    expect($freshCase->investigation_notes)->toBe('Valid investigation notes.')
        ->and($freshCase->status)->toBe(CaseStatus::UnderInvestigation)
        ->and($freshCase->judgment_notes)->toBeNull()
        ->and($freshCase->closed_at)->toBeNull()
        ->and($freshCase->investigator_id)->toBe($investigator->id);
});

test('investigation update form is visible only to authorized users', function (UserRole $role, bool $expected) {
    $actor = caseManagementUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : caseManagementUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation);

    $response = $this->actingAs($actor)->get(route('cases.show', $case));

    $response->assertOk();

    if ($expected) {
        $response->assertSee('Update investigation')
            ->assertSee(route('cases.update_investigation', $case), false)
            ->assertSee('Save investigation notes');
    } else {
        $response->assertDontSee('Update investigation')
            ->assertDontSee(route('cases.update_investigation', $case), false)
            ->assertDontSee('Save investigation notes');
    }
})->with([
    'administrator' => [UserRole::Administrator, true],
    'assigned investigator' => [UserRole::Investigator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
]);

test('investigation update form is hidden from investigators on terminal cases', function (CaseStatus $status) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, $status);

    $this->actingAs($investigator)->get(route('cases.show', $case))
        ->assertOk()
        ->assertDontSee('Update investigation')
        ->assertDontSee(route('cases.update_investigation', $case), false);
})->with([
    CaseStatus::JudgmentRecorded,
    CaseStatus::Closed,
]);

test('investigation notes are escaped when displayed', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $rawNotes = '<script>alert("investigation")</script>';
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => $rawNotes,
    ]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee(e($rawNotes), false)
        ->assertDontSee($rawNotes, false);
});

test('guests cannot record or edit judgments', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Investigation notes.',
    ]);

    $this->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Unauthorized guest judgment.',
    ])->assertRedirect(route('login'));

    $this->assertGuest();

    expect($case->fresh()->judgment_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::UnderInvestigation);
});

test('administrator can record a judgment after investigation', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Investigation notes are complete.',
    ]);

    $response = $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'The judgment is recorded for this case.',
    ]);

    $response->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'judgment-recorded');

    $this->assertDatabaseHas('cases', [
        'id' => $case->id,
        'judgment_notes' => 'The judgment is recorded for this case.',
        'status' => CaseStatus::JudgmentRecorded->value,
        'investigation_notes' => 'Investigation notes are complete.',
        'investigator_id' => $investigator->id,
        'closed_at' => null,
    ]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Judgment saved.')
        ->assertSee('The judgment is recorded for this case.');
});

test('administrator can edit an existing judgment and preserves judgment recorded status', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Original judgment notes.',
    ]);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Updated judgment notes.',
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'judgment-recorded');

    $freshCase = $case->fresh();

    expect($freshCase->judgment_notes)->toBe('Updated judgment notes.')
        ->and($freshCase->status)->toBe(CaseStatus::JudgmentRecorded)
        ->and($freshCase->closed_at)->toBeNull();

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Judgment saved.')
        ->assertSee('Updated judgment notes.');
});

test('investigators and police officers cannot record or edit judgments', function (UserRole $role, CaseStatus $status) {
    $actor = caseManagementUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : caseManagementUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : caseManagementUser(UserRole::Investigator);
    $originalNotes = $status === CaseStatus::JudgmentRecorded ? 'Original judgment notes.' : null;
    $case = caseManagementAssignedCase($officer, $investigator, $status, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => $originalNotes,
    ]);

    $this->actingAs($actor)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Unauthorized judgment attempt.',
    ])->assertForbidden();

    expect($case->fresh()->judgment_notes)->toBe($originalNotes)
        ->and($case->fresh()->status)->toBe($status);
})->with([
    'investigator records' => [UserRole::Investigator, CaseStatus::UnderInvestigation],
    'investigator edits' => [UserRole::Investigator, CaseStatus::JudgmentRecorded],
    'police officer records' => [UserRole::PoliceOfficer, CaseStatus::UnderInvestigation],
    'police officer edits' => [UserRole::PoliceOfficer, CaseStatus::JudgmentRecorded],
]);

test('administrator cannot record a judgment before investigation', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $case = caseManagementCase($officer, null, [
        'investigation_notes' => 'Notes saved on an open case.',
    ]);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Premature judgment.',
    ])->assertForbidden();

    expect($case->fresh()->judgment_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::Open);
});

test('administrator cannot record a judgment without investigation notes', function (?string $investigationNotes) {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => $investigationNotes,
    ]);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Premature judgment.',
    ])->assertForbidden();

    expect($case->fresh()->judgment_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::UnderInvestigation);
})->with([
    'null investigation notes' => [null],
    'empty investigation notes' => [''],
]);

test('administrator cannot modify judgments on closed cases', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::Closed, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Original judgment notes.',
    ]);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Attempted closed case edit.',
    ])->assertForbidden();

    expect($case->fresh()->judgment_notes)->toBe('Original judgment notes.')
        ->and($case->fresh()->status)->toBe(CaseStatus::Closed);
});

test('invalid judgment notes are rejected without modifying the case', function (array $payload) {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Investigation notes are complete.',
    ]);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), $payload)
        ->assertSessionHasErrors('judgment_notes');

    expect($case->fresh()->judgment_notes)->toBeNull()
        ->and($case->fresh()->status)->toBe(CaseStatus::UnderInvestigation);
})->with([
    'missing notes' => [[]],
    'empty notes' => [['judgment_notes' => '']],
    'non string notes' => [['judgment_notes' => ['unexpected']]],
    'notes too long' => [['judgment_notes' => str_repeat('a', 5001)]],
]);

test('judgment validation errors are displayed on the case details page', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Investigation notes are complete.',
    ]);

    $this->actingAs($admin)
        ->followingRedirects()
        ->from(route('cases.show', $case))
        ->patch(route('cases.record_judgment', $case), [])
        ->assertOk()
        ->assertSee('The judgment notes field is required.');
});

test('judgment update ignores protected case fields', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $otherInvestigator = caseManagementUser(UserRole::Investigator);
    $closedAt = now()->subDay()->startOfSecond();
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Original judgment notes.',
        'closed_at' => $closedAt,
    ]);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Updated judgment notes.',
        'status' => CaseStatus::Closed->value,
        'closed_at' => now()->toDateTimeString(),
        'investigator_id' => $otherInvestigator->id,
    ])->assertRedirect(route('cases.show', $case));

    $freshCase = $case->fresh();

    expect($freshCase->judgment_notes)->toBe('Updated judgment notes.')
        ->and($freshCase->status)->toBe(CaseStatus::JudgmentRecorded)
        ->and($freshCase->investigator_id)->toBe($investigator->id)
        ->and($freshCase->closed_at?->toDateTimeString())->toBe($closedAt->toDateTimeString());
});

test('judgment form is visible only to authorized users and eligible statuses', function (UserRole $role, CaseStatus $status, bool $expected) {
    $actor = caseManagementUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : caseManagementUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, $status, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => $status === CaseStatus::JudgmentRecorded ? 'Existing judgment notes.' : null,
    ]);

    $response = $this->actingAs($actor)->get(route('cases.show', $case));

    $response->assertOk();

    if ($expected) {
        $response->assertSee('Judgment')
            ->assertSee(route('cases.record_judgment', $case), false)
            ->assertSee('Save judgment');
    } else {
        $response->assertDontSee(route('cases.record_judgment', $case), false)
            ->assertDontSee('Save judgment');
    }
})->with([
    'administrator after investigation' => [UserRole::Administrator, CaseStatus::UnderInvestigation, true],
    'administrator editing judgment' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, true],
    'administrator before investigation' => [UserRole::Administrator, CaseStatus::Open, false],
    'administrator on closed case' => [UserRole::Administrator, CaseStatus::Closed, false],
    'investigator after investigation' => [UserRole::Investigator, CaseStatus::UnderInvestigation, false],
    'investigator editing judgment' => [UserRole::Investigator, CaseStatus::JudgmentRecorded, false],
    'police officer after investigation' => [UserRole::PoliceOfficer, CaseStatus::UnderInvestigation, false],
    'police officer editing judgment' => [UserRole::PoliceOfficer, CaseStatus::JudgmentRecorded, false],
]);

test('judgment notes are escaped when displayed', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $rawNotes = '<script>alert("judgment")</script>';
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => $rawNotes,
    ]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee(e($rawNotes), false)
        ->assertDontSee($rawNotes, false);
});
