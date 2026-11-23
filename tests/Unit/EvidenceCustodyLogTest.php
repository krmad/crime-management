<?php

use App\Enums\CustodyEvent;
use App\Models\EvidenceCustodyLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('custody events cannot be updated', function () {
    $log = EvidenceCustodyLog::factory()->create(['notes' => 'Original note']);
    $log->notes = 'Changed note';

    expect(fn () => $log->save())->toThrow(LogicException::class, 'Custody events are append-only.');

    expect(EvidenceCustodyLog::findOrFail($log->getKey())->notes)->toBe('Original note');
});

test('custody events cannot be deleted', function () {
    $log = EvidenceCustodyLog::factory()->create();

    expect(fn () => $log->delete())->toThrow(LogicException::class, 'Custody events are append-only.');

    expect(EvidenceCustodyLog::find($log->getKey()))->not->toBeNull();
});

test('new custody events can be appended to existing evidence', function () {
    $collection = EvidenceCustodyLog::factory()->create([
        'event_type' => CustodyEvent::Collection,
    ]);

    EvidenceCustodyLog::factory()->create([
        'evidence_id' => $collection->evidence_id,
        'event_type' => CustodyEvent::Transfer,
    ]);

    expect($collection->evidence->custodyLogs)->toHaveCount(2)
        ->and($collection->evidence->custodyLogs->pluck('event_type')->all())->toBe([
            CustodyEvent::Collection,
            CustodyEvent::Transfer,
        ]);
});
