import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import { useState } from 'react';
import {
  UserIcon,
  MagnifyingGlassIcon,
  CalendarIcon,
  CheckCircleIcon,
  XCircleIcon,
  ComputerDesktopIcon,
  GlobeAltIcon,
  ClockIcon
} from '@heroicons/react/24/outline';

interface User {
  id: number;
  name: string;
  email: string;
}

interface LoginActivity {
  id: number;
  user_id: number | null;
  email: string;
  ip_address: string;
  user_agent: string;
  status: 'success' | 'failed';
  failure_reason: string | null;
  login_at: string;
  logout_at: string | null;
  user?: User;
}

interface PaginatedActivities {
  data: LoginActivity[];
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
  activities: PaginatedActivities;
  filters: {
    search: string;
    status: string;
  };
}

const statusColors: Record<string, { bg: string; text: string; darkBg: string; darkText: string; icon: typeof CheckCircleIcon }> = {
  'success': { bg: 'bg-green-100', text: 'text-green-700', darkBg: 'dark:bg-green-900/30', darkText: 'dark:text-green-400', icon: CheckCircleIcon },
  'failed': { bg: 'bg-red-100', text: 'text-red-700', darkBg: 'dark:bg-red-900/30', darkText: 'dark:text-red-400', icon: XCircleIcon },
};

