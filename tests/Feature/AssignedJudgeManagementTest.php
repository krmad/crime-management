<?php

use App\Enums\CaseStatus;
use App\Enums\CaseTitleType;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\CrimeCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

function assignedJudgeUser(UserRole $role, bool $active = true): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => $active])->save();

    return $user;
}

function assignedJudgeCategoryHierarchy(): array
{
    $level1 = CrimeCategory::factory()->create(['name' => 'Book V — Crimes Against Individuals and the Family']);
    $level2 = CrimeCategory::factory()->childOf($level1)->create(['name' => 'Title I — Crimes Against Life, Person and Health']);
    $level3 = CrimeCategory::factory()->childOf($level2)->create(['name' => 'Homicide (Article 538)']);

    return [$level1, $level2, $level3];
}

function assignedJudgeCasePayload(array $overrides = []): array
{
    [, , $level3] = assignedJudgeCategoryHierarchy();

    return array_merge([
        'case_title_type' => CaseTitleType::Homicide->value,
        'level_1_category_id' => $level3->parent->parent->getKey(),
        'level_2_category_id' => $level3->parent->getKey(),
        'crime_category_id' => $level3->getKey(),
        'location' => 'Downtown District',
        'description' => 'Fictional incident report for internal testing.',
        'incident_date' => now()->subDay()->toDateTimeString(),
        'suspect_name' => 'None',
    ], $overrides);
}

function assignedJudgeConditionalConflict(CrimeCase $staleCase, string $ability, callable $racingWrite): void
{
    $authorizationCalls = 0;

    Gate::before(function ($user, string $calledAbility, array $arguments) use (&$authorizationCalls, $ability, $racingWrite): void {
        if ($calledAbility === $ability && ++$authorizationCalls === 2) {
            $racingWrite();
        }
    });

    Route::bind('case', fn (string $value): CrimeCase => $staleCase);

    $databaseManager = app('db');
    $databaseManagerMock = Mockery::mock($databaseManager)->makePartial();
    $databaseManagerMock->shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn (callable $callback): mixed => $callback());

    app()->instance('db', $databaseManagerMock);
    DB::clearResolvedInstance();
}

test('administrator creation form lists only active judges', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $activeJudge = assignedJudgeUser(UserRole::Judge);
    $inactiveJudge = assignedJudgeUser(UserRole::Judge, false);

    $response = $this->actingAs($admin)->get(route('cases.create'))->assertOk();

    expect($response->viewData('judges')->pluck('id')->all())->toBe([$activeJudge->id]);

    $response->assertSee($activeJudge->name)
        ->assertDontSee($inactiveJudge->name);
});

test('administrator can assign a judge while creating a case', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $judge = assignedJudgeUser(UserRole::Judge);

    $this->actingAs($admin)->post(route('cases.store'), assignedJudgeCasePayload([
        'judge_id' => $judge->id,
    ]))->assertRedirect(route('cases.index'))
        ->assertSessionHas('status', 'case-created');

    $case = CrimeCase::firstOrFail();

    expect($case->judge_id)->toBe($judge->id)
        ->and($case->statusHistories()->count())->toBe(0);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee(__('Assigned Judge'))
        ->assertSee($judge->name);
});

test('administrator can assign and change a judge before forwarding', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $firstJudge = assignedJudgeUser(UserRole::Judge);
    $secondJudge = assignedJudgeUser(UserRole::Judge);
    $case = CrimeCase::factory()->withCreator($officer)->create();

    $this->actingAs($admin)->patch(route('cases.assign_judge', $case), [
        'judge_id' => $firstJudge->id,
    ])->assertRedirect(route('cases.show', $case))
        ->assertSessionHas('status', 'case-judge-assigned');

    expect($case->fresh()->judge_id)->toBe($firstJudge->id)
        ->and($case->statusHistories()->count())->toBe(1);

    $history = $case->statusHistories()->latest('id')->firstOrFail();

    expect($history->actor_id)->toBe($admin->id)
        ->and($history->previous_status)->toBe(CaseStatus::Open)
        ->and($history->new_status)->toBe(CaseStatus::Open)
        ->and($history->reason)->toContain($firstJudge->name);

    $this->actingAs($admin)->patch(route('cases.assign_judge', $case), [
        'judge_id' => $secondJudge->id,
    ])->assertRedirect(route('cases.show', $case));

    expect($case->fresh()->judge_id)->toBe($secondJudge->id)
        ->and($case->statusHistories()->count())->toBe(2);

    $latestHistory = $case->statusHistories()->latest('id')->firstOrFail();

    expect($latestHistory->previous_status)->toBe(CaseStatus::Open)
        ->and($latestHistory->new_status)->toBe(CaseStatus::Open)
        ->and($latestHistory->reason)->toContain($firstJudge->name)
        ->and($latestHistory->reason)->toContain($secondJudge->name);
});

