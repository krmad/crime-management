<?php

use App\Enums\CaseStatus;
use App\Enums\CustodyEvent;
use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function evidenceTestUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function evidenceFakeJpegContent(): string
{
    return base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=');
}

function evidenceTestCase(User $creator, ?User $investigator = null, CaseStatus $status = CaseStatus::Open): CrimeCase
{
    $factory = CrimeCase::factory()->withCreator($creator);

    if ($investigator !== null) {
        $factory = $factory->assignedTo($investigator);
    }

    return $factory->create(['status' => $status]);
}

function evidenceWithCollectionLog(CrimeCase $case, User $collector, array $attributes = []): Evidence
{
    $evidence = Evidence::factory()->create([
        'case_id' => $case->getKey(),
        'collected_by' => $collector->getKey(),
        'status' => EvidenceStatus::Collected,
        ...$attributes,
    ]);

    $evidence->custodyLogs()->create([
        'event_type' => CustodyEvent::Collection,
        'transferred_by' => $collector->getKey(),
        'from_location' => null,
        'to_location' => $evidence->storage_location,
        'occurred_at' => $evidence->collected_at,
        'notes' => 'Evidence registered in the case file.',
    ]);

    return $evidence;
}

test('authorized roles view the case evidence section and evidence details', function (UserRole $role) {
    $actor = evidenceTestUser($role);
    $officer = $role === UserRole::PoliceOfficer ? $actor : evidenceTestUser(UserRole::PoliceOfficer);
    $investigator = $role === UserRole::Investigator ? $actor : evidenceTestUser(UserRole::Investigator);
    $case = evidenceTestCase($officer, $investigator);
    $evidence = evidenceWithCollectionLog($case, $actor, ['title' => 'Authorized Role Evidence']);

    $this->actingAs($actor)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Authorized Role Evidence')
        ->assertSee($evidence->evidence_number);

    $this->actingAs($actor)->get(route('evidence.show', $evidence))
        ->assertOk()
        ->assertSee('Authorized Role Evidence')
        ->assertSee('Chain of custody');
})->with([
    UserRole::Administrator,
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('unrelated users cannot view or download another case evidence', function (UserRole $role) {
    $actor = evidenceTestUser($role);
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $investigator = evidenceTestUser(UserRole::Investigator);
    $case = evidenceTestCase($officer, $investigator);
    $evidence = evidenceWithCollectionLog($case, $officer, [
        'title' => 'Restricted Evidence',
        'private_file_path' => 'evidence/1/restricted.pdf',
        'original_filename' => 'restricted.pdf',
    ]);

    $this->actingAs($actor)->get(route('evidence.show', $evidence))->assertForbidden();
    $this->actingAs($actor)->get(route('evidence.download', $evidence))->assertForbidden();

    $this->actingAs($actor)->get(route('evidence.index'))
        ->assertOk()
        ->assertDontSee('Restricted Evidence')
        ->assertDontSee($evidence->evidence_number);
})->with([
    UserRole::PoliceOfficer,
    UserRole::Investigator,
]);

test('registering evidence stores metadata a unique reference and collection custody event', function () {
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer);

    $response = $this->actingAs($officer)->post(route('cases.evidence.store', $case), [
        'title' => 'Sealed Envelope',
        'evidence_type' => EvidenceType::Documentary->value,
        'description' => 'Recovered from the records office.',
        'collected_at' => now()->subDay()->format('Y-m-d\TH:i'),
        'storage_location' => 'Evidence Room 3',
        'status' => EvidenceStatus::Collected->value,
    ]);

    $evidence = Evidence::where('title', 'Sealed Envelope')->firstOrFail();

    $response->assertRedirect(route('evidence.show', $evidence))
        ->assertSessionHasNoErrors();

    expect($evidence->evidence_number)->toStartWith('EV-'.now()->year.'-')
        ->and($evidence->collected_by)->toBe($officer->getKey())
        ->and($evidence->custodyLogs)->toHaveCount(1);

    expect($evidence->custodyLogs->first()->event_type)->toBe(CustodyEvent::Collection);

    $this->assertDatabaseHas('evidence', [
        'id' => $evidence->getKey(),
        'case_id' => $case->getKey(),
        'title' => 'Sealed Envelope',
        'evidence_type' => EvidenceType::Documentary->value,
        'status' => EvidenceStatus::Collected->value,
    ]);
});

test('closed cases reject new evidence registration', function () {
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer, null, CaseStatus::Closed);

    $this->actingAs($officer)->post(route('cases.evidence.store', $case), [
        'title' => 'Late Evidence',
        'evidence_type' => EvidenceType::Physical->value,
        'collected_at' => now()->format('Y-m-d\TH:i'),
        'storage_location' => 'Evidence Room 1',
        'status' => EvidenceStatus::Collected->value,
    ])->assertForbidden();

    expect(Evidence::where('title', 'Late Evidence')->exists())->toBeFalse();
});

