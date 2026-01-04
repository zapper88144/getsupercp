import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState } from 'react';
import { ArrowLeftIcon, TrashIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  status: string;
}

interface Props {
  user: User;
  roles: string[];
  statuses: string[];
}

export default function UserEdit({ user, roles, statuses }: Props) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const { data, setData, patch, processing, errors } = useForm({
    name: user.name,
    email: user.email,
    role: user.role,
    status: user.status,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    patch(route('admin.users.update', user.id));
  };

  const deleteUser = () => {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
      router.delete(route('admin.users.destroy', user.id));
    }
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
            Edit User: {user.name}
          </h2>
        </div>
      }
    >
      <Head title={`Edit: ${user.name}`} />

      <div className="py-12">
        <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
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
                />
                <InputError message={errors.email} className="mt-2" />
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
                <PrimaryButton disabled={processing}>Save Changes</PrimaryButton>
                <SecondaryButton
                  onClick={() => window.history.back()}
                >
                  Cancel
                </SecondaryButton>
              </div>
            </form>
          </div>

          <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 shadow sm:rounded-lg overflow-hidden">
            <div className="p-6 sm:p-8">
              <h3 className="text-lg font-medium text-red-900 dark:text-red-100 mb-2">
                Danger Zone
              </h3>
              <p className="text-sm text-red-700 dark:text-red-300 mb-4">
                This action cannot be undone. Please be certain before proceeding.
              </p>
              <DangerButton
                onClick={deleteUser}
                className="flex items-center gap-2"
              >
                <TrashIcon className="w-5 h-5" />
                Delete User
              </DangerButton>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
