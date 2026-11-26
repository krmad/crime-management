<?php

use App\Enums\CaseJudgmentState;
use App\Enums\CaseStatus;
use App\Enums\JudgmentVerdict;
use App\Enums\PunishmentType;
use App\Enums\UserRole;
use App\Models\CaseJudgment;
use App\Models\CaseStatusHistory;
use App\Models\CrimeCase;
use App\Models\OfficerProfile;
use App\Models\Punishment;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('judicial tables and nullable case columns are present', function () {
    expect(Schema::hasTable('case_judgments'))->toBeTrue()
        ->and(Schema::hasTable('punishments'))->toBeTrue()
        ->and(Schema::hasTable('case_status_histories'))->toBeTrue()
        ->and(Schema::hasColumns('cases', [
            'judge_id',
            'forwarded_for_review_at',
            'judicial_reopened_at',
            'judgment_finalized_at',
        ]))->toBeTrue();

    $case = CrimeCase::factory()->create();

    expect($case->judge_id)->toBeNull()
        ->and($case->forwarded_for_review_at)->toBeNull()
        ->and($case->judicial_reopened_at)->toBeNull()
        ->and($case->judgment_finalized_at)->toBeNull();
});

test('assigned judge relationship connects cases and judges', function () {
    $judge = User::factory()->judge()->create();
    $case = CrimeCase::factory()->assignedToJudge($judge)->create();

    expect($judge->role)->toBe(UserRole::Judge)
        ->and($case->judge->is($judge))->toBeTrue()
        ->and($judge->assignedJudicialCases->first()->is($case))->toBeTrue();
});

test('case judgments cast enums and belong to case and judge', function () {
    $case = CrimeCase::factory()->create();
    $judge = User::factory()->judge()->create();

    $judgment = CaseJudgment::factory()
        ->withCase($case)
        ->withJudge($judge)
        ->finalized()
        ->create(['judgment_notes' => 'Guilty verdict recorded.']);

    expect($judgment->judgment_state)->toBe(CaseJudgmentState::Final)
        ->and($judgment->verdict)->toBe(JudgmentVerdict::Guilty)
        ->and($judgment->decided_at)->not->toBeNull()
        ->and($judgment->case->is($case))->toBeTrue()
        ->and($judgment->judge->is($judge))->toBeTrue()
        ->and($case->judgments->first()->is($judgment))->toBeTrue()
        ->and($judge->recordedJudgments->first()->is($judgment))->toBeTrue();
});

test('one draft and one final judgment are allowed per case', function () {
    $case = CrimeCase::factory()->create();
    $judge = User::factory()->judge()->create();

    CaseJudgment::factory()->withCase($case)->withJudge($judge)->create();
    CaseJudgment::factory()->withCase($case)->withJudge($judge)->finalized()->create();

    expect($case->judgments()->count())->toBe(2);

    expect(fn () => CaseJudgment::factory()
        ->withCase($case)
        ->withJudge($judge)
        ->create()
    )->toThrow(QueryException::class);

    expect(fn () => CaseJudgment::factory()
        ->withCase($case)
        ->withJudge($judge)
        ->finalized()
        ->create()
    )->toThrow(QueryException::class);
});

test('a judgment can have multiple punishments with enum casts', function () {
    $judgment = CaseJudgment::factory()->create();

    $fine = Punishment::factory()->forJudgment($judgment)->create();
    $imprisonment = Punishment::factory()->forJudgment($judgment)->imprisonment()->create();
    $other = Punishment::factory()->forJudgment($judgment)->other()->create();

    expect($judgment->punishments)->toHaveCount(3)
        ->and($fine->type)->toBe(PunishmentType::Fine)
        ->and($fine->fine_amount)->toBe('1250.00')
        ->and($imprisonment->type)->toBe(PunishmentType::Imprisonment)
        ->and($imprisonment->fine_amount)->toBeNull()
        ->and($imprisonment->imprisonment_term)->toBe('2 years')
        ->and($other->type)->toBe(PunishmentType::Other)
        ->and($other->judgment->is($judgment))->toBeTrue();
});

test('status history records relate to cases and actors and stay append-only', function () {
    $case = CrimeCase::factory()->create();
    $actor = User::factory()->create();

    $history = CaseStatusHistory::factory()
        ->forCase($case)
        ->withActor($actor)
        ->create(['reason' => 'Investigation started']);

    expect($history->previous_status)->toBe(CaseStatus::Open)
        ->and($history->new_status)->toBe(CaseStatus::UnderInvestigation)
        ->and($history->occurred_at)->not->toBeNull()
        ->and($history->case->is($case))->toBeTrue()
        ->and($history->actor->is($actor))->toBeTrue()
        ->and($case->statusHistories->first()->is($history))->toBeTrue()
        ->and($actor->statusHistories->first()->is($history))->toBeTrue();

    expect(fn () => CaseStatusHistory::create([
        'case_id' => $case->id,
        'new_status' => CaseStatus::JudgmentFinalized,
        'occurred_at' => now(),
    ]))->toThrow(MassAssignmentException::class);

    expect(fn () => $history->update(['reason' => 'Edited']))
        ->toThrow(MassAssignmentException::class);

    $history->reason = 'Edited';

    expect(fn () => $history->save())->toThrow(LogicException::class);

    expect(fn () => $history->delete())->toThrow(LogicException::class);
});

test('legacy closed case fields are unchanged by the judicial foundation', function () {
    $closedAt = now()->subDays(3);

    $case = CrimeCase::factory()->closed()->create([
        'judgment_notes' => 'Legacy judgment summary.',
        'closed_at' => $closedAt,
    ]);

    CaseStatusHistory::factory()->forCase($case)->create();
    CaseJudgment::factory()->withCase($case)->create();

    $case = $case->fresh();

    expect($case->status)->toBe(CaseStatus::Closed)
        ->and($case->closed_at?->toDateTimeString())->toBe($closedAt->toDateTimeString())
        ->and($case->judgment_notes)->toBe('Legacy judgment summary.')
        ->and($case->judge_id)->toBeNull()
        ->and($case->forwarded_for_review_at)->toBeNull()
        ->and($case->judicial_reopened_at)->toBeNull()
        ->and($case->judgment_finalized_at)->toBeNull();
});

test('existing user roles and officer profile behavior remain intact', function () {
    $judge = User::factory()->judge()->create();
    $officer = User::factory()->create(['name' => 'Officer Profile User']);
    $officer->forceFill(['role' => UserRole::PoliceOfficer])->save();

    $profile = OfficerProfile::factory()->for($officer)->create();

    expect($judge->role)->toBe(UserRole::Judge)
        ->and($officer->role)->toBe(UserRole::PoliceOfficer)
        ->and($officer->officerProfile->is($profile))->toBeTrue()
        ->and($profile->user->is($officer))->toBeTrue();
});
