import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm, router } from "@inertiajs/react";
import PrimaryButton from "@/Components/PrimaryButton";
import DangerButton from "@/Components/DangerButton";
import SecondaryButton from "@/Components/SecondaryButton";
import TextInput from "@/Components/TextInput";
import InputLabel from "@/Components/InputLabel";
import InputError from "@/Components/InputError";
import Pagination from "@/Components/Pagination";
import { FormEventHandler, useState, useEffect } from "react";
import {
    UserIcon,
    FolderIcon,
    TrashIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    CalendarIcon,
    KeyIcon,
    ShieldCheckIcon,
} from "@heroicons/react/24/outline";

interface FtpUser {
    id: number;
    username: string;
    homedir: string;
    created_at: string;
}

interface Props {
    ftpUsers: {
        data: FtpUser[];
        links: any[];
    };
    filters: {
        search?: string;
    };
}

export default function Index({ ftpUsers, filters }: Props) {
    const [searchQuery, setSearchQuery] = useState(filters.search || "");
    const [isAdding, setIsAdding] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchQuery !== (filters.search || "")) {
                router.get(
                    route("ftp-users.index"),
                    { search: searchQuery },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    }
                );
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [searchQuery]);

    const { data, setData, post, processing, errors, reset } = useForm({
        username: "",
        password: "",
        homedir: "/home/super/getsupercp/storage/app/public/www",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("ftp-users.store"), {
            onSuccess: () => {
                reset();
                setIsAdding(false);
            },
        });
    };

    const deleteFtpUser = (id: number) => {
        if (confirm("Are you sure you want to delete this FTP user?")) {
            router.delete(route("ftp-users.destroy", id));
        }
    };

    const filteredUsers = ftpUsers.data;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        FTP Users
                    </h2>
                    <PrimaryButton
                        onClick={() => setIsAdding(!isAdding)}
                        className="flex items-center gap-2"
                    >
                        {isAdding ? (
                            <XMarkIcon className="w-5 h-5" />
                        ) : (
                            <PlusIcon className="w-5 h-5" />
                        )}
                        {isAdding ? "Cancel" : "Create FTP User"}
                    </PrimaryButton>
                </div>
            }
            breadcrumbs={[{ title: "FTP Users" }]}
        >
            <Head title="FTP Users" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    {isAdding && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800 border-l-4 border-indigo-500">
                            <section className="max-w-xl">
                                <header>
                                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        Create New FTP User
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Add a new user with FTP access to a
                                        specific directory.
                                    </p>
                                </header>

                                <form
                                    onSubmit={submit}
                                    className="mt-6 space-y-6"
                                >
                                    <div>
                                        <InputLabel
                                            htmlFor="username"
                                            value="Username"
                                        />
                                        <TextInput
                                            id="username"
                                            className="mt-1 block w-full"
                                            value={data.username}
                                            onChange={(e) =>
                                                setData(
                                                    "username",
                                                    e.target.value
                                                )
                                            }
                                            required
                                            isFocused
                                            placeholder="ftp_user"
                                        />
                                        <InputError
                                            message={errors.username}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel
                                            htmlFor="password"
                                            value="Password"
                                        />
                                        <TextInput
                                            id="password"
                                            type="password"
                                            className="mt-1 block w-full"
                                            value={data.password}
                                            onChange={(e) =>
                                                setData(
                                                    "password",
                                                    e.target.value
                                                )
                                            }
                                            required
                                            placeholder="********"
                                        />
                                        <InputError
                                            message={errors.password}
                                            className="mt-2"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel
                                            htmlFor="homedir"
                                            value="Home Directory"
                                        />
                                        <TextInput
                                            id="homedir"
                                            className="mt-1 block w-full"
                                            value={data.homedir}
                                            onChange={(e) =>
                                                setData(
                                                    "homedir",
                                                    e.target.value
                                                )
                                            }
                                            required
                                            placeholder="/home/super/getsupercp/storage/app/public/www"
                                        />
                                        <InputError
                                            message={errors.homedir}
                                            className="mt-2"
                                        />
                                        <p className="mt-1 text-xs text-gray-500">
                                            The directory this user will be
                                            restricted to.
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <PrimaryButton disabled={processing}>
                                            Create FTP User
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </section>
                        </div>
                    )}

                    <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Manage FTP Users
                                </h3>
                                <div className="relative w-full md:w-64">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <TextInput
                                        type="text"
                                        className="block w-full pl-10 pr-3 py-2"
                                        placeholder="Search users..."
                                        value={searchQuery}
                                        onChange={(e) =>
                                            setSearchQuery(e.target.value)
                                        }
                                    />
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-gray-700">
                                            <th className="py-3 px-4 font-semibold text-sm">
                                                User
                                            </th>
                                            <th className="py-3 px-4 font-semibold text-sm">
                                                Home Directory
                                            </th>
                                            <th className="py-3 px-4 font-semibold text-sm">
                                                Created
                                            </th>
                                            <th className="py-3 px-4 font-semibold text-sm text-right">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredUsers.map((user) => (
                                            <tr
                                                key={user.id}
                                                className="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors"
                                            >
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                                            <UserIcon className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                                        </div>
                                                        <div className="font-bold text-gray-900 dark:text-white">
                                                            {user.username}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <FolderIcon className="w-4 h-4" />
                                                        <span
                                                            className="truncate max-w-xs"
                                                            title={user.homedir}
                                                        >
                                                            {user.homedir}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-2 text-sm text-gray-500">
                                                        <CalendarIcon className="w-4 h-4" />
                                                        {new Date(
                                                            user.created_at
                                                        ).toLocaleDateString()}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4 text-right">
                                                    <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <button
                                                            onClick={() =>
                                                                deleteFtpUser(
                                                                    user.id
                                                                )
                                                            }
                                                            className="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md text-red-600"
                                                            title="Delete FTP User"
                                                        >
                                                            <TrashIcon className="w-5 h-5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                        {filteredUsers.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={4}
                                                    className="py-12 text-center text-gray-500"
                                                >
                                                    <div className="flex flex-col items-center gap-2">
                                                        <UserIcon className="w-12 h-12 text-gray-300" />
                                                        <span>
                                                            {searchQuery
                                                                ? `No users matching "${searchQuery}"`
                                                                : "No FTP users found."}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination links={ftpUsers.links} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
