<?php

use App\Enums\CaseStatus;
use App\Enums\CaseTitleType;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\CrimeCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;

function classificationUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

/**
 * @return array{0: CrimeCategory, 1: CrimeCategory, 2: CrimeCategory}
 */
function classificationHierarchy(string $level3Name = 'Homicide (Article 538)'): array
{
    $level1 = CrimeCategory::factory()->create(['name' => 'Book V — Crimes Against Individuals and the Family']);
    $level2 = CrimeCategory::factory()->childOf($level1)->create(['name' => 'Title I — Crimes Against Life, Person and Health']);
    $level3 = CrimeCategory::factory()->childOf($level2)->create(['name' => $level3Name]);

    return [$level1, $level2, $level3];
}

/**
 * @param  array{0: CrimeCategory, 1: CrimeCategory, 2: CrimeCategory}  $hierarchy
 * @return array<string, mixed>
 */
function classificationPayload(array $hierarchy, array $overrides = []): array
{
    [$level1, $level2, $level3] = $hierarchy;

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

function classificationCase(User $creator, CrimeCategory $level3): CrimeCase
{
    return CrimeCase::factory()
        ->withCreator($creator)
        ->withCrimeCategory($level3)
        ->create();
}

function classificationCategoryQueryCount(callable $request): int
{
    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        $request();
    } finally {
        DB::disableQueryLog();
    }

    return collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'crime_categories'))
        ->count();
}

test('creation form displays every case title type option', function (string $label) {
    $officer = classificationUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)->get(route('cases.create'))
        ->assertOk()
        ->assertViewIs('cases.create')
        ->assertSee($label);
})->with(collect(CaseTitleType::cases())
    ->mapWithKeys(fn (CaseTitleType $type): array => [$type->value => [$type->label()]])
    ->all());

test('creation form shows the required custom input for other custom', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)->get(route('cases.create'))
        ->assertOk()
        ->assertSee('x-show="isCustomTitle"', false)
        ->assertSee('name="custom_crime_type"', false)
        ->assertSee('maxlength="255"', false)
        ->assertSee('x-bind:required="isCustomTitle"', false)
        ->assertSee(Js::from(CaseTitleType::Other->value), false);
});

test('creation form provides active categories with their children', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    [$level1, $level2, $level3] = classificationHierarchy('Petty Theft (Article 852)');
    $inactive = CrimeCategory::factory()->create([
        'name' => 'Inactive Book IX',
        'is_active' => false,
    ]);

    $this->actingAs($officer)->get(route('cases.create'))
        ->assertOk()
        ->assertSee($level1->name)
        ->assertSee($level2->name)
        ->assertSee($level3->name)
        ->assertDontSee($inactive->name);
});

test('a valid level 1 2 3 hierarchy is saved with derived title and type', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $payload = classificationPayload($hierarchy);

    $this->actingAs($officer)->post(route('cases.store'), $payload)
        ->assertRedirect(route('cases.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('cases', [
        'title' => CaseTitleType::Homicide->label(),
        'crime_type' => CaseTitleType::Homicide->label(),
        'crime_category_id' => $hierarchy[2]->getKey(),
        'custom_crime_type' => null,
        'created_by' => $officer->id,
    ]);
});

test('a level 2 category from another parent is rejected', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $otherHierarchy = classificationHierarchy('Robbery (Article 670)');

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload($hierarchy, [
        'level_2_category_id' => $otherHierarchy[1]->getKey(),
        'crime_category_id' => $otherHierarchy[2]->getKey(),
    ]))->assertSessionHasErrors('level_2_category_id');

    $this->assertDatabaseCount('cases', 0);
});

test('a level 3 category from another parent is rejected', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $otherHierarchy = classificationHierarchy('Robbery (Article 670)');

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload($hierarchy, [
        'crime_category_id' => $otherHierarchy[2]->getKey(),
    ]))->assertSessionHasErrors('crime_category_id');

    $this->assertDatabaseCount('cases', 0);
});

test('inactive categories are rejected', function (string $field) {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $categories = [
        'level_1_category_id' => $hierarchy[0],
        'level_2_category_id' => $hierarchy[1],
        'crime_category_id' => $hierarchy[2],
    ];
    $categories[$field]->forceFill(['is_active' => false])->save();

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload($hierarchy))
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('cases', 0);
})->with([
    'level 1' => ['level_1_category_id'],
    'level 2' => ['level_2_category_id'],
    'level 3' => ['crime_category_id'],
]);

test('categories at the wrong level are rejected', function (string $field, int $level) {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $wrongLevelCategory = CrimeCategory::factory()->create([
        'level' => $level,
        'name' => 'Wrong Level Category',
    ]);

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload($hierarchy, [
        $field => $wrongLevelCategory->getKey(),
    ]))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('cases', 0);
})->with([
    'level 1 field' => ['level_1_category_id', 2],
    'level 2 field' => ['level_2_category_id', 3],
    'level 3 field' => ['crime_category_id', 2],
]);

test('a custom title is saved with the other custom crime type', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $customTitle = 'Suspicious package near the market';

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload($hierarchy, [
        'case_title_type' => CaseTitleType::Other->value,
        'custom_crime_type' => $customTitle,
    ]))->assertRedirect(route('cases.index'));

    $case = CrimeCase::firstOrFail();

    expect($case->title)->toBe($customTitle)
        ->and($case->crime_type)->toBe(CaseTitleType::Other->label())
        ->and($case->custom_crime_type)->toBe($customTitle)
        ->and($case->crime_category_id)->toBe($hierarchy[2]->getKey());

    $this->actingAs($officer)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee($customTitle)
        ->assertSee(CaseTitleType::Other->label());
});

