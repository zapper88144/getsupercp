import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import { FormEvent, FormEventHandler, useState } from 'react';
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
  XCircleIcon,
  ChevronDownIcon
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
  filters: {
    search: string;
    role: string;
    status: string;
  };
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

export default function UserIndex({ users, filters, roles, statuses }: Props) {
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [roleFilter, setRoleFilter] = useState(filters.role || '');
  const [statusFilter, setStatusFilter] = useState(filters.status || '');
  const [isAdding, setIsAdding] = useState(false);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [showBulkSuspendModal, setShowBulkSuspendModal] = useState(false);
  const [bulkSuspendReason, setBulkSuspendReason] = useState('');

  const breadcrumbs = [
    { title: 'User Management' },
  ];

  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
    status: 'active',
  });

  const handleFilter = () => {
    router.get(route('admin.users.index'), {
      search: searchQuery,
      role: roleFilter,
      status: statusFilter,
    }, {
      preserveState: true,
      replace: true,
    });
  };

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

  const toggleSelectAll = () => {
    if (selectedIds.length === users.data.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(users.data.map(u => u.id));
    }
  };

  const toggleSelectUser = (id: number) => {
    if (selectedIds.includes(id)) {
      setSelectedIds(selectedIds.filter(i => i !== id));
    } else {
      setSelectedIds([...selectedIds, id]);
    }
  };

  const handleBulkDelete = () => {
    if (confirm(`Are you sure you want to delete ${selectedIds.length} users? This action cannot be undone.`)) {
      router.post(route('admin.users.bulk-delete'), { ids: selectedIds }, {
        onSuccess: () => setSelectedIds([]),
      });
    }
  };

  const handleBulkUnsuspend = () => {
    if (confirm(`Are you sure you want to unsuspend ${selectedIds.length} users?`)) {
      router.post(route('admin.users.bulk-unsuspend'), { ids: selectedIds }, {
        onSuccess: () => setSelectedIds([]),
      });
    }
  };

  const handleBulkSuspend = (e: FormEvent) => {
    e.preventDefault();
    router.post(route('admin.users.bulk-suspend'), {
      ids: selectedIds,
      reason: bulkSuspendReason
    }, {
      onSuccess: () => {
        setSelectedIds([]);
        setShowBulkSuspendModal(false);
        setBulkSuspendReason('');
      },
    });
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={breadcrumbs}
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
                <div className="flex items-center gap-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    All Users
                  </h3>
                  {selectedIds.length > 0 && (
                    <div className="flex items-center gap-2 animate-in fade-in slide-in-from-left-4 duration-300">
                      <span className="text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 px-2 py-1 rounded">
                        {selectedIds.length} selected
                      </span>
                      <div className="h-4 w-px bg-gray-300 dark:bg-gray-700 mx-1"></div>
                      <SecondaryButton
                        onClick={() => setShowBulkSuspendModal(true)}
                        className="!py-1 !px-3 text-xs"
                      >
                        Suspend
                      </SecondaryButton>
                      <SecondaryButton
                        onClick={handleBulkUnsuspend}
                        className="!py-1 !px-3 text-xs"
                      >
                        Unsuspend
                      </SecondaryButton>
                      <DangerButton
                        onClick={handleBulkDelete}
                        className="!py-1 !px-3 text-xs"
                      >
                        Delete
                      </DangerButton>
                    </div>
                  )}
                </div>
                <div className="flex flex-col md:flex-row gap-4 w-full md:w-auto">
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
                      onKeyUp={(e) => e.key === 'Enter' && handleFilter()}
                    />
                  </div>
                  <select
                    className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                    value={roleFilter}
                    onChange={(e) => {
                      setRoleFilter(e.target.value);
                      router.get(route('admin.users.index'), {
                        search: searchQuery,
                        role: e.target.value,
                        status: statusFilter,
                      }, { preserveState: true, replace: true });
                    }}
                  >
                    <option value="">All Roles</option>
                    {roles.map(role => (
                      <option key={role} value={role}>{role.charAt(0).toUpperCase() + role.slice(1)}</option>
                    ))}
                  </select>
                  <select
                    className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                    value={statusFilter}
                    onChange={(e) => {
                      setStatusFilter(e.target.value);
                      router.get(route('admin.users.index'), {
                        search: searchQuery,
                        role: roleFilter,
                        status: e.target.value,
                      }, { preserveState: true, replace: true });
                    }}
                  >
                    <option value="">All Statuses</option>
                    {statuses.map(status => (
                      <option key={status} value={status}>{status.charAt(0).toUpperCase() + status.slice(1)}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-gray-200 dark:border-gray-700">
                      <th className="py-3 px-4 w-10">
                        <input
                          type="checkbox"
                          className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                          checked={selectedIds.length === users.data.length && users.data.length > 0}
                          onChange={toggleSelectAll}
                        />
                      </th>
                      <th className="py-3 px-4 font-semibold text-sm">User</th>
                      <th className="py-3 px-4 font-semibold text-sm">Role</th>
                      <th className="py-3 px-4 font-semibold text-sm">Status</th>
                      <th className="py-3 px-4 font-semibold text-sm">Joined</th>
                      <th className="py-3 px-4 font-semibold text-sm text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {users.data.map((user) => {
                      const roleColor = roleColors[user.role] || roleColors['user'];
                      const statusColor = statusColors[user.status] || statusColors['active'];
                      const isSelected = selectedIds.includes(user.id);

                      return (
                        <tr
                          key={user.id}
                          className={`border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors ${isSelected ? 'bg-indigo-50/30 dark:bg-indigo-900/10' : ''}`}
                        >
                          <td className="py-4 px-4">
                            <input
                              type="checkbox"
                              className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                              checked={isSelected}
                              onChange={() => toggleSelectUser(user.id)}
                            />
                          </td>
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
                              <Link
                                href={route('admin.users.show', user.id)}
                                className="p-2 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-md text-indigo-600 dark:text-indigo-400"
                                title="View"
                              >
                                <ShieldCheckIcon className="w-5 h-5" />
                              </Link>
                              <Link
                                href={route('admin.users.edit', user.id)}
                                className="p-2 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-md text-blue-600 dark:text-blue-400"
                                title="Edit"
                              >
                                <PencilIcon className="w-5 h-5" />
                              </Link>
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
                    {users.data.length === 0 && (
                      <tr>
                        <td colSpan={6} className="py-12 text-center text-gray-500">
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

      <Modal show={showBulkSuspendModal} onClose={() => setShowBulkSuspendModal(false)}>
        <div className="p-6">
          <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
            Bulk Suspend Users
          </h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            You are about to suspend {selectedIds.length} users. Please provide a reason for this action.
          </p>

          <form onSubmit={handleBulkSuspend} className="mt-6">
            <div>
              <InputLabel htmlFor="bulk_reason" value="Suspension Reason" />
              <textarea
                id="bulk_reason"
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                rows={3}
                value={bulkSuspendReason}
                onChange={(e) => setBulkSuspendReason(e.target.value)}
                required
                placeholder="Violation of terms of service..."
              />
            </div>

            <div className="mt-6 flex justify-end gap-3">
              <SecondaryButton onClick={() => setShowBulkSuspendModal(false)}>
                Cancel
              </SecondaryButton>
              <DangerButton type="submit">
                Suspend Users
              </DangerButton>
            </div>
          </form>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}
