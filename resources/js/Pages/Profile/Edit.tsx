import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { 
    UserCircleIcon, 
    KeyIcon, 
    ExclamationTriangleIcon 
} from '@heroicons/react/24/outline';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Profile Settings
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    {/* Profile Information */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-indigo-50 p-2 dark:bg-indigo-900/20">
                                    <UserCircleIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                    Profile Information
                                </h3>
                            </div>
                        </div>
                        <div className="p-6">
                            <UpdateProfileInformationForm
                                mustVerifyEmail={mustVerifyEmail}
                                status={status}
                                className="max-w-xl"
                            />
                        </div>
                    </div>

                    {/* Update Password */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-green-50 p-2 dark:bg-green-900/20">
                                    <KeyIcon className="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                    Security
                                </h3>
                            </div>
                        </div>
                        <div className="p-6">
                            <UpdatePasswordForm className="max-w-xl" />
                        </div>
                    </div>

                    {/* Delete Account */}
                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                        <div className="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-red-50 p-2 dark:bg-red-900/20">
                                    <ExclamationTriangleIcon className="h-6 w-6 text-red-600 dark:text-red-400" />
                                </div>
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                    Danger Zone
                                </h3>
                            </div>
                        </div>
                        <div className="p-6">
                            <DeleteUserForm className="max-w-xl" />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

