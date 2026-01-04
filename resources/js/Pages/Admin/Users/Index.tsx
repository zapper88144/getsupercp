import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { FormEventHandler, useState } from 'react';
import {
  UserIcon,
  EnvelopeIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  TrashIcon,
  PencilIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  XMarkIcon,
  CalendarIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/react/24/outline';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  status: string;
  is_admin: boolean;
  created_at: string;
}

interface PaginatedUsers {
  data: User[];
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
  current_page: number;
  last_page: number;
  total: number;
}

interface Props {
  users: PaginatedUsers;
  roles: string[];
  statuses: string[];
}

const roleColors: Record<string, { bg: string; text: string; darkBg: string; darkText: string }> = {
  'super-admin': { bg: 'bg-red-100', text: 'text-red-700', darkBg: 'dark:bg-red-900/30', darkText: 'dark:text-red-400' },
  'admin': { bg: 'bg-indigo-100', text: 'text-indigo-700', darkBg: 'dark:bg-indigo-900/30', darkText: 'dark:text-indigo-400' },
  'moderator': { bg: 'bg-purple-100', text: 'text-purple-700', darkBg: 'dark:bg-purple-900/30', darkText: 'dark:text-purple-400' },
  'user': { bg: 'bg-blue-100', text: 'text-blue-700', darkBg: 'dark:bg-blue-900/30', darkText: 'dark:text-blue-400' },
};

const statusColors: Record<string, { bg: string; text: string; darkBg: string; darkText: string; icon: typeof CheckCircleIcon }> = {
  'active': { bg: 'bg-green-100', text: 'text-green-700', darkBg: 'dark:bg-green-900/30', darkText: 'dark:text-green-400', icon: CheckCircleIcon },
  'suspended': { bg: 'bg-yellow-100', text: 'text-yellow-700', darkBg: 'dark:bg-yellow-900/30', darkText: 'dark:text-yellow-400', icon: XCircleIcon },
  'inactive': { bg: 'bg-gray-100', text: 'text-gray-700', darkBg: 'dark:bg-gray-900/30', darkText: 'dark:text-gray-400', icon: XCircleIcon },
};

