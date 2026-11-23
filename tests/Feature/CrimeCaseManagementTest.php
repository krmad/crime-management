<?php

use App\Enums\CaseStatus;
use App\Enums\CaseTitleType;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\CrimeCategory;
use App\Models\Suspect;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Mockery;

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

function caseManagementCategoryHierarchy(): array
{
    $level1 = CrimeCategory::factory()->create(['name' => 'Book V — Crimes Against Individuals and the Family']);
    $level2 = CrimeCategory::factory()->childOf($level1)->create(['name' => 'Title I — Crimes Against Life, Person and Health']);
    $level3 = CrimeCategory::factory()->childOf($level2)->create(['name' => 'Homicide (Article 538)']);

    return [$level1, $level2, $level3];
}

function validCasePayload(array $overrides = []): array
{
    [$level1, $level2, $level3] = caseManagementCategoryHierarchy();

    return array_merge([
        'case_title_type' => CaseTitleType::Homicide->value,
        'level_1_category_id' => $level1->getKey(),
        'level_2_category_id' => $level2->getKey(),
        'crime_category_id' => $level3->getKey(),
        'location' => 'Downtown District',
        'description' => 'Fictional incident report for internal testing.',
        'incident_date' => now()->subDay()->toDateTimeString(),
        'suspect_name' => 'None',
    ], $overrides);
}

function caseManagementConditionalConflict(CrimeCase $staleCase, string $ability, callable $racingWrite): void
{
    $authorizationCalls = 0;

    Gate::before(function ($user, string $calledAbility, array $arguments) use (&$authorizationCalls, $ability, $racingWrite) {
        if ($calledAbility === $ability && ++$authorizationCalls === 2) {
            $racingWrite();
        }
    });

    Route::bind('case', fn (string $value) => $staleCase);

    $databaseManager = app('db');
    $databaseManagerMock = Mockery::mock($databaseManager)->makePartial();
    $databaseManagerMock->shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn (callable $callback) => $callback());

    app()->instance('db', $databaseManagerMock);
    DB::clearResolvedInstance();
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

test('case listing orders by created_at and id with deterministic pagination', function (UserRole $role) {
    $actor = caseManagementUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : caseManagementUser(UserRole::PoliceOfficer);
    $otherOfficer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : caseManagementUser(UserRole::Investigator);
    $otherInvestigator = caseManagementUser(UserRole::Investigator);

    $makeVisibleCase = function (string $createdAt) use ($role, $actor, $officer, $investigator): CrimeCase {
        return match ($role) {
            UserRole::Administrator => caseManagementCase($officer, null, ['created_at' => $createdAt]),
            UserRole::PoliceOfficer => caseManagementCase($actor, null, ['created_at' => $createdAt]),
            UserRole::Investigator => caseManagementCase($officer, $investigator, ['created_at' => $createdAt]),
        };
    };

    $baseTime = now()->subDays(30)->startOfSecond();
    $visibleCases = collect(range(1, 10))
        ->map(fn (int $day): CrimeCase => $makeVisibleCase($baseTime->copy()->addDays($day)->toDateTimeString()));

    $tieTime = $baseTime->copy()->addDays(11)->toDateTimeString();
    $visibleCases->push($makeVisibleCase($tieTime), $makeVisibleCase($tieTime));

    $excludedIds = [];

    if ($role === UserRole::PoliceOfficer) {
        $excludedIds[] = caseManagementCase($otherOfficer, null, [
            'created_at' => $baseTime->copy()->addDays(13)->toDateTimeString(),
        ])->id;
        $excludedIds[] = caseManagementCase($otherOfficer, $actor, [
            'created_at' => $baseTime->copy()->addDays(14)->toDateTimeString(),
        ])->id;
    }

    if ($role === UserRole::Investigator) {
        $excludedIds[] = caseManagementCase($actor, null, [
            'created_at' => $baseTime->copy()->addDays(13)->toDateTimeString(),
        ])->id;
        $excludedIds[] = caseManagementCase($officer, $otherInvestigator, [
            'created_at' => $baseTime->copy()->addDays(14)->toDateTimeString(),
        ])->id;
    }

    $expectedIds = $visibleCases
        ->sortByDesc(fn (CrimeCase $case): array => [$case->created_at->getTimestamp(), $case->id])
        ->pluck('id')
        ->values()
        ->all();

    $firstPage = $this->actingAs($actor)->get(route('cases.index', ['page' => 1]));
    $secondPage = $this->actingAs($actor)->get(route('cases.index', ['page' => 2]));

    $firstPage->assertOk();
    $secondPage->assertOk();

    $firstPageIds = $firstPage->viewData('cases')->getCollection()->pluck('id')->all();
    $secondPageIds = $secondPage->viewData('cases')->getCollection()->pluck('id')->all();
    $tiedIds = $visibleCases->slice(-2)->pluck('id')->all();

    expect($firstPageIds)
        ->toHaveCount(10)
        ->toBe(array_slice($expectedIds, 0, 10))
        ->and(array_slice($firstPageIds, 0, 2))
        ->toBe([$tiedIds[1], $tiedIds[0]])
        ->and($secondPageIds)
        ->toHaveCount(2)
        ->toBe(array_slice($expectedIds, 10, 2));

    foreach ($excludedIds as $excludedId) {
        expect(array_merge($firstPageIds, $secondPageIds))->not->toContain($excludedId);
    }
})->with([
    'administrator' => [UserRole::Administrator],
    'police officer' => [UserRole::PoliceOfficer],
    'investigator' => [UserRole::Investigator],
]);

