import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    TrashIcon
} from '@heroicons/react/24/outline';

interface AuditLog {
    id: number;
    user_id: number;
    user_name: string;
    action: string;
    description: string;
    ip_address: string;
    result: string;
    created_at: string;
}

interface Props {
    logs: AuditLog[];
}

export default function AuditLogs({ logs }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [filterAction, setFilterAction] = useState('');

    const filteredLogs = logs.filter(log => {
        const matchesSearch = 
            log.user_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            log.action.toLowerCase().includes(searchQuery.toLowerCase()) ||
            log.ip_address.toLowerCase().includes(searchQuery.toLowerCase()) ||
            log.description.toLowerCase().includes(searchQuery.toLowerCase());
        
        const matchesFilter = !filterAction || log.action === filterAction;
        
        return matchesSearch && matchesFilter;
    });

    const uniqueActions = Array.from(new Set(logs.map(log => log.action)));

    const getActionColor = (action: string): string => {
        if (action.includes('delete') || action.includes('remove')) return 'red';
        if (action.includes('create') || action.includes('add')) return 'green';
        if (action.includes('update') || action.includes('edit')) return 'blue';
        if (action.includes('login')) return 'purple';
        return 'gray';
    };

    const getResultIcon = (result: string) => {
        return result === 'success' 
            ? <CheckCircleIcon className="w-5 h-5 text-green-600" />
            : <ExclamationTriangleIcon className="w-5 h-5 text-red-600" />;
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('security.index')}>
                        <button className="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </button>
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Audit Logs
                    </h2>
                </div>
            }
        >
            <Head title="Audit Logs" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Search */}
                            <div className="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-2">
                                <MagnifyingGlassIcon className="w-5 h-5 text-gray-400" />
                                <input
                                    type="text"
                                    placeholder="Search by user, action, IP..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="flex-1 bg-transparent border-0 outline-none text-gray-900 dark:text-gray-100"
                                />
                            </div>

                            {/* Action Filter */}
                            <select
                                value={filterAction}
                                onChange={(e) => setFilterAction(e.target.value)}
                                className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            >
                                <option value="">All Actions</option>
                                {uniqueActions.map(action => (
                                    <option key={action} value={action}>
                                        {action.charAt(0).toUpperCase() + action.slice(1)}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Showing {filteredLogs.length} of {logs.length} logs
                        </p>
                    </div>

                    {/* Logs Table */}
                    {filteredLogs.length > 0 ? (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Time
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                User
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Action
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Description
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                IP Address
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Result
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-600">
                                        {filteredLogs.map((log) => (
                                            <tr key={log.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {new Date(log.created_at).toLocaleString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <span className="font-medium text-gray-900 dark:text-gray-100">
                                                        {log.user_name}
                                                    </span>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">ID: {log.user_id}</p>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${getActionColor(log.action)}-100 dark:bg-${getActionColor(log.action)}-900 text-${getActionColor(log.action)}-800 dark:text-${getActionColor(log.action)}-200`}>
                                                        {log.action}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                                    {log.description}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600 dark:text-gray-400">
                                                    {log.ip_address}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center gap-2">
                                                        {getResultIcon(log.result)}
                                                        <span className="text-sm text-gray-900 dark:text-gray-100 capitalize">
                                                            {log.result}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ) : (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <TrashIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                            <p className="text-gray-600 dark:text-gray-400">
                                {searchQuery || filterAction ? 'No audit logs match your filters.' : 'No audit logs found.'}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