test('re-assigning the same judge does not create a duplicate history entry', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $judge = assignedJudgeUser(UserRole::Judge);
    $case = CrimeCase::factory()->withCreator($officer)->assignedToJudge($judge)->create();

    $this->actingAs($admin)->patch(route('cases.assign_judge', $case), [
        'judge_id' => $judge->id,
    ])->assertRedirect(route('cases.show', $case));

    expect($case->fresh()->judge_id)->toBe($judge->id)
        ->and($case->statusHistories()->count())->toBe(0);
});

test('re-assigning the same judge submitted as a string creates no history', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $judge = assignedJudgeUser(UserRole::Judge);
    $case = CrimeCase::factory()->withCreator($officer)->assignedToJudge($judge)->create();

    $this->actingAs($admin)->patch(route('cases.assign_judge', $case), [
        'judge_id' => (string) $judge->id,
    ])->assertRedirect(route('cases.show', $case));

    expect($case->fresh()->judge_id)->toBe($judge->id)
        ->and($case->statusHistories()->count())->toBe(0);
});

test('non administrators cannot assign or change judges', function (UserRole $role) {
    $user = assignedJudgeUser($role);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $originalJudge = assignedJudgeUser(UserRole::Judge);
    $newJudge = assignedJudgeUser(UserRole::Judge);
    $case = CrimeCase::factory()->withCreator($officer)->assignedToJudge($originalJudge)->create();

    $this->actingAs($user)->patch(route('cases.assign_judge', $case), [
        'judge_id' => $newJudge->id,
    ])->assertForbidden();

    expect($case->fresh()->judge_id)->toBe($originalJudge->id)
        ->and($case->statusHistories()->count())->toBe(0);
})->with([
    'police officer' => [UserRole::PoliceOfficer],
    'investigator' => [UserRole::Investigator],
    'judge' => [UserRole::Judge],
]);

test('non administrators cannot assign a judge while creating a case', function () {
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $judge = assignedJudgeUser(UserRole::Judge);

    $this->actingAs($officer)->post(route('cases.store'), assignedJudgeCasePayload([
        'judge_id' => $judge->id,
    ]))->assertSessionHasErrors('judge_id');

    $this->assertDatabaseCount('cases', 0);
});

test('case creation rejects nonexistent inactive and non judge ids', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $inactiveJudge = assignedJudgeUser(UserRole::Judge, false);
    $nonJudge = assignedJudgeUser(UserRole::PoliceOfficer);

    foreach ([
        (string) 999999,
        (string) $inactiveJudge->id,
        (string) $nonJudge->id,
    ] as $judgeId) {
        $this->actingAs($admin)->post(route('cases.store'), assignedJudgeCasePayload([
            'judge_id' => $judgeId,
        ]))->assertSessionHasErrors('judge_id');
    }

    $this->assertDatabaseCount('cases', 0);
});

test('judge assignment validation rejects inactive non judge and missing judges', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $inactiveJudge = assignedJudgeUser(UserRole::Judge, false);
    $nonJudge = assignedJudgeUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()->withCreator($officer)->create();

    foreach ([
        ['judge_id' => $inactiveJudge->id],
        ['judge_id' => $nonJudge->id],
        ['judge_id' => 999999],
        [],
    ] as $payload) {
        $this->actingAs($admin)->patch(route('cases.assign_judge', $case), $payload)
            ->assertSessionHasErrors('judge_id');
    }

    expect($case->fresh()->judge_id)->toBeNull()
        ->and($case->statusHistories()->count())->toBe(0);
});

test('case details show not assigned when no judge is assigned', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()->withCreator($officer)->create();

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Not assigned');
});

