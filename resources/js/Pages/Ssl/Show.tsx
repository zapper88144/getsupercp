import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { 
    ArrowPathIcon,
    TrashIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ClockIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';

interface Certificate {
    id: number;
    domain: string;
    provider: string;
    validation_method: string;
    issued_at: string | null;
    expires_at: string | null;
    auto_renewal_enabled: boolean;
    status: 'pending' | 'active' | 'expired' | 'failed';
    webDomain: {
        id: number;
        domain: string;
    };
}

interface Props {
    certificate: Certificate;
}

export default function Show({ certificate }: Props) {
    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <CheckCircleIcon className="w-6 h-6 text-green-600 dark:text-green-400" />;
            case 'expired':
                return <ExclamationTriangleIcon className="w-6 h-6 text-red-600 dark:text-red-400" />;
            case 'failed':
                return <XCircleIcon className="w-6 h-6 text-red-600 dark:text-red-400" />;
            case 'pending':
                return <ClockIcon className="w-6 h-6 text-yellow-600 dark:text-yellow-400" />;
            default:
                return null;
        }
    };

    const getStatusBadgeClass = (status: string) => {
        const baseClass = 'px-4 py-2 rounded-full text-sm font-medium inline-flex items-center gap-2';
        switch (status) {
            case 'active':
                return `${baseClass} bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200`;
            case 'expired':
                return `${baseClass} bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200`;
            case 'failed':
                return `${baseClass} bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200`;
            case 'pending':
                return `${baseClass} bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200`;
            default:
                return baseClass;
        }
    };

    const handleRenew = () => {
        if (confirm('Renew this SSL certificate?')) {
            router.post(route('ssl.renew', certificate.id));
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this certificate? This action cannot be undone.')) {
            router.delete(route('ssl.destroy', certificate.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('ssl.index')}>
                        <button className="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition">
                            <ArrowLeftIcon className="w-6 h-6 text-gray-600 dark:text-gray-400" />
                        </button>
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        {certificate.domain}
                    </h2>
                </div>
            }
        >
            <Head title={certificate.domain} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    {/* Status Card */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-8">
                        <div className="flex items-center gap-6 mb-8">
                            {getStatusIcon(certificate.status)}
                            <div>
                                <p className="text-gray-600 dark:text-gray-400 text-sm mb-2">Certificate Status</p>
                                <span className={getStatusBadgeClass(certificate.status)}>
                                    {certificate.status.charAt(0).toUpperCase() + certificate.status.slice(1)}
                                </span>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {/* Left Column */}
                            <div className="space-y-6">
                                <div>
                                    <p className="text-gray-600 dark:text-gray-400 text-sm mb-1">Domain Name</p>
                                    <p className="text-gray-900 dark:text-gray-100 font-semibold text-lg">
                                        {certificate.domain}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-gray-600 dark:text-gray-400 text-sm mb-1">Web Domain</p>
                                    <Link href={route('web-domains.index')}>
                                        <p className="text-blue-600 dark:text-blue-400 hover:underline">
                                            {certificate.webDomain.domain}
                                        </p>
                                    </Link>
                                </div>

                                <div>
                                    <p className="text-gray-600 dark:text-gray-400 text-sm mb-1">Provider</p>
                                    <p className="text-gray-900 dark:text-gray-100 capitalize font-semibold">
                                        {certificate.provider}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-gray-600 dark:text-gray-400 text-sm mb-1">Validation Method</p>
                                    <p className="text-gray-900 dark:text-gray-100 capitalize font-semibold">
                                        {certificate.validation_method.replace('-', ' ')}
                                    </p>
                                </div>
                            </div>

                            {/* Right Column */}
                            <div className="space-y-6">
                                {certificate.issued_at && (
                                    <div>
                                        <p className="text-gray-600 dark:text-gray-400 text-sm mb-1">Issued Date</p>
                                        <p className="text-gray-900 dark:text-gray-100 font-semibold">
                                            {new Date(certificate.issued_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric'
                                            })}
                                        </p>
                                    </div>
                                )}

                                {certificate.expires_at && (
                                    <div>
                                        <p className="text-gray-600 dark:text-gray-400 text-sm mb-1">Expiration Date</p>
                                        <p className="text-gray-900 dark:text-gray-100 font-semibold">
                                            {new Date(certificate.expires_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric'
                                            })}
                                        </p>
                                    </div>
                                )}

                                <div>
                                    <p className="text-gray-600 dark:text-gray-400 text-sm mb-1">Auto-Renewal</p>
                                    <div className="flex items-center gap-2">
                                        {certificate.auto_renewal_enabled ? (
                                            <>
                                                <CheckCircleIcon className="w-5 h-5 text-green-600 dark:text-green-400" />
                                                <p className="text-green-600 dark:text-green-400 font-semibold">Enabled</p>
                                            </>
                                        ) : (
                                            <p className="text-gray-600 dark:text-gray-400">Disabled</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex gap-4">
                            <button
                                onClick={handleRenew}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition"
                            >
                                <ArrowPathIcon className="w-5 h-5" />
                                Renew Certificate
                            </button>
                            <button
                                onClick={handleDelete}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition"
                            >
                                <TrashIcon className="w-5 h-5" />
                                Delete Certificate
                            </button>
                            <Link href={route('ssl.index')}>
                                <SecondaryButton>Back to List</SecondaryButton>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
