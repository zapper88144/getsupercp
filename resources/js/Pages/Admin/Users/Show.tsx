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

  return (
    <AuthenticatedLayout
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
          <a
            href={route('admin.users.edit', user.id)}
            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition-colors"
          >
            <PencilIcon className="w-5 h-5" />
            Edit
          </a>
        </div>
      }
    >
      <Head title={`User: ${user.name}`} />

      <div className="py-12">
        <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
          <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
            <div className="p-6 sm:p-8">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Role
                  </label>
                  <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ${roleColor.bg} ${roleColor.text} ${roleColor.darkBg} ${roleColor.darkText}`}>
                    <ShieldCheckIcon className="w-4 h-4" />
                    {user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                  </span>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Status
                  </label>
                  <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ${statusColor.bg} ${statusColor.text} ${statusColor.darkBg} ${statusColor.darkText}`}>
                    <span className={`h-2 w-2 rounded-full ${statusColor.dot}`}></span>
                    {user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                  </span>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
