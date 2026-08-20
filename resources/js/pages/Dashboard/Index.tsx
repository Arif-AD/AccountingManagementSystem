import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.tsx';
import type { SharedPageProps } from '../../types/index.ts';

interface DashboardProps extends SharedPageProps {
    summary: {
        transactions: number;
        revenue: number;
        expenses: number;
        netProfit: number;
    };
}

export default function Dashboard({ summary }: DashboardProps) {
    const page = usePage();
    const { auth } = page.props as any as SharedPageProps;
    const cards = [['Total transactions', summary.transactions.toLocaleString(), 'This reporting period'], ['Total revenue', `$${summary.revenue.toLocaleString()}`, 'Awaiting journal data'], ['Total expenses', `$${summary.expenses.toLocaleString()}`, 'Awaiting journal data'], ['Net profit', `$${summary.netProfit.toLocaleString()}`, 'Will update with activity']];

    return <AuthenticatedLayout><Head title="Dashboard" /><div className="mx-auto max-w-7xl"><div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p className="text-sm font-semibold text-[#c17d35]">Overview</p><h1 className="mt-2 text-3xl font-semibold tracking-tight lg:text-4xl">Good morning, {auth.user?.name.split(' ')[0]}.</h1><p className="mt-2 text-sm text-[#617268]">Here is the pulse of your accounting workspace.</p></div><span className="w-fit rounded-full bg-[#e3eee1] px-3 py-1.5 text-xs font-medium capitalize text-[#386251]">{auth.user?.role} access</span></div><div className="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{cards.map(([label, value, note]) => <div key={label} className="border-t-2 border-[#b8cbb9] bg-[#f8faf6] p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[#789083]">{label}</p><p className="mt-5 text-3xl font-semibold tracking-tight">{value}</p><p className="mt-2 text-xs text-[#789083]">{note}</p></div>)}</div><section className="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]"><div className="min-h-64 bg-[#17352d] p-7 text-[#edf4ec]"><p className="text-xs font-semibold uppercase tracking-[0.16em] text-[#9cb8a8]">Day 1 foundation</p><h2 className="mt-4 max-w-md text-2xl font-semibold leading-tight">Your ledger is ready for its first transaction.</h2><p className="mt-3 max-w-lg text-sm leading-6 text-[#b4c9ba]">Chart of Accounts and role-aware access are in place. Journal workflows and reports will build on this foundation.</p></div><div className="border border-[#dce4d9] bg-[#f8faf6] p-7"><p className="text-xs font-semibold uppercase tracking-[0.16em] text-[#789083]">Quick start</p><h2 className="mt-3 text-xl font-semibold">Set up your accounts</h2><p className="mt-2 text-sm leading-6 text-[#617268]">Review the seeded accounts before journal entry work begins.</p><a href="/accounting/chart-of-accounts" className="mt-6 inline-block text-sm font-semibold text-[#386251] underline decoration-[#d9e96d] decoration-2 underline-offset-4">View Chart of Accounts</a></div></section></div></AuthenticatedLayout>;
}