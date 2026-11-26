<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreOfficerProfileRequest;
use App\Http\Requests\UpdateOfficerProfileRequest;
use App\Models\OfficerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OfficerProfileController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-users');

        $search = $request->string('search')->trim()->toString();
        $rank = $request->string('rank')->toString();
        $employmentStatus = $request->string('employment_status')->toString();

        $officers = OfficerProfile::query()
            ->with('user')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('badge_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($rank !== '', fn ($query) => $query->where('rank', $rank))
            ->when(in_array($employmentStatus, array_column(EmploymentStatus::cases(), 'value'), true), fn ($query) => $query->where('employment_status', $employmentStatus))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $ranks = OfficerProfile::query()
            ->whereNotNull('rank')
            ->distinct()
            ->orderBy('rank')
            ->pluck('rank');

        return view('officers.index', compact('officers', 'search', 'rank', 'employmentStatus', 'ranks'));
    }

    public function create(): View
    {
        Gate::authorize('manage-users');

        $eligibleUsers = User::query()
            ->whereIn('role', [UserRole::PoliceOfficer->value, UserRole::Investigator->value])
            ->whereDoesntHave('officerProfile')
            ->whereDoesntHave('archivedOfficerProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('officers.create', [
            'eligibleUsers' => $eligibleUsers,
            'employmentStatuses' => EmploymentStatus::cases(),
        ]);
    }

    public function store(StoreOfficerProfileRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        OfficerProfile::create($request->validated());

        return redirect()->route('officers.index')->with('status', 'officer-created');
    }

    public function show(OfficerProfile $officerProfile): View
    {
        Gate::authorize('manage-users');

        return view('officers.show', [
            'officerProfile' => $officerProfile->load('user'),
        ]);
    }

    public function edit(OfficerProfile $officerProfile): View
    {
        Gate::authorize('manage-users');

        return view('officers.edit', [
            'officerProfile' => $officerProfile,
            'employmentStatuses' => EmploymentStatus::cases(),
        ]);
    }

    public function update(UpdateOfficerProfileRequest $request, OfficerProfile $officerProfile): RedirectResponse
    {
        Gate::authorize('manage-users');

        $officerProfile->update($request->validated());

        return redirect()->route('officers.index')->with('status', 'officer-updated');
    }
}
