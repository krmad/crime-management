<?php

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\CrimeCategory;
use App\Models\User;

function reportTestUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function reportTestCase(User $creator, ?User $investigator = null, array $attributes = []): CrimeCase
{
    return CrimeCase::factory()
        ->withCreator($creator)
        ->when($investigator !== null, fn ($factory) => $factory->assignedTo($investigator))
        ->create($attributes);
}

test('report totals use role based case visibility', function (UserRole $role, int $expectedTotal, string $visibleTitle, string $hiddenTitle) {
    $officer = reportTestUser(UserRole::PoliceOfficer);
    $investigator = reportTestUser(UserRole::Investigator);
    $otherOfficer = reportTestUser(UserRole::PoliceOfficer);
    $otherInvestigator = reportTestUser(UserRole::Investigator);

    $ownCase = reportTestCase($officer, $investigator, ['title' => $visibleTitle]);
    $otherCase = reportTestCase($otherOfficer, $otherInvestigator, ['title' => $hiddenTitle]);

    $actor = match ($role) {
        UserRole::Administrator => reportTestUser(UserRole::Administrator),
        UserRole::Investigator => $investigator,
        default => $officer,
    };

    $response = $this->actingAs($actor)->get(route('reports.index'));

    $response->assertOk()->assertViewIs('reports.index');

    expect($response->viewData('cases')->pluck('id')->all())
        ->toEqualCanonicalizing($role === UserRole::Administrator
            ? [$ownCase->getKey(), $otherCase->getKey()]
            : [$ownCase->getKey()]);

    expect($response->viewData('summary')[0]['value'])->toBe($expectedTotal)
        ->and($response->viewData('summary'))->toHaveCount(5);
})->with([
    'administrator' => [UserRole::Administrator, 2, 'Visible Report Case', 'Hidden Report Case'],
    'police officer' => [UserRole::PoliceOfficer, 1, 'Visible Report Case', 'Hidden Report Case'],
    'investigator' => [UserRole::Investigator, 1, 'Visible Report Case', 'Hidden Report Case'],
]);

test('date range status and classification filters narrow report results', function () {
    $admin = reportTestUser(UserRole::Administrator);
    $officer = reportTestUser(UserRole::PoliceOfficer);
    $category = CrimeCategory::factory()->create(['name' => 'Filtered Theft']);
    $otherCategory = CrimeCategory::factory()->create(['name' => 'Other Classification']);

    reportTestCase($officer, null, [
        'title' => 'Included Report Case',
        'status' => CaseStatus::Closed,
        'crime_category_id' => $category->getKey(),
        'created_at' => now()->subDays(3),
    ]);
    reportTestCase($officer, null, [
        'title' => 'Wrong Date Report Case',
        'status' => CaseStatus::Closed,
        'crime_category_id' => $category->getKey(),
        'created_at' => now()->subDays(20),
    ]);
    reportTestCase($officer, null, [
        'title' => 'Wrong Status Report Case',
        'status' => CaseStatus::Open,
        'crime_category_id' => $category->getKey(),
        'created_at' => now()->subDays(3),
    ]);
    reportTestCase($officer, null, [
        'title' => 'Wrong Classification Report Case',
        'status' => CaseStatus::Closed,
        'crime_category_id' => $otherCategory->getKey(),
        'created_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($admin)->get(route('reports.index', [
        'from' => now()->subDays(5)->toDateString(),
        'to' => now()->toDateString(),
        'status' => CaseStatus::Closed->value,
        'classification' => $category->getKey(),
    ]));

    $response->assertOk();

    expect($response->viewData('cases')->pluck('title')->all())->toBe(['Included Report Case'])
        ->and($response->viewData('filters'))->toMatchArray([
            'status' => CaseStatus::Closed->value,
            'classification' => (string) $category->getKey(),
        ]);
});

test('csv export applies the same filters and role visibility as report pages', function () {
    $officer = reportTestUser(UserRole::PoliceOfficer);
    $otherOfficer = reportTestUser(UserRole::PoliceOfficer);

    reportTestCase($officer, null, [
        'title' => 'Officer Export Case',
        'status' => CaseStatus::Open,
    ]);
    reportTestCase($officer, null, [
        'title' => '=SUM(1+1)',
        'status' => CaseStatus::Closed,
    ]);
    reportTestCase($otherOfficer, null, [
        'title' => 'Unauthorized Export Case',
        'status' => CaseStatus::Open,
    ]);

    $openResponse = $this->actingAs($officer)->get(route('reports.export', [
        'status' => CaseStatus::Open->value,
    ]));

    $openResponse->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $openCsv = $openResponse->streamedContent();

    expect($openCsv)->toContain('Officer Export Case')
        ->toContain('"Case ID",Title,"Crime Type"')
        ->not->toContain('Unauthorized Export Case')
        ->not->toContain('=SUM(1+1)');

    $formulaResponse = $this->actingAs($officer)->get(route('reports.export', [
        'status' => CaseStatus::Closed->value,
    ]));

    $formulaCsv = $formulaResponse->streamedContent();

    expect($formulaCsv)->toContain("'=SUM(1+1)");
});

test('reports render every report section', function () {
    $admin = reportTestUser(UserRole::Administrator);
    $officer = reportTestUser(UserRole::PoliceOfficer);
    $investigator = reportTestUser(UserRole::Investigator);
    reportTestCase($officer, $investigator);

    $this->actingAs($admin)->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Cases by status')
        ->assertSee('Cases by crime type')
        ->assertSee('Cases by classification')
        ->assertSee('Monthly case registrations')
        ->assertSee('Case outcomes')
        ->assertSee('Investigator workload')
        ->assertSee('Export CSV')
        ->assertSee('Print');
});

test('reports show an empty state when no cases are visible', function () {
    $investigator = reportTestUser(UserRole::Investigator);

    $response = $this->actingAs($investigator)->get(route('reports.index'));

    $response->assertOk()
        ->assertSee('No cases match the selected filters.');

    expect($response->viewData('cases')->isEmpty())->toBeTrue()
        ->and($response->viewData('summary')[0]['value'])->toBe(0);
});

test('reports require authentication', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
    $this->get(route('reports.export'))->assertRedirect(route('login'));
});
