<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuspectRequest;
use App\Http\Requests\UpdateSuspectRequest;
use App\Models\Suspect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SuspectController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Suspect::class);

        $search = $request->string('search')->trim()->toString();

        $suspects = Suspect::visibleTo($request->user())
            ->withCount(['cases' => fn ($query) => $query->visibleTo($request->user())])
            ->when($search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('alias', 'like', "%{$search}%")))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('suspects.index', compact('suspects', 'search'));
    }

    public function create(): View
    {
        Gate::authorize('create', Suspect::class);

        return view('suspects.create');
    }

    public function store(StoreSuspectRequest $request): RedirectResponse
    {
        Gate::authorize('create', Suspect::class);

        Suspect::create($request->safe()->only(['full_name', 'alias', 'date_of_birth', 'gender', 'address', 'phone']));

        return redirect()->route('suspects.index')->with('status', 'suspect-created');
    }

    public function show(Request $request, Suspect $suspect): View
    {
        Gate::authorize('view', $suspect);

        $cases = $suspect->cases()
            ->visibleTo($request->user())
            ->with(['creator:id,name', 'investigator:id,name'])
            ->orderByDesc('cases.created_at')
            ->orderByDesc('cases.id')
            ->paginate(10, ['*'], 'cases_page')
            ->withQueryString();

        return view('suspects.show', compact('suspect', 'cases'));
    }

    public function edit(Suspect $suspect): View
    {
        Gate::authorize('update', $suspect);

        return view('suspects.edit', compact('suspect'));
    }

    public function update(UpdateSuspectRequest $request, Suspect $suspect): RedirectResponse
    {
        Gate::authorize('update', $suspect);

        $suspect->update($request->safe()->only(['full_name', 'alias', 'date_of_birth', 'gender', 'address', 'phone']));

        return redirect()->route('suspects.index')->with('status', 'suspect-updated');
    }
}
