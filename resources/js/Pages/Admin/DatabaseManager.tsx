import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

interface Database {
    name: string;
    tables: number;
    size_mb: number;
    rows: number;
}

interface Props {
    databases: Database[];
    stats: Record<string, any>;
}

export default function DatabaseManager({ databases, stats }: Props) {
    const [selectedDb, setSelectedDb] = useState<string | null>(null);

    const totalSize = databases.reduce((sum, db) => sum + db.size_mb, 0);
    const totalTables = databases.reduce((sum, db) => sum + db.tables, 0);
    const totalRows = databases.reduce((sum, db) => sum + db.rows, 0);

    return (
        <AuthenticatedLayout>
            <Head title="Database Manager" />

            <div className="space-y-6">
                {/* Header */}
                <div className="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Database Manager
                        </h1>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Manage and monitor your databases
                        </p>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Total Databases
                                </p>
                                <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                    {databases.length}
                                </p>
                            </div>
                            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                <svg
                                    className="h-6 w-6 text-blue-600 dark:text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Total Tables
                                </p>
                                <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                    {totalTables}
                                </p>
                            </div>
                            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                                <svg
                                    className="h-6 w-6 text-green-600 dark:text-green-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Total Size
                                </p>
                                <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                    {totalSize.toFixed(2)} MB
                                </p>
                            </div>
                            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                                <svg
                                    className="h-6 w-6 text-purple-600 dark:text-purple-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M7 12a4 4 0 11-8 0 4 4 0 018 0zM0 12a4 4 0 110 8 4 4 0 010-8zm10-8a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Databases Table */}
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Databases
                        </h2>
                    </div>

                    {databases.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <p className="text-gray-600 dark:text-gray-400">
                                No databases found
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                            Database Name
                                        </th>
                                        <th className="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                            Tables
                                        </th>
                                        <th className="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                            Rows
                                        </th>
                                        <th className="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                            Size
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {databases.map((db) => (
                                        <tr
                                            key={db.name}
                                            className="hover:bg-gray-50 dark:hover:bg-gray-700"
                                        >
                                            <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                                {db.name}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-400">
                                                {db.tables}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-400">
                                                {db.rows.toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-400">
                                                {db.size_mb.toFixed(2)} MB
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Info Box */}
                <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-900">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <svg
                                className="h-5 w-5 text-green-400"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </div>
                        <div className="ml-3 flex-1">
                            <p className="text-sm font-medium text-green-800 dark:text-green-200">
                                phpMyAdmin is installed and accessible!
                            </p>
                            <p className="mt-2 text-sm text-green-700 dark:text-green-300">
                                <a 
                                    href="/phpmyadmin" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    className="underline font-semibold hover:text-green-900 dark:hover:text-green-100"
                                >
                                    Open phpMyAdmin in a new tab
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
