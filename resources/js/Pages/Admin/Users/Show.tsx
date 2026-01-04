import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import { ArrowLeftIcon, PencilIcon, EnvelopeIcon, ShieldCheckIcon, CalendarIcon, CheckCircleIcon } from '@heroicons/react/24/outline';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  status: string;
  is_admin: boolean;
  phone: string | null;
  notes: string | null;
  last_login_at: string | null;
  last_login_ip: string | null;
  two_factor_enabled: boolean;
  suspended_at: string | null;
  suspended_reason: string | null;
  created_at: string;
}

interface Props {
  user: User;
}

const roleColors: Record<string, { bg: string; text: string; darkBg: string; darkText: string }> = {
  'super-admin': { bg: 'bg-red-100', text: 'text-red-700', darkBg: 'dark:bg-red-900/30', darkText: 'dark:text-red-400' },
  'admin': { bg: 'bg-indigo-100', text: 'text-indigo-700', darkBg: 'dark:bg-indigo-900/30', darkText: 'dark:text-indigo-400' },
  'moderator': { bg: 'bg-purple-100', text: 'text-purple-700', darkBg: 'dark:bg-purple-900/30', darkText: 'dark:text-purple-400' },
  'user': { bg: 'bg-blue-100', text: 'text-blue-700', darkBg: 'dark:bg-blue-900/30', darkText: 'dark:text-blue-400' },
};

const statusColors: Record<string, { bg: string; text: string; darkBg: string; darkText: string; dot: string }> = {
  'active': { bg: 'bg-green-100', text: 'text-green-700', darkBg: 'dark:bg-green-900/30', darkText: 'dark:text-green-400', dot: 'bg-green-600' },
  'suspended': { bg: 'bg-yellow-100', text: 'text-yellow-700', darkBg: 'dark:bg-yellow-900/30', darkText: 'dark:text-yellow-400', dot: 'bg-yellow-600' },
  'inactive': { bg: 'bg-gray-100', text: 'text-gray-700', darkBg: 'dark:bg-gray-900/30', darkText: 'dark:text-gray-400', dot: 'bg-gray-600' },
};

export default function UserShow({ user }: Props) {
  const roleColor = roleColors[user.role] || roleColors['user'];
  const statusColor = statusColors[user.status] || statusColors['active'];

  const breadcrumbs = [
    { title: 'User Management', url: route('admin.users.index') },
    { title: user.name },
  ];

  return (
    <AuthenticatedLayout
      breadcrumbs={breadcrumbs}
      header={
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <a
              href={route('admin.users.index')}
              className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
            >
              <ArrowLeftIcon className="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </a>
            <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
              {user.name}
            </h2>
          </div>
          <div className="flex gap-2">
            <a
              href={route('admin.users.edit', user.id)}
              className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition-colors"
            >
              <PencilIcon className="w-5 h-5" />
              Edit
            </a>
          </div>
        </div>
      }
    >
      <Head title={`User: ${user.name}`} />

      <div className="py-12">
        <div className="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
          <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
            <div className="p-6 sm:p-8">
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-6 border-b pb-2 dark:border-gray-700">
                Account Information
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Full Name
                  </label>
                  <p className="text-lg font-semibold text-gray-900 dark:text-white">
                    {user.name}
                    {user.is_admin && (
                      <span className="ml-2 inline-block px-2 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-xs font-semibold rounded">ADMIN</span>
                    )}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Email Address
                  </label>
                  <p className="flex items-center gap-2 text-gray-900 dark:text-white">
                    <EnvelopeIcon className="w-4 h-4 text-gray-400" />
                    <a href={`mailto:${user.email}`} className="hover:text-indigo-600 dark:hover:text-indigo-400">
                      {user.email}
                    </a>
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Phone Number
                  </label>
                  <p className="text-gray-900 dark:text-white">
                    {user.phone || 'Not provided'}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Role
                  </label>
                  <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ${roleColor.bg} ${roleColor.text} ${roleColor.darkBg} ${roleColor.darkText}`}>
                    <ShieldCheckIcon className="w-4 h-4" />
                    {user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                  </span>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Status
                  </label>
                  <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ${statusColor.bg} ${statusColor.text} ${statusColor.darkBg} ${statusColor.darkText}`}>
                    <span className={`h-2 w-2 rounded-full ${statusColor.dot}`}></span>
                    {user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                  </span>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Two-Factor Authentication
                  </label>
                  <p className="flex items-center gap-2 text-gray-900 dark:text-white">
                    {user.two_factor_enabled ? (
                      <span className="text-green-600 dark:text-green-400 flex items-center gap-1">
                        <CheckCircleIcon className="w-4 h-4" /> Enabled
                      </span>
                    ) : (
                      <span className="text-gray-500 dark:text-gray-400">Disabled</span>
                    )}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
              <div className="p-6 sm:p-8">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b pb-2 dark:border-gray-700">
                  Activity & History
                </h3>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Member Since
                    </label>
                    <p className="flex items-center gap-2 text-gray-900 dark:text-white">
                      <CalendarIcon className="w-4 h-4 text-gray-400" />
                      {new Date(user.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                      })}
                    </p>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Last Login
                    </label>
                    <p className="text-gray-900 dark:text-white">
                      {user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never'}
                    </p>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Last Login IP
                    </label>
                    <p className="text-gray-900 dark:text-white">
                      {user.last_login_ip || 'N/A'}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
              <div className="p-6 sm:p-8">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4 border-b pb-2 dark:border-gray-700">
                  Notes & Admin Info
                </h3>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Admin Notes
                    </label>
                    <p className="text-gray-900 dark:text-white whitespace-pre-wrap">
                      {user.notes || 'No notes available.'}
                    </p>
                  </div>

                  {user.status === 'suspended' && (
                    <div className="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                      <label className="block text-sm font-medium text-yellow-800 dark:text-yellow-300">
                        Suspension Reason
                      </label>
                      <p className="text-yellow-700 dark:text-yellow-400 text-sm mt-1">
                        {user.suspended_reason || 'No reason provided.'}
                      </p>
                      <p className="text-yellow-600 dark:text-yellow-500 text-xs mt-2">
                        Suspended on: {user.suspended_at ? new Date(user.suspended_at).toLocaleString() : 'Unknown'}
                      </p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
