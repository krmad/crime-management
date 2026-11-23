<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachSuspectRequest;
use App\Http\Requests\StoreCaseSuspectRequest;
use App\Http\Requests\UpdateCaseSuspectRequest;
use App\Models\CrimeCase;
use App\Models\Suspect;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CaseSuspectController extends Controller
{
    public function attach(AttachSuspectRequest $request, CrimeCase $case): RedirectResponse
    {
        Gate::authorize('managePeople', $case);

        $validated = $request->safe()->only(['notes', 'status']);
        $suspect = Suspect::visibleTo($request->user())->findOrFail($request->integer('suspect_id'));

        try {
            $case->suspects()->attach($suspect->getKey(), $validated);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'suspect_id' => 'This suspect is already linked to this case.',
            ]);
        }

        return redirect()->route('cases.show', $case)->with('status', 'suspect-linked');
    }

    public function storeNew(StoreCaseSuspectRequest $request, CrimeCase $case): RedirectResponse
    {
        Gate::authorize('managePeople', $case);

        $personData = $request->safe()->only(['full_name', 'alias', 'date_of_birth', 'gender', 'address', 'phone']);
        $associationData = $request->safe()->only(['notes', 'status']);

        DB::transaction(function () use ($case, $personData, $associationData): void {
            $suspect = Suspect::create($personData);
            $case->suspects()->attach($suspect->getKey(), $associationData);
        });

        return redirect()->route('cases.show', $case)->with('status', 'suspect-created');
    }

    public function update(UpdateCaseSuspectRequest $request, CrimeCase $case, Suspect $suspect): RedirectResponse
    {
        Gate::authorize('managePeople', $case);

        $case->suspects()
            ->where('suspects.id', $suspect->getKey())
            ->firstOrFail();

        $case->suspects()->updateExistingPivot(
            $suspect->getKey(),
            $request->safe()->only(['notes', 'status']),
        );

        return redirect()->route('cases.show', $case)->with('status', 'suspect-updated');
    }
}
