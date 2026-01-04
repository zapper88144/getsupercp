import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { 
    ShieldCheckIcon, 
    ShieldExclamationIcon, 
    PlusIcon, 
    TrashIcon, 
    PowerIcon,
    CheckCircleIcon,
    XCircleIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    GlobeAltIcon,
    HashtagIcon,
    ArrowsRightLeftIcon
} from '@heroicons/react/24/outline';

interface Rule {
    id: number;
    name: string;
    port: number;
    protocol: 'tcp' | 'udp';
    action: 'allow' | 'deny';
    source: string;
    is_active: boolean;
}

interface Props {
    rules: Rule[];
    status: {
        status: string;
        rules: any[];
    };
}

export default function Index({ rules, status }: Props) {
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const isFirewallActive = status?.status === 'active';

    const { data, setData, post, delete: destroy, processing, reset, errors } = useForm({
        name: '',
        port: '',
        protocol: 'tcp',
        action: 'allow',
        source: 'any',
    });

    const filteredRules = useMemo(() => {
        return rules.filter(rule => 
            rule.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            rule.port.toString().includes(searchQuery) ||
            rule.source.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [rules, searchQuery]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('firewall.store'), {
            onSuccess: () => {
                setShowCreateForm(false);
                reset();
            },
        });
    };

    const toggleRule = (rule: Rule) => {
        post(route('firewall.toggle', rule.id));
    };

    const toggleGlobal = () => {
        router.post(route('firewall.toggle-global'), {
            enable: !isFirewallActive
        });
    };

    const deleteRule = (rule: Rule) => {
        if (confirm('Are you sure you want to delete this rule?')) {
            destroy(route('firewall.destroy', rule.id));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Firewall Management
                    </h2>
                    <button
                        onClick={() => setShowCreateForm(!showCreateForm)}
                        className="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors duration-200"
                    >
                        {showCreateForm ? (
                            <>
                                <ChevronUpIcon className="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                                Hide Form
                            </>
                        ) : (
                            <>
                                <PlusIcon className="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
                                Add Rule
                            </>
                        )}
                    </button>
                </div>
            }
            breadcrumbs={[{ title: 'Firewall Management' }]}
        >
            <Head title="Firewall" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Global Status Card */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="flex flex-col sm:flex-row items-center justify-between gap-6">
                                <div className="flex items-center gap-4">
                                    <div className={`p-4 rounded-2xl ${isFirewallActive ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-red-100 dark:bg-red-900/30 text-red-600'}`}>
                                        {isFirewallActive ? <ShieldCheckIcon className="w-10 h-10" /> : <ShieldExclamationIcon className="w-10 h-10" />}
                                    </div>
                                    <div>
                                        <h3 className="text-xl font-bold text-gray-900 dark:text-gray-100">System Firewall</h3>
                                        <div className="flex items-center mt-1">
                                            <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                isFirewallActive 
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                                                : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                            }`}>
                                                <span className={`mr-1.5 h-2 w-2 rounded-full ${isFirewallActive ? 'bg-green-500' : 'bg-red-500'}`}></span>
                                                {isFirewallActive ? 'Active & Protecting' : 'Disabled & Vulnerable'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    onClick={toggleGlobal}
                                    className={`inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-sm transition-all duration-200 shadow-sm ${
                                        isFirewallActive 
                                        ? 'bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30 border border-red-200 dark:border-red-800' 
                                        : 'bg-green-600 text-white hover:bg-green-700 shadow-green-200 dark:shadow-none'
                                    }`}
                                >
                                    <PowerIcon className="w-5 h-5" />
                                    {isFirewallActive ? 'Disable Firewall' : 'Enable Firewall'}
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Create Form */}
                    {showCreateForm && (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 border border-gray-200 dark:border-gray-700 transition-all duration-300 ease-in-out">
                            <div className="p-6 text-gray-900 dark:text-gray-100">
                                <header className="mb-6">
                                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                        <PlusIcon className="mr-2 h-5 w-5 text-indigo-500" />
                                        Add Firewall Rule
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Define a new rule to control network traffic to your server.
                                    </p>
                                </header>

                                <form onSubmit={submit} className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <div className="md:col-span-2">
                                        <InputLabel htmlFor="name" value="Rule Name" />
                                        <TextInput
                                            id="name"
                                            className="mt-1 block w-full"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                            placeholder="e.g. Web Server (HTTP)"
                                            required
                                        />
                                        <InputError message={errors.name} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="port" value="Port" />
                                        <div className="mt-1 flex rounded-md shadow-sm">
                                            <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                <HashtagIcon className="h-4 w-4" />
                                            </span>
                                            <TextInput
                                                id="port"
                                                type="number"
                                                className="block w-full rounded-none rounded-r-md"
                                                value={data.port}
                                                onChange={e => setData('port', e.target.value)}
                                                placeholder="80"
                                                required
                                            />
                                        </div>
                                        <InputError message={errors.port} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="protocol" value="Protocol" />
                                        <select
                                            id="protocol"
                                            value={data.protocol}
                                            onChange={e => setData('protocol', e.target.value as any)}
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        >
                                            <option value="tcp">TCP</option>
                                            <option value="udp">UDP</option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="action" value="Action" />
                                        <select
                                            id="action"
                                            value={data.action}
                                            onChange={e => setData('action', e.target.value as any)}
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        >
                                            <option value="allow">Allow</option>
                                            <option value="deny">Deny</option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="source" value="Source IP" />
                                        <div className="mt-1 flex rounded-md shadow-sm">
                                            <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                <GlobeAltIcon className="h-4 w-4" />
                                            </span>
                                            <TextInput
                                                id="source"
                                                className="block w-full rounded-none rounded-r-md"
                                                value={data.source}
                                                onChange={e => setData('source', e.target.value)}
                                                placeholder="any or 1.2.3.4"
                                                required
                                            />
                                        </div>
                                        <InputError message={errors.source} className="mt-2" />
                                    </div>

                                    <div className="md:col-span-3 flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                        <button
                                            type="button"
                                            onClick={() => setShowCreateForm(false)}
                                            className="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                                        >
                                            Cancel
                                        </button>
                                        <PrimaryButton disabled={processing}>
                                            {processing ? 'Adding...' : 'Add Firewall Rule'}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* Rules List */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Firewall Rules
                                </h3>
                                <div className="relative max-w-sm w-full">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                                    </div>
                                    <input
                                        type="text"
                                        className="block w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-white dark:ring-gray-600"
                                        placeholder="Search rules, ports, or IPs..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                    <thead className="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                        <tr>
                                            <th className="px-6 py-3 font-semibold">Rule Name</th>
                                            <th className="px-6 py-3 font-semibold">Port / Protocol</th>
                                            <th className="px-6 py-3 font-semibold">Action</th>
                                            <th className="px-6 py-3 font-semibold">Source</th>
                                            <th className="px-6 py-3 font-semibold">Status</th>
                                            <th className="px-6 py-3 text-right font-semibold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {filteredRules.map((rule) => (
                                            <tr 
                                                key={rule.id} 
                                                className="group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150"
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {rule.name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-700 dark:text-gray-300">
                                                            {rule.port}
                                                        </span>
                                                        <span className="text-xs uppercase text-gray-500 dark:text-gray-400 font-bold">
                                                            {rule.protocol}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                        rule.action === 'allow' 
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                                    }`}>
                                                        {rule.action === 'allow' ? (
                                                            <CheckCircleIcon className="mr-1 h-3 w-3" />
                                                        ) : (
                                                            <XCircleIcon className="mr-1 h-3 w-3" />
                                                        )}
                                                        {rule.action.toUpperCase()}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center text-gray-600 dark:text-gray-400">
                                                        <GlobeAltIcon className="mr-1.5 h-4 w-4 opacity-50" />
                                                        {rule.source}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <button
                                                        onClick={() => toggleRule(rule)}
                                                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors duration-200 ${
                                                            rule.is_active 
                                                            ? 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50' 
                                                            : 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600'
                                                        }`}
                                                    >
                                                        <ArrowsRightLeftIcon className="mr-1 h-3 w-3" />
                                                        {rule.is_active ? 'Active' : 'Inactive'}
                                                    </button>
                                                </td>
                                                <td className="px-6 py-4 text-right whitespace-nowrap">
                                                    <div className="flex justify-end opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                        <button
                                                            onClick={() => deleteRule(rule)}
                                                            className="inline-flex items-center rounded-md p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors duration-200"
                                                            title="Delete Rule"
                                                        >
                                                            <TrashIcon className="h-5 w-5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                        {filteredRules.length === 0 && (
                                            <tr>
                                                <td colSpan={6} className="px-6 py-12 text-center">
                                                    <div className="flex flex-col items-center justify-center">
                                                        <ShieldExclamationIcon className="h-12 w-12 text-gray-300 dark:text-gray-600" />
                                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                            {searchQuery ? 'No rules match your search.' : 'No firewall rules found. Add one to secure your server.'}
                                                        </p>
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
