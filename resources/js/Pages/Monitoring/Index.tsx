import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { useEffect, useState, useMemo } from "react";
import axios from "axios";
import {
    CpuChipIcon,
    CircleStackIcon,
    ClockIcon,
    ArrowsUpDownIcon,
    ServerIcon,
    ArrowPathIcon,
} from "@heroicons/react/24/outline";
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from "recharts";

interface Stats {
    cpu_usage: number;
    memory: {
        total: number;
        used: number;
        free: number;
    };
    disks: Array<{
        name: string;
        mount_point: string;
        total: number;
        available: number;
    }>;
    networks: Array<{
        interface: string;
        received: number;
        transmitted: number;
        total_received: number;
        total_transmitted: number;
    }>;
    uptime: number;
    load_average: [number, number, number];
}

interface HistoryPoint {
    time: string;
    cpu: number;
    memory: number;
}

export default function Index({ stats: initialStats }: { stats: Stats }) {
    const [stats, setStats] = useState<Stats>(initialStats);
    const [history, setHistory] = useState<HistoryPoint[]>([]);
    const [refreshRate, setRefreshRate] = useState(5000);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [isRealtime, setIsRealtime] = useState(false);

    const updateStats = (newStats: Stats) => {
        setStats(newStats);
        const now = new Date().toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });
        setHistory((prev) => {
            const newHistory = [
                ...prev,
                {
                    time: now,
                    cpu: parseFloat((newStats?.cpu_usage ?? 0).toFixed(1)),
                    memory: parseFloat(
                        (
                            ((newStats?.memory?.used ?? 0) /
                                (newStats?.memory?.total ?? 1)) *
                            100
                        ).toFixed(1)
                    ),
                },
            ];
            return newHistory.slice(-20); // Keep last 20 points
        });
    };

    const fetchStats = async () => {
        if (isRealtime) return;
        setIsRefreshing(true);
        try {
            const response = await axios.get(route("monitoring.stats"));
            updateStats(response.data);
        } catch (error) {
            console.error("Failed to fetch stats:", error);
        } finally {
            setIsRefreshing(false);
        }
    };

    useEffect(() => {
        if (isRealtime) return;
        const interval = setInterval(fetchStats, refreshRate);
        return () => clearInterval(interval);
    }, [refreshRate, isRealtime]);

    useEffect(() => {
        const channel = window.Echo.private("monitoring").listen(
            ".stats.updated",
            (e: { stats: Stats }) => {
                setIsRealtime(true);
                updateStats(e.stats);
            }
        );

        return () => {
            window.Echo.leave("monitoring");
        };
    }, []);

    const formatUptime = (seconds: number) => {
        const days = Math.floor(seconds / (24 * 3600));
        const hours = Math.floor((seconds % (24 * 3600)) / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        return `${days}d ${hours}h ${minutes}m`;
    };

    const formatBytes = (bytes: number) => {
        if (bytes === 0) return "0 B";
        const k = 1024;
        const sizes = ["B", "KB", "MB", "GB", "TB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            System Monitoring
                        </h2>
                        {isRealtime ? (
                            <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <span className="mr-1.5 h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
                                Real-time
                            </span>
                        ) : (
                            <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                Polling
                            </span>
                        )}
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <span className="hidden sm:inline">
                                Refresh Rate:
                            </span>
                            <select
                                value={refreshRate}
                                onChange={(e) =>
                                    setRefreshRate(Number(e.target.value))
                                }
                                className="rounded-md border-gray-300 bg-white py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800"
                            >
                                <option value={2000}>2s</option>
                                <option value={5000}>5s</option>
                                <option value={10000}>10s</option>
                                <option value={30000}>30s</option>
                            </select>
                        </div>
                        <button
                            onClick={fetchStats}
                            disabled={isRefreshing}
                            className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-1 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <ArrowPathIcon
                                className={`mr-2 h-4 w-4 ${
                                    isRefreshing ? "animate-spin" : ""
                                }`}
                            />
                            Refresh
                        </button>
                    </div>
                </div>
            }
            breadcrumbs={[{ title: "System Monitoring" }]}
        >
            <Head title="System Monitoring" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Top Stats Grid */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {/* CPU Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        CPU Usage
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                        {(stats?.cpu_usage ?? 0).toFixed(1)}%
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
                                        style={{
                                            width: `${stats?.cpu_usage ?? 0}%`,
                                        }}
                                    />
                                </div>
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Load:{" "}
                                    {(stats?.load_average?.[0] ?? 0).toFixed(2)}{" "}
                                    {(stats?.load_average?.[1] ?? 0).toFixed(2)}{" "}
                                    {(stats?.load_average?.[2] ?? 0).toFixed(2)}
                                </p>
                            </div>
                        </div>

                        {/* Memory Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Memory Usage
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                        {(
                                            ((stats?.memory?.used ?? 0) /
                                                (stats?.memory?.total ?? 1)) *
                                            100
                                        ).toFixed(1)}
                                        %
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
                                        style={{
                                            width: `${
                                                ((stats?.memory?.used ?? 0) /
                                                    (stats?.memory?.total ??
                                                        1)) *
                                                100
                                            }%`,
                                        }}
                                    />
                                </div>
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {stats?.memory?.used ?? 0} MB /{" "}
                                    {stats?.memory?.total ?? 0} MB
                                </p>
                            </div>
                        </div>

                        {/* Uptime Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        System Uptime
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                        {formatUptime(stats?.uptime ?? 0)}
                                    </p>
                                </div>
                                <div className="rounded-lg bg-purple-50 p-3 dark:bg-purple-900/20">
                                    <ClockIcon className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                            <p className="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                Running since last reboot
                            </p>
                        </div>

                        {/* Network Card */}
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Network Activity
                                    </p>
                                    <div className="mt-1 flex items-baseline gap-2">
                                        <span className="text-2xl font-semibold text-gray-900 dark:text-white">
                                            {formatBytes(
                                                stats?.networks?.[0]
                                                    ?.received || 0
                                            )}
                                            /s
                                        </span>
                                    </div>
                                </div>
                                <div className="rounded-lg bg-orange-50 p-3 dark:bg-orange-900/20">
                                    <ArrowsUpDownIcon className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                </div>
                            </div>
                            <p className="mt-4 text-xs text-gray-500 dark:text-gray-400">
                                Primary interface:{" "}
                                {stats?.networks?.[0]?.interface || "N/A"}
                            </p>
                        </div>
                    </div>

                    {/* Charts Section */}
                    <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                CPU Usage History
                            </h3>
                            <div
                                className="mt-6 min-h-64 w-full"
                                style={{ minWidth: "0px", height: "256px" }}
                            >
                                <ResponsiveContainer
                                    width="100%"
                                    height="100%"
                                    minWidth={0}
                                >
                                    <AreaChart data={history}>
                                        <defs>
                                            <linearGradient
                                                id="colorCpu"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="5%"
                                                    stopColor="#3b82f6"
                                                    stopOpacity={0.3}
                                                />
                                                <stop
                                                    offset="95%"
                                                    stopColor="#3b82f6"
                                                    stopOpacity={0}
                                                />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            vertical={false}
                                            stroke="#374151"
                                            opacity={0.1}
                                        />
                                        <XAxis dataKey="time" hide />
                                        <YAxis domain={[0, 100]} hide />
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: "#1f2937",
                                                border: "none",
                                                borderRadius: "8px",
                                                color: "#fff",
                                            }}
                                            itemStyle={{ color: "#3b82f6" }}
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="cpu"
                                            stroke="#3b82f6"
                                            fillOpacity={1}
                                            fill="url(#colorCpu)"
                                            isAnimationActive={false}
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                Memory Usage History
                            </h3>
                            <div
                                className="mt-6 min-h-64 w-full"
                                style={{ minWidth: "0px", height: "256px" }}
                            >
                                <ResponsiveContainer
                                    width="100%"
                                    height="100%"
                                    minWidth={0}
                                >
                                    <AreaChart data={history}>
                                        <defs>
                                            <linearGradient
                                                id="colorMem"
                                                x1="0"
                                                y1="0"
                                                x2="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="5%"
                                                    stopColor="#10b981"
                                                    stopOpacity={0.3}
                                                />
                                                <stop
                                                    offset="95%"
                                                    stopColor="#10b981"
                                                    stopOpacity={0}
                                                />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            vertical={false}
                                            stroke="#374151"
                                            opacity={0.1}
                                        />
                                        <XAxis dataKey="time" hide />
                                        <YAxis domain={[0, 100]} hide />
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: "#1f2937",
                                                border: "none",
                                                borderRadius: "8px",
                                                color: "#fff",
                                            }}
                                            itemStyle={{ color: "#10b981" }}
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="memory"
                                            stroke="#10b981"
                                            fillOpacity={1}
                                            fill="url(#colorMem)"
                                            isAnimationActive={false}
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    </div>

                    {/* Disk Usage Section */}
                    <div className="mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 className="flex items-center text-lg font-medium text-gray-900 dark:text-white">
                                <ServerIcon className="mr-2 h-5 w-5 text-gray-400" />
                                Disk Usage
                            </h3>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {(stats?.disks ?? []).map((disk, index) => (
                                    <div
                                        key={index}
                                        className="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/50"
                                    >
                                        <div className="mb-3 flex items-center justify-between">
                                            <div>
                                                <span className="block font-medium text-gray-900 dark:text-white">
                                                    {disk.mount_point}
                                                </span>
                                                <span className="text-xs text-gray-500 dark:text-gray-400">
                                                    {disk.name}
                                                </span>
                                            </div>
                                            <span className="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                                {(
                                                    ((disk.total -
                                                        disk.available) /
                                                        (disk.total || 1)) *
                                                    100
                                                ).toFixed(1)}
                                                %
                                            </span>
                                        </div>
                                        <div className="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                className="h-2 rounded-full bg-indigo-500"
                                                style={{
                                                    width: `${
                                                        ((disk.total -
                                                            disk.available) /
                                                            (disk.total || 1)) *
                                                        100
                                                    }%`,
                                                }}
                                            />
                                        </div>
                                        <div className="mt-3 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span>
                                                {(
                                                    (disk.total -
                                                        disk.available) /
                                                    1024
                                                ).toFixed(2)}{" "}
                                                GB used
                                            </span>
                                            <span>
                                                {(disk.total / 1024).toFixed(2)}{" "}
                                                GB total
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Network Details Table */}
                    <div className="mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 className="flex items-center text-lg font-medium text-gray-900 dark:text-white">
                                <ArrowsUpDownIcon className="mr-2 h-5 w-5 text-gray-400" />
                                Network Interfaces
                            </h3>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                        <th className="px-6 py-3">Interface</th>
                                        <th className="px-6 py-3">
                                            Current RX
                                        </th>
                                        <th className="px-6 py-3">
                                            Current TX
                                        </th>
                                        <th className="px-6 py-3">Total RX</th>
                                        <th className="px-6 py-3">Total TX</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {(stats?.networks ?? []).map(
                                        (net, index) => (
                                            <tr
                                                key={index}
                                                className="hover:bg-gray-50 dark:hover:bg-gray-900/50"
                                            >
                                                <td className="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                                    {net.interface}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-blue-600 dark:text-blue-400">
                                                    {formatBytes(net.received)}
                                                    /s
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-green-600 dark:text-green-400">
                                                    {formatBytes(
                                                        net.transmitted
                                                    )}
                                                    /s
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {formatBytes(
                                                        net.total_received
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {formatBytes(
                                                        net.total_transmitted
                                                    )}
                                                </td>
                                            </tr>
                                        )
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