test('administrators and police officers can create cases', function (UserRole $role) {
    $user = caseManagementUser($role);
    $payload = validCasePayload();

    $response = $this->actingAs($user)->post(route('cases.store'), $payload);

    $response->assertRedirect(route('cases.index'))
        ->assertSessionHas('status', 'case-created');

    $this->assertDatabaseHas('cases', [
        'title' => 'Homicide',
        'crime_type' => 'Homicide',
        'crime_category_id' => $payload['crime_category_id'],
        'custom_crime_type' => null,
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
        'case_title_type' => '',
        'level_1_category_id' => '',
        'level_2_category_id' => '',
        'crime_category_id' => '',
        'location' => '',
        'description' => '',
        'incident_date' => 'not-a-date',
    ]);

    $response->assertSessionHasErrors([
        'case_title_type',
        'level_1_category_id',
        'level_2_category_id',
        'crime_category_id',
        'location',
        'description',
        'incident_date',
    ]);

    $this->assertDatabaseCount('cases', 0);
});

test('incident location is required and cannot be blank or whitespace only', function (?string $location, string $message) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload([
            'location' => $location,
        ]))
        ->assertRedirect(route('cases.create'))
        ->assertSessionHasErrors([
            'location' => $message,
        ]);

    $this->assertDatabaseCount('cases', 0);
})->with([
    'missing' => [null, 'The location field is required.'],
    'empty string' => ['', 'The location field is required.'],
    'whitespace only' => ['   ', 'The location field is required.'],
]);

test('incident location validation error is displayed on the case creation form', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)
        ->followingRedirects()
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload([
            'location' => '   ',
        ]))
        ->assertOk()
        ->assertSee('The location field is required.');
});

test('case creation form treats the suspect name as optional and explains the None sentinel', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)->get(route('cases.create'))
        ->assertOk()
        ->assertSee('name="location"', false)
        ->assertSee('Location <span class="text-danger" aria-hidden="true">*</span>', false)
        ->assertSee('name="suspect_name"', false)
        ->assertDontSee('Suspect Full Name (optional) <span class="text-danger" aria-hidden="true">*</span>', false)
        ->assertSee('Suspect Full Name (optional)', false)
        ->assertSee('Leave blank or enter None if the suspect is unknown', false)
        ->assertSee('name="suspect_alias"', false);
});