test('client submitted title and crime type cannot override server derived values', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload($hierarchy, [
        'title' => 'Injected title',
        'crime_type' => 'Injected crime type',
    ]))->assertRedirect(route('cases.index'));

    $case = CrimeCase::firstOrFail();

    expect($case->title)->toBe(CaseTitleType::Homicide->label())
        ->and($case->crime_type)->toBe(CaseTitleType::Homicide->label())
        ->and($case->custom_crime_type)->toBeNull();
});

test('a custom crime type is rejected for predefined case title types', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload(classificationHierarchy(), [
        'custom_crime_type' => 'Injected custom title',
    ]))->assertSessionHasErrors('custom_crime_type');

    $this->assertDatabaseCount('cases', 0);
});

test('custom crime type is required for other custom and limited to 255 characters', function (array $overrides, string $error) {
    $officer = classificationUser(UserRole::PoliceOfficer);

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload(classificationHierarchy(), array_merge([
        'case_title_type' => CaseTitleType::Other->value,
    ], $overrides)))->assertSessionHasErrors($error);

    $this->assertDatabaseCount('cases', 0);
})->with([
    'missing custom text' => [[], 'custom_crime_type'],
    'custom text too long' => [['custom_crime_type' => str_repeat('a', 256)], 'custom_crime_type'],
]);

test('custom crime type of exactly 255 characters is accepted', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $customTitle = str_repeat('a', 255);

    $this->actingAs($officer)->post(route('cases.store'), classificationPayload(classificationHierarchy(), [
        'case_title_type' => CaseTitleType::Other->value,
        'custom_crime_type' => $customTitle,
    ]))->assertRedirect(route('cases.index'));

    $this->assertDatabaseHas('cases', [
        'title' => $customTitle,
        'custom_crime_type' => $customTitle,
    ]);
});

test('validation errors preserve submitted selections and show field messages', function () {
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $payload = classificationPayload($hierarchy, [
        'case_title_type' => CaseTitleType::Other->value,
        'custom_crime_type' => 'Custom title kept after failure',
        'description' => str_repeat('a', 10001),
    ]);

    $this->actingAs($officer)
        ->from(route('cases.create'))
        ->post(route('cases.store'), $payload)
        ->assertRedirect(route('cases.create'));

    $this->followingRedirects()
        ->get(route('cases.create'))
        ->assertOk()
        ->assertSee('The description field must not be greater than 10000 characters.')
        ->assertSee(Js::from(CaseTitleType::Other->value), false)
        ->assertSee(Js::from($hierarchy[0]->getKey()), false)
        ->assertSee(Js::from($hierarchy[1]->getKey()), false)
        ->assertSee(Js::from($hierarchy[2]->getKey()), false)
        ->assertSee('Custom title kept after failure');
});

test('case details show the classification breadcrumb and provisional label', function () {
    $admin = classificationUser(UserRole::Administrator);
    $officer = classificationUser(UserRole::PoliceOfficer);
    $hierarchy = classificationHierarchy();
    $case = classificationCase($officer, $hierarchy[2]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Provisional classification')
        ->assertSee('Effective case title / type')
        ->assertSeeInOrder([$hierarchy[0]->name, $hierarchy[1]->name, $hierarchy[2]->name]);
});

test('case list and dashboard display classification without N+1 queries', function (string $routeName) {
    $admin = classificationUser(UserRole::Administrator);
    $officer = classificationUser(UserRole::PoliceOfficer);
    $firstHierarchy = classificationHierarchy('Petty Theft (Article 852)');
    classificationCase($officer, $firstHierarchy[2]);

    $oneCaseQueries = classificationCategoryQueryCount(
        fn () => $this->actingAs($admin)->get(route($routeName))->assertOk(),
    );

    $secondHierarchy = classificationHierarchy('Robbery (Article 670)');
    classificationCase($officer, $secondHierarchy[2]);

    $twoCaseQueries = classificationCategoryQueryCount(
        fn () => $this->actingAs($admin)->get(route($routeName))
            ->assertOk()
            ->assertSee('Provisional classification')
            ->assertSee($firstHierarchy[2]->name)
            ->assertSee($secondHierarchy[2]->name),
    );

    expect($twoCaseQueries)->toBe($oneCaseQueries);
})->with([
    'case list' => ['cases.index'],
    'dashboard' => ['dashboard'],
]);

test('legacy cases without classification still display their title and crime type', function (string $routeName) {
    $admin = classificationUser(UserRole::Administrator);
    $officer = classificationUser(UserRole::PoliceOfficer);
    $case = CrimeCase::factory()
        ->withCreator($officer)
        ->create([
            'title' => 'Legacy Warehouse Burglary',
            'crime_type' => 'Burglary',
            'status' => CaseStatus::Open,
        ]);

    expect($case->crime_category_id)->toBeNull();

    $response = $routeName === 'cases.show'
        ? $this->actingAs($admin)->get(route($routeName, $case))
        : $this->actingAs($admin)->get(route($routeName));

    $response->assertOk()
        ->assertSee('Legacy Warehouse Burglary')
        ->assertSee('Burglary');

    if ($routeName === 'cases.show') {
        $response->assertDontSee('Provisional classification');
    }
})->with([
    'case details' => ['cases.show'],
    'case list' => ['cases.index'],
    'dashboard' => ['dashboard'],
]);
