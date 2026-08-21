import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { SharedPageProps, TrialBalanceAccount } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    accounts: TrialBalanceAccount[];
    totalDebit: number;
    totalCredit: number;
    filters: { from: string; to: string };
}

const money = (value: number) => value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function TrialBalance() {
    const page = usePage();
    const { accounts, totalDebit, totalCredit, filters } = page.props as any as Props;
    const [loading, setLoading] = useState(false);
    const balanced = Math.abs(totalDebit - totalCredit) < 0.005;
    const submit = (event: React.FormEvent<HTMLFormElement>) => { event.preventDefault(); setLoading(true); router.get('/accounting/trial-balance', Object.fromEntries(new FormData(event.currentTarget).entries()), { preserveState: true, replace: true, onFinish: () => setLoading(false) }); };

    return <AuthenticatedLayout><Head title="Trial Balance" /><div className="mx-auto max-w-7xl"><div className="flex flex-col justify-between gap-5 border-b border-[#dce4d9] pb-7 sm:flex-row sm:items-end"><div><p className="text-sm font-semibold text-[#c17d35]">Accounting reports</p><h1 className="mt-2 text-3xl font-semibold tracking-tight">Trial Balance</h1><p className="mt-2 text-sm text-[#617268]">Posted account activity for the selected period.</p></div><Link href="/accounting/general-ledger" className="text-sm font-semibold text-[#386251]">General Ledger</Link></div><form onSubmit={submit} className="mt-8 grid gap-3 border border-[#dce4d9] bg-[#f8faf6] p-4 sm:grid-cols-3"><input type="date" name="from" defaultValue={filters.from} className="rounded-lg border-[#ccd8cb] bg-white text-sm" /><input type="date" name="to" defaultValue={filters.to} className="rounded-lg border-[#ccd8cb] bg-white text-sm" /><button type="submit" disabled={loading} className="rounded-lg bg-[#17352d] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">{loading ? 'Loading...' : 'Apply date range'}</button></form><div className={`mt-6 flex flex-col justify-between gap-3 border p-5 sm:flex-row sm:items-center ${balanced ? 'border-[#9fc19c] bg-[#eef6eb]' : 'border-[#d99c9c] bg-[#fff0f0]'}`}><div><p className="font-semibold">{balanced ? 'Trial balance is balanced' : 'Trial balance is unbalanced'}</p><p className="mt-1 text-sm text-[#617268]">{balanced ? 'Total debit equals total credit.' : 'Review the posted journal data before relying on this report.'}</p></div><div className="flex gap-6 text-right text-sm"><div><p className="text-xs text-[#789083]">Total debit</p><p className="mt-1 font-semibold">{money(totalDebit)}</p></div><div><p className="text-xs text-[#789083]">Total credit</p><p className="mt-1 font-semibold">{money(totalCredit)}</p></div></div></div><div className="mt-6 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]"><table className="w-full min-w-[650px] text-left text-sm"><thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]"><tr><th className="px-5 py-4">Account code</th><th className="px-5 py-4">Account name</th><th className="px-5 py-4">Type</th><th className="px-5 py-4 text-right">Debit</th><th className="px-5 py-4 text-right">Credit</th></tr></thead><tbody className="divide-y divide-[#e5ebe4]">{accounts.map((account) => <tr key={account.id}><td className="px-5 py-4 font-mono text-xs">{account.code}</td><td className="px-5 py-4 font-medium">{account.name}</td><td className="px-5 py-4 capitalize text-[#617268]">{account.type}</td><td className="px-5 py-4 text-right">{money(account.debit)}</td><td className="px-5 py-4 text-right">{money(account.credit)}</td></tr>)}</tbody></table>{accounts.length === 0 && <p className="px-5 py-10 text-center text-sm text-[#789083]">No active accounts have posted activity in this date range.</p>}</div></div></AuthenticatedLayout>;
}