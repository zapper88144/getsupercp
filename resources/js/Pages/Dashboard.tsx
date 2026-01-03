import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePoll, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { 
    XAxis, 
    YAxis, 
    CartesianGrid, 
    Tooltip, 
    ResponsiveContainer,
    AreaChart,
    Area
} from 'recharts';
import {
    CpuChipIcon,
    CircleStackIcon,
    ServerIcon,
    ClockIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    ArrowRightIcon,
} from '@heroicons/react/24/outline';

interface Stats {
    cpu_usage: number;
    memory: {
        total: number;
        used: number;
        free: number;
    };
    disk: {
        total: number;
        used: number;
        free: number;
    };
    uptime: number;
    load_average: number[];
}

interface Props {
    stats: Stats;
    counts: {
        domains: number;
        firewall_rules: number;
    };
}

interface HistoryItem {
    time: string;
    cpu: number;
    ram: number;
}

export default function Dashboard({ stats, counts }: Props) {
    usePoll(5000);
    const [history, setHistory] = useState<HistoryItem[]>([]);

    useEffect(() => {
        if (stats) {
            const now = new Date();
            const timeStr = `${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}`;
            
            const ramUsage = stats.memory ? Math.round((stats.memory.used / stats.memory.total) * 100) : 0;
            
            setHistory(prev => {
                const newHistory = [...prev, {
                    time: timeStr,
                    cpu: stats.cpu_usage,
                    ram: ramUsage
                }];
                return newHistory.slice(-20);
            });
        }
    }, [stats]);

    const formatUptime = (seconds: number) => {
        if (!seconds) return 'N/A';
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        return `${h}h ${m}m`;
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {/* CPU Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">CPU Usage</p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                        {stats?.cpu_usage ?? 0}%
                                    </p>
                                </div>
                                <div className="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                                    <CpuChipIcon className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <div className="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div 
                                        className="h-2 rounded-full bg-blue-500 transition-all duration-500" 
                                        style={{ width: `${stats?.cpu_usage ?? 0}%` }}
                                    />
                                </div>
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Load: {stats?.load_average ? stats.load_average[0].toFixed(2) : '0.00'}
                                </p>
                            </div>
                        </div>

                        {/* Memory Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Memory Usage</p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                        {stats?.memory ? Math.round((stats.memory.used / stats.memory.total) * 100) : 0}%
                                    </p>
                                </div>
                                <div className="rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                    <CircleStackIcon className="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <div className="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div 
                                        className="h-2 rounded-full bg-green-500 transition-all duration-500" 
                                        style={{ width: `${stats?.memory ? (stats.memory.used / stats.memory.total) * 100 : 0}%` }}
                                    />
                                </div>
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {stats?.memory ? `${Math.round(stats.memory.used / 1024)}GB / ${Math.round(stats.memory.total / 1024)}GB` : '0 / 0'}
                                </p>
                            </div>
                        </div>

                        {/* Disk Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Disk Usage</p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                        {stats?.disk ? Math.round((stats.disk.used / stats.disk.total) * 100) : 0}%
                                    </p>
                                </div>
                                <div className="rounded-lg bg-orange-50 p-3 dark:bg-orange-900/20">
                                    <ServerIcon className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                </div>
                            </div>
                            <div className="mt-4">
                                <div className="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div 
                                        className="h-2 rounded-full bg-orange-500 transition-all duration-500" 
                                        style={{ width: `${stats?.disk ? (stats.disk.used / stats.disk.total) * 100 : 0}%` }}
                                    />
                                </div>
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {stats?.disk ? `${Math.round(stats.disk.used / 1024)}GB / ${Math.round(stats.disk.total / 1024)}GB` : '0 / 0'}
                                </p>
                            </div>
                        </div>

                        {/* Uptime Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">System Uptime</p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                        {formatUptime(stats?.uptime)}
                                    </p>
                                </div>
                                <div className="rounded-lg bg-purple-50 p-3 dark:bg-purple-900/20">
                                    <ClockIcon className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                            <div className="mt-4 flex items-center gap-2">
                                <div className="h-2 w-2 rounded-full bg-green-500 animate-pulse" />
                                <p className="text-xs text-gray-500 dark:text-gray-400">System Online</p>
                            </div>
                        </div>
                    </div>

                    {/* Charts Section */}
                    <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">CPU Usage History (%)</h3>
                            <div className="mt-6 min-h-64 w-full" style={{ minWidth: '0px', height: '256px' }}>
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={history}>
                                        <defs>
                                            <linearGradient id="colorCpu" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3}/>
                                                <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#374151" opacity={0.1} />
                                        <XAxis dataKey="time" hide />
                                        <YAxis domain={[0, 100]} hide />
                                        <Tooltip 
                                            contentStyle={{ backgroundColor: '#1f2937', border: 'none', borderRadius: '8px', color: '#fff' }}
                                            itemStyle={{ color: '#3b82f6' }}
                                        />
                                        <Area type="monotone" dataKey="cpu" stroke="#3b82f6" fillOpacity={1} fill="url(#colorCpu)" isAnimationActive={false} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">RAM Usage History (%)</h3>
                            <div className="mt-6 min-h-64 w-full" style={{ minWidth: '0px', height: '256px' }}>
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={history}>
                                        <defs>
                                            <linearGradient id="colorRam" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#10b981" stopOpacity={0.3}/>
                                                <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#374151" opacity={0.1} />
                                        <XAxis dataKey="time" hide />
                                        <YAxis domain={[0, 100]} hide />
                                        <Tooltip 
                                            contentStyle={{ backgroundColor: '#1f2937', border: 'none', borderRadius: '8px', color: '#fff' }}
                                            itemStyle={{ color: '#10b981' }}
                                        />
                                        <Area type="monotone" dataKey="ram" stroke="#10b981" fillOpacity={1} fill="url(#colorRam)" isAnimationActive={false} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>

                    {/* Quick Links & Info */}
                    <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">Quick Access</h3>
                            </div>
                            <div className="p-6">
                                <div className="grid grid-cols-2 gap-4">
                                    <Link
                                        href={route('web-domains.index')}
                                        className="flex flex-col items-center justify-center rounded-xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:bg-gray-900"
                                    >
                                        <GlobeAltIcon className="h-8 w-8 text-indigo-500" />
                                        <span className="mt-2 text-sm font-medium text-gray-900 dark:text-white">Web Domains</span>
                                        <span className="text-xs text-gray-500">{counts.domains} active</span>
                                    </Link>
                                    <Link
                                        href={route('firewall.index')}
                                        className="flex flex-col items-center justify-center rounded-xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:bg-gray-900"
                                    >
                                        <ShieldCheckIcon className="h-8 w-8 text-green-500" />
                                        <span className="mt-2 text-sm font-medium text-gray-900 dark:text-white">Firewall</span>
                                        <span className="text-xs text-gray-500">{counts.firewall_rules} rules</span>
                                    </Link>
                                    <Link
                                        href={route('services.index')}
                                        className="flex flex-col items-center justify-center rounded-xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:bg-gray-900"
                                    >
                                        <CommandLineIcon className="h-8 w-8 text-purple-500" />
                                        <span className="mt-2 text-sm font-medium text-gray-900 dark:text-white">Services</span>
                                        <span className="text-xs text-gray-500">Manage system</span>
                                    </Link>
                                    <Link
                                        href={route('monitoring.index')}
                                        className="flex flex-col items-center justify-center rounded-xl border border-gray-100 bg-gray-50 p-4 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:bg-gray-900"
                                    >
                                        <CpuChipIcon className="h-8 w-8 text-blue-500" />
                                        <span className="mt-2 text-sm font-medium text-gray-900 dark:text-white">Monitoring</span>
                                        <span className="text-xs text-gray-500">Real-time stats</span>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">System Information</h3>
                            </div>
                            <div className="p-6">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                                                <CommandLineIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                            </div>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">OS Version</span>
                                        </div>
                                        <span className="text-sm font-medium text-gray-900 dark:text-white">Ubuntu 24.04 LTS</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                                                <GlobeAltIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                            </div>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">IP Address</span>
                                        </div>
                                        <span className="text-sm font-medium text-gray-900 dark:text-white">127.0.0.1</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-700">
                                                <ClockIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                            </div>
                                            <span className="text-sm text-gray-600 dark:text-gray-400">Server Time</span>
                                        </div>
                                        <span className="text-sm font-medium text-gray-900 dark:text-white">{new Date().toLocaleTimeString()}</span>
                                    </div>
                                </div>

                                <div className="mt-8">
                                    <Link
                                        href={route('monitoring.index')}
                                        className="flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                        View Detailed Monitoring
                                        <ArrowRightIcon className="h-4 w-4" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


