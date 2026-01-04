<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailAccountRequest;
use App\Models\EmailAccount;
use App\Services\EmailService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailAccountController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(protected EmailService $emailService) {}

    public function index(Request $request): Response
    {
        $query = $request->user()->emailAccounts()->latest();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('email', 'like', "%{$search}%");
        }

        $allAccounts = $request->user()->emailAccounts;

        return Inertia::render('Email/Index', [
            'accounts' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
            'stats' => [
                'total' => $allAccounts->count(),
                'active' => $allAccounts->where('status', 'active')->count(),
                'totalQuota' => $allAccounts->sum('quota_mb'),
            ],
        ]);
    }

    public function show(EmailAccount $emailAccount): Response
    {
        $this->authorize('view', $emailAccount);

        return Inertia::render('Email/Show', [
            'account' => $emailAccount,
        ]);
    }

    public function store(StoreEmailAccountRequest $request)
    {
        $validated = $request->validated();

        try {
            $account = $this->emailService->create($request->user(), $validated);

            return redirect()->route('email-accounts.index')
                ->with('success', 'Email account created successfully.');
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to create email account.', route('email-accounts.index'));
        }
    }

    public function update(Request $request, EmailAccount $emailAccount)
    {
        $this->authorize('update', $emailAccount);

        $validated = $request->validate([
            'password' => 'nullable|string|min:8',
            'quota_mb' => 'nullable|integer|min:256|max:102400',
        ]);

        try {
            $this->emailService->update($emailAccount, $validated);

            return back()->with('success', 'Email account updated successfully.');
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to update email account.');
        }
    }

    public function patch(Request $request, EmailAccount $emailAccount)
    {
        return $this->update($request, $emailAccount);
    }

    public function destroy(EmailAccount $emailAccount)
    {
        $this->authorize('delete', $emailAccount);

        try {
            $this->emailService->delete($emailAccount);

            return redirect()->route('email-accounts.index')
                ->with('success', 'Email account deleted successfully.');
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to delete email account.', route('email-accounts.index'));
        }
    }
}
