<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\UserRole;
use App\Http\Requests\AssignInvestigatorRequest;
use App\Http\Requests\CloseCaseRequest;
use App\Http\Requests\RecordJudgmentRequest;
use App\Http\Requests\StoreCrimeCaseRequest;
use App\Http\Requests\UpdateInvestigationRequest;
use App\Models\CrimeCase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CrimeCaseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CrimeCase::class);

        $query = CrimeCase::visibleTo($request->user())
            ->with(['creator:id,name', 'investigator:id,name']);

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
        $case = DB::transaction(function () use ($request, $case): CrimeCase {
            $case->refreshForUpdate();

            Gate::authorize('assign', $case);

            $updated = CrimeCase::whereKey($case->getKey())
                ->where('status', '!=', CaseStatus::Closed->value)
                ->where('investigator_id', $case->investigator_id)
                ->update(['investigator_id' => $request->validated()['investigator_id']]);

            abort_unless($updated === 1, 409, 'The case is no longer available for assignment.');

            return $case;
        });

        return redirect()->route('cases.show', $case)->with('status', 'case-assigned');
    }

    public function updateInvestigation(UpdateInvestigationRequest $request, CrimeCase $case): RedirectResponse
    {
        $case = DB::transaction(function () use ($request, $case): CrimeCase {
            $case->refreshForUpdate();

            Gate::authorize('updateInvestigation', $case);

            $status = $case->status === CaseStatus::Open
                ? CaseStatus::UnderInvestigation
                : $case->status;

            $updated = CrimeCase::whereKey($case->getKey())
                ->whereIn('status', [CaseStatus::Open->value, CaseStatus::UnderInvestigation->value])
                ->where('investigator_id', $case->investigator_id)
                ->whereNull('closed_at')
                ->update([
                    'investigation_notes' => $request->validated()['investigation_notes'],
                    'status' => $status->value,
                ]);

            abort_unless($updated === 1, 409, 'The case changed while the notes were being saved.');

            return $case;
        });

        return redirect()->route('cases.show', $case)->with('status', 'investigation-updated');
    }

    public function recordJudgment(RecordJudgmentRequest $request, CrimeCase $case): RedirectResponse
    {
        $case = DB::transaction(function () use ($request, $case): CrimeCase {
            $case->refreshForUpdate();

            Gate::authorize('recordJudgment', $case);

            $updated = CrimeCase::whereKey($case->getKey())
                ->whereIn('status', [CaseStatus::UnderInvestigation->value, CaseStatus::JudgmentRecorded->value])
                ->whereNotNull('investigation_notes')
                ->where('investigation_notes', '!=', '')
                ->update([
                    'judgment_notes' => $request->validated()['judgment_notes'],
                    'status' => CaseStatus::JudgmentRecorded->value,
                ]);

            abort_unless($updated === 1, 409, 'The case changed while the judgment was being saved.');

            return $case;
        });

        return redirect()->route('cases.show', $case)->with('status', 'judgment-recorded');
    }

    public function close(CloseCaseRequest $request, CrimeCase $case): RedirectResponse
    {
        $case = DB::transaction(function () use ($case): CrimeCase {
            $case->refreshForUpdate();

            Gate::authorize('close', $case);

            $updated = CrimeCase::whereKey($case->getKey())
                ->where('status', CaseStatus::JudgmentRecorded->value)
                ->whereNull('closed_at')
                ->whereNotNull('judgment_notes')
                ->where('judgment_notes', '!=', '')
                ->update([
                    'status' => CaseStatus::Closed->value,
                    'closed_at' => now(),
                ]);

            abort_unless($updated === 1, 409, 'The case changed while it was being closed.');

            return $case;
        });

        return redirect()->route('cases.show', $case)->with('status', 'case-closed');
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
