<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Models\CrimeCase;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CrimeCase::class);

        $user = $request->user();
        $visibleCases = CrimeCase::visibleTo($user);

        $countsByStatus = (clone $visibleCases)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statistics = [
            ['label' => 'Total Cases', 'value' => (clone $visibleCases)->count(), 'note' => 'Cases visible to you', 'accent' => 'bg-indigo-500'],
            ['label' => 'Open Cases', 'value' => (int) $countsByStatus->get(CaseStatus::Open->value, 0), 'note' => 'Awaiting action', 'accent' => 'bg-amber-500'],
            ['label' => 'Under Investigation', 'value' => (int) $countsByStatus->get(CaseStatus::UnderInvestigation->value, 0), 'note' => 'Active investigations', 'accent' => 'bg-sky-500'],
            ['label' => 'Judgment Recorded', 'value' => (int) $countsByStatus->get(CaseStatus::JudgmentRecorded->value, 0), 'note' => 'Awaiting closure', 'accent' => 'bg-violet-500'],
            ['label' => 'Closed Cases', 'value' => (int) $countsByStatus->get(CaseStatus::Closed->value, 0), 'note' => 'Resolved cases', 'accent' => 'bg-emerald-500'],
        ];

        $recentCases = (clone $visibleCases)
            ->with(['investigator:id,name', 'crimeCategory.parent.parent'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $navigation = $this->navigationFor($user);

        return view('dashboard', compact('statistics', 'recentCases', 'navigation'));
    }

    /**
     * @return array<int, array{label: string, url: ?string}>
     */
    private function navigationFor(User $user): array
    {
        $navigation = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
        ];

        if ($user->can('create', CrimeCase::class)) {
            $navigation[] = ['label' => 'Crime Reports', 'url' => route('cases.create')];
        }

        $navigation[] = ['label' => 'Cases', 'url' => route('cases.index')];

        if ($user->can('manage-users')) {
            $navigation[] = ['label' => 'User Management', 'url' => route('users.index')];
            $navigation[] = ['label' => 'Officers', 'url' => route('officers.index')];
        }

        $navigation[] = ['label' => 'Suspects', 'url' => route('suspects.index')];
        $navigation[] = ['label' => 'Victims', 'url' => route('victims.index')];
        $navigation[] = ['label' => 'Evidence', 'url' => $user->can('viewAny', Evidence::class) ? route('evidence.index') : null];
        $navigation[] = ['label' => 'Reports', 'url' => route('reports.index')];

        return $navigation;
    }
}
