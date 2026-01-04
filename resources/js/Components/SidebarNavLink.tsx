import { Link } from '@inertiajs/react';

interface SidebarNavLinkProps {
    href: string;
    active: boolean;
    icon: string;
    label: string;
    open: boolean;
}

export default function SidebarNavLink({
    href,
    active,
    icon,
    label,
    open,
}: SidebarNavLinkProps) {
    return (
        <Link
            href={href}
            className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors ${
                active
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
            }`}
        >
            <svg
                className="h-5 w-5 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d={icon}
                />
            </svg>
            {open && <span className="truncate">{label}</span>}
        </Link>
    );
}
