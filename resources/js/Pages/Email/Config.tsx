import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState } from 'react';
import {
    EnvelopeIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    SparklesIcon
} from '@heroicons/react/24/outline';

interface EmailConfig {
    id: number;
    smtp_host: string;
    smtp_port: number;
    smtp_username: string;
    imap_host: string;
    imap_port: number;
    imap_username: string;
    encryption: string;
    is_configured: boolean;
    is_healthy: boolean;
    last_test_at: string;
    last_test_status: string;
}

interface Props {
    config: EmailConfig | null;
}

export default function Config({ config }: Props) {
    const [isEditing, setIsEditing] = useState(false);
    const [testingConnection, setTestingConnection] = useState(false);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        smtp_host: config?.smtp_host || '',
        smtp_port: config?.smtp_port.toString() || '587',
        smtp_username: config?.smtp_username || '',
        smtp_password: '',
        imap_host: config?.imap_host || '',
        imap_port: config?.imap_port.toString() || '993',
        imap_username: config?.imap_username || '',
        imap_password: '',
        encryption: config?.encryption || 'TLS',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (config?.id) {
            patch(route('email.update', config.id), {
                onSuccess: () => {
                    reset();
                    setIsEditing(false);
                },
            });
        } else {
            post(route('email.store'), {
                onSuccess: () => {
                    reset();
                    setIsEditing(false);
                },
            });
        }
    };

    const handleTestConnection = async () => {
        setTestingConnection(true);
        // The actual test is handled by the server
        post(route('email.test'), {
            onFinish: () => setTestingConnection(false),
        });
    };

    if (!config?.is_configured && !isEditing) {
        return (
            <AuthenticatedLayout
                header={
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Email Server Configuration
                    </h2>
                }
                breadcrumbs={[{ title: 'Email Configuration' }]}
            >
                <Head title="Email Configuration" />

                <div className="py-12">
                    <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                        <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <EnvelopeIcon className="w-16 h-16 mx-auto text-yellow-600 mb-4" />
                            <h3 className="text-lg font-semibold text-yellow-900 dark:text-yellow-200 mb-2">
                                Email Server Not Configured
                            </h3>
                            <p className="text-yellow-800 dark:text-yellow-300 mb-6">
                                Set up your SMTP and IMAP configuration to enable email functionality.
                            </p>
                            <PrimaryButton onClick={() => setIsEditing(true)}>
                                Configure Email Server
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Email Server Configuration
                    </h2>
                    {!isEditing && (
                        <div className="flex gap-2">
                            <SecondaryButton onClick={handleTestConnection} disabled={testingConnection || !config?.is_configured}>
                                {testingConnection ? 'Testing...' : 'Test Connection'}
                            </SecondaryButton>
                            <PrimaryButton onClick={() => setIsEditing(true)}>
                                Edit Configuration
                            </PrimaryButton>
                        </div>
                    )}
                </div>
            }
            breadcrumbs={[{ title: 'Email Configuration' }]}
        >
            <Head title="Email Configuration" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                    {/* Configuration Form */}
                    {isEditing && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <form onSubmit={submit} className="space-y-6">
                                {/* SMTP Configuration */}
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                        SMTP Configuration
                                    </h3>
                                    <div className="space-y-4">
                                        <div>
                                            <InputLabel htmlFor="smtp_host" value="SMTP Host" />
                                            <TextInput
                                                id="smtp_host"
                                                type="text"
                                                value={data.smtp_host}
                                                onChange={(e) => setData('smtp_host', e.target.value)}
                                                placeholder="mail.example.com"
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={errors.smtp_host} className="mt-2" />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel htmlFor="smtp_port" value="SMTP Port" />
                                                <TextInput
                                                    id="smtp_port"
                                                    type="number"
                                                    value={data.smtp_port}
                                                    onChange={(e) => setData('smtp_port', e.target.value)}
                                                    placeholder="587"
                                                    className="mt-1 block w-full"
                                                />
                                                <InputError message={errors.smtp_port} className="mt-2" />
                                            </div>

                                            <div>
                                                <InputLabel htmlFor="encryption" value="Encryption" />
                                                <select
                                                    id="encryption"
                                                    value={data.encryption}
                                                    onChange={(e) => setData('encryption', e.target.value)}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                >
                                                    <option value="NONE">None</option>
                                                    <option value="TLS">TLS</option>
                                                    <option value="SSL">SSL/TLS</option>
                                                </select>
                                                <InputError message={errors.encryption} className="mt-2" />
                                            </div>
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="smtp_username" value="SMTP Username" />
                                            <TextInput
                                                id="smtp_username"
                                                type="text"
                                                value={data.smtp_username}
                                                onChange={(e) => setData('smtp_username', e.target.value)}
                                                placeholder="noreply@example.com"
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={errors.smtp_username} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="smtp_password" value="SMTP Password" />
                                            <TextInput
                                                id="smtp_password"
                                                type="password"
                                                value={data.smtp_password}
                                                onChange={(e) => setData('smtp_password', e.target.value)}
                                                placeholder={config?.is_configured ? '••••••••' : 'Enter password'}
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={errors.smtp_password} className="mt-2" />
                                            {config?.is_configured && !data.smtp_password && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                    Leave empty to keep existing password
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* IMAP Configuration */}
                                <div className="border-t dark:border-gray-700 pt-6">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                        IMAP Configuration
                                    </h3>
                                    <div className="space-y-4">
                                        <div>
                                            <InputLabel htmlFor="imap_host" value="IMAP Host" />
                                            <TextInput
                                                id="imap_host"
                                                type="text"
                                                value={data.imap_host}
                                                onChange={(e) => setData('imap_host', e.target.value)}
                                                placeholder="mail.example.com"
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={errors.imap_host} className="mt-2" />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel htmlFor="imap_port" value="IMAP Port" />
                                                <TextInput
                                                    id="imap_port"
                                                    type="number"
                                                    value={data.imap_port}
                                                    onChange={(e) => setData('imap_port', e.target.value)}
                                                    placeholder="993"
                                                    className="mt-1 block w-full"
                                                />
                                                <InputError message={errors.imap_port} className="mt-2" />
                                            </div>
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="imap_username" value="IMAP Username" />
                                            <TextInput
                                                id="imap_username"
                                                type="text"
                                                value={data.imap_username}
                                                onChange={(e) => setData('imap_username', e.target.value)}
                                                placeholder="noreply@example.com"
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={errors.imap_username} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="imap_password" value="IMAP Password" />
                                            <TextInput
                                                id="imap_password"
                                                type="password"
                                                value={data.imap_password}
                                                onChange={(e) => setData('imap_password', e.target.value)}
                                                placeholder={config?.is_configured ? '••••••••' : 'Enter password'}
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={errors.imap_password} className="mt-2" />
                                            {config?.is_configured && !data.imap_password && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                    Leave empty to keep existing password
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Form Actions */}
                                <div className="flex gap-4 pt-4 border-t dark:border-gray-700">
                                    <PrimaryButton disabled={processing}>
                                        {processing ? (config?.id ? 'Updating...' : 'Saving...') : (config?.id ? 'Update Configuration' : 'Save Configuration')}
                                    </PrimaryButton>
                                    <SecondaryButton onClick={() => {
                                        setIsEditing(false);
                                        reset();
                                    }}>
                                        Cancel
                                    </SecondaryButton>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Configuration Display */}
                    {!isEditing && config?.is_configured && (
                        <div className="space-y-4">
                            {/* Health Status */}
                            <div className={`overflow-hidden shadow-sm sm:rounded-lg p-6 ${
                                config.is_healthy
                                    ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
                                    : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
                            }`}>
                                <div className="flex items-center gap-3">
                                    {config.is_healthy ? (
                                        <CheckCircleIcon className="w-6 h-6 text-green-600" />
                                    ) : (
                                        <ExclamationTriangleIcon className="w-6 h-6 text-red-600" />
                                    )}
                                    <div>
                                        <h3 className={`font-semibold ${
                                            config.is_healthy
                                                ? 'text-green-900 dark:text-green-200'
                                                : 'text-red-900 dark:text-red-200'
                                        }`}>
                                            {config.is_healthy ? 'Connection Healthy' : 'Connection Issue'}
                                        </h3>
                                        <p className={`text-sm mt-1 ${
                                            config.is_healthy
                                                ? 'text-green-800 dark:text-green-300'
                                                : 'text-red-800 dark:text-red-300'
                                        }`}>
                                            Last test: {config.last_test_at ? new Date(config.last_test_at).toLocaleString() : 'Never'}
                                        </p>
                                        {config.last_test_status && (
                                            <p className={`text-sm mt-1 ${
                                                config.is_healthy
                                                    ? 'text-green-800 dark:text-green-300'
                                                    : 'text-red-800 dark:text-red-300'
                                            }`}>
                                                Status: {config.last_test_status}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Configuration Details */}
                            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    SMTP Configuration
                                </h3>
                                <dl className="space-y-3">
                                    <div className="flex justify-between">
                                        <dt className="text-gray-600 dark:text-gray-400">Host:</dt>
                                        <dd className="font-mono text-gray-900 dark:text-gray-100">{config.smtp_host}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-gray-600 dark:text-gray-400">Port:</dt>
                                        <dd className="font-mono text-gray-900 dark:text-gray-100">{config.smtp_port}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-gray-600 dark:text-gray-400">Username:</dt>
                                        <dd className="font-mono text-gray-900 dark:text-gray-100">{config.smtp_username}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-gray-600 dark:text-gray-400">Encryption:</dt>
                                        <dd className="font-mono text-gray-900 dark:text-gray-100">{config.encryption}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    IMAP Configuration
                                </h3>
                                <dl className="space-y-3">
                                    <div className="flex justify-between">
                                        <dt className="text-gray-600 dark:text-gray-400">Host:</dt>
                                        <dd className="font-mono text-gray-900 dark:text-gray-100">{config.imap_host}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-gray-600 dark:text-gray-400">Port:</dt>
                                        <dd className="font-mono text-gray-900 dark:text-gray-100">{config.imap_port}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-gray-600 dark:text-gray-400">Username:</dt>
                                        <dd className="font-mono text-gray-900 dark:text-gray-100">{config.imap_username}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