test('case creation can create and link a suspect in one transaction', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)->post(route('cases.store'), validCasePayload([
        'suspect_name' => '  New Case Suspect  ',
        'suspect_alias' => '  Night Falcon  ',
    ]))
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'case-created');

    $case = CrimeCase::firstOrFail();
    $suspect = Suspect::where('full_name', 'New Case Suspect')->firstOrFail();

    expect($suspect->alias)->toBe('Night Falcon');

    $this->assertDatabaseHas('case_suspect', [
        'case_id' => $case->getKey(),
        'suspect_id' => $suspect->getKey(),
        'status' => 'identified',
    ]);

    $this->actingAs($officer)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('New Case Suspect')
        ->assertSee('Night Falcon');
});

test('case creation accepts an omitted blank or whitespace only suspect name', function (?string $suspectName) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $payload = validCasePayload();

    if ($suspectName === null) {
        unset($payload['suspect_name']);
    } else {
        $payload['suspect_name'] = $suspectName;
    }

    $this->actingAs($officer)->post(route('cases.store'), $payload)
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'case-created');

    $case = CrimeCase::firstOrFail();

    expect(Suspect::count())->toBe(0)
        ->and($case->suspects()->count())->toBe(0);

    $this->assertDatabaseCount('cases', 1)
        ->assertDatabaseCount('case_suspect', 0);

    $this->actingAs($officer)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('No known suspect at time of report');
})->with([
    'omitted' => [null],
    'empty string' => [''],
    'whitespace only' => ['   '],
]);

test('unknown suspect creates a case without suspect records and displays the report note', function (string $suspectName) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)->post(route('cases.store'), validCasePayload([
        'suspect_name' => $suspectName,
    ]))
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'case-created');

    $case = CrimeCase::firstOrFail();

    expect(Suspect::count())->toBe(0)
        ->and($case->suspects()->count())->toBe(0);

    $this->assertDatabaseCount('case_suspect', 0);

    $this->actingAs($officer)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('No known suspect at time of report');
})->with([
    'None' => ['None'],
    'lowercase none' => ['none'],
    'uppercase NONE' => ['NONE'],
    'padded mixed case none' => [' nOnE '],
]);

test('an unknown or missing suspect rejects a submitted alias without creating a case', function (string $suspectName) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload([
            'suspect_name' => $suspectName,
            'suspect_alias' => 'Secret Alias',
        ]))
        ->assertRedirect(route('cases.create'))
        ->assertSessionHasErrors([
            'suspect_alias' => 'An alias cannot be entered when the suspect is unknown.',
        ]);

    $this->assertDatabaseCount('cases', 0)
        ->assertDatabaseCount('suspects', 0)
        ->assertDatabaseCount('case_suspect', 0);
})->with([
    'None' => ['None'],
    'lowercase none' => ['none'],
    'uppercase NONE' => ['NONE'],
    'blank suspect name' => [''],
    'whitespace only suspect name' => ['   '],
]);

test('suspect name and alias length validation rejects overlong values', function (array $overrides, string $field, string $message) {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload($overrides))
        ->assertRedirect(route('cases.create'))
        ->assertSessionHasErrors([
            $field => $message,
        ]);

    $this->assertDatabaseCount('cases', 0)
        ->assertDatabaseCount('suspects', 0)
        ->assertDatabaseCount('case_suspect', 0);
})->with([
    'suspect name too long' => [
        ['suspect_name' => str_repeat('a', 256)],
        'suspect_name',
        'The suspect full name field must not be greater than 255 characters.',
    ],
    'alias too long' => [
        ['suspect_alias' => str_repeat('a', 256)],
        'suspect_alias',
        'The suspect alias field must not be greater than 255 characters.',
    ],
]);

test('description of exactly 10000 characters is accepted and saved', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $description = str_repeat('a', 10000);

    $response = $this->actingAs($officer)->post(route('cases.store'), validCasePayload([
        'description' => $description,
    ]));

    $response
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'case-created');

    $this->assertDatabaseHas('cases', [
        'title' => 'Homicide',
        'description' => $description,
        'created_by' => $officer->id,
    ]);
});

