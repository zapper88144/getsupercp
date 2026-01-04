import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler } from 'react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

interface Props {
  roles: string[];
  statuses: string[];
}

export default function UserCreate({ roles, statuses }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
    status: 'active',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('admin.users.store'));
  };

  return (
    <AuthenticatedLayout
      header={
        <div className="flex items-center gap-4">
          <a
            href={route('admin.users.index')}
            className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
          >
            <ArrowLeftIcon className="w-5 h-5 text-gray-600 dark:text-gray-400" />
          </a>
          <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Create New User
          </h2>
        </div>
      }
    >
      <Head title="Create User" />

      <div className="py-12">
        <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
          <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
            <form onSubmit={submit} className="space-y-6 p-6 sm:p-8">
              <div>
                <InputLabel htmlFor="name" value="Full Name" />
                <TextInput
                  id="name"
                  className="mt-1 block w-full"
                  value={data.name}
                  onChange={(e) => setData('name', e.target.value)}
                  required
                  isFocused
                  placeholder="John Doe"
                />
                <InputError message={errors.name} className="mt-2" />
              </div>

              <div>
                <InputLabel htmlFor="email" value="Email Address" />
                <TextInput
                  id="email"
                  type="email"
                  className="mt-1 block w-full"
                  value={data.email}
                  onChange={(e) => setData('email', e.target.value)}
                  required
                  placeholder="john@example.com"
                />
                <InputError message={errors.email} className="mt-2" />
              </div>

              <div>
                <InputLabel htmlFor="password" value="Password" />
                <TextInput
                  id="password"
                  type="password"
                  className="mt-1 block w-full"
                  value={data.password}
                  onChange={(e) => setData('password', e.target.value)}
                  required
                  placeholder="••••••••"
                />
                <InputError message={errors.password} className="mt-2" />
              </div>

              <div>
                <InputLabel htmlFor="password_confirmation" value="Confirm Password" />
                <TextInput
                  id="password_confirmation"
                  type="password"
                  className="mt-1 block w-full"
                  value={data.password_confirmation}
                  onChange={(e) => setData('password_confirmation', e.target.value)}
                  required
                  placeholder="••••••••"
                />
                <InputError message={errors.password_confirmation} className="mt-2" />
              </div>

              <div>
                <InputLabel htmlFor="role" value="Role" />
                <select
                  id="role"
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                  value={data.role}
                  onChange={(e) => setData('role', e.target.value)}
                >
                  {roles.map((role) => (
                    <option key={role} value={role}>
                      {role.charAt(0).toUpperCase() + role.slice(1)}
                    </option>
                  ))}
                </select>
                <InputError message={errors.role} className="mt-2" />
              </div>

              <div>
                <InputLabel htmlFor="status" value="Status" />
                <select
                  id="status"
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                  value={data.status}
                  onChange={(e) => setData('status', e.target.value)}
                >
                  {statuses.map((status) => (
                    <option key={status} value={status}>
                      {status.charAt(0).toUpperCase() + status.slice(1)}
                    </option>
                  ))}
                </select>
                <InputError message={errors.status} className="mt-2" />
              </div>

              <div className="flex items-center gap-4 pt-4">
                <PrimaryButton disabled={processing}>Create User</PrimaryButton>
                <SecondaryButton
                  onClick={() => window.history.back()}
                >
                  Cancel
                </SecondaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
