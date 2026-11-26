<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Enums\CaseTitleType;
use App\Enums\SuspectCaseStatus;
use App\Enums\UserRole;
use App\Http\Requests\AssignInvestigatorRequest;
use App\Http\Requests\AssignJudgeRequest;
use App\Http\Requests\CloseCaseRequest;
use App\Http\Requests\RecordJudgmentRequest;
use App\Http\Requests\StoreCrimeCaseRequest;
use App\Http\Requests\UpdateCaseLocationRequest;
use App\Http\Requests\UpdateInvestigationRequest;
use App\Models\CaseStatusHistory;
use App\Models\CrimeCase;
use App\Models\CrimeCategory;
use App\Models\Suspect;
use App\Models\User;
use App\Models\Victim;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            ->with(['creator:id,name', 'investigator:id,name', 'crimeCategory.parent.parent']);

        $cases = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('cases.index', compact('cases'));
    }

    public function show(Request $request, CrimeCase $case): View
    {
        Gate::authorize('view', $case);

        $case->load([
            'creator:id,name',
            'investigator:id,name',
            'judge:id,name',
            'crimeCategory.parent.parent',
            'suspects',
            'victims',
            'evidence' => fn ($query) => $query->orderByDesc('collected_at')->orderByDesc('id'),
        ]);

        $investigators = User::where('role', UserRole::Investigator)
            ->orderBy('name')
            ->get(['id', 'name']);

        $judges = $this->assignableJudges();

        $availableSuspects = Suspect::visibleTo($request->user())
            ->whereDoesntHave('cases', fn ($query) => $query->where('cases.id', $case->getKey()))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $availableVictims = Victim::visibleTo($request->user())
            ->whereDoesntHave('cases', fn ($query) => $query->where('cases.id', $case->getKey()))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return view('cases.show', compact('case', 'investigators', 'judges', 'availableSuspects', 'availableVictims'));
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

    public function assignJudge(AssignJudgeRequest $request, CrimeCase $case): RedirectResponse
    {
        $case = DB::transaction(function () use ($request, $case): CrimeCase {
            $case->refreshForUpdate();

            Gate::authorize('assignJudge', $case);

            $originalJudgeId = $case->judge_id === null ? null : (int) $case->judge_id;
            $judgeId = (int) $request->validated()['judge_id'];

            $query = CrimeCase::query()
                ->whereKey($case->getKey())
                ->whereIn('status', [CaseStatus::Open->value, CaseStatus::UnderInvestigation->value])
                ->whereNull('forwarded_for_review_at');

            if ($originalJudgeId === null) {
                $query->whereNull('judge_id');
            } else {
                $query->where('judge_id', $originalJudgeId);
            }

            $updated = $query->update(['judge_id' => $judgeId]);

            abort_unless($updated === 1, 409, 'The case is no longer available for judge assignment.');

            if ($originalJudgeId !== $judgeId) {
                $history = new CaseStatusHistory;
                $history->case_id = $case->getKey();
                $history->actor_id = $request->user()->getAuthIdentifier();
                $history->previous_status = $case->status;
                $history->new_status = $case->status;
                $history->reason = $this->judgeAssignmentReason($originalJudgeId, $judgeId);
                $history->occurred_at = now();
                $history->save();
            }

            return $case;
        });

        return redirect()->route('cases.show', $case)->with('status', 'case-judge-assigned');
    }

    public function updateLocation(UpdateCaseLocationRequest $request, CrimeCase $case): RedirectResponse
    {
        $case = DB::transaction(function () use ($request, $case): CrimeCase {
            $case->refreshForUpdate();

            Gate::authorize('updateLocation', $case);

            $validated = $request->validated();

            $updated = CrimeCase::whereKey($case->getKey())
                ->where('latitude', $case->latitude)
                ->where('longitude', $case->longitude)
                ->update([
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                ]);

            abort_unless($updated === 1, 409, 'The case location changed while it was being updated.');

            return $case;
        });

        return redirect()->route('cases.show', $case)->with('status', 'case-location-updated');
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

        $titleTypes = array_map(
            fn (CaseTitleType $titleType): array => [
                'value' => $titleType->value,
                'label' => $titleType->label(),
            ],
            CaseTitleType::cases(),
        );

        return view('cases.create', [
            'titleTypes' => $titleTypes,
            'categories' => $this->categoryData(),
            'customTitleTypeValue' => CaseTitleType::Other->value,
            'judges' => $this->assignableJudges(),
        ]);
    }

    public function store(StoreCrimeCaseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $titleType = CaseTitleType::from($validated['case_title_type']);
        $isCustom = $titleType === CaseTitleType::Other;

        $case = DB::transaction(function () use ($request, $validated, $titleType, $isCustom): CrimeCase {
            $case = $request->user()->createdCases()->create([
                'title' => $isCustom ? $validated['custom_crime_type'] : $titleType->label(),
                'crime_type' => $titleType->label(),
                'custom_crime_type' => $isCustom ? $validated['custom_crime_type'] : null,
                'crime_category_id' => $validated['crime_category_id'],
                'location' => $validated['location'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'description' => $validated['description'],
                'incident_date' => $validated['incident_date'] ?? null,
            ]);

            if (filled($validated['judge_id'] ?? null)) {
                $case->forceFill(['judge_id' => $validated['judge_id']])->save();
            }

            if ($request->hasKnownSuspect()) {
                $suspect = Suspect::create([
                    'full_name' => $validated['suspect_name'],
                    'alias' => $validated['suspect_alias'] ?? null,
                ]);

                $case->suspects()->attach($suspect->getKey(), [
                    'status' => SuspectCaseStatus::Identified->value,
                ]);
            }

            return $case;
        });

        return redirect()->route('cases.index')->with('status', 'case-created');
    }

    /**
     * @return array<int, array{id: int, name: string, children: array<int, mixed>}>
     */
    private function categoryData(): array
    {
        return CrimeCategory::query()
            ->active()
            ->where('level', 1)
            ->whereNull('parent_id')
            ->with([
                'children' => fn (HasMany $query): HasMany => $query
                    ->where('is_active', true)
                    ->where('level', 2)
                    ->with([
                        'children' => fn (HasMany $childQuery): HasMany => $childQuery
                            ->where('is_active', true)
                            ->where('level', 3)
                            ->orderBy('sort_order')
                            ->orderBy('id'),
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CrimeCategory $level1): array => [
                'id' => $level1->getKey(),
                'name' => $level1->name,
                'children' => $level1->children->map(fn (CrimeCategory $level2): array => [
                    'id' => $level2->getKey(),
                    'name' => $level2->name,
                    'children' => $level2->children->map(fn (CrimeCategory $level3): array => [
                        'id' => $level3->getKey(),
                        'name' => $level3->name,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function judgeAssignmentReason(?int $previousJudgeId, int $judgeId): string
    {
        $names = User::query()
            ->whereKey(array_filter([$previousJudgeId, $judgeId]))
            ->pluck('name', 'id');

        $newJudgeName = $names->get($judgeId, 'Unknown judge');

        if ($previousJudgeId === null) {
            return "Assigned judge: {$newJudgeName}";
        }

        $previousJudgeName = $names->get($previousJudgeId, 'Unknown judge');

        return "Assigned judge changed from {$previousJudgeName} to {$newJudgeName}";
    }

    /**
     * @return Collection<int, User>
     */
    private function assignableJudges(): Collection
    {
        return User::query()
            ->where('role', UserRole::Judge)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