test('description over 10000 characters is rejected without creating a case', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $response = $this->actingAs($officer)
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload([
            'description' => str_repeat('a', 10001),
        ]));

    $response
        ->assertSessionHasErrors([
            'description' => 'The description field must not be greater than 10000 characters.',
        ])
        ->assertRedirect(route('cases.create'));

    $this->assertDatabaseCount('cases', 0);
});

test('description length validation error is displayed on the case creation form', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)
        ->followingRedirects()
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload([
            'description' => str_repeat('a', 10001),
        ]))
        ->assertOk()
        ->assertSee('The description field must not be greater than 10000 characters.');
});

test('future incident date is rejected without creating a case', function () {
    $this->freezeTime();
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $response = $this->actingAs($officer)
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload([
            'incident_date' => now()->addDay()->toDateTimeString(),
        ]));

    $response
        ->assertSessionHasErrors([
            'incident_date' => 'The incident date field must be a date before or equal to now.',
        ])
        ->assertRedirect(route('cases.create'));

    $this->assertDatabaseCount('cases', 0);
});

test('past incident date is accepted and saved', function () {
    $this->freezeTime();
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $incidentDate = now()->subDay()->toDateTimeString();

    $response = $this->actingAs($officer)->post(route('cases.store'), validCasePayload([
        'incident_date' => $incidentDate,
    ]));

    $response
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('cases', [
        'title' => 'Homicide',
        'incident_date' => $incidentDate,
        'created_by' => $officer->id,
    ]);
});

test('today incident date is accepted and saved', function () {
    $this->freezeTime();
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $incidentDate = now()->toDateTimeString();

    $response = $this->actingAs($officer)->post(route('cases.store'), validCasePayload([
        'incident_date' => $incidentDate,
    ]));

    $response
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('cases', [
        'title' => 'Homicide',
        'incident_date' => $incidentDate,
        'created_by' => $officer->id,
    ]);
});

test('omitted incident date remains accepted', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $payload = validCasePayload();

    unset($payload['incident_date']);

    $response = $this->actingAs($officer)->post(route('cases.store'), $payload);

    $response
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('cases', [
        'title' => 'Homicide',
        'incident_date' => null,
        'created_by' => $officer->id,
    ]);
});

test('future incident date error is displayed on the case creation form', function () {
    $this->freezeTime();
    $officer = caseManagementUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)
        ->followingRedirects()
        ->from(route('cases.create'))
        ->post(route('cases.store'), validCasePayload([
            'incident_date' => now()->addDay()->toDateTimeString(),
        ]))
        ->assertOk()
        ->assertSee('The incident date field must be a date before or equal to now.');
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

test('administrator cannot reassign an investigator on a closed case', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $originalInvestigator = caseManagementUser(UserRole::Investigator);
    $newInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $originalInvestigator, CaseStatus::Closed, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Final judgment notes.',
        'closed_at' => now(),
    ]);

    $this->actingAs($admin)->patch(route('cases.assign', $case), [
        'investigator_id' => $newInvestigator->id,
    ])->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->investigator_id)->toBe($originalInvestigator->id)
        ->and($freshCase->status)->toBe(CaseStatus::Closed);
});

test('administrator cannot assign an investigator when the case was closed after authorization', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $originalInvestigator = caseManagementUser(UserRole::Investigator);
    $newInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $originalInvestigator, CaseStatus::Open);
    $staleCase = $case->fresh();

    $case->forceFill([
        'status' => CaseStatus::Closed,
        'judgment_notes' => 'Final judgment notes.',
        'closed_at' => now(),
    ])->save();

    Route::bind('case', fn (string $value) => $staleCase);

    $this->actingAs($admin)->patch(route('cases.assign', $case), [
        'investigator_id' => $newInvestigator->id,
    ])->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->investigator_id)->toBe($originalInvestigator->id)
        ->and($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at)->not->toBeNull();
});

