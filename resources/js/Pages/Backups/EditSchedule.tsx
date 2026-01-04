import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler } from 'react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

interface BackupSchedule {
    id: number;
    name: string;
    frequency: string;
    time: string;
    backup_type: string;
    retention_days: number;
    compress: boolean;
    encrypt: boolean;
}

interface Props {
    schedule: BackupSchedule;
}

export default function EditSchedule({ schedule }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: schedule.name,
        frequency: schedule.frequency,
        time: schedule.time,
        backup_type: schedule.backup_type,
        retention_days: schedule.retention_days.toString(),
        compress: schedule.compress,
        encrypt: schedule.encrypt,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('backups.schedules.update', schedule.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('backups.schedules.index')}>
                        <button className="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </button>
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Edit Schedule
                    </h2>
                </div>
            }
        >
            <Head title="Edit Schedule" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <form onSubmit={submit} className="space-y-6">
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
                                <InputError message={errors.frequency} className="mt-2" />
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
                                <InputError message={errors.time} className="mt-2" />
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
                                <InputError message={errors.backup_type} className="mt-2" />
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
                                <InputError message={errors.retention_days} className="mt-2" />
                            </div>

                            <div>
                                <h4 className="font-semibold text-gray-900 dark:text-gray-100 mb-3">Options</h4>
                                <div className="space-y-3">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.compress}
                                            onChange={(e) => setData('compress', e.target.checked)}
                                            className="rounded"
                                        />
                                        <span className="text-sm text-gray-700 dark:text-gray-300">Compress Backup</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.encrypt}
                                            onChange={(e) => setData('encrypt', e.target.checked)}
                                            className="rounded"
                                        />
                                        <span className="text-sm text-gray-700 dark:text-gray-300">Encrypt Backup</span>
                                    </label>
                                </div>
                            </div>

                            <div className="flex gap-4 pt-4 border-t dark:border-gray-700">
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Updating...' : 'Update Schedule'}
                                </PrimaryButton>
                                <Link href={route('backups.schedules.index')}>
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
