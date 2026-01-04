import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState } from 'react';
import { 
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    TrashIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    PowerIcon
} from '@heroicons/react/24/outline';

interface BackupSchedule {
    id: number;
    name: string;
    frequency: string;
    time: string;
    backup_type: string;
    retention_days: number;
    compress: boolean;
    encrypt: boolean;
    is_enabled: boolean;
    next_run_in: string;
    success_rate: number;
}

interface Props {
    schedules: BackupSchedule[];
}

export default function Schedules({ schedules }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [isAdding, setIsAdding] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const { data, setData, post, patch, delete: destroy, processing, errors, reset } = useForm({
        name: '',
        frequency: 'daily',
        time: '00:00',
        backup_type: 'full',
        retention_days: '30',
        compress: true,
        encrypt: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('backups.schedules.store'), {
            onSuccess: () => {
                reset();
                setIsAdding(false);
            },
        });
    };

    const toggleSchedule = (schedule: BackupSchedule) => {
        router.post(route('backups.schedules.toggle', schedule.id));
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this schedule?')) {
            router.delete(route('backups.schedules.destroy', id));
        }
    };

    const filteredSchedules = schedules.filter(s =>
        s.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Backup Schedules
                    </h2>
                    <PrimaryButton 
                        onClick={() => setIsAdding(!isAdding)}
                        className="flex items-center gap-2"
                    >
                        {isAdding ? '✕' : <PlusIcon className="w-5 h-5" />}
                        {isAdding ? 'Cancel' : 'New Schedule'}
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Backup Schedules" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Search Bar */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-2">
                            <MagnifyingGlassIcon className="w-5 h-5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Search schedules..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="flex-1 bg-transparent border-0 outline-none text-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    {/* Create Form */}
                    {isAdding && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="name" value="Schedule Name" />
                                        <TextInput
                                            id="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="e.g., Daily Website Backup"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.name} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="frequency" value="Frequency" />
                                        <select
                                            id="frequency"
                                            value={data.frequency}
                                            onChange={(e) => setData('frequency', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                        >
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="time" value="Time" />
                                        <TextInput
                                            id="time"
                                            type="time"
                                            value={data.time}
                                            onChange={(e) => setData('time', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="backup_type" value="Backup Type" />
                                        <select
                                            id="backup_type"
                                            value={data.backup_type}
                                            onChange={(e) => setData('backup_type', e.target.value)}
                                            className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                        >
                                            <option value="full">Full</option>
                                            <option value="incremental">Incremental</option>
                                            <option value="database_only">Database Only</option>
                                            <option value="files_only">Files Only</option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="retention_days" value="Retention (Days)" />
                                        <TextInput
                                            id="retention_days"
                                            type="number"
                                            value={data.retention_days}
                                            onChange={(e) => setData('retention_days', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.compress}
                                                onChange={(e) => setData('compress', e.target.checked)}
                                                className="rounded"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">Compress</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={data.encrypt}
                                                onChange={(e) => setData('encrypt', e.target.checked)}
                                                className="rounded"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">Encrypt</span>
                                        </label>
                                    </div>
                                </div>

                                <div className="flex gap-4 pt-4 border-t dark:border-gray-700">
                                    <PrimaryButton disabled={processing}>
                                        {processing ? 'Creating...' : 'Create Schedule'}
                                    </PrimaryButton>
                                    <SecondaryButton onClick={() => setIsAdding(false)}>
                                        Cancel
                                    </SecondaryButton>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Schedules List */}
                    {filteredSchedules.length > 0 ? (
                        <div className="space-y-4">
                            {filteredSchedules.map((schedule) => (
                                <div
                                    key={schedule.id}
                                    className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6"
                                >
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                                        <div>
                                            <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                                                {schedule.name}
                                            </h3>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                {schedule.frequency.charAt(0).toUpperCase() + schedule.frequency.slice(1)} at {schedule.time}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Type</p>
                                            <p className="font-semibold text-gray-900 dark:text-gray-100 capitalize">
                                                {schedule.backup_type.replace('_', ' ')}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Success Rate</p>
                                            <div className="flex items-center gap-2">
                                                {schedule.success_rate >= 80 ? (
                                                    <CheckCircleIcon className="w-5 h-5 text-green-600" />
                                                ) : (
                                                    <XCircleIcon className="w-5 h-5 text-red-600" />
                                                )}
                                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {schedule.success_rate}%
                                                </span>
                                            </div>
                                        </div>

                                        <div className="flex gap-2 justify-end">
                                            <button
                                                onClick={() => toggleSchedule(schedule)}
                                                className={`inline-flex items-center gap-2 px-3 py-2 rounded-md transition ${
                                                    schedule.is_enabled
                                                        ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200'
                                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                                                }`}
                                            >
                                                <PowerIcon className="w-4 h-4" />
                                            </button>
                                            <Link href={route('backups.schedules.edit', schedule.id)}>
                                                <button className="inline-flex items-center gap-2 px-3 py-2 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 rounded-md hover:bg-blue-200 transition">
                                                    <PencilIcon className="w-4 h-4" />
                                                </button>
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(schedule.id)}
                                                className="inline-flex items-center gap-2 px-3 py-2 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded-md hover:bg-red-200 transition"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <ClockIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                {searchQuery ? 'No schedules match your search.' : 'No backup schedules yet.'}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
