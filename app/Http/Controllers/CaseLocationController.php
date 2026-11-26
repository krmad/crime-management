<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaseLocationSearchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CaseLocationController extends Controller
{
    public function search(CaseLocationSearchRequest $request): JsonResponse
    {
        $query = $request->string('q')->toString();
        $locale = $request->getPreferredLanguage(['en', 'am', 'om']) ?? 'en';
        $cacheKey = 'case-location-search:'.$locale.':'.hash('sha256', mb_strtolower($query));

        $results = Cache::remember($cacheKey, now()->addDay(), function () use ($query, $locale): array {
            $parameters = [
                'q' => $query,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'limit' => 5,
            ];

            $contactEmail = config('services.nominatim.contact_email');

            if (is_string($contactEmail) && $contactEmail !== '') {
                $parameters['email'] = $contactEmail;
            }

            $response = Http::connectTimeout(3)
                ->timeout(5)
                ->withHeaders([
                    'User-Agent' => (string) config('services.nominatim.user_agent'),
                    'Accept-Language' => $locale,
                ])
                ->get(rtrim((string) config('services.nominatim.url'), '/').'/search', $parameters);

            abort_if($response->failed(), 502, 'Location search is currently unavailable.');

            $payload = $response->json();

            if (! is_array($payload)) {
                return [];
            }

            return collect($payload)
                ->filter(fn (mixed $item): bool => is_array($item)
                    && is_numeric($item['lat'] ?? null)
                    && is_numeric($item['lon'] ?? null))
                ->map(fn (array $item): array => [
                    'label' => (string) ($item['display_name'] ?? ''),
                    'latitude' => (float) $item['lat'],
                    'longitude' => (float) $item['lon'],
                ])
                ->filter(fn (array $item): bool => $item['label'] !== ''
                    && abs($item['latitude']) <= 90
                    && abs($item['longitude']) <= 180)
                ->values()
                ->all();
        });

        return response()->json(['results' => $results]);
    }
}
