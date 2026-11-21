<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCrimeCaseRequest;
use App\Models\CrimeCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CrimeCaseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CrimeCase::class);

        $user = $request->user();
        $query = CrimeCase::query()->with(['creator:id,name', 'investigator:id,name']);

        if ($user->cannot('view-all-cases') && $user->can('investigate-cases')) {
            $query->whereBelongsTo($user, 'investigator');
        } elseif ($user->cannot('view-all-cases') && $user->can('open-cases')) {
            $query->whereBelongsTo($user, 'creator');
        }

        $cases = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('cases.index', compact('cases'));
    }

    public function show(CrimeCase $case): View
    {
        Gate::authorize('view', $case);

        $case->load(['creator:id,name', 'investigator:id,name']);

        return view('cases.show', compact('case'));
    }

    public function create(): View
    {
        Gate::authorize('create', CrimeCase::class);

        return view('cases.create');
    }

    public function store(StoreCrimeCaseRequest $request): RedirectResponse
    {
        $request->user()->createdCases()->create($request->validated());

        return redirect()->route('cases.index')->with('status', 'case-created');
    }
}
