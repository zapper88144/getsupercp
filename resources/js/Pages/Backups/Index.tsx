import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { 
    CloudArrowDownIcon, 
    ArrowPathIcon, 
    TrashIcon, 
    PlusIcon, 
    MagnifyingGlassIcon,
    ArchiveBoxIcon,
    CircleStackIcon,
    GlobeAltIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ArrowDownTrayIcon
} from '@heroicons/react/24/outline';

interface Backup {
    id: number;
    name: string;
    type: string;
    source: string;
    path: string;
    size: number;
    status: string;
    created_at: string;
}

interface Domain {
    id: number;
    domain: string;
}

interface Database {
    id: number;
    name: string;
}

export default function Index({ backups, domains, databases }: { backups: Backup[], domains: Domain[], databases: Database[] }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [showCreateForm, setShowCreateForm] = useState(false);

    const { data, setData, post, delete: destroy, processing, reset } = useForm({
        type: 'web',
        source: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('backups.store'), {
            onSuccess: () => {
                reset();
                setShowCreateForm(false);
            },
        });
    };

    const formatSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const filteredBackups = useMemo(() => {
        return backups.filter(backup => 
            backup.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            backup.source.toLowerCase().includes(searchQuery.toLowerCase()) ||
            backup.type.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [backups, searchQuery]);

    const stats = useMemo(() => {
        const totalSize = backups.reduce((acc, b) => acc + b.size, 0);
        const webBackups = backups.filter(b => b.type === 'web').length;
        const dbBackups = backups.filter(b => b.type === 'database').length;
        return { totalSize, webBackups, dbBackups };
    }, [backups]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Backups
                    </h2>
                    <button
                        onClick={() => setShowCreateForm(!showCreateForm)}
                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <PlusIcon className="h-4 w-4" />
                        Create Backup
                    </button>
                </div>
            }
        >
            <Head title="Backups" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Stats Overview */}
                    <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/30">
                                    <ArchiveBoxIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Storage</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{formatSize(stats.totalSize)}</p>
                                </div>
                            </div>
                        </div>
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/30">
                                    <GlobeAltIcon className="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Web Backups</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.webBackups}</p>
                                </div>
                            </div>
                        </div>
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-amber-50 p-3 dark:bg-amber-900/30">
                                    <CircleStackIcon className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Database Backups</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.dbBackups}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Create Form */}
                    {showCreateForm && (
                        <div className="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Create New Backup</h3>
                            </div>
                            <form onSubmit={submit} className="p-6">
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Backup Type</label>
                                        <select
                                            value={data.type}
                                            onChange={(e) => {
                                                setData('type', e.target.value);
                                                setData('source', '');
                                            }}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="web">Web Files</option>
                                            <option value="database">Database</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Source</label>
                                        <select
                                            value={data.source}
                                            onChange={(e) => setData('source', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            required
                                        >
                                            <option value="">Select Source</option>
                                            {data.type === 'web' ? (
                                                domains.map((domain) => (
                                                    <option key={domain.id} value={domain.domain}>
                                                        {domain.domain}
                                                    </option>
                                                ))
                                            ) : (
                                                databases.map((db) => (
                                                    <option key={db.id} value={db.name}>
                                                        {db.name}
                                                    </option>
                                                ))
                                            )}
                                        </select>
                                    </div>
                                </div>
                                <div className="mt-6 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowCreateForm(false)}
                                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {processing ? (
                                            <ArrowPathIcon className="h-4 w-4 animate-spin" />
                                        ) : (
                                            <CloudArrowDownIcon className="h-4 w-4" />
                                        )}
                                        Start Backup
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Backups List */}
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                            <div className="relative max-w-md">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    className="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Search backups..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Backup Name</th>
                                        <th className="px-6 py-4 font-medium">Type</th>
                                        <th className="px-6 py-4 font-medium">Source</th>
                                        <th className="px-6 py-4 font-medium text-right">Size</th>
                                        <th className="px-6 py-4 font-medium">Status</th>
                                        <th className="px-6 py-4 font-medium">Created</th>
                                        <th className="px-6 py-4 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {filteredBackups.map((backup) => (
                                        <tr key={backup.id} className="group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                                                        <ArchiveBoxIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                                    </div>
                                                    <span className="font-medium text-gray-900 dark:text-white">{backup.name}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300 capitalize">
                                                    {backup.type === 'web' ? <GlobeAltIcon className="h-3 w-3" /> : <CircleStackIcon className="h-3 w-3" />}
                                                    {backup.type}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 dark:text-gray-400">{backup.source}</td>
                                            <td className="px-6 py-4 text-right font-mono text-gray-600 dark:text-gray-400">{formatSize(backup.size)}</td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                    backup.status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                                    backup.status === 'failed' ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400' :
                                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'
                                                }`}>
                                                    {backup.status === 'completed' && <CheckCircleIcon className="h-3 w-3" />}
                                                    {backup.status === 'failed' && <XCircleIcon className="h-3 w-3" />}
                                                    {backup.status === 'pending' && <ClockIcon className="h-3 w-3 animate-pulse" />}
                                                    {backup.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">
                                                {new Date(backup.created_at).toLocaleDateString()}
                                                <span className="ml-2 text-xs opacity-50">{new Date(backup.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                                    <a
                                                        href={route('backups.download', backup.id)}
                                                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-indigo-600 dark:hover:bg-gray-600 dark:hover:text-indigo-400"
                                                        title="Download"
                                                    >
                                                        <ArrowDownTrayIcon className="h-5 w-5" />
                                                    </a>
                                                    <button
                                                        onClick={() => {
                                                            if (confirm('Are you sure you want to restore this backup? This will overwrite current data.')) {
                                                                post(route('backups.restore', backup.id));
                                                            }
                                                        }}
                                                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-amber-600 dark:hover:bg-gray-600 dark:hover:text-amber-400"
                                                        title="Restore"
                                                    >
                                                        <ArrowPathIcon className="h-5 w-5" />
                                                    </button>
                                                    <button
                                                        onClick={() => {
                                                            if (confirm('Are you sure you want to delete this backup?')) {
                                                                destroy(route('backups.destroy', backup.id));
                                                            }
                                                        }}
                                                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-rose-600 dark:hover:bg-gray-600 dark:hover:text-rose-400"
                                                        title="Delete"
                                                    >
                                                        <TrashIcon className="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredBackups.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="px-6 py-12 text-center">
                                                <ArchiveBoxIcon className="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">No backups found</h3>
                                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    {searchQuery ? 'Try adjusting your search query.' : 'Get started by creating your first backup.'}
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