test('editing evidence metadata appends a location change without rewriting custody history', function () {
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer);
    $evidence = evidenceWithCollectionLog($case, $officer, [
        'title' => 'Original Title',
        'storage_location' => 'Evidence Room 1',
    ]);
    $originalEventId = $evidence->custodyLogs->first()->getKey();

    $this->actingAs($officer)->patch(route('evidence.update', $evidence), [
        'title' => 'Updated Title',
        'evidence_type' => EvidenceType::Digital->value,
        'description' => 'Updated description.',
        'collected_at' => $evidence->collected_at->format('Y-m-d\TH:i'),
        'storage_location' => 'Evidence Room 9',
        'status' => EvidenceStatus::Stored->value,
    ])->assertRedirect(route('evidence.show', $evidence))->assertSessionHasNoErrors();

    $evidence->refresh();

    expect($evidence->title)->toBe('Updated Title')
        ->and($evidence->storage_location)->toBe('Evidence Room 9')
        ->and($evidence->custodyLogs)->toHaveCount(2);

    $this->assertDatabaseHas('evidence_custody_logs', [
        'id' => $originalEventId,
        'event_type' => CustodyEvent::Collection->value,
        'to_location' => 'Evidence Room 1',
    ]);

    $this->assertDatabaseHas('evidence_custody_logs', [
        'evidence_id' => $evidence->getKey(),
        'event_type' => CustodyEvent::LocationChange->value,
        'from_location' => 'Evidence Room 1',
        'to_location' => 'Evidence Room 9',
    ]);
});

test('release and disposal are limited to administrators', function (UserRole $role, bool $allowed) {
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $investigator = evidenceTestUser(UserRole::Investigator);
    $actor = match ($role) {
        UserRole::Administrator => evidenceTestUser(UserRole::Administrator),
        UserRole::Investigator => $investigator,
        default => $officer,
    };
    $case = evidenceTestCase($officer, $investigator);
    $evidence = evidenceWithCollectionLog($case, $officer, ['status' => EvidenceStatus::Stored]);

    $response = $this->actingAs($actor)->patch(route('evidence.update', $evidence), [
        'title' => $evidence->title,
        'evidence_type' => $evidence->evidence_type->value,
        'description' => $evidence->description,
        'collected_at' => $evidence->collected_at->format('Y-m-d\TH:i'),
        'storage_location' => $evidence->storage_location,
        'status' => EvidenceStatus::Released->value,
    ]);

    if ($allowed) {
        $response->assertRedirect(route('evidence.show', $evidence));
        $evidence->refresh();

        expect($evidence->status)->toBe(EvidenceStatus::Released)
            ->and($evidence->custodyLogs->pluck('event_type')->contains(CustodyEvent::Release))->toBeTrue();
    } else {
        $response->assertForbidden();
        $evidence->refresh();

        expect($evidence->status)->toBe(EvidenceStatus::Stored)
            ->and($evidence->custodyLogs)->toHaveCount(1);
    }
})->with([
    'administrator' => [UserRole::Administrator, true],
    'police officer' => [UserRole::PoliceOfficer, false],
    'investigator' => [UserRole::Investigator, false],
]);