export default function LoginActivityIndex({ activities, filters }: Props) {
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [statusFilter, setStatusFilter] = useState(filters.status || '');

  const breadcrumbs = [
    { title: 'Security' },
    { title: 'Login Activity' },
  ];

  const handleFilter = () => {
    router.get(route('admin.login-activities.index'), {
      search: searchQuery,
      status: statusFilter,
    }, {
      preserveState: true,
      replace: true,
    });
  };

  return (
    <AuthenticatedLayout
      breadcrumbs={breadcrumbs}
      header={
        <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Login Activity Audit Log
        </h2>
      }
    >
      <Head title="Login Activity" />

      <div className="py-12">
        <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
          <div className="bg-white shadow sm:rounded-lg dark:bg-gray-800 overflow-hidden">
            <div className="p-6">
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                  Authentication History
                </h3>
                <div className="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                  <div className="relative w-full md:w-64">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                    </div>
                    <TextInput
                      type="text"
                      className="block w-full pl-10 pr-3 py-2"
                      placeholder="Search email or IP..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      onKeyUp={(e) => e.key === 'Enter' && handleFilter()}
                    />
                  </div>
                  <select
                    className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                    value={statusFilter}
                    onChange={(e) => {
                      setStatusFilter(e.target.value);
                      router.get(route('admin.login-activities.index'), {
                        search: searchQuery,
                        status: e.target.value,
                      }, { preserveState: true, replace: true });
                    }}
                  >
                    <option value="">All Statuses</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                  </select>
                </div>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-gray-200 dark:border-gray-700">
                      <th className="py-3 px-4 font-semibold text-sm">User / Email</th>
                      <th className="py-3 px-4 font-semibold text-sm">Status</th>
                      <th className="py-3 px-4 font-semibold text-sm">IP Address</th>
                      <th className="py-3 px-4 font-semibold text-sm">Device / Browser</th>
                      <th className="py-3 px-4 font-semibold text-sm">Time</th>
                      <th className="py-3 px-4 font-semibold text-sm">Session</th>
                    </tr>
                  </thead>
                  <tbody>
                    {activities.data.map((activity) => {
                      const statusColor = statusColors[activity.status];
                      const StatusIcon = statusColor.icon;

                      return (
                        <tr
                          key={activity.id}
                          className="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                          <td className="py-4 px-4">
                            <div className="flex items-center gap-3">
                              <div className="p-2 bg-gray-100 dark:bg-gray-900/30 rounded-lg">
                                <UserIcon className="w-5 h-5 text-gray-600 dark:text-gray-400" />
                              </div>
                              <div>
                                <div className="font-bold text-gray-900 dark:text-white">
                                  {activity.user ? activity.user.name : 'Guest'}
                                </div>
                                <div className="text-xs text-gray-500">
                                  {activity.email}
                                </div>
                              </div>
                            </div>
                          </td>
                          <td className="py-4 px-4">
                            <div className="flex flex-col gap-1">
                              <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${statusColor.bg} ${statusColor.text} ${statusColor.darkBg} ${statusColor.darkText}`}>
                                <StatusIcon className="w-3.5 h-3.5" />
                                {activity.status.toUpperCase()}
                              </span>
                              {activity.failure_reason && (
                                <span className="text-[10px] text-red-500 dark:text-red-400 max-w-[150px] truncate" title={activity.failure_reason}>
                                  {activity.failure_reason}
                                </span>
                              )}
                            </div>
                          </td>
                          <td className="py-4 px-4">
                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                              <GlobeAltIcon className="w-4 h-4" />
                              {activity.ip_address}
                            </div>
                          </td>
                          <td className="py-4 px-4">
                            <div className="flex items-center gap-2 text-xs text-gray-500 max-w-[200px]">
                              <ComputerDesktopIcon className="w-4 h-4 shrink-0" />
                              <span className="truncate" title={activity.user_agent}>
                                {activity.user_agent}
                              </span>
                            </div>
                          </td>
                          <td className="py-4 px-4">
                            <div className="flex flex-col text-xs text-gray-500">
                              <div className="flex items-center gap-1 font-medium text-gray-700 dark:text-gray-300">
                                <CalendarIcon className="w-3.5 h-3.5" />
                                {new Date(activity.login_at).toLocaleDateString()}
                              </div>
                              <div className="flex items-center gap-1 mt-0.5">
                                <ClockIcon className="w-3.5 h-3.5" />
                                {new Date(activity.login_at).toLocaleTimeString()}
                              </div>
                            </div>
                          </td>
                          <td className="py-4 px-4">
                            {activity.status === 'success' && (
                              <div className="text-xs">
                                {activity.logout_at ? (
                                  <span className="text-gray-500">
                                    Ended: {new Date(activity.logout_at).toLocaleTimeString()}
                                  </span>
                                ) : (
                                  <span className="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-medium">
                                    <span className="h-1.5 w-1.5 rounded-full bg-green-600 animate-pulse"></span>
                                    Active
                                  </span>
                                )}
                              </div>
                            )}
                          </td>
                        </tr>
                      );
                    })}
                    {activities.data.length === 0 && (
                      <tr>
                        <td colSpan={6} className="py-12 text-center text-gray-500">
                          <div className="flex flex-col items-center gap-2">
                            <ClockIcon className="w-12 h-12 text-gray-300" />
                            <span>No login activities found.</span>
                          </div>
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              {/* Pagination */}
              {activities.links.length > 3 && (
                <div className="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-6">
                  <div className="flex flex-1 justify-between sm:hidden">
                    <Link
                      href={activities.links[0].url || '#'}
                      className="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                      Previous
                    </Link>
                    <Link
                      href={activities.links[activities.links.length - 1].url || '#'}
                      className="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                      Next
                    </Link>
                  </div>
                  <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                      <p className="text-sm text-gray-700 dark:text-gray-400">
                        Showing <span className="font-medium">{(activities.current_page - 1) * 20 + 1}</span> to{' '}
                        <span className="font-medium">
                          {Math.min(activities.current_page * 20, activities.total)}
                        </span>{' '}
                        of <span className="font-medium">{activities.total}</span> results
                      </p>
                    </div>
                    <div>
                      <nav className="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                        {activities.links.map((link, i) => (
                          <Link
                            key={i}
                            href={link.url || '#'}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 ${
                              link.active
                                ? 'z-10 bg-indigo-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-offset-0 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700'
                            } ${i === 0 ? 'rounded-l-md' : ''} ${
                              i === activities.links.length - 1 ? 'rounded-r-md' : ''
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
