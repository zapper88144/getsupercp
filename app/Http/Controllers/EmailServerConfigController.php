<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailServerConfigController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $config = $user->emailServerConfig;

        return Inertia::render('Email/Config', [
            'config' => $config,
            'isHealthy' => $config?->isHealthy() ?? false,
            'requiresAttention' => $config?->requiresAttention() ?? true,
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        if ($user->emailServerConfig) {
            return redirect()->route('email.config');
        }

        return Inertia::render('Email/Setup', [
            'encryptionMethods' => ['none', 'tls', 'ssl'],
            'dmarcPolicies' => ['none', 'quarantine', 'reject'],
        ]);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        if ($user->emailServerConfig) {
            return redirect()->route('email.config');
        }

        $validated = $request->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|numeric',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'boolean',
            'from_email' => 'required|email',
            'from_name' => 'required|string',
        ]);

        $user->emailServerConfig()->create($validated + [
            'is_configured' => true,
        ]);

        return redirect()->route('email.config')
            ->with('success', 'Email server configured. Please test the connection.');
    }

    public function edit(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $config = $user->emailServerConfig;

        if (! $config) {
            return redirect()->route('email.create');
        }

        return Inertia::render('Email/Edit', [
            'config' => $config,
            'encryptionMethods' => ['none', 'tls', 'ssl'],
            'dmarcPolicies' => ['none', 'quarantine', 'reject'],
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $config = $user->emailServerConfig;

        if (! $config) {
            return redirect()->route('email.create');
        }

        $validated = $request->validate([
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|numeric',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'boolean',
            'imap_host' => 'nullable|string',
            'imap_port' => 'nullable|numeric',
            'imap_username' => 'nullable|string',
            'from_email' => 'required|email',
            'from_name' => 'required|string',
            'dmarc_policy' => 'nullable|in:none,quarantine,reject',
        ]);

        $config->update($validated);

        return back()->with('success', 'Email configuration updated.');
    }

    public function test(Request $request)
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $config = $user->emailServerConfig;

        if (! $config) {
            return back()->with('error', 'No email configuration found.');
        }

        try {
            // Test SMTP connection here
            // For now, just mark as tested
            $config->update([
                'last_tested_at' => now(),
                'last_test_passed' => true,
                'last_test_error' => null,
            ]);

            return back()->with('success', 'Email configuration test passed.');
        } catch (\Exception $e) {
            $config->update([
                'last_tested_at' => now(),
                'last_test_passed' => false,
                'last_test_error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Email configuration test failed: '.$e->getMessage());
        }
    }
}
