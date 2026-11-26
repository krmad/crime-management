<?php

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Models\CrimeCase;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function incidentLocationUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function incidentLocationCase(User $creator, array $attributes = []): CrimeCase
{
    return CrimeCase::factory()->withCreator($creator)->create($attributes);
}

beforeEach(fn () => Cache::flush());

test('guests cannot search incident locations', function () {
    $this->getJson(route('case-locations.search', ['q' => 'Addis Ababa']))
        ->assertUnauthorized();

    $this->assertGuest();
});

test('location search returns normalized nominatim results with required headers', function () {
    Http::preventStrayRequests();
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Addis Ababa, Ethiopia',
                'lat' => '9.0320000',
                'lon' => '38.7469000',
            ],
            [
                'display_name' => 'Invalid coordinates',
                'lat' => 'not-a-number',
                'lon' => '38',
            ],
            [
                'display_name' => 'Out of range',
                'lat' => '91',
                'lon' => '0',
            ],
        ]),
    ]);

    $user = incidentLocationUser(UserRole::PoliceOfficer);

    $this->actingAs($user)
        ->withHeaders(['Accept-Language' => 'am'])
        ->getJson(route('case-locations.search', ['q' => '  Addis Ababa  ']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.label', 'Addis Ababa, Ethiopia')
        ->assertJsonPath('results.0.latitude', 9.032)
        ->assertJsonPath('results.0.longitude', 38.7469);

    Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader('User-Agent', config('services.nominatim.user_agent'))
        && $request->hasHeader('Accept-Language', 'am')
        && ($request->data()['q'] ?? null) === 'Addis Ababa'
        && ($request->data()['format'] ?? null) === 'jsonv2'
        && (int) ($request->data()['addressdetails'] ?? 0) === 1
        && (int) ($request->data()['limit'] ?? 0) === 5);
});

test('location search caches identical queries', function () {
    Http::preventStrayRequests();
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([
            [
                'display_name' => 'Bahir Dar, Ethiopia',
                'lat' => '11.5934000',
                'lon' => '37.3867000',
            ],
        ]),
    ]);

    $user = incidentLocationUser(UserRole::Administrator);
    $url = route('case-locations.search', ['q' => 'Bahir Dar Cached Query']);

    $this->actingAs($user)->getJson($url)->assertOk();

    $this->travel(2)->seconds();

    $this->actingAs($user)->getJson($url)->assertOk();

    Http::assertSentCount(1);
});

test('location search is globally limited to one request per second', function () {
    Http::preventStrayRequests();
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([]),
    ]);

    $user = incidentLocationUser(UserRole::PoliceOfficer);

    $this->actingAs($user)
        ->getJson(route('case-locations.search', ['q' => 'Rate Limit First Query']))
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('case-locations.search', ['q' => 'Rate Limit Second Query']))
        ->assertStatus(429);

    Http::assertSentCount(1);
});

test('location search rejects queries shorter than three characters', function () {
    Http::preventStrayRequests();

    $user = incidentLocationUser(UserRole::Investigator);

    $this->actingAs($user)
        ->getJson(route('case-locations.search', ['q' => 'ab']))
        ->assertJsonValidationErrors('q');
});

test('location search returns 502 when nominatim fails', function () {
    Http::preventStrayRequests();
    Http::fake([
        'nominatim.openstreetmap.org/search*' => Http::response([], 503),
    ]);

    $user = incidentLocationUser(UserRole::PoliceOfficer);

    $this->actingAs($user)
        ->getJson(route('case-locations.search', ['q' => 'Addis Ababa failing search']))
        ->assertStatus(502);
});

test('case details render the incident map and coordinate editor for the case owner', function () {
    $officer = incidentLocationUser(UserRole::PoliceOfficer);
    $case = incidentLocationCase($officer, [
        'title' => 'Mapped Details Case',
        'latitude' => '9.0320000',
        'longitude' => '38.7469000',
    ]);

    $this->actingAs($officer)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Mapped Details Case')
        ->assertSee('Incident location map')
        ->assertSee('incidentLocationPicker(', false)
        ->assertSee(route('cases.update_location', $case), false)
        ->assertSee('name="latitude"', false)
        ->assertSee('name="longitude"', false);
});

test('case details hide the coordinate editor on closed cases', function () {
    $admin = incidentLocationUser(UserRole::Administrator);
    $officer = incidentLocationUser(UserRole::PoliceOfficer);
    $case = incidentLocationCase($officer, [
        'title' => 'Closed Mapped Case',
        'latitude' => '9.0320000',
        'longitude' => '38.7469000',
        'status' => CaseStatus::Closed,
        'judgment_notes' => 'Final judgment notes.',
        'closed_at' => now(),
    ]);

    $this->actingAs($admin)->get(route('cases.show', $case))
        ->assertOk()
        ->assertSee('Incident location map')
        ->assertDontSee(route('cases.update_location', $case), false);
});
