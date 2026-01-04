export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    is_admin: boolean;
    role?: 'super-admin' | 'admin' | 'moderator' | 'user';
    status?: 'active' | 'suspended' | 'inactive';
    two_factor_enabled: boolean;
    last_login_at?: string;
    last_login_ip?: string;
    phone?: string;
    notes?: string;
    suspended_at?: string;
    suspended_reason?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
