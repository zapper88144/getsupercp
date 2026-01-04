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
        certificate: null as File | null,
        private_key: null as File | null,
        ca_bundle: null as File | null,
        certificate_text: '',
        private_key_text: '',
        ca_bundle_text: '',
        input_type: 'file' as 'file' | 'text',
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
            breadcrumbs={[
                { title: 'SSL Certificates', url: route('ssl.index') },
                { title: 'Create' },
            ]}
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

                            {data.provider === 'letsencrypt' ? (
                                <>
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
                                </>
                            ) : (
                                <>
                                    {/* Input Type Selection */}
                                    <div>
                                        <InputLabel htmlFor="input_type" value="Input Method" />
                                        <div className="mt-2 flex gap-4">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="input_type"
                                                    value="file"
                                                    checked={data.input_type === 'file'}
                                                    onChange={() => setData('input_type', 'file')}
                                                    className="text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span className="text-sm text-gray-700 dark:text-gray-300">File Upload</span>
                                            </label>
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="input_type"
                                                    value="text"
                                                    checked={data.input_type === 'text'}
                                                    onChange={() => setData('input_type', 'text')}
                                                    className="text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span className="text-sm text-gray-700 dark:text-gray-300">Paste Text</span>
                                            </label>
                                        </div>
                                    </div>

                                    {data.input_type === 'file' ? (
                                        <>
                                            {/* Custom Certificate Upload */}
                                            <div>
                                                <InputLabel htmlFor="certificate" value="Certificate (.crt, .pem)" />
                                                <input
                                                    id="certificate"
                                                    type="file"
                                                    onChange={(e) => setData('certificate', e.target.files ? e.target.files[0] : null)}
                                                    className="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200"
                                                />
                                                <InputError message={errors.certificate} className="mt-2" />
                                            </div>

                                            <div>
                                                <InputLabel htmlFor="private_key" value="Private Key (.key, .pem)" />
                                                <input
                                                    id="private_key"
                                                    type="file"
                                                    onChange={(e) => setData('private_key', e.target.files ? e.target.files[0] : null)}
                                                    className="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200"
                                                />
                                                <InputError message={errors.private_key} className="mt-2" />
                                            </div>

                                            <div>
                                                <InputLabel htmlFor="ca_bundle" value="CA Bundle (Optional)" />
                                                <input
                                                    id="ca_bundle"
                                                    type="file"
                                                    onChange={(e) => setData('ca_bundle', e.target.files ? e.target.files[0] : null)}
                                                    className="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200"
                                                />
                                                <InputError message={errors.ca_bundle} className="mt-2" />
                                            </div>
                                        </>
                                    ) : (
                                        <>
                                            {/* Custom Certificate Text */}
                                            <div>
                                                <InputLabel htmlFor="certificate_text" value="Certificate Content" />
                                                <textarea
                                                    id="certificate_text"
                                                    value={data.certificate_text}
                                                    onChange={(e) => setData('certificate_text', e.target.value)}
                                                    rows={6}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-mono text-xs"
                                                    placeholder="-----BEGIN CERTIFICATE-----"
                                                />
                                                <InputError message={errors.certificate_text} className="mt-2" />
                                            </div>

                                            <div>
                                                <InputLabel htmlFor="private_key_text" value="Private Key Content" />
                                                <textarea
                                                    id="private_key_text"
                                                    value={data.private_key_text}
                                                    onChange={(e) => setData('private_key_text', e.target.value)}
                                                    rows={6}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-mono text-xs"
                                                    placeholder="-----BEGIN PRIVATE KEY-----"
                                                />
                                                <InputError message={errors.private_key_text} className="mt-2" />
                                            </div>

                                            <div>
                                                <InputLabel htmlFor="ca_bundle_text" value="CA Bundle Content (Optional)" />
                                                <textarea
                                                    id="ca_bundle_text"
                                                    value={data.ca_bundle_text}
                                                    onChange={(e) => setData('ca_bundle_text', e.target.value)}
                                                    rows={4}
                                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-mono text-xs"
                                                    placeholder="-----BEGIN CERTIFICATE-----"
                                                />
                                                <InputError message={errors.ca_bundle_text} className="mt-2" />
                                            </div>
                                        </>
                                    )}
                                </>
                            )}

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
