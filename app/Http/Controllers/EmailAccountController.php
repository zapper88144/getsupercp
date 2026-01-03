<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class EmailAccountController extends Controller
{
    public function __construct(protected RustDaemonClient $daemon) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Email/Index', [
            'accounts' => $request->user()->emailAccounts()->latest()->get(),
            'domains' => $request->user()->webDomains()->select('domain')->get()->pluck('domain'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'domain' => 'required|string|exists:web_domains,domain',
            'password' => 'required|string|min:8',
            'quota_mb' => 'required|integer|min:100|max:10240',
        ]);

        $email = $request->username.'@'.$request->domain;

        if (EmailAccount::where('email', $email)->exists()) {
            return back()->withErrors(['username' => 'This email address already exists.']);
        }

        $account = $request->user()->emailAccounts()->create([
            'email' => $email,
            'password' => Hash::make($request->password),
            'quota_mb' => $request->quota_mb,
            'status' => 'active',
        ]);

        $this->syncWithDaemon($account, $request->password);

        return redirect()->route('email-accounts.index');
    }

    public function destroy(EmailAccount $emailAccount)
    {
        $this->authorize('delete', $emailAccount);

        $this->daemon->call('delete_email_account', [
            'email' => $emailAccount->email,
        ]);

        $emailAccount->delete();

        return redirect()->route('email-accounts.index');
    }

    protected function syncWithDaemon(EmailAccount $account, string $plainPassword)
    {
        $this->daemon->call('update_email_account', [
            'email' => $account->email,
            'password' => $plainPassword,
            'quota_mb' => $account->quota_mb,
        ]);
    }
}
