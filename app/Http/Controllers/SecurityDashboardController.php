<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class SecurityDashboardController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();

        $recentLogs = AuditLog::byUser($user->id)
            ->recent(days: 30)
            ->latest()
            ->limit(50)
            ->get();

        $failedLogins = AuditLog::byUser($user->id)
            ->where('action', 'login')
            ->failures()
            ->recent(days: 7)
            ->count();

        $loginAttempts = AuditLog::byUser($user->id)
            ->where('action', 'login')
            ->recent(days: 7)
            ->count();

        $activityByDay = AuditLog::byUser($user->id)
            ->recent(days: 30)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        $twoFactorEnabled = $user->twoFactorAuthentication?->is_enabled ?? false;

        return Inertia::render('Security/Dashboard', [
            'recentLogs' => $recentLogs,
            'failedLogins' => $failedLogins,
            'loginAttempts' => $loginAttempts,
            'activityByDay' => $activityByDay,
            'twoFactorEnabled' => $twoFactorEnabled,
            'suspiciousActivity' => $failedLogins > 5,
        ]);
    }

    public function auditLogs(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $logs = AuditLog::byUser($user->id)
            ->latest()
            ->paginate(50);

        return Inertia::render('Security/AuditLogs', [
            'logs' => $logs,
        ]);
    }
}
