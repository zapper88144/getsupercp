import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router, Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { 
    PlusIcon, 
    MagnifyingGlassIcon, 
    TrashIcon, 
    ArrowPathIcon,
    ChevronLeftIcon,
    InformationCircleIcon,
    TagIcon,
    HashtagIcon,
    ClockIcon,
    ArrowsUpDownIcon
} from '@heroicons/react/24/outline';

interface DnsRecord {
    id: number;
    type: string;
    name: string;
    value: string;
    priority: number | null;
    ttl: number;
}

interface DnsZone {
    id: number;
    domain: string;
    dns_records: DnsRecord[];
}

interface Props {
    zone: DnsZone;
    availableTypes: string[];
}

export default function Show({ zone, availableTypes }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [showAddForm, setShowAddForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        type: 'A',
        name: '',
        value: '',
        priority: '',
        ttl: 3600,
    });

    const addRecord = (e: React.FormEvent) => {
        e.preventDefault();
        
        const newRecord = {
            type: data.type,
            name: data.name,
            value: data.value,
            priority: data.priority ? parseInt(data.priority as string) : null,
            ttl: data.ttl,
        };

        const updatedRecords = [...zone.dns_records, { ...newRecord, id: Date.now() }];
        
        saveRecords(updatedRecords);
    };

    const deleteRecord = (id: number) => {
        if (confirm('Are you sure you want to delete this record?')) {
            const updatedRecords = zone.dns_records.filter(r => r.id !== id);
            saveRecords(updatedRecords);
        }
    };

    const saveRecords = (updatedRecords: any[]) => {
        router.put(route('dns-zones.update-records', zone.id), {
            records: updatedRecords.map(r => ({
                type: r.type,
                name: r.name,
                value: r.value,
                priority: r.priority,
                ttl: r.ttl,
            }))
        }, {
            onSuccess: () => {
                reset();
                setShowAddForm(false);
            },
        });
    };

    const filteredRecords = useMemo(() => {
        return zone.dns_records.filter(record => 
            record.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            record.value.toLowerCase().includes(searchQuery.toLowerCase()) ||
            record.type.toLowerCase().includes(searchQuery.toLowerCase())
        );
    }, [zone.dns_records, searchQuery]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href={route('dns-zones.index')}
                            className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        >
                            <ChevronLeftIcon className="h-5 w-5" />
                        </Link>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            DNS Records: {zone.domain}
                        </h2>
                    </div>
                    <button
                        onClick={() => setShowAddForm(!showAddForm)}
                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <PlusIcon className="h-4 w-4" />
                        Add Record
                    </button>
                </div>
            }
        >
            <Head title={`DNS Records - ${zone.domain}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Add Record Form */}
                    {showAddForm && (
                        <div className="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Add New DNS Record</h3>
                            </div>
                            <form onSubmit={addRecord} className="p-6">
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-6">
                                    <div className="md:col-span-1">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                        <select
                                            value={data.type}
                                            onChange={(e) => setData('type', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            {availableTypes.map(type => (
                                                <option key={type} value={type}>{type}</option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="md:col-span-1">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="@ or sub"
                                        />
                                    </div>

                                    <div className="md:col-span-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Value</label>
                                        <input
                                            type="text"
                                            value={data.value}
                                            onChange={(e) => setData('value', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="IP or hostname"
                                            required
                                        />
                                    </div>

                                    <div className="md:col-span-1">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                                        <input
                                            type="number"
                                            value={data.priority}
                                            onChange={(e) => setData('priority', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white disabled:opacity-50"
                                            placeholder="MX only"
                                            disabled={data.type !== 'MX'}
                                        />
                                    </div>

                                    <div className="md:col-span-1">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">TTL</label>
                                        <input
                                            type="number"
                                            value={data.ttl}
                                            onChange={(e) => setData('ttl', parseInt(e.target.value))}
                                            className="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
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
                                        Add Record
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Records List */}
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                            <div className="relative max-w-md">
                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    className="block w-full rounded-lg border-gray-300 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Search records..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Type</th>
                                        <th className="px-6 py-4 font-medium">Name</th>
                                        <th className="px-6 py-4 font-medium">Value</th>
                                        <th className="px-6 py-4 font-medium">Priority</th>
                                        <th className="px-6 py-4 font-medium">TTL</th>
                                        <th className="px-6 py-4 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {filteredRecords.map((record) => (
                                        <tr key={record.id} className="group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td className="px-6 py-4">
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                                    {record.type}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 font-mono text-gray-900 dark:text-white">{record.name}</td>
                                            <td className="px-6 py-4 font-mono text-gray-600 dark:text-gray-400 break-all">{record.value}</td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{record.priority ?? '-'}</td>
                                            <td className="px-6 py-4 text-gray-500 dark:text-gray-400">{record.ttl}</td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    onClick={() => deleteRecord(record.id)}
                                                    className="rounded-lg p-2 text-gray-400 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-100 hover:text-rose-600 dark:hover:bg-gray-600 dark:hover:text-rose-400"
                                                    title="Delete Record"
                                                >
                                                    <TrashIcon className="h-5 w-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {filteredRecords.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-6 py-12 text-center">
                                                <InformationCircleIcon className="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">No records found</h3>
                                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    {searchQuery ? 'Try adjusting your search query.' : 'Get started by adding your first DNS record.'}
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
