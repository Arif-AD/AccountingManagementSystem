export type UserRole = 'accountant' | 'manager';

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
}

export interface ChartOfAccount {
    id: number;
    code: string;
    name: string;
    type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
    parent_id: number | null;
    description: string | null;
    is_active: boolean;
    parent?: Pick<ChartOfAccount, 'id' | 'code' | 'name'> | null;
}

export interface SharedPageProps {
    auth: {
        user: User | null;
    };
    errors: Record<string, string>;
}