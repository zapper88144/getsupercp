<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactorService) {}

    public function show(Request $request): Response
    {
        $user = $request->user();
        $twoFactor = $user->twoFactorAuthentication;

        if ($twoFactor && $twoFactor->is_enabled) {
            return Inertia::render('Auth/TwoFactor/Status', [
                'isEnabled' => true,
            ]);
        }

        $secret = session('2fa_secret') ?? $this->twoFactorService->generateSecret();
        session(['2fa_secret' => $secret]);

        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        return Inertia::render('Auth/TwoFactor/Setup', [
            'secret' => $secret,
            'qrCodeSvg' => $this->twoFactorService->getQrCodeSvg($qrCodeUrl),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $secret = session('2fa_secret');

        if (! $secret || ! $this->twoFactorService->verifyCode($secret, $request->code)) {
            return back()->withErrors(['code' => 'The provided two-factor authentication code was invalid.']);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $this->twoFactorService->enable($request->user(), $secret, $recoveryCodes);

        AuditLog::log(
            action: '2fa_enabled',
            description: 'Two-factor authentication enabled.',
            result: 'success'
        );

        session()->forget('2fa_secret');
        session(['2fa_recovery_codes' => $recoveryCodes]);

        return redirect()->route('two-factor.recovery-codes');
    }

    public function recoveryCodes(Request $request): Response|RedirectResponse
    {
        $recoveryCodes = session('2fa_recovery_codes');

        if (! $recoveryCodes) {
            return redirect()->route('two-factor.setup');
        }

        return Inertia::render('Auth/TwoFactor/RecoveryCodes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $this->twoFactorService->disable($request->user());

        AuditLog::log(
            action: '2fa_disabled',
            description: 'Two-factor authentication disabled.',
            result: 'warning'
        );

        return back()->with('status', 'two-factor-authentication-disabled');
    }

    public function challenge(): Response
    {
        return Inertia::render('Auth/TwoFactor/Challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'nullable|string|size:6',
            'recovery_code' => 'nullable|string',
        ]);

        $user = $request->user();
        $twoFactor = $user->twoFactorAuthentication;

        if (! $twoFactor || ! $twoFactor->is_enabled) {
            return redirect()->route('dashboard');
        }

        if ($twoFactor->isLocked()) {
            return back()->withErrors(['code' => 'Too many failed attempts. Please try again later.']);
        }

        $verified = false;

        if ($request->filled('code')) {
            $verified = $this->twoFactorService->verifyCode($twoFactor->secret, $request->code);
        } elseif ($request->filled('recovery_code')) {
            $recoveryCodes = $twoFactor->recovery_codes;
            if (($key = array_search($request->recovery_code, $recoveryCodes)) !== false) {
                unset($recoveryCodes[$key]);
                $twoFactor->update(['recovery_codes' => array_values($recoveryCodes)]);
                $verified = true;
            }
        }

        if (! $verified) {
            $twoFactor->increment('failed_attempts');
            $twoFactor->update(['last_failed_at' => now()]);

            AuditLog::log(
                action: '2fa_failed',
                description: 'Two-factor authentication failed.',
                result: 'failed',
                changes: ['attempts' => $twoFactor->failed_attempts]
            );

            return back()->withErrors([
                'code' => $request->filled('code') ? 'The provided two-factor authentication code was invalid.' : null,
                'recovery_code' => $request->filled('recovery_code') ? 'The provided recovery code was invalid.' : null,
            ]);
        }

        $twoFactor->resetAttempts();
        session(['2fa_verified_at' => now()->timestamp]);

        AuditLog::log(
            action: '2fa_verified',
            description: 'Two-factor authentication verified.',
            result: 'success'
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
