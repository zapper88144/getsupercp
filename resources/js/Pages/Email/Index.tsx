import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { 
    PlusIcon, 
    MagnifyingGlassIcon, 
    TrashIcon, 
    ArrowPathIcon,
    EnvelopeIcon,
    ShieldCheckIcon,
    CircleStackIcon,
    InformationCircleIcon,
    KeyIcon,
    ChartBarIcon
} from '@heroicons/react/24/outline';

interface EmailAccount {
    id: number;
    email: string;
    quota_mb: number;
    status: string;
    created_at: string;
}

interface Props {
    emailAccounts: EmailAccount[];
}

export default function Index({ emailAccounts }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [showAddForm, setShowAddForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        quota_mb: 1024,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('email-accounts.store'), {
            onSuccess: () => {
                reset();
                setShowAddForm(false);
            },
        });
    };

    const deleteAccount = (id: number) => {
        if (confirm('Are you sure you want to delete this email account?')) {
            router.delete(route('email-accounts.destroy', id));
        }
    };

    const filteredAccounts = useMemo(() => {
        return emailAccounts.filter(account => 
            account.email.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [emailAccounts, searchQuery]);

    const stats = useMemo(() => {
        const total = emailAccounts.length;
        const active = emailAccounts.filter(a => a.status === 'active').length;
        const totalQuota = emailAccounts.reduce((acc, a) => acc + a.quota_mb, 0);
        
        return { total, active, totalQuota };
    }, [emailAccounts]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Email Accounts
                    </h2>
                    <button
                        onClick={() => setShowAddForm(!showAddForm)}
                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <PlusIcon className="h-4 w-4" />
                        Create Account
                    </button>
                </div>
            }
        >
            <Head title="Email Accounts" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Stats Grid */}
                    <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/30">
                                    <EnvelopeIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Accounts</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.total}</p>
                                </div>
                            </div>
                        </div>
                        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/30">
                                    <ShieldCheckIcon className="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Active Status</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.active}</p>
                                </div>
                            </div>
                        </div>
                        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/30">
                                    <CircleStackIcon className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Allocated Quota</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{(stats.totalQuota / 1024).toFixed(1)} GB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Create Account Form */}
                    {showAddForm && (
                        <div className="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Create New Email Account</h3>
                            </div>
                            <form onSubmit={submit} className="p-6">
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                                        <div className="mt-1 relative">
                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <EnvelopeIcon className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input
                                                type="email"
                                                value={data.email}
                                                onChange={(e) => setData('email', e.target.value)}
                                                className="block w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                placeholder="user@example.com"
                                                required
                                            />
                                        </div>
                                        {errors.email && <p className="mt-1 text-sm text-rose-600">{errors.email}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                        <div className="mt-1 relative">
                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <KeyIcon className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input
                                                type="password"
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                className="block w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                placeholder="********"
                                                required
                                            />
                                        </div>
                                        {errors.password && <p className="mt-1 text-sm text-rose-600">{errors.password}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Quota (MB)</label>
                                        <div className="mt-1 relative">
                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <ChartBarIcon className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <input
                                                type="number"
                                                value={data.quota_mb}
                                                onChange={(e) => setData('quota_mb', parseInt(e.target.value))}
                                                className="block w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                min="1"
                                                required
                                            />
                                        </div>
                                        {errors.quota_mb && <p className="mt-1 text-sm text-rose-600">{errors.quota_mb}</p>}
                                    </div>
                                </div>
                                <div className="mt-6 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowAddForm(false)}
                                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {processing ? <ArrowPathIcon className="h-4 w-4 animate-spin" /> : <PlusIcon className="h-4 w-4" />}
                                        Create Account
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Accounts List */}
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                            <div className="relative max-w-md">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    className="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Search email accounts..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Email Address</th>
                                        <th className="px-6 py-4 font-medium">Quota</th>
                                        <th className="px-6 py-4 font-medium">Status</th>
                                        <th className="px-6 py-4 font-medium">Created At</th>
                                        <th className="px-6 py-4 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {filteredAccounts.map((account) => (
                                        <tr key={account.id} className="group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="rounded-full bg-indigo-100 p-2 dark:bg-indigo-900/30">
                                                        <EnvelopeIcon className="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                                                    </div>
                                                    <span className="font-medium text-gray-900 dark:text-white">{account.email}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-400">
                                                {account.quota_mb} MB
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium ${
                                                    account.status === 'active' 
                                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' 
                                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                }`}>
                                                    <span className={`h-1.5 w-1.5 rounded-full ${account.status === 'active' ? 'bg-emerald-600' : 'bg-rose-600'}`} />
                                                    {account.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">
                                                {new Date(account.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    onClick={() => deleteAccount(account.id)}
                                                    className="rounded-lg p-2 text-gray-400 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-100 hover:text-rose-600 dark:hover:bg-gray-600 dark:hover:text-rose-400"
                                                    title="Delete Account"
                                                >
                                                    <TrashIcon className="h-5 w-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredAccounts.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-6 py-12 text-center">
                                                <InformationCircleIcon className="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">No email accounts found</h3>
                                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    {searchQuery ? 'Try adjusting your search query.' : 'Get started by creating your first email account.'}
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
