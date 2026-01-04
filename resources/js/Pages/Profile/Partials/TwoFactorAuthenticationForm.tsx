import PrimaryButton from "@/Components/PrimaryButton";
import { Link, usePage } from "@inertiajs/react";
import {
    ShieldCheckIcon,
    ShieldExclamationIcon,
} from "@heroicons/react/24/outline";

export default function TwoFactorAuthenticationForm({
    className = "",
}: {
    className?: string;
}) {
    const user = usePage().props.auth.user;
    const isEnabled = user.two_factor_enabled;

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Two-Factor Authentication
                </h2>

                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Add additional security to your account using two-factor
                    authentication.
                </p>
            </header>

            <div className="mt-6 space-y-6">
                <div className="flex items-center gap-4">
                    <div
                        className={`rounded-lg p-2 ${
                            isEnabled
                                ? "bg-green-50 dark:bg-green-900/20"
                                : "bg-amber-50 dark:bg-amber-900/20"
                        }`}
                    >
                        {isEnabled ? (
                            <ShieldCheckIcon className="h-6 w-6 text-green-600 dark:text-green-400" />
                        ) : (
                            <ShieldExclamationIcon className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        )}
                    </div>
                    <div>
                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Status: {isEnabled ? "Enabled" : "Disabled"}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {isEnabled
                                ? "Your account is protected with an additional layer of security."
                                : "We recommend enabling two-factor authentication for better security."}
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-4">
                    <Link href={route("two-factor.setup")}>
                        <PrimaryButton>
                            {isEnabled ? "Manage 2FA" : "Enable 2FA"}
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
        </section>
    );
}