test('assignment returns 409 when the case is closed after the fresh read', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $originalInvestigator = caseManagementUser(UserRole::Investigator);
    $newInvestigator = caseManagementUser(UserRole::Investigator);
    $closedAt = now()->subMinute()->startOfSecond();
    $case = caseManagementAssignedCase($officer, $originalInvestigator, CaseStatus::Open);
    $staleCase = $case->fresh();

    caseManagementConditionalConflict($staleCase, 'assign', function () use ($case, $closedAt) {
        CrimeCase::whereKey($case->getKey())->update([
            'status' => CaseStatus::Closed->value,
            'judgment_notes' => 'Final judgment notes.',
            'closed_at' => $closedAt,
        ]);
    });

    $this->actingAs($admin)->patch(route('cases.assign', $case), [
        'investigator_id' => $newInvestigator->id,
    ])->assertStatus(409);

    $freshCase = $case->fresh();

    expect($freshCase->investigator_id)->toBe($originalInvestigator->id)
        ->and($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at?->toDateTimeString())->toBe($closedAt->toDateTimeString());
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

test('assignment form is hidden on closed cases', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::Closed, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Final judgment notes.',
        'closed_at' => now(),
    ]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertDontSee('Assign investigator')
        ->assertDontSee(route('cases.assign', $case), false);
});

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

test('former investigator cannot save notes after the case was reassigned', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $originalInvestigator = caseManagementUser(UserRole::Investigator);
    $newInvestigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $originalInvestigator, CaseStatus::UnderInvestigation);
    $staleCase = $case->fresh();

    $case->forceFill(['investigator_id' => $newInvestigator->id])->save();

    Route::bind('case', fn (string $value) => $staleCase);

    $this->actingAs($originalInvestigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Attempted notes by former investigator.',
    ])->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->investigation_notes)->toBeNull()
        ->and($freshCase->investigator_id)->toBe($newInvestigator->id);
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

test('administrator cannot update investigation notes on judgment recorded or closed cases', function (CaseStatus $status) {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, $status, [
        'investigation_notes' => 'Existing investigation notes.',
    ]);

    $this->actingAs($admin)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Administrator retrospective notes.',
    ])->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->investigation_notes)->toBe('Existing investigation notes.')
        ->and($freshCase->status)->toBe($status);
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

test('investigation notes cannot be saved when the case was closed after authorization', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::Open);
    $staleCase = $case->fresh();

    $case->forceFill([
        'status' => CaseStatus::Closed,
        'judgment_notes' => 'Final judgment notes.',
        'closed_at' => now(),
    ])->save();

    Route::bind('case', fn (string $value) => $staleCase);

    $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Notes submitted after closure.',
    ])->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->investigation_notes)->toBeNull()
        ->and($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at)->not->toBeNull();
});

test('investigation update returns 409 when the case is closed after the fresh read', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $closedAt = now()->subMinute()->startOfSecond();
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::Open);
    $staleCase = $case->fresh();

    caseManagementConditionalConflict($staleCase, 'updateInvestigation', function () use ($case, $closedAt) {
        CrimeCase::whereKey($case->getKey())->update([
            'status' => CaseStatus::Closed->value,
            'judgment_notes' => 'Final judgment notes.',
            'closed_at' => $closedAt,
        ]);
    });

    $this->actingAs($investigator)->patch(route('cases.update_investigation', $case), [
        'investigation_notes' => 'Notes submitted after closure.',
    ])->assertStatus(409);

    $freshCase = $case->fresh();

    expect($freshCase->investigation_notes)->toBeNull()
        ->and($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at?->toDateTimeString())->toBe($closedAt->toDateTimeString());
});

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

test('judgment cannot be recorded when the case was closed after authorization', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Investigation notes are complete.',
    ]);
    $staleCase = $case->fresh();

    $case->forceFill([
        'status' => CaseStatus::Closed,
        'judgment_notes' => 'Judgment recorded before closure.',
        'closed_at' => now(),
    ])->save();

    Route::bind('case', fn (string $value) => $staleCase);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Judgment submitted after closure.',
    ])->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->judgment_notes)->toBe('Judgment recorded before closure.')
        ->and($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at)->not->toBeNull();
});

