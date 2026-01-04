import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import InputLabel from "@/Components/InputLabel";
import InputError from "@/Components/InputError";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";
import { ShieldCheckIcon } from "@heroicons/react/24/outline";

export default function Status() {
    const {
        data,
        setData,
        delete: destroy,
        processing,
        errors,
        reset,
    } = useForm({
        password: "",
    });

    const breadcrumbs = [
        { title: "Profile", href: route("profile.edit") },
        { title: "Two-Factor Status" },
    ];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route("two-factor.destroy"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={breadcrumbs}
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Two-Factor Authentication Status
                </h2>
            }
        >
            <Head title="Two-Factor Authentication Status" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="p-6">
                            <div className="mb-6 text-center">
                                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 dark:bg-green-900/20">
                                    <ShieldCheckIcon className="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Two-Factor Authentication is Enabled
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Your account is protected with an additional
                                    layer of security.
                                </p>
                            </div>

                            <div className="space-y-6">
                                <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
                                    <h3 className="text-sm font-bold text-red-800 dark:text-red-400">
                                        Disable Two-Factor Authentication
                                    </h3>
                                    <p className="mt-1 text-sm text-red-700 dark:text-red-300">
                                        Disabling two-factor authentication will
                                        make your account less secure. You will
                                        be asked for your password to confirm
                                        this action.
                                    </p>

                                    <form
                                        onSubmit={submit}
                                        className="mt-4 space-y-4"
                                    >
                                        <div>
                                            <InputLabel
                                                htmlFor="password"
                                                value="Confirm Password"
                                            />
                                            <TextInput
                                                id="password"
                                                type="password"
                                                name="password"
                                                value={data.password}
                                                className="mt-1 block w-full"
                                                onChange={(e) =>
                                                    setData(
                                                        "password",
                                                        e.target.value
                                                    )
                                                }
                                                placeholder="••••••••"
                                            />
                                            <InputError
                                                message={errors.password}
                                                className="mt-2"
                                            />
                                        </div>

                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700 disabled:opacity-25"
                                        >
                                            Disable 2FA
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
