<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachVictimRequest;
use App\Http\Requests\StoreCaseVictimRequest;
use App\Http\Requests\UpdateCaseVictimRequest;
use App\Models\CrimeCase;
use App\Models\Victim;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CaseVictimController extends Controller
{
    public function attach(AttachVictimRequest $request, CrimeCase $case): RedirectResponse
    {
        Gate::authorize('managePeople', $case);

        $validated = $request->safe()->only(['notes']);
        $victim = Victim::visibleTo($request->user())->findOrFail($request->integer('victim_id'));

        try {
            $case->victims()->attach($victim->getKey(), $validated);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'victim_id' => 'This victim is already linked to this case.',
            ]);
        }

        return redirect()->route('cases.show', $case)->with('status', 'victim-linked');
    }

    public function storeNew(StoreCaseVictimRequest $request, CrimeCase $case): RedirectResponse
    {
        Gate::authorize('managePeople', $case);

        $personData = $request->safe()->only(['full_name', 'date_of_birth', 'gender', 'address', 'phone']);
        $associationData = $request->safe()->only(['notes']);

        DB::transaction(function () use ($case, $personData, $associationData): void {
            $victim = Victim::create($personData);
            $case->victims()->attach($victim->getKey(), $associationData);
        });

        return redirect()->route('cases.show', $case)->with('status', 'victim-created');
    }

    public function update(UpdateCaseVictimRequest $request, CrimeCase $case, Victim $victim): RedirectResponse
    {
        Gate::authorize('managePeople', $case);

        $case->victims()
            ->where('victims.id', $victim->getKey())
            ->firstOrFail();

        $case->victims()->updateExistingPivot(
            $victim->getKey(),
            $request->safe()->only(['notes']),
        );

        return redirect()->route('cases.show', $case)->with('status', 'victim-updated');
    }
}
