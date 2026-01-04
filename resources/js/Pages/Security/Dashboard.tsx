import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    ShieldExclamationIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    ArrowTopRightOnSquareIcon,
    LockClosedIcon,
    UsersIcon
} from '@heroicons/react/24/outline';

interface SecurityMetric {
    failed_login_attempts_24h: number;
    failed_login_attempts_7d: number;
    active_sessions: number;
    two_fa_enabled_users: number;
    total_users: number;
    suspicious_ips: number;
    failed_api_requests_24h: number;
    last_security_audit: string;
}

interface Props {
    metrics: SecurityMetric;
}

export default function Dashboard({ metrics }: Props) {
    const twoFaPercentage = Math.round((metrics.two_fa_enabled_users / metrics.total_users) * 100);
    const failedLoginTrend = metrics.failed_login_attempts_24h > 5 ? 'warning' : 'ok';
    const suspiciousActivityLevel = metrics.suspicious_ips > 3 ? 'critical' : metrics.suspicious_ips > 0 ? 'warning' : 'ok';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Security Dashboard
                </h2>
            }
        >
            <Head title="Security Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Alert Banner */}
                    {(suspiciousActivityLevel !== 'ok' || failedLoginTrend !== 'ok') && (
                        <div className={`overflow-hidden shadow-sm sm:rounded-lg p-6 ${
                            suspiciousActivityLevel === 'critical'
                                ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
                                : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800'
                        }`}>
                            <div className="flex gap-3">
                                <ExclamationTriangleIcon className={`w-6 h-6 flex-shrink-0 ${
                                    suspiciousActivityLevel === 'critical' ? 'text-red-600' : 'text-yellow-600'
                                }`} />
                                <div>
                                    <h3 className={`font-semibold ${
                                        suspiciousActivityLevel === 'critical' 
                                            ? 'text-red-900 dark:text-red-200' 
                                            : 'text-yellow-900 dark:text-yellow-200'
                                    }`}>
                                        Security Alert
                                    </h3>
                                    <p className={`text-sm mt-1 ${
                                        suspiciousActivityLevel === 'critical'
                                            ? 'text-red-800 dark:text-red-300'
                                            : 'text-yellow-800 dark:text-yellow-300'
                                    }`}>
                                        {suspiciousActivityLevel === 'critical'
                                            ? `${metrics.suspicious_ips} suspicious IPs detected. Review activity logs immediately.`
                                            : `${metrics.failed_login_attempts_24h} failed login attempts in the last 24 hours.`
                                        }
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Key Metrics Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {/* Failed Login Attempts */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Failed Logins (24h)</p>
                                    <p className="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {metrics.failed_login_attempts_24h}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        {metrics.failed_login_attempts_7d} in last 7 days
                                    </p>
                                </div>
                                <ShieldExclamationIcon className={`w-12 h-12 ${
                                    failedLoginTrend === 'warning' ? 'text-yellow-600' : 'text-green-600'
                                }`} />
                            </div>
                        </div>

                        {/* Two-Factor Authentication */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">2FA Enabled Users</p>
                                    <p className="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {twoFaPercentage}%
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        {metrics.two_fa_enabled_users} of {metrics.total_users} users
                                    </p>
                                </div>
                                <CheckBadgeIcon className={`w-12 h-12 ${
                                    twoFaPercentage >= 80 ? 'text-green-600' : 'text-yellow-600'
                                }`} />
                            </div>
                        </div>

                        {/* Active Sessions */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Active Sessions</p>
                                    <p className="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {metrics.active_sessions}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        Currently online
                                    </p>
                                </div>
                                <UsersIcon className="w-12 h-12 text-blue-600" />
                            </div>
                        </div>

                        {/* Suspicious IPs */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Suspicious IPs</p>
                                    <p className="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {metrics.suspicious_ips}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        {suspiciousActivityLevel === 'ok' ? 'No threats detected' : 'Action required'}
                                    </p>
                                </div>
                                <ExclamationTriangleIcon className={`w-12 h-12 ${
                                    suspiciousActivityLevel === 'critical' ? 'text-red-600' : 
                                    suspiciousActivityLevel === 'warning' ? 'text-yellow-600' : 
                                    'text-green-600'
                                }`} />
                            </div>
                        </div>

                        {/* Failed API Requests */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Failed API Requests (24h)</p>
                                    <p className="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2">
                                        {metrics.failed_api_requests_24h}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        Monitor for anomalies
                                    </p>
                                </div>
                                <LockClosedIcon className="w-12 h-12 text-blue-600" />
                            </div>
                        </div>

                        {/* Last Security Audit */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Last Security Audit</p>
                                <p className="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-2">
                                    {new Date(metrics.last_security_audit).toLocaleDateString()}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {new Date(metrics.last_security_audit).toLocaleTimeString([], {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}
                                </p>
                                <Link href={route('security.audit-logs')}>
                                    <button className="mt-4 inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-semibold">
                                        View Audit Logs
                                        <ArrowTopRightOnSquareIcon className="w-4 h-4" />
                                    </button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Security Recommendations */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Security Recommendations
                        </h3>
                        <ul className="space-y-3">
                            {twoFaPercentage < 80 && (
                                <li className="flex items-start gap-3">
                                    <ExclamationTriangleIcon className="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p className="font-semibold text-gray-900 dark:text-gray-100">Enable Two-Factor Authentication</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            Only {twoFaPercentage}% of users have 2FA enabled. Encourage users to secure their accounts.
                                        </p>
                                    </div>
                                </li>
                            )}
                            {metrics.suspicious_ips > 0 && (
                                <li className="flex items-start gap-3">
                                    <ExclamationTriangleIcon className="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p className="font-semibold text-gray-900 dark:text-gray-100">Review Suspicious Activity</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {metrics.suspicious_ips} suspicious IP{metrics.suspicious_ips !== 1 ? 's have' : ' has'} been detected. Check audit logs for details.
                                        </p>
                                    </div>
                                </li>
                            )}
                            {metrics.failed_login_attempts_24h > 10 && (
                                <li className="flex items-start gap-3">
                                    <ExclamationTriangleIcon className="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p className="font-semibold text-gray-900 dark:text-gray-100">Consider Rate Limiting</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            High number of failed login attempts detected. Consider implementing stricter rate limiting.
                                        </p>
                                    </div>
                                </li>
                            )}
                            {twoFaPercentage >= 80 && metrics.suspicious_ips === 0 && metrics.failed_login_attempts_24h <= 5 && (
                                <li className="flex items-start gap-3">
                                    <CheckBadgeIcon className="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <p className="font-semibold text-gray-900 dark:text-gray-100">Security Status: Good</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            Your security metrics are looking good. Continue monitoring regularly.
                                        </p>
                                    </div>
                                </li>
                            )}
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
