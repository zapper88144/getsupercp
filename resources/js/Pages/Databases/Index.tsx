import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState } from 'react';
import { 
    CircleStackIcon, 
    UserIcon, 
    KeyIcon, 
    TrashIcon, 
    PlusIcon, 
    MagnifyingGlassIcon, 
    XMarkIcon,
    CalendarIcon,
    ServerIcon
} from '@heroicons/react/24/outline';

interface Database {
    id: number;
    name: string;
    db_user: string;
    type: 'mysql' | 'postgres';
    created_at: string;
}

interface Props {
    databases: Database[];
}

export default function Index({ databases }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [isAdding, setIsAdding] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        db_user: '',
        db_password: '',
        type: 'mysql' as 'mysql' | 'postgres',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('databases.store'), {
            onSuccess: () => {
                reset();
                setIsAdding(false);
            },
        });
    };

    const deleteDatabase = (id: number) => {
        if (confirm('Are you sure you want to delete this database?')) {
            router.delete(route('databases.destroy', id));
        }
    };

    const filteredDatabases = databases.filter(db => 
        db.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        db.db_user.toLowerCase().includes(searchQuery.toLowerCase())
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Databases
                    </h2>
                    <PrimaryButton 
                        onClick={() => setIsAdding(!isAdding)}
                        className="flex items-center gap-2"
                    >
                        {isAdding ? <XMarkIcon className="w-5 h-5" /> : <PlusIcon className="w-5 h-5" />}
                        {isAdding ? 'Cancel' : 'Create Database'}
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Databases" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    
                    {isAdding && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800 border-l-4 border-indigo-500">
                            <section className="max-w-xl">
                                <header>
                                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        Create New Database
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Provision a new MySQL or PostgreSQL database.
                                    </p>
                                </header>

                                <form onSubmit={submit} className="mt-6 space-y-6">
                                    <div>
                                        <InputLabel htmlFor="name" value="Database Name" />
                                        <TextInput
                                            id="name"
                                            className="mt-1 block w-full"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            required
                                            isFocused
                                            placeholder="my_app_db"
                                        />
                                        <InputError message={errors.name} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="db_user" value="Database User" />
                                        <TextInput
                                            id="db_user"
                                            className="mt-1 block w-full"
                                            value={data.db_user}
                                            onChange={(e) => setData('db_user', e.target.value)}
                                            required
                                            placeholder="my_app_user"
                                        />
                                        <InputError message={errors.db_user} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="db_password" value="Database Password" />
                                        <TextInput
                                            id="db_password"
                                            type="password"
                                            className="mt-1 block w-full"
                                            value={data.db_password}
                                            onChange={(e) => setData('db_password', e.target.value)}
                                            required
                                            placeholder="********"
                                        />
                                        <InputError message={errors.db_password} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="type" value="Database Type" />
                                        <select
                                            id="type"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                            value={data.type}
                                            onChange={(e) => setData('type', e.target.value as 'mysql' | 'postgres')}
                                        >
                                            <option value="mysql">MySQL</option>
                                            <option value="postgres">PostgreSQL</option>
                                        </select>
                                        <InputError message={errors.type} className="mt-2" />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <PrimaryButton disabled={processing}>Create Database</PrimaryButton>
                                    </div>
                                </form>
                            </section>
                        </div>
                    )}

                    <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
                        <div className="p-6">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Manage Databases
                                </h3>
                                <div className="relative w-full md:w-64">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <TextInput
                                        type="text"
                                        className="block w-full pl-10 pr-3 py-2"
                                        placeholder="Search databases..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-gray-700">
                                            <th className="py-3 px-4 font-semibold text-sm">Database</th>
                                            <th className="py-3 px-4 font-semibold text-sm">User</th>
                                            <th className="py-3 px-4 font-semibold text-sm">Type</th>
                                            <th className="py-3 px-4 font-semibold text-sm">Created</th>
                                            <th className="py-3 px-4 font-semibold text-sm text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredDatabases.map((db) => (
                                            <tr key={db.id} className="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors">
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                                            <CircleStackIcon className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                                        </div>
                                                        <div className="font-bold text-gray-900 dark:text-white">{db.name}</div>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <UserIcon className="w-4 h-4" />
                                                        {db.db_user}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-2">
                                                        <ServerIcon className="w-4 h-4 text-gray-400" />
                                                        <span className={`px-2 py-0.5 rounded text-xs font-bold uppercase ${db.type === 'mysql' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'}`}>
                                                            {db.type}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4">
                                                    <div className="flex items-center gap-2 text-sm text-gray-500">
                                                        <CalendarIcon className="w-4 h-4" />
                                                        {new Date(db.created_at).toLocaleDateString()}
                                                    </div>
                                                </td>
                                                <td className="py-4 px-4 text-right">
                                                    <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <button 
                                                            onClick={() => deleteDatabase(db.id)}
                                                            className="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md text-red-600"
                                                            title="Delete Database"
                                                        >
                                                            <TrashIcon className="w-5 h-5" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                        {filteredDatabases.length === 0 && (
                                            <tr>
                                                <td colSpan={5} className="py-12 text-center text-gray-500">
                                                    <div className="flex flex-col items-center gap-2">
                                                        <CircleStackIcon className="w-12 h-12 text-gray-300" />
                                                        <span>{searchQuery ? `No databases matching "${searchQuery}"` : 'No databases found.'}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
