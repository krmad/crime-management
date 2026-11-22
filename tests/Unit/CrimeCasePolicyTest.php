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

test('update investigation enforces oversight assignment and terminal statuses', function (UserRole $role, int $actorId, int $creatorId, ?int $investigatorId, CaseStatus $status, bool $expected) {
    $user = caseAuthorizationUser($role, $actorId);
    $case = caseAuthorizationCase($creatorId, $investigatorId, $status);

    expect(Gate::forUser($user)->allows('updateInvestigation', $case))->toBe($expected);
})->with([
    'administrator oversees open case' => [UserRole::Administrator, 1, 2, 3, CaseStatus::Open, true],
    'administrator oversees case under investigation' => [UserRole::Administrator, 1, 2, 3, CaseStatus::UnderInvestigation, true],
    'administrator cannot update assigned judgment recorded case' => [UserRole::Administrator, 1, 2, 3, CaseStatus::JudgmentRecorded, false],
    'administrator cannot update assigned closed case' => [UserRole::Administrator, 1, 2, 3, CaseStatus::Closed, false],
    'police officer cannot update own open case' => [UserRole::PoliceOfficer, 2, 2, null, CaseStatus::Open, false],
    'investigator updates assigned open case' => [UserRole::Investigator, 3, 2, 3, CaseStatus::Open, true],
    'investigator updates assigned case under investigation' => [UserRole::Investigator, 3, 2, 3, CaseStatus::UnderInvestigation, true],
    'investigator cannot update unassigned case' => [UserRole::Investigator, 3, 2, null, CaseStatus::Open, false],
    'investigator cannot update another investigator case' => [UserRole::Investigator, 3, 2, 4, CaseStatus::UnderInvestigation, false],
    'investigator cannot update assigned judgment recorded case' => [UserRole::Investigator, 3, 2, 3, CaseStatus::JudgmentRecorded, false],
    'investigator cannot update assigned closed case' => [UserRole::Investigator, 3, 2, 3, CaseStatus::Closed, false],
]);

test('assign investigators is granted only to administrators and not on closed cases', function (UserRole $role, CaseStatus $status, bool $expected) {
    $user = caseAuthorizationUser($role, 1);

    expect(Gate::forUser($user)->allows('assign', caseAuthorizationCase(2, null, $status)))->toBe($expected);
})->with([
    'administrator assigns open case' => [UserRole::Administrator, CaseStatus::Open, true],
    'administrator assigns judgment recorded case' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, true],
    'administrator cannot assign closed case' => [UserRole::Administrator, CaseStatus::Closed, false],
    'police officer' => [UserRole::PoliceOfficer, CaseStatus::Open, false],
    'investigator' => [UserRole::Investigator, CaseStatus::Open, false],
]);

test('record judgment enforces administrator access and investigation status', function (UserRole $role, CaseStatus $status, ?string $investigationNotes, bool $expected) {
    $user = caseAuthorizationUser($role, 1);
    $case = caseAuthorizationCase(2, 3, $status);
    $case->investigation_notes = $investigationNotes;

    expect(Gate::forUser($user)->allows('recordJudgment', $case))->toBe($expected);
})->with([
    'administrator records completed investigation' => [UserRole::Administrator, CaseStatus::UnderInvestigation, 'Investigation notes.', true],
    'administrator edits existing judgment' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, 'Investigation notes.', true],
    'administrator cannot record before investigation' => [UserRole::Administrator, CaseStatus::Open, 'Investigation notes.', false],
    'administrator cannot record without investigation notes' => [UserRole::Administrator, CaseStatus::UnderInvestigation, null, false],
    'administrator cannot record with empty investigation notes' => [UserRole::Administrator, CaseStatus::UnderInvestigation, '', false],
    'administrator cannot modify closed case' => [UserRole::Administrator, CaseStatus::Closed, 'Investigation notes.', false],
    'investigator cannot record completed investigation' => [UserRole::Investigator, CaseStatus::UnderInvestigation, 'Investigation notes.', false],
    'police officer cannot record completed investigation' => [UserRole::PoliceOfficer, CaseStatus::UnderInvestigation, 'Investigation notes.', false],
]);

test('close enforces administrator access, judgment status, and notes', function (UserRole $role, CaseStatus $status, ?string $judgmentNotes, bool $expected) {
    $user = caseAuthorizationUser($role, 1);
    $case = caseAuthorizationCase(2, 2, $status);
    $case->judgment_notes = $judgmentNotes;

    expect(Gate::forUser($user)->allows('close', $case))->toBe($expected);
})->with([
    'administrator closes recorded judgment' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, 'Judgment notes.', true],
    'administrator cannot close without judgment notes' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, null, false],
    'administrator cannot close with empty judgment notes' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, '', false],
    'administrator cannot close with whitespace judgment notes' => [UserRole::Administrator, CaseStatus::JudgmentRecorded, '   ', false],
    'administrator cannot close open case' => [UserRole::Administrator, CaseStatus::Open, 'Judgment notes.', false],
    'administrator cannot close case under investigation' => [UserRole::Administrator, CaseStatus::UnderInvestigation, 'Judgment notes.', false],
    'administrator cannot close closed case' => [UserRole::Administrator, CaseStatus::Closed, 'Judgment notes.', false],
    'investigator cannot close recorded judgment' => [UserRole::Investigator, CaseStatus::JudgmentRecorded, 'Judgment notes.', false],
    'police officer cannot close recorded judgment' => [UserRole::PoliceOfficer, CaseStatus::JudgmentRecorded, 'Judgment notes.', false],
]);
