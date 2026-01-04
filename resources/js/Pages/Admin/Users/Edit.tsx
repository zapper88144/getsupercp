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
  is_admin: boolean;
  phone: string | null;
  notes: string | null;
}

interface Props {
  user: User;
  roles: string[];
  statuses: string[];
}

export default function UserEdit({ user, roles, statuses }: Props) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const breadcrumbs = [
    { title: 'User Management', url: route('admin.users.index') },
    { title: `Edit: ${user.name}` },
  ];

  const { data, setData, patch, processing, errors } = useForm({
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    role: user.role,
    status: user.status,
    is_admin: user.is_admin,
    phone: user.phone || '',
    notes: user.notes || '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    patch(route('admin.users.update', user.id), {
      onSuccess: () => {
        setData('password', '');
        setData('password_confirmation', '');
      },
    });
  };

  const deleteUser = () => {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
      router.delete(route('admin.users.destroy', user.id));
    }
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={breadcrumbs}
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
        <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
          <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
            <form onSubmit={submit} className="p-6 sm:p-8 space-y-8">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Basic Info Section */}
                <div className="space-y-6">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">
                    Basic Information
                  </h3>
                  
                  <div>
                    <InputLabel htmlFor="name" value="Full Name" />
                    <TextInput
                      id="name"
                      name="name"
                      value={data.name}
                      className="mt-1 block w-full"
                      autoComplete="name"
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
                      name="email"
                      value={data.email}
                      className="mt-1 block w-full"
                      autoComplete="username"
                      onChange={(e) => setData('email', e.target.value)}
                      required
                    />
                    <InputError message={errors.email} className="mt-2" />
                  </div>

                  <div>
                    <InputLabel htmlFor="phone" value="Phone Number (Optional)" />
                    <TextInput
                      id="phone"
                      type="tel"
                      name="phone"
                      value={data.phone}
                      className="mt-1 block w-full"
                      onChange={(e) => setData('phone', e.target.value)}
                    />
                    <InputError message={errors.phone} className="mt-2" />
                  </div>

                  <div className="pt-4 border-t dark:border-gray-700">
                    <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-4">
                      Change Password (Leave blank to keep current)
                    </h4>
                    <div className="space-y-4">
                      <div>
                        <InputLabel htmlFor="password" value="New Password" />
                        <TextInput
                          id="password"
                          type="password"
                          name="password"
                          value={data.password}
                          className="mt-1 block w-full"
                          autoComplete="new-password"
                          onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} className="mt-2" />
                      </div>

                      <div>
                        <InputLabel htmlFor="password_confirmation" value="Confirm New Password" />
                        <TextInput
                          id="password_confirmation"
                          type="password"
                          name="password_confirmation"
                          value={data.password_confirmation}
                          className="mt-1 block w-full"
                          autoComplete="new-password"
                          onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                        <InputError message={errors.password_confirmation} className="mt-2" />
                      </div>
                    </div>
                  </div>
                </div>

                {/* Role & Status Section */}
                <div className="space-y-6">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">
                    Access & Status
                  </h3>

                  <div>
                    <InputLabel htmlFor="role" value="User Role" />
                    <select
                      id="role"
                      name="role"
                      value={data.role}
                      className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
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
                    <InputLabel htmlFor="status" value="Account Status" />
                    <select
                      id="status"
                      name="status"
                      value={data.status}
                      className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
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

                  <div className="flex items-center gap-2 pt-2">
                    <input
                      type="checkbox"
                      id="is_admin"
                      name="is_admin"
                      checked={data.is_admin}
                      className="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                      onChange={(e) => setData('is_admin', e.target.checked)}
                    />
                    <InputLabel htmlFor="is_admin" value="Grant Administrative Access" />
                    <InputError message={errors.is_admin} className="mt-2" />
                  </div>
                </div>
              </div>

              <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">
                  Additional Notes
                </h3>

                <div>
                  <InputLabel htmlFor="notes" value="Admin Notes" />
                  <textarea
                    id="notes"
                    name="notes"
                    value={data.notes}
                    rows={4}
                    className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    onChange={(e) => setData('notes', e.target.value)}
                    placeholder="Internal notes about this user..."
                  />
                  <InputError message={errors.notes} className="mt-2" />
                </div>
              </div>

              <div className="flex items-center justify-end gap-4 pt-6 border-t dark:border-gray-700">
                <SecondaryButton
                  onClick={() => window.history.back()}
                >
                  Cancel
                </SecondaryButton>
                <PrimaryButton disabled={processing}>
                  Save Changes
                </PrimaryButton>
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