test('judgment cannot be edited when the case was closed after authorization', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Original judgment notes.',
    ]);
    $staleCase = $case->fresh();

    $case->forceFill([
        'status' => CaseStatus::Closed,
        'closed_at' => now(),
    ])->save();

    Route::bind('case', fn (string $value) => $staleCase);

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Edited judgment notes.',
    ])->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->judgment_notes)->toBe('Original judgment notes.')
        ->and($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at)->not->toBeNull();
});

test('judgment recording returns 409 when the case is closed after the fresh read', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $closedAt = now()->subMinute()->startOfSecond();
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::UnderInvestigation, [
        'investigation_notes' => 'Investigation notes are complete.',
    ]);
    $staleCase = $case->fresh();

    caseManagementConditionalConflict($staleCase, 'recordJudgment', function () use ($case, $closedAt) {
        CrimeCase::whereKey($case->getKey())->update([
            'status' => CaseStatus::Closed->value,
            'judgment_notes' => 'Judgment recorded before closure.',
            'closed_at' => $closedAt,
        ]);
    });

    $this->actingAs($admin)->patch(route('cases.record_judgment', $case), [
        'judgment_notes' => 'Judgment submitted after closure.',
    ])->assertStatus(409);

    $freshCase = $case->fresh();

    expect($freshCase->judgment_notes)->toBe('Judgment recorded before closure.')
        ->and($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at?->toDateTimeString())->toBe($closedAt->toDateTimeString());
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

test('guests cannot close cases', function () {
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'judgment_notes' => 'Judgment notes are complete.',
    ]);

    $this->patch(route('cases.close', $case))
        ->assertRedirect(route('login'));

    expect($case->fresh()->status)->toBe(CaseStatus::JudgmentRecorded)
        ->and($case->fresh()->closed_at)->toBeNull();
});

test('administrator can close a case with recorded judgment', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $judgmentNotes = 'Final judgment notes.';
    $investigationNotes = 'Investigation notes are complete.';
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => $investigationNotes,
        'judgment_notes' => $judgmentNotes,
    ]);

    $this->actingAs($admin)
        ->patch(route('cases.close', $case))
        ->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'case-closed');

    $freshCase = $case->fresh();

    expect($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at)->not->toBeNull()
        ->and($freshCase->judgment_notes)->toBe($judgmentNotes)
        ->and($freshCase->investigation_notes)->toBe($investigationNotes)
        ->and($freshCase->investigator_id)->toBe($investigator->id);
});

test('closure success message and closure date are displayed', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Final judgment notes.',
    ]);

    $response = $this->actingAs($admin)
        ->followingRedirects()
        ->from(route('cases.show', $case))
        ->patch(route('cases.close', $case))
        ->assertOk();

    $closureDate = $case->fresh()->closed_at->format('M d, Y h:i A');

    $response->assertSee('Case closed.')
        ->assertSee('Closure date')
        ->assertSee($closureDate);
});

test('administrator cannot close cases without a recorded judgment', function (CaseStatus $status, ?string $judgmentNotes) {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $originalClosedAt = $status === CaseStatus::Closed ? now()->subDay()->startOfSecond() : null;
    $case = caseManagementAssignedCase($officer, $investigator, $status, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => $judgmentNotes,
        'closed_at' => $originalClosedAt,
    ]);

    $this->actingAs($admin)
        ->patch(route('cases.close', $case))
        ->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->status)->toBe($status)
        ->and($freshCase->closed_at?->toDateTimeString())->toBe($originalClosedAt?->toDateTimeString());
})->with([
    'open case' => [CaseStatus::Open, null],
    'case under investigation' => [CaseStatus::UnderInvestigation, 'Judgment notes are complete.'],
    'recorded judgment without notes' => [CaseStatus::JudgmentRecorded, null],
    'recorded judgment with empty notes' => [CaseStatus::JudgmentRecorded, ''],
    'recorded judgment with whitespace notes' => [CaseStatus::JudgmentRecorded, '   '],
    'closed case' => [CaseStatus::Closed, 'Final judgment notes.'],
]);

