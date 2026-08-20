import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { ChartOfAccount, SharedPageProps } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    accounts: ChartOfAccount[];
}

const typeStyles: Record<ChartOfAccount['type'], string> = { asset: 'bg-[#e3eee1] text-[#386251]', liability: 'bg-[#f8e9d8] text-[#9b5e23]', equity: 'bg-[#e5e1f0] text-[#5d4b81]', revenue: 'bg-[#dcebf0] text-[#2f6577]', expense: 'bg-[#f5dfdf] text-[#994c4c]' };

export default function ChartOfAccounts() {
    const page = usePage();
    const { accounts } = page.props as any as Props;
    return <AuthenticatedLayout><Head title="Chart of Accounts" /><div className="mx-auto max-w-7xl"><div className="flex flex-col justify-between gap-5 border-b border-[#dce4d9] pb-7 sm:flex-row sm:items-end"><div><p className="text-sm font-semibold text-[#c17d35]">Accounting</p><h1 className="mt-2 text-3xl font-semibold tracking-tight">Chart of Accounts</h1><p className="mt-2 text-sm text-[#617268]">The account structure that will organize every journal entry.</p></div><button type="button" className="w-fit rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#285348]">+ Add account</button></div><div className="mt-8 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]"><table className="w-full min-w-[700px] text-left text-sm"><thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]"><tr><th className="px-5 py-4 font-semibold">Code</th><th className="px-5 py-4 font-semibold">Account name</th><th className="px-5 py-4 font-semibold">Type</th><th className="px-5 py-4 font-semibold">Parent account</th><th className="px-5 py-4 font-semibold">Status</th></tr></thead><tbody className="divide-y divide-[#e5ebe4]">{accounts.map((account: ChartOfAccount) => <tr key={account.id} className="transition hover:bg-[#f0f5ed]"><td className="px-5 py-4 font-mono text-xs text-[#617268]">{account.code}</td><td className="px-5 py-4 font-medium">{account.name}</td><td className="px-5 py-4"><span className={`rounded-full px-2.5 py-1 text-xs font-medium capitalize ${typeStyles[account.type]}`}>{account.type}</span></td><td className="px-5 py-4 text-[#617268]">{account.parent?.name ?? 'Top level account'}</td><td className="px-5 py-4"><span className="inline-flex items-center gap-2 text-xs text-[#386251]"><span className="h-1.5 w-1.5 rounded-full bg-[#6d9e69]" />Active</span></td></tr>)}</tbody></table></div><p className="mt-4 text-xs text-[#789083]">{accounts.length} seeded accounts · Ready for future journal workflows</p></div></AuthenticatedLayout>;
}