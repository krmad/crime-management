<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVictimRequest;
use App\Http\Requests\UpdateVictimRequest;
use App\Models\Victim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VictimController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Victim::class);

        $search = $request->string('search')->trim()->toString();

        $victims = Victim::visibleTo($request->user())
            ->withCount(['cases' => fn ($query) => $query->visibleTo($request->user())])
            ->when($search !== '', fn ($query) => $query->where('full_name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('victims.index', compact('victims', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', Victim::class);

        return view('victims.create');
    }

    public function store(StoreVictimRequest $request): RedirectResponse
    {
        Gate::authorize('create', Victim::class);

        Victim::create($request->safe()->only(['full_name', 'date_of_birth', 'gender', 'address', 'phone']));

        return redirect()->route('victims.index')->with('status', 'victim-created');
    }

    public function show(Request $request, Victim $victim): View
    {
        Gate::authorize('view', $victim);

        $cases = $victim->cases()
            ->visibleTo($request->user())
            ->with(['creator:id,name', 'investigator:id,name'])
            ->orderByDesc('cases.created_at')
            ->orderByDesc('cases.id')
            ->paginate(10, ['*'], 'cases_page')
            ->withQueryString();

        return view('victims.show', compact('victim', 'cases'));
    }

    public function edit(Victim $victim): View
    {
        Gate::authorize('update', $victim);

        return view('victims.edit', compact('victim'));
    }

    public function update(UpdateVictimRequest $request, Victim $victim): RedirectResponse
    {
        Gate::authorize('update', $victim);

        $victim->update($request->safe()->only(['full_name', 'date_of_birth', 'gender', 'address', 'phone']));

        return redirect()->route('victims.index')->with('status', 'victim-updated');
    }
}
