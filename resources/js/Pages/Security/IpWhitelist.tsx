import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link } from "@inertiajs/react";
import { useEffect, useState } from "react";
import {
    PlusIcon,
    TrashIcon,
    ArrowPathIcon,
} from "@heroicons/react/24/outline";

interface WhitelistItem {
    id: number;
    ip_address: string;
    ip_range: string | null;
    description: string;
    reason: string;
    is_permanent: boolean;
    expires_at: string | null;
    user_id: number | null;
    created_at: string;
}

export default function IpWhitelist() {
    const [whitelist, setWhitelist] = useState<WhitelistItem[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isAdding, setIsAdding] = useState(false);
    const [showForm, setShowForm] = useState(false);
    const [formData, setFormData] = useState({
        ip_address: "",
        reason: "",
        description: "",
        is_permanent: true,
        expires_in_hours: "",
    });

    useEffect(() => {
        fetchWhitelist();
    }, []);

    const fetchWhitelist = async () => {
        try {
            const response = await fetch("/api/security/whitelist");
            if (response.ok) {
                const data = await response.json();
                setWhitelist(data.data || []);
            }
        } catch (error) {
            console.error("Error fetching whitelist:", error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleAddIp = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsAdding(true);

        try {
            const response = await fetch("/api/security/whitelist/add", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({
                    ip_address: formData.ip_address,
                    reason: formData.reason,
                    description: formData.description,
                    is_permanent: formData.is_permanent,
                    expires_in_hours: formData.expires_in_hours
                        ? parseInt(formData.expires_in_hours)
                        : null,
                }),
            });

            if (response.ok) {
                setFormData({
                    ip_address: "",
                    reason: "",
                    description: "",
                    is_permanent: true,
                    expires_in_hours: "",
                });
                setShowForm(false);
                fetchWhitelist();
            }
        } catch (error) {
            console.error("Error adding IP:", error);
        } finally {
            setIsAdding(false);
        }
    };

    const handleRemoveIp = async (id: number) => {
        if (
            !window.confirm(
                "Are you sure you want to remove this IP from the whitelist?"
            )
        ) {
            return;
        }

        try {
            const response = await fetch(`/api/security/whitelist/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
            });

            if (response.ok) {
                fetchWhitelist();
            }
        } catch (error) {
            console.error("Error removing IP:", error);
        }
    };

    const handleSyncCloudflareIps = async () => {
        setIsLoading(true);
        try {
            const response = await fetch(
                "/api/security/whitelist/sync-cloudflare",
                {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                }
            );

            if (response.ok) {
                fetchWhitelist();
                alert("Cloudflare IPs synced successfully");
            }
        } catch (error) {
            console.error("Error syncing Cloudflare IPs:", error);
        } finally {
            setIsLoading(false);
        }
    };

    const isExpired = (expiresAt: string | null) => {
        if (!expiresAt) return false;
        return new Date(expiresAt) < new Date();
    };

    const breadcrumbs = [
        { title: "Security", href: "/security/dashboard" },
        { title: "IP Whitelist" },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={breadcrumbs}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    IP Whitelist Management
                </h2>
            }
        >
            <Head title="IP Whitelist" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {/* Actions Bar */}
                    <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                        <div>
                            <p className="text-gray-600 dark:text-gray-400">
                                Total whitelisted IPs:{" "}
                                <span className="font-bold text-gray-900 dark:text-gray-100">
                                    {whitelist.length}
                                </span>
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <button
                                onClick={handleSyncCloudflareIps}
                                disabled={isLoading}
                                className="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                            >
                                <ArrowPathIcon className="w-5 h-5" />
                                Sync Cloudflare IPs
                            </button>
                            <button
                                onClick={() => setShowForm(!showForm)}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                <PlusIcon className="w-5 h-5" />
                                Add IP
                            </button>
                        </div>
                    </div>

                    {/* Add IP Form */}
                    {showForm && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                Add IP to Whitelist
                            </h3>
                            <form onSubmit={handleAddIp} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        IP Address *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.ip_address}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                ip_address: e.target.value,
                                            })
                                        }
                                        placeholder="192.168.1.1"
                                        className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Reason *
                                    </label>
                                    <select
                                        required
                                        value={formData.reason}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                reason: e.target.value,
                                            })
                                        }
                                        className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">
                                            Select a reason
                                        </option>
                                        <option value="cloudflare">
                                            Cloudflare
                                        </option>
                                        <option value="admin">Admin IP</option>
                                        <option value="trusted_partner">
                                            Trusted Partner
                                        </option>
                                        <option value="backup_service">
                                            Backup Service
                                        </option>
                                        <option value="monitoring">
                                            Monitoring
                                        </option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        value={formData.description}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                description: e.target.value,
                                            })
                                        }
                                        placeholder="Optional notes about this IP..."
                                        rows={3}
                                        className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_permanent}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    is_permanent:
                                                        e.target.checked,
                                                })
                                            }
                                            className="rounded border-gray-300 dark:border-gray-600"
                                        />
                                        <span className="text-sm text-gray-700 dark:text-gray-300">
                                            Permanent
                                        </span>
                                    </label>
                                    {!formData.is_permanent && (
                                        <div className="flex-1">
                                            <input
                                                type="number"
                                                min="1"
                                                value={
                                                    formData.expires_in_hours
                                                }
                                                onChange={(e) =>
                                                    setFormData({
                                                        ...formData,
                                                        expires_in_hours:
                                                            e.target.value,
                                                    })
                                                }
                                                placeholder="Hours until expiration"
                                                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            />
                                        </div>
                                    )}
                                </div>

                                <div className="flex gap-3">
                                    <button
                                        type="submit"
                                        disabled={isAdding}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {isAdding ? "Adding..." : "Add IP"}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowForm(false)}
                                        className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Whitelist Table */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        {isLoading ? (
                            <div className="p-6 text-center text-gray-600 dark:text-gray-400">
                                Loading...
                            </div>
                        ) : whitelist.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                IP Address
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Reason
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Description
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Expires
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {whitelist.map((item) => (
                                            <tr key={item.id}>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white font-mono">
                                                    {item.ip_address}
                                                    {item.ip_range && (
                                                        <span className="block text-xs text-gray-600 dark:text-gray-400">
                                                            {item.ip_range}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                                                        {item.reason}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                    {item.description || "-"}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    {item.is_permanent ? (
                                                        <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                                            Permanent
                                                        </span>
                                                    ) : isExpired(
                                                          item.expires_at
                                                      ) ? (
                                                        <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                                            Expired
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                                            Temporary
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {item.expires_at
                                                        ? new Date(
                                                              item.expires_at
                                                          ).toLocaleDateString()
                                                        : "Never"}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                    <button
                                                        onClick={() =>
                                                            handleRemoveIp(
                                                                item.id
                                                            )
                                                        }
                                                        className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                                                    >
                                                        <TrashIcon className="w-5 h-5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="p-6 text-center text-gray-600 dark:text-gray-400">
                                No whitelisted IPs yet
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
