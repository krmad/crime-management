<?php

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class);

function evidencePolicyUser(?UserRole $role, int $id): User
{
    $user = new User;
    $user->id = $id;
    $user->role = $role;

    return $user;
}

function evidencePolicyCase(int $creatorId, ?int $investigatorId, CaseStatus $status = CaseStatus::Open): CrimeCase
{
    $case = new CrimeCase;
    $case->created_by = $creatorId;
    $case->investigator_id = $investigatorId;
    $case->status = $status;

    return $case;
}

function evidencePolicyEvidence(CrimeCase $case): Evidence
{
    $evidence = new Evidence;
    $evidence->setRelation('case', $case);

    return $evidence;
}

test('all case roles can access the evidence listing', function (?UserRole $role, bool $expected) {
    $user = evidencePolicyUser($role, 1);

    expect(Gate::forUser($user)->allows('viewAny', Evidence::class))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, true],
    'investigator' => [UserRole::Investigator, true],
    'no role' => [null, false],
]);

test('evidence view and download follow case visibility', function (UserRole $role, int $actorId, int $creatorId, ?int $investigatorId, bool $expected) {
    $user = evidencePolicyUser($role, $actorId);
    $evidence = evidencePolicyEvidence(evidencePolicyCase($creatorId, $investigatorId));

    expect(Gate::forUser($user)->allows('view', $evidence))->toBe($expected)
        ->and(Gate::forUser($user)->allows('download', $evidence))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, 1, 2, 3, true],
    'police officer own case' => [UserRole::PoliceOfficer, 2, 2, null, true],
    'investigator assigned case' => [UserRole::Investigator, 3, 2, 3, true],
    'police officer unrelated case' => [UserRole::PoliceOfficer, 2, 4, null, false],
    'investigator unassigned case' => [UserRole::Investigator, 3, 2, null, false],
]);

test('creating and updating evidence follows case management permissions', function (UserRole $role, int $actorId, CaseStatus $status, bool $expected) {
    $user = evidencePolicyUser($role, $actorId);
    $case = evidencePolicyCase(2, 3, $status);
    $evidence = evidencePolicyEvidence($case);

    expect(Gate::forUser($user)->allows('create', [Evidence::class, $case]))->toBe($expected)
        ->and(Gate::forUser($user)->allows('update', $evidence))->toBe($expected)
        ->and(Gate::forUser($user)->allows('recordCustody', $evidence))->toBe($expected);
})->with([
    'administrator open case' => [UserRole::Administrator, 1, CaseStatus::Open, true],
    'creator open case' => [UserRole::PoliceOfficer, 2, CaseStatus::Open, true],
    'assigned investigator open case' => [UserRole::Investigator, 3, CaseStatus::Open, true],
    'unrelated police officer' => [UserRole::PoliceOfficer, 4, CaseStatus::Open, false],
    'unrelated investigator' => [UserRole::Investigator, 4, CaseStatus::Open, false],
    'closed case' => [UserRole::Administrator, 1, CaseStatus::Closed, false],
]);

test('release and disposal require an administrator on a managed case', function (UserRole $role, int $actorId, CaseStatus $status, bool $expected) {
    $user = evidencePolicyUser($role, $actorId);
    $evidence = evidencePolicyEvidence(evidencePolicyCase(2, 3, $status));

    expect(Gate::forUser($user)->allows('dispose', $evidence))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, 1, CaseStatus::Open, true],
    'creator' => [UserRole::PoliceOfficer, 2, CaseStatus::Open, false],
    'assigned investigator' => [UserRole::Investigator, 3, CaseStatus::Open, false],
    'administrator on closed case' => [UserRole::Administrator, 1, CaseStatus::Closed, false],
]);
