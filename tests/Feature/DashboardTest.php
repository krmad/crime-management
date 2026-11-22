<?php

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\User;
use Illuminate\Testing\TestResponse;

function dashboardTestUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function dashboardStatistics(TestResponse $response): array
{
    return collect($response->viewData('statistics'))
        ->mapWithKeys(fn (array $statistic): array => [$statistic['label'] => $statistic['value']])
        ->all();
}

test('administrator sees correct counts for every case status', function () {
    $admin = dashboardTestUser(UserRole::Administrator);
    $officer = dashboardTestUser(UserRole::PoliceOfficer);
    $investigator = dashboardTestUser(UserRole::Investigator);

    CrimeCase::factory()->withCreator($officer)->create();
    CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->create();
    CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->judgmentRecorded()->create();
    CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->closed()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertViewIs('dashboard');

    expect(dashboardStatistics($response))->toBe([
        'Total Cases' => 4,
        'Open Cases' => 1,
        'Under Investigation' => 1,
        'Judgment Recorded' => 1,
        'Closed Cases' => 1,
    ]);
});

test('police officer sees only own cases in counts and recent cases', function () {
    $officer = dashboardTestUser(UserRole::PoliceOfficer);
    $otherOfficer = dashboardTestUser(UserRole::PoliceOfficer);

    $ownOpen = CrimeCase::factory()->withCreator($officer)->create();
    $ownClosed = CrimeCase::factory()->withCreator($officer)->closed()->create();
    $otherCase = CrimeCase::factory()->withCreator($otherOfficer)->create();
    $assignedToOfficer = CrimeCase::factory()->withCreator($otherOfficer)->assignedTo($officer)->create();

    $response = $this->actingAs($officer)->get(route('dashboard'));

    $response->assertOk();

    expect(dashboardStatistics($response))->toBe([
        'Total Cases' => 2,
        'Open Cases' => 1,
        'Under Investigation' => 0,
        'Judgment Recorded' => 0,
        'Closed Cases' => 1,
    ]);

    $recentIds = $response->viewData('recentCases')->pluck('id');

    expect($recentIds)->toContain($ownOpen->id)
        ->toContain($ownClosed->id)
        ->not->toContain($otherCase->id)
        ->not->toContain($assignedToOfficer->id);
});

test('investigator sees only assigned cases in counts and recent cases', function () {
    $investigator = dashboardTestUser(UserRole::Investigator);
    $otherInvestigator = dashboardTestUser(UserRole::Investigator);
    $officer = dashboardTestUser(UserRole::PoliceOfficer);

    $assignedOpen = CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->create(['status' => CaseStatus::Open]);
    $assignedJudgment = CrimeCase::factory()->withCreator($officer)->assignedTo($investigator)->judgmentRecorded()->create();
    $createdUnassigned = CrimeCase::factory()->withCreator($investigator)->create();
    $otherAssigned = CrimeCase::factory()->withCreator($officer)->assignedTo($otherInvestigator)->create();

    $response = $this->actingAs($investigator)->get(route('dashboard'));

    $response->assertOk();

    expect(dashboardStatistics($response))->toBe([
        'Total Cases' => 2,
        'Open Cases' => 1,
        'Under Investigation' => 0,
        'Judgment Recorded' => 1,
        'Closed Cases' => 0,
    ]);

    $recentIds = $response->viewData('recentCases')->pluck('id');

    expect($recentIds)->toContain($assignedOpen->id)
        ->toContain($assignedJudgment->id)
        ->not->toContain($createdUnassigned->id)
        ->not->toContain($otherAssigned->id);
});

test('empty database shows zero counts and an empty state', function () {
    $officer = dashboardTestUser(UserRole::PoliceOfficer);

    $response = $this->actingAs($officer)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('No cases are available to your role yet.')
        ->assertDontSee('CR-2026-0142')
        ->assertDontSee('Insp. Maria Santos');

    expect(dashboardStatistics($response))->toBe([
        'Total Cases' => 0,
        'Open Cases' => 0,
        'Under Investigation' => 0,
        'Judgment Recorded' => 0,
        'Closed Cases' => 0,
    ]);
});

test('recent cases are limited to five and ordered by creation date then id', function () {
    $admin = dashboardTestUser(UserRole::Administrator);
    $officer = dashboardTestUser(UserRole::PoliceOfficer);

    $tieTime = now()->subDays(5);
    $cases = collect([
        now()->subDays(10),
        now()->subDays(9),
        now()->subDays(8),
        now()->subDays(7),
        now()->subDays(6),
        $tieTime,
        $tieTime,
    ])->map(fn ($createdAt) => CrimeCase::factory()->withCreator($officer)->create(['created_at' => $createdAt]));

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(route('cases.index'), false)
        ->assertSee(route('cases.show', $cases->last()), false);

    expect($response->viewData('recentCases')->pluck('id')->all())
        ->toBe($cases->reverse()->take(5)->pluck('id')->all());
});

test('dashboard case row opens case details for every role', function (UserRole $role) {
    $actor = dashboardTestUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : dashboardTestUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : dashboardTestUser(UserRole::Investigator);
    $case = CrimeCase::factory()
        ->withCreator($officer)
        ->assignedTo($investigator)
        ->create(['title' => 'Dashboard Clickable Case']);

    $this->actingAs($actor)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Clickable Case')
        ->assertSee('cursor-pointer', false)
        ->assertSee("onclick=\"window.location.href='".route('cases.show', $case)."'\"", false)
        ->assertSee('>Dashboard Clickable Case</a>', false);

    $this->actingAs($actor)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Dashboard Clickable Case');
})->with([
    UserRole::Administrator,
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('dashboard logout control signs out every role', function (UserRole $role) {
    $user = dashboardTestUser($role);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('Log Out');

    $this->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
})->with([
    UserRole::Administrator,
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('management navigation links are visible only to administrators', function (UserRole $role, bool $expected) {
    $user = dashboardTestUser($role);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();

    if ($expected) {
        $response->assertSee('User Management')
            ->assertSee(route('users.index'), false)
            ->assertSee(route('officers.index'), false);
    } else {
        $response->assertDontSee('User Management')
            ->assertDontSee(route('users.index'), false)
            ->assertDontSee(route('officers.index'), false);
    }
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
    'investigator' => [UserRole::Investigator, false],
]);

test('case creation link follows case creation authorization', function (UserRole $role, bool $expected) {
    $user = dashboardTestUser($role);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();

    if ($expected) {
        $response->assertSee(route('cases.create'), false)
            ->assertSee('Crime Reports');
    } else {
        $response->assertDontSee(route('cases.create'), false)
            ->assertDontSee('Crime Reports');
    }
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, true],
    'investigator' => [UserRole::Investigator, false],
]);

test('guests are redirected to login from the dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
