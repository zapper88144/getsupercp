import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm, Link } from "@inertiajs/react";
import {
    EnvelopeIcon,
    KeyIcon,
    ChartBarIcon,
    ArrowLeftIcon,
    ShieldCheckIcon,
    ClockIcon,
    CheckCircleIcon,
} from "@heroicons/react/24/outline";

interface EmailAccount {
    id: number;
    email: string;
    quota_mb: number;
    status: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    account: EmailAccount;
}

export default function Show({ account }: Props) {
    const { data, setData, patch, processing, errors, recentlySuccessful } =
        useForm({
            password: "",
            quota_mb: account.quota_mb,
        });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route("email-accounts.update", account.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Link
                        href={route("email-accounts.index")}
                        className="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    >
                        <ArrowLeftIcon className="h-5 w-5" />
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Email Account: {account.email}
                    </h2>
                </div>
            }
            breadcrumbs={[
                {
                    title: "Email Accounts",
                    url: route("email-accounts.index"),
                },
                { title: account.email },
            ]}
        >
            <Head title={`Email Account - ${account.email}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        {/* Account Details */}
                        <div className="lg:col-span-1">
                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <div className="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                        Account Information
                                    </h3>
                                </div>
                                <div className="p-6 space-y-6">
                                    <div>
                                        <p className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Email Address
                                        </p>
                                        <div className="mt-1 flex items-center gap-2 text-gray-900 dark:text-white">
                                            <EnvelopeIcon className="h-5 w-5 text-indigo-500" />
                                            <span className="font-semibold">
                                                {account.email}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Status
                                        </p>
                                        <div className="mt-1">
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                    account.status === "active"
                                                        ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                                        : "bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400"
                                                }`}
                                            >
                                                <ShieldCheckIcon className="h-3.5 w-3.5" />
                                                {account.status.toUpperCase()}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Created At
                                        </p>
                                        <div className="mt-1 flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                            <ClockIcon className="h-5 w-5 text-gray-400" />
                                            <span>
                                                {new Date(
                                                    account.created_at
                                                ).toLocaleString()}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Settings Form */}
                        <div className="lg:col-span-2">
                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <div className="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                        Account Settings
                                    </h3>
                                </div>
                                <form onSubmit={submit} className="p-6">
                                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                New Password
                                            </label>
                                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Leave blank to keep current
                                                password.
                                            </p>
                                            <div className="mt-2 relative">
                                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <KeyIcon className="h-5 w-5 text-gray-400" />
                                                </div>
                                                <input
                                                    type="password"
                                                    value={data.password}
                                                    onChange={(e) =>
                                                        setData(
                                                            "password",
                                                            e.target.value
                                                        )
                                                    }
                                                    className="block w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    placeholder="********"
                                                />
                                            </div>
                                            {errors.password && (
                                                <p className="mt-1 text-sm text-rose-600">
                                                    {errors.password}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Quota (MB)
                                            </label>
                                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Maximum storage allowed for this
                                                account.
                                            </p>
                                            <div className="mt-2 relative">
                                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <ChartBarIcon className="h-5 w-5 text-gray-400" />
                                                </div>
                                                <input
                                                    type="number"
                                                    value={data.quota_mb}
                                                    onChange={(e) =>
                                                        setData(
                                                            "quota_mb",
                                                            parseInt(
                                                                e.target.value
                                                            )
                                                        )
                                                    }
                                                    className="block w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    min="256"
                                                    max="102400"
                                                />
                                            </div>
                                            {errors.quota_mb && (
                                                <p className="mt-1 text-sm text-rose-600">
                                                    {errors.quota_mb}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="mt-8 flex items-center justify-between border-t border-gray-100 pt-6 dark:border-gray-700">
                                        <div className="flex items-center gap-2">
                                            {recentlySuccessful && (
                                                <div className="flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                    <CheckCircleIcon className="h-5 w-5" />
                                                    Settings saved successfully
                                                </div>
                                            )}
                                        </div>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-gray-900"
                                        >
                                            {processing ? (
                                                <ArrowLeftIcon className="h-4 w-4 animate-spin" />
                                            ) : (
                                                "Save Changes"
                                            )}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
