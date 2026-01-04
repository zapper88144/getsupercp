<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Notifications\Admin\UserCreatedNotification;
use App\Notifications\Admin\UserSuspendedNotification;
use App\Notifications\Admin\UserUnsuspendedNotification;
use App\Traits\LogsActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use LogsActivity;

    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $users = $query->latest('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->string('search'),
                'role' => $request->string('role'),
                'status' => $request->string('status'),
            ],
            'roles' => ['super-admin', 'admin', 'moderator', 'user'],
            'statuses' => ['active', 'suspended', 'inactive'],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'roles' => ['super-admin', 'admin', 'moderator', 'user'],
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'phone' => $request->string('phone'),
            'role' => $request->string('role'),
            'status' => 'active',
            'notes' => $request->string('notes'),
            'is_admin' => in_array($request->string('role'), ['super-admin', 'admin']),
        ]);

        $user->notify(new UserCreatedNotification($user));

        $this->logActivity(
            action: 'user_created',
            model: $user,
            description: "Created user account for {$user->email}"
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "User '{$user->name}' created successfully");
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'roles' => ['super-admin', 'admin', 'moderator', 'user'],
            'statuses' => ['active', 'suspended', 'inactive'],
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => ['super-admin', 'admin', 'moderator', 'user'],
            'statuses' => ['active', 'suspended', 'inactive'],
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'phone' => $request->string('phone'),
            'role' => $request->string('role'),
            'status' => $request->string('status'),
            'notes' => $request->string('notes'),
            'is_admin' => in_array($request->string('role'), ['super-admin', 'admin']),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => $request->string('password')]);
        }

        $this->logActivity(
            action: 'user_updated',
            model: $user,
            description: "Updated user account for {$user->email}"
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "User '{$user->name}' updated successfully");
    }

    /**
     * Delete the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        $this->logActivity(
            action: 'user_deleted',
            description: "Deleted user account for {$email} ({$name})"
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$name}' deleted successfully");
    }

    /**
     * Suspend a user account.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorize('suspend', $user);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => $request->string('reason'),
        ]);

        $user->notify(new UserSuspendedNotification($user, $request->string('reason')));

        $this->logActivity(
            action: 'user_suspended',
            model: $user,
            description: "Suspended user account for {$user->email}. Reason: {$request->string('reason')}"
        );

        return redirect()
            ->back()
            ->with('success', "User '{$user->name}' has been suspended");
    }

    /**
     * Unsuspend a user account.
     */
    public function unsuspend(User $user): RedirectResponse
    {
        $this->authorize('unsuspend', $user);

        $user->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        $user->notify(new UserUnsuspendedNotification($user));

        $this->logActivity(
            action: 'user_unsuspended',
            model: $user,
            description: "Unsuspended user account for {$user->email}"
        );

        return redirect()
            ->back()
            ->with('success', "User '{$user->name}' has been unsuspended");
    }

    /**
     * Force logout a user from all sessions.
     */
    public function forceLogout(User $user): RedirectResponse
    {
        $this->authorize('forceLogout', $user);

        // Invalidate all sessions by deleting user sessions
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        $this->logActivity(
            action: 'user_force_logout',
            model: $user,
            description: "Force logged out user {$user->email} from all sessions"
        );

        return redirect()
            ->back()
            ->with('success', "User '{$user->name}' has been logged out of all sessions");
    }

    /**
     * Reset two-factor authentication for a user.
     */
    public function resetTwoFactor(User $user): RedirectResponse
    {
        $this->authorize('resetTwoFactor', $user);

        $user->update(['two_factor_enabled' => false]);
        $user->twoFactorAuthentication()?->delete();

        $this->logActivity(
            action: 'user_reset_2fa',
            model: $user,
            description: "Reset two-factor authentication for user {$user->email}"
        );

        return redirect()
            ->back()
            ->with('success', "Two-factor authentication reset for '{$user->name}'");
    }

    /**
     * Bulk suspend users.
     */
    public function bulkSuspend(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
            'reason' => 'required|string|max:500',
        ]);

        $ids = $request->input('ids');
        $reason = $request->string('reason');
        $count = 0;

        foreach ($ids as $id) {
            $user = User::find($id);
            if ($user && auth()->user()->can('suspend', $user)) {
                $user->update([
                    'status' => 'suspended',
                    'suspended_at' => now(),
                    'suspended_reason' => $reason,
                ]);
                $user->notify(new UserSuspendedNotification($user, $reason));
                $count++;
            }
        }

        $this->logActivity(
            action: 'bulk_user_suspended',
            description: "Bulk suspended {$count} users. Reason: {$reason}"
        );

        return redirect()
            ->back()
            ->with('success', "Successfully suspended {$count} users");
    }

    /**
     * Bulk unsuspend users.
     */
    public function bulkUnsuspend(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        $ids = $request->input('ids');
        $count = 0;

        foreach ($ids as $id) {
            $user = User::find($id);
            if ($user && auth()->user()->can('unsuspend', $user)) {
                $user->update([
                    'status' => 'active',
                    'suspended_at' => null,
                    'suspended_reason' => null,
                ]);
                $user->notify(new UserUnsuspendedNotification($user));
                $count++;
            }
        }

        $this->logActivity(
            action: 'bulk_user_unsuspended',
            description: "Bulk unsuspended {$count} users"
        );

        return redirect()
            ->back()
            ->with('success', "Successfully unsuspended {$count} users");
    }

    /**
     * Bulk delete users.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        $ids = $request->input('ids');
        $count = 0;

        foreach ($ids as $id) {
            $user = User::find($id);
            if ($user && auth()->user()->can('delete', $user)) {
                $user->delete();
                $count++;
            }
        }

        $this->logActivity(
            action: 'bulk_user_deleted',
            description: "Bulk deleted {$count} users"
        );

        return redirect()
            ->back()
            ->with('success', "Successfully deleted {$count} users");
    }

    /**
     * Get users statistics for the admin dashboard.
     */
    public function stats(): array
    {
        $this->authorize('viewAny', User::class);

        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'admins' => User::where('role', 'admin')->orWhere('role', 'super-admin')->count(),
            'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray(),
            'users_by_status' => User::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
        ];
    }
}
