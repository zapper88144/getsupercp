import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler } from 'react';
import { ShieldCheckIcon } from '@heroicons/react/24/outline';

interface Domain {
    id: number;
    domain: string;
}

interface Props {
    domains: Domain[];
}

export default function Create({ domains }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        web_domain_id: '',
        provider: 'letsencrypt',
        validation_method: 'dns',
        auto_renewal_enabled: true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('ssl.store'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Create SSL Certificate
                </h2>
            }
        >
            <Head title="Create SSL Certificate" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <form onSubmit={submit} className="space-y-6">
                            {/* Web Domain */}
                            <div>
                                <InputLabel htmlFor="web_domain_id" value="Web Domain" />
                                <select
                                    id="web_domain_id"
                                    value={data.web_domain_id}
                                    onChange={(e) => setData('web_domain_id', e.target.value)}
                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="">Select a domain...</option>
                                    {domains.map((domain) => (
                                        <option key={domain.id} value={domain.id}>
                                            {domain.domain}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.web_domain_id} className="mt-2" />
                            </div>

                            {/* Provider */}
                            <div>
                                <InputLabel htmlFor="provider" value="Certificate Provider" />
                                <select
                                    id="provider"
                                    value={data.provider}
                                    onChange={(e) => setData('provider', e.target.value)}
                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="letsencrypt">Let's Encrypt</option>
                                    <option value="custom">Custom</option>
                                </select>
                                <InputError message={errors.provider} className="mt-2" />
                            </div>

                            {/* Validation Method */}
                            <div>
                                <InputLabel htmlFor="validation_method" value="Validation Method" />
                                <select
                                    id="validation_method"
                                    value={data.validation_method}
                                    onChange={(e) => setData('validation_method', e.target.value)}
                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="dns">DNS</option>
                                    <option value="http">HTTP</option>
                                    <option value="tls-alpn">TLS-ALPN</option>
                                </select>
                                <InputError message={errors.validation_method} className="mt-2" />
                            </div>

                            {/* Auto Renewal */}
                            <div className="flex items-center gap-2">
                                <input
                                    id="auto_renewal_enabled"
                                    type="checkbox"
                                    checked={data.auto_renewal_enabled}
                                    onChange={(e) => setData('auto_renewal_enabled', e.target.checked)}
                                    className="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <InputLabel htmlFor="auto_renewal_enabled" value="Enable Auto-Renewal" />
                            </div>

                            {/* Actions */}
                            <div className="flex items-center gap-4">
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Creating...' : 'Create Certificate'}
                                </PrimaryButton>
                                <Link href={route('ssl.index')}>
                                    <SecondaryButton>Cancel</SecondaryButton>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
