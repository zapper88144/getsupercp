import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState } from 'react';
import { 
    GlobeAltIcon, 
    ShieldCheckIcon, 
    ShieldExclamationIcon,
    TrashIcon,
    PowerIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    CommandLineIcon,
    FolderIcon
} from '@heroicons/react/24/outline';

interface WebDomain {
    id: number;
    domain: string;
    root_path: string;
    php_version: string;
    is_active: boolean;
    has_ssl: boolean;
}

interface Props {
    domains: WebDomain[];
}

export default function Index({ domains }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [isAdding, setIsAdding] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        domain: '',
        php_version: '8.4',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('web-domains.store'), {
            onSuccess: () => {
                reset();
                setIsAdding(false);
            },
        });
    };

    const toggleActive = (domain: WebDomain) => {
        router.patch(route('web-domains.update', domain.id), {
            php_version: domain.php_version,
            is_active: !domain.is_active,
        });
    };

    const toggleSsl = (id: number) => {
        router.post(route('web-domains.toggle-ssl', id));
    };

    const requestSsl = (id: number) => {
        router.post(route('web-domains.request-ssl', id));
    };

    const deleteDomain = (id: number) => {
        if (confirm('Are you sure you want to delete this domain?')) {
            router.delete(route('web-domains.destroy', id));
        }
    };

    const filteredDomains = domains.filter(d => 
        d.domain.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Web Domains
                    </h2>
                    <PrimaryButton 
                        onClick={() => setIsAdding(!isAdding)}
                        className="flex items-center gap-2"
                    >
                        {isAdding ? <XMarkIcon className="w-5 h-5" /> : <PlusIcon className="w-5 h-5" />}
                        {isAdding ? 'Cancel' : 'Add Domain'}
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Web Domains" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    
                    {isAdding && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800 border-l-4 border-indigo-500">
                            <section className="max-w-xl">
                                <header>
                                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        Add New Domain
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Create a new virtual host for your website.
                                    </p>
                                </header>

                                <form onSubmit={submit} className="mt-6 space-y-6">
                                    <div>
                                        <InputLabel htmlFor="domain" value="Domain Name" />
                                        <TextInput
                                            id="domain"
                                            className="mt-1 block w-full"
                                            value={data.domain}
                                            onChange={(e) => setData('domain', e.target.value)}
                                            required
                                            isFocused
                                            placeholder="example.com"
                                        />
                                        <InputError message={errors.domain} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="php_version" value="PHP Version" />
                                        <select
                                            id="php_version"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                            value={data.php_version}
                                            onChange={(e) => setData('php_version', e.target.value)}
                                        >
                                            <option value="8.1">8.1</option>
                                            <option value="8.2">8.2</option>
                                            <option value="8.3">8.3</option>
                                            <option value="8.4">8.4</option>
                                        </select>
                                        <InputError message={errors.php_version} className="mt-2" />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <PrimaryButton disabled={processing}>Create Domain</PrimaryButton>
                                    </div>
                                </form>
                            </section>
                        </div>
                    )}

                    <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Manage Domains
                                </h3>
                                <div className="relative w-full md:w-64">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <TextInput
                                        type="text"
                                        className="block w-full pl-10 pr-3 py-2"
                                        placeholder="Search domains..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-gray-700">
                                            <th className="py-3 px-4 font-semibold text-sm">Domain</th>
                                            <th className="py-3 px-4 font-semibold text-sm">Configuration</th>
                                            <th className="py-3 px-4 font-semibold text-sm">Security</th>
                                            <th className="py-3 px-4 font-semibold text-sm">Status</th>
                                            <th className="py-3 px-4 font-semibold text-sm text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredDomains.map((domain) => (
                                            <tr key={domain.id} className="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors">
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                                            <GlobeAltIcon className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                                        </div>
                                                        <div>
                                                            <div className="font-bold text-gray-900 dark:text-white">{domain.domain}</div>
                                                            <div className="text-xs text-gray-500 flex items-center gap-1">
                                                                <FolderIcon className="w-3 h-3" />
                                                                {domain.root_path}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-2 text-sm">
                                                        <CommandLineIcon className="w-4 h-4 text-gray-400" />
                                                        <span className="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">
                                                            PHP {domain.php_version}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-2">
                                                        {domain.has_ssl ? (
                                                            <span className="flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">
                                                                <ShieldCheckIcon className="w-4 h-4" />
                                                                SSL Active
                                                            </span>
                                                        ) : (
                                                            <span className="flex items-center gap-1 text-xs font-medium text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30 px-2 py-1 rounded-full">
                                                                <ShieldExclamationIcon className="w-4 h-4" />
                                                                No SSL
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${domain.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'}`}>
                                                        <span className={`h-1.5 w-1.5 rounded-full ${domain.is_active ? 'bg-green-600' : 'bg-red-600'}`}></span>
                                                        {domain.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="py-4 px-4 text-right">
                                                    <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        {!domain.has_ssl && (
                                                            <button 
                                                                onClick={() => requestSsl(domain.id)}
                                                                className="p-2 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-md text-indigo-600 dark:text-indigo-400"
                                                                title="Request SSL"
                                                            >
                                                                <ShieldCheckIcon className="w-5 h-5" />
                                                            </button>
                                                        )}
                                                        <button 
                                                            onClick={() => toggleSsl(domain.id)}
                                                            className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md text-gray-500"
                                                            title={domain.has_ssl ? 'Disable SSL' : 'Enable SSL'}
                                                        >
                                                            <ShieldExclamationIcon className="w-5 h-5" />
                                                        </button>
                                                        <button 
                                                            onClick={() => toggleActive(domain)}
                                                            className={`p-2 rounded-md ${domain.is_active ? 'hover:bg-yellow-100 dark:hover:bg-yellow-900/30 text-yellow-600' : 'hover:bg-green-100 dark:hover:bg-green-900/30 text-green-600'}`}
                                                            title={domain.is_active ? 'Deactivate' : 'Activate'}
                                                        >
                                                            <PowerIcon className="w-5 h-5" />
                                                        </button>
                                                        <button 
                                                            onClick={() => deleteDomain(domain.id)}
                                                            className="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md text-red-600"
                                                            title="Delete"
                                                        >
                                                            <TrashIcon className="w-5 h-5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                        {filteredDomains.length === 0 && (
                                            <tr>
                                                <td colSpan={5} className="py-12 text-center text-gray-500">
                                                    <div className="flex flex-col items-center gap-2">
                                                        <GlobeAltIcon className="w-12 h-12 text-gray-300" />
                                                        <span>{searchQuery ? `No domains matching "${searchQuery}"` : 'No domains found.'}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
