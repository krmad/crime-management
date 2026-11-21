<?php

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class);

function caseAuthorizationUser(?UserRole $role, int $id): User
{
    $user = new User;
    $user->id = $id;
    $user->role = $role;

    return $user;
}

function caseAuthorizationCase(int $creatorId, ?int $investigatorId = null, CaseStatus $status = CaseStatus::Open): CrimeCase
{
    $case = new CrimeCase;
    $case->created_by = $creatorId;
    $case->investigator_id = $investigatorId;
    $case->status = $status;

    return $case;
}

test('all case roles can access the case listing', function (?UserRole $role, bool $expected) {
    $user = caseAuthorizationUser($role, 1);

    expect(Gate::forUser($user)->allows('viewAny', CrimeCase::class))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, true],
    'investigator' => [UserRole::Investigator, true],
    'no role' => [null, false],
]);

test('administrator listing access includes all cases', function () {
    $user = caseAuthorizationUser(UserRole::Administrator, 1);
    $unrelatedCase = caseAuthorizationCase(9, 10);

    expect(Gate::forUser($user)->allows('viewAny', CrimeCase::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view-all-cases'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $unrelatedCase))->toBeTrue();
});

test('listing access does not grant all case visibility to non administrators', function (UserRole $role) {
    $user = caseAuthorizationUser($role, 2);
    $unrelatedCase = caseAuthorizationCase(9, 10);

    expect(Gate::forUser($user)->allows('viewAny', CrimeCase::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view-all-cases'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $unrelatedCase))->toBeFalse();
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('view allows owners and assigned investigators and rejects unrelated users', function (UserRole $role, int $actorId, int $creatorId, ?int $investigatorId, bool $expected) {
    $user = caseAuthorizationUser($role, $actorId);
    $case = caseAuthorizationCase($creatorId, $investigatorId);

    expect(Gate::forUser($user)->allows('view', $case))->toBe($expected);
})->with([
    'administrator views unrelated case' => [UserRole::Administrator, 1, 2, 3, true],
    'police officer views own case' => [UserRole::PoliceOfficer, 2, 2, null, true],
    'investigator views assigned case' => [UserRole::Investigator, 3, 2, 3, true],
    'police officer cannot view another officer case' => [UserRole::PoliceOfficer, 2, 4, null, false],
    'investigator cannot view another investigator case' => [UserRole::Investigator, 3, 2, 4, false],
    'investigator cannot view own created unassigned case' => [UserRole::Investigator, 3, 3, null, false],
]);

test('create is granted to administrators and police officers', function (UserRole $role, bool $expected) {
    $user = caseAuthorizationUser($role, 1);

    expect(Gate::forUser($user)->allows('create', CrimeCase::class))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, true],
    'investigator' => [UserRole::Investigator, false],
]);

test('update enforces ownership assignment and case status', function (UserRole $role, int $actorId, int $creatorId, ?int $investigatorId, CaseStatus $status, bool $expected) {
    $user = caseAuthorizationUser($role, $actorId);
    $case = caseAuthorizationCase($creatorId, $investigatorId, $status);

    expect(Gate::forUser($user)->allows('update', $case))->toBe($expected);
})->with([
    'administrator updates any case' => [UserRole::Administrator, 1, 2, 3, CaseStatus::Closed, true],
    'police officer updates own open case' => [UserRole::PoliceOfficer, 2, 2, null, CaseStatus::Open, true],
    'police officer cannot update another case' => [UserRole::PoliceOfficer, 2, 4, null, CaseStatus::Open, false],
    'police officer cannot update own case under investigation' => [UserRole::PoliceOfficer, 2, 2, 3, CaseStatus::UnderInvestigation, false],
    'police officer cannot update own closed case' => [UserRole::PoliceOfficer, 2, 2, null, CaseStatus::Closed, false],
    'investigator updates assigned case' => [UserRole::Investigator, 3, 2, 3, CaseStatus::UnderInvestigation, true],
    'investigator cannot update another investigator case' => [UserRole::Investigator, 3, 2, 4, CaseStatus::UnderInvestigation, false],
    'investigator cannot update own created unassigned case' => [UserRole::Investigator, 3, 3, null, CaseStatus::Open, false],
]);

test('assign investigators is granted only to administrators', function (UserRole $role, bool $expected) {
    $user = caseAuthorizationUser($role, 1);

    expect(Gate::forUser($user)->allows('assign', caseAuthorizationCase(2)))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
    'investigator' => [UserRole::Investigator, false],
]);

test('record judgment is granted only to administrators', function (UserRole $role, bool $expected) {
    $user = caseAuthorizationUser($role, 1);

    expect(Gate::forUser($user)->allows('recordJudgment', caseAuthorizationCase(2)))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
    'investigator' => [UserRole::Investigator, false],
]);

test('close cases is granted only to administrators', function (UserRole $role, bool $expected) {
    $user = caseAuthorizationUser($role, 1);

    expect(Gate::forUser($user)->allows('close', caseAuthorizationCase(2)))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
    'investigator' => [UserRole::Investigator, false],
]);
