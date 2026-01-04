import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";
import { ShieldCheckIcon } from "@heroicons/react/24/outline";

export default function Setup({
    secret,
    qrCodeSvg,
}: {
    secret: string;
    qrCodeSvg: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: "",
    });

    const breadcrumbs = [
        { title: "Profile", href: route("profile.edit") },
        { title: "Two-Factor Setup" },
    ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("two-factor.store"), {
            onFinish: () => reset("code"),
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={breadcrumbs}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Two-Factor Authentication Setup
                </h2>
            }
        >
            <Head title="Two-Factor Authentication Setup" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="p-6">
                            <div className="mb-6 text-center">
                                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/20">
                                    <ShieldCheckIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Secure your account
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Two-factor authentication adds an extra
                                    layer of security to your account.
                                </p>
                            </div>

                            <div className="space-y-6">
                                <div className="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                                    <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                        To enable two-factor authentication,
                                        scan the following QR code using your
                                        authenticator application (e.g., Google
                                        Authenticator, Authy).
                                    </p>

                                    <div className="flex justify-center bg-white p-4 rounded-lg inline-block mx-auto">
                                        <div
                                            dangerouslySetInnerHTML={{
                                                __html: qrCodeSvg,
                                            }}
                                        />
                                    </div>

                                    <div className="mt-4">
                                        <p className="text-xs font-medium text-gray-500 dark:text-gray-500 uppercase tracking-wider">
                                            Or enter this code manually:
                                        </p>
                                        <p className="mt-1 font-mono text-sm font-bold text-gray-900 dark:text-white break-all">
                                            {secret}
                                        </p>
                                    </div>
                                </div>

                                <form onSubmit={submit} className="space-y-4">
                                    <div>
                                        <InputLabel
                                            htmlFor="code"
                                            value="Verification Code"
                                        />

                                        <TextInput
                                            id="code"
                                            type="text"
                                            name="code"
                                            value={data.code}
                                            className="mt-1 block w-full text-center text-2xl tracking-widest"
                                            autoComplete="one-time-code"
                                            isFocused={true}
                                            onChange={(e) =>
                                                setData("code", e.target.value)
                                            }
                                            placeholder="000000"
                                            maxLength={6}
                                        />

                                        <InputError
                                            message={errors.code}
                                            className="mt-2"
                                        />
                                    </div>

                                    <PrimaryButton
                                        className="w-full justify-center py-3 text-base"
                                        disabled={processing}
                                    >
                                        Enable Two-Factor Authentication
                                    </PrimaryButton>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
