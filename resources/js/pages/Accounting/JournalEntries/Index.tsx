import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../../layouts/AuthenticatedLayout.tsx';
import type { JournalEntry, Paginated, SharedPageProps } from '../../../types/index.ts';

interface Props extends SharedPageProps {
    journals: Paginated<JournalEntry>;
}

const money = (value: string | number) => Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function JournalEntriesIndex() {
    const page = usePage();
    const { journals, auth } = page.props as any as Props;
    const canCreate = auth.user?.role === 'accountant';

    return (
        <AuthenticatedLayout>
            <Head title="Journal Entries" />
            <div className="mx-auto max-w-7xl">
                <div className="flex flex-col justify-between gap-5 border-b border-[#dce4d9] pb-7 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold text-[#c17d35]">Accounting</p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight">Journal Entries</h1>
                        <p className="mt-2 text-sm text-[#617268]">Review balanced journal activity across the workspace.</p>
                    </div>
                    {canCreate && <Link href="/accounting/journal-entries/create" className="w-fit rounded-lg bg-[#17352d] px-4 py-3 text-sm font-semibold text-white hover:bg-[#285348]">+ Create journal</Link>}
                </div>

                <div className="mt-8 overflow-x-auto border border-[#dce4d9] bg-[#f8faf6]">
                    <table className="w-full min-w-[850px] text-left text-sm">
                        <thead className="border-b border-[#dce4d9] text-xs uppercase tracking-[0.12em] text-[#789083]"><tr><th className="px-5 py-4">Journal number</th><th className="px-5 py-4">Date</th><th className="px-5 py-4">Description</th><th className="px-5 py-4">Total amount</th><th className="px-5 py-4">Status</th><th className="px-5 py-4">Created by</th><th className="px-5 py-4">Action</th></tr></thead>
                        <tbody className="divide-y divide-[#e5ebe4]">
                            {journals.data.map((journal) => <tr key={journal.id} className="hover:bg-[#f0f5ed]"><td className="px-5 py-4 font-mono text-xs font-semibold">{journal.journal_number}</td><td className="px-5 py-4 text-[#617268]">{journal.transaction_date}</td><td className="max-w-[220px] truncate px-5 py-4">{journal.description || 'No description'}</td><td className="px-5 py-4 font-medium">{money(journal.lines_sum_debit ?? 0)}</td><td className="px-5 py-4"><span className="rounded-full bg-[#e3eee1] px-2.5 py-1 text-xs font-medium capitalize text-[#386251]">{journal.status}</span></td><td className="px-5 py-4 text-[#617268]">{journal.creator.name}</td><td className="px-5 py-4"><Link href={`/accounting/journal-entries/${journal.id}`} className="font-semibold text-[#386251] hover:underline">View</Link></td></tr>)}
                        </tbody>
                    </table>
                </div>
                <p className="mt-4 text-xs text-[#789083]">{journals.total} journal entries</p>
            </div>
        </AuthenticatedLayout>
    );
}