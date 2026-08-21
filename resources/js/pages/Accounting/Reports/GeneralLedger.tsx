import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { LedgerTransaction, ReportAccount, SharedPageProps } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    accounts: ReportAccount[];
    account: ReportAccount | null;
    transactions: LedgerTransaction[];
    openingBalance: number;
    totalDebit: number;
    totalCredit: number;
    endingBalance: number;
    filters: { account_id?: number; from: string; to: string };
}

const money = (value: number) => value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function GeneralLedger() {
    const page = usePage();
    const { accounts, account, transactions, openingBalance, totalDebit, totalCredit, endingBalance, filters } = page.props as any as Props;
    const [loading, setLoading] = useState(false);
    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(event.currentTarget).entries());
        setLoading(true);
        router.get('/accounting/general-ledger', values, { preserveState: true, replace: true, onFinish: () => setLoading(false) });
    };

    return <AuthenticatedLayout><Head title="General Ledger" /><div className="mx-auto max-w-7xl"><div className="flex flex-col justify-between gap-5 border-b border-[#dce4d9] pb-7 sm:flex-row sm:items-end"><div><p className="text-sm font-semibold text-[#c17d35]">Accounting reports</p><h1 className="mt-2 text-3xl font-semibold tracking-tight">General Ledger</h1><p className="mt-2 text-sm text-[#617268]">Posted activity and running balance for one account.</p></div><Link href="/accounting/trial-balance" className="text-sm font-semibold text-[#386251]">Trial Balance</Link></div>
        <form onSubmit={submit} className="mt-8 grid gap-3 border border-[#dce4d9] bg-[#f8faf6] p-4 sm:grid-cols-2 lg:grid-cols-4"><select name="account_id" defaultValue={filters.account_id ?? ''} className="rounded-lg border-[#ccd8cb] bg-white text-sm"><option value="">Select account</option>{accounts.map((item) => <option key={item.id} value={item.id}>{item.code} · {item.name}</option>)}</select><input type="date" name="from" defaultValue={filters.from} className="rounded-lg border-[#ccd8cb] bg-white text-sm" /><input type="date" name="to" defaultValue={filters.to} className="rounded-lg border-[#ccd8cb] bg-white text-sm" /><button type="submit" disabled={loading} className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">{loading ? 'Loading...' : 'Apply filters'}</button></form>
        {account ? <><div className="mt-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-xs uppercase tracking-[0.12em] text-[#789083]">Selected account</p><p className="mt-1 font-semibold">{account.code} · {account.name}</p></div><div className="grid grid-cols-2 gap-5 text-right sm:grid-cols-4"><div><p className="text-xs text-[#789083]">Opening</p><p className="mt-1 font-semibold">{money(openingBalance)}</p></div><div><p className="text-xs text-[#789083]">Debit</p><p className="mt-1 font-semibold">{money(totalDebit)}</p></div><div><p className="text-xs text-[#789083]">Credit</p><p className="mt-1 font-semibold">{money(totalCredit)}</p></div><div><p className="text-xs text-[#789083]">Ending</p><p className="mt-1 font-semibold">{money(endingBalance)}</p></div></div></div><div className="mt-6 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]"><table className="w-full min-w-[750px] text-left text-sm"><thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]"><tr><th className="px-5 py-4">Date</th><th className="px-5 py-4">Journal number</th><th className="px-5 py-4">Description</th><th className="px-5 py-4 text-right">Debit</th><th className="px-5 py-4 text-right">Credit</th><th className="px-5 py-4 text-right">Running balance</th></tr></thead><tbody className="divide-y divide-[#e5ebe4]">{transactions.map((transaction) => <tr key={`${transaction.journal_number}-${transaction.date}`}><td className="px-5 py-4 text-[#617268]">{transaction.date}</td><td className="px-5 py-4 font-mono text-xs">{transaction.journal_number}</td><td className="px-5 py-4">{transaction.description || '-'}</td><td className="px-5 py-4 text-right">{money(transaction.debit)}</td><td className="px-5 py-4 text-right">{money(transaction.credit)}</td><td className="px-5 py-4 text-right font-medium">{money(transaction.running_balance)}</td></tr>)}</tbody></table>{transactions.length === 0 && <p className="px-5 py-10 text-center text-sm text-[#789083]">No posted transactions found for this account and date range.</p>}</div></> : <div className="mt-8 border border-dashed border-[#b9cbb9] px-6 py-12 text-center text-sm text-[#789083]">Select an active Chart of Account to view its ledger.</div>}
    </div></AuthenticatedLayout>;
}