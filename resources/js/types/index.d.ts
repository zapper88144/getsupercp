export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    is_admin: boolean;
    role?: 'super-admin' | 'admin' | 'moderator' | 'user';
    status?: 'active' | 'suspended' | 'inactive';
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
