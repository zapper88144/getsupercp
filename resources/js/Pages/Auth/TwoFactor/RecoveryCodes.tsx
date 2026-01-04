import PrimaryButton from "@/Components/PrimaryButton";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link } from "@inertiajs/react";
import {
    ShieldExclamationIcon,
    ClipboardDocumentIcon,
} from "@heroicons/react/24/outline";
import { useState } from "react";

export default function RecoveryCodes({
    recoveryCodes,
}: {
    recoveryCodes: string[];
}) {
    const [copied, setCopied] = useState(false);

    const breadcrumbs = [
        { title: "Profile", href: route("profile.edit") },
        { title: "Recovery Codes" },
    ];

    const copyToClipboard = () => {
        navigator.clipboard.writeText(recoveryCodes.join("\n"));
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={breadcrumbs}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Two-Factor Recovery Codes
                </h2>
            }
        >
            <Head title="Recovery Codes" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="p-6">
                            <div className="mb-6 text-center">
                                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/20">
                                    <ShieldExclamationIcon className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                </div>
                                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Save your recovery codes
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Store these recovery codes in a secure
                                    password manager. They can be used to
                                    recover access to your account if your
                                    two-factor authentication device is lost.
                                </p>
                            </div>

                            <div className="space-y-6">
                                <div className="grid grid-cols-1 gap-2 rounded-lg bg-gray-50 p-4 font-mono text-sm dark:bg-gray-800/50">
                                    {recoveryCodes.map((code, index) => (
                                        <div
                                            key={index}
                                            className="flex items-center justify-between text-gray-900 dark:text-white"
                                        >
                                            <span>{code}</span>
                                        </div>
                                    ))}
                                </div>

                                <div className="flex flex-col gap-3">
                                    <button
                                        onClick={copyToClipboard}
                                        className="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        <ClipboardDocumentIcon className="h-4 w-4" />
                                        {copied
                                            ? "Copied!"
                                            : "Copy to Clipboard"}
                                    </button>

                                    <Link href={route("dashboard")}>
                                        <PrimaryButton className="w-full justify-center py-3 text-base">
                                            I have saved these codes
                                        </PrimaryButton>
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
