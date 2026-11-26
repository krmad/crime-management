<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-users');

        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        $users = User::query()
            ->with('officerProfile')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when(in_array($role, array_column(UserRole::cases(), 'value'), true), fn ($query) => $query->where('role', $role))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users', 'search', 'role', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('manage-users');

        return view('users.create', ['roles' => UserRole::cases()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validated();
        $role = UserRole::from($validated['role']);

        DB::transaction(function () use ($validated, $role): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->forceFill(['role' => $role])->save();

            if (in_array($role, [UserRole::PoliceOfficer, UserRole::Investigator], true)) {
                $user->officerProfile()->create([
                    'badge_number' => $validated['badge_number'] ?? null,
                    'rank' => $validated['rank'] ?? null,
                    'department' => $validated['department'] ?? null,
                    'police_station' => $validated['police_station'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'employment_status' => $validated['employment_status'] ?? 'active',
                ]);
            }
        });

        return redirect()->route('users.index')->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        return view('users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user): void {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $role = UserRole::from($validated['role']);

            $this->ensureAdministratorChangeAllowed($target, $role, $target->is_active);

            $target->name = $validated['name'];
            $target->email = $validated['email'];
            $target->role = $role;

            if (($validated['password'] ?? null) !== null) {
                $target->password = $validated['password'];
            }

            $target->save();

            if ($role === UserRole::Judge) {
                $target->officerProfile()->first()?->delete();
            } elseif (in_array($role, [UserRole::PoliceOfficer, UserRole::Investigator], true)) {
                $target->archivedOfficerProfile()->first()?->restore();
            }
        });

        return redirect()->route('users.index')->with('status', 'user-updated');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        DB::transaction(function () use ($request, $user): void {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($target->is($request->user())) {
                throw ValidationException::withMessages([
                    'is_active' => 'You cannot deactivate your own account.',
                ]);
            }

            $this->ensureAdministratorChangeAllowed($target, $target->role, false);

            $target->is_active = false;
            $target->save();
        });

        return redirect()->route('users.index')->with('status', 'user-deactivated');
    }

    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        DB::transaction(function () use ($user): void {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $target->is_active = true;
            $target->save();
        });

        return redirect()->route('users.index')->with('status', 'user-activated');
    }

    private function ensureAdministratorChangeAllowed(User $user, UserRole $role, bool $isActive): void
    {
        $wasActiveAdministrator = $user->role === UserRole::Administrator && $user->is_active;
        $remainsActiveAdministrator = $role === UserRole::Administrator && $isActive;

        if (! $wasActiveAdministrator || $remainsActiveAdministrator) {
            return;
        }

        $otherActiveAdministrators = User::query()
            ->where('role', UserRole::Administrator->value)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->exists();

        if (! $otherActiveAdministrators) {
            throw ValidationException::withMessages([
                'role' => 'The last active administrator cannot be deactivated or demoted.',
            ]);
        }
    }
}
