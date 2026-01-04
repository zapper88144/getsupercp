import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState } from 'react';
import {
    BellIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    TrashIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    SparklesIcon
} from '@heroicons/react/24/outline';

interface Alert {
    id: number;
    metric: string;
    operator: string;
    threshold: number;
    current_value: number;
    triggered: boolean;
    is_enabled: boolean;
    email_notification: boolean;
    created_at: string;
}

interface Props {
    alerts: Alert[];
}

export default function Alerts({ alerts }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [isAdding, setIsAdding] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        metric: 'cpu_usage',
        operator: '>',
        threshold: '80',
        email_notification: true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('monitoring.alerts.store'), {
            onSuccess: () => {
                reset();
                setIsAdding(false);
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this alert?')) {
            router.delete(route('monitoring.alerts.destroy', id));
        }
    };

    const handleToggle = (alert: Alert) => {
        router.post(route('monitoring.alerts.toggle', alert.id));
    };

    const filteredAlerts = alerts.filter(a =>
        a.metric.toLowerCase().includes(searchQuery.toLowerCase())
    );

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

    const getAlertStatus = (alert: Alert) => {
        if (!alert.is_enabled) return 'disabled';
        return alert.triggered ? 'critical' : 'ok';
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Monitoring Alerts
                    </h2>
                    <PrimaryButton
                        onClick={() => setIsAdding(!isAdding)}
                        className="flex items-center gap-2"
                    >
                        {isAdding ? '✕' : <PlusIcon className="w-5 h-5" />}
                        {isAdding ? 'Cancel' : 'New Alert'}
                    </PrimaryButton>
                </div>
            }
            breadcrumbs={[
                { title: 'System Monitoring', url: route('monitoring.index') },
                { title: 'Monitoring Alerts' }
            ]}
        >
            <Head title="Monitoring Alerts" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Search Bar */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div className="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-2">
                            <MagnifyingGlassIcon className="w-5 h-5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Search alerts..."
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
                                            placeholder="80"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.threshold} className="mt-2" />
                                    </div>

                                    <div className="flex items-center pt-6">
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
                                </div>

                                <div className="flex gap-4 pt-4 border-t dark:border-gray-700">
                                    <PrimaryButton disabled={processing}>
                                        {processing ? 'Creating...' : 'Create Alert'}
                                    </PrimaryButton>
                                    <SecondaryButton onClick={() => setIsAdding(false)}>
                                        Cancel
                                    </SecondaryButton>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Alerts List */}
                    {filteredAlerts.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {filteredAlerts.map((alert) => {
                                const status = getAlertStatus(alert);
                                return (
                                    <div
                                        key={alert.id}
                                        className={`overflow-hidden shadow-sm sm:rounded-lg p-6 ${
                                            status === 'critical'
                                                ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
                                                : status === 'disabled'
                                                ? 'bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600'
                                                : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700'
                                        }`}
                                    >
                                        <div className="flex justify-between items-start mb-4">
                                            <div className="flex items-center gap-3">
                                                {status === 'critical' ? (
                                                    <ExclamationTriangleIcon className="w-6 h-6 text-red-600" />
                                                ) : status === 'disabled' ? (
                                                    <BellIcon className="w-6 h-6 text-gray-400" />
                                                ) : (
                                                    <CheckCircleIcon className="w-6 h-6 text-green-600" />
                                                )}
                                                <div>
                                                    <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                                                        {getMetricLabel(alert.metric)}
                                                    </h3>
                                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                                        {alert.operator} {alert.threshold}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Current Value</p>
                                            <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                                {alert.current_value.toFixed(2)}
                                            </p>
                                        </div>

                                        {alert.email_notification && (
                                            <div className="mb-4 flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                                                <SparklesIcon className="w-4 h-4" />
                                                Email notifications enabled
                                            </div>
                                        )}

                                        <div className="flex gap-2 justify-end pt-4 border-t dark:border-gray-700">
                                            <Link href={route('monitoring.alerts.edit', alert.id)}>
                                                <button className="inline-flex items-center gap-2 px-3 py-2 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 rounded-md hover:bg-blue-200 transition">
                                                    <PencilIcon className="w-4 h-4" />
                                                </button>
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(alert.id)}
                                                className="inline-flex items-center gap-2 px-3 py-2 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded-md hover:bg-red-200 transition"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <BellIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                {searchQuery ? 'No alerts match your search.' : 'No monitoring alerts yet.'}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
