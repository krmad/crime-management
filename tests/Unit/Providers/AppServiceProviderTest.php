<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class);

test('manage-users grants administrators only', function (?UserRole $role, bool $expected) {
    $user = new User;
    $user->role = $role;

    expect(Gate::forUser($user)->allows('manage-users'))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'investigator' => [UserRole::Investigator, false],
    'police officer' => [UserRole::PoliceOfficer, false],
    'no role' => [null, false],
]);

test('open-cases grants administrators and police officers', function (?UserRole $role, bool $expected) {
    $user = new User;
    $user->role = $role;

    expect(Gate::forUser($user)->allows('open-cases'))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'investigator' => [UserRole::Investigator, false],
    'police officer' => [UserRole::PoliceOfficer, true],
    'no role' => [null, false],
]);

test('view-all-cases grants administrators only', function (?UserRole $role, bool $expected) {
    $user = new User;
    $user->role = $role;

    expect(Gate::forUser($user)->allows('view-all-cases'))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'investigator' => [UserRole::Investigator, false],
    'police officer' => [UserRole::PoliceOfficer, false],
    'no role' => [null, false],
]);

test('investigate-cases grants administrators and investigators', function (?UserRole $role, bool $expected) {
    $user = new User;
    $user->role = $role;

    expect(Gate::forUser($user)->allows('investigate-cases'))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'investigator' => [UserRole::Investigator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
    'no role' => [null, false],
]);

test('record-judgment grants administrators only', function (?UserRole $role, bool $expected) {
    $user = new User;
    $user->role = $role;

    expect(Gate::forUser($user)->allows('record-judgment'))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'investigator' => [UserRole::Investigator, false],
    'police officer' => [UserRole::PoliceOfficer, false],
    'no role' => [null, false],
]);

test('close-cases grants administrators only', function (?UserRole $role, bool $expected) {
    $user = new User;
    $user->role = $role;

    expect(Gate::forUser($user)->allows('close-cases'))->toBe($expected);
})->with([
    'administrator' => [UserRole::Administrator, true],
    'investigator' => [UserRole::Investigator, false],
    'police officer' => [UserRole::PoliceOfficer, false],
    'no role' => [null, false],
]);
