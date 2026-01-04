import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useState } from 'react';
import { 
    ShieldCheckIcon,
    ShieldExclamationIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    TrashIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    XMarkIcon
} from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';

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
    certificates: Certificate[];
}

export default function Index({ certificates }: Props) {
    const [searchQuery, setSearchQuery] = useState('');

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <CheckCircleIcon className="w-5 h-5 text-green-600 dark:text-green-400" />;
            case 'expired':
                return <ExclamationTriangleIcon className="w-5 h-5 text-red-600 dark:text-red-400" />;
            case 'failed':
                return <XCircleIcon className="w-5 h-5 text-red-600 dark:text-red-400" />;
            case 'pending':
                return <ClockIcon className="w-5 h-5 text-yellow-600 dark:text-yellow-400" />;
            default:
                return null;
        }
    };

    const getStatusBadgeClass = (status: string) => {
        const baseClass = 'px-3 py-1 rounded-full text-sm font-medium flex items-center gap-2';
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

    const handleRenew = (id: number) => {
        if (confirm('Renew this SSL certificate?')) {
            router.post(route('ssl.renew', id));
        }
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this certificate? This action cannot be undone.')) {
            router.delete(route('ssl.destroy', id));
        }
    };

    const filteredCertificates = certificates.filter(cert =>
        cert.domain.toLowerCase().includes(searchQuery.toLowerCase()) ||
        cert.webDomain.domain.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        SSL Certificates
                    </h2>
                    <Link href={route('ssl.create')}>
                        <PrimaryButton className="flex items-center gap-2">
                            <PlusIcon className="w-5 h-5" />
                            New Certificate
                        </PrimaryButton>
                    </Link>
                </div>
            }
        >
            <Head title="SSL Certificates" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Search Bar */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-2">
                            <MagnifyingGlassIcon className="w-5 h-5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Search certificates..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="flex-1 bg-transparent border-0 outline-none text-gray-900 dark:text-gray-100 placeholder-gray-500"
                            />
                        </div>
                    </div>

                    {/* Certificates List */}
                    {filteredCertificates.length > 0 ? (
                        <div className="space-y-4">
                            {filteredCertificates.map((cert) => (
                                <div
                                    key={cert.id}
                                    className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                                >
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                        {/* Certificate Info */}
                                        <div className="space-y-2">
                                            <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                                                {cert.domain}
                                            </h3>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Domain: {cert.webDomain.domain}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Provider: <span className="capitalize">{cert.provider}</span>
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Validation: <span className="capitalize">{cert.validation_method.replace('-', ' ')}</span>
                                            </p>
                                        </div>

                                        {/* Status and Dates */}
                                        <div className="space-y-2">
                                            <div>
                                                <span className={getStatusBadgeClass(cert.status)}>
                                                    {getStatusIcon(cert.status)}
                                                    {cert.status.charAt(0).toUpperCase() + cert.status.slice(1)}
                                                </span>
                                            </div>
                                            {cert.issued_at && (
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    Issued: {new Date(cert.issued_at).toLocaleDateString()}
                                                </p>
                                            )}
                                            {cert.expires_at && (
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    Expires: {new Date(cert.expires_at).toLocaleDateString()}
                                                </p>
                                            )}
                                            {cert.auto_renewal_enabled && (
                                                <p className="text-sm text-green-600 dark:text-green-400 flex items-center gap-1">
                                                    <CheckCircleIcon className="w-4 h-4" />
                                                    Auto-renewal enabled
                                                </p>
                                            )}
                                        </div>

                                        {/* Actions */}
                                        <div className="flex gap-2 justify-end md:flex-col md:items-end">
                                            <Link href={route('ssl.show', cert.id)}>
                                                <SecondaryButton className="flex items-center gap-2">
                                                    <EyeIcon className="w-4 h-4" />
                                                    <span className="hidden md:inline">View</span>
                                                </SecondaryButton>
                                            </Link>
                                            <button
                                                onClick={() => handleRenew(cert.id)}
                                                className="inline-flex items-center gap-2 justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition"
                                            >
                                                <ArrowPathIcon className="w-4 h-4" />
                                                <span className="hidden md:inline">Renew</span>
                                            </button>
                                            <button
                                                onClick={() => handleDelete(cert.id)}
                                                className="inline-flex items-center gap-2 justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                                <span className="hidden md:inline">Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <ShieldCheckIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                {searchQuery ? 'No certificates match your search.' : 'No SSL certificates yet.'}
                            </p>
                            {!searchQuery && (
                                <Link href={route('ssl.create')}>
                                    <PrimaryButton>Create Your First Certificate</PrimaryButton>
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
