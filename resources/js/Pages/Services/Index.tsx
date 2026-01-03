import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';
import {
    ArrowPathIcon,
    ServerIcon,
    CommandLineIcon,
    CircleStackIcon,
    GlobeAltIcon,
    CpuChipIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationCircleIcon,
} from '@heroicons/react/24/outline';

interface ServiceStatus {
    [key: string]: 'running' | 'stopped' | 'not found';
}

export default function Index() {
    const [services, setServices] = useState<ServiceStatus>({});
    const [loading, setLoading] = useState(true);
    const [restarting, setRestarting] = useState<string | null>(null);
    const [isRefreshing, setIsRefreshing] = useState(false);

    const fetchStatus = async () => {
        setIsRefreshing(true);
        try {
            const response = await axios.get(route('services.status'));
            setServices(response.data);
        } catch (error) {
            console.error('Error fetching service status:', error);
        } finally {
            setLoading(false);
            setIsRefreshing(false);
        }
    };

    const restartService = async (service: string) => {
        if (!confirm(`Are you sure you want to restart ${service}?`)) return;
        
        setRestarting(service);
        try {
            const response = await axios.post(route('services.restart'), { service });
            // We could use a toast here if available, but for now we'll just refresh
            fetchStatus();
        } catch (error: any) {
            alert(error.response?.data?.message || 'Failed to restart service');
        } finally {
            setRestarting(null);
        }
    };

    useEffect(() => {
        fetchStatus();
        const interval = setInterval(fetchStatus, 15000);
        return () => clearInterval(interval);
    }, []);

    const serviceInfo: { [key: string]: { name: string; description: string; icon: any } } = {
        'nginx': {
            name: 'Nginx Web Server',
            description: 'High-performance HTTP server and reverse proxy.',
            icon: GlobeAltIcon
        },
        'php8.4-fpm': {
            name: 'PHP 8.4 FPM',
            description: 'FastCGI Process Manager for PHP 8.4.',
            icon: CommandLineIcon
        },
        'mysql': {
            name: 'MySQL Database',
            description: 'Relational database management system.',
            icon: CircleStackIcon
        },
        'redis-server': {
            name: 'Redis Server',
            description: 'In-memory data structure store.',
            icon: CpuChipIcon
        },
        'daemon': {
            name: 'Super CP Daemon',
            description: 'Core system management service.',
            icon: ServerIcon
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'running':
                return (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <CheckCircleIcon className="h-3.5 w-3.5" />
                        Running
                    </span>
                );
            case 'stopped':
                return (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                        <XCircleIcon className="h-3.5 w-3.5" />
                        Stopped
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                        <ExclamationCircleIcon className="h-3.5 w-3.5" />
                        {status}
                    </span>
                );
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        System Services
                    </h2>
                    <button
                        onClick={fetchStatus}
                        disabled={isRefreshing}
                        className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <ArrowPathIcon className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                        Refresh
                    </button>
                </div>
            }
        >
            <Head title="System Services" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {loading ? (
                        <div className="flex h-64 items-center justify-center">
                            <div className="flex flex-col items-center gap-4">
                                <ArrowPathIcon className="h-8 w-8 animate-spin text-indigo-500" />
                                <p className="text-gray-500 dark:text-gray-400">Loading service status...</p>
                            </div>
                        </div>
                    ) : (
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {Object.entries(services).map(([id, status]) => {
                                const info = serviceInfo[id] || {
                                    name: id,
                                    description: 'System service',
                                    icon: ServerIcon
                                };
                                const Icon = info.icon;

                                return (
                                    <div
                                        key={id}
                                        className="group relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md dark:bg-gray-800 dark:ring-gray-700"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-4">
                                                <div className="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/50">
                                                    <Icon className="h-6 w-6 text-gray-600 dark:text-gray-400" />
                                                </div>
                                                <div>
                                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                                        {info.name}
                                                    </h3>
                                                    <p className="text-xs font-mono text-gray-500 dark:text-gray-400">
                                                        {id}
                                                    </p>
                                                </div>
                                            </div>
                                            {getStatusBadge(status)}
                                        </div>

                                        <p className="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                            {info.description}
                                        </p>

                                        <div className="mt-6 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-700">
                                            <div className="flex items-center gap-2">
                                                <div className={`h-2 w-2 rounded-full ${status === 'running' ? 'animate-pulse bg-green-500' : 'bg-red-500'}`} />
                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                    {status === 'running' ? 'Active' : 'Inactive'}
                                                </span>
                                            </div>

                                            {id !== 'daemon' && (
                                                <button
                                                    onClick={() => restartService(id)}
                                                    disabled={restarting !== null}
                                                    className="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/40"
                                                >
                                                    {restarting === id ? (
                                                        <>
                                                            <ArrowPathIcon className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                                            Restarting...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <ArrowPathIcon className="mr-1.5 h-3.5 w-3.5" />
                                                            Restart
                                                        </>
                                                    )}
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

