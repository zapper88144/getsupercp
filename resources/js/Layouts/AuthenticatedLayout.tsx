import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import SidebarNavLink from '@/Components/SidebarNavLink';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage().props.auth.user;

    const [sidebarOpen, setSidebarOpen] = useState(true);

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            {/* Sidebar */}
            <aside
                className={`fixed left-0 top-0 z-40 h-screen transition-all duration-300 bg-gray-900 dark:bg-gray-950 border-r border-gray-800 ${
                    sidebarOpen ? 'w-64' : 'w-20'
                }`}
            >
                {/* Logo */}
                <div className="flex h-16 shrink-0 items-center justify-between px-4">
                    <Link href="/">
                        <ApplicationLogo className={`h-8 w-auto fill-current text-white transition-all ${
                            sidebarOpen ? 'opacity-100' : 'opacity-0'
                        }`} />
                    </Link>
                    <button
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                        className="rounded-lg p-1.5 hover:bg-gray-800 lg:hidden"
                    >
                        <svg
                            className="h-6 w-6 text-gray-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d={sidebarOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'}
                            />
                        </svg>
                    </button>
                </div>

                {/* Navigation Links */}
                <nav className="space-y-1 px-2 py-4">
                    <SidebarNavLink
                        href={route('dashboard')}
                        active={route().current('dashboard')}
                        icon="M3 12l2-12m9 11l4 5m7-15l-3.87 12.804c-.5 1.517-.923 2.948-1.85 5.48m-5.378-5.361L9 7m4 6v6m6-7v7"
                        label="Dashboard"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('web-domains.index')}
                        active={route().current('web-domains.index')}
                        icon="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        label="Web Domains"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('databases.index')}
                        active={route().current('databases.index')}
                        icon="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7m0 0V5c0-2.21-3.582-4-8-4S4 2.79 4 5v2m0 0a1 1 0 100 2 1 1 0 000-2m8 14a1 1 0 100 2 1 1 0 000-2"
                        label="Databases"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('ftp-users.index')}
                        active={route().current('ftp-users.index')}
                        icon="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        label="FTP Users"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('cron-jobs.index')}
                        active={route().current('cron-jobs.index')}
                        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        label="Cron Jobs"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('dns-zones.index')}
                        active={route().current('dns-zones.index')}
                        icon="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.658 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                        label="DNS"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('email-accounts.index')}
                        active={route().current('email-accounts.index')}
                        icon="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                        label="Email"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('file-manager.index')}
                        active={route().current('file-manager.index')}
                        icon="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                        label="File Manager"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('logs.index')}
                        active={route().current('logs.index')}
                        icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        label="Logs"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('services.index')}
                        active={route().current('services.index')}
                        icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        label="Services"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('backups.index')}
                        active={route().current('backups.index')}
                        icon="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        label="Backups"
                        open={sidebarOpen}
                    />
                    <SidebarNavLink
                        href={route('monitoring.index')}
                        active={route().current('monitoring.index')}
                        icon="M13 10V3L4 14h7v7l9-11h-7z"
                        label="Monitoring"
                        open={sidebarOpen}
                    />
                </nav>

                {/* User Section */}
                <div className="absolute bottom-0 left-0 right-0 border-t border-gray-800 p-4">
                    {sidebarOpen ? (
                        <Dropdown>
                            <Dropdown.Trigger>
                                <button className="w-full rounded-lg bg-gray-800 px-3 py-2 text-sm text-left text-gray-200 hover:bg-gray-700 transition-colors">
                                    <div className="font-medium truncate">{user.name}</div>
                                    <div className="text-xs text-gray-400 truncate">{user.email}</div>
                                </button>
                            </Dropdown.Trigger>
                            <Dropdown.Content>
                                <Dropdown.Link href={route('profile.edit')}>
                                    Profile
                                </Dropdown.Link>
                                <Dropdown.Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    ) : (
                        <div className="flex justify-center">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button className="rounded-full bg-gray-800 p-2 text-gray-200 hover:bg-gray-700">
                                        <svg
                                            className="h-5 w-5"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fillRule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </Dropdown.Trigger>
                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        Profile
                                    </Dropdown.Link>
                                    <Dropdown.Link
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    )}
                </div>
            </aside>

            {/* Main Content */}
            <div className={`transition-all duration-300 ${sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'}`}>
                {/* Top Bar */}
                <div className="sticky top-0 z-30 flex h-16 items-center border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-800 lg:hidden">
                    <button
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                        className="rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg
                            className="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                    <div className="ml-auto">
                        <Dropdown>
                            <Dropdown.Trigger>
                                <button className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300">
                                    {user.name}
                                    <svg
                                        className="-me-0.5 ms-2 h-4 w-4"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </Dropdown.Trigger>
                            <Dropdown.Content>
                                <Dropdown.Link href={route('profile.edit')}>
                                    Profile
                                </Dropdown.Link>
                                <Dropdown.Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                >
                                    Log Out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </div>

                {header && (
                    <header className="bg-white shadow dark:bg-gray-800">
                        <div className="px-4 py-6 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </header>
                )}

                <main className="p-4 sm:p-6 lg:p-8">{children}</main>
            </div>
        </div>
    );
}