test('investigators and police officers cannot close cases', function (UserRole $role) {
    $actor = caseManagementUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : caseManagementUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Final judgment notes.',
    ]);

    $this->actingAs($actor)
        ->patch(route('cases.close', $case))
        ->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->status)->toBe(CaseStatus::JudgmentRecorded)
        ->and($freshCase->closed_at)->toBeNull()
        ->and($freshCase->judgment_notes)->toBe('Final judgment notes.');
})->with([
    'investigator' => [UserRole::Investigator],
    'police officer' => [UserRole::PoliceOfficer],
]);

test('closed cases cannot be closed again or reopened', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $closedAt = now()->subDay()->startOfSecond();
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::Closed, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Final judgment notes.',
        'closed_at' => $closedAt,
    ]);

    $this->actingAs($admin)
        ->patch(route('cases.close', $case))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('cases.record_judgment', $case), [
            'judgment_notes' => 'Attempted reopen judgment.',
        ])
        ->assertForbidden();

    $freshCase = $case->fresh();

    expect($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at?->toDateTimeString())->toBe($closedAt->toDateTimeString())
        ->and($freshCase->judgment_notes)->toBe('Final judgment notes.');
});

test('closure returns 409 when another request closes the case after the fresh read', function () {
    $admin = caseManagementUser(UserRole::Administrator);
    $officer = caseManagementUser(UserRole::PoliceOfficer);
    $investigator = caseManagementUser(UserRole::Investigator);
    $racingClosedAt = now()->subMinute()->startOfSecond();
    $case = caseManagementAssignedCase($officer, $investigator, CaseStatus::JudgmentRecorded, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => 'Final judgment notes.',
    ]);
    $staleCase = $case->fresh();

    caseManagementConditionalConflict($staleCase, 'close', function () use ($case, $racingClosedAt) {
        CrimeCase::whereKey($case->getKey())->update([
            'status' => CaseStatus::Closed->value,
            'closed_at' => $racingClosedAt,
        ]);
    });

    $this->actingAs($admin)
        ->patch(route('cases.close', $case))
        ->assertStatus(409);

    $freshCase = $case->fresh();

    expect($freshCase->status)->toBe(CaseStatus::Closed)
        ->and($freshCase->closed_at?->toDateTimeString())->toBe($racingClosedAt->toDateTimeString())
        ->and($freshCase->judgment_notes)->toBe('Final judgment notes.');
});

test('close form is visible only to eligible administrators', function (UserRole $role, CaseStatus $status, ?string $judgmentNotes, bool $expected) {
    $actor = caseManagementUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : caseManagementUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : caseManagementUser(UserRole::Investigator);
    $case = caseManagementAssignedCase($officer, $investigator, $status, [
        'investigation_notes' => 'Investigation notes are complete.',
        'judgment_notes' => $judgmentNotes,
    ]);

    $response = $this->actingAs($actor)->get(route('cases.show', $case));

    $response->assertOk();

    if ($expected) {
        $response->assertSee(route('cases.close', $case), false)
            ->assertSee('Close case');
    } else {
        $response->assertDontSee(route('cases.close', $case), false)
            ->assertDontSee('Close case');
    }
})->with([
    'administrator with recorded judgment' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, 'Final judgment notes.', true],
    'administrator with open case' => [UserRole::Administrator, CaseStatus::Open, null, false],
    'administrator with case under investigation' => [UserRole::Administrator, CaseStatus::UnderInvestigation, 'Judgment notes are complete.', false],
    'administrator without judgment notes' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, null, false],
    'administrator with whitespace judgment notes' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, '   ', false],
    'administrator on closed case' => [UserRole::Administrator, CaseStatus::Closed, 'Final judgment notes.', false],
    'investigator with recorded judgment' => [UserRole::Investigator, CaseStatus::JudgmentRecorded, 'Final judgment notes.', false],
    'police officer with recorded judgment' => [UserRole::PoliceOfficer, CaseStatus::JudgmentRecorded, 'Final judgment notes.', false],
]);
