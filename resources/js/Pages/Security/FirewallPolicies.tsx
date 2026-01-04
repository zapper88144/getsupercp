import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { CheckIcon, XMarkIcon } from "@heroicons/react/24/outline";

interface Policy {
    id: number;
    name: string;
    description: string;
    enable_firewall: boolean;
    enable_brute_force_protection: boolean;
    failed_login_threshold: number;
    lockout_duration_minutes: number;
    enable_ip_filtering: boolean;
    enable_ssl_enforcement: boolean;
    enable_cloudflare_security: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export default function FirewallPolicies() {
    const [policy, setPolicy] = useState<Policy | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [activeTab, setActiveTab] = useState<
        "firewall" | "brute-force" | "ssl" | "headers"
    >("firewall");
    const [editingHeaders, setEditingHeaders] = useState(false);
    const [headers, setHeaders] = useState<Record<string, string>>({});
    const [formData, setFormData] = useState({
        failed_login_threshold: 5,
        lockout_duration_minutes: 60,
    });

    useEffect(() => {
        fetchPolicy();
    }, []);

    const fetchPolicy = async () => {
        try {
            const response = await fetch("/api/security/policy");
            if (response.ok) {
                const data = await response.json();
                setPolicy(data.policy);
                setFormData({
                    failed_login_threshold: data.policy.failed_login_threshold,
                    lockout_duration_minutes:
                        data.policy.lockout_duration_minutes,
                });
                if (data.policy.security_headers) {
                    setHeaders(data.policy.security_headers);
                }
            }
        } catch (error) {
            console.error("Error fetching policy:", error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleToggle = async (field: string, enabled: boolean) => {
        if (!policy) return;

        const endpoint =
            field === "enable_firewall"
                ? "firewall"
                : field === "enable_brute_force_protection"
                ? "brute-force"
                : field === "enable_ssl_enforcement"
                ? "ssl"
                : null;

        if (!endpoint) return;

        try {
            const response = await fetch(
                `/api/security/policy/${endpoint}/toggle`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                    body: JSON.stringify({ enabled }),
                }
            );

            if (response.ok) {
                fetchPolicy();
            }
        } catch (error) {
            console.error("Error updating policy:", error);
        }
    };

    const handleSaveThresholds = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSaving(true);

        try {
            const response = await fetch("/api/security/policy", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify(formData),
            });

            if (response.ok) {
                fetchPolicy();
                alert("Policy updated successfully");
            }
        } catch (error) {
            console.error("Error saving policy:", error);
        } finally {
            setIsSaving(false);
        }
    };

    const handleSaveHeaders = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSaving(true);

        try {
            const response = await fetch("/api/security/policy/headers", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
                body: JSON.stringify({ headers }),
            });

            if (response.ok) {
                setEditingHeaders(false);
                fetchPolicy();
                alert("Security headers updated successfully");
            }
        } catch (error) {
            console.error("Error saving headers:", error);
        } finally {
            setIsSaving(false);
        }
    };

    const ToggleSwitch = ({
        label,
        description,
        enabled,
        onChange,
        disabled = false,
    }: {
        label: string;
        description: string;
        enabled: boolean;
        onChange: (val: boolean) => void;
        disabled?: boolean;
    }) => (
        <div className="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
            <div>
                <p className="font-medium text-gray-900 dark:text-white">
                    {label}
                </p>
                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {description}
                </p>
            </div>
            <button
                onClick={() => onChange(!enabled)}
                disabled={disabled}
                className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors disabled:opacity-50 ${
                    enabled ? "bg-blue-600" : "bg-gray-200 dark:bg-gray-600"
                }`}
            >
                <span
                    className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform ${
                        enabled ? "translate-x-5" : "translate-x-0.5"
                    }`}
                />
            </button>
        </div>
    );

    const breadcrumbs = [
        { title: "Security", href: "/security/dashboard" },
        { title: "Firewall Policies" },
    ];

    if (isLoading) {
        return (
            <AuthenticatedLayout
                breadcrumbs={breadcrumbs}
                header={
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Firewall Policies
                    </h2>
                }
            >
                <Head title="Firewall Policies" />
                <div className="py-12">
                    <div className="mx-auto max-w-7xl px-6 lg:px-8">
                        <p className="text-center text-gray-600 dark:text-gray-400">
                            Loading policies...
                        </p>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            breadcrumbs={breadcrumbs}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Firewall Policies
                </h2>
            }
        >
            <Head title="Firewall Policies" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    {/* Tabs */}
                    <div className="border-b border-gray-200 dark:border-gray-700">
                        <nav className="flex space-x-8" aria-label="Tabs">
                            {["firewall", "brute-force", "ssl", "headers"].map(
                                (tab) => (
                                    <button
                                        key={tab}
                                        onClick={() => setActiveTab(tab as any)}
                                        className={`py-2 px-1 border-b-2 font-medium text-sm capitalize ${
                                            activeTab === tab
                                                ? "border-blue-500 text-blue-600 dark:text-blue-400"
                                                : "border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600"
                                        }`}
                                    >
                                        {tab.replace("-", " ")}
                                    </button>
                                )
                            )}
                        </nav>
                    </div>

                    {/* Firewall Tab */}
                    {activeTab === "firewall" && policy && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                Firewall Settings
                            </h3>
                            <ToggleSwitch
                                label="Enable Firewall"
                                description="Block malicious traffic and DDoS attacks"
                                enabled={policy.enable_firewall}
                                onChange={(val) =>
                                    handleToggle("enable_firewall", val)
                                }
                            />
                            <ToggleSwitch
                                label="Enable IP Filtering"
                                description="Only allow traffic from whitelisted IPs"
                                enabled={policy.enable_ip_filtering}
                                onChange={(val) =>
                                    handleToggle("enable_ip_filtering", val)
                                }
                            />
                            <ToggleSwitch
                                label="Enable Cloudflare Security"
                                description="Use Cloudflare's DDoS and WAF protection"
                                enabled={policy.enable_cloudflare_security}
                                onChange={(val) =>
                                    handleToggle(
                                        "enable_cloudflare_security",
                                        val
                                    )
                                }
                            />
                        </div>
                    )}

                    {/* Brute Force Tab */}
                    {activeTab === "brute-force" && policy && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                Brute Force Protection
                            </h3>
                            <ToggleSwitch
                                label="Enable Brute Force Protection"
                                description="Block IPs after multiple failed login attempts"
                                enabled={policy.enable_brute_force_protection}
                                onChange={(val) =>
                                    handleToggle(
                                        "enable_brute_force_protection",
                                        val
                                    )
                                }
                            />

                            {policy.enable_brute_force_protection && (
                                <form
                                    onSubmit={handleSaveThresholds}
                                    className="mt-6 space-y-6"
                                >
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Failed Login Threshold
                                        </label>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            Number of failed attempts before IP
                                            is blocked (default: 5)
                                        </p>
                                        <input
                                            type="number"
                                            min="1"
                                            max="100"
                                            value={
                                                formData.failed_login_threshold
                                            }
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    failed_login_threshold:
                                                        parseInt(
                                                            e.target.value
                                                        ),
                                                })
                                            }
                                            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Lockout Duration (Minutes)
                                        </label>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            How long to block an IP after
                                            exceeding threshold (default: 60)
                                        </p>
                                        <input
                                            type="number"
                                            min="1"
                                            max="1440"
                                            value={
                                                formData.lockout_duration_minutes
                                            }
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    lockout_duration_minutes:
                                                        parseInt(
                                                            e.target.value
                                                        ),
                                                })
                                            }
                                            className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={isSaving}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {isSaving
                                            ? "Saving..."
                                            : "Save Settings"}
                                    </button>
                                </form>
                            )}
                        </div>
                    )}

                    {/* SSL Tab */}
                    {activeTab === "ssl" && policy && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                                SSL/TLS Settings
                            </h3>
                            <ToggleSwitch
                                label="Force HTTPS"
                                description="Redirect all HTTP traffic to HTTPS"
                                enabled={policy.enable_ssl_enforcement}
                                onChange={(val) =>
                                    handleToggle("enable_ssl_enforcement", val)
                                }
                            />
                        </div>
                    )}

                    {/* Security Headers Tab */}
                    {activeTab === "headers" && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="flex justify-between items-center mb-6">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Security Headers
                                </h3>
                                <button
                                    onClick={() =>
                                        setEditingHeaders(!editingHeaders)
                                    }
                                    className="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium"
                                >
                                    {editingHeaders ? "Cancel" : "Edit"}
                                </button>
                            </div>

                            {editingHeaders ? (
                                <form
                                    onSubmit={handleSaveHeaders}
                                    className="space-y-4"
                                >
                                    {Object.entries(headers).map(
                                        ([key, value]) => (
                                            <div key={key}>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    {key}
                                                </label>
                                                <textarea
                                                    value={value}
                                                    onChange={(e) =>
                                                        setHeaders({
                                                            ...headers,
                                                            [key]: e.target
                                                                .value,
                                                        })
                                                    }
                                                    rows={2}
                                                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                                                />
                                            </div>
                                        )
                                    )}
                                    <button
                                        type="submit"
                                        disabled={isSaving}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {isSaving
                                            ? "Saving..."
                                            : "Save Headers"}
                                    </button>
                                </form>
                            ) : (
                                <div className="space-y-4">
                                    {Object.entries(headers).map(
                                        ([key, value]) => (
                                            <div
                                                key={key}
                                                className="border-l-4 border-blue-500 pl-4 py-2"
                                            >
                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {key}
                                                </p>
                                                <p className="text-xs text-gray-600 dark:text-gray-400 font-mono mt-1">
                                                    {value}
                                                </p>
                                            </div>
                                        )
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
