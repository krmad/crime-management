<?php

use App\Enums\CaseJudgmentState;
use App\Enums\CaseStatus;
use App\Enums\JudgmentVerdict;
use App\Enums\PunishmentType;

test('case status keeps all legacy values and adds judicial statuses', function () {
    expect(CaseStatus::cases())->toBe([
        CaseStatus::Open,
        CaseStatus::UnderInvestigation,
        CaseStatus::JudgmentRecorded,
        CaseStatus::AwaitingJudicialReview,
        CaseStatus::UnderJudicialReview,
        CaseStatus::JudgmentFinalized,
        CaseStatus::Closed,
    ])->and(array_map(fn (CaseStatus $status) => $status->value, CaseStatus::cases()))->toBe([
        'open',
        'under_investigation',
        'judgment_recorded',
        'awaiting_judicial_review',
        'under_judicial_review',
        'judgment_finalized',
        'closed',
    ]);
});

test('judicial case statuses map to exact backing values', function (CaseStatus $status, string $value) {
    expect($status->value)->toBe($value)
        ->and(CaseStatus::from($value))->toBe($status)
        ->and(CaseStatus::tryFrom($value))->toBe($status);
})->with([
    'awaiting judicial review' => [CaseStatus::AwaitingJudicialReview, 'awaiting_judicial_review'],
    'under judicial review' => [CaseStatus::UnderJudicialReview, 'under_judicial_review'],
    'judgment finalized' => [CaseStatus::JudgmentFinalized, 'judgment_finalized'],
]);

test('legacy case statuses remain resolvable', function (CaseStatus $status, string $value) {
    expect(CaseStatus::from($value))->toBe($status);
})->with([
    'open' => [CaseStatus::Open, 'open'],
    'under investigation' => [CaseStatus::UnderInvestigation, 'under_investigation'],
    'judgment recorded' => [CaseStatus::JudgmentRecorded, 'judgment_recorded'],
    'closed' => [CaseStatus::Closed, 'closed'],
]);

test('judgment verdict has exact backing values', function (JudgmentVerdict $verdict, string $value) {
    expect($verdict->value)->toBe($value)
        ->and(JudgmentVerdict::from($value))->toBe($verdict);
})->with([
    'guilty' => [JudgmentVerdict::Guilty, 'guilty'],
    'not guilty' => [JudgmentVerdict::NotGuilty, 'not_guilty'],
]);

test('judgment verdict cases are complete', function () {
    expect(JudgmentVerdict::cases())->toBe([
        JudgmentVerdict::Guilty,
        JudgmentVerdict::NotGuilty,
    ]);
});

test('case judgment state has exact backing values', function (CaseJudgmentState $state, string $value) {
    expect($state->value)->toBe($value)
        ->and(CaseJudgmentState::from($value))->toBe($state);
})->with([
    'draft' => [CaseJudgmentState::Draft, 'draft'],
    'final' => [CaseJudgmentState::Final, 'final'],
]);

test('case judgment state cases are complete', function () {
    expect(CaseJudgmentState::cases())->toBe([
        CaseJudgmentState::Draft,
        CaseJudgmentState::Final,
    ]);
});

test('punishment type has exact backing values', function (PunishmentType $type, string $value) {
    expect($type->value)->toBe($value)
        ->and(PunishmentType::from($value))->toBe($type);
})->with([
    'imprisonment' => [PunishmentType::Imprisonment, 'imprisonment'],
    'fine' => [PunishmentType::Fine, 'fine'],
    'other' => [PunishmentType::Other, 'other'],
]);

test('punishment type cases are complete', function () {
    expect(PunishmentType::cases())->toBe([
        PunishmentType::Imprisonment,
        PunishmentType::Fine,
        PunishmentType::Other,
    ]);
});
