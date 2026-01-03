import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState, useMemo } from 'react';
import { 
    ClockIcon, 
    CommandLineIcon, 
    TrashIcon, 
    PlusIcon, 
    MagnifyingGlassIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon
} from '@heroicons/react/24/outline';

interface CronJob {
    id: number;
    command: string;
    schedule: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
}

interface Props {
    cronJobs: CronJob[];
}

export default function Index({ cronJobs }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [showCreateForm, setShowCreateForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        command: '',
        schedule: '* * * * *',
        description: '',
    });

    const filteredJobs = useMemo(() => {
        return cronJobs.filter(job => 
            job.command.toLowerCase().includes(searchQuery.toLowerCase()) ||
            (job.description?.toLowerCase() || '').includes(searchQuery.toLowerCase())
        );
    }, [cronJobs, searchQuery]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('cron-jobs.store'), {
            onSuccess: () => {
                reset();
                setShowCreateForm(false);
            },
        });
    };

    const deleteCronJob = (id: number) => {
        if (confirm('Are you sure you want to delete this cron job?')) {
            router.delete(route('cron-jobs.destroy', id));
        }
    };

    const toggleStatus = (cronJob: CronJob) => {
        router.patch(route('cron-jobs.update', cronJob.id), {
            is_active: !cronJob.is_active,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Cron Jobs
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
                                Add Cron Job
                            </>
                        )}
                    </button>
                </div>
            }
        >
            <Head title="Cron Jobs" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Create Form */}
                    {showCreateForm && (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 border border-gray-200 dark:border-gray-700 transition-all duration-300 ease-in-out">
                            <div className="p-6 text-gray-900 dark:text-gray-100">
                                <header className="mb-6">
                                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                                        <PlusIcon className="mr-2 h-5 w-5 text-indigo-500" />
                                        Create New Cron Job
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Schedule a command to run periodically on your system.
                                    </p>
                                </header>

                                <form onSubmit={submit} className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div className="md:col-span-2">
                                        <InputLabel htmlFor="command" value="Command" />
                                        <div className="mt-1 flex rounded-md shadow-sm">
                                            <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                <CommandLineIcon className="h-4 w-4" />
                                            </span>
                                            <TextInput
                                                id="command"
                                                className="block w-full rounded-none rounded-r-md"
                                                value={data.command}
                                                onChange={(e) => setData('command', e.target.value)}
                                                required
                                                placeholder="php /home/super/getsupercp/artisan schedule:run"
                                            />
                                        </div>
                                        <InputError message={errors.command} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="schedule" value="Schedule (Cron Expression)" />
                                        <div className="mt-1 flex rounded-md shadow-sm">
                                            <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                <ClockIcon className="h-4 w-4" />
                                            </span>
                                            <TextInput
                                                id="schedule"
                                                className="block w-full rounded-none rounded-r-md"
                                                value={data.schedule}
                                                onChange={(e) => setData('schedule', e.target.value)}
                                                required
                                                placeholder="* * * * *"
                                            />
                                        </div>
                                        <p className="mt-1 text-xs text-gray-500 flex items-center">
                                            <InformationCircleIcon className="mr-1 h-3 w-3" />
                                            Format: minute hour day month day-of-week
                                        </p>
                                        <InputError message={errors.schedule} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="description" value="Description (Optional)" />
                                        <TextInput
                                            id="description"
                                            className="mt-1 block w-full"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            placeholder="Daily backup script"
                                        />
                                        <InputError message={errors.description} className="mt-2" />
                                    </div>

                                    <div className="md:col-span-2 flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                        <button
                                            type="button"
                                            onClick={() => setShowCreateForm(false)}
                                            className="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                                        >
                                            Cancel
                                        </button>
                                        <PrimaryButton disabled={processing}>
                                            {processing ? 'Creating...' : 'Create Cron Job'}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* Search and List */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        <div className="p-6">
                            <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Existing Cron Jobs
                                </h3>
                                <div className="relative max-w-sm w-full">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                                    </div>
                                    <input
                                        type="text"
                                        className="block w-full rounded-md border-0 py-1.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-white dark:ring-gray-600"
                                        placeholder="Search commands or descriptions..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                    <thead className="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                        <tr>
                                            <th className="px-6 py-3 font-semibold">Schedule</th>
                                            <th className="px-6 py-3 font-semibold">Command</th>
                                            <th className="px-6 py-3 font-semibold">Description</th>
                                            <th className="px-6 py-3 font-semibold">Status</th>
                                            <th className="px-6 py-3 text-right font-semibold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {filteredJobs.map((job) => (
                                            <tr 
                                                key={job.id} 
                                                className="group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150"
                                            >
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <ClockIcon className="mr-2 h-4 w-4 text-gray-400" />
                                                        <span className="font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">
                                                            {job.schedule}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center max-w-xs lg:max-w-md">
                                                        <CommandLineIcon className="mr-2 h-4 w-4 flex-shrink-0 text-gray-400" />
                                                        <span className="font-mono text-xs truncate text-gray-700 dark:text-gray-300" title={job.command}>
                                                            {job.command}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="text-gray-600 dark:text-gray-400 italic">
                                                        {job.description || 'No description'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <button
                                                        onClick={() => toggleStatus(job)}
                                                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors duration-200 ${
                                                            job.is_active
                                                                ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50'
                                                                : 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50'
                                                        }`}
                                                    >
                                                        {job.is_active ? (
                                                            <>
                                                                <CheckCircleIcon className="mr-1 h-3 w-3" />
                                                                Active
                                                            </>
                                                        ) : (
                                                            <>
                                                                <XCircleIcon className="mr-1 h-3 w-3" />
                                                                Inactive
                                                            </>
                                                        )}
                                                    </button>
                                                </td>
                                                <td className="px-6 py-4 text-right whitespace-nowrap">
                                                    <div className="flex justify-end opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                        <button
                                                            onClick={() => deleteCronJob(job.id)}
                                                            className="inline-flex items-center rounded-md p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors duration-200"
                                                            title="Delete Cron Job"
                                                        >
                                                            <TrashIcon className="h-5 w-5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                        {filteredJobs.length === 0 && (
                                            <tr>
                                                <td colSpan={5} className="px-6 py-12 text-center">
                                                    <div className="flex flex-col items-center justify-center">
                                                        <ClockIcon className="h-12 w-12 text-gray-300 dark:text-gray-600" />
                                                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                                            {searchQuery ? 'No cron jobs match your search.' : 'No cron jobs found. Create one to get started.'}
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
