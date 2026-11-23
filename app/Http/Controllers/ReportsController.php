<?php

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Http\Requests\ReportFilterRequest;
use App\Models\CrimeCase;
use App\Models\CrimeCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(ReportFilterRequest $request): View
    {
        $validated = $request->validated();

        $cases = $this->cases($request)
            ->with([
                'investigator:id,name',
                'crimeCategory:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $statusCounts = $cases->countBy(fn (CrimeCase $case): string => $case->status->value);

        $summary = [
            ['label' => 'Total Cases', 'value' => $cases->count(), 'accent' => 'bg-indigo-500'],
            ['label' => 'Open Cases', 'value' => $statusCounts->get(CaseStatus::Open->value, 0), 'accent' => 'bg-amber-500'],
            ['label' => 'Under Investigation', 'value' => $statusCounts->get(CaseStatus::UnderInvestigation->value, 0), 'accent' => 'bg-sky-500'],
            ['label' => 'Judgment Recorded', 'value' => $statusCounts->get(CaseStatus::JudgmentRecorded->value, 0), 'accent' => 'bg-violet-500'],
            ['label' => 'Closed Cases', 'value' => $statusCounts->get(CaseStatus::Closed->value, 0), 'accent' => 'bg-emerald-500'],
        ];

        $byCrimeType = $cases
            ->groupBy(fn (CrimeCase $case): string => $case->crime_type)
            ->map(fn ($group, string $crimeType): array => ['label' => $crimeType, 'total' => $group->count()])
            ->sortByDesc('total')
            ->values();

        $byClassification = $cases
            ->groupBy(fn (CrimeCase $case): string => $case->crimeCategory?->name ?? 'Unclassified')
            ->map(fn ($group, string $classification): array => ['label' => $classification, 'total' => $group->count()])
            ->sortByDesc('total')
            ->values();

        $monthly = $cases
            ->groupBy(fn (CrimeCase $case): string => $case->created_at?->format('Y-m') ?? 'unknown')
            ->map(fn ($group, string $month): array => ['month' => $month, 'total' => $group->count()])
            ->sortKeys()
            ->values();

        $outcomes = [
            ['label' => 'Cases with investigation notes', 'total' => $cases->filter(fn (CrimeCase $case): bool => filled($case->investigation_notes))->count()],
            ['label' => 'Cases with judgment notes', 'total' => $cases->filter(fn (CrimeCase $case): bool => filled($case->judgment_notes))->count()],
            ['label' => 'Judgment recorded', 'total' => $statusCounts->get(CaseStatus::JudgmentRecorded->value, 0)],
            ['label' => 'Closed', 'total' => $statusCounts->get(CaseStatus::Closed->value, 0)],
        ];

        $workload = $cases
            ->groupBy(fn (CrimeCase $case): string => $case->investigator_id === null ? 'unassigned' : (string) $case->investigator_id)
            ->map(fn ($group): array => [
                'investigator' => $group->first()->investigator?->name ?? 'Unassigned',
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $categories = CrimeCategory::active()->orderBy('name')->get(['id', 'name']);

        $filters = [
            'from' => $validated['from'] ?? '',
            'to' => $validated['to'] ?? '',
            'status' => $validated['status'] ?? '',
            'classification' => $validated['classification'] ?? '',
        ];

        return view('reports.index', compact(
            'cases',
            'summary',
            'statusCounts',
            'byCrimeType',
            'byClassification',
            'monthly',
            'outcomes',
            'workload',
            'categories',
            'filters',
        ));
    }

    public function export(ReportFilterRequest $request): StreamedResponse
    {
        $cases = $this->cases($request)
            ->with([
                'investigator:id,name',
                'crimeCategory:id,name',
            ])
            ->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($cases): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Case ID',
                'Title',
                'Crime Type',
                'Classification',
                'Status',
                'Investigator',
                'Created At',
                'Incident Date',
                'Judgment Notes',
            ]);

            foreach ($cases as $case) {
                fputcsv($handle, array_map(self::escapeCsv(...), [
                    (string) $case->getKey(),
                    $case->title,
                    $case->crime_type,
                    $case->crimeCategory?->name ?? 'Unclassified',
                    $case->status->value,
                    $case->investigator?->name ?? 'Unassigned',
                    $case->created_at?->toDateString() ?? '',
                    $case->incident_date?->toDateString() ?? '',
                    $case->judgment_notes ?? '',
                ]));
            }

            fclose($handle);
        }, 'cases-report-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function cases(ReportFilterRequest $request): Builder
    {
        $validated = $request->validated();

        return CrimeCase::visibleTo($request->user())
            ->when(filled($validated['from'] ?? null), fn (Builder $query): Builder => $query->whereDate('created_at', '>=', $validated['from']))
            ->when(filled($validated['to'] ?? null), fn (Builder $query): Builder => $query->whereDate('created_at', '<=', $validated['to']))
            ->when(filled($validated['status'] ?? null), fn (Builder $query): Builder => $query->where('status', CaseStatus::from($validated['status'])->value))
            ->when(filled($validated['classification'] ?? null), fn (Builder $query): Builder => $query->where('crime_category_id', $validated['classification']));
    }

    private static function escapeCsv(mixed $value): string
    {
        $text = $value === null ? '' : (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $text) === 1 ? "'".$text : $text;
    }
}
