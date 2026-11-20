<?php

use App\Enums\CaseStatus;
use App\Models\CrimeCase;
use App\Models\User;

test('factory creates a fictional case with open status', function () {
    $case = CrimeCase::factory()->create();

    expect($case->status)->toBe(CaseStatus::Open)
        ->and($case->title)->not->toBeEmpty()
        ->and($case->description)->not->toBeEmpty();

    $this->assertDatabaseHas('cases', [
        'id' => $case->id,
        'status' => 'open',
    ]);
});

test('case belongs to its creator and assigned investigator', function () {
    $creator = User::factory()->create();
    $investigator = User::factory()->create();

    $case = CrimeCase::factory()
        ->withCreator($creator)
        ->assignedTo($investigator)
        ->create();

    expect($case->creator->is($creator))->toBeTrue()
        ->and($case->investigator->is($investigator))->toBeTrue()
        ->and($case->status)->toBe(CaseStatus::UnderInvestigation);
});

test('user has created and assigned case relationships', function () {
    $user = User::factory()->create();

    $createdCase = CrimeCase::factory()->withCreator($user)->create();
    $assignedCase = CrimeCase::factory()->assignedTo($user)->create();

    $user->load('createdCases', 'assignedCases');

    expect($user->createdCases->pluck('id'))->toContain($createdCase->id)
        ->and($user->assignedCases->pluck('id'))->toContain($assignedCase->id);
});

test('sensitive fields are not mass assignable', function () {
    $creator = User::factory()->create();

    $case = CrimeCase::create([
        'title' => 'Fictional title',
        'crime_type' => 'Fraud',
        'location' => 'Central Business District',
        'description' => 'Fictional description for testing.',
        'created_by' => $creator->id,
        'judgment_notes' => 'Should not be mass assignable.',
        'closed_at' => now(),
    ]);

    expect($case->created_by)->toBeNull()
        ->and($case->judgment_notes)->toBeNull()
        ->and($case->closed_at)->toBeNull()
        ->and($case->status)->toBe(CaseStatus::Open);
});

test('case status factory states cast to the case status enum', function (string $state, CaseStatus $expected) {
    $case = CrimeCase::factory()->{$state}()->create();

    expect($case->status)->toBe($expected);
})->with([
    'judgment recorded' => ['judgmentRecorded', CaseStatus::JudgmentRecorded],
    'closed' => ['closed', CaseStatus::Closed],
]);
