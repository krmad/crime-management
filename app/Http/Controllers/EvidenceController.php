<?php

namespace App\Http\Controllers;

use App\Enums\CustodyEvent;
use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Http\Requests\StoreCustodyEventRequest;
use App\Http\Requests\StoreEvidenceRequest;
use App\Http\Requests\UpdateEvidenceRequest;
use App\Http\Requests\UploadEvidenceFileRequest;
use App\Models\CrimeCase;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Evidence::class);

        $search = $request->string('search')->trim()->toString();
        $status = EvidenceStatus::tryFrom($request->string('status')->trim()->toString())?->value ?? '';
        $type = EvidenceType::tryFrom($request->string('type')->trim()->toString())?->value ?? '';

        $evidence = Evidence::visibleTo($request->user())
            ->with([
                'case:id,title,status,created_by,investigator_id',
                'collector:id,name',
            ])
            ->when($search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('title', 'like', "%{$search}%")
                ->orWhere('evidence_number', 'like', "%{$search}%")))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($type !== '', fn ($query) => $query->where('evidence_type', $type))
            ->orderByDesc('collected_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('evidence.index', compact('evidence', 'search', 'status', 'type'));
    }

    public function create(CrimeCase $case): View
    {
        Gate::authorize('create', [Evidence::class, $case]);

        return view('evidence.create', compact('case'));
    }

    public function store(StoreEvidenceRequest $request, CrimeCase $case): RedirectResponse
    {
        $validated = $request->validated();
        $validated['collected_by'] ??= $request->user()->getKey();

        $evidence = DB::transaction(function () use ($case, $request, $validated): Evidence {
            $evidence = Evidence::createWithReference([
                'case_id' => $case->getKey(),
                ...$validated,
            ]);

            $evidence->custodyLogs()->create([
                'event_type' => CustodyEvent::Collection,
                'transferred_by' => $request->user()->getKey(),
                'from_location' => null,
                'to_location' => $evidence->storage_location,
                'occurred_at' => $evidence->collected_at,
                'notes' => 'Evidence registered in the case file.',
            ]);

            return $evidence;
        });

        return redirect()->route('evidence.show', $evidence)->with('status', 'evidence-created');
    }

    public function show(Request $request, Evidence $evidence): View
    {
        Gate::authorize('view', $evidence);

        $evidence->load([
            'case:id,title,status,created_by,investigator_id',
            'collector:id,name',
            'custodyLogs.transferredBy:id,name',
            'custodyLogs.receivedBy:id,name',
        ]);

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('evidence.show', compact('evidence', 'users'));
    }

    public function edit(Evidence $evidence): View
    {
        Gate::authorize('update', $evidence);

        return view('evidence.edit', compact('evidence'));
    }

    public function update(UpdateEvidenceRequest $request, Evidence $evidence): RedirectResponse
    {
        $validated = $request->validated();

        $evidence = DB::transaction(function () use ($evidence, $request, $validated): Evidence {
            $previousLocation = $evidence->storage_location;
            $previousStatus = $evidence->status;

            $evidence->update($validated);

            if ($previousLocation !== $evidence->storage_location) {
                $evidence->custodyLogs()->create([
                    'event_type' => CustodyEvent::LocationChange,
                    'transferred_by' => $request->user()->getKey(),
                    'from_location' => $previousLocation,
                    'to_location' => $evidence->storage_location,
                    'occurred_at' => now(),
                    'notes' => 'Storage location updated in evidence metadata.',
                ]);
            }

            if ($evidence->status !== $previousStatus && in_array($evidence->status, [EvidenceStatus::Released, EvidenceStatus::Disposed], true)) {
                $evidence->custodyLogs()->create([
                    'event_type' => $evidence->status === EvidenceStatus::Released ? CustodyEvent::Release : CustodyEvent::Disposal,
                    'transferred_by' => $request->user()->getKey(),
                    'from_location' => $evidence->storage_location,
                    'to_location' => null,
                    'occurred_at' => now(),
                    'notes' => 'Evidence status updated in evidence metadata.',
                ]);
            }

            return $evidence;
        });

        return redirect()->route('evidence.show', $evidence)->with('status', 'evidence-updated');
    }

    public function uploadFile(UploadEvidenceFileRequest $request, Evidence $evidence): RedirectResponse
    {
        $file = $request->file('attachment');
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $storedPath = $file->storeAs('evidence/'.$evidence->getKey(), Str::uuid()->toString().'.'.$extension, 'evidence');
        $previousPath = $evidence->private_file_path;

        $evidence->update([
            'private_file_path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        if ($previousPath !== null && $previousPath !== $storedPath) {
            Storage::disk('evidence')->delete($previousPath);
        }

        return back()->with('status', 'evidence-file-uploaded');
    }

    public function download(Evidence $evidence): StreamedResponse
    {
        Gate::authorize('download', $evidence);

        abort_unless(
            $evidence->private_file_path !== null
                && Storage::disk('evidence')->exists($evidence->private_file_path),
            404,
        );

        $filename = Str::replace(["\r", "\n", '\\', '/'], '_', Str::ascii($evidence->original_filename ?? 'evidence'));

        return Storage::disk('evidence')->download($evidence->private_file_path, $filename, [
            'Content-Type' => $evidence->mime_type ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function storeCustody(StoreCustodyEventRequest $request, Evidence $evidence): RedirectResponse
    {
        $validated = $request->validated();
        $eventType = CustodyEvent::from($validated['event_type']);

        $evidence = DB::transaction(function () use ($evidence, $request, $eventType, $validated): Evidence {
            $fromLocation = $evidence->storage_location;
            $updates = [];

            if ($eventType === CustodyEvent::Transfer) {
                $updates['status'] = EvidenceStatus::Transferred;
                $updates['storage_location'] = $validated['to_location'];
            } elseif ($eventType === CustodyEvent::LocationChange) {
                $updates['storage_location'] = $validated['to_location'];
            } elseif ($eventType === CustodyEvent::Release) {
                $updates['status'] = EvidenceStatus::Released;
            } elseif ($eventType === CustodyEvent::Disposal) {
                $updates['status'] = EvidenceStatus::Disposed;
            }

            if ($updates !== []) {
                $evidence->update($updates);
            }

            $evidence->custodyLogs()->create([
                'event_type' => $eventType,
                'transferred_by' => $request->user()->getKey(),
                'received_by' => $validated['received_by'] ?? null,
                'from_location' => $fromLocation,
                'to_location' => $validated['to_location'] ?? null,
                'occurred_at' => $validated['occurred_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            return $evidence;
        });

        return redirect()->route('evidence.show', $evidence)->with('status', 'custody-recorded');
    }
}
