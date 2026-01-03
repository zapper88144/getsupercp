import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router, Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { 
    PlusIcon, 
    MagnifyingGlassIcon, 
    GlobeAltIcon, 
    TrashIcon, 
    ChevronRightIcon,
    CheckCircleIcon,
    XCircleIcon,
    ServerIcon
} from '@heroicons/react/24/outline';

interface DnsZone {
    id: number;
    domain: string;
    status: string;
    dns_records_count: number;
}

interface Props {
    zones?: DnsZone[];
}

export default function Index({ zones = [] }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [showCreateForm, setShowCreateForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        domain: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('dns-zones.store'), {
            onSuccess: () => {
                reset();
                setShowCreateForm(false);
            },
        });
    };

    const deleteZone = (id: number) => {
        if (confirm('Are you sure you want to delete this DNS zone?')) {
            router.delete(route('dns-zones.destroy', id));
        }
    };

    const filteredZones = useMemo(() => {
        if (!Array.isArray(zones)) {
            return [];
        }
        return zones.filter(zone => 
            zone.domain.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [zones, searchQuery]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        DNS Management
                    </h2>
                    <button
                        onClick={() => setShowCreateForm(!showCreateForm)}
                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <PlusIcon className="h-4 w-4" />
                        Add DNS Zone
                    </button>
                </div>
            }
        >
            <Head title="DNS Management" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* DNS Summary Card */}
                    <div className="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/30">
                                    <GlobeAltIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Zones</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{zones.length}</p>
                                </div>
                            </div>
                        </div>
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-4">
                                <div className="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/30">
                                    <ServerIcon className="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Active Records</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {zones.reduce((acc, z) => acc + z.dns_records_count, 0)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Create Form */}
                    {showCreateForm && (
                        <div className="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Add New DNS Zone</h3>
                            </div>
                            <form onSubmit={submit} className="p-6">
                                <div className="max-w-xl">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Domain Name</label>
                                    <div className="mt-1 flex gap-3">
                                        <input
                                            type="text"
                                            value={data.domain}
                                            onChange={(e) => setData('domain', e.target.value)}
                                            className="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="example.com"
                                            required
                                        />
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        >
                                            {processing ? 'Adding...' : 'Add Zone'}
                                        </button>
                                    </div>
                                    {errors.domain && <p className="mt-2 text-sm text-red-600">{errors.domain}</p>}
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Zones List */}
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                            <div className="relative max-w-md">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    className="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Search DNS zones..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Domain</th>
                                        <th className="px-6 py-4 font-medium">Records</th>
                                        <th className="px-6 py-4 font-medium">Status</th>
                                        <th className="px-6 py-4 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {filteredZones.map((zone) => (
                                        <tr key={zone.id} className="group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                                                        <GlobeAltIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                                    </div>
                                                    <span className="font-medium text-gray-900 dark:text-white">{zone.domain}</span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                    {zone.dns_records_count} records
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                    zone.status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                                }`}>
                                                    {zone.status === 'active' ? <CheckCircleIcon className="h-3 w-3" /> : <XCircleIcon className="h-3 w-3" />}
                                                    {zone.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex justify-end gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                                                    <Link
                                                        href={route('dns-zones.show', zone.id)}
                                                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-indigo-600 dark:hover:bg-gray-600 dark:hover:text-indigo-400"
                                                        title="Manage Records"
                                                    >
                                                        <ChevronRightIcon className="h-5 w-5" />
                                                    </Link>
                                                    <button
                                                        onClick={() => deleteZone(zone.id)}
                                                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-rose-600 dark:hover:bg-gray-600 dark:hover:text-rose-400"
                                                        title="Delete Zone"
                                                    >
                                                        <TrashIcon className="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredZones.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-6 py-12 text-center">
                                                <GlobeAltIcon className="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">No DNS zones found</h3>
                                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    {searchQuery ? 'Try adjusting your search query.' : 'Get started by adding your first DNS zone.'}
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