export default function UserIndex({ users, roles, statuses }: Props) {
  const [searchQuery, setSearchQuery] = useState('');
  const [isAdding, setIsAdding] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
    status: 'active',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('admin.users.store'), {
      onSuccess: () => {
        reset();
        setIsAdding(false);
      },
    });
  };

  const deleteUser = (id: number) => {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
      router.delete(route('admin.users.destroy', id));
    }
  };

  const filteredUsers = users.data.filter(u =>
    u.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    u.email.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <AuthenticatedLayout
      header={
        <div className="flex justify-between items-center">
          <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            User Management
          </h2>
          <PrimaryButton
            onClick={() => setIsAdding(!isAdding)}
            className="flex items-center gap-2"
          >
            {isAdding ? <XMarkIcon className="w-5 h-5" /> : <PlusIcon className="w-5 h-5" />}
            {isAdding ? 'Cancel' : 'Create User'}
          </PrimaryButton>
        </div>
      }
    >
      <Head title="User Management" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
          {isAdding && (
            <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800 border-l-4 border-indigo-500">
              <section className="max-w-xl">
                <header>
                  <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Create New User
                  </h2>
                  <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Add a new user to the system with appropriate role and status.
                  </p>
                </header>

                <form onSubmit={submit} className="mt-6 space-y-6">
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

                  <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Create User</PrimaryButton>
                  </div>
                </form>
              </section>
            </div>
          )}

          <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
            <div className="p-6">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                  All Users
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
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                </div>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-gray-200 dark:border-gray-700">
                      <th className="py-3 px-4 font-semibold text-sm">User</th>
                      <th className="py-3 px-4 font-semibold text-sm">Role</th>
                      <th className="py-3 px-4 font-semibold text-sm">Status</th>
                      <th className="py-3 px-4 font-semibold text-sm">Joined</th>
                      <th className="py-3 px-4 font-semibold text-sm text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.map((user) => {
                      const roleColor = roleColors[user.role] || roleColors['user'];
                      const statusColor = statusColors[user.status] || statusColors['active'];
                      const StatusIcon = statusColor.icon;

                      return (
                        <tr key={user.id} className="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors">
                          <td className="py-4 px-4">
                            <div className="flex items-center gap-3">
                              <div className="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                <UserIcon className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                              </div>
                              <div>
                                <div className="font-bold text-gray-900 dark:text-white">
                                  {user.name}
                                  {user.is_admin && (
                                    <span className="ml-2 inline-block px-2 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-xs font-semibold rounded">ADMIN</span>
                                  )}
                                </div>
                                <div className="text-xs text-gray-500 flex items-center gap-1">
                                  <EnvelopeIcon className="w-3 h-3" />
                                  {user.email}
                                </div>
                              </div>
                            </div>
                          </td>
                          <td className="py-4 px-4">
                            <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${roleColor.bg} ${roleColor.text} ${roleColor.darkBg} ${roleColor.darkText}`}>
                              {user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                            </span>
                          </td>
                          <td className="py-4 px-4">
                            <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColor.bg} ${statusColor.text} ${statusColor.darkBg} ${statusColor.darkText}`}>
                              <span className={`h-1.5 w-1.5 rounded-full ${user.status === 'active' ? 'bg-green-600' : user.status === 'suspended' ? 'bg-yellow-600' : 'bg-gray-600'}`}></span>
                              {user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                            </span>
                          </td>
                          <td className="py-4 px-4">
                            <div className="flex items-center gap-2 text-sm text-gray-500">
                              <CalendarIcon className="w-4 h-4" />
                              {new Date(user.created_at).toLocaleDateString()}
                            </div>
                          </td>
                          <td className="py-4 px-4 text-right">
                            <div className="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                              <a
                                href={route('admin.users.show', user.id)}
                                className="p-2 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-md text-indigo-600 dark:text-indigo-400"
                                title="View"
                              >
                                <ShieldCheckIcon className="w-5 h-5" />
                              </a>
                              <a
                                href={route('admin.users.edit', user.id)}
                                className="p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-md text-blue-600 dark:text-blue-400"
                                title="Edit"
                              >
                                <PencilIcon className="w-5 h-5" />
                              </a>
                              <button
                                onClick={() => deleteUser(user.id)}
                                className="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md text-red-600"
                                title="Delete"
                              >
                                <TrashIcon className="w-5 h-5" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                    {filteredUsers.length === 0 && (
                      <tr>
                        <td colSpan={5} className="py-12 text-center text-gray-500">
                          <div className="flex flex-col items-center gap-2">
                            <UserIcon className="w-12 h-12 text-gray-300" />
                            <span>{searchQuery ? `No users matching "${searchQuery}"` : 'No users found.'}</span>
                          </div>
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              {/* Pagination */}
              {users.links.length > 3 && (
                <div className="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6">
                  <div className="flex flex-1 justify-between sm:hidden">
                    <Link
                      href={users.links[0].url || '#'}
                      className="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                      Previous
                    </Link>
                    <Link
                      href={users.links[users.links.length - 1].url || '#'}
                      className="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                      Next
                    </Link>
                  </div>
                  <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                      <p className="text-sm text-gray-700 dark:text-gray-400">
                        Showing <span className="font-medium">{(users.current_page - 1) * 15 + 1}</span> to{' '}
                        <span className="font-medium">
                          {Math.min(users.current_page * 15, users.total)}
                        </span>{' '}
                        of <span className="font-medium">{users.total}</span> results
                      </p>
                    </div>
                    <div>
                      <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        {users.links.map((link, i) => (
                          <Link
                            key={i}
                            href={link.url || '#'}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 ${
                              link.active
                                ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700'
                            } ${i === 0 ? 'rounded-l-md' : ''} ${
                              i === users.links.length - 1 ? 'rounded-r-md' : ''
                            }`}
                          />
                        ))}
                      </nav>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