test('uploaded files are stored privately with generated names and authorized downloads', function () {
    Storage::fake('evidence');

    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer);
    $evidence = evidenceWithCollectionLog($case, $officer);
    $file = UploadedFile::fake()->createWithContent('scene.jpg', evidenceFakeJpegContent());

    $this->actingAs($officer)->post(route('evidence.file.store', $evidence), [
        'attachment' => $file,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $evidence->refresh();

    expect($evidence->private_file_path)->not->toBeNull()
        ->and($evidence->private_file_path)->not->toContain('scene.jpg')
        ->and($evidence->original_filename)->toBe('scene.jpg');

    Storage::disk('evidence')->assertExists($evidence->private_file_path);

    $download = $this->actingAs($officer)->get(route('evidence.download', $evidence));

    $download->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($download->headers->get('content-disposition'))->toContain('scene.jpg');

    $this->actingAs($officer)->get(route('evidence.show', $evidence))
        ->assertOk()
        ->assertDontSee($evidence->private_file_path, false);
});

test('upload validation rejects disallowed file types', function () {
    Storage::fake('evidence');

    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer);
    $evidence = evidenceWithCollectionLog($case, $officer);

    $this->actingAs($officer)->post(route('evidence.file.store', $evidence), [
        'attachment' => UploadedFile::fake()->create('payload.exe', 10),
    ])->assertSessionHasErrors('attachment');

    $evidence->refresh();

    expect($evidence->private_file_path)->toBeNull();

    Storage::disk('evidence')->assertEmpty();
});

test('upload validation rejects files larger than twenty megabytes', function () {
    Storage::fake('evidence');

    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer);
    $evidence = evidenceWithCollectionLog($case, $officer);

    $this->actingAs($officer)->post(route('evidence.file.store', $evidence), [
        'attachment' => UploadedFile::fake()->createWithContent('large.jpg', evidenceFakeJpegContent().str_repeat("\0", 21000 * 1024)),
    ])->assertSessionHasErrors('attachment');

    Storage::disk('evidence')->assertEmpty();
});

test('recording a transfer updates custody status and keeps earlier events', function () {
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $recipient = evidenceTestUser(UserRole::Investigator);
    $case = evidenceTestCase($officer, $recipient);
    $evidence = evidenceWithCollectionLog($case, $officer, ['storage_location' => 'Evidence Room 1']);

    $this->actingAs($officer)->post(route('evidence.custody.store', $evidence), [
        'event_type' => CustodyEvent::Transfer->value,
        'received_by' => $recipient->getKey(),
        'to_location' => 'Forensics Lab',
        'occurred_at' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Transferred for analysis.',
    ])->assertRedirect(route('evidence.show', $evidence))->assertSessionHasNoErrors();

    $evidence->refresh();

    expect($evidence->status)->toBe(EvidenceStatus::Transferred)
        ->and($evidence->storage_location)->toBe('Forensics Lab')
        ->and($evidence->custodyLogs)->toHaveCount(2);

    $this->assertDatabaseHas('evidence_custody_logs', [
        'evidence_id' => $evidence->getKey(),
        'event_type' => CustodyEvent::Transfer->value,
        'transferred_by' => $officer->getKey(),
        'received_by' => $recipient->getKey(),
        'from_location' => 'Evidence Room 1',
        'to_location' => 'Forensics Lab',
    ]);
});

test('evidence search and status filters only return authorized records', function () {
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $otherOfficer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer);
    $otherCase = evidenceTestCase($otherOfficer);

    evidenceWithCollectionLog($case, $officer, [
        'title' => 'Alpha Seized Device',
        'status' => EvidenceStatus::Collected,
    ]);
    evidenceWithCollectionLog($otherCase, $otherOfficer, [
        'title' => 'Alpha Restricted Device',
        'status' => EvidenceStatus::Collected,
    ]);

    $this->actingAs($officer)->get(route('evidence.index', [
        'search' => 'Alpha',
        'status' => EvidenceStatus::Collected->value,
    ]))
        ->assertOk()
        ->assertSee('Alpha Seized Device')
        ->assertDontSee('Alpha Restricted Device');

    $this->actingAs($officer)->get(route('evidence.index', [
        'status' => EvidenceStatus::Disposed->value,
    ]))
        ->assertOk()
        ->assertSee('No evidence matches the selected filters.');
});

test('guests are redirected to login from evidence endpoints', function () {
    $officer = evidenceTestUser(UserRole::PoliceOfficer);
    $case = evidenceTestCase($officer);
    $evidence = evidenceWithCollectionLog($case, $officer);

    $this->get(route('evidence.index'))->assertRedirect(route('login'));
    $this->get(route('evidence.show', $evidence))->assertRedirect(route('login'));
    $this->get(route('evidence.download', $evidence))->assertRedirect(route('login'));
});
