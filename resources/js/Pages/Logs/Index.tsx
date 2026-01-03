import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, useEffect, useRef, useMemo } from 'react';
import axios from 'axios';
import { 
    ArrowPathIcon, 
    CommandLineIcon, 
    DocumentTextIcon,
    AdjustmentsHorizontalIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    PauseIcon,
    PlayIcon
} from '@heroicons/react/24/outline';

interface LogType {
    id: string;
    name: string;
}

interface Props {
    logTypes: LogType[];
}

export default function Index({ logTypes }: Props) {
    const [selectedType, setSelectedType] = useState(logTypes[0]?.id || 'daemon');
    const [logs, setLogs] = useState<string>('Loading logs...');
    const [lines, setLines] = useState(100);
    const [autoRefresh, setAutoRefresh] = useState(false);
    const [loading, setLoading] = useState(false);
    const logContainerRef = useRef<HTMLPreElement>(null);

    const fetchLogs = async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('logs.fetch'), {
                params: { type: selectedType, lines }
            });
            setLogs(response.data.content);
        } catch (error) {
            setLogs('Error fetching logs.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchLogs();
    }, [selectedType, lines]);

    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (autoRefresh) {
            interval = setInterval(fetchLogs, 5000);
        }
        return () => clearInterval(interval);
    }, [autoRefresh, selectedType, lines]);

    useEffect(() => {
        if (logContainerRef.current) {
            logContainerRef.current.scrollTop = logContainerRef.current.scrollHeight;
        }
    }, [logs]);

    const downloadLogs = () => {
        const element = document.createElement("a");
        const file = new Blob([logs], {type: 'text/plain'});
        element.href = URL.createObjectURL(file);
        element.download = `${selectedType}-logs.txt`;
        document.body.appendChild(element);
        element.click();
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <CommandLineIcon className="h-6 w-6 text-gray-400" />
                        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                            System Log Viewer
                        </h2>
                    </div>
                    <div className="flex items-center gap-3">
                        <button
                            onClick={downloadLogs}
                            className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <ArrowDownTrayIcon className="h-4 w-4" />
                            Download
                        </button>
                        <button
                            onClick={fetchLogs}
                            disabled={loading}
                            className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <ArrowPathIcon className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                            Refresh
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Log Viewer" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        {/* Toolbar */}
                        <div className="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div className="flex items-center gap-6">
                                    <div className="flex items-center gap-2">
                                        <DocumentTextIcon className="h-5 w-5 text-gray-400" />
                                        <select
                                            value={selectedType}
                                            onChange={(e) => setSelectedType(e.target.value)}
                                            className="rounded-lg border-gray-300 bg-white py-1.5 pl-3 pr-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            {logTypes.map((type) => (
                                                <option key={type.id} value={type.id}>
                                                    {type.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <AdjustmentsHorizontalIcon className="h-5 w-5 text-gray-400" />
                                        <select
                                            value={lines}
                                            onChange={(e) => setLines(Number(e.target.value))}
                                            className="rounded-lg border-gray-300 bg-white py-1.5 pl-3 pr-10 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value={50}>Last 50 lines</option>
                                            <option value={100}>Last 100 lines</option>
                                            <option value={200}>Last 200 lines</option>
                                            <option value={500}>Last 500 lines</option>
                                            <option value={1000}>Last 1000 lines</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <button
                                        onClick={() => setAutoRefresh(!autoRefresh)}
                                        className={`inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors ${
                                            autoRefresh 
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' 
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                        }`}
                                    >
                                        {autoRefresh ? <PauseIcon className="h-4 w-4" /> : <PlayIcon className="h-4 w-4" />}
                                        {autoRefresh ? 'Auto-refreshing' : 'Auto-refresh'}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Log Content */}
                        <div className="relative bg-gray-950 p-1">
                            <div className="absolute right-4 top-4 z-10 flex gap-2">
                                <div className="h-3 w-3 rounded-full bg-rose-500" />
                                <div className="h-3 w-3 rounded-full bg-amber-500" />
                                <div className="h-3 w-3 rounded-full bg-emerald-500" />
                            </div>
                            <pre 
                                ref={logContainerRef}
                                className="h-[600px] overflow-auto rounded-lg bg-gray-900 p-6 font-mono text-sm leading-relaxed text-gray-300 selection:bg-indigo-500/30"
                            >
                                {logs.split('\n').map((line, i) => (
                                    <div key={i} className="flex gap-4 hover:bg-gray-800/50">
                                        <span className="w-12 shrink-0 select-none text-right text-gray-600">{i + 1}</span>
                                        <span className={`break-all ${
                                            line.toLowerCase().includes('error') || line.toLowerCase().includes('fail') 
                                                ? 'text-rose-400' 
                                                : line.toLowerCase().includes('warn') 
                                                    ? 'text-amber-400' 
                                                    : 'text-gray-300'
                                        }`}>
                                            {line}
                                        </span>
                                    </div>
                                ))}
                                {loading && (
                                    <div className="mt-4 flex items-center gap-2 text-indigo-400">
                                        <ArrowPathIcon className="h-4 w-4 animate-spin" />
                                        <span>Streaming updates...</span>
                                    </div>
                                )}
                            </pre>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
