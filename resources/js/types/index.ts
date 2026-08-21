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
    [key: string]: any;
}

export interface JournalEntryLine {
    id: number;
    chart_of_account_id: number;
    description: string | null;
    debit: string;
    credit: string;
    account: Pick<ChartOfAccount, 'id' | 'code' | 'name'>;
}

export interface JournalEntry {
    id: number;
    journal_number: string;
    transaction_date: string;
    description: string | null;
    status: 'draft' | 'pending' | 'approved' | 'posted';
    created_by: number;
    created_at: string;
    creator: Pick<User, 'id' | 'name'>;
    lines?: JournalEntryLine[];
    lines_sum_debit?: string;
    lines_sum_credit?: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface ReportAccount {
    id: number;
    code: string;
    name: string;
    type: ChartOfAccount['type'];
}

export interface LedgerTransaction {
    date: string;
    journal_number: string;
    description: string | null;
    debit: number;
    credit: number;
    running_balance: number;
}

export interface TrialBalanceAccount extends ReportAccount {
    debit: number;
    credit: number;
}

export interface StatementAccount extends ReportAccount {
    balance: number;
}