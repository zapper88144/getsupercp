import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler } from 'react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

interface Alert {
    id: number;
    metric: string;
    operator: string;
    threshold: number;
    email_notification: boolean;
}

interface Props {
    alert: Alert;
}

export default function EditAlert({ alert }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        metric: alert.metric,
        operator: alert.operator,
        threshold: alert.threshold.toString(),
        email_notification: alert.email_notification,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('monitoring.alerts.update', alert.id));
    };

    const getMetricLabel = (metric: string): string => {
        const labels: Record<string, string> = {
            'cpu_usage': 'CPU Usage',
            'memory_usage': 'Memory Usage',
            'disk_usage': 'Disk Usage',
            'bandwidth_usage': 'Bandwidth Usage',
            'load_average': 'Load Average',
            'api_errors': 'API Errors',
        };
        return labels[metric] || metric;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('monitoring.alerts.index')}>
                        <button className="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </button>
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Edit Alert
                    </h2>
                </div>
            }
        >
            <Head title="Edit Alert" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="metric" value="Metric" />
                                <select
                                    id="metric"
                                    value={data.metric}
                                    onChange={(e) => setData('metric', e.target.value)}
                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="cpu_usage">CPU Usage (%)</option>
                                    <option value="memory_usage">Memory Usage (%)</option>
                                    <option value="disk_usage">Disk Usage (%)</option>
                                    <option value="bandwidth_usage">Bandwidth Usage (%)</option>
                                    <option value="load_average">Load Average</option>
                                    <option value="api_errors">API Errors</option>
                                </select>
                                <InputError message={errors.metric} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="operator" value="Operator" />
                                <select
                                    id="operator"
                                    value={data.operator}
                                    onChange={(e) => setData('operator', e.target.value)}
                                    className="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                >
                                    <option value=">">Greater than (&gt;)</option>
                                    <option value=">=">Greater than or equal (&gt;=)</option>
                                    <option value="<">Less than (&lt;)</option>
                                    <option value="<=">Less than or equal (&lt;=)</option>
                                    <option value="==">Equal (==)</option>
                                </select>
                                <InputError message={errors.operator} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="threshold" value="Threshold Value" />
                                <TextInput
                                    id="threshold"
                                    type="number"
                                    step="0.1"
                                    value={data.threshold}
                                    onChange={(e) => setData('threshold', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.threshold} className="mt-2" />
                            </div>

                            <div>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.email_notification}
                                        onChange={(e) => setData('email_notification', e.target.checked)}
                                        className="rounded"
                                    />
                                    <span className="text-sm text-gray-700 dark:text-gray-300">Email Notification</span>
                                </label>
                            </div>

                            <div className="flex gap-4 pt-4 border-t dark:border-gray-700">
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Updating...' : 'Update Alert'}
                                </PrimaryButton>
                                <Link href={route('monitoring.alerts.index')}>
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