test('administrator details page shows the judge assignment form with active options', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $currentJudge = assignedJudgeUser(UserRole::Judge);
    $otherJudge = assignedJudgeUser(UserRole::Judge);
    $inactiveJudge = assignedJudgeUser(UserRole::Judge, false);
    $case = CrimeCase::factory()->withCreator($officer)->assignedToJudge($currentJudge)->create();

    $response = $this->actingAs($admin)->get(route('cases.show', $case))->assertOk();

    expect($response->viewData('judges')->pluck('id')->all())
        ->toContain($currentJudge->id, $otherJudge->id)
        ->not->toContain($inactiveJudge->id);

    $response->assertSee(route('cases.assign_judge', $case), false)
        ->assertSee('name="judge_id"', false)
        ->assertSee($currentJudge->name)
        ->assertSee($otherJudge->name)
        ->assertDontSee($inactiveJudge->name)
        ->assertSee('value="'.$currentJudge->id.'" selected', false);
});

test('non administrators cannot see judge assignment field on case creation', function (UserRole $role) {
    $user = assignedJudgeUser($role);

    $response = $this->actingAs($user)->get(route('cases.create'));

    if ($role === UserRole::PoliceOfficer) {
        $response->assertOk()
            ->assertDontSee('name="judge_id"', false)
            ->assertDontSee(__('Assigned Judge'));
    } else {
        $response->assertForbidden();
    }
})->with([
    'police officer' => [UserRole::PoliceOfficer],
    'investigator' => [UserRole::Investigator],
    'judge' => [UserRole::Judge],
]);

test('non administrators cannot see judge assignment form on case details', function () {
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $investigator = assignedJudgeUser(UserRole::Investigator);
    $judge = assignedJudgeUser(UserRole::Judge);
    $case = CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->create();

    foreach ([$officer, $investigator] as $user) {
        $this->actingAs($user)->get(route('cases.show', $case))
            ->assertOk()
            ->assertDontSee(route('cases.assign_judge', $case), false)
            ->assertDontSee('name="judge_id"', false)
            ->assertDontSee(__('Assign judge'));
    }

    $this->actingAs($judge)->get(route('cases.show', $case))
        ->assertForbidden();
});

test('ordinary judge reassignment is blocked after forwarding', function (CaseStatus $status, bool $forwardedTimestamp) {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $originalJudge = assignedJudgeUser(UserRole::Judge);
    $newJudge = assignedJudgeUser(UserRole::Judge);
    $case = CrimeCase::factory()
        ->withCreator($officer)
        ->assignedToJudge($originalJudge)
        ->create(['status' => $status]);

    if ($forwardedTimestamp) {
        $case->forceFill(['forwarded_for_review_at' => now()])->save();
    }

    $this->actingAs($admin)->patch(route('cases.assign_judge', $case), [
        'judge_id' => $newJudge->id,
    ])->assertForbidden();

    expect($case->fresh()->judge_id)->toBe($originalJudge->id)
        ->and($case->statusHistories()->count())->toBe(0);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertDontSee(route('cases.assign_judge', $case), false);
})->with([
    'judgment recorded' => [CaseStatus::JudgmentRecorded, false],
    'awaiting judicial review' => [CaseStatus::AwaitingJudicialReview, false],
    'under judicial review' => [CaseStatus::UnderJudicialReview, false],
    'judgment finalized' => [CaseStatus::JudgmentFinalized, false],
    'closed' => [CaseStatus::Closed, false],
    'forwarded timestamp' => [CaseStatus::Open, true],
]);

test('judge assignment returns conflict when case is forwarded after authorization', function () {
    $admin = assignedJudgeUser(UserRole::Administrator);
    $officer = assignedJudgeUser(UserRole::PoliceOfficer);
    $originalJudge = assignedJudgeUser(UserRole::Judge);
    $newJudge = assignedJudgeUser(UserRole::Judge);
    $case = CrimeCase::factory()->withCreator($officer)->assignedToJudge($originalJudge)->create();
    $staleCase = $case->fresh();

    assignedJudgeConditionalConflict($staleCase, 'assignJudge', function () use ($case): void {
        $case->forceFill([
            'status' => CaseStatus::AwaitingJudicialReview,
            'forwarded_for_review_at' => now(),
        ])->save();
    });

    $this->actingAs($admin)->patch(route('cases.assign_judge', $case), [
        'judge_id' => $newJudge->id,
    ])->assertStatus(409);

    expect($case->fresh()->judge_id)->toBe($originalJudge->id)
        ->and($case->statusHistories()->count())->toBe(0);
});
