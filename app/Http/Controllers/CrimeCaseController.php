<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Http\Requests\AssignInvestigatorRequest;
use App\Http\Requests\RecordJudgmentRequest;
use App\Http\Requests\StoreCrimeCaseRequest;
use App\Http\Requests\UpdateInvestigationRequest;
use App\Models\CrimeCase;
use App\Models\User;
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

        $investigators = User::where('role', UserRole::Investigator)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('cases.show', compact('case', 'investigators'));
    }

    public function assign(AssignInvestigatorRequest $request, CrimeCase $case): RedirectResponse
    {
        $case->investigator()->associate($request->validated()['investigator_id']);
        $case->save();

        return redirect()->route('cases.show', $case)->with('status', 'case-assigned');
    }

    public function updateInvestigation(UpdateInvestigationRequest $request, CrimeCase $case): RedirectResponse
    {
        $case->investigation_notes = $request->validated()['investigation_notes'];

        if ($case->status === CaseStatus::Open) {
            $case->status = CaseStatus::UnderInvestigation;
        }

        $case->save();

        return redirect()->route('cases.show', $case)->with('status', 'investigation-updated');
    }

    public function recordJudgment(RecordJudgmentRequest $request, CrimeCase $case): RedirectResponse
    {
        $case->judgment_notes = $request->validated()['judgment_notes'];
        $case->status = CaseStatus::JudgmentRecorded;
        $case->save();

        return redirect()->route('cases.show', $case)->with('status', 'judgment-recorded');
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
